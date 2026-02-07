<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WishlistSyncCreaturesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WishlistSyncCreaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $eggcaveUsername,
        public bool $clear = false,
    ) {}

    public function handle(WishlistSyncCreaturesService $service): void
    {
        $service->sync($this->user, $this->eggcaveUsername, $this->clear);
    }
}
