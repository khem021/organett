<?php

use App\Models\ActivityLog;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

// ── Email case ──────────────────────────────────────────────────────────────

it('signs in with the email typed in any letter case', function () {
    [$farm, $admin] = makeFarm('Case Login Farm');
    $admin->update(['email' => 'case.login@example.test']);

    $this->post('/login', ['email' => 'Case.LOGIN@Example.TEST', 'password' => 'secret123'])
        ->assertRedirect('/dashboard');

    expect(auth()->id())->toBe($admin->id);
});

it('still refuses a wrong password for a differently-cased email', function () {
    [$farm, $admin] = makeFarm('Case Wrong Farm');
    $admin->update(['email' => 'case.wrong@example.test']);

    $this->post('/login', ['email' => 'CASE.WRONG@example.test', 'password' => 'nope'])
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('finds the account for a password reset whatever case the email is typed in', function () {
    Notification::fake();
    [$farm, $admin] = makeFarm('Case Reset Farm');
    $admin->update(['email' => 'case.reset@example.test']);

    $this->post('/forgot-password', ['email' => 'Case.RESET@example.test']);

    Notification::assertSentTo($admin, ResetPassword::class);
});

// ── Remember me ─────────────────────────────────────────────────────────────

it('keeps "remember me" for the 30 days the form promises', function () {
    [$farm, $admin] = makeFarm('Remember Farm');

    $response = $this->post('/login', ['email' => $admin->email, 'password' => 'secret123', 'remember' => 'on']);

    $cookie = collect($response->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

    expect($cookie)->not->toBeNull();

    $days = ($cookie->getExpiresTime() - time()) / 86400;
    expect($days)->toBeGreaterThan(29)->toBeLessThan(31);
});

it('sets no remember cookie unless asked', function () {
    [$farm, $admin] = makeFarm('No Remember Farm');

    $response = $this->post('/login', ['email' => $admin->email, 'password' => 'secret123']);

    expect(collect($response->headers->getCookies())->contains(fn ($c) => str_starts_with($c->getName(), 'remember_web_')))->toBeFalse();
});

// ── Signing out while impersonating ─────────────────────────────────────────

it('returns to the platform admin, not the login page, when signing out while impersonating', function () {
    [$farm, $admin] = makeFarm('Logout Impersonation Farm');
    $owner = superAdmin();
    $admin->forceFill(['remember_token' => 'farm-admin-token'])->save();

    $this->actingAs($owner)->post("/admin/farms/{$farm->id}/impersonate");
    $this->post('/logout')->assertRedirect(route('admin.farms.show', $farm));

    expect(auth()->id())->toBe($owner->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

it('does not touch the farm admin\'s remember token or log them out when the operator signs out', function () {
    [$farm, $admin] = makeFarm('Logout Token Farm');
    $admin->forceFill(['remember_token' => 'farm-admin-token'])->save();

    $this->actingAs(superAdmin())->post("/admin/farms/{$farm->id}/impersonate");
    $this->post('/logout');

    expect($admin->fresh()->remember_token)->toBe('farm-admin-token')
        ->and(ActivityLog::where('farm_id', $farm->id)->where('user_id', $admin->id)->where('action', 'logout')->exists())->toBeFalse()
        ->and(ActivityLog::where('farm_id', $farm->id)->where('action', 'impersonate_end')->exists())->toBeTrue();
});

it('still signs a normal user all the way out', function () {
    [$farm, $admin] = makeFarm('Plain Logout Farm');

    $this->actingAs($admin)->post('/logout')->assertRedirect('/');

    expect(auth()->check())->toBeFalse();
});

// ── Password reset ends other sessions and is audited ───────────────────────

it('signs the account out everywhere when its password is reset by email link', function () {
    [$farm, $admin] = makeFarm('Reset Sessions Farm');
    DB::table('sessions')->insert([
        'id' => 'stolen-session', 'user_id' => $admin->id, 'payload' => 'x', 'last_activity' => time(),
    ]);
    $token = Password::createToken($admin);

    $this->post('/reset-password', [
        'token' => $token, 'email' => $admin->email,
        'password' => 'Br4nd-New!Passw0rd', 'password_confirmation' => 'Br4nd-New!Passw0rd',
    ])->assertRedirect(route('login'));

    expect(Hash::check('Br4nd-New!Passw0rd', $admin->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $admin->id)->count())->toBe(0);
});

it('records a password reset by email link against the account\'s farm', function () {
    [$farm, $admin] = makeFarm('Reset Audit Farm');
    $token = Password::createToken($admin);

    $this->post('/reset-password', [
        'token' => $token, 'email' => $admin->email,
        'password' => 'Br4nd-New!Passw0rd', 'password_confirmation' => 'Br4nd-New!Passw0rd',
    ]);

    $log = ActivityLog::where('farm_id', $farm->id)->where('action', 'password_reset')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($admin->id);
});

it('rejects a reset with a bad token and changes nothing', function () {
    [$farm, $admin] = makeFarm('Bad Token Farm');

    $this->post('/reset-password', [
        'token' => 'not-a-real-token', 'email' => $admin->email,
        'password' => 'Br4nd-New!Passw0rd', 'password_confirmation' => 'Br4nd-New!Passw0rd',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('secret123', $admin->fresh()->password))->toBeTrue();
});

// ── Why a farm\'s people cannot get in ──────────────────────────────────────

function lockoutCases(): array
{
    return [
        'pending' => ['pending', 'awaiting approval'],
        'rejected' => ['rejected', 'not approved'],
        'suspended' => ['inactive', 'suspended'],
        'archived' => ['archived', 'archived'],
    ];
}

it('says why a farm\'s people cannot sign in, at the login form', function (string $state, string $words) {
    [$farm, $admin] = makeFarm("Login {$state} Farm");
    $state === 'archived' ? $farm->delete() : $farm->update(['status' => $state]);

    $this->post('/login', ['email' => $admin->email, 'password' => 'secret123'])
        ->assertRedirect()
        ->assertSessionHasErrors('email');

    expect(firstError('email'))->toContain($words)
        ->and(auth()->check())->toBeFalse();
})->with(lockoutCases());

it('does not write a login entry for someone who was never let in', function (string $state) {
    [$farm, $admin] = makeFarm("Blocked {$state} Farm");
    $state === 'archived' ? $farm->delete() : $farm->update(['status' => $state]);

    $this->post('/login', ['email' => $admin->email, 'password' => 'secret123']);

    expect(ActivityLog::withoutGlobalScopes()->where('farm_id', $farm->id)->where('action', 'login')->exists())->toBeFalse();
})->with(['pending', 'rejected', 'inactive', 'archived']);

it('says the same thing to someone already signed in when the farm is closed under them', function (string $state, string $words) {
    [$farm, $admin] = makeFarm("Mid session {$state} Farm");
    $this->actingAs($admin);
    $state === 'archived' ? $farm->delete() : $farm->update(['status' => $state]);

    $this->get('/dashboard')->assertRedirect('/login');

    expect(firstError('email'))->toContain($words);
})->with(lockoutCases());

it('tells a deactivated person their own account is the problem, ahead of the farm', function () {
    [$farm, $admin] = makeFarm('Own Account Farm');
    $admin->update(['status' => 'inactive']);
    $farm->update(['status' => 'inactive']);

    $this->post('/login', ['email' => $admin->email, 'password' => 'secret123']);

    expect(firstError('email'))->toContain('account has been deactivated');
});

it('stores a rejected farm as rejected, not as suspended', function () {
    $farm = Farm::create(['name' => 'Spam Farm', 'slug' => 'spam-farm', 'status' => 'pending']);

    $this->actingAs(superAdmin())->patch("/admin/farms/{$farm->id}/reject", ['reason' => 'Duplicate'])->assertRedirect();

    expect($farm->fresh()->status)->toBe('rejected');
});

it('lists a rejected farm for the platform admin and lets them reactivate it', function () {
    $farm = Farm::create(['name' => 'Second Chance Farm', 'slug' => 'second-chance', 'status' => 'rejected']);
    User::create(['farm_id' => $farm->id, 'full_name' => 'Owner', 'username' => 'secondchance', 'email' => 'second@example.test', 'password' => Hash::make('secret123'), 'role' => 'farm_admin', 'status' => 'active']);
    $owner = superAdmin();

    $this->actingAs($owner)->get('/admin/farms')->assertOk()->assertSee('Second Chance Farm')->assertSee('Rejected');

    $this->patch("/admin/farms/{$farm->id}/status", ['status' => 'active'])->assertRedirect();

    expect($farm->fresh()->status)->toBe('active');
});

it('completes a password reset when the email is typed in another letter case', function () {
    [$farm, $admin] = makeFarm('Case Reset Complete Farm');
    $admin->update(['email' => 'case.complete@example.test']);
    $token = Password::createToken($admin);

    $this->post('/reset-password', [
        'token' => $token, 'email' => 'CASE.Complete@Example.TEST',
        'password' => 'Br4nd-New!Passw0rd', 'password_confirmation' => 'Br4nd-New!Passw0rd',
    ])->assertRedirect(route('login'));

    expect(Hash::check('Br4nd-New!Passw0rd', $admin->fresh()->password))->toBeTrue();
});
