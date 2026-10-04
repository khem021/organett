<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('resets the password of an existing account', function () {
    [, $user] = makeFarm('Reset Farm');

    $this->artisan('organett:reset-password', ['--email' => $user->email])
        ->expectsConfirmation("Reset the password for {$user->email}?", 'yes')
        ->expectsQuestion('New password (min 10 chars, mixed case, number, symbol)', 'Str0ng-Pass!2026')
        ->expectsQuestion('Confirm new password', 'Str0ng-Pass!2026')
        ->assertSuccessful();

    expect(Hash::check('Str0ng-Pass!2026', $user->fresh()->password))->toBeTrue();
});

it('rejects a weak password and leaves the old one in place', function () {
    [, $user] = makeFarm('Weak Farm');
    $original = $user->password;

    $this->artisan('organett:reset-password', ['--email' => $user->email])
        ->expectsConfirmation("Reset the password for {$user->email}?", 'yes')
        ->expectsQuestion('New password (min 10 chars, mixed case, number, symbol)', 'password')
        ->expectsQuestion('Confirm new password', 'password')
        ->assertFailed();

    expect($user->fresh()->password)->toBe($original);
});

it('rejects a mismatched confirmation', function () {
    [, $user] = makeFarm('Mismatch Farm');
    $original = $user->password;

    $this->artisan('organett:reset-password', ['--email' => $user->email])
        ->expectsConfirmation("Reset the password for {$user->email}?", 'yes')
        ->expectsQuestion('New password (min 10 chars, mixed case, number, symbol)', 'Str0ng-Pass!2026')
        ->expectsQuestion('Confirm new password', 'Different-Pass!2026')
        ->assertFailed();

    expect($user->fresh()->password)->toBe($original);
});

it('changes nothing when the confirmation is declined', function () {
    [, $user] = makeFarm('Decline Farm');
    $original = $user->password;

    $this->artisan('organett:reset-password', ['--email' => $user->email])
        ->expectsConfirmation("Reset the password for {$user->email}?", 'no')
        ->assertFailed();

    expect($user->fresh()->password)->toBe($original);
});

it('fails when no account matches the email', function () {
    $this->artisan('organett:reset-password', ['--email' => 'nobody@example.test'])
        ->assertFailed();
});

it('clears stored sessions and records an audit entry', function () {
    [$farm, $user] = makeFarm('Session Farm');

    DB::table('sessions')->insert([
        ['id' => 'session-one', 'user_id' => $user->id, 'payload' => 'x', 'last_activity' => time()],
        ['id' => 'session-other', 'user_id' => null, 'payload' => 'x', 'last_activity' => time()],
    ]);

    $this->artisan('organett:reset-password', ['--email' => $user->email, '--force' => true])
        ->expectsQuestion('New password (min 10 chars, mixed case, number, symbol)', 'Str0ng-Pass!2026')
        ->expectsQuestion('Confirm new password', 'Str0ng-Pass!2026')
        ->assertSuccessful();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('sessions')->count())->toBe(1);

    $log = ActivityLog::withoutGlobalScopes()->where('action', 'password_reset')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->farm_id)->toBe($farm->id);
});

it('resets a super admin that belongs to no farm', function () {
    $user = User::create([
        'farm_id' => null,
        'full_name' => 'Organett Super Admin',
        'username' => 'super-'.uniqid(),
        'email' => 'super-'.uniqid().'@example.test',
        'password' => Hash::make('secret123'),
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $this->artisan('organett:reset-password', ['--email' => $user->email, '--force' => true])
        ->expectsQuestion('New password (min 10 chars, mixed case, number, symbol)', 'Str0ng-Pass!2026')
        ->expectsQuestion('Confirm new password', 'Str0ng-Pass!2026')
        ->assertSuccessful();

    expect(Hash::check('Str0ng-Pass!2026', $user->fresh()->password))->toBeTrue()
        ->and(ActivityLog::withoutGlobalScopes()->where('action', 'password_reset')->first()->farm_id)->toBeNull();
});
