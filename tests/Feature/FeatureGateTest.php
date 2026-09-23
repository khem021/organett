<?php

it('allows a feature-gated route when no flag row exists (default on)', function () {
    [$farm, $user] = makeFarm('Gamma Farm');

    $this->actingAs($user)->get('/reports')->assertOk();
});

it('blocks a feature-gated route when the flag is explicitly disabled', function () {
    [$farm, $user] = makeFarm('Delta Farm', 'farm_admin', ['reports' => false]);

    $this->actingAs($user)->get('/reports')->assertForbidden();
});

it('allows a feature-gated route when the flag is explicitly enabled', function () {
    [$farm, $user] = makeFarm('Epsilon Farm', 'farm_admin', ['reports' => true]);

    $this->actingAs($user)->get('/reports')->assertOk();
});

it('blocks the admin activity-log route for farm staff regardless of feature', function () {
    [$farm, $user] = makeFarm('Zeta Farm', 'farm_staff');

    $this->actingAs($user)->get('/activity-logs')->assertForbidden();
});

it('logs a farm-scoped user out when their farm is deactivated', function () {
    [$farm, $user] = makeFarm('Eta Farm');
    $farm->update(['status' => 'inactive']);

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
    $this->assertGuest();
});
