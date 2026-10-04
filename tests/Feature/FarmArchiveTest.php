<?php

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Farm;

it('archives a farm without removing it or its data', function () {
    [$farm, $admin] = makeFarm('Archivable Farm');

    $this->actingAs($admin);
    Customer::create(['customer_name' => 'Kept Customer', 'address' => 'x', 'phone' => '1']);
    auth()->logout();

    $this->actingAs(superAdmin())
        ->delete("/admin/farms/{$farm->id}")
        ->assertRedirect(route('admin.farms.index'));

    expect(Farm::find($farm->id))->toBeNull()
        ->and(Farm::withTrashed()->find($farm->id)->trashed())->toBeTrue()
        ->and(Customer::withoutGlobalScopes()->where('customer_name', 'Kept Customer')->exists())->toBeTrue();
});

it('hides an archived farm from the master dashboard', function () {
    [$farm] = makeFarm('Vanishing Farm');

    $owner = superAdmin();
    $this->actingAs($owner)->get('/admin/farms')->assertSee($farm->slug);

    $this->actingAs($owner)->delete("/admin/farms/{$farm->id}");

    // Asserting on the slug, not the name: the name legitimately reappears in the
    // "archived" flash message on the page we redirect to.
    $this->actingAs($owner)->get('/admin/farms')->assertDontSee($farm->slug);
});

it('locks the farm users out once archived', function () {
    [$farm, $admin] = makeFarm('Locked Farm');

    $this->actingAs(superAdmin())->delete("/admin/farms/{$farm->id}");
    auth()->logout();

    // The relation no longer resolves, which is exactly the case CheckActiveUser
    // used to let through.
    $this->post('/login', ['email' => $admin->email, 'password' => 'secret123']);

    $this->get('/dashboard')->assertRedirect('/login');
    expect(auth()->check())->toBeFalse();
});

it('restores an archived farm with its previous status', function () {
    [$farm, $admin] = makeFarm('Returning Farm');
    $owner = superAdmin();

    $this->actingAs($owner)->delete("/admin/farms/{$farm->id}");
    $this->actingAs($owner)
        ->patch("/admin/farms/{$farm->id}/restore")
        ->assertRedirect(route('admin.farms.index'));

    expect(Farm::find($farm->id))->not->toBeNull()
        ->and(Farm::find($farm->id)->status)->toBe('active');

    auth()->logout();
    $this->post('/login', ['email' => $admin->email, 'password' => 'secret123']);
    $this->get('/dashboard')->assertOk();
});

it('lists archived farms on their own screen', function () {
    [$farm] = makeFarm('Shelved Farm');
    $owner = superAdmin();

    $this->actingAs($owner)->delete("/admin/farms/{$farm->id}");

    $this->actingAs($owner)->get('/admin/farms/archived')
        ->assertOk()
        ->assertSee('Shelved Farm')
        ->assertSee('Restore');
});

it('does not read the archived path as a farm id', function () {
    $this->actingAs(superAdmin())->get('/admin/farms/archived')
        ->assertOk()
        ->assertSee('Archived Farms');
});

it('refuses to restore a farm that is not archived', function () {
    [$farm] = makeFarm('Live Farm');

    $this->actingAs(superAdmin())
        ->patch("/admin/farms/{$farm->id}/restore")
        ->assertStatus(409);
});

it('refuses archiving to a farm admin', function () {
    [$target] = makeFarm('Protected Farm');
    [, $nosyAdmin] = makeFarm('Nosy Archiver Farm');

    $this->actingAs($nosyAdmin)->delete("/admin/farms/{$target->id}")->assertForbidden();

    expect(Farm::find($target->id))->not->toBeNull();
});

it('records the archive and the restore against the farm', function () {
    [$farm] = makeFarm('Tracked Farm');
    $owner = superAdmin();

    $this->actingAs($owner)->delete("/admin/farms/{$farm->id}");
    $this->actingAs($owner)->patch("/admin/farms/{$farm->id}/restore");

    $archive = ActivityLog::where('action', 'archive')->latest('id')->first();
    $restore = ActivityLog::where('action', 'restore')->latest('id')->first();

    expect($archive->farm_id)->toBe($farm->id)
        ->and($archive->user_id)->toBe($owner->id)
        ->and($restore->farm_id)->toBe($farm->id);
});

it('still names an archived farm in the audit instead of calling it platform', function () {
    [$farm] = makeFarm('Named Farm');
    $owner = superAdmin();

    $this->actingAs($owner)->delete("/admin/farms/{$farm->id}");

    $this->actingAs($owner)->get('/admin/audit?farm='.$farm->id)
        ->assertOk()
        ->assertSee('Named Farm')
        ->assertSee('archived');
});

it('keeps an archived slug reserved for new registrations', function () {
    [$farm] = makeFarm('Slug Farm');
    $farm->update(['slug' => 'slug-farm']);

    $this->actingAs(superAdmin())->delete("/admin/farms/{$farm->id}");
    auth()->logout();

    // Would hit the unique index on farms.slug if archived farms were ignored.
    $this->post('/register/farm', [
        'farm_name' => 'Slug Farm',
        'full_name' => 'New Owner',
        'email' => 'newslug@example.test',
        'password' => 'Mushr00m!Harvest',
        'password_confirmation' => 'Mushr00m!Harvest',
    ])->assertRedirect('/login');

    expect(Farm::where('name', 'Slug Farm')->value('slug'))->not->toBe('slug-farm');
});
