<?php

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Carbon;

it('ranks top customers by what they paid, with customers who have paid nothing last', function () {
    [$farm, $admin] = makeFarm('Ranking Farm');
    $d = seedFarmData($farm); // "Customer A" has ₱200 in payments
    $big = Customer::create(['farm_id' => $farm->id, 'customer_name' => 'Big Spender', 'phone' => '0917', 'address' => 'x']);
    Customer::create(['farm_id' => $farm->id, 'customer_name' => 'Never Paid', 'phone' => '0917', 'address' => 'x']);
    Sale::create(['farm_id' => $farm->id, 'order_id' => $d['order']->id, 'customer_id' => $big->id, 'sale_date' => '2026-10-02', 'quantity_kg' => 1, 'amount' => 300, 'payment_method' => 'Cash']);

    $names = $this->actingAs($admin)->get('/reports')->assertOk()->viewData('topCustomers')->pluck('customer_name')->all();

    expect($names)->toBe(['Big Spender', 'Customer A', 'Never Paid']);
});

it('counts a payment in the month it was made, and not the same month of another year', function () {
    [$farm, $admin] = makeFarm('Revenue Month Farm');
    $d = seedFarmData($farm);
    Sale::query()->delete();
    $this->travelTo(Carbon::parse('2026-10-15 09:00:00', 'Asia/Manila'));

    foreach (['2025-10-15' => 1000, '2026-10-01' => 250, '2026-09-30' => 90] as $date => $amount) {
        Sale::create(['farm_id' => $farm->id, 'order_id' => $d['order']->id, 'customer_id' => $d['customer']->id, 'sale_date' => $date, 'quantity_kg' => 1, 'amount' => $amount, 'payment_method' => 'Cash']);
    }

    $response = $this->actingAs($admin)->get('/reports')->assertOk();

    expect((float) $response->viewData('monthRevenue'))->toBe(250.0)
        ->and((float) $response->viewData('lastMonthRevenue'))->toBe(90.0)
        ->and((float) $response->viewData('totalRevenue'))->toBe(1340.0);
});
