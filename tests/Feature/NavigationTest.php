<?php

use App\Models\Farm;

/*
 * The menu offers only what the signed-in person can open, every detail page says where
 * it sits, and nothing that destroys or changes access uses a browser pop-up.
 */

it('hides the Reports link when the farm has switched that module off', function () {
    [$farm, $staff] = makeFarm('No Reports Farm', 'farm_staff', ['reports' => false]);

    $html = $this->actingAs($staff)->get('/dashboard')->assertOk()->getContent();

    expect(str_contains($html, 'href="'.route('reports').'"'))->toBeFalse()
        ->and(str_contains($html, '>Analytics<'))->toBeFalse();
});

it('shows the Reports link when the module is on', function () {
    [$farm, $staff] = makeFarm('With Reports Farm', 'farm_staff');

    $html = $this->actingAs($staff)->get('/dashboard')->getContent();

    expect(str_contains($html, 'href="'.route('reports').'"'))->toBeTrue();
});

it('hides the Activity Log link when that module is off, but keeps the rest of the admin menu', function () {
    [$farm, $admin] = makeFarm('No Logs Farm', 'farm_admin', ['activity_logs' => false]);

    $html = $this->actingAs($admin)->get('/dashboard')->getContent();

    expect(str_contains($html, 'href="'.route('activity-logs.index').'"'))->toBeFalse()
        ->and(str_contains($html, 'href="'.route('users.index').'"'))->toBeTrue()
        ->and(str_contains($html, 'href="'.route('settings').'"'))->toBeTrue();
});

it('marks the current page in the menu', function () {
    [$farm, $admin] = makeFarm('Active Menu Farm');

    $html = $this->actingAs($admin)->get('/batches')->getContent();

    expect(preg_match('/<a href="[^"]*\/batches" class="nav-item active"/', $html))->toBe(1);
});

it('shows a breadcrumb trail on every detail page', function () {
    [$farm, $admin] = makeFarm('Crumbs Farm');
    $d = seedFarmData($farm);
    $this->actingAs($admin);

    foreach ([
        "/batches/{$d['batch']->id}" => ['Batches', $d['batch']->batch_code],
        "/inventory/{$d['item']->id}" => ['Inventory', $d['item']->item_name],
        "/orders/{$d['order']->id}" => ['Orders', $d['order']->order_no],
    ] as $url => [$section, $current]) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('aria-label="Breadcrumb"')
            ->and($html)->toContain('aria-current="page"')
            ->and(substr_count($html, 'aria-label="Breadcrumb"'))->toBe(1, "{$url}: one trail")
            ->and($html)->toContain(">{$section}</a>")
            ->and($html)->toContain($current);
    }
});

it('shows a breadcrumb trail on the platform detail pages', function () {
    [$farm] = makeFarm('Platform Crumbs Farm');
    $this->actingAs(superAdmin());

    foreach (["/admin/farms/{$farm->id}", "/admin/farms/{$farm->id}/features", '/admin/farms/archived'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('aria-label="Breadcrumb"')->and($html)->toContain('>All farms</a>');
    }
});

it('uses the in-app dialog, never a browser pop-up, for platform actions', function () {
    $owner = superAdmin();
    $farm = Farm::create(['name' => 'Popup Farm', 'slug' => 'popup-farm', 'status' => 'active']);
    Farm::create(['name' => 'Waiting Farm', 'slug' => 'waiting-farm', 'status' => 'pending']);
    $gone = Farm::create(['name' => 'Gone Farm', 'slug' => 'gone-farm', 'status' => 'active']);
    $gone->delete();
    $this->actingAs($owner);

    foreach (['/admin/farms', '/admin/farms/archived', '/admin/security'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect(str_contains($html, 'confirm('))->toBeFalse("{$url} still calls confirm()")
            ->and(str_contains($html, 'prompt('))->toBeFalse("{$url} still calls prompt()");
    }

    $this->get('/admin/farms')->assertSee('data-confirm-title="Archive farm"', false);
    $this->get('/admin/farms/archived')->assertSee('data-confirm-title="Restore farm"', false);
    $this->get('/admin/security')->assertSee('data-confirm-title="Turn off protection"', false);
});

it('asks for the rejection reason in a dialog with a labelled text box', function () {
    $farm = Farm::create(['name' => 'Waiting Farm', 'slug' => 'waiting-farm', 'status' => 'pending']);

    $html = $this->actingAs(superAdmin())->get('/admin/farms')->getContent();

    expect($html)->toContain('<dialog id="reject-'.$farm->id.'"')
        ->and($html)->toContain('for="reject-reason-'.$farm->id.'"')
        ->and($html)->toContain('name="reason"');
});

it('still rejects with a reason typed into that dialog', function () {
    $farm = Farm::create(['name' => 'Spam Farm', 'slug' => 'spam-farm', 'status' => 'pending']);

    $this->actingAs(superAdmin())->patch("/admin/farms/{$farm->id}/reject", ['reason' => 'Duplicate of an existing farm'])->assertRedirect();

    expect($farm->fresh()->status)->toBe('rejected');
});

it('names the thing being deleted in its confirmation', function () {
    [$farm, $admin] = makeFarm('Specific Confirm Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->get('/batches')->assertSee('data-confirm="Delete batch &ldquo;'.$d['batch']->batch_code.'&rdquo;?', false);
});

it('asks before cancelling an order, naming it, from the list and from the order page', function () {
    [$farm, $admin] = makeFarm('Cancel Confirm Farm');
    $d = seedFarmData($farm);
    $d['sale']->delete();
    $d['order']->update(['payment_status' => 'unpaid']);
    $this->actingAs($admin);

    foreach (['/orders', "/orders/{$d['order']->id}"] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('data-confirm="Cancel order '.$d['order']->order_no.'?')
            ->and($html)->toContain('data-confirm-title="Cancel order"');
    }
});
