<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    protected $signature = 'users:list';

    protected $description = 'List all users in the database (id, name, email, role)';

    public function handle(): int
    {
        $users = User::orderBy('id')->get(['id', 'name', 'email', 'role']);

        if ($users->isEmpty()) {
            $this->warn('No users in the database.');
            return self::SUCCESS;
        }

        $this->table(
            ['id', 'name', 'email', 'role'],
            $users->map(fn (User $u) => [$u->id, $u->name, $u->email, $u->role])
        );

        $this->info('Total: ' . $users->count() . ' user(s).');
        return self::SUCCESS;
    }
}
