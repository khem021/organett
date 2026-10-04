<?php

use App\Models\Farm;

/*
 * Every GET route, as every kind of visitor. The expected status for each pairing
 * is stated here, so a route that starts returning a 500, or opens to the wrong
 * role, fails by name.
 */

const GUEST_PAGES = ['/login', '/register/farm', '/forgot-password', '/reset-password/sometoken'];

/** @return array<string, string> label => url, for pages every signed-in farm member can open */
function farmMemberPages(array $d): array
{
    return [
        'dashboard' => '/dashboard',
        'search' => '/search?q=ab',
        'batches' => '/batches',
        'batch detail' => "/batches/{$d['batch']->id}",
        'harvest' => '/harvest',
        'inventory' => '/inventory',
        'inventory detail' => "/inventory/{$d['item']->id}",
        'customers' => '/customers',
        'orders' => '/orders',
        'order detail' => "/orders/{$d['order']->id}",
        'receipt' => "/orders/{$d['order']->id}/print",
        'reports' => '/reports',
    ];
}

/** @return array<string, string> pages restricted to farm admins */
function farmAdminPages(): array
{
    return [
        'activity log' => '/activity-logs',
        'export' => '/reports/export?period=daily',
        'settings' => '/settings',
        'users' => '/users',
    ];
}

/** @return array<string, string> pages restricted to the platform owner */
function platformPages(Farm $farm): array
{
    return [
        'farms' => '/admin/farms',
        'archived farms' => '/admin/farms/archived',
        'farm detail' => "/admin/farms/{$farm->id}",
        'farm features' => "/admin/farms/{$farm->id}/features",
        'audit' => '/admin/audit',
        'security' => '/admin/security',
    ];
}

it('lets a guest open only the public pages', function () {
    [$farm] = makeFarm('Guest Farm');
    $d = seedFarmData($farm);

    foreach (GUEST_PAGES as $url) {
        $this->get($url)->assertOk();
    }

    foreach ([...farmMemberPages($d), ...farmAdminPages(), ...platformPages($farm)] as $label => $url) {
        expectRedirectTo($this->get($url), '/login', "guest on {$label} ({$url})");
    }
});

it('gives farm staff the member pages and nothing administrative', function () {
    [$farm, $staff] = makeFarm('Staff Farm', 'farm_staff');
    $d = seedFarmData($farm);
    $this->actingAs($staff);

    foreach (farmMemberPages($d) as $label => $url) {
        expectStatus($this->get($url), 200, "staff on {$label} ({$url})");
    }

    foreach ([...farmAdminPages(), ...platformPages($farm)] as $label => $url) {
        expectStatus($this->get($url), 403, "staff on {$label} ({$url})");
    }
});

it('gives a farm admin the member and admin pages but not the platform', function () {
    [$farm, $admin] = makeFarm('Admin Farm');
    $d = seedFarmData($farm);
    $this->actingAs($admin);

    foreach ([...farmMemberPages($d), ...farmAdminPages()] as $label => $url) {
        expectStatus($this->get($url), 200, "farm admin on {$label} ({$url})");
    }

    foreach (platformPages($farm) as $label => $url) {
        expectStatus($this->get($url), 403, "farm admin on {$label} ({$url})");
    }
});

it('gives the platform owner the platform and keeps them out of one farm\'s data', function () {
    [$farm] = makeFarm('Owner View Farm');
    $d = seedFarmData($farm);
    $this->actingAs(superAdmin());

    foreach (platformPages($farm) as $label => $url) {
        expectStatus($this->get($url), 200, "super admin on {$label} ({$url})");
    }

    // Farm pages would otherwise merge every farm's rows together, and anything
    // created there would belong to no farm at all.
    foreach (farmMemberPages($d) as $label => $url) {
        if ($label === 'search') {
            continue;
        }

        expectRedirectTo($this->get($url), route('admin.farms.index'), "super admin on {$label} ({$url})");
    }

    $this->get('/activity-logs')->assertRedirect(route('admin.audit'));
    $this->get('/settings')->assertRedirect(route('admin.farms.index'));
    $this->get('/users')->assertRedirect(route('admin.farms.index'));
});

it('sends every member of an inactive farm back to the login screen', function () {
    foreach (['pending', 'inactive'] as $status) {
        [$farm, $admin] = makeFarm("Closed {$status} Farm");
        $farm->update(['status' => $status]);
        $d = seedFarmData($farm, $status);

        foreach (farmMemberPages($d) as $label => $url) {
            expectRedirectTo($this->actingAs($admin)->get($url), '/login', "{$status} farm on {$label}");
        }
    }
});

it('sends members of an archived farm back to the login screen', function () {
    [$farm, $admin] = makeFarm('Archived Access Farm');
    $d = seedFarmData($farm);
    $farm->delete();

    foreach (farmMemberPages($d) as $label => $url) {
        expectRedirectTo($this->actingAs($admin)->get($url), '/login', "archived farm on {$label}");
    }
});

it('sends a deactivated user back to the login screen', function () {
    [$farm, $staff] = makeFarm('Deactivated User Farm', 'farm_staff');
    $d = seedFarmData($farm);
    $staff->update(['status' => 'inactive']);

    foreach (farmMemberPages($d) as $label => $url) {
        expectRedirectTo($this->actingAs($staff)->get($url), '/login', "inactive user on {$label}");
    }
});

it('shows an impersonating super admin the farm\'s pages and none of the platform\'s', function () {
    [$farm] = makeFarm('Impersonated Access Farm');
    $d = seedFarmData($farm);
    $this->actingAs(superAdmin())->post("/admin/farms/{$farm->id}/impersonate");

    foreach ([...farmMemberPages($d), ...farmAdminPages()] as $label => $url) {
        expectStatus($this->get($url), 200, "impersonating on {$label} ({$url})");
    }

    foreach (platformPages($farm) as $label => $url) {
        expectStatus($this->get($url), 403, "impersonating on {$label} ({$url})");
    }
});

it('returns 404 for another farm\'s records on every detail page', function () {
    [$farmA, $adminA] = makeFarm('Reader Farm');
    [$farmB] = makeFarm('Other Farm');
    seedFarmData($farmA, 'A');
    $b = seedFarmData($farmB, 'B');
    $this->actingAs($adminA);

    foreach ([
        "/batches/{$b['batch']->id}",
        "/inventory/{$b['item']->id}",
        "/orders/{$b['order']->id}",
        "/orders/{$b['order']->id}/print",
    ] as $url) {
        expectStatus($this->get($url), 404, "farm A reading farm B at {$url}");
    }
});

it('hides another farm\'s rows from the list pages and the search box', function () {
    [$farmA, $adminA] = makeFarm('Lister Farm');
    [$farmB] = makeFarm('Hidden Farm');
    seedFarmData($farmA, 'A');
    seedFarmData($farmB, 'B');
    $this->actingAs($adminA);

    foreach (['/batches' => 'B-B-001', '/inventory' => 'Spawn B', '/customers' => 'Customer B', '/orders' => 'ORD-2026-00'.$farmB->id] as $url => $secret) {
        $this->get($url)->assertOk()->assertDontSee($secret);
    }

    $this->getJson('/search?q=B-B-001')->assertOk()->assertExactJson(['groups' => []]);
});
