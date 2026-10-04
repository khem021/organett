# What changed, and why

This is the summary of the audit: the login system was documented, the application was checked for bugs and fixed test-first, the interface was made easier and safer to use, and everything was re-verified at the end. It is grouped as **Fixed**, **Improved** and **Known limitations**. Detail lives in three other files:

* [LOGIN_EXPLAINED.md](LOGIN_EXPLAINED.md): how sign-in, roles, farm isolation, impersonation and the security switches work (for the capstone panel).
* [BUG_LEDGER.md](BUG_LEDGER.md): every bug (B-01 to B-24): where, symptom, root cause, fix, and the test that covers it.
* `git log a6103e8..HEAD`: the work as small commits, one concern each. Nothing has been pushed.

**How it was checked, honestly.** The automated suite grew from **100 tests (408 assertions)** to **257 tests (1,295 assertions)** and passes on both SQLite (256 pass, 1 PostgreSQL-only test skipped) and PostgreSQL 16 (all 257, including the one test that only PostgreSQL can fail). A real Chrome browser was driven through the running app on a PostgreSQL database: a crawl of every reachable page as four kinds of visitor in light and dark mode at 375, 768 and 1440 px (594 page loads: no console errors, no sideways scrolling, no exceptions in the log), an accessibility scan (axe-core), and two scripted end-to-end journeys. Details and what was *not* covered are at the end.

---

## Fixed

Twenty-four defects, each reproduced by a failing test before it was fixed (one, B-18, could not be reproduced on the databases available and is marked so in the ledger).

### Access and sign-in

| | |
|---|---|
| **Platform owner on farm pages (B-01).** Opening `/orders`, `/batches`, `/users` … by hand showed *every farm's* rows mixed together, created records that belonged to no farm, and `/users` let the owner **delete another platform owner**. | A `RequireFarmContext` middleware sends the owner to the master dashboard instead. It is an allowlist, so a farm page added later stays closed to them by default. They look inside a farm through *View as farm*. |
| **Why can't I sign in? (B-14).** A pending, rejected, suspended and archived farm all got the same "not active" message, and a blocked person briefly counted as logged in (an audit row was written). A rejected farm was stored as "suspended". | Four distinct messages, shared by the login form and the per-request check; a new `rejected` farm status (migration `2026_10_05_000001`); no login entry for someone never let in. |
| **Email case (B-10, B-20).** On PostgreSQL `Ana@x.com` could not sign in to `ana@x.com`. | Matched in lower case at sign-in, forgot-password and reset; new emails are stored in lower case. |
| **"Remember me for 30 days" lasted 400 (B-11).** | It lasts 30. |
| **Signing out while viewing a farm (B-12)** signed the owner out entirely, logged it under the farm admin and rotated that admin's remember token. | It now exits to the platform admin. |
| **A password reset left other devices signed in and wrote no audit entry (B-13).** | The emailed-link reset now uses the same routine as the admin and console resets: other sessions are cleared and an entry is written. |
| **Menu showed Reports and Activity Log to farms that had switched them off (B-23)**; each link answered 403. | Hidden when the module is off. |

### Money and orders

| | |
|---|---|
| **0.03 kg at ₱5.50 totalled ₱0.16, not ₱0.17 (B-04).** Float arithmetic: over every pair of 2-decimal quantities and prices up to 30 kg and ₱60, 66,971 of 18,000,000 totals were a cent out, and the stored figure depended on the database. | Totals are computed exactly (bcmath, half up). |
| **Numbers the database cannot store (B-03).** A huge quantity or price, or a stock top-up past the column, was accepted and then crashed with a 500 on PostgreSQL; a third decimal place was silently rounded. | Every quantity, price, stock and payment field is held to `decimal(10,2)` and two decimals, with a clear message. |
| **Order numbers broke after the 999th of a year (B-05)**: the next number was issued twice and the second order failed. | Numbered by length then value. |
| **Orders and payments could contradict each other (B-06 to B-09).** A payment on a cancelled order; an order with ₱200 paid could be cancelled; deleting an order **silently deleted its payment records**; payment status could be typed in by hand, so an order could be "Paid" with no payments (or "Unpaid" with ₱500 paid). | Blocked with a clear message; payment status is derived from the recorded payments only. *These changed a business rule; see "Decisions" below.* |
| **Reports (B-18).** The top-customers ordering used `NULLS LAST`, which MySQL rejects. | Dialect-neutral ordering. (My first attempt broke the page on PostgreSQL; caught by the browser pass and fixed.) |

### Crashes on unusual input

| | |
|---|---|
| **Sign-up crashed for some names (B-02, B-17).** Two people with the same non-Latin name got the same number-only username and the second request returned a 500; a 144-character name made a 146-character username for an 80-character column; name and email lengths were not capped at the column sizes. | Usernames are length-capped, never empty and checked for uniqueness; limits match the columns. |
| **A freshly seeded PostgreSQL database could not register a farm (B-19).** The demo seeder inserted fixed ids without moving the id sequences, so the first new farm or user failed with a duplicate key. | The seeder advances the sequences. |
| **The app ran on UTC (B-15).** "Today", the year in an order number and the month a harvest counts toward rolled over at 08:00 Manila time. | `Asia/Manila` (override with `APP_TIMEZONE`). |
| **A deleted batch's code was refused as "already taken" (B-16)** though nothing showed it. | The message now says it belonged to a deleted batch. |
| **Cancel order had no confirmation on the order page (B-22)**; the reorder-level field could not be set to 0 from the browser (B-21); **the impersonation banner covered the top of the page on a phone (B-24)**. | Fixed. |

---

## Improved

* **Error pages.** Only a 403 existed. 404, 419 (expired form), 429, 500 and 503 now share one standalone page that follows light or dark mode, says what happened and offers a way forward; a 500 never shows the exception. The 403 used to say "administrators only" for every cause and now gives the real reason (disabled module, read-only view).
* **Sign-in screens.** Show/hide password on all five password fields (keyboard operable, announced to screen readers), errors and hints tied to their field, the password rule shown on sign-up, visible keyboard focus.
* **Dashboard.** A "Needs your attention today" strip: late deliveries, money owed (order totals less recorded payments), low stock and harvests due, each linking to a pre-filtered list; or an all-clear line.
* **Navigation and confirmations.** Breadcrumbs on every detail page; destructive and access-changing actions use one in-app dialog with a specific title and button ("Archive farm", "Cancel order", "Turn off protection"); the browser `confirm()` and `prompt()` pop-ups are gone (rejecting a farm opens a dialog with a labelled reason box).
* **Accessibility.** A browser scan across 23 pages in both themes found 9 kinds of violation (2 critical, 4 serious; 743 low-contrast elements). It now finds none (0 violations on the same pages, re-run on the finished code). Contrast tokens fixed at the source in both layouts (and guarded by a test that fails if any text colour drops below WCAG AA), `<main>` landmarks and an `<h1>` per page, named filters and table-action columns, keyboard access to tables that scroll sideways.
* **Forms.** Number fields have `inputmode="decimal"` and the server's maximum; phone fields `inputmode="tel"`.
* **Performance.** The logo went from 1,687 KB to 152 KB; every image has explicit dimensions (no layout shift); the web font uses `display=swap`. `npm run build` produces 41 kB of CSS (7.8 kB gzipped) and an empty script.
* **Documentation and tests.** The login explanation, the bug ledger and this file. Every fix has a test; there are also tests for things that were already right and should stay so (tenant isolation on every detail page, escaping of hostile text, empty farms, zero/negative amounts, overpaying).

### Decisions made on your behalf (all reversible)

You asked me to pick the conservative option and flag it. These changed a rule rather than fixed a slip:

| Decision | Where | To reverse |
|---|---|---|
| Payment status follows the payments recorded; it can no longer be set by hand (B-09) | `OrderController`, order forms | restore the dropdowns and the validation |
| An order with payments cannot be cancelled or deleted (B-07, B-08); a cancelled order cannot take payments (B-06) | `OrderController`, `SaleController` | remove `paymentsBlock()` / the status check |
| A rejected farm is stored as `rejected`, not `inactive` (B-14) | migration + `FarmController` | the migration's `down()` is intentionally empty |
| The app timezone is Manila (B-15). **Rows written earlier were stored in UTC and now display 8 hours earlier than their real Manila time**; nothing was rewritten. | `config/app.php` | `APP_TIMEZONE=UTC` |
| The platform owner cannot open farm pages directly (B-01) | `RequireFarmContext` | remove the `farm` middleware from the route group |

---

## Known limitations

### Things you need to do
* **Run `php artisan migrate` on your development database.** The new `farms.status` migration has not been applied there (all testing used throwaway databases). Until it is, rejecting a pending farm will fail on that database.
* **Add `SESSION_SECURE_COOKIE=true`** to any deployment made with `deploy.sh` once HTTPS is live (it is already set in `render.yaml`). I did not force it, because that script can run on plain HTTP.

### Decisions still open
* **Farm staff can delete** customers, batches, inventory items, harvest records and individual payments, and adjust stock; only users, settings, the activity log and the CSV export are admin-only. Who may delete is a policy question. The dangerous money cases are blocked (above), but the permissions are unchanged.

### Not changed, or not done
* **Column sorting and sticky table headers** were asked for and **not added**; the lists already search, filter and paginate (keeping the query string) and have empty states that offer "Clear filters" or "add the first one" (the platform farms list has no empty state, since it is never empty). Wide tables scroll sideways (keyboard-reachable) rather than turning into cards on a phone.
* **Sign-up still reveals whether an email is registered** ("already been taken"), unlike sign-in and forgot-password. A farm admin also learns that a username exists elsewhere. Making it non-revealing means a different sign-up flow.
* **Login throttling** is per account+IP and per IP, with no lockout or alert, and its counters are in the local file cache.
* **CSP still allows inline scripts** (`'unsafe-inline'`); the pages use inline handlers.
* **Two simultaneous first orders of a year can still collide** on the order number (nothing to lock before the first row exists).
* **Impersonation:** if the viewed farm is suspended mid-visit the operator lands on the login page and no "ended" entry is written.
* `AccountRecovery`'s "sign out everywhere" only works with the `database` session driver (production's); with the local `file` driver it clears nothing.

### What I could not verify
* **MySQL.** It is not installed here. One MySQL-incompatible query was fixed by reading; nothing was run on MySQL.
* **Real email.** The mailer in every test is `log`/`array`; the reset email was never delivered to an inbox. The breached-password check is skipped in tests (it calls an external service).
* **Docker, Render and the VPS script** were not run.
* **Screen readers.** The accessibility check is automated (axe finds roughly a third of real issues); nothing was tried with NVDA or VoiceOver. Open modal dialogs were not scanned.
* **Printed receipts and the CSV opened in Excel** were checked by content, not by eye or in Excel.
* **A real expired-CSRF 419** was tested through a forced 419, not by waiting for a session to expire.
* **Concurrency** (two payments or two stock changes at the same instant) is guarded by row locks that the SQLite tests cannot exercise.
* **The browser scripts live outside the repository** (they point at this machine's Chrome), so the browser checks are not repeatable from a clean checkout. The PHP tests are.
* **Your development database was used in parallel** while this ran: nine activity-log rows (a login, an inventory item and an order deleted, batch and order status changes) appeared between my first and last check. None came from my runs, which used separate databases, and its structure is unchanged.
