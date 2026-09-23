<?php

use App\Models\Customer;
use App\Models\Order;

it('hides other farms rows from the global scope', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');
    [$farmB, $userB] = makeFarm('Beta Farm');

    $this->actingAs($userA);
    Customer::create(['customer_name' => 'A Customer', 'address' => 'x', 'phone' => '1']);
    Order::create([
        'order_no' => 'ORD-TEST-001',
        'customer_id' => Customer::first()->id,
        'order_date' => now(), 'delivery_date' => now(),
        'item_name' => 'Mushrooms', 'quantity_kg' => 1, 'unit_price' => 10, 'total_amount' => 10,
        'payment_status' => 'unpaid', 'order_status' => 'pending',
    ]);

    expect(Order::count())->toBe(1)
        ->and(Customer::count())->toBe(1);

    $this->actingAs($userB);
    expect(Order::count())->toBe(0)
        ->and(Customer::count())->toBe(0);
});

it('stamps farm_id from the acting user on create', function () {
    [$farmB, $userB] = makeFarm('Beta Farm');

    $this->actingAs($userB);
    $customer = Customer::create(['customer_name' => 'B Customer', 'address' => 'x', 'phone' => '1']);

    expect($customer->farm_id)->toBe($farmB->id);
});

it('does not leak another farms records over HTTP', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');
    [$farmB, $userB] = makeFarm('Beta Farm');

    $this->actingAs($userA);
    $customerA = Customer::create(['customer_name' => 'Alpha Only', 'address' => 'x', 'phone' => '1']);

    $this->actingAs($userB);

    $this->get('/customers')->assertOk()->assertDontSee('Alpha Only');
    $this->get("/inventory/{$customerA->id}")->assertNotFound();
});

it('rejects creating an order against another farms customer', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');
    [$farmB, $userB] = makeFarm('Beta Farm');

    $this->actingAs($userA);
    $customerA = Customer::create(['customer_name' => 'Alpha Only', 'address' => 'x', 'phone' => '1']);

    $this->actingAs($userB);
    $this->post('/orders', [
        'customer_id' => $customerA->id,
        'order_date' => now()->toDateString(),
        'delivery_date' => now()->toDateString(),
        'item_name' => 'Mushrooms',
        'quantity_kg' => 2,
        'unit_price' => 100,
        'payment_status' => 'unpaid',
        'order_status' => 'pending',
    ])->assertNotFound();

    expect(Order::withoutGlobalScopes()->count())->toBe(0);
});

it('numbers orders per farm', function () {
    [$farmA, $userA] = makeFarm('Alpha Farm');
    [$farmB, $userB] = makeFarm('Beta Farm');

    $payload = fn ($customerId) => [
        'customer_id' => $customerId,
        'order_date' => now()->toDateString(),
        'delivery_date' => now()->toDateString(),
        'item_name' => 'Mushrooms',
        'quantity_kg' => 1,
        'unit_price' => 50,
        'payment_status' => 'unpaid',
        'order_status' => 'pending',
    ];

    $this->actingAs($userA);
    $cA = Customer::create(['customer_name' => 'CA', 'address' => 'x', 'phone' => '1']);
    $this->post('/orders', $payload($cA->id))->assertRedirect();

    $this->actingAs($userB);
    $cB = Customer::create(['customer_name' => 'CB', 'address' => 'x', 'phone' => '1']);
    $this->post('/orders', $payload($cB->id))->assertRedirect();

    $year = now()->year;
    expect(Order::withoutGlobalScopes()->where('farm_id', $farmA->id)->value('order_no'))->toBe("ORD-{$year}-001")
        ->and(Order::withoutGlobalScopes()->where('farm_id', $farmB->id)->value('order_no'))->toBe("ORD-{$year}-001");
});
