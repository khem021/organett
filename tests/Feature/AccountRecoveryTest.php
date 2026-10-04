<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('resets a farm account password and clears its sessions', function () {
    [$farm, $admin] = makeFarm('Locked Out Farm');
    $owner = superAdmin();

    DB::table('sessions')->insert([
        ['id' => 'sess-a', 'user_id' => $admin->id, 'payload' => 'x', 'last_activity' => time()],
        ['id' => 'sess-b', 'user_id' => null, 'payload' => 'x', 'last_activity' => time()],
    ]);

    $this->actingAs($owner)
        ->post("/admin/farms/{$farm->id}/users/{$admin->id}/reset-password", [
            'password' => 'Mushr00m!Harvest',
            'password_confirmation' => 'Mushr00m!Harvest',
        ])
        ->assertRedirect();

    expect(Hash::check('Mushr00m!Harvest', $admin->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $admin->id)->count())->toBe(0)
        ->and(DB::table('sessions')->count())->toBe(1);
});

it('attributes a web reset to the super admin who did it', function () {
    [$farm, $admin] = makeFarm('Attributed Farm');
    $owner = superAdmin();

    $this->actingAs($owner)->post("/admin/farms/{$farm->id}/users/{$admin->id}/reset-password", [
        'password' => 'Mushr00m!Harvest',
        'password_confirmation' => 'Mushr00m!Harvest',
    ]);

    $log = ActivityLog::where('action', 'password_reset')->latest('id')->first();

    expect($log->user_id)->toBe($owner->id)
        ->and($log->farm_id)->toBe($farm->id)
        ->and($log->description)->toContain($admin->email);
});

it('rejects a weak replacement password', function () {
    [$farm, $admin] = makeFarm('Weak Reset Farm');
    $original = $admin->password;

    $this->actingAs(superAdmin())
        ->post("/admin/farms/{$farm->id}/users/{$admin->id}/reset-password", [
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])
        ->assertSessionHasErrors('password');

    expect($admin->fresh()->password)->toBe($original);
});

it('refuses to act on a user that belongs to another farm', function () {
    [$farm] = makeFarm('Owner Farm');
    [, $outsider] = makeFarm('Outsider Farm');

    $this->actingAs(superAdmin())
        ->post("/admin/farms/{$farm->id}/users/{$outsider->id}/reset-password", [
            'password' => 'Mushr00m!Harvest',
            'password_confirmation' => 'Mushr00m!Harvest',
        ])
        ->assertNotFound();
});

it('refuses to touch another super admin', function () {
    [$farm] = makeFarm('Any Farm');
    $target = superAdmin();

    $this->actingAs(superAdmin())
        ->post("/admin/farms/{$farm->id}/users/{$target->id}/reset-password", [
            'password' => 'Mushr00m!Harvest',
            'password_confirmation' => 'Mushr00m!Harvest',
        ])
        ->assertNotFound(); // farm_id mismatch is checked first
});

it('deactivates and reactivates a farm staff account', function () {
    [$farm, $admin] = makeFarm('Staffed Farm');
    $staff = User::create([
        'farm_id' => $farm->id, 'full_name' => 'Some Staff', 'username' => 'staff'.uniqid(),
        'email' => 'staff-'.uniqid().'@example.test', 'password' => Hash::make('secret123'),
        'role' => 'farm_staff', 'status' => 'active',
    ]);
    $owner = superAdmin();

    $this->actingAs($owner)->patch("/admin/farms/{$farm->id}/users/{$staff->id}/status", ['status' => 'inactive']);
    expect($staff->fresh()->status)->toBe('inactive');

    $this->actingAs($owner)->patch("/admin/farms/{$farm->id}/users/{$staff->id}/status", ['status' => 'active']);
    expect($staff->fresh()->status)->toBe('active');
});

it('refuses to deactivate the last active administrator of a farm', function () {
    [$farm, $admin] = makeFarm('Sole Admin Farm');

    $this->actingAs(superAdmin())
        ->patch("/admin/farms/{$farm->id}/users/{$admin->id}/status", ['status' => 'inactive'])
        ->assertRedirect();

    expect($admin->fresh()->status)->toBe('active');
    expect(session('error'))->toContain('last active administrator');
});

it('allows deactivating one admin when the farm has another', function () {
    [$farm, $first] = makeFarm('Two Admin Farm');
    $second = User::create([
        'farm_id' => $farm->id, 'full_name' => 'Second Admin', 'username' => 'second'.uniqid(),
        'email' => 'second-'.uniqid().'@example.test', 'password' => Hash::make('secret123'),
        'role' => 'farm_admin', 'status' => 'active',
    ]);

    $this->actingAs(superAdmin())
        ->patch("/admin/farms/{$farm->id}/users/{$first->id}/status", ['status' => 'inactive']);

    expect($first->fresh()->status)->toBe('inactive')
        ->and($second->fresh()->status)->toBe('active');
});

it('refuses account recovery to a farm admin', function () {
    [$farm, $admin] = makeFarm('Self Service Farm');

    $this->actingAs($admin)
        ->patch("/admin/farms/{$farm->id}/users/{$admin->id}/status", ['status' => 'inactive'])
        ->assertForbidden();
});

it('renders the recovery controls on the farm detail page', function () {
    [$farm, $admin] = makeFarm('Rendered Farm');

    $this->actingAs(superAdmin())->get("/admin/farms/{$farm->id}")
        ->assertOk()
        ->assertSee('Recovery')
        ->assertSee('Reset password')
        ->assertSee('Deactivate')
        ->assertSee('View as farm')
        ->assertSee($admin->email);
});
