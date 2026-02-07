<?php

namespace App\Console\Commands;

use App\Jobs\WishlistSyncCreaturesJob;
use App\Models\User;
use App\Services\WishlistSyncCreaturesService;
use Illuminate\Console\Command;

class WishlistSyncCreatures extends Command
{
    protected $signature = 'wishlist:sync-creatures
                            {username : Your username (same on eggcavity and Eggcave): email, name, or user ID}
                            {--clear : Clear creature wishlist before adding (recommended so the list is exactly "creatures I don\'t have")}
                            {--queue : Run as a queued job instead of inline}';

    protected $description = 'Add all archive creatures you don\'t own (on Eggcave) to your creature wishlist. Use --clear to replace your list.';

    public function handle(WishlistSyncCreaturesService $service): int
    {
        $userInput = $this->argument('username');
        $user = ctype_digit($userInput)
            ? User::find($userInput)
            : User::where('email', $userInput)->orWhere('name', $userInput)->first();

        if (! $user) {
            $this->error('User not found: ' . $userInput);
            return self::FAILURE;
        }

        // Same username is used for Eggcave profile (e.g. eggcave.com/@username)
        $eggcaveUsername = ctype_digit($userInput) ? $user->name : $userInput;
        if ($eggcaveUsername === null || $eggcaveUsername === '') {
            $this->error('Could not determine Eggcave username (user ID given but user has no name).');
            return self::FAILURE;
        }
        $clear = $this->option('clear');
        $useQueue = $this->option('queue');

        if ($useQueue) {
            WishlistSyncCreaturesJob::dispatch($user, $eggcaveUsername, $clear);
            $this->info("Job dispatched for user {$user->name} ({$user->email}). Run the queue worker to process it.");
            return self::SUCCESS;
        }

        $this->info("Fetching Eggcave profile @{$eggcaveUsername}...");
        $start = microtime(true);
        $result = $service->sync($user, $eggcaveUsername, $clear, function (string $msg): void {
            $this->line('  ' . $msg);
        });
        $elapsed = (int) round(microtime(true) - $start);
        $totalRuntime = $elapsed >= 60
            ? (int) floor($elapsed / 60) . ' min ' . ($elapsed % 60) . ' sec'
            : $elapsed . ' sec';
        $this->info("  Found {$result['have_count']} species on Eggcave.");
        if ($clear && $result['cleared'] > 0) {
            $this->info("  Cleared {$result['cleared']} from wishlist.");
        }
        $this->info("  Added {$result['added']} creature(s) to wishlist.");
        $this->info("Total run time: {$totalRuntime}.");
        $this->info('Done.');
        return self::SUCCESS;
    }
}
