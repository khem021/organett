<?php

use App\Models\ProductionBatch;
use App\Models\Setting;

/*
 * Tenant tables that carry a unique column need that uniqueness scoped to the
 * farm. A global unique index lets the first farm to write a value lock every
 * other farm out of it — a row they cannot even see. These cover the write
 * side of multi-tenancy; TenantIsolationTest covers the read side.
 */

it('lets a second farm save settings the first farm already saved', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');
    [$farmB, $userB] = makeFarm('Beta Farm');

    $this->actingAs($userA);
    Setting::updateOrCreate(['setting_key' => 'farm_name'], ['setting_value' => 'Alpha']);

    $this->actingAs($userB);
    Setting::updateOrCreate(['setting_key' => 'farm_name'], ['setting_value' => 'Beta']);

    expect(Setting::getValue('farm_name'))->toBe('Beta');

    $this->actingAs($userA);
    expect(Setting::getValue('farm_name'))->toBe('Alpha');
});

it('saves settings over HTTP for a farm that is not the first one', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');
    [$farmB, $userB] = makeFarm('Beta Farm');

    $this->actingAs($userA);
    $this->post('/settings', ['farm_name' => 'Alpha Farm'])->assertRedirect();

    // This was a 500: the farm-scoped lookup missed Alpha's row, then the
    // insert collided with it on the global unique index.
    $this->actingAs($userB);
    $this->post('/settings', ['farm_name' => 'Beta Farm'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Setting::getValue('farm_name'))->toBe('Beta Farm');
});

it('keeps each farms settings separate', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');
    [$farmB, $userB] = makeFarm('Beta Farm');

    $this->actingAs($userA);
    Setting::updateOrCreate(['setting_key' => 'farm_name'], ['setting_value' => 'Alpha']);

    $this->actingAs($userB);
    expect(Setting::count())->toBe(0)
        ->and(Setting::getValue('farm_name', 'fallback'))->toBe('fallback');
});

it('lets two farms use the same batch code', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');
    [$farmB, $userB] = makeFarm('Beta Farm');

    $payload = [
        'batch_code' => 'BATCH-2026-001',
        'substrate_type' => 'Sawdust',
        'spawn_type' => 'Oyster',
        'inoculation_date' => '2026-09-01',
        'expected_harvest_date' => '2026-09-28',
        'status' => 'planned',
    ];

    $this->actingAs($userA);
    $this->post('/batches', $payload)->assertRedirect()->assertSessionHasNoErrors();

    $this->actingAs($userB);
    $this->post('/batches', $payload)->assertRedirect()->assertSessionHasNoErrors();

    // allFarms() drops the tenant scope so both rows are visible at once.
    expect(ProductionBatch::allFarms()->where('farm_id', $farmA->id)->count())->toBe(1)
        ->and(ProductionBatch::allFarms()->where('farm_id', $farmB->id)->count())->toBe(1);
});

it('still rejects a duplicate batch code within the same farm', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');

    $payload = [
        'batch_code' => 'BATCH-2026-001',
        'substrate_type' => 'Sawdust',
        'spawn_type' => 'Oyster',
        'inoculation_date' => '2026-09-01',
        'expected_harvest_date' => '2026-09-28',
        'status' => 'planned',
    ];

    $this->actingAs($userA);
    $this->post('/batches', $payload)->assertRedirect()->assertSessionHasNoErrors();
    $this->post('/batches', $payload)->assertSessionHasErrors('batch_code');

    expect(ProductionBatch::count())->toBe(1);
});
