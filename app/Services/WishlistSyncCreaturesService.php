<?php

namespace App\Services;

use App\Models\ArchiveItem;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class WishlistSyncCreaturesService
{
    private const EGGCAVE_BASE = 'https://eggcave.com';
    private const REQUEST_DELAY_MS_MIN = 45;
    private const REQUEST_DELAY_MS_MAX = 1500;
    /** Average seconds per HTTP request (network latency) for run-time estimate */
    private const ESTIMATE_REQUEST_SEC = 0.2;

    private function httpHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
    }

    /**
     * Scan the Eggcave user page for links, follow them to the user's creature pages,
     * store the creature type (archive slug) of each. Compare that list to the creatures
     * database (archive) and add any missing creatures to the user's wishlist.
     * Optionally clear the wishlist first.
     *
     * @param  callable(string): void|null  $onProgress  Optional callback for console progress (message string).
     * @return array{cleared: int, added: int, have_count: int, to_add_count: int}
     */
    public function sync(User $user, string $eggcaveUsername, bool $clear = false, ?callable $onProgress = null): array
    {
        $haveSlugs = $this->fetchProfileArchiveSlugs($eggcaveUsername, $onProgress);
        $archiveItems = ArchiveItem::orderBy('title')->get(['id', 'title', 'slug']);
        $toAdd = $archiveItems->filter(function ($item) use ($haveSlugs) {
            return $item->slug !== null && $item->slug !== '' && ! isset($haveSlugs[$item->slug]);
        });

        $cleared = 0;
        if ($clear) {
            $cleared = $user->creatureWishlists()->count();
            $user->creatureWishlists()->delete();
        }

        $added = 0;
        foreach ($toAdd as $item) {
            $user->creatureWishlists()->updateOrCreate(
                ['archive_item_id' => $item->id],
                ['amount' => 1, 'gender' => null, 'notes' => null, 'stage_number' => null]
            );
            $added++;
        }

        return [
            'cleared' => $cleared,
            'added' => $added,
            'have_count' => count($haveSlugs),
            'to_add_count' => $toAdd->count(),
        ];
    }

    /**
     * User profile page has no /archives/ links; it has /click/{id} links (cove-lists).
     * For each click ID we request the creature page at /egg/{id} and parse it for the archive slug (h1 link to /archives/slug).
     *
     * @param  callable(string): void|null  $onProgress
     * @return array<string, true> slug => true
     */
    private function fetchProfileArchiveSlugs(string $eggcaveUsername, ?callable $onProgress = null): array
    {
        $url = self::EGGCAVE_BASE . '/@' . $eggcaveUsername;
        $response = Http::withHeaders($this->httpHeaders())->timeout(30)->get($url);
        if (! $response->successful()) {
            return [];
        }
        $html = $response->body();
        $clickIds = [];
        if (preg_match_all('/href\s*=\s*[\'"](?:https?:\/\/[^\'"]*?)?\/click\/(\d+)[\'"\s?#]/i', $html, $matches)) {
            foreach ($matches[1] as $id) {
                $clickIds[$id] = true;
            }
        }
        $total = count($clickIds);
        if ($onProgress !== null) {
            $onProgress("{$total} creature link(s) found on profile.");
            if ($total > 0) {
                $avgDelaySec = (self::REQUEST_DELAY_MS_MIN + self::REQUEST_DELAY_MS_MAX) / 2 / 1000;
                $estimatedSec = (int) ceil($total * ($avgDelaySec + self::ESTIMATE_REQUEST_SEC));
                if ($estimatedSec < 60) {
                    $onProgress('Estimated run time: ~' . $estimatedSec . ' second(s).');
                } else {
                    $min = (int) floor($estimatedSec / 60);
                    $sec = $estimatedSec % 60;
                    $onProgress($sec > 0
                        ? "Estimated run time: ~{$min} min " . $sec . ' sec.'
                        : "Estimated run time: ~{$min} minute(s).");
                }
            }
        }
        $haveSlugs = [];
        $processed = 0;
        $lastProgressAt = $total > 0 ? microtime(true) : 0;
        foreach (array_keys($clickIds) as $clickId) {
            $delayMs = random_int(self::REQUEST_DELAY_MS_MIN, self::REQUEST_DELAY_MS_MAX);
            usleep($delayMs * 1000);
            $creatureUrl = self::EGGCAVE_BASE . '/egg/' . $clickId;
            $response = Http::withHeaders($this->httpHeaders())->timeout(30)->get($creatureUrl);
            $slug = null;
            if ($response->successful()) {
                // Creature page has species in e.g. <h1>...<a href="https://eggcave.com/archives/trefulp">Trefulp</a></h1>
                if (preg_match('~href\s*=\s*[\'"](?:https?://[^\'"]*?)?/archives/([^\/\'"\s?#]+)[\'"]~i', $response->body(), $m)) {
                    $slug = trim($m[1]);
                }
                if (($slug === null || $slug === '') && $response->effectiveUri()) {
                    $finalUrl = $response->effectiveUri()->__toString();
                    if (preg_match('~/archives/([^/?#]+)(?:[?#]|$)~i', $finalUrl, $m)) {
                        $slug = trim($m[1]);
                    }
                }
            }
            if ($slug !== null && $slug !== '' && $slug !== 'archives') {
                $haveSlugs[$slug] = true;
            }
            $processed++;
            if ($onProgress !== null && ($processed % 100 === 0 || ($processed === $total && $total % 100 !== 0))) {
                $elapsed = (int) round(microtime(true) - $lastProgressAt);
                $lastProgressAt = microtime(true);
                $batchSize = ($processed % 100 === 0) ? 100 : ($processed % 100);
                $runtime = $elapsed >= 60
                    ? (int) floor($elapsed / 60) . 'm ' . ($elapsed % 60) . 's'
                    : $elapsed . 's';
                $onProgress("Processing creature links... {$processed} / {$total} (last {$batchSize} in {$runtime})");
            }
        }
        return $haveSlugs;
    }
}
