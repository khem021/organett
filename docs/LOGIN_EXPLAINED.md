# How ORGANETT's login and access control work

A plain-language walkthrough for the capstone panel. Everything here was read from the code in this repository (file and line references are given so a claim can be checked), and the behaviours marked **[ran it]** were also executed in a throwaway test against the current code. Nothing in this document changes the system; the weaknesses at the end are reported, not fixed.

**Vocabulary used throughout**

| Term | Meaning |
|---|---|
| **Farm** (tenant) | One customer business. Every farm's data is kept apart from every other farm's. |
| **Super admin** | The platform owner. Belongs to no farm (`farm_id` is empty) and manages all farms under `/admin/*`. |
| **Farm admin** | Runs one farm: users, settings, activity log, exports. |
| **Farm staff** | Day-to-day user of one farm (role value `farm_staff`). The brief calls this a "farm user". |
| **Middleware** | A checkpoint a request passes through before it reaches a controller. |
| **Session** | The server-side record, found through a cookie, that remembers who is signed in. |

---

## 0. The big picture

Seven layers, from the outside in. A request has to get through every layer that applies to it.

```
 browser request
   │
   ├─ 1. Web middleware group      cookies decrypted, session loaded, CSRF token checked
   ├─ 2. CheckActiveUser           signed-in but deactivated / farm not active?  → signed out
   ├─ 3. ReadOnlyImpersonation     super admin "viewing as a farm"?  → only reads allowed
   ├─ 4. SecurityHeaders           adds CSP etc. to the response (unless switched off)
   ├─ 5. Route middleware          guest | auth | admin | can:super-admin | feature:* | throttle:*
   ├─ 6. Controller + validation   input rules; ownership checks
   └─ 7. FarmScope (database)      every query on farm data silently gets  WHERE farm_id = <my farm>
```

Layer 7 is what actually keeps one farm's data away from another farm. The earlier layers decide *who may enter*; layer 7 decides *what rows they can ever see*.

---

## 1. The login flow, step by step

### 1.1 Routes

Defined in [routes/web.php](../routes/web.php):

| Method & path | Middleware | Handler |
|---|---|---|
| `GET /login` | `guest` | `LoginController@create` (shows the form) |
| `POST /login` | `guest`, `throttle:login` | `LoginController@store` |
| `POST /logout` | `auth` | `LoginController@destroy` |
| `GET /` | none | redirects to `/dashboard` |

`guest` means "only for people who are *not* signed in": a signed-in person opening `/login` is bounced to `/dashboard` (the framework's default target, because a route named `dashboard` exists). `throttle:login` is the rate limiter described in section 7.

### 1.2 What happens when someone presses "Sign in"

[LoginController::store](../app/Http/Controllers/Auth/LoginController.php#L19-L46)

1. **CSRF check.** The form carries a hidden token (`@csrf`). The web middleware group rejects the POST (HTTP 419) if the token is missing or stale. This blocks other websites from submitting the form on a victim's behalf.
2. **Rate-limit check** (`throttle:login`, before the controller): at most 5 attempts per minute for the same email + IP, and 20 per minute per IP.
3. **Validation** ([line 21](../app/Http/Controllers/Auth/LoginController.php#L21)): `email` must be present and look like an email; `password` must be present. Nothing else (no length rule at login, on purpose: old passwords must still work).
4. **`Auth::attempt($credentials, remember)`** ([line 26](../app/Http/Controllers/Auth/LoginController.php#L26)). Laravel looks the user up by email, checks the typed password against the stored bcrypt hash, and runs the whole check inside a *timebox* so a wrong email and a wrong password take the same time (no timing leak; `vendor/.../Auth/SessionGuard.php` `attempt()`). If both pass, the user id is written into the session and **the session id is regenerated** (`updateSession()` → `regenerate(true)`), which defeats session fixation.
5. **On failure** ([lines 26-31](../app/Http/Controllers/Auth/LoginController.php#L26-L31)): a warning `auth.failed` (with the typed email and IP) is written to the application log, and the user is sent back to the form with one message, *"These credentials do not match our records."* The message is identical for "no such email" and "wrong password", so the form cannot be used to discover which emails exist.
6. **Account-status check** ([lines 34-39](../app/Http/Controllers/Auth/LoginController.php#L34-L39)). A correct password is not enough: if the *user's own* status is not `active`, they are signed straight back out with *"Your account has been deactivated. Please contact the administrator."* Note this checks only the person, not their farm (section 2 explains where the farm is checked).
7. **Session regenerate again** ([line 41](../app/Http/Controllers/Auth/LoginController.php#L41)): a second, redundant regeneration. Harmless.
8. **Audit entry** ([line 43](../app/Http/Controllers/Auth/LoginController.php#L43)): "Logged in from IP: …" is written to the activity log under the user's farm (under *Platform* for a super admin).
9. **Redirect** ([line 45](../app/Http/Controllers/Auth/LoginController.php#L45)): `redirect()->intended('/dashboard')`: back to the page the person originally wanted, otherwise `/dashboard`. The "intended" address is stored by the framework from the page that bounced them to the login screen; it is never taken from a request parameter, so it cannot be used as an open redirect. A **super admin** landing on `/dashboard` is forwarded again to `/admin/farms` ([DashboardController.php](../app/Http/Controllers/DashboardController.php#L14-L16)).

### 1.3 "Remember me"

The checkbox sends `remember=on`; the controller passes `boolean('remember')` to `attempt()`.

* The framework stores a random value in `users.remember_token` and sends a second, **encrypted** cookie (`remember_web_<hash>`) containing `user-id | token | hash-of-password-hash`.
* If the normal session has expired, a request that arrives with this cookie is signed in again automatically.
* The cookie follows the same path/domain/secure/same-site defaults as the session cookie ([CookieServiceProvider](../vendor/laravel/framework/src/Illuminate/Cookie/CookieServiceProvider.php#L19-L21)).
* **It lasts 400 days, not 30.** The form says *"Remember me for 30 days"* ([login.blade.php](../resources/views/auth/login.blade.php)) but the framework's `rememberDuration` is 576000 minutes (`SessionGuard.php`, line 62) and the app never overrides it. See weakness W3.
* Because the cookie embeds a hash of the password, **changing the password invalidates every remember-me cookie.**

### 1.4 Logout

[LoginController::destroy](../app/Http/Controllers/Auth/LoginController.php#L48-L58): `POST /logout` (a POST with CSRF, so a link or image on another site cannot sign anyone out):

1. writes "Logged out" to the activity log;
2. `Auth::logout()`, which removes the user from the session **and rotates `remember_token`**, so remember-me cookies on *all* of that user's devices stop working, not just this one;
3. `session()->invalidate()`, which destroys all session data and issues a new session id;
4. `regenerateToken()`, a fresh CSRF token;
5. redirects to `/`, which redirects to `/dashboard`, which (not being signed in) redirects to `/login`.

---

## 2. What `CheckActiveUser` blocks, and what the person sees

[CheckActiveUser](../app/Http/Middleware/CheckActiveUser.php) is attached to the whole web group ([bootstrap/app.php](../bootstrap/app.php#L39)), so it runs on **every web request**, not only at login. This is why a user who is deactivated *while signed in* is thrown out on their very next click, not at their next login.

For a signed-in user it computes two things:

* `accountInactive`: the user's own `status` is not `active`;
* `farmInactive`: the user is not a super admin, belongs to a farm (`farm_id` is set), and either the farm cannot be found or its `status` is not `active`. An **archived** farm is soft-deleted, so the `farm` relation returns nothing, and that case is deliberately treated as inactive ([comment, lines 22-25](../app/Http/Middleware/CheckActiveUser.php#L22-L25)).

If either is true it signs the user out, wipes and regenerates the session, and redirects to `/login` with an error under the email box.

| Situation | Where it is caught | What the person sees |
|---|---|---|
| Own account `inactive`, tries to log in | `LoginController` (step 6 above) | *"Your account has been deactivated. Please contact the administrator."* |
| Own account turned `inactive` while signed in | `CheckActiveUser` on the next request | *"Your account has been deactivated. Contact the administrator."* (slightly different wording, no "Please") |
| Farm **pending** (not yet approved) | `CheckActiveUser`, one request *after* a successful login | *"Your farm account is not active. Please contact Organett support."* |
| Farm **rejected** (stored as `inactive`) | same | the same message |
| Farm **suspended** (`inactive`) | same | the same message |
| Farm **archived** (soft-deleted) | same | the same message |
| Account inactive **and** farm inactive | `CheckActiveUser` | the *account* message (it wins) |
| Super admin | exempt | never blocked by this middleware |
| Non-super-admin with **no farm** (`farm_id` empty) | not blocked | gets in, but `FarmScope` shows them no data at all (section 3.4) |

**[ran it]** For a pending farm the sequence is: `POST /login` → 302 to `/dashboard` (credentials *were* accepted and a session exists) → `GET /dashboard` → 302 to `/login` with the "farm account is not active" message. One `login` entry was written to that farm's activity log even though the person never saw a page. Pending, suspended and archived farms all produce the *identical* message (weakness W1).

---

## 3. Roles and authorization

### 3.1 The three roles

| Role value | Belongs to a farm? | Can do |
|---|---|---|
| `super_admin` | no | everything under `/admin/*`; also passes every `admin` and `feature:*` check |
| `farm_admin` | yes | everything farm staff can, plus users, settings, activity log, export |
| `farm_staff` | yes | the farm's day-to-day modules |

(A legacy value `admin` is still recognised by the sidebar but not by the middleware; a migration converted old rows to `farm_admin`.)

### 3.2 The four gates a route can have

| Gate | Where defined | Rule | If it fails |
|---|---|---|---|
| `auth` | framework | must be signed in | redirect to `/login` (and remembers the page) |
| `admin` | [AdminMiddleware](../app/Http/Middleware/AdminMiddleware.php#L16-L18) | role is `farm_admin` or `super_admin` | **403** *"This area is restricted to administrators only."* |
| `can:super-admin` | [Gate::define](../app/Providers/AppServiceProvider.php#L24) | role is exactly `super_admin` | **403** |
| `feature:<name>` | [CheckFarmFeature](../app/Http/Middleware/CheckFarmFeature.php#L20-L26) | super admin always passes; otherwise the farm must have the feature on | **403** *"The '<name>' feature is not enabled for your farm."* |

A feature is **on by default**: it is only off when an explicit `farm_features` row says so ([Farm::hasFeature](../app/Models/Farm.php)). The three features are `reports`, `activity_logs` and `export`, switched per farm from `/admin/farms/{farm}/features`.

### 3.3 Who can reach what

(Derived from `php artisan route:list`; "✓" = allowed, "403" = refused, "→login" = redirected to sign in.)

| Area (routes) | Guest | Farm staff | Farm admin | Super admin |
|---|---|---|---|---|
| `/login`, `/register/farm`, `/forgot-password`, `/reset-password/*` | ✓ | →dashboard | →dashboard | →dashboard |
| `/dashboard`, `/search` | →login | ✓ | ✓ | ✓ (dashboard forwards to `/admin/farms`) |
| Batches, harvest, inventory, customers, orders, payments (all verbs) | →login | ✓ | ✓ | ✓ but see W7 |
| `/reports` | →login | ✓ if `reports` on, else 403 | same | ✓ |
| `/reports/export` | →login | **403** | ✓ if `export` on | ✓ |
| `/activity-logs` | →login | **403** | ✓ if `activity_logs` on | redirected to `/admin/audit` |
| `/settings`, `/users` | →login | **403** | ✓ | ✓ but see W7 |
| `/admin/*` (farms, audit, security, impersonate, account recovery) | →login | **403** | **403** | ✓ |
| `POST /impersonate/stop`, `POST /logout` | →login | ✓ | ✓ | ✓ |

Two things worth stating plainly to the panel:

* **Only four farm-level areas are admin-only** (`/users`, `/settings`, `/activity-logs`, `/reports/export`). Every other module, including *deleting* orders, customers, batches and payments, is open to farm staff (W8).
* The sidebar hides the Admin section from staff, but hiding a link is cosmetic; the middleware above is what enforces it. The one mismatch: the **Reports** link is shown to everyone even if the farm has switched the feature off, so those users get a 403 page.

### 3.4 How one farm's data is kept from another's

Every table that holds farm data (batches, harvest records, inventory and its transactions, customers, orders, deliveries, sales, alerts, settings, activity logs: 11 models) uses the `BelongsToFarm` trait ([BelongsToFarm.php](../app/Models/Concerns/BelongsToFarm.php)). It does two things:

1. **Reading: a global scope** ([FarmScope](../app/Models/Scopes/FarmScope.php)) is attached to the model, so *every* query on it (lists, counts, sums, searches, `find`, eager loads, relationship queries) is automatically rewritten to `… WHERE <table>.farm_id = <signed-in user's farm>`.

   | Who is asking | What FarmScope does |
   |---|---|
   | nobody signed in, during a web request | `WHERE 1 = 0`: returns nothing (fails closed) ([line 17](../app/Models/Scopes/FarmScope.php#L17)) |
   | nobody signed in, in a console command (seeders, migrations) | no filter |
   | super admin | no filter (sees every farm) |
   | farm user whose `farm_id` is empty | `WHERE 1 = 0` ([line 31](../app/Models/Scopes/FarmScope.php#L31)) |
   | farm user | `WHERE farm_id = their farm` ([line 36](../app/Models/Scopes/FarmScope.php#L36)) |

2. **Writing:** a `creating` hook stamps `farm_id` from the signed-in user, so no controller has to remember to ([lines 24-34](../app/Models/Concerns/BelongsToFarm.php#L24-L34)).

**Route IDs are covered too.** `/orders/{order}` uses implicit model binding, which loads the model through the scoped query, so another farm's order id simply returns **404**. This is tested in [TenantIsolationTest](../tests/Feature/TenantIsolationTest.php) and [SecurityTest](../tests/Feature/SecurityTest.php).

**Is every query scoped? An honest answer.** Every Eloquent query on the 11 farm models is scoped; there is no `withoutGlobalScopes` or `allFarms()` call in any web controller (only in the console seeder, which filters by `farm_id` explicitly). The exceptions are all deliberate or covered by hand, and each is listed so the claim "everything is scoped" is not overstated (full inventory in the appendix):

| Not covered by `FarmScope` | Why that is acceptable / how it is handled |
|---|---|
| `User`, `Farm`, `FarmFeature`, `SecuritySetting` have no scope | `User` queries are filtered by hand: `UserController::farmUsers()` and `authorizeSameFarm()` ([UserController](../app/Http/Controllers/UserController.php)); the super-admin controllers are intentionally cross-farm and sit behind `can:super-admin`. |
| Validation rules `exists:` and `unique:` run raw SQL and **ignore** global scopes | The three that matter are handled: harvest batch and order customer are re-checked through a scoped query (`abort_unless(... ->exists(), 404)`), and `batch_code` uniqueness adds `where farm_id` ([BatchController](../app/Http/Controllers/BatchController.php#L30-L40)). `unique:users` is global on purpose (emails and usernames are unique platform-wide), which has a small side effect (W9). |
| Super admin bypasses the scope | By design on `/admin/*`; but it also applies if a super admin opens a farm-level URL by hand (W7). |
| Cache keys | Per-farm keys: `inventory.categories.farm.<id>`, `customers.dropdown.farm.<id>`, `setting.<id>.<key>`, `nav.badges.<id>` ([Controller::farmCacheKey](../app/Http/Controllers/Controller.php)). |
| Raw `DB::table(...)` | Only `AccountRecovery` (the `sessions` table, by user id) and the console seeder (filters by `farm_id`). |

---

## 4. Farm registration, approval and first login

1. **Sign-up form**: `GET /register/farm` (guests only). `POST /register/farm` passes `throttle:register` (20/min per IP) and a separate **hard cap of 10 farms created per hour per IP** ([FarmRegistrationController](../app/Http/Controllers/FarmRegistrationController.php#L19)). Failed submissions do not count toward the cap; the cap is not affected by the security switches.
2. **Validation**: farm name, full name, email (must not already exist), password with the strong rule (min 10 characters, upper + lower case, a digit, a symbol, and not found in known data breaches outside tests, [AppServiceProvider](../app/Providers/AppServiceProvider.php#L27-L30)).
3. **Inside one database transaction**: a unique `slug` is generated from the name (archived farms' slugs count as taken), the farm is created with status **`pending`**, three features are seeded as on, and the owner is created as `farm_admin` with status `active`.
4. **The owner is deliberately not signed in.** They are redirected to `/login` with: *"Thanks! '<farm>' has been submitted for review. You can sign in once a platform administrator approves it."* ([lines 88-92](../app/Http/Controllers/FarmRegistrationController.php#L88-L92)). No email is sent to anyone; the application sends no mail except the password-reset link.
5. **Super admin reviews** at `/admin/farms`: an "Awaiting Approval" block and a sidebar badge list pending farms with the registrant's name and email. Each has **Approve** and **Reject** ([FarmController](../app/Http/Controllers/SuperAdmin/FarmController.php#L62-L94)):
   * *Approve*: only if the farm is still `pending` (otherwise 409); sets it `active`; logged.
   * *Reject*: only if still `pending`; because the database has no `rejected` status, the farm is stored as **`inactive`**, the optional reason (max 500 chars) is kept only in the audit trail.
6. **First login of an approved admin**: they enter the same credentials. `LoginController` accepts them, `CheckActiveUser` finds the farm `active`, and they land on `/dashboard`. A brand-new farm has no data, so every dashboard panel shows its empty-state text (*"No active batches right now."*, *"No harvest records yet."*, …). There is no onboarding wizard, and the sidebar shows the product name, not the farm's own name. `php artisan organett:seed-demo --farm=<id|slug>` can fill a farm with demo data for presentations.
7. **If they try to log in before approval** they see the generic "farm account is not active" message, with no mention of review (W1).

Super admins can also archive a farm (soft-delete: nothing is erased, its users are locked out, it disappears from lists) and restore it from *Archived Farms*, which brings it back with the status it had.

---

## 5. Password reset and super-admin account recovery

### 5.1 Self-service reset ("Forgot password?")

1. `GET /forgot-password`, then `POST /forgot-password` (`throttle:password-reset`: 3 per minute per IP, 5 per hour per email). Validation: `email` required and well-formed ([ForgotPasswordController](../app/Http/Controllers/Auth/ForgotPasswordController.php#L20-L23)).
2. The controller asks Laravel's password broker to send a link and then **ignores the outcome**: the response is always *"If that email is registered, a password reset link has been sent."* so the form cannot be used to find out who is registered (tested in `SecurityTest`; the broker itself also runs in a timebox).
3. If the email exists, the broker creates a random token, stores **only its hash** in `password_reset_tokens` (replacing any earlier one), and emails a link `/reset-password/<token>?email=<email>`. Tokens expire after **60 minutes**; asking again within **60 seconds** sends nothing new (`config/auth.php`). The email goes through the configured mailer (the `log` mailer in local development, so the link appears in `storage/logs`). The link is sent for any existing account, including deactivated users and users of inactive farms.
4. `GET /reset-password/{token}` shows the form (token as a hidden field). `POST /reset-password` (same throttle) validates token, email and the new password (confirmed, strong rule), then the broker checks that the token matches and is unexpired. On success the controller ([ResetPasswordController](../app/Http/Controllers/Auth/ResetPasswordController.php#L28-L38)) stores the new bcrypt hash, rotates `remember_token` (all remember-me cookies die), fires the framework `PasswordReset` event, and the broker deletes the token (single use). The person is sent to `/login` with *"Your password has been reset."* They are **not** signed in automatically. A bad or expired token returns *"This password reset token is invalid."*
5. **What a reset does not do:** it does not end sessions that are already signed in on other devices (W4).

### 5.2 Super-admin account recovery (for people locked out)

Both entry points share one function, [AccountRecovery::resetPassword](../app/Services/AccountRecovery.php#L22-L48):

* sets the new password (bcrypt), rotates `remember_token`;
* **deletes that user's rows from the `sessions` table**, signing them out everywhere. This only works when sessions are stored in the database (production's setting); with the local `file` driver it deletes nothing and reports "Cleared 0" (W5);
* writes a `password_reset` entry to the *account's own farm* log, with the acting super admin and IP when it came from the web.

The two ways in:

| Entry point | Who | Safeguards |
|---|---|---|
| Farm detail page → **Recovery** column → *Reset password* (`POST /admin/farms/{farm}/users/{user}/reset-password`) | super admin only (`can:super-admin`) | new password must pass the strong rule and be confirmed; the `{farm}/{user}` pair is verified to match (404 otherwise) and a super-admin account can never be the target (403) ([AccountController::guard](../app/Http/Controllers/SuperAdmin/AccountController.php#L68-L75)) |
| `php artisan organett:reset-password --email=…` | whoever has server shell access | shows the account, asks for confirmation (unless `--force`), reads the password with a hidden prompt (never as a command-line argument, so it cannot end up in shell history), applies the strong rule. It can reset a **super admin**, which is the intended way to recover the platform owner ([ResetPassword.php](../app/Console/Commands/ResetPassword.php)). |

The same screen can **deactivate / reactivate** a user, but refuses to deactivate a farm's *last active administrator*.

---

## 6. Impersonation ("View as farm")

Purpose: let the platform owner see exactly what a farm sees, safely.

**Start**: `POST /admin/farms/{farm}/impersonate` ([ImpersonationController::start](../app/Http/Controllers/ImpersonationController.php#L14-L49)), super admin only:

1. Refused unless the farm is `active` (otherwise the next request would sign the operator out, losing their place).
2. Picks the farm's **first active `farm_admin`** (lowest id); refused if there is none.
3. Writes `impersonate_start` to that farm's log **before** switching identity, so the entry names the super admin as the actor.
4. `Auth::login($target)`: the session now belongs to the farm admin (the session id is regenerated).
5. Stores `impersonator_id` (the super admin's id) in the **server-side session**; the browser cannot see or alter it.

**While viewing:**

* A fixed amber banner on every page says *"Read-only view. You are viewing <farm> as <email>."* with an **Exit to platform admin** button ([layouts/app.blade.php](../resources/views/layouts/app.blade.php)).
* [ReadOnlyImpersonation](../app/Http/Middleware/ReadOnlyImpersonation.php#L16-L34) runs on every web request. While `impersonator_id` exists, **any method other than GET/HEAD/OPTIONS is refused with 403** *"You are viewing this farm as a platform administrator. This view is read-only."* The only two exceptions are `impersonate.stop` and `logout`. Because it is one middleware rather than a check in each controller, no new controller can forget it. The farm admin's data scoping applies as usual, so only that farm is visible.

**Stop**: `POST /impersonate/stop` ([ImpersonationController::stop](../app/Http/Controllers/ImpersonationController.php#L51-L74)): lives *outside* the super-admin group because by now the session belongs to a farm admin. It removes `impersonator_id`, signs the super admin back in with `loginUsingId`, writes `impersonate_end` to the farm's log, and returns to that farm's page under `/admin/farms/{farm}`.

**How it is logged:** the audit trail shows `impersonate_start` and `impersonate_end` under the viewed farm with the super admin as the actor and the IP address. Because the session is read-only, there are no write actions to attribute during the visit.

---

## 7. Rate limiting, security headers, the kill switch, session and cookies

### 7.1 Rate limiting ([AppServiceProvider](../app/Providers/AppServiceProvider.php#L54-L87))

| Limiter | Applied to | Limits | Key |
|---|---|---|---|
| `login` | `POST /login` | 5 / minute **and** 20 / minute | email + IP; IP alone |
| `password-reset` | `POST /forgot-password`, `POST /reset-password` | 3 / minute **and** 5 / hour | IP; email |
| `register` | `POST /register/farm` | 20 / minute | IP |
| farms-created cap | inside the registration controller | 10 farms / hour | IP (not switchable) |

When a limit is hit the user is sent back to the form with *"Too many login attempts. Please try again in N seconds."* instead of a bare HTTP 429 page. Counters live in the cache (`CACHE_STORE=file`). The client IP comes from the proxy headers **only** when the request really arrived through Render's private network or Cloudflare ([bootstrap/app.php](../bootstrap/app.php#L24-L33)); a visitor cannot fake their address by sending their own `X-Forwarded-For` (tested).

### 7.2 Security headers ([SecurityHeaders](../app/Http/Middleware/SecurityHeaders.php))

Added to every web response: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (camera, microphone, geolocation, payment all off), `Cross-Origin-Opener-Policy: same-origin`, a **Content-Security-Policy** (scripts and styles only from the site itself, `cdn.jsdelivr.net` and the font host; no framing, no plugins, forms may only post to the site), and, in production over HTTPS only, **HSTS** for one year. The policy still contains `'unsafe-inline'` for scripts because the pages use inline `<script>` blocks and `onclick` handlers (the code says so, [line 38](../app/Http/Middleware/SecurityHeaders.php#L38)).

### 7.3 The security kill switch (`/admin/security`): verified as designed

This is a deliberate feature, not a bug: a super admin can switch off, **platform-wide and in production**, (a) the security headers/CSP and (b) the login / password-reset / sign-up limiters. What was verified:

* **Defaults to ON.** With no saved row, `SecuritySetting::enabled()` returns true.
* **Fails closed.** If the table or cache cannot be read, it returns true, and it is called on every request so it is wrapped in `try/catch` ([SecuritySetting.php:49-60](../app/Models/SecuritySetting.php#L49-L60)). Tested.
* **Super admin only**: the routes sit in the `can:super-admin` group; a farm admin gets 403 (tested).
* **Every flip is logged** with the actor and IP (tested). A flip takes effect immediately on the server that handled it (the cached value is dropped on save); the value is otherwise cached for 5 minutes.
* **The page explains itself.** For each switch it shows ACTIVE/OFF, *what it covers*, a red **"With this off: …"** risk sentence, who changed it and when; a red banner reads *"A protection is switched off right now … This applies to every farm and to anyone visiting the site, not just your own account."* Turning one off asks for confirmation naming the switch and "for every farm on this platform"; there is a *Restore all* button; and the sidebar's **Security** item shows an **OFF** badge while anything is off. It also states the two protections that cannot be switched off (per-farm data isolation and the farms-per-hour cap).

### 7.4 Sessions and cookies

| Setting | Value | Source |
|---|---|---|
| Cookie name | `organett-session` | `config/session.php` (app name) |
| Driver | `file` locally; **`database`** on Render and on the VPS script | `.env`; `render.yaml`; `deploy.sh` |
| Idle lifetime | 120 min locally and on the VPS script; **60 min** on Render | `SESSION_LIFETIME` |
| Encrypted payload | off locally and on the VPS script; **on** on Render | `SESSION_ENCRYPT` |
| `Secure` flag | **on** on Render (`SESSION_SECURE_COOKIE=true`); **not set** locally or by `deploy.sh` | env (W14) |
| `HttpOnly` | on (JavaScript cannot read it) | default |
| `SameSite` | `lax` (the browser withholds the cookie on cross-site POSTs) | default |
| Serialization | JSON | `config/session.php` |
| Remember-me cookie | 400 days, encrypted | framework |

The cookies themselves are encrypted by the framework's `EncryptCookies` middleware with the app key.

---

## 8. Sequence diagrams

### 8.1 Login (successful, then blocked because the farm is not approved)

```
 Browser                    Laravel (web group)                 Database / Cache
    │                              │                                   │
    │  GET /login                  │                                   │
    │ ───────────────────────────► │  guest ✓, CheckActiveUser (no user)│
    │ ◄─────────────────────────── │  200 form + CSRF token             │
    │                              │                                   │
    │  POST /login  email,password,remember                            │
    │ ───────────────────────────► │  CSRF ✓                           │
    │                              │  throttle:login ───────────────►  │ cache: 5/min & 20/min
    │                              │  validate email/password          │
    │                              │  Auth::attempt ────────────────►  │ SELECT user BY email
    │                              │   bcrypt check (timeboxed)        │
    │                              │   session id regenerated          │
    │                              │  user.status == active ?          │
    │                              │  log "login" ──────────────────►  │ INSERT activity_logs
    │ ◄─────────────────────────── │  302 → /dashboard  (+ session cookie, + remember cookie if ticked)
    │                              │                                   │
    │  GET /dashboard              │                                   │
    │ ───────────────────────────► │  CheckActiveUser ────────────────►│ SELECT farm
    │                              │   farm active?  ── yes ──► dashboard (200)
    │                              │        └── no (pending/rejected/suspended/archived)
    │                              │            logout, wipe session   │
    │ ◄─────────────────────────── │  302 → /login  "Your farm account is not active…"
```

### 8.2 Impersonation (read-only), start to stop

```
 Super admin browser            Laravel                                     Session / DB
        │                          │                                              │
        │ POST /admin/farms/7/impersonate                                         │
        │ ───────────────────────► │ auth ✓  can:super-admin ✓  CSRF ✓            │
        │                          │ farm 7 active? first active farm_admin?      │
        │                          │ log impersonate_start (actor = super admin) ►│ activity_logs(farm 7)
        │                          │ Auth::login(farm admin)  (new session id)    │
        │                          │ session.impersonator_id = <super admin id> ─►│
        │ ◄─────────────────────── │ 302 → /dashboard                             │
        │                          │                                              │
        │ GET /batches             │ ReadOnlyImpersonation: GET → allowed         │
        │ ───────────────────────► │ FarmScope = farm 7 only  → 200, amber banner │
        │                          │                                              │
        │ POST /customers          │ ReadOnlyImpersonation: not a read            │
        │ ───────────────────────► │ → 403 "…This view is read-only."             │
        │                          │                                              │
        │ POST /impersonate/stop   │ (exempt from read-only; reachable on purpose)│
        │ ───────────────────────► │ pull impersonator_id ◄───────────────────────│
        │                          │ Auth::loginUsingId(super admin)              │
        │                          │ log impersonate_end ────────────────────────►│ activity_logs(farm 7)
        │ ◄─────────────────────── │ 302 → /admin/farms/7                         │
```

---

## 9. Weaknesses and edge cases found (reported, not fixed)

Evidence column: **ran** = reproduced with a throwaway test against the current code; **read** = established by reading the code and the framework source; severity is my judgement for a small multi-tenant app.

| # | Finding | Evidence | Severity |
|---|---|---|---|
| W1 | **Pending, rejected, suspended and archived farms get one identical message** at login ("…not active. Please contact Organett support."). A newly registered owner who tries to sign in before approval is not told the farm is *awaiting review*. A rejected farm is stored as plain `inactive`, so it cannot even be distinguished in the database from a suspended one. | ran | Medium (UX) |
| W2 | **Login "succeeds" before the farm is checked.** For a pending/archived farm, a session is created, a `login` row is written to that farm's audit log, and only the next request bounces them. The audit trail therefore shows logins for people who never saw a page. | ran | Low |
| W3 | **"Remember me for 30 days" is wrong.** The cookie lasts 400 days (framework default 576000 min, never overridden). | read | Medium (misleading) |
| W4 | **A self-service password reset does not sign out other devices.** The app uses no `AuthenticateSession` middleware and the reset only rotates the remember token, so a session that was already signed in elsewhere stays valid after the owner resets a stolen/compromised password. (The admin/console recovery path *does* purge sessions.) | read | Medium |
| W5 | **Session purge in `AccountRecovery` is a silent no-op with the `file` session driver** (the local `.env`). It reports "Cleared 0 session(s)" and the old sessions survive. Production (`render.yaml`, `deploy.sh`) uses `database`, so it works there. | read | Low (dev only) |
| W6 | **Logging out while impersonating** signs the super admin out completely (not back to the admin view) and writes the "Logged out" audit entry as the **farm admin**, and rotates the **farm admin's** remember token (kicking their remember-me devices). The banner offers "Exit", but the sidebar still shows a normal *Sign out* button. | ran | Low |
| W7 | **A super admin who types a farm-level URL by hand** (`/orders`, `/batches`, `/customers`, …) sees **every farm's rows mixed together**, and anything they create is stored with an **empty `farm_id`** (an orphan no farm can see). The sidebar hides these pages, but nothing blocks them. | ran | Low–Medium |
| W8 | **Farm staff have broad destructive power.** Outside `/users`, `/settings`, `/activity-logs` and `/reports/export`, routes are open to staff: a staff user can delete an order **together with its recorded payments**, delete customers/batches/inventory/harvests and payment records, adjust stock, and set an order's payment status to *anything* (a cancelled/"unpaid" status on a fully paid order was accepted, leaving 500 recorded as paid while the order says unpaid). This is a business-rule question, flagged for decision in Phase 2. | ran | Medium |
| W9 | **Sign-up reveals whether an email is registered** ("The email has already been taken."), whereas login and forgot-password are carefully non-revealing. Farm admins also learn that a username is used elsewhere (`unique:users` is platform-wide). | ran | Low |
| W10 | **Throttling is per account+IP and per IP, with no lockout or alert**, so a guesser rotating IP addresses is never limited per account. Counters are in the local file cache, so they are not shared if the app ever runs on more than one server. | read | Low |
| W11 | **CSP still allows `'unsafe-inline'` scripts**, which weakens its protection against injected script. Documented in the code; needs the inline handlers moved to bundled files. | read | Low |
| W12 | **Impersonation edge:** if the viewed farm is suspended/archived or the viewed user is deactivated while the super admin is "inside", `CheckActiveUser` destroys the whole session; the operator lands on the login page and **no `impersonate_end` entry is written**. `stop()` also does not re-check that the stored super-admin id is still an active super admin. | read | Low |
| W13 | **Kill-switch value is cached 5 minutes** (dropped immediately on the server that saved it). On a single server this is invisible; on several servers with per-server file caches another one could lag. | read | Info |
| W14 | **`Secure` cookie flag is set on Render but not by `deploy.sh`** (the VPS path sets no `SESSION_SECURE_COOKIE`), so a deployment created from that script sends the session cookie without the Secure flag unless it is added to `.env`. | read | Low–Medium (ops) |
| W15 | **Failed logins log the typed email to `laravel.log`**, which can contain a password pasted into the email box by mistake. | read | Info |
| W16 | **Inconsistent wording / legacy role:** the deactivated-account message differs between login ("Please contact…") and the middleware ("Contact…"); the sidebar treats a legacy `admin` role as an administrator while `AdminMiddleware` does not. | read | Info |

### Checked and found sound (so the panel can rely on them)

* Wrong email and wrong password produce the same message and take the same time (timebox) at login **and** at forgot-password.
* Session id is regenerated at sign-in (fixation) and the session is invalidated at sign-out.
* `intended()` redirect target is server-generated; there is no open redirect.
* No unescaped output (`{!! !!}`) exists in any Blade view; user-entered text is escaped.
* Reset tokens are stored hashed, expire in 60 minutes, are single use, and are rate limited.
* The impersonation marker lives in the server-side session and cannot be forged by the browser; a farm admin posting to `/admin/farms/{id}/impersonate` gets 403 (tested).
* Foreign record ids return 404 through implicit binding; `exists:`/`unique:` leaks are closed in the three places that matter.

---

## Appendix: query-scoping inventory

How each place that reads farm data is protected. "Scope" means the `FarmScope` global scope applies automatically.

| Area | Models touched | Protection |
|---|---|---|
| Batch, Harvest, Inventory, Customer, Order, Sale, Settings, Activity-log controllers | `ProductionBatch`, `HarvestRecord`, `Inventory`, `InventoryTransaction`, `Customer`, `Order`, `Delivery`, `Sale`, `Setting`, `ActivityLog` | Scope on every query; route-bound models resolve through the scope (foreign id → 404) |
| `HarvestController@store` (`batch_id`), `OrderController@store` (`customer_id`) | the `exists:` rule is unscoped | Re-checked with a scoped query and `abort_unless(... , 404)` |
| `BatchController@store` (`batch_code` uniqueness) | `unique` rule is unscoped | Rule limited with `where farm_id = <my farm>` |
| `DashboardController`, `ReportController`, `SearchController` | batch/harvest/order/sale/customer/inventory models | Scope on every query, including aggregates, `withSum`, `orWhere` groups (the scope wraps the original conditions so `OR` cannot escape it) |
| Navigation badge counts | `Order`, `Inventory` | Scope; cache key per farm |
| `UserController` | `User` (no scope) | Every query starts from `farmUsers()` (`where farm_id = mine`); update/delete call `authorizeSameFarm()` (403 otherwise); role limited to `farm_admin`/`farm_staff`; cannot demote or delete oneself or the last admin |
| `/admin/*` controllers | `Farm`, `User`, `ActivityLog`, `FarmFeature`, `SecuritySetting` | Intentionally cross-farm; behind `can:super-admin`; nested `{farm}/{user}` pairs verified |
| `ImpersonationController` | `User` | Explicit `where farm_id = <chosen farm>` |
| `AccountRecovery` | `sessions` (raw table) | By the target user's id |
| Console: `organett:seed-demo`, `organett:create-superadmin`, `organett:reset-password` | various | Run without a signed-in user, where the scope is off by design; the seeder filters by `farm_id` explicitly |
| Cache entries | categories, customer dropdown, settings, badges | Key includes the farm id |
