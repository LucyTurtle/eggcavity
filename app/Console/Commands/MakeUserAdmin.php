<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    protected $signature = 'users:make-admin
                            {user : User email or ID}';

    protected $description = 'Set a user\'s role to admin';

    public function handle(): int
    {
        $userInput = $this->argument('user');

        $user = is_numeric($userInput)
            ? User::find($userInput)
            : User::where('email', $userInput)->first();

        if (! $user) {
            $this->error('User not found.');
            return self::FAILURE;
        }

        if ($user->role === User::ROLE_ADMIN) {
            $this->warn("User [{$user->email}] is already an admin.");
            return self::SUCCESS;
        }

        $user->update(['role' => User::ROLE_ADMIN]);
        $this->info("User [{$user->email}] is now an admin.");
        return self::SUCCESS;
    }
}
