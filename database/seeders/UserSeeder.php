<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::exists()) {
            return;
        }

        User::create([
            'name' => 'Admin',
            'email' => config('app.admin_email', 'admin@eggcavity.local'),
            'password' => Hash::make(config('app.admin_password', 'password')),
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
