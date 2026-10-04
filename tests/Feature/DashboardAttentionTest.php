<?php

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Support\Carbon;

/*
 * The first screen answers "what needs my attention today?": deliveries that are
 * late, money still owed, stock running out and harvests that are due.
 */

beforeEach(function () {
    // 09:00 on 4 October in Manila.
    $this->travelTo(Carbon::parse('2026-10-04 09:00:00', 'Asia/Manila'));
});

function extraOrder(int $farmId, Customer $customer, string $no, array $over = []): Order
{
    return Order::create(array_merge([
        'farm_id' => $farmId, 'order_no' => $no, 'customer_id' => $customer->id,
        'order_date' => '2026-09-20', 'delivery_date' => '2026-10-10', 'item_name' => 'Oyster',
        'quantity_kg' => 1, 'unit_price' => 100, 'total_amount' => 100,
        'payment_status' => 'unpaid', 'order_status' => 'pending',
    ], $over));
}

it('counts late deliveries, money owed, low stock and harvests due', function () {
    [$farm, $admin] = makeFarm('Busy Today Farm');
    $d = seedFarmData($farm); // processing, due 3 Oct (late), ₱500 with ₱200 paid; batch fruiting, due 1 Oct
    extraOrder($farm->id, $d['customer'], 'ORD-2026-101');                                                               // unpaid ₱100, not late
    extraOrder($farm->id, $d['customer'], 'ORD-2026-102', ['order_status' => 'cancelled', 'delivery_date' => '2026-09-01']); // cancelled: ignored
    extraOrder($farm->id, $d['customer'], 'ORD-2026-103', ['order_status' => 'completed', 'payment_status' => 'paid', 'delivery_date' => '2026-09-01']); // done
    Inventory::create(['farm_id' => $farm->id, 'item_name' => 'Nearly out', 'category' => 'c', 'unit' => 'pcs', 'stock_qty' => 2, 'reorder_level' => 10]);

    $response = $this->actingAs($admin)->get('/dashboard')->assertOk();

    expect($response->viewData('attention'))->toMatchArray([
        'overdueOrders' => 1,
        'openOrders' => 2,
        'outstanding' => '400.00',
        'lowStock' => 1,
        'harvestsDue' => 1,
    ]);
    $response->assertSee('Needs your attention today')
        ->assertSee('1 delivery overdue')
        ->assertSee('₱400.00 unpaid across 2 orders')
        ->assertSee('1 item low on stock')
        ->assertSee('1 harvest due');
});

it('does not call a delivery due today late', function () {
    [$farm, $admin] = makeFarm('Due Today Farm');
    $d = seedFarmData($farm);
    $d['order']->update(['delivery_date' => '2026-10-04']);

    $this->actingAs($admin)->get('/dashboard')->assertViewHas('attention', fn ($a) => $a['overdueOrders'] === 0);
});

it('says so when nothing needs attention', function () {
    [$farm, $admin] = makeFarm('Calm Farm');

    $this->actingAs($admin)->get('/dashboard')->assertOk()
        ->assertSee('Nothing needs your attention today');
});

it('keeps one farm\'s late deliveries and debts out of another farm\'s dashboard', function () {
    [$farmA, $adminA] = makeFarm('Attention A');
    [$farmB] = makeFarm('Attention B');
    seedFarmData($farmB, 'B');

    $this->actingAs($adminA)->get('/dashboard')->assertViewHas('attention', fn ($a) => $a['overdueOrders'] === 0 && $a['openOrders'] === 0);
});

it('lists only the late orders when asked', function () {
    [$farm, $admin] = makeFarm('Late Filter Farm');
    $d = seedFarmData($farm);
    extraOrder($farm->id, $d['customer'], 'ORD-2026-201'); // not late

    $this->actingAs($admin)->get('/orders?overdue=1')->assertOk()
        ->assertSee($d['order']->order_no)
        ->assertDontSee('ORD-2026-201');
});

it('lists orders that still owe money, but not paid or cancelled ones', function () {
    [$farm, $admin] = makeFarm('Open Filter Farm');
    $d = seedFarmData($farm);
    extraOrder($farm->id, $d['customer'], 'ORD-2026-301');
    extraOrder($farm->id, $d['customer'], 'ORD-2026-302', ['payment_status' => 'paid']);
    extraOrder($farm->id, $d['customer'], 'ORD-2026-303', ['order_status' => 'cancelled']);

    $this->actingAs($admin)->get('/orders?payment=open')->assertOk()
        ->assertSee($d['order']->order_no)
        ->assertSee('ORD-2026-301')
        ->assertDontSee('ORD-2026-302')
        ->assertDontSee('ORD-2026-303');
});
