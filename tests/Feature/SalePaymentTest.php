<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Sale;

function makeOrder(float $total = 2000.0): Order
{
    $customer = Customer::create(['customer_name' => 'Buyer', 'address' => 'x', 'phone' => '1']);

    return Order::create([
        'order_no' => 'ORD-TEST-'.uniqid(),
        'customer_id' => $customer->id,
        'order_date' => now(), 'delivery_date' => now(),
        'item_name' => 'Mushrooms', 'quantity_kg' => 10, 'unit_price' => $total / 10,
        'total_amount' => $total,
        'payment_status' => 'unpaid', 'order_status' => 'pending',
    ]);
}

it('records a partial payment and marks the order partial', function () {
    [$farm, $user] = makeFarm('Alpha Farm');
    $this->actingAs($user);
    $order = makeOrder();

    $this->post("/orders/{$order->id}/sales", [
        'sale_date' => now()->toDateString(),
        'quantity_kg' => 10, 'amount' => 500, 'payment_method' => 'Cash',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($order->fresh()->payment_status)->toBe('partial')
        ->and((float) $order->sales()->sum('amount'))->toBe(500.0);
});

it('marks the order paid once the balance is settled', function () {
    [$farm, $user] = makeFarm('Alpha Farm');
    $this->actingAs($user);
    $order = makeOrder();

    foreach ([500, 1500] as $amount) {
        $this->post("/orders/{$order->id}/sales", [
            'sale_date' => now()->toDateString(),
            'quantity_kg' => 10, 'amount' => $amount, 'payment_method' => 'Cash',
        ])->assertSessionHasNoErrors();
    }

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and($order->sales()->count())->toBe(2);
});

it('rejects a payment larger than the outstanding balance', function () {
    [$farm, $user] = makeFarm('Alpha Farm');
    $this->actingAs($user);
    $order = makeOrder();

    $this->post("/orders/{$order->id}/sales", [
        'sale_date' => now()->toDateString(),
        'quantity_kg' => 10, 'amount' => 99999, 'payment_method' => 'Cash',
    ])->assertSessionHasErrors('amount');

    expect(Sale::count())->toBe(0)
        ->and($order->fresh()->payment_status)->toBe('unpaid');
});

it('rejects a further payment once the order is fully paid', function () {
    [$farm, $user] = makeFarm('Alpha Farm');
    $this->actingAs($user);
    $order = makeOrder();

    $this->post("/orders/{$order->id}/sales", [
        'sale_date' => now()->toDateString(),
        'quantity_kg' => 10, 'amount' => 2000, 'payment_method' => 'Cash',
    ])->assertSessionHasNoErrors();

    $this->post("/orders/{$order->id}/sales", [
        'sale_date' => now()->toDateString(),
        'quantity_kg' => 1, 'amount' => 1, 'payment_method' => 'Cash',
    ])->assertSessionHasErrors('amount');

    expect((float) $order->fresh()->sales()->sum('amount'))->toBe(2000.0);
});

it('reverts the payment status when a payment is deleted', function () {
    [$farm, $user] = makeFarm('Alpha Farm');
    $this->actingAs($user);
    $order = makeOrder();

    $this->post("/orders/{$order->id}/sales", [
        'sale_date' => now()->toDateString(),
        'quantity_kg' => 10, 'amount' => 2000, 'payment_method' => 'Cash',
    ])->assertSessionHasNoErrors();
    expect($order->fresh()->payment_status)->toBe('paid');

    $sale = $order->sales()->first();
    $this->delete("/orders/{$order->id}/sales/{$sale->id}")->assertRedirect();

    expect($order->fresh()->payment_status)->toBe('unpaid');
});
