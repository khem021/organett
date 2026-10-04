<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
 * The platform owner belongs to no farm. Farm pages opened by hand would show
 * every farm's rows mixed together, and anything created there would belong to
 * no farm. The owner sees one farm at a time through "View as farm" instead.
 */

it('does not let the platform owner create farm records', function () {
    $this->actingAs(superAdmin())
        ->post('/customers', ['customer_name' => 'Orphan', 'phone' => '0917', 'address' => 'x'])
        ->assertRedirect(route('admin.farms.index'));

    expect(Customer::withoutGlobalScopes()->where('customer_name', 'Orphan')->exists())->toBeFalse();
});

it('does not let the platform owner change or delete another platform owner through the farm user screen', function () {
    $owner = superAdmin();
    $other = superAdmin();

    $this->actingAs($owner)
        ->put("/users/{$other->id}", ['full_name' => 'Hijacked', 'role' => 'farm_staff', 'status' => 'inactive']);
    $this->delete("/users/{$other->id}");

    $fresh = User::find($other->id);
    expect($fresh)->not->toBeNull()
        ->and($fresh->role)->toBe('super_admin')
        ->and($fresh->status)->toBe('active');
});

it('does not let the platform owner create a farm user with no farm', function () {
    $this->actingAs(superAdmin())->post('/users', [
        'full_name' => 'Nowhere', 'username' => 'nowhere', 'email' => 'nowhere@example.test',
        'password' => 'Mushr00m!Harvest', 'password_confirmation' => 'Mushr00m!Harvest', 'role' => 'farm_admin',
    ]);

    expect(User::where('username', 'nowhere')->exists())->toBeFalse();
});

it('gives the platform owner no search results from any farm', function () {
    [$farm] = makeFarm('Searchable Farm');
    seedFarmData($farm);

    $this->actingAs(superAdmin())->getJson('/search?q=Customer')->assertOk()->assertExactJson(['groups' => []]);
});

it('returns 403 rather than a redirect to a platform owner\'s JSON call on a farm route', function () {
    $this->actingAs(superAdmin())->postJson('/customers', ['customer_name' => 'x', 'phone' => '0917', 'address' => 'x'])
        ->assertForbidden();
});

it('lets farm admins use their own farm pages as before', function () {
    [$farm, $admin] = makeFarm('Normal Use Farm');

    $this->actingAs($admin)->post('/customers', ['customer_name' => 'Real', 'phone' => '0917', 'address' => 'x'])
        ->assertRedirect(route('customers.index'));

    expect(Customer::where('customer_name', 'Real')->value('farm_id'))->toBe($farm->id);
});

it('still lets the platform owner reach the user screen of a farm they are viewing as', function () {
    [$farm, $admin] = makeFarm('Viewed Users Farm');
    User::create(['farm_id' => $farm->id, 'full_name' => 'Staffer', 'username' => 'staffer1', 'email' => 'staffer1@example.test', 'password' => Hash::make('x'), 'role' => 'farm_staff', 'status' => 'active']);

    $this->actingAs(superAdmin())->post("/admin/farms/{$farm->id}/impersonate");

    $this->get('/users')->assertOk()->assertSee('Staffer');
});
