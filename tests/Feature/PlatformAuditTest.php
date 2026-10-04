<?php

use App\Models\ActivityLog;
use App\Models\SecuritySetting;

it('refuses the audit page to a farm admin', function () {
    [, $admin] = makeFarm('Audit Blocked Farm');

    $this->actingAs($admin)->get('/admin/audit')->assertForbidden();
});

it('shows entries from every farm to a super admin', function () {
    [$alpha, $alphaAdmin] = makeFarm('Alpha Farm');
    [$beta, $betaAdmin] = makeFarm('Beta Farm');

    ActivityLog::create(['farm_id' => $alpha->id, 'user_id' => $alphaAdmin->id, 'module' => 'Orders', 'action' => 'create', 'description' => 'Alpha made an order']);
    ActivityLog::create(['farm_id' => $beta->id, 'user_id' => $betaAdmin->id, 'module' => 'Orders', 'action' => 'create', 'description' => 'Beta made an order']);

    $this->actingAs(superAdmin())->get('/admin/audit')
        ->assertOk()
        ->assertSee('Alpha made an order')
        ->assertSee('Beta made an order')
        ->assertSee('Alpha Farm')
        ->assertSee('Beta Farm');
});

it('surfaces platform entries that belong to no farm', function () {
    $owner = superAdmin();

    // The security kill switch writes with farm_id = null.
    $this->actingAs($owner)->patch('/admin/security', [
        'key' => SecuritySetting::HEADERS,
        'enabled' => 0,
    ]);

    expect(ActivityLog::whereNull('farm_id')->where('module', 'Security')->exists())->toBeTrue();

    $this->actingAs($owner)->get('/admin/audit')
        ->assertOk()
        ->assertSee('Turned OFF')
        ->assertSee('Platform');
});

it('filters down to platform-only entries', function () {
    [$farm, $admin] = makeFarm('Noisy Farm');
    $owner = superAdmin();

    ActivityLog::create(['farm_id' => $farm->id, 'user_id' => $admin->id, 'module' => 'Orders', 'action' => 'create', 'description' => 'A farm-level entry']);
    ActivityLog::create(['farm_id' => null, 'user_id' => $owner->id, 'module' => 'Security', 'action' => 'disable', 'description' => 'A platform-level entry']);

    $this->actingAs($owner)->get('/admin/audit?farm=platform')
        ->assertOk()
        ->assertSee('A platform-level entry')
        ->assertDontSee('A farm-level entry');
});

it('filters down to a single farm', function () {
    [$alpha, $alphaAdmin] = makeFarm('Keep Farm');
    [$beta, $betaAdmin] = makeFarm('Drop Farm');

    ActivityLog::create(['farm_id' => $alpha->id, 'user_id' => $alphaAdmin->id, 'module' => 'Orders', 'action' => 'create', 'description' => 'Keep this entry']);
    ActivityLog::create(['farm_id' => $beta->id, 'user_id' => $betaAdmin->id, 'module' => 'Orders', 'action' => 'create', 'description' => 'Drop this entry']);

    $this->actingAs(superAdmin())->get('/admin/audit?farm='.$alpha->id)
        ->assertOk()
        ->assertSee('Keep this entry')
        ->assertDontSee('Drop this entry');
});

it('records the actor IP on a web action', function () {
    $owner = superAdmin();

    $this->actingAs($owner)
        ->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->patch('/admin/security', ['key' => SecuritySetting::HEADERS, 'enabled' => 0]);

    expect(ActivityLog::latest('id')->first()->ip_address)->toBe('198.51.100.7');
});

it('redirects a super admin off the single-farm activity log', function () {
    $this->actingAs(superAdmin())->get('/activity-logs')
        ->assertRedirect(route('admin.audit'));
});
