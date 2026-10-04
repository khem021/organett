<?php

use App\Models\Customer;
use App\Models\Farm;
use App\Models\FarmFeature;
use App\Models\HarvestRecord;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a platform super admin, who belongs to no farm.
 */
function superAdmin(): User
{
    return User::create([
        'farm_id' => null,
        'full_name' => 'Platform Owner',
        'username' => 'owner'.uniqid(),
        'email' => 'owner-'.uniqid().'@example.test',
        'password' => Hash::make('Sup3r!Secure#Pass'),
        'role' => 'super_admin',
        'status' => 'active',
    ]);
}

/**
 * Create a farm plus one user attached to it.
 *
 * @return array{0: Farm, 1: User}
 */
function makeFarm(string $name, string $role = 'farm_admin', array $features = []): array
{
    $farm = Farm::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.uniqid(),
        'status' => 'active',
    ]);

    foreach ($features as $key => $enabled) {
        FarmFeature::create([
            'farm_id' => $farm->id,
            'feature_key' => $key,
            'is_enabled' => $enabled,
        ]);
    }

    $user = User::create([
        'farm_id' => $farm->id,
        'full_name' => $name.' Admin',
        'username' => Str::slug($name).uniqid(),
        'email' => Str::slug($name).'-'.uniqid().'@example.test',
        'password' => Hash::make('secret123'),
        'role' => $role,
        'status' => 'active',
    ]);

    return [$farm, $user];
}

/**
 * Give a farm one of everything, created without a signed-in user so the
 * farm_id has to be stated explicitly. Used to check what each role can reach.
 *
 * @return array{batch: ProductionBatch, harvest: HarvestRecord, item: Inventory, customer: Customer, order: Order, sale: Sale}
 */
function seedFarmData(Farm $farm, string $tag = 'A'): array
{
    $batch = ProductionBatch::create([
        'farm_id' => $farm->id, 'batch_code' => "B-{$tag}-001", 'substrate_type' => 'Sawdust',
        'spawn_type' => 'Oyster', 'inoculation_date' => '2026-09-01', 'expected_harvest_date' => '2026-10-01',
        'status' => 'fruiting',
    ]);
    $harvest = HarvestRecord::create([
        'farm_id' => $farm->id, 'batch_id' => $batch->id, 'harvest_date' => '2026-10-02',
        'quantity_kg' => 12.5, 'quality_grade' => 'A',
    ]);
    $item = Inventory::create([
        'farm_id' => $farm->id, 'item_name' => "Spawn {$tag}", 'category' => 'Supplies', 'unit' => 'pcs',
        'stock_qty' => 50, 'reorder_level' => 10,
    ]);
    $customer = Customer::create([
        'farm_id' => $farm->id, 'customer_name' => "Customer {$tag}", 'phone' => '0917 000 0000', 'address' => 'Laguna',
    ]);
    $order = Order::create([
        'farm_id' => $farm->id, 'order_no' => "ORD-2026-00{$farm->id}", 'customer_id' => $customer->id,
        'order_date' => '2026-10-01', 'delivery_date' => '2026-10-03', 'item_name' => 'Oyster',
        'quantity_kg' => 5, 'unit_price' => 100, 'total_amount' => 500,
        'payment_status' => 'partial', 'order_status' => 'processing',
    ]);
    $sale = Sale::create([
        'farm_id' => $farm->id, 'order_id' => $order->id, 'customer_id' => $customer->id,
        'sale_date' => '2026-10-02', 'quantity_kg' => 2, 'amount' => 200, 'payment_method' => 'Cash',
    ]);

    return compact('batch', 'harvest', 'item', 'customer', 'order', 'sale');
}

/**
 * First validation message for a field after a redirect. The error bag comes back
 * as an object or as a plain array depending on how the session store hands it over.
 */
function firstError(string $field): ?string
{
    $errors = session('errors');

    if ($errors instanceof ViewErrorBag) {
        $errors = $errors->getBag('default');
    }

    if ($errors instanceof MessageBag) {
        return $errors->first($field) ?: null;
    }

    return $errors['default']['messages'][$field][0] ?? $errors['messages'][$field][0] ?? $errors[$field][0] ?? null;
}

/** Assert a response status, naming what was being requested when it is wrong. */
function expectStatus(TestResponse $response, int $status, string $what): void
{
    expect($response->getStatusCode())->toBe($status, "{$what}: expected {$status}, got {$response->getStatusCode()}");
}

/** Assert a redirect to a path, naming what was being requested when it is wrong. */
function expectRedirectTo(TestResponse $response, string $path, string $what): void
{
    $location = parse_url((string) $response->headers->get('Location'), PHP_URL_PATH);

    expect($location)->toBe(parse_url($path, PHP_URL_PATH), "{$what}: expected redirect to {$path}, got status {$response->getStatusCode()} ".($location ?: '(no redirect)'));
}
