<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'users:create
                            {--email= : Email (default: ADMIN_EMAIL from .env)}
                            {--password= : Password (default: ADMIN_PASSWORD from .env)}
                            {--name=Admin : Display name}';

    protected $description = 'Create an admin user (uses ADMIN_EMAIL and ADMIN_PASSWORD from .env if set)';

    public function handle(): int
    {
        $email = $this->option('email') ?? config('app.admin_email', 'admin@eggcavity.local');
        $password = $this->option('password') ?? config('app.admin_password', 'password');
        $name = $this->option('name') ?: 'Admin';

        if (User::where('email', $email)->exists()) {
            $this->warn("A user with email [{$email}] already exists.");
            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->info("User created: {$email} (admin).");
        return self::SUCCESS;
    }
}
