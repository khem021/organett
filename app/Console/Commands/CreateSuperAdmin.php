<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command
{
    protected $signature = 'organett:create-superadmin
                            {--email= : Email address for the new super admin}
                            {--name= : Full name for the new super admin}
                            {--disable-demo-accounts : Deactivate and scramble the seeded @organett.local demo accounts}';

    protected $description = 'Create a super admin account with a strong password (prompted, never passed as an argument)';

    private const DEMO_EMAILS = [
        'superadmin@organett.local',
        'admin@organett.local',
        'staff@organett.local',
        'coordinator@organett.local',
    ];

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email');
        $name = $this->option('name') ?: $this->ask('Full name', 'Organett Super Admin');
        $password = $this->secret('Password (min 10 chars, mixed case, number, symbol)');
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make(
            ['email' => $email, 'full_name' => $name, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'email' => ['required', 'email', 'unique:users,email'],
                'full_name' => ['required', 'string', 'max:150'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User([
            'full_name' => $name,
            'username' => Str::slug(Str::before($email, '@')).'-'.Str::lower(Str::random(4)),
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->farm_id = null;
        $user->role = 'super_admin';
        $user->status = 'active';
        $user->save();

        $this->info("Super admin {$email} created.");

        if ($this->option('disable-demo-accounts')) {
            $count = 0;
            User::whereIn('email', self::DEMO_EMAILS)->each(function (User $demo) use (&$count) {
                $demo->forceFill([
                    'status' => 'inactive',
                    'password' => Hash::make(Str::random(64)),
                    'remember_token' => Str::random(60),
                ])->save();
                $count++;
            });

            $this->info("Disabled {$count} demo account(s).");
        }

        return self::SUCCESS;
    }
}
