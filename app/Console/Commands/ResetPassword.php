<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountRecovery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ResetPassword extends Command
{
    protected $signature = 'organett:reset-password
                            {--email= : Email address of the account to reset}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Reset one existing account\'s password (prompted, never passed as an argument)';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email of the account to reset');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No account found for {$email}. Nothing was changed.");

            return self::FAILURE;
        }

        $this->table(
            ['Name', 'Username', 'Role', 'Status', 'Farm'],
            [[$user->full_name, $user->username, $user->role, $user->status, $user->farm_id ?? '— (all farms)']],
        );

        if ($user->status !== 'active') {
            $this->warn("This account is {$user->status}; a new password alone will not let it sign in.");
        }

        if (! $this->option('force') && ! $this->confirm("Reset the password for {$user->email}?")) {
            $this->warn('Aborted. Nothing was changed.');

            return self::FAILURE;
        }

        $password = $this->secret('New password (min 10 chars, mixed case, number, symbol)');
        $confirmation = $this->secret('Confirm new password');

        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => ['required', 'confirmed', Password::defaults()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            $this->warn('Nothing was changed.');

            return self::FAILURE;
        }

        $sessions = AccountRecovery::resetPassword(
            $user,
            $password,
            "Password reset from the console for {$user->email}.",
        );

        $this->info("Password reset for {$user->email}. Cleared {$sessions} active session(s).");

        return self::SUCCESS;
    }
}
