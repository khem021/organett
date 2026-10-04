<?php

use App\Models\ActivityLog;
use App\Models\Farm;
use App\Models\User;

/** Register a farm through the public form and return it. */
function registerFarm(string $name, string $email): Farm
{
    test()->post('/register/farm', [
        'farm_name' => $name,
        'full_name' => $name.' Owner',
        'email' => $email,
        'password' => 'Mushr00m!Harvest',
        'password_confirmation' => 'Mushr00m!Harvest',
    ]);

    return Farm::where('name', $name)->firstOrFail();
}

it('parks a newly registered farm in pending without signing anyone in', function () {
    $farm = registerFarm('Waiting Farm', 'waiting@example.test');

    expect($farm->status)->toBe('pending')
        ->and(auth()->check())->toBeFalse();
});

it('keeps a pending farm admin out until approval', function () {
    registerFarm('Gated Farm', 'gated@example.test');

    // Credentials are right, but CheckActiveUser bounces a non-active farm.
    $this->post('/login', ['email' => 'gated@example.test', 'password' => 'Mushr00m!Harvest']);

    $this->get('/dashboard')->assertRedirect('/login');
    expect(auth()->check())->toBeFalse();
});

it('lets the farm admin in once a super admin approves', function () {
    $farm = registerFarm('Approved Farm', 'approved@example.test');

    $this->actingAs(superAdmin())
        ->patch("/admin/farms/{$farm->id}/approve")
        ->assertRedirect();

    expect($farm->fresh()->status)->toBe('active');

    auth()->logout();

    $this->post('/login', ['email' => 'approved@example.test', 'password' => 'Mushr00m!Harvest']);
    $this->get('/dashboard')->assertOk();
});

it('records the approval against the farm it approved', function () {
    $farm = registerFarm('Logged Farm', 'logged@example.test');
    $owner = superAdmin();

    $this->actingAs($owner)->patch("/admin/farms/{$farm->id}/approve");

    $log = ActivityLog::where('module', 'Farms')->where('action', 'approve')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->farm_id)->toBe($farm->id)
        ->and($log->user_id)->toBe($owner->id)
        ->and($log->description)->toContain('Logged Farm');
});

it('rejects a pending farm and keeps the reason in the audit trail', function () {
    $farm = registerFarm('Spam Farm', 'spam@example.test');

    $this->actingAs(superAdmin())
        ->patch("/admin/farms/{$farm->id}/reject", ['reason' => 'Looks like a duplicate'])
        ->assertRedirect();

    expect($farm->fresh()->status)->toBe('inactive');

    $log = ActivityLog::where('action', 'reject')->latest('id')->first();
    expect($log->description)->toContain('Looks like a duplicate');
});

it('refuses to approve a farm that is not pending', function () {
    $farm = registerFarm('Double Farm', 'double@example.test');
    $owner = superAdmin();

    $this->actingAs($owner)->patch("/admin/farms/{$farm->id}/approve")->assertRedirect();

    // A second submit must not revive a farm that could since have been suspended.
    $this->actingAs($owner)->patch("/admin/farms/{$farm->id}/approve")->assertStatus(409);
});

it('refuses approval to a farm admin', function () {
    $farm = registerFarm('Not Yours Farm', 'notyours@example.test');
    [, $otherAdmin] = makeFarm('Bystander Farm');

    $this->actingAs($otherAdmin)->patch("/admin/farms/{$farm->id}/approve")->assertForbidden();

    expect($farm->fresh()->status)->toBe('pending');
});

it('lists pending farms on the master dashboard', function () {
    registerFarm('Queued Farm', 'queued@example.test');

    $this->actingAs(superAdmin())->get('/admin/farms')
        ->assertOk()
        ->assertSee('Awaiting Approval')
        ->assertSee('Queued Farm')
        ->assertSee('queued@example.test');
});

it('still creates the farm admin account while pending', function () {
    registerFarm('Account Farm', 'account@example.test');

    expect(User::where('email', 'account@example.test')->value('role'))->toBe('farm_admin')
        ->and(User::where('email', 'account@example.test')->value('status'))->toBe('active');
});
