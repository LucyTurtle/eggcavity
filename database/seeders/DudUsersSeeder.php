<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DudUsersSeeder extends Seeder
{
    /** Default password for all dud users (dev/testing only). */
    private const DUD_PASSWORD = 'password';

    /**
     * Seed one user per role for viewing/testing role-based UI.
     * Safe to run multiple times (uses firstOrCreate by email).
     */
    public function run(): void
    {
        $roles = [
            User::ROLE_USER => ['name' => 'Dud User', 'email' => 'dud-user@eggcavity.local'],
            User::ROLE_ADMIN => ['name' => 'Dud Admin', 'email' => 'dud-admin@eggcavity.local'],
            User::ROLE_DEVELOPER => ['name' => 'Dud Developer', 'email' => 'dud-developer@eggcavity.local'],
            User::ROLE_CONTENT_MANAGER => ['name' => 'Dud Content Manager', 'email' => 'dud-content-manager@eggcavity.local'],
            User::ROLE_TRAVEL_SUGGESTOR => ['name' => 'Dud Travel Suggestor', 'email' => 'dud-travel-suggestor@eggcavity.local'],
        ];

        foreach ($roles as $role => $attrs) {
            User::updateOrCreate(
                ['email' => $attrs['email']],
                [
                    'name' => $attrs['name'],
                    'password' => Hash::make(self::DUD_PASSWORD),
                    'role' => $role,
                ]
            );
        }
    }
}
