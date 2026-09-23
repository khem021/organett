<?php

use App\Models\Farm;
use App\Models\FarmFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
