<?php

namespace App\Console\Commands;

use App\Services\EggcaveArchiveScraper;
use Illuminate\Console\Command;

class ScrapeEggcaveArchives extends Command
{
    protected $signature = 'archive:scrape
                            {--delay=0.1 : Seconds to wait between requests (0 for fastest, default: 0.1)}
                            {--limit= : Max number of items to scrape (default: all)}
                            {--full : Re-scrape everything; default is only new (not yet in DB)}';

    protected $description = 'Scrape EggCave archives. By default only fetches new creatures (not already in DB). Use --full to re-scrape all.';

    public function handle(EggcaveArchiveScraper $scraper): int
    {
        $scraper->setLogger(fn (string $message) => $this->info($message));

        $delay = (float) $this->option('delay');
        if ($delay >= 0) {
            $scraper->setDelay($delay);
        }

        $limit = $this->option('limit');
        $limit = $limit !== null && $limit !== '' ? (int) $limit : null;

        $newOnly = ! $this->option('full');
        try {
            $result = $scraper->scrape($limit, $newOnly);
        } catch (\Throwable $e) {
            $this->error('Scrape failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Done. Total: {$result['total']}, Created: {$result['created']}, Updated: {$result['updated']}.");
        return self::SUCCESS;
    }
}
