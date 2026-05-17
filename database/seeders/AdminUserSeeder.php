<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@organett.com'],
            [
                'full_name' => 'Admin',
                'username'  => 'admin',
                'password'  => Hash::make('password123'),
                'role'      => 'admin',
                'status'    => 'active',
            ]
        );
    }
}
