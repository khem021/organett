<?php

use App\Models\Farm;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

/*
 * OrganettSeeder inserts rows with fixed ids (farm 1, users 1, 2, 3 and 99, ...).
 * SQLite quietly carries on from the highest id, but a PostgreSQL sequence does not
 * know about rows inserted that way, so the first new farm, user or order after
 * seeding is handed an id that is already taken and fails with a 500.
 */

it('lets a new farm register after the demo data is seeded', function () {
    $this->seed(DatabaseSeeder::class);

    $this->post('/register/farm', [
        'farm_name' => 'After Seed Farm', 'full_name' => 'After Seed', 'email' => 'afterseed@example.test',
        'password' => 'Mushr00m!Harvest', 'password_confirmation' => 'Mushr00m!Harvest',
    ])->assertRedirect('/login');

    expect(Farm::where('name', 'After Seed Farm')->exists())->toBeTrue();
});

it('leaves every seeded table ready to hand out an unused id', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Only PostgreSQL keeps a separate sequence that can fall behind.');
    }

    $this->seed(DatabaseSeeder::class);

    foreach (['users', 'farms', 'farm_features', 'settings', 'inventory', 'production_batches', 'harvest_records', 'customers', 'orders', 'deliveries', 'sales', 'alerts', 'activity_logs'] as $table) {
        $max = (int) DB::table($table)->max('id');
        $next = (int) DB::selectOne("select nextval(pg_get_serial_sequence('{$table}', 'id')) as n")->n;

        expect($next > $max)->toBeTrue("{$table}: next id {$next} is not above the highest seeded id {$max}");
    }
});
