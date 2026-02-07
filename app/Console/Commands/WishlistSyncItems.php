<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WishlistSyncItemsService;
use Illuminate\Console\Command;

class WishlistSyncItems extends Command
{
    protected $signature = 'wishlist:sync-items
                            {user : User (name, email, or ID) to sync item wishlist for}
                            {egg_id : Egg ID from your collection URL (e.g. 347684 from eggcave.com/egg/347684/collection)}
                            {--clear : Clear item wishlist before adding (recommended so the list is exactly "items I don\'t have")}';

    protected $description = 'Add all catalog items you don\'t have (on Eggcave) to your item wishlist. Scrapes your collection for one egg across all shops. Use --clear to replace your list.';

    public function handle(WishlistSyncItemsService $service): int
    {
        $userInput = $this->argument('user');
        $user = ctype_digit((string) $userInput)
            ? User::find($userInput)
            : User::where('email', $userInput)->orWhere('name', $userInput)->first();

        if (! $user) {
            $this->error('User not found: ' . $userInput);
            return self::FAILURE;
        }

        $eggId = (int) $this->argument('egg_id');
        if ($eggId < 1) {
            $this->error('egg_id must be a positive number (e.g. 347684 from eggcave.com/egg/347684/collection).');
            return self::FAILURE;
        }

        $clear = $this->option('clear');

        $this->info("Syncing item wishlist for {$user->name} ({$user->email}), egg ID {$eggId}...");
        $start = microtime(true);
        $result = $service->sync($user, $eggId, $clear, function (string $msg): void {
            $this->line('  ' . $msg);
        });
        $elapsed = (int) round(microtime(true) - $start);
        $totalRuntime = $elapsed >= 60
            ? (int) floor($elapsed / 60) . ' min ' . ($elapsed % 60) . ' sec'
            : $elapsed . ' sec';
        $this->info("  Found {$result['have_count']} item(s) in your collection.");
        if ($clear && $result['cleared'] > 0) {
            $this->info("  Cleared {$result['cleared']} from wishlist.");
        }
        $this->info("  Added {$result['added']} item(s) to wishlist.");
        $this->info("Total run time: {$totalRuntime}.");
        $this->info('Done.');
        return self::SUCCESS;
    }
}
