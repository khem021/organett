<?php

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductionBatch;

it('opens every page for a farm that has no data yet', function () {
    [$farm, $admin] = makeFarm('Brand New Farm');
    $this->actingAs($admin);

    foreach (['/dashboard', '/batches', '/harvest', '/inventory', '/customers', '/orders', '/reports', '/settings', '/users', '/activity-logs', '/reports/export?period=yearly', '/reports/export?period=weekly'] as $url) {
        $this->get($url)->assertOk("empty farm on {$url}");
    }
});

it('escapes hostile and unusual text wherever it is shown back', function () {
    [$farm, $admin] = makeFarm('Escaping Farm');
    $name = '<script>alert(1)</script> & "quotes" \'single\' 💡 Ñandú 李雷';
    $this->actingAs($admin);

    $this->post('/customers', ['customer_name' => $name, 'phone' => '0917 000', 'address' => $name])->assertSessionHasNoErrors();
    $customer = Customer::first();
    $order = Order::create([
        'farm_id' => $farm->id, 'order_no' => 'ORD-2026-001', 'customer_id' => $customer->id, 'order_date' => '2026-10-04',
        'delivery_date' => '2026-10-05', 'item_name' => $name, 'quantity_kg' => 1, 'unit_price' => 10, 'total_amount' => 10,
        'payment_status' => 'unpaid', 'order_status' => 'pending', 'notes' => $name,
    ]);

    foreach (['/customers', '/orders', "/orders/{$order->id}", "/orders/{$order->id}/print", '/activity-logs'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect(str_contains($html, '<script>alert(1)</script>'))->toBeFalse("raw script tag on {$url}")
            ->and(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'))->toBeTrue("escaped text missing on {$url}");
    }
});

it('stores and shows emoji and non-Latin text intact', function () {
    [$farm, $admin] = makeFarm('Unicode Farm');

    $this->actingAs($admin)->post('/customers', ['customer_name' => 'Ñandú 💡 李雷', 'phone' => '0917', 'address' => 'x']);

    expect(Customer::first()->customer_name)->toBe('Ñandú 💡 李雷');
    $this->get('/customers')->assertSee('Ñandú 💡 李雷');
});

it('refuses to delete a batch that still has harvest records', function () {
    [$farm, $admin] = makeFarm('Keep Batch Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->delete("/batches/{$d['batch']->id}")->assertSessionHas('error');

    expect(ProductionBatch::find($d['batch']->id))->not->toBeNull();
});

it('deletes a batch once its harvest records are gone', function () {
    [$farm, $admin] = makeFarm('Drop Batch Farm');
    $d = seedFarmData($farm);
    $this->actingAs($admin);

    $this->delete("/harvest/{$d['harvest']->id}")->assertSessionHas('success');
    $this->delete("/batches/{$d['batch']->id}")->assertSessionHas('success');

    expect(ProductionBatch::find($d['batch']->id))->toBeNull();
});

it('explains that a deleted batch\'s code cannot be reused instead of calling it taken', function () {
    [$farm, $admin] = makeFarm('Reuse Code Farm');
    $d = seedFarmData($farm);
    $d['harvest']->delete();
    $this->actingAs($admin);
    $this->delete("/batches/{$d['batch']->id}");

    $this->post('/batches', [
        'batch_code' => $d['batch']->batch_code, 'substrate_type' => 's', 'spawn_type' => 's',
        'inoculation_date' => '2026-10-01', 'expected_harvest_date' => '2026-11-01', 'status' => 'planned',
    ])->assertSessionHasErrors('batch_code');

    expect(firstError('batch_code'))->toContain('deleted');
});

it('lets two farms use the same batch code', function () {
    [$a, $adminA] = makeFarm('Same Code A');
    [$b, $adminB] = makeFarm('Same Code B');
    $payload = ['batch_code' => 'SHARED-1', 'substrate_type' => 's', 'spawn_type' => 's', 'inoculation_date' => '2026-10-01', 'expected_harvest_date' => '2026-11-01', 'status' => 'planned'];

    $this->actingAs($adminA)->post('/batches', $payload)->assertSessionHasNoErrors();
    $this->actingAs($adminB)->post('/batches', $payload)->assertSessionHasNoErrors();

    expect(ProductionBatch::withoutGlobalScopes()->where('batch_code', 'SHARED-1')->count())->toBe(2);
});

it('refuses to take out more stock than there is, and refuses zero or negative amounts', function (float $qty) {
    [$farm, $admin] = makeFarm('Stock Farm');
    $d = seedFarmData($farm); // 50 in stock

    $this->actingAs($admin)->post("/inventory/{$d['item']->id}/adjust", ['transaction_type' => 'out', 'quantity' => $qty]);

    expect((float) $d['item']->fresh()->stock_qty)->toBe(50.0);
})->with([50.01, 0.0, -5.0]);

it('records every stock change in the activity log', function () {
    [$farm, $admin] = makeFarm('Stock Log Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->post("/inventory/{$d['item']->id}/adjust", ['transaction_type' => 'out', 'quantity' => 5])->assertSessionHasNoErrors();

    expect(ActivityLog::where('module', 'Inventory')->where('action', 'adjust')->exists())->toBeTrue()
        ->and((float) $d['item']->fresh()->stock_qty)->toBe(45.0);
});

it('keeps an unrelated farm\'s customers out of a batch search that matches their text', function () {
    // Unrelated to length, but guards the same list pages the long-text cases hit.
    [$farm, $admin] = makeFarm('Search Guard Farm');
    Customer::create(['farm_id' => $farm->id, 'customer_name' => 'Mine', 'phone' => '0917', 'address' => 'x']);
    [$other] = makeFarm('Search Guard Other');
    ProductionBatch::create(['farm_id' => $other->id, 'batch_code' => 'MINE-9', 'substrate_type' => 's', 'spawn_type' => 's', 'inoculation_date' => '2026-10-01', 'expected_harvest_date' => '2026-11-01', 'status' => 'planned']);

    $this->actingAs($admin)->get('/batches?search=MINE')->assertOk()->assertDontSee('MINE-9');
});
