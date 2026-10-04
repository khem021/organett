<?php

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Farm;

it('signs the super admin in as the farm administrator', function () {
    [$farm, $admin] = makeFarm('Observed Farm');

    $this->actingAs(superAdmin())
        ->post("/admin/farms/{$farm->id}/impersonate")
        ->assertRedirect('/dashboard');

    expect(auth()->id())->toBe($admin->id)
        ->and(session('impersonator_id'))->not->toBeNull();
});

it('scopes the view to the impersonated farm only', function () {
    [$farm] = makeFarm('Mine Farm');
    [$other, $otherAdmin] = makeFarm('Theirs Farm');

    $this->actingAs($otherAdmin);
    Customer::create(['farm_id' => $other->id, 'customer_name' => 'Hidden Customer', 'phone' => '0900', 'address' => 'Elsewhere']);
    auth()->logout();

    $this->actingAs(superAdmin())->post("/admin/farms/{$farm->id}/impersonate");

    $this->get('/customers')->assertOk()->assertDontSee('Hidden Customer');
});

it('blocks every write while impersonating', function () {
    [$farm] = makeFarm('Read Only Farm');

    $this->actingAs(superAdmin())->post("/admin/farms/{$farm->id}/impersonate");

    $this->post('/customers', [
        'customer_name' => 'Should Not Exist',
        'phone' => '0912',
        'address' => 'Nowhere',
    ])->assertForbidden();

    expect(Customer::withoutGlobalScopes()->where('customer_name', 'Should Not Exist')->exists())->toBeFalse();
});

it('still allows leaving the impersonated session', function () {
    [$farm] = makeFarm('Escapable Farm');
    $owner = superAdmin();

    $this->actingAs($owner)->post("/admin/farms/{$farm->id}/impersonate");

    $this->post('/impersonate/stop')->assertRedirect(route('admin.farms.show', $farm));

    expect(auth()->id())->toBe($owner->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

it('restores full access after leaving', function () {
    [$farm] = makeFarm('Restored Farm');

    $this->actingAs(superAdmin())->post("/admin/farms/{$farm->id}/impersonate");
    $this->post('/impersonate/stop');

    $this->get('/admin/farms')->assertOk();
});

it('refuses to impersonate into a farm that is not active', function () {
    [$farm] = makeFarm('Suspended Farm');
    $farm->update(['status' => 'inactive']);
    $owner = superAdmin();

    $this->actingAs($owner)
        ->post("/admin/farms/{$farm->id}/impersonate")
        ->assertRedirect();

    // Still the super admin, not bounced to login by CheckActiveUser.
    expect(auth()->id())->toBe($owner->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

it('refuses a farm with no active administrator', function () {
    [$farm, $admin] = makeFarm('Headless Farm');
    $admin->update(['status' => 'inactive']);

    $this->actingAs(superAdmin())
        ->post("/admin/farms/{$farm->id}/impersonate")
        ->assertRedirect();

    expect(session()->has('impersonator_id'))->toBeFalse();
});

it('refuses impersonation to a farm admin', function () {
    [$target] = makeFarm('Target Farm');
    [, $nosyAdmin] = makeFarm('Nosy Farm');

    $this->actingAs($nosyAdmin)
        ->post("/admin/farms/{$target->id}/impersonate")
        ->assertForbidden();
});

it('records both the start and the end against the farm', function () {
    [$farm] = makeFarm('Audited Farm');
    $owner = superAdmin();

    $this->actingAs($owner)->post("/admin/farms/{$farm->id}/impersonate");
    $this->post('/impersonate/stop');

    $start = ActivityLog::where('action', 'impersonate_start')->latest('id')->first();
    $end = ActivityLog::where('action', 'impersonate_end')->latest('id')->first();

    expect($start)->not->toBeNull()
        ->and($start->user_id)->toBe($owner->id)
        ->and($start->farm_id)->toBe($farm->id)
        ->and($end)->not->toBeNull()
        ->and($end->user_id)->toBe($owner->id)
        ->and($end->farm_id)->toBe($farm->id);
});

it('leaves a normal super admin session untouched', function () {
    [$farm] = makeFarm('Normal Farm');

    // No impersonation started: writes elsewhere must still work.
    $this->actingAs(superAdmin())
        ->patch("/admin/farms/{$farm->id}/status", ['status' => 'inactive'])
        ->assertRedirect();

    expect(Farm::find($farm->id)->status)->toBe('inactive');
});
