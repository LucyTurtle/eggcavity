<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class EggcaveItemsScraper
{
    protected string $baseUrl = 'https://eggcave.com';

    protected string $itemsUrl = 'https://eggcave.com/items';

    protected float $delayBetweenRequests = 0.1;

    /** @var callable(string $message): void|null */
    protected $logger = null;

    public function setLogger(?callable $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function setDelay(float $seconds): self
    {
        $this->delayBetweenRequests = max(0, $seconds);
        return $this;
    }

    protected function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }

    protected function absoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, '#')) {
            return $url;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        $base = $this->baseUrl;
        if (!str_starts_with($url, '/')) {
            $url = '/' . ltrim($url, '/');
        }
        return rtrim($base, '/') . $url;
    }

    protected function isImageUrl(string $url): bool
    {
        $url = strtolower($url);
        if (!str_contains($url, 'eggcave.com') && !str_contains($url, 'static.eggcave.com')) {
            return false;
        }
        $ext = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        foreach ($ext as $e) {
            if (str_contains($url, '.' . $e) || preg_match('#/' . preg_quote($e) . '(\?|$)#', $url)) {
                return true;
            }
        }
        return true;
    }

    protected function fetch(string $url): string
    {
        if ($this->delayBetweenRequests > 0) {
            usleep((int) ($this->delayBetweenRequests * 1_000_000));
        }

        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => $this->baseUrl . '/',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => str_starts_with($url, $this->baseUrl) ? 'same-origin' : 'cross-site',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP ' . $response->status() . ' for ' . $url);
        }

        return $response->body();
    }

    protected function extractSlugsFromListing(string $html): array
    {
        $crawler = new Crawler($html);
        $body = $crawler->filter('body');
        if ($body->count() === 0) {
            return [];
        }

        $slugs = [];
        $links = $body->filter('a[href*="/items/"]');

        foreach ($links as $node) {
            $link = new Crawler($node);
            $href = trim($link->attr('href') ?? '');
            if ($href === '' || $href === '/items' || rtrim($href, '/') === $this->itemsUrl) {
                continue;
            }

            $path = parse_url($this->absoluteUrl($href), PHP_URL_PATH);
            if (!$path || !preg_match('#^/items/([^/]+)/?$#', $path, $m)) {
                continue;
            }
            $slug = trim($m[1], '/');
            if ($slug !== '' && !in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    protected function findNextListingPage(Crawler $body, string $currentPageUrl): ?string
    {
        $parsed = parse_url($currentPageUrl);
        $path = $parsed['path'] ?? '/items';
        $query = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        $currentPage = (int) ($query['page'] ?? 1);
        $nextPage = $currentPage + 1;

        $nextLink = $body->filter('a[rel="next"]')->first();
        if ($nextLink->count() > 0) {
            $href = trim($nextLink->attr('href') ?? '');
            if ($href !== '') {
                return $this->absoluteUrl($href);
            }
        }

        $nextPageLink = $body->filter('a[href*="page=' . $nextPage . '"]')->first();
        if ($nextPageLink->count() > 0) {
            $href = trim($nextPageLink->attr('href') ?? '');
            if ($href !== '') {
                return $this->absoluteUrl($href);
            }
        }

        $links = $body->filter('a');
        foreach ($links as $node) {
            $link = new Crawler($node);
            $href = trim($link->attr('href') ?? '');
            $text = strtolower(trim($link->text()));

            if ($href === '') {
                continue;
            }

            $full = $this->absoluteUrl($href);
            if (!str_contains($full, $this->baseUrl . '/items') || $full === $currentPageUrl) {
                continue;
            }

            $isNextWord = in_array($text, ['next', '»', '>', '›'], true) || str_contains($text, 'next');
            if (!$isNextWord) {
                continue;
            }

            $linkPage = 1;
            if (preg_match('/[?&]page=(\d+)/', $href, $m)) {
                $linkPage = (int) $m[1];
            }
            if ($linkPage === $nextPage) {
                return $full;
            }
        }

        return null;
    }

    protected function parseDate(string $text): ?\DateTime
    {
        // Try to parse "around January 7, 2026" or similar
        if (preg_match('/(?:around\s+)?(\w+)\s+(\d+),?\s+(\d{4})/i', $text, $m)) {
            $month = $m[1];
            $day = (int) $m[2];
            $year = (int) $m[3];
            $monthNum = date_parse($month)['month'] ?? null;
            if ($monthNum) {
                try {
                    return new \DateTime("{$year}-{$monthNum}-{$day}");
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }
        return null;
    }

    protected function scrapeDetailPage(string $slug): array
    {
        $url = rtrim($this->baseUrl, '/') . '/items/' . $slug;
        $html = $this->fetch($url);
        $crawler = new Crawler($html);
        $body = $crawler->filter('body');
        $defaultName = Str::title(str_replace(['-', '_'], ' ', $slug));

        $empty = [
            'name' => $defaultName,
            'description' => null,
            'image_url' => null,
            'rarity' => null,
            'use' => null,
            'associated_shop' => null,
            'restock_price' => null,
            'is_retired' => false,
            'is_cavecash' => false,
            'first_appeared' => null,
        ];

        if ($body->count() === 0) {
            return $empty;
        }

        // Name from h1
        $name = $defaultName;
        $h1 = $body->filter('h1')->first();
        if ($h1->count() > 0) {
            $name = trim($h1->text());
        }

        // First appeared date
        $firstAppeared = null;
        $firstP = $body->filter('.content p')->first();
        if ($firstP->count() > 0) {
            $text = trim($firstP->text());
            if (stripos($text, 'first on Egg Cave') !== false) {
                $firstAppeared = $this->parseDate($text);
            }
        }

        // Image: img.black-border in .text-center.block-margin
        $imageUrl = null;
        $img = $body->filter('.text-center.block-margin img.black-border, img.black-border')->first();
        if ($img->count() > 0) {
            $src = $img->attr('src') ?? $img->attr('data-src');
            if ($src && $this->isImageUrl($src)) {
                $imageUrl = $this->absoluteUrl($src);
            }
        }

        // Description: span.is-size-4 (but exclude "You Have" and other metadata)
        $description = null;
        $descSpan = $body->filter('span.is-size-4')->first();
        if ($descSpan->count() > 0) {
            $text = trim($descSpan->text());
            // Stop at "You Have:" or other metadata markers
            $text = preg_replace('/\s*You\s+Have:.*$/i', '', $text);
            $text = preg_replace('/\s*Use:.*$/i', '', $text);
            $text = trim($text);
            if ($text !== '') {
                $description = $text;
            }
        }

        // Rarity, Use, Associated Shop, Restock Price, Retired status, CaveCash status from the box
        $rarity = null;
        $use = null;
        $associatedShop = null;
        $restockPrice = null;
        $isRetired = false;
        $isCavecash = false;

        // CaveCash: page has <span class="has-text-success">... CaveCash Item</span> or "CaveCash Item" text
        $fullHtml = $body->html();
        if (stripos($fullHtml, 'CaveCash Item') !== false || stripos($fullHtml, 'icons/money.png') !== false) {
            $isCavecash = true;
        }

        $boxes = $body->filter('.box');
        foreach ($boxes as $boxNode) {
            $box = new Crawler($boxNode);
            $boxHtml = $box->html();
            $text = trim($box->text());

            // Also check inside each box (in case structure varies)
            if (stripos($boxHtml, 'CaveCash Item') !== false || stripos($text, 'CaveCash Item') !== false) {
                $isCavecash = true;
            }

            // Rarity: look for "r83 (uncommon)" pattern in green text or gray text (for unobtainable)
            $rarityEl = $box->filter('strong[style*="color: green"], strong[style*="color:green"], strong[style*="color: gray"], strong[style*="color:gray"]')->first();
            if ($rarityEl->count() > 0) {
                $rarity = trim($rarityEl->text());
            }

            // Use: "Use: Food Item" -> normalize to "item" or "travel"
            // Look for <strong>Use:</strong> followed by text, stop at <br> or next <strong>
            if (preg_match('/<strong>Use:<\/strong>\s*([^<\n\r]+?)(?:\s*<br|\s*<strong|$)/is', $boxHtml, $m)) {
                $useText = trim(strip_tags($m[1]));
                // Normalize: if contains "Travel" -> "travel", else -> "item"
                if (stripos($useText, 'Travel') !== false) {
                    $use = 'travel';
                } else {
                    $use = 'item';
                }
            }

            // Associated Shop: "Associated Shop: ..." with link, stop before Restock Price
            // Look for <strong>Associated Shop:</strong> followed by <a> tag
            if (preg_match('/<strong>Associated\s+Shop:<\/strong>\s*<a[^>]*>([^<]+)<\/a>/is', $boxHtml, $m)) {
                $associatedShop = trim($m[1]);
            } elseif (preg_match('/<strong>Associated\s+Shop:<\/strong>\s*([^<\n\r]+?)(?:\s*<br|\s*<strong|$)/is', $boxHtml, $m)) {
                $associatedShop = trim(strip_tags($m[1]));
                // Remove any trailing price or status text
                $associatedShop = preg_replace('/\s+\d+[,\d]*\s*EC.*$/i', '', $associatedShop);
                $associatedShop = preg_replace('/\s+(Not\s+)?Retired.*$/i', '', $associatedShop);
                $associatedShop = trim($associatedShop);
            }

            // Restock Price: "Restock Price: 1,502 EC" - stop before status or next <strong>
            // Look for <strong>Restock Price:</strong> followed by text, stop at <br> or <span> (status)
            if (preg_match('/<strong>Restock\s+Price:<\/strong>\s*([^<\n\r]+?)(?:\s*<br|\s*<span|\s*<strong|$)/is', $boxHtml, $m)) {
                $restockPrice = trim(strip_tags($m[1]));
                // Remove any status text that got captured
                $restockPrice = preg_replace('/\s+(Not\s+)?Retired.*$/i', '', $restockPrice);
                $restockPrice = trim($restockPrice);
            }

            // Retired status: look for checkmark with "Not retired" or "Retired"
            if (stripos($text, 'Not retired') !== false) {
                $isRetired = false;
            } elseif (stripos($text, 'Retired') !== false && stripos($text, 'Not retired') === false) {
                $isRetired = true;
            }
        }

        return [
            'name' => $name,
            'description' => $description,
            'image_url' => $imageUrl,
            'rarity' => $rarity,
            'use' => $use,
            'associated_shop' => $associatedShop,
            'restock_price' => $restockPrice,
            'is_retired' => $isRetired,
            'is_cavecash' => $isCavecash,
            'first_appeared' => $firstAppeared,
        ];
    }

    public function collectAllSlugs(): array
    {
        $allSlugs = [];
        $visitedUrls = [];
        $url = $this->itemsUrl;

        while (true) {
            if (isset($visitedUrls[$url])) {
                break;
            }
            $visitedUrls[$url] = true;
            $this->log('Fetching listing: ' . $url);

            $html = $this->fetch($url);
            $crawler = new Crawler($html);
            $body = $crawler->filter('body');
            if ($body->count() === 0) {
                break;
            }

            $slugs = $this->extractSlugsFromListing($html);
            foreach ($slugs as $s) {
                if (!in_array($s, $allSlugs, true)) {
                    $allSlugs[] = $s;
                }
            }
            $this->log('Found ' . count($slugs) . ' links on page, total unique: ' . count($allSlugs));

            $nextUrl = $this->findNextListingPage($body, $url);
            if ($nextUrl === null || $nextUrl === $url) {
                break;
            }
            $url = $nextUrl;
        }

        return $allSlugs;
    }

    /**
     * When $newOnly is true (default): only fetches detail pages for slugs not already in the DB.
     * We never request existing links unless doing a full refresh (e.g. manual CLI with --full).
     *
     * @param int|null $limit Max number of items to scrape (null = all new)
     * @param bool $newOnly If true, only scrape slugs that don't exist yet; if false, re-scrape all (manual refresh only)
     */
    public function scrape(?int $limit = null, bool $newOnly = true): array
    {
        $this->log('Collecting all item links (including pagination)...');
        $slugs = $this->collectAllSlugs();
        if ($limit !== null && $limit > 0) {
            $slugs = array_slice($slugs, 0, $limit);
            $this->log('Limiting to first ' . $limit . ' items.');
        }

        if ($newOnly) {
            $existingSlugs = Item::whereIn('slug', $slugs)->pluck('slug')->all();
            $slugs = array_values(array_diff($slugs, $existingSlugs));
            $this->log('Only new: ' . count($slugs) . ' to scrape (rest already in DB).');
        } else {
            $this->log('Total items to scrape: ' . count($slugs));
        }

        if (empty($slugs)) {
            $this->log('Nothing new to scrape.');
            return ['created' => 0, 'updated' => 0, 'total' => 0];
        }

        $created = 0;
        $updated = 0;
        $sortOrder = (int) (Item::max('sort_order') ?? 0);

        foreach ($slugs as $slug) {
            $sortOrder++;
            $sourceUrl = rtrim($this->baseUrl, '/') . '/items/' . $slug;

            try {
                $this->log('Scraping: ' . $slug);
                $data = $this->scrapeDetailPage($slug);
            } catch (\Throwable $e) {
                $this->log('  Error: ' . $e->getMessage());
                $data = [
                    'name' => Str::title(str_replace(['-', '_'], ' ', $slug)),
                    'description' => null,
                    'image_url' => null,
                    'rarity' => null,
                    'use' => null,
                    'associated_shop' => null,
                    'restock_price' => null,
                    'is_retired' => false,
                    'is_cavecash' => false,
                    'first_appeared' => null,
                ];
            }

            $item = Item::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'image_url' => $data['image_url'],
                    'source_url' => $sourceUrl,
                    'rarity' => $data['rarity'],
                    'use' => $data['use'],
                    'associated_shop' => $data['associated_shop'],
                    'restock_price' => $data['restock_price'],
                    'is_retired' => $data['is_retired'],
                    'is_cavecash' => $data['is_cavecash'],
                    'first_appeared' => $data['first_appeared'],
                    'sort_order' => $sortOrder,
                ]
            );

            if ($item->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($slugs),
        ];
    }
}
