<?php

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Sale;

/*
 * Orders and money. Payment status is a consequence of the payments on record,
 * never something typed in by hand, and an order that has had money paid against
 * it cannot be cancelled or deleted out from under those payments.
 */

function orderPayload(Customer $customer, array $over = []): array
{
    return array_merge([
        'customer_id' => $customer->id, 'order_date' => '2026-10-04', 'delivery_date' => '2026-10-05',
        'item_name' => 'Oyster', 'quantity_kg' => 2, 'unit_price' => 100,
        'payment_status' => 'unpaid', 'order_status' => 'pending',
    ], $over);
}

// ── Numbering ───────────────────────────────────────────────────────────────

it('keeps numbering orders after the 999th of the year', function () {
    [$farm, $admin] = makeFarm('Busy Farm');
    $d = seedFarmData($farm);
    $year = now()->year;
    $d['order']->update(['order_no' => "ORD-{$year}-999"]);

    $this->actingAs($admin);
    $this->post('/orders', orderPayload($d['customer']))->assertSessionHasNoErrors();
    $this->post('/orders', orderPayload($d['customer']))->assertSessionHasNoErrors();

    expect(Order::pluck('order_no')->all())->toContain("ORD-{$year}-1000", "ORD-{$year}-1001");
});

it('numbers the first order of a year 001', function () {
    [$farm, $admin] = makeFarm('Fresh Year Farm');
    $customer = Customer::create(['farm_id' => $farm->id, 'customer_name' => 'C', 'phone' => '0917', 'address' => 'x']);

    $this->actingAs($admin)->post('/orders', orderPayload($customer))->assertSessionHasNoErrors();

    expect(Order::value('order_no'))->toBe('ORD-'.now()->year.'-001');
});

// ── Payment status follows payments ─────────────────────────────────────────

it('creates every order as unpaid whatever the form says', function () {
    [$farm, $admin] = makeFarm('Honest Status Farm');
    $customer = Customer::create(['farm_id' => $farm->id, 'customer_name' => 'C', 'phone' => '0917', 'address' => 'x']);

    $this->actingAs($admin)->post('/orders', orderPayload($customer, ['payment_status' => 'paid']))->assertSessionHasNoErrors();

    $order = Order::first();
    expect($order->payment_status)->toBe('unpaid')
        ->and(Sale::count())->toBe(0);
});

it('does not let the status form mark an order paid without payments', function () {
    [$farm, $admin] = makeFarm('Status Form Farm');
    $d = seedFarmData($farm);
    $d['sale']->delete();
    $d['order']->update(['payment_status' => 'unpaid']);

    $this->actingAs($admin)->patch("/orders/{$d['order']->id}/status", [
        'order_status' => 'processing', 'payment_status' => 'paid',
    ]);

    expect($d['order']->fresh()->payment_status)->toBe('unpaid');
});

it('does not let the status form mark a paid order unpaid', function () {
    [$farm, $admin] = makeFarm('Reverse Status Farm');
    $d = seedFarmData($farm);
    $d['sale']->update(['amount' => 500]);
    $d['order']->update(['payment_status' => 'paid']);

    $this->actingAs($admin)->patch("/orders/{$d['order']->id}/status", [
        'order_status' => 'processing', 'payment_status' => 'unpaid',
    ]);

    expect($d['order']->fresh()->payment_status)->toBe('paid');
});

it('still changes the order status through the status form', function () {
    [$farm, $admin] = makeFarm('Order Status Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->patch("/orders/{$d['order']->id}/status", [
        'order_status' => 'completed', 'payment_status' => 'partial',
    ])->assertSessionHasNoErrors();

    expect($d['order']->fresh()->order_status)->toBe('completed');
});

// ── Payments on cancelled orders ────────────────────────────────────────────

it('refuses a payment on a cancelled order', function () {
    [$farm, $admin] = makeFarm('Cancelled Pay Farm');
    $d = seedFarmData($farm);
    $d['sale']->delete();
    $d['order']->update(['order_status' => 'cancelled', 'payment_status' => 'unpaid']);

    $this->actingAs($admin)->post("/orders/{$d['order']->id}/sales", [
        'sale_date' => '2026-10-04', 'quantity_kg' => 1, 'amount' => 50, 'payment_method' => 'Cash',
    ])->assertSessionHasErrors('amount');

    expect(Sale::count())->toBe(0);
});

// ── Cancelling and deleting orders that have payments ───────────────────────

it('refuses to cancel an order that has payments recorded', function () {
    [$farm, $admin] = makeFarm('Cancel Paid Farm');
    $d = seedFarmData($farm); // ₱200 of ₱500 paid

    $this->actingAs($admin)->patch("/orders/{$d['order']->id}/cancel")
        ->assertSessionHas('error');

    expect($d['order']->fresh()->order_status)->toBe('processing');
});

it('still cancels an order with no payments', function () {
    [$farm, $admin] = makeFarm('Cancel Unpaid Farm');
    $d = seedFarmData($farm);
    $d['sale']->delete();
    $d['order']->update(['payment_status' => 'unpaid']);

    $this->actingAs($admin)->patch("/orders/{$d['order']->id}/cancel")->assertSessionHas('success');

    expect($d['order']->fresh()->order_status)->toBe('cancelled');
});

it('refuses to delete an order that has payments, and keeps them', function () {
    [$farm, $admin] = makeFarm('Delete Paid Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->delete("/orders/{$d['order']->id}")->assertSessionHas('error');

    expect(Order::find($d['order']->id))->not->toBeNull()
        ->and(Sale::where('order_id', $d['order']->id)->count())->toBe(1);
});

it('deletes an unpaid order together with its delivery', function () {
    [$farm, $admin] = makeFarm('Delete Unpaid Farm');
    $d = seedFarmData($farm);
    $d['sale']->delete();
    Delivery::create(['farm_id' => $farm->id, 'order_id' => $d['order']->id, 'destination' => 'Calamba', 'delivery_date' => '2026-10-05', 'transport_status' => 'scheduled']);

    $this->actingAs($admin)->delete("/orders/{$d['order']->id}")->assertSessionHas('success');

    expect(Order::find($d['order']->id))->toBeNull()
        ->and(Delivery::count())->toBe(0);
});

it('can delete an order once its payments are removed', function () {
    [$farm, $admin] = makeFarm('Refund Then Delete Farm');
    $d = seedFarmData($farm);
    $this->actingAs($admin);

    $this->delete("/orders/{$d['order']->id}/sales/{$d['sale']->id}")->assertSessionHas('success');
    $this->delete("/orders/{$d['order']->id}")->assertSessionHas('success');

    expect(Order::find($d['order']->id))->toBeNull();
});

// ── Guards that already hold ────────────────────────────────────────────────

it('refuses to overpay an order', function () {
    [$farm, $admin] = makeFarm('Overpay Farm');
    $d = seedFarmData($farm); // ₱300 outstanding

    $this->actingAs($admin)->post("/orders/{$d['order']->id}/sales", [
        'sale_date' => '2026-10-04', 'quantity_kg' => 1, 'amount' => '300.01', 'payment_method' => 'Cash',
    ])->assertSessionHasErrors('amount');
});

it('refuses zero and negative payments and quantities', function (string $field, mixed $value) {
    [$farm, $admin] = makeFarm('Zero Farm');
    $d = seedFarmData($farm);

    $payload = ['sale_date' => '2026-10-04', 'quantity_kg' => 1, 'amount' => 10, 'payment_method' => 'Cash', $field => $value];

    $this->actingAs($admin)->post("/orders/{$d['order']->id}/sales", $payload)->assertSessionHasErrors($field);
})->with([
    'zero amount' => ['amount', 0],
    'negative amount' => ['amount', -5],
    'zero weight' => ['quantity_kg', 0],
    'negative weight' => ['quantity_kg', -1],
]);

it('refuses zero and negative order quantities and a negative price', function (string $field, mixed $value) {
    [$farm, $admin] = makeFarm('Zero Order Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->post('/orders', orderPayload($d['customer'], [$field => $value]))->assertSessionHasErrors($field);
})->with([
    'zero kg' => ['quantity_kg', 0],
    'negative kg' => ['quantity_kg', -2],
    'negative price' => ['unit_price', -1],
]);

it('refuses a delivery date before the order date', function () {
    [$farm, $admin] = makeFarm('Dates Farm');
    $d = seedFarmData($farm);

    $this->actingAs($admin)->post('/orders', orderPayload($d['customer'], ['order_date' => '2026-10-10', 'delivery_date' => '2026-10-09']))
        ->assertSessionHasErrors('delivery_date');
});
