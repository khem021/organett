<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('throttles repeated login attempts for the same account', function () {
    [, $user] = makeFarm('Throttle Farm');

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertStatus(429);
});

it('throttles password reset requests', function () {
    Notification::fake();

    foreach (range(1, 3) as $i) {
        $this->post('/forgot-password', ['email' => "nobody{$i}@example.test"])->assertRedirect();
    }

    $this->post('/forgot-password', ['email' => 'nobody4@example.test'])->assertStatus(429);
});

it('gives the same forgot-password response for known and unknown emails', function () {
    Notification::fake();
    [, $user] = makeFarm('Enum Farm');

    $known = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
    $unknown = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'ghost@example.test']);

    $known->assertRedirect('/forgot-password')->assertSessionHasNoErrors();
    $unknown->assertRedirect('/forgot-password')->assertSessionHasNoErrors();
    expect($known->getSession()->get('status'))->toBe($unknown->getSession()->get('status'));
});

it('rejects weak passwords on public farm registration', function () {
    $this->post('/register/farm', [
        'farm_name' => 'Weak Farm',
        'full_name' => 'Weak Owner',
        'email' => 'weak@example.test',
        'password' => 'password1',
        'password_confirmation' => 'password1',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'weak@example.test')->exists())->toBeFalse();
});

it('accepts a strong password on public farm registration', function () {
    $this->post('/register/farm', [
        'farm_name' => 'Strong Farm',
        'full_name' => 'Strong Owner',
        'email' => 'strong@example.test',
        'password' => 'Mushr00m!Harvest',
        'password_confirmation' => 'Mushr00m!Harvest',
    ])->assertRedirect('/dashboard');

    expect(User::where('email', 'strong@example.test')->value('role'))->toBe('farm_admin');
});

it('does not expose another farms orders by id or through search', function () {
    [, $userA] = makeFarm('Alpha Farm');
    [, $userB] = makeFarm('Beta Farm');

    $this->actingAs($userA);
    $customer = Customer::create(['customer_name' => 'Alpha Secret Buyer', 'address' => 'x', 'phone' => '1']);
    $order = Order::create([
        'order_no' => 'ORD-ALPHA-001',
        'customer_id' => $customer->id,
        'order_date' => now(), 'delivery_date' => now(),
        'item_name' => 'Mushrooms', 'quantity_kg' => 1, 'unit_price' => 10, 'total_amount' => 10,
        'payment_status' => 'unpaid', 'order_status' => 'pending',
    ]);

    $this->actingAs($userB);
    $this->get("/orders/{$order->id}")->assertNotFound();
    $this->getJson('/search?q=Alpha')->assertOk()->assertExactJson(['groups' => []]);
});

it('shows nothing to a farm user who has no farm assigned', function () {
    Customer::create(['customer_name' => 'Orphan Row', 'address' => 'x', 'phone' => '1']);
    expect(Customer::withoutGlobalScopes()->whereNull('farm_id')->count())->toBe(1);

    $orphan = User::create([
        'farm_id' => null,
        'full_name' => 'No Farm Staff',
        'username' => 'nofarm'.uniqid(),
        'email' => 'nofarm@example.test',
        'password' => Hash::make('secret123'),
        'role' => 'farm_staff',
        'status' => 'active',
    ]);

    $this->actingAs($orphan);
    expect(Customer::count())->toBe(0);
});

it('sends security headers on web responses', function () {
    $this->get('/login')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Content-Security-Policy');

    expect($this->get('/login')->headers->get('Content-Security-Policy'))
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'");
});

it('never seeds demo accounts outside local and testing', function () {
    $this->app['env'] = 'production';

    app(DatabaseSeeder::class)->__invoke();

    expect(User::count())->toBe(0);
});

it('creates a super admin and disables the demo accounts', function () {
    $demo = User::create([
        'farm_id' => null,
        'full_name' => 'Demo Admin',
        'username' => 'demoadmin',
        'email' => 'admin@organett.local',
        'password' => Hash::make('admin123'),
        'role' => 'farm_admin',
        'status' => 'active',
    ]);

    $this->artisan('organett:create-superadmin', ['--email' => 'owner@example.test', '--name' => 'Owner', '--disable-demo-accounts' => true])
        ->expectsQuestion('Password (min 10 chars, mixed case, number, symbol)', 'Sup3r!Secure#Pass')
        ->expectsQuestion('Confirm password', 'Sup3r!Secure#Pass')
        ->assertSuccessful();

    $owner = User::where('email', 'owner@example.test')->first();
    expect($owner->role)->toBe('super_admin')
        ->and($owner->farm_id)->toBeNull()
        ->and($demo->fresh()->status)->toBe('inactive')
        ->and(Hash::check('admin123', $demo->fresh()->password))->toBeFalse();
});

it('refuses a weak super admin password', function () {
    $this->artisan('organett:create-superadmin', ['--email' => 'owner@example.test', '--name' => 'Owner'])
        ->expectsQuestion('Password (min 10 chars, mixed case, number, symbol)', 'admin123')
        ->expectsQuestion('Confirm password', 'admin123')
        ->assertFailed();

    expect(User::where('email', 'owner@example.test')->exists())->toBeFalse();
});
