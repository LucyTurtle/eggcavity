<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class WishlistSyncItemsService
{
    private const EGGCAVE_BASE = 'https://eggcave.com';

    /** Same delay range as creature wishlist sync (ms) */
    private const REQUEST_DELAY_MS_MIN = 45;
    private const REQUEST_DELAY_MS_MAX = 1500;

    /** Seconds per request for run-time estimate (same as creature sync: ~1.2s/request from real runs) */
    private const ESTIMATE_SEC_PER_REQUEST = 1.2;

    /** Shop IDs from the collection page dropdown (match option values: 1, 2, 4, 5, 6, 7, 9, 10) */
    private const SHOP_IDS = [1, 2, 4, 5, 6, 7, 9, 10];

    /** Shop names from the collection page dropdown (option labels) */
    private const SHOP_NAMES = [
        1 => 'General Food Store',
        2 => 'Travel Agency',
        4 => 'Toy Shop',
        5 => 'Bakery',
        6 => 'Bean Sack',
        7 => 'Leila Library',
        9 => 'Trinket Travels',
        10 => 'Finley\'s Flavors',
    ];

    private function shopLabel(int $shopId): string
    {
        return self::SHOP_NAMES[$shopId] ?? "Shop {$shopId}";
    }

    private function httpHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer' => self::EGGCAVE_BASE . '/',
        ];
    }

    /**
     * Scrape the user's item collection for one egg (all shops, all pages), then add every
     * item from our catalog that they don't have to their item wishlist.
     *
     * @param  callable(string): void|null  $onProgress
     * @return array{cleared: int, added: int, have_count: int, to_add_count: int}
     */
    public function sync(User $user, int $eggId, bool $clear = false, ?callable $onProgress = null): array
    {
        $haveItemIds = $this->fetchCollectionItemIds($eggId, $onProgress);
        $allItems = Item::orderBy('name')->get(['id', 'name', 'slug']);
        $toAdd = $allItems->filter(fn ($item) => ! isset($haveItemIds[$item->id]));

        $cleared = 0;
        if ($clear) {
            $cleared = $user->itemWishlists()->count();
            $user->itemWishlists()->delete();
        }

        $added = 0;
        foreach ($toAdd as $item) {
            $user->itemWishlists()->updateOrCreate(
                ['item_id' => $item->id],
                ['amount' => 1, 'notes' => null]
            );
            $added++;
        }

        return [
            'cleared' => $cleared,
            'added' => $added,
            'have_count' => count($haveItemIds),
            'to_add_count' => $toAdd->count(),
        ];
    }

    /**
     * Fetch all collection pages (all shops, all pages), parse item image URLs, match to our Item IDs.
     * Phase 1: visit each shop's page 1 to get page counts, then show per-shop and total time estimates.
     * Phase 2: fetch remaining pages (2..last) for each shop.
     *
     * @param  callable(string): void|null  $onProgress
     * @return array<int, true> item_id => true
     */
    private function fetchCollectionItemIds(int $eggId, ?callable $onProgress = null): array
    {
        $collectionImageUrls = [];
        /** @var array<int, array<string, true>> unique normalized image URLs per shop (for progress output) */
        $uniquePerShop = array_fill_keys(self::SHOP_IDS, []);
        /** @var array<int, int|null> total items shown on first page of each shop (null if unparseable) */
        $totalShownPerShop = [];
        /** @var array<int, int|null> unique used total from "X / Y Unique Used" (Y) per shop */
        $uniqueTotalPerShop = [];

        // Phase 1: fetch page 1 of each shop to get page counts and collect items from page 1
        if ($onProgress) {
            $onProgress('Checking each shop for page counts...');
        }
        $pagesPerShop = [];
        foreach (self::SHOP_IDS as $shopId) {
            $url = self::EGGCAVE_BASE . '/egg/' . $eggId . '/collection?shop=' . $shopId . '&page=1';
            $this->delay();
            $response = Http::withHeaders($this->httpHeaders())->timeout(30)->get($url);
            if (! $response->successful()) {
                if ($onProgress) {
                    $onProgress('  ' . $this->shopLabel($shopId) . ': HTTP ' . $response->status());
                }
                $pagesPerShop[$shopId] = 0;
                $totalShownPerShop[$shopId] = null;
                continue;
            }
            $html = $response->body();
            $lastPage = $this->parseLastPageNumber($html);
            $totalShownPerShop[$shopId] = $this->parseTotalItemsFromCollectionPage($html);
            $uniqueTotalPerShop[$shopId] = $this->parseUniqueUsedTotalFromCollectionPage($html);
            $page1Urls = $this->parseImageUrlsFromCollectionHtml($html);
            $page1Count = count($page1Urls);
            if ($totalShownPerShop[$shopId] !== null && $page1Count > 0) {
                $lastPageFromTotal = (int) ceil($totalShownPerShop[$shopId] / $page1Count);
                if ($lastPageFromTotal > $lastPage) {
                    $lastPage = $lastPageFromTotal;
                }
            }
            $pagesPerShop[$shopId] = $lastPage;
            foreach ($page1Urls as $imageUrl) {
                $norm = $this->normalizeImageUrl($imageUrl);
                $collectionImageUrls[$norm] = true;
                $uniquePerShop[$shopId][$norm] = true;
            }
        }

        $totalPages = array_sum($pagesPerShop);
        if ($onProgress && $totalPages > 0) {
            $estTotalSec = (int) ceil($totalPages * self::ESTIMATE_SEC_PER_REQUEST);
            $estMin = (int) floor($estTotalSec / 60);
            $estSecRem = $estTotalSec % 60;
            $estStr = $estMin . ' min' . ($estSecRem > 0 ? ' ' . $estSecRem . ' sec' : '');
            $onProgress('Per shop:');
            foreach ($pagesPerShop as $shopId => $pages) {
                $shopSec = (int) ceil($pages * self::ESTIMATE_SEC_PER_REQUEST);
                $shopMin = (int) floor($shopSec / 60);
                $shopSecRem = $shopSec % 60;
                $shopEst = $shopMin . ' min' . ($shopSecRem > 0 ? ' ' . $shopSecRem . ' sec' : '');
                $onProgress('  ' . $this->shopLabel($shopId) . ": {$pages} page(s) (~{$shopEst})");
            }
            $onProgress("Estimated total run time: ~{$estStr} ({$totalPages} requests).");
            $onProgress('Fetching remaining pages...');
        }

        // Phase 2: fetch pages 2..last for each shop (page 1 already done)
        foreach (self::SHOP_IDS as $shopId) {
            $lastPage = $pagesPerShop[$shopId] ?? 0;
            $shopEstSec = $lastPage > 0 ? (int) ceil($lastPage * self::ESTIMATE_SEC_PER_REQUEST) : 0;
            $shopEstMin = (int) floor($shopEstSec / 60);
            $shopEstSecRem = $shopEstSec % 60;
            $shopEstStr = $shopEstMin . ' min' . ($shopEstSecRem > 0 ? ' ' . $shopEstSecRem . ' sec' : '');
            if ($onProgress && $lastPage > 0) {
                $onProgress('  ' . $this->shopLabel($shopId) . ": fetching (est. ~{$shopEstStr})...");
            }
            $shopStart = microtime(true);
            for ($page = 2; $page <= $lastPage; $page++) {
                $this->delay();
                $url = self::EGGCAVE_BASE . '/egg/' . $eggId . '/collection?shop=' . $shopId . '&page=' . $page;
                $response = Http::withHeaders($this->httpHeaders())->timeout(30)->get($url);
                if (! $response->successful()) {
                    continue;
                }
                $pageUrls = $this->parseImageUrlsFromCollectionHtml($response->body());
                foreach ($pageUrls as $imageUrl) {
                    $norm = $this->normalizeImageUrl($imageUrl);
                    $collectionImageUrls[$norm] = true;
                    $uniquePerShop[$shopId][$norm] = true;
                }
            }
            if ($onProgress && $lastPage > 0) {
                $elapsed = (int) round(microtime(true) - $shopStart);
                $elapsedMin = (int) floor($elapsed / 60);
                $elapsedSec = $elapsed % 60;
                $elapsedStr = $elapsedMin . ' min ' . $elapsedSec . ' sec';
                $uniqueCount = count($uniquePerShop[$shopId]);
                $uniqueTotal = $uniqueTotalPerShop[$shopId] ?? null;
                $countNote = $uniqueTotal !== null
                    ? " Unique items: {$uniqueCount}/{$uniqueTotal}."
                    : " Unique items: {$uniqueCount}.";
                $onProgress('  ' . $this->shopLabel($shopId) . ": done ({$lastPage} page(s)) in {$elapsedStr}.{$countNote}");
            }
        }

        if ($onProgress) {
            $onProgress('Collected ' . count($collectionImageUrls) . ' unique item image(s). Matching to catalog by image_url...');
        }

        return $this->itemIdsHavingImageUrls(array_keys($collectionImageUrls));
    }

    private function delay(): void
    {
        $ms = random_int(self::REQUEST_DELAY_MS_MIN, self::REQUEST_DELAY_MS_MAX);
        usleep($ms * 1000);
    }

    private function parseLastPageNumber(string $html): int
    {
        $lastPage = 1;
        // Match page=N in href (HTML may have &amp; e.g. ...?shop=1&amp;page=29)
        if (preg_match_all('/(?:&amp;|&|\?)page=(\d+)/', $html, $m)) {
            foreach ($m[1] as $p) {
                $p = (int) $p;
                if ($p > $lastPage) {
                    $lastPage = $p;
                }
            }
        }
        return $lastPage;
    }

    /**
     * Parse total item count from collection page HTML (e.g. "Showing 1 to 24 of 500" or "of 1,234").
     * Returns null if no total found.
     */
    private function parseTotalItemsFromCollectionPage(string $html): ?int
    {
        // "of 500", "of 1,234", "of 1234"
        if (preg_match('/\bof\s+([\d,]+)/i', $html, $m)) {
            return (int) str_replace(',', '', $m[1]);
        }
        // "Total: 500", "total 500"
        if (preg_match('/total[:\s]+([\d,]+)/i', $html, $m)) {
            return (int) str_replace(',', '', $m[1]);
        }
        return null;
    }

    /**
     * Parse the total (Y) from "X / Y Unique Used" in the collection page stats box.
     * Returns null if not found. Tolerates HTML (e.g. </strong>) between digits and "Unique Used".
     */
    private function parseUniqueUsedTotalFromCollectionPage(string $html): ?int
    {
        if (preg_match('/(\d[\d,]*)\s*\/\s*(\d[\d,]*).*?Unique\s+Used/is', $html, $m)) {
            return (int) str_replace(',', '', $m[2]);
        }
        return null;
    }

    /**
     * Parse collection page HTML for item image URLs (img src from static.eggcave.com/items/).
     *
     * @return array<int, string> list of image URLs
     */
    private function parseImageUrlsFromCollectionHtml(string $html): array
    {
        $urls = [];
        try {
            $crawler = new Crawler($html);
            $columns = $crawler->filter('.box .columns.is-multiline .column');
            foreach ($columns as $node) {
                $column = new Crawler($node);
                $img = $column->filter('img[src*="static.eggcave.com/items/"], img[src*="eggcave.com/items/"]')->first();
                if ($img->count()) {
                    $src = trim($img->attr('src') ?? '');
                    if ($src !== '') {
                        $urls[] = $src;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore parse errors
        }
        return $urls;
    }

    /**
     * Normalize image URL for comparison (lowercase, no query string, consistent path).
     */
    private function normalizeImageUrl(string $url): string
    {
        $url = trim($url);
        if (str_contains($url, '?')) {
            $url = substr($url, 0, strpos($url, '?'));
        }
        $url = strtolower($url);
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }
        return rtrim($url, '/');
    }

    /**
     * Return item IDs that have image_url (in DB) matching one of the collected normalized URLs from Eggcave.
     *
     * @param  array<int, string>  $normalizedCollectionUrls  normalized image URLs from collection page
     * @return array<int, true> item_id => true
     */
    private function itemIdsHavingImageUrls(array $normalizedCollectionUrls): array
    {
        $collectionSet = array_fill_keys($normalizedCollectionUrls, true);
        $itemIds = [];
        $items = Item::whereNotNull('image_url')->where('image_url', '!=', '')->get(['id', 'image_url']);
        foreach ($items as $item) {
            $norm = $this->normalizeImageUrl($item->image_url);
            if (isset($collectionSet[$norm])) {
                $itemIds[$item->id] = true;
            }
        }
        return $itemIds;
    }
}
