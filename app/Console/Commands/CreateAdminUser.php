<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creates the first administrator account interactively.
 *
 * Production must never ship with a seeded administrator whose password is known, so
 * there is no admin seeder: use this command instead. The password is read without
 * echo and validated against the application's password policy.
 */
class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin {email : Email address for the administrator} {--name= : Display name}';

    protected $description = 'Create an administrator account interactively (no password is ever logged)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $name = (string) ($this->option('name') ?: 'Administrator');

        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:190']]);

        if ($validator->fails()) {
            $this->error('That email address is not valid.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('An account already exists for that email.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (hidden, minimum 10 characters)');
        $confirm = (string) $this->secret('Confirm password');

        if ($password !== $confirm) {
            $this->error('The passwords did not match.');

            return self::FAILURE;
        }

        if (mb_strlen($password) < 10) {
            $this->error('Choose a password of at least 10 characters.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'account_status' => 'active',
            'preferred_language' => config('app.locale'),
            'email_verified_at' => now(),
        ]);

        AuditLog::record('admin_created', User::class, $user->id, 'created via console');

        $this->info(sprintf('Administrator #%d created for %s.', $user->id, $email));

        return self::SUCCESS;
    }
}
