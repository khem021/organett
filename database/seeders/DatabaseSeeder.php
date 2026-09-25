<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo data ships well-known passwords; it must never reach production.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Skipping demo seed outside local/testing. Use `php artisan organett:create-superadmin`.');

            return;
        }

        $this->call([
            OrganettSeeder::class,
        ]);
    }
}
