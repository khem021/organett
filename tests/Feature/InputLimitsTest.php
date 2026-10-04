<?php

use App\Models\Inventory;
use App\Models\Order;

/*
 * Validation has to stop what the database cannot hold. Money and weight columns
 * are decimal(10,2) and several text columns are 100-150 characters; PostgreSQL
 * answers an oversize value with an exception, which the visitor sees as a 500.
 * Totals are worked out exactly, not in floating point.
 */

it('rejects quantities and prices above what decimal(10,2) can hold', function (string $method, string $uri, array $payload, string $field) {
    [$farm, $admin] = makeFarm('Overflow Farm');
    $d = seedFarmData($farm);
    $this->actingAs($admin);

    $uri = str_replace(['{batch}', '{item}', '{order}'], [$d['batch']->id, $d['item']->id, $d['order']->id], $uri);
    $payload = array_map(fn ($v) => $v === '{batch}' ? $d['batch']->id : ($v === '{customer}' ? $d['customer']->id : $v), $payload);

    $this->{$method}($uri, $payload)->assertSessionHasErrors($field);
})->with([
    'harvest kg' => ['post', '/harvest', ['batch_id' => '{batch}', 'harvest_date' => '2026-10-04', 'quantity_kg' => 1e9, 'quality_grade' => 'A'], 'quantity_kg'],
    'order kg' => ['post', '/orders', ['customer_id' => '{customer}', 'order_date' => '2026-10-04', 'delivery_date' => '2026-10-05', 'item_name' => 'x', 'quantity_kg' => 1e9, 'unit_price' => 1, 'payment_status' => 'unpaid', 'order_status' => 'pending'], 'quantity_kg'],
    'order price' => ['post', '/orders', ['customer_id' => '{customer}', 'order_date' => '2026-10-04', 'delivery_date' => '2026-10-05', 'item_name' => 'x', 'quantity_kg' => 1, 'unit_price' => 1e9, 'payment_status' => 'unpaid', 'order_status' => 'pending'], 'unit_price'],
    'new stock' => ['post', '/inventory', ['item_name' => 'x', 'category' => 'c', 'unit' => 'u', 'stock_qty' => 1e9, 'reorder_level' => 1], 'stock_qty'],
    'reorder level' => ['post', '/inventory', ['item_name' => 'x', 'category' => 'c', 'unit' => 'u', 'stock_qty' => 1, 'reorder_level' => 1e9], 'reorder_level'],
    'stock adjustment' => ['post', '/inventory/{item}/adjust', ['transaction_type' => 'in', 'quantity' => 1e9], 'quantity'],
]);

it('rejects an order whose total would not fit the total column', function () {
    [$farm, $admin] = makeFarm('Big Total Farm');
    $d = seedFarmData($farm);

    // Each figure is fine alone; the product is ₱1,000,000,000.
    $this->actingAs($admin)->post('/orders', [
        'customer_id' => $d['customer']->id, 'order_date' => '2026-10-04', 'delivery_date' => '2026-10-05',
        'item_name' => 'Oyster', 'quantity_kg' => 100000, 'unit_price' => 10000,
        'payment_status' => 'unpaid', 'order_status' => 'pending',
    ])->assertSessionHasErrors();

    expect(Order::count())->toBe(1); // only the seeded one
});

it('refuses a stock top-up that would push the balance past the column', function () {
    [$farm, $admin] = makeFarm('Full Shelf Farm');
    $item = Inventory::create(['farm_id' => $farm->id, 'item_name' => 'Full', 'category' => 'c', 'unit' => 'u', 'stock_qty' => 99999999, 'reorder_level' => 1]);

    $this->actingAs($admin)->post("/inventory/{$item->id}/adjust", ['transaction_type' => 'in', 'quantity' => 5])
        ->assertSessionHasErrors();

    expect((float) $item->fresh()->stock_qty)->toBe(99999999.0);
});

it('refuses more than two decimal places instead of rounding silently', function () {
    [$farm, $admin] = makeFarm('Decimals Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->post('/harvest', [
        'batch_id' => $d['batch']->id, 'harvest_date' => '2026-10-04', 'quantity_kg' => '1.005', 'quality_grade' => 'A',
    ])->assertSessionHasErrors('quantity_kg');

    $this->post("/orders/{$d['order']->id}/sales", [
        'sale_date' => '2026-10-04', 'quantity_kg' => 1, 'amount' => '10.005', 'payment_method' => 'Cash',
    ])->assertSessionHasErrors('amount');
});

it('totals an order exactly: 0.03 kg at ₱5.50 is ₱0.17, not ₱0.16', function () {
    [$farm, $admin] = makeFarm('Exact Money Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->post('/orders', [
        'customer_id' => $d['customer']->id, 'order_date' => '2026-10-04', 'delivery_date' => '2026-10-05',
        'item_name' => 'Oyster', 'quantity_kg' => '0.03', 'unit_price' => '5.50',
        'payment_status' => 'unpaid', 'order_status' => 'pending',
    ])->assertSessionHasNoErrors();

    $order = Order::latest('id')->first();

    // 0.03 * 5.5 is 0.16499999999999998 as a float; the exact answer rounds half up to 0.17.
    expect(abs((float) $order->total_amount - 0.17))->toBeLessThan(0.0000001);
});

it('turns over-long text into validation errors, never a server error', function (string $method, string $uri, array $payload, string $field) {
    [$farm, $admin] = makeFarm('Long Text Farm');
    seedFarmData($farm);

    $this->actingAs($admin)->{$method}($uri, $payload)->assertSessionHasErrors($field);
})->with([
    'batch code' => ['post', '/batches', ['batch_code' => str_repeat('b', 101), 'substrate_type' => 's', 'spawn_type' => 's', 'inoculation_date' => '2026-10-01', 'expected_harvest_date' => '2026-11-01', 'status' => 'planned'], 'batch_code'],
    'substrate' => ['post', '/batches', ['batch_code' => 'LT-1', 'substrate_type' => str_repeat('s', 151), 'spawn_type' => 's', 'inoculation_date' => '2026-10-01', 'expected_harvest_date' => '2026-11-01', 'status' => 'planned'], 'substrate_type'],
    'customer name' => ['post', '/customers', ['customer_name' => str_repeat('c', 151), 'phone' => '0917', 'address' => 'x'], 'customer_name'],
    'customer phone' => ['post', '/customers', ['customer_name' => 'c', 'phone' => str_repeat('1', 51), 'address' => 'x'], 'phone'],
    'inventory item' => ['post', '/inventory', ['item_name' => str_repeat('i', 151), 'category' => 'c', 'unit' => 'u', 'stock_qty' => 1, 'reorder_level' => 1], 'item_name'],
    'user name' => ['post', '/users', ['full_name' => str_repeat('n', 151), 'username' => 'longuser', 'email' => 'long@example.test', 'password' => 'Mushr00m!Harvest', 'password_confirmation' => 'Mushr00m!Harvest', 'role' => 'farm_staff'], 'full_name'],
]);

it('accepts very long free-text notes without error', function () {
    [$farm, $admin] = makeFarm('Notes Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->post('/harvest', [
        'batch_id' => $d['batch']->id, 'harvest_date' => '2026-10-04', 'quantity_kg' => 3, 'quality_grade' => 'B',
        'notes' => str_repeat('Long note. ', 600), // ~6,600 characters
    ])->assertSessionHasNoErrors()->assertRedirect(route('harvest.index'));
});
