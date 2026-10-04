<?php

use App\Models\User;
use Illuminate\Support\Str;

/*
 * Sign-up and user creation have to stop what the database cannot hold: usernames
 * are 80 characters, names and emails 150, and PostgreSQL answers an oversize value
 * with an exception, which the visitor sees as a 500.
 */

function registrationForm(array $over = []): array
{
    return array_merge([
        'farm_name' => 'Limit Farm '.Str::random(5),
        'full_name' => 'Limit Tester',
        'email' => 'limit'.Str::lower(Str::random(8)).'@example.test',
        'password' => 'Mushr00m!Harvest',
        'password_confirmation' => 'Mushr00m!Harvest',
    ], $over);
}

it('rejects a full name longer than the column holds instead of crashing', function () {
    $this->post('/register/farm', registrationForm(['full_name' => str_repeat('a', 200)]))
        ->assertSessionHasErrors('full_name');
});

it('rejects an email longer than the column holds instead of crashing', function () {
    $long = str_repeat('a', 145).'@example.test';

    $this->post('/register/farm', registrationForm(['email' => $long]))
        ->assertSessionHasErrors('email');
});

it('keeps the generated username inside its column for a very long name', function () {
    $name = str_repeat('Maximiliano ', 12); // 144 characters, valid for full_name

    $this->post('/register/farm', registrationForm(['full_name' => trim($name)]))->assertRedirect('/login');

    expect(strlen(User::where('role', 'farm_admin')->latest('id')->value('username')))->toBeLessThanOrEqual(80);
});

it('gives two people with the same non-Latin name different usernames', function () {
    // Str::slug() reduces "李雷" to nothing, so the username is only the random suffix.
    mt_srand(11);
    $this->post('/register/farm', registrationForm(['full_name' => '李雷']))->assertRedirect('/login');
    mt_srand(11);
    $this->post('/register/farm', registrationForm(['full_name' => '李雷']))->assertRedirect('/login');

    expect(User::where('role', 'farm_admin')->pluck('username')->unique())->toHaveCount(2);
});

it('stores emails in lower case and refuses a second sign-up that differs only by case', function () {
    $this->post('/register/farm', registrationForm(['email' => 'Case.Owner@Example.test']))->assertRedirect('/login');

    expect(User::where('role', 'farm_admin')->latest('id')->value('email'))->toBe('case.owner@example.test');

    $this->post('/register/farm', registrationForm(['email' => 'CASE.OWNER@example.test']))
        ->assertSessionHasErrors('email');
});

it('rejects an email longer than the column holds when a farm admin adds a user', function () {
    [$farm, $admin] = makeFarm('Long Email Farm');

    $this->actingAs($admin)->post('/users', [
        'full_name' => 'Long Email', 'username' => 'longemail', 'email' => str_repeat('a', 145).'@example.test',
        'password' => 'Mushr00m!Harvest', 'password_confirmation' => 'Mushr00m!Harvest', 'role' => 'farm_staff',
    ])->assertSessionHasErrors('email');
});

it('stores a new user\'s email in lower case', function () {
    [$farm, $admin] = makeFarm('Lower Case Farm');

    $this->actingAs($admin)->post('/users', [
        'full_name' => 'Mixed Case', 'username' => 'mixedcase', 'email' => 'Mixed.Case@Example.test',
        'password' => 'Mushr00m!Harvest', 'password_confirmation' => 'Mushr00m!Harvest', 'role' => 'farm_staff',
    ])->assertSessionHasNoErrors();

    expect(User::where('username', 'mixedcase')->value('email'))->toBe('mixed.case@example.test');
});
