<?php

namespace App\Console\Commands;

use App\Services\EggcaveItemsScraper;
use Illuminate\Console\Command;

class ScrapeEggcaveItems extends Command
{
    protected $signature = 'items:scrape
                            {--delay=0.1 : Seconds to wait between requests (0 for fastest, default: 0.1)}
                            {--limit= : Max number of items to scrape (default: all)}';

    protected $description = 'Scrape EggCave items (listing + all subpages) and store in DB (images as URLs only).';

    public function handle(EggcaveItemsScraper $scraper): int
    {
        $scraper->setLogger(fn (string $message) => $this->info($message));

        $delay = (float) $this->option('delay');
        if ($delay >= 0) {
            $scraper->setDelay($delay);
        }

        $limit = $this->option('limit');
        $limit = $limit !== null && $limit !== '' ? (int) $limit : null;

        try {
            $result = $scraper->scrape($limit);
        } catch (\Throwable $e) {
            $this->error('Scrape failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Done. Total: {$result['total']}, Created: {$result['created']}, Updated: {$result['updated']}.");
        return self::SUCCESS;
    }
}
