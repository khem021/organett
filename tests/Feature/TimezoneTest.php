<?php

use App\Models\Customer;
use App\Models\HarvestRecord;
use App\Models\Order;
use Illuminate\Support\Carbon;

/*
 * The farms are in the Philippines, so "today", the year in an order number and
 * the month a harvest or payment belongs to follow Manila's clock, not UTC's.
 */

it('runs on Philippine time', function () {
    expect(config('app.timezone'))->toBe('Asia/Manila');
});

it('counts an order as dispatching today by the farm\'s clock, not UTC\'s', function () {
    [$farm, $admin] = makeFarm('Clock Farm');
    $d = seedFarmData($farm);
    $d['order']->update(['order_status' => 'processing', 'delivery_date' => '2026-10-05']);

    // 20:00 UTC on the 4th is already 04:00 on the 5th in Manila.
    $this->travelTo(Carbon::parse('2026-10-04 20:00:00', 'UTC'));

    $this->actingAs($admin)->get('/dashboard')->assertOk()->assertViewHas('dispatchingToday', 1);
});

it('numbers an order with the new year as soon as it is New Year in Manila', function () {
    [$farm, $admin] = makeFarm('New Year Farm');
    $customer = Customer::create(['farm_id' => $farm->id, 'customer_name' => 'C', 'phone' => '0917', 'address' => 'x']);

    $this->travelTo(Carbon::parse('2026-12-31 20:00:00', 'UTC')); // 04:00 on 1 January in Manila

    $this->actingAs($admin)->post('/orders', [
        'customer_id' => $customer->id, 'order_date' => '2027-01-01', 'delivery_date' => '2027-01-02',
        'item_name' => 'Oyster', 'quantity_kg' => 1, 'unit_price' => 100,
        'payment_status' => 'unpaid', 'order_status' => 'pending',
    ])->assertSessionHasNoErrors();

    expect(Order::value('order_no'))->toBe('ORD-2027-001');
});

it('does not count the same month of another year as this month\'s harvest', function () {
    [$farm, $admin] = makeFarm('Same Month Farm');
    $d = seedFarmData($farm);
    HarvestRecord::query()->delete();
    $this->travelTo(Carbon::parse('2026-10-15 09:00:00', 'Asia/Manila'));

    foreach (['2025-10-15' => 40, '2026-10-10' => 7.5, '2026-09-30' => 5] as $date => $kg) {
        HarvestRecord::create(['farm_id' => $farm->id, 'batch_id' => $d['batch']->id, 'harvest_date' => $date, 'quantity_kg' => $kg, 'quality_grade' => 'A']);
    }

    $this->actingAs($admin)->get('/harvest')->assertOk()->assertViewHas('monthKg', 7.5);
});
