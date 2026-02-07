<?php

namespace App\Console\Commands;

use App\Models\ArchiveItem;
use App\Models\Item;
use App\Models\PendingTravelSuggestion;
use App\Models\TravelSuggestion;
use App\Services\ImageMatchService;
use Illuminate\Console\Command;

class SuggestTravelsByImage extends Command
{
    protected $signature = 'travels:suggest-by-image
                            {--limit= : Max creatures to process (default: all)}
                            {--travel-limit= : Max travels to score per creature (default: all with image)}
                            {--delay=0 : Seconds to wait between images (optional, to avoid hammering image hosts)}
                            {--min-score=1 : Minimum score (1-10) to accept a suggestion}
                            {--clear : Clear all pending suggestions before running}';

    protected $description = 'Suggest travels per creature by comparing images (free local color analysis). Saves pending suggestions for approval.';

    public function handle(ImageMatchService $imageMatch): int
    {
        $query = ArchiveItem::with(['stages'])->orderBy('title');
        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }
        $creatures = $query->get();

        $travels = Item::whereRaw('LOWER(use) = ?', ['travel'])
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'image_url']);

        if ($travels->isEmpty()) {
            $this->warn('No travel items with image_url found.');
            return self::SUCCESS;
        }

        $travelCount = $travels->count();
        if ($travelCount > 300) {
            $this->line("Found {$travelCount} travels with images. Use <info>--travel-limit=200</info> (or similar) for a quicker run.");
        }

        $travelLimit = $this->option('travel-limit') ? (int) $this->option('travel-limit') : null;
        $delay = (float) $this->option('delay');
        $minScore = (float) $this->option('min-score');
        $clearFirst = $this->option('clear');

        if ($clearFirst) {
            $deleted = PendingTravelSuggestion::query()->count();
            PendingTravelSuggestion::query()->delete();
            $this->info("Cleared {$deleted} pending suggestion(s).");
        }

        $created = 0;
        $travelHsvCache = [];

        foreach ($creatures as $creature) {
            $creatureImageUrl = $this->getCreatureImageUrl($creature);
            if (! $creatureImageUrl) {
                $this->line("  [skip] {$creature->title} (no image)");
                continue;
            }

            $creatureHsv = $imageMatch->getDominantHsv($creatureImageUrl);
            if ($creatureHsv === null) {
                $this->line("  [skip] {$creature->title} (could not load image)");
                if ($delay > 0) {
                    usleep((int) ($delay * 1000000));
                }
                continue;
            }
            if ($delay > 0) {
                usleep((int) ($delay * 1000000));
            }

            $toScore = $travelLimit ? $travels->take($travelLimit) : $travels;
            $toScore = $toScore->filter(fn ($travel) => stripos($travel->name, $creature->title . ' Stage') === false);
            $scores = [];

            foreach ($toScore as $travel) {
                $url = $travel->image_url;
                if (! isset($travelHsvCache[$url])) {
                    $travelHsvCache[$url] = $imageMatch->getDominantHsv($url);
                    if ($delay > 0) {
                        usleep((int) ($delay * 1000000));
                    }
                }
                $score = ImageMatchService::scoreFromHsv($creatureHsv, $travelHsvCache[$url]);
                if ($score !== null && $score >= $minScore) {
                    $scores[] = ['item' => $travel, 'score' => $score];
                }
            }

            if (empty($scores)) {
                $this->line("  [skip] {$creature->title} (no matches above min score)");
                continue;
            }

            usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);
            $top = array_slice($scores, 0, 5);

            $alreadySuggestedItemIds = array_unique(array_merge(
                TravelSuggestion::where('archive_item_id', $creature->id)->pluck('item_id')->toArray(),
                $creature->pendingTravelSuggestions()->pluck('item_id')->toArray()
            ));
            $top = array_values(array_filter($top, fn ($entry) => ! in_array($entry['item']->id, $alreadySuggestedItemIds, true)));

            if (empty($top)) {
                $this->line("  [skip] {$creature->title} (all top matches already suggested)");
                continue;
            }

            $creature->pendingTravelSuggestions()->delete();
            foreach ($top as $sortOrder => $entry) {
                PendingTravelSuggestion::create([
                    'archive_item_id' => $creature->id,
                    'item_id' => $entry['item']->id,
                    'sort_order' => $sortOrder + 1,
                ]);
                $created++;
            }

            $names = implode(', ', array_map(fn ($e) => $e['item']->name . ' (' . round($e['score'], 1) . ')', $top));
            $this->line("  [ok] {$creature->title} → {$names}");
        }

        $this->info("Done. {$created} pending image-based suggestions created.");
        return self::SUCCESS;
    }

    private function getCreatureImageUrl(ArchiveItem $creature): ?string
    {
        $stage = $creature->stages->first(fn ($s) => ! empty(trim($s->image_url ?? '')));
        if ($stage) {
            return trim($stage->image_url);
        }
        if (! empty(trim($creature->image_url ?? ''))) {
            return trim($creature->image_url);
        }
        return null;
    }
}
