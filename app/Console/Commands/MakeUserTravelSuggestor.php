<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserTravelSuggestor extends Command
{
    protected $signature = 'users:make-travel-suggestor
                            {user : User name, email, or ID}';

    protected $description = 'Set a user\'s role to travel_suggestor (can manage travel suggestions)';

    public function handle(): int
    {
        $userInput = $this->argument('user');

        $user = $this->findUser($userInput);

        if (! $user) {
            $this->error('User not found.');
            return self::FAILURE;
        }

        if ($user->role === User::ROLE_TRAVEL_SUGGESTOR) {
            $this->warn("User [{$user->name}] ({$user->email}) is already a travel suggestor.");
            return self::SUCCESS;
        }

        $user->update(['role' => User::ROLE_TRAVEL_SUGGESTOR]);
        $this->info("User [{$user->name}] ({$user->email}) is now a travel suggestor.");
        return self::SUCCESS;
    }

    private function findUser(string $userInput): ?User
    {
        if (is_numeric($userInput)) {
            return User::find($userInput);
        }

        return User::where('name', $userInput)->orWhere('email', $userInput)->first();
    }
}
