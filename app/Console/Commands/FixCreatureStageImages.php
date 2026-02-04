<?php

namespace App\Console\Commands;

use App\Models\ArchiveItem;
use Illuminate\Console\Command;

class FixCreatureStageImages extends Command
{
    protected $signature = 'creatures:fix-stage-images
                            {--dry-run : Show what would be updated without writing to the database}';

    protected $description = 'Fix stages with missing images: use first stage URL and replace # (or stage number) with each stage number.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Dry run – no changes will be written.');
        }

        $creatures = ArchiveItem::with(['stages' => fn ($q) => $q->orderBy('sort_order')])->get();
        $updated = 0;
        $skipped = 0;

        foreach ($creatures as $creature) {
            $stages = $creature->stages;
            if ($stages->isEmpty()) {
                continue;
            }

            // First stage that has a non-empty image URL to use as template
            $firstStage = $stages->first(fn ($s) => ! empty(trim($s->image_url ?? '')));
            if (! $firstStage) {
                $skipped++;
                continue;
            }

            $firstUrl = trim($firstStage->image_url);
            $firstNum = (int) $firstStage->stage_number;
            $hasHash = str_contains($firstUrl, '#');

            foreach ($stages as $stage) {
                $currentUrl = trim($stage->image_url ?? '');

                if ($hasHash) {
                    $newUrl = str_replace('#', (string) $stage->stage_number, $firstUrl);
                } else {
                    $newUrl = str_replace(
                        (string) $firstNum,
                        (string) $stage->stage_number,
                        $firstUrl
                    );
                }

                if ($newUrl === $currentUrl) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [{$creature->title}] Stage {$stage->stage_number}: would set {$newUrl}");
                } else {
                    $stage->update(['image_url' => $newUrl]);
                }
                $updated++;
            }
        }

        $this->info("Stages updated: {$updated}. Creatures skipped (no template URL): {$skipped}.");
        return self::SUCCESS;
    }
}
