<?php

use App\Models\ActivityLog;
use App\Models\SecuritySetting;

it('leaves every protection on by default', function () {
    expect(SecuritySetting::enabled(SecuritySetting::HEADERS))->toBeTrue()
        ->and(SecuritySetting::enabled(SecuritySetting::AUTH_THROTTLING))->toBeTrue();

    $this->get('/login')->assertHeader('Content-Security-Policy');
});

it('shows the security page to a super admin only', function () {
    $this->actingAs(superAdmin())->get('/admin/security')
        ->assertOk()
        ->assertSee('Turn off security system')
        ->assertSee('Security headers &amp; CSP', false);

    [, $farmAdmin] = makeFarm('Nosy Farm');
    $this->actingAs($farmAdmin)->get('/admin/security')->assertForbidden();
});

it('refuses to let a farm admin flip a protection', function () {
    [, $farmAdmin] = makeFarm('Nosy Farm');

    $this->actingAs($farmAdmin)
        ->patch('/admin/security', ['key' => SecuritySetting::HEADERS, 'enabled' => 0])
        ->assertForbidden();

    expect(SecuritySetting::enabled(SecuritySetting::HEADERS))->toBeTrue();
});

it('stops sending security headers once they are turned off', function () {
    $this->actingAs(superAdmin())
        ->patch('/admin/security', ['key' => SecuritySetting::HEADERS, 'enabled' => 0])
        ->assertRedirect();

    auth()->logout();

    $this->get('/login')
        ->assertOk()
        ->assertHeaderMissing('Content-Security-Policy')
        ->assertHeaderMissing('X-Frame-Options');
});

it('sends the headers again once they are turned back on', function () {
    SecuritySetting::setEnabled(SecuritySetting::HEADERS, false);
    $this->get('/login')->assertHeaderMissing('Content-Security-Policy');

    $this->actingAs(superAdmin())
        ->patch('/admin/security', ['key' => SecuritySetting::HEADERS, 'enabled' => 1])
        ->assertRedirect();

    auth()->logout();

    $this->get('/login')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy');
});

it('lets login be hammered once throttling is turned off', function () {
    [, $user] = makeFarm('Hammer Farm');

    $this->actingAs(superAdmin())
        ->patch('/admin/security', ['key' => SecuritySetting::AUTH_THROTTLING, 'enabled' => 0]);

    auth()->logout();

    // Well past the 5-per-account and 20-per-IP limits that normally apply.
    foreach (range(1, 25) as $i) {
        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
    }

    $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionDoesntHaveErrors(['email' => 'Too many login attempts']);
});

it('throttles login again once throttling is turned back on', function () {
    [, $user] = makeFarm('Relock Farm');

    SecuritySetting::setEnabled(SecuritySetting::AUTH_THROTTLING, false);
    SecuritySetting::setEnabled(SecuritySetting::AUTH_THROTTLING, true);

    foreach (range(1, 5) as $i) {
        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertInvalid(['email' => 'Too many login attempts']);
});

it('turns every protection off at once', function () {
    $this->actingAs(superAdmin())
        ->patch('/admin/security', ['key' => 'all', 'enabled' => 0])
        ->assertRedirect();

    expect(SecuritySetting::enabled(SecuritySetting::HEADERS))->toBeFalse()
        ->and(SecuritySetting::enabled(SecuritySetting::AUTH_THROTTLING))->toBeFalse();
});

it('records who turned a protection off, and their IP', function () {
    $owner = superAdmin();

    $this->actingAs($owner)
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->patch('/admin/security', ['key' => SecuritySetting::HEADERS, 'enabled' => 0]);

    $log = ActivityLog::where('module', 'Security')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('disable')
        ->and($log->user_id)->toBe($owner->id)
        ->and($log->description)->toContain('Turned OFF')
        ->and($log->description)->toContain('Security headers & CSP')
        ->and($log->ip_address)->toBe('203.0.113.9');

    expect(SecuritySetting::where('setting_key', SecuritySetting::HEADERS)->value('updated_by'))
        ->toBe($owner->id);
});

it('rejects a protection key it does not know', function () {
    $this->actingAs(superAdmin())
        ->from('/admin/security')
        ->patch('/admin/security', ['key' => 'farm_isolation', 'enabled' => 0])
        ->assertInvalid('key');
});

it('keeps protections on when no switch has ever been saved', function () {
    expect(SecuritySetting::count())->toBe(0)
        ->and(SecuritySetting::enabled(SecuritySetting::HEADERS))->toBeTrue();
});

it('keeps protections on when the switch table cannot be read at all', function () {
    // Stands in for a deploy where the migration has not run yet: the query throws,
    // and the middleware must still send headers rather than fail open.
    $broken = new class extends SecuritySetting
    {
        protected $table = 'security_settings_does_not_exist';
    };

    expect($broken::enabled('probe-'.uniqid()))->toBeTrue();
});
