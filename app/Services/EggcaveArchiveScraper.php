<?php

namespace App\Services;

use App\Models\ArchiveItem;
use App\Models\ArchiveItemImage;
use App\Models\ArchiveStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class EggcaveArchiveScraper
{
    protected string $baseUrl = 'https://eggcave.com';

    protected string $archivesUrl = 'https://eggcave.com/archives';

    protected float $delayBetweenRequests = 0.1;

    public function setDelay(float $seconds): self
    {
        $this->delayBetweenRequests = max(0, $seconds);
        return $this;
    }

    /** @var callable(string $message): void|null */
    protected $logger = null;

    public function setLogger(?callable $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    protected function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }

    /**
     * Resolve a possibly relative URL to a full eggcave URL.
     */
    protected function absoluteUrl(string $url, string $contextUrl = ''): string
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

    /**
     * Check if URL is an image we want to store (eggcave or static.eggcave).
     */
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

    /**
     * Fetch HTML with polite headers and optional delay.
     */
    protected function fetch(string $url): string
    {
        if ($this->delayBetweenRequests > 0) {
            usleep((int) ($this->delayBetweenRequests * 1_000_000));
        }

        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP ' . $response->status() . ' for ' . $url);
        }

        return $response->body();
    }

    /**
     * Extract all unique archive slugs from a listing page HTML.
     */
    protected function extractSlugsFromListing(string $html): array
    {
        $crawler = new Crawler($html);
        $body = $crawler->filter('body');
        if ($body->count() === 0) {
            return [];
        }

        $slugs = [];
        $links = $body->filter('a[href*="/archives/"]');

        foreach ($links as $node) {
            $link = new Crawler($node);
            $href = trim($link->attr('href') ?? '');
            if ($href === '' || $href === '/archives' || rtrim($href, '/') === $this->archivesUrl) {
                continue;
            }

            $path = parse_url($this->absoluteUrl($href), PHP_URL_PATH);
            if (!$path || !preg_match('#^/archives/([^/]+)/?$#', $path, $m)) {
                continue;
            }
            $slug = trim($m[1], '/');
            if ($slug !== '' && !in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * Find next listing page URL (pagination). Returns null if no next page.
     */
    protected function findNextListingPage(Crawler $body, string $currentPageUrl): ?string
    {
        $parsed = parse_url($currentPageUrl);
        $path = $parsed['path'] ?? '/archives';
        $query = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        $currentPage = (int) ($query['page'] ?? 1);
        $nextPage = $currentPage + 1;

        // 1. rel="next"
        $nextLink = $body->filter('a[rel="next"]')->first();
        if ($nextLink->count() > 0) {
            $href = trim($nextLink->attr('href') ?? '');
            if ($href !== '') {
                return $this->absoluteUrl($href);
            }
        }

        // 2. Link that points to the next page number (page=N where N = current+1)
        $nextPageLink = $body->filter('a[href*="page=' . $nextPage . '"]')->first();
        if ($nextPageLink->count() > 0) {
            $href = trim($nextPageLink->attr('href') ?? '');
            if ($href !== '') {
                return $this->absoluteUrl($href);
            }
        }

        // 3. Text "next", "»", ">" etc. that points to next page
        $links = $body->filter('a');
        foreach ($links as $node) {
            $link = new Crawler($node);
            $href = trim($link->attr('href') ?? '');
            $text = strtolower(trim($link->text()));

            if ($href === '') {
                continue;
            }

            $full = $this->absoluteUrl($href);
            if (!str_contains($full, $this->baseUrl . '/archives') || $full === $currentPageUrl) {
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

    /**
     * Scrape a single archive detail page: title, intro, stages (image + requirement), stats, about eggs/creature, author, tags.
     */
    protected function scrapeDetailPage(string $slug): array
    {
        $url = rtrim($this->baseUrl, '/') . '/archives/' . $slug;
        $html = $this->fetch($url);
        $crawler = new Crawler($html);
        $body = $crawler->filter('body');
        $defaultTitle = Str::title(str_replace(['-', '_'], ' ', $slug));
        $empty = [
            'title' => $defaultTitle,
            'description' => null,
            'image_urls' => [],
            'stages' => [],
            'availability' => null,
            'dates' => null,
            'weight' => null,
            'length' => null,
            'obtained_from' => null,
            'gender_profile' => null,
            'habitat' => null,
            'about_eggs' => null,
            'about_creature' => null,
            'entry_written_by' => null,
            'design_concept_user' => null,
            'cdwc_entry_by' => null,
            'tags' => [],
        ];
        if ($body->count() === 0) {
            return $empty;
        }

        // Title from h1 (e.g. "The Archives: Aal" -> "Aal" or keep full)
        $title = $defaultTitle;
        $h1 = $body->filter('h1')->first();
        if ($h1->count() > 0) {
            $raw = trim($h1->text());
            if (preg_match('/Archives:\s*(.+)$/i', $raw, $m)) {
                $title = trim($m[1]);
            } elseif ($raw !== '') {
                $title = $raw;
            }
        }

        // Skip generic intro description (we don't want "One file in the Archives reveals...")
        $description = null;

        // Evolution stages: .box with .columns.has-text-centered, each .column with img + optional requirement text
        $stages = [];
        $evolutionBox = $body->filter('.box .columns.has-text-centered')->first();
        if ($evolutionBox->count() > 0) {
            $columns = $evolutionBox->filter('.column');
            $stageNum = 0;
            foreach ($columns as $colNode) {
                $col = new Crawler($colNode);
                $img = $col->filter('img')->first();
                if ($img->count() === 0) {
                    continue;
                }
                $src = $img->attr('src') ?? $img->attr('data-src');
                if (!$src || !$this->isImageUrl($src)) {
                    continue;
                }
                $stageNum++;
                $imageUrl = $this->absoluteUrl($src);
                $requirement = trim($col->text());
                $requirement = preg_replace('/\s+/', ' ', $requirement);
                if (strlen($requirement) > 200) {
                    $requirement = null;
                }
                $stages[] = [
                    'stage_number' => $stageNum,
                    'image_url' => $imageUrl,
                    'requirement' => $requirement ?: null,
                    'sort_order' => $stageNum - 1,
                ];
            }
        }

        // Creature stats from .box.content .columns (h3 + p pairs)
        $stats = [];
        $statsBox = $body->filter('.box.content .columns');
        foreach ($statsBox as $row) {
            $rowCrawler = new Crawler($row);
            $cols = $rowCrawler->filter('.column');
            foreach ($cols as $colNode) {
                $col = new Crawler($colNode);
                $h3 = $col->filter('h3')->first();
                $p = $col->filter('p')->first();
                if ($h3->count() > 0 && $p->count() > 0) {
                    $key = trim($h3->text());
                    $key = preg_replace('/\s*\(.*\)\s*$/', '', $key);
                    $val = trim($p->text());
                    if ($key !== '' && $val !== '') {
                        $stats[$key] = $val;
                    }
                }
            }
        }

        $availability = $stats['Availability'] ?? null;
        $dates = $stats['Dates'] ?? null;
        $weight = $stats['Weight'] ?? null;
        $length = $stats['Length'] ?? null;
        $obtained_from = $stats['Obtained From'] ?? null;
        $gender_profile = $stats['Gender Profile'] ?? null;
        $habitat = $stats['Habitat'] ?? null;
        // Population rank is not scraped or displayed — it changes too often.

        // About Eggs / About the X Creature: h3 + following p(s)
        $aboutEggs = null;
        $aboutCreature = null;
        $contentSections = $body->filter('#eggcave .content, .content');
        foreach ($contentSections as $sec) {
            $secCrawler = new Crawler($sec);
            $nodes = $sec->childNodes;
            $currentSection = null;
            $paragraphs = [];
            foreach ($nodes as $node) {
                if ($node->nodeType !== 1) {
                    continue;
                }
                $el = new Crawler($node);
                $tag = strtolower($node->nodeName ?? '');
                if ($tag === 'h3') {
                    if ($currentSection !== null && !empty($paragraphs)) {
                        $text = implode("\n\n", $paragraphs);
                        // Remove "Entry Written By" if it got captured
                        $text = preg_replace('/\s*Entry\s+Written\s+By\s*:.*$/is', '', $text);
                        $text = trim($text);
                        if ($currentSection === 'eggs' && $text !== '') {
                            $aboutEggs = $text;
                        } elseif ($currentSection === 'creature' && $text !== '') {
                            $aboutCreature = $text;
                        }
                    }
                    $paragraphs = [];
                    $h3Text = trim($el->text());
                    if (stripos($h3Text, 'About') !== false && stripos($h3Text, 'Egg') !== false) {
                        $currentSection = 'eggs';
                    } elseif (stripos($h3Text, 'About') !== false && stripos($h3Text, 'Creature') !== false) {
                        $currentSection = 'creature';
                    } else {
                        $currentSection = null;
                    }
                } elseif ($tag === 'p' && $currentSection !== null) {
                    $text = trim($el->text());
                    // Stop if we hit "Entry Written By"
                    if (stripos($text, 'Entry Written By') !== false) {
                        break;
                    }
                    $paragraphs[] = $text;
                } elseif ($tag === 'div' && $currentSection !== null) {
                    $text = trim($el->text());
                    // Stop if this div contains "Entry Written By"
                    if (stripos($text, 'Entry Written By') !== false) {
                        break;
                    }
                    $paragraphs[] = $text;
                }
            }
            if ($currentSection !== null && !empty($paragraphs)) {
                $text = implode("\n\n", $paragraphs);
                // Remove "Entry Written By" if it got captured
                $text = preg_replace('/\s*Entry\s+Written\s+By\s*:.*$/is', '', $text);
                $text = trim($text);
                if ($currentSection === 'eggs' && $text !== '') {
                    $aboutEggs = $text;
                } elseif ($currentSection === 'creature' && $text !== '') {
                    $aboutCreature = $text;
                }
            }
            if ($aboutEggs !== null && $aboutCreature !== null) {
                break;
            }
        }

        // Entry Written By, Design Concept, and CDWC Winning Entry By: .box - extract separately
        $entryWrittenBy = null;
        $designConceptUser = null;
        $cdwcEntryBy = null;
        $boxes = $body->filter('.box');
        foreach ($boxes as $boxNode) {
            $box = new Crawler($boxNode);
            $boxHtml = $box->html();
            $boxText = trim($box->text());
            // Normalize whitespace for text-based fallback
            $boxTextNorm = preg_replace('/\s+/', ' ', $boxText);

            // CDWC Winning Entry By: e.g. <strong>CDWC Winning Entry By:</strong> <a href="/@vespira">vespira</a>
            if (stripos($boxText, 'CDWC') !== false && (stripos($boxText, 'Winning Entry') !== false || stripos($boxText, 'Entry By') !== false)) {
                if (preg_match('/CDWC\s+Winning\s+Entry\s+By\s*:?\s*<\/strong>\s*<a[^>]*>([^<]+)<\/a>/is', $boxHtml, $m)) {
                    $cdwcEntryBy = trim(strip_tags($m[1]));
                } elseif (preg_match('/CDWC\s+Winning\s+Entry\s+By\s*:?\s*([^\s<]+)/is', $boxHtml, $m)) {
                    $cdwcEntryBy = trim(strip_tags($m[1]));
                } elseif (preg_match('/CDWC\s+Winning\s+Entry\s+By\s*:?\s*(\S+)/i', $boxTextNorm, $m)) {
                    $cdwcEntryBy = trim($m[1]);
                }
            }

            // Check if this box contains "Entry Written By" or "Design Concept"
            if (stripos($boxText, 'Entry Written By') !== false || stripos($boxText, 'Design Concept') !== false) {
                // Extract from HTML: skip </strong> after the label so we capture only the name
                // e.g. <strong>Entry Written By:</strong> Meteoroid<br><strong>Design Concept:
                if (preg_match('/Entry\s+Written\s+By\s*:?\s*<\/strong>\s*([^<]+?)\s*<br/is', $boxHtml, $m)) {
                    $entryWrittenBy = trim(strip_tags($m[1]));
                } elseif (preg_match('/Entry\s+Written\s+By\s*:?\s*([^\n\r<]+?)(?:\s*<br|\s*<strong|\s*Design\s+Concept|\s+Tags|$)/is', $boxHtml, $m)) {
                    $entryWrittenBy = trim(preg_replace('/\s*Design\s+Concept.*$/i', '', trim(strip_tags($m[1]))));
                    $entryWrittenBy = preg_replace('/\s*Tags.*$/i', '', $entryWrittenBy);
                    $entryWrittenBy = trim($entryWrittenBy);
                    $entryWrittenBy = ltrim($entryWrittenBy, '>'); // in case we captured "> Name"
                }
                if (preg_match('/Design\s+Concept\s*:?\s*<\/strong>\s*([^<]+?)(?:\s*<br|$)/is', $boxHtml, $m)) {
                    $designConceptUser = trim(strip_tags($m[1]));
                } elseif (preg_match('/Design\s+Concept\s*:?\s*([^\n\r<]+?)(?:\s*<br|\s*<strong|\s*Tags|$)/is', $boxHtml, $m)) {
                    $designConceptUser = trim(preg_replace('/\s*Tags.*$/i', '', trim(strip_tags($m[1]))));
                    $designConceptUser = trim($designConceptUser);
                }

                // Fallback: extract from plain text (e.g. "Entry Written By: Meteoroid Design Concept: miu_angel")
                if (($entryWrittenBy === null || $entryWrittenBy === '') && preg_match('/Entry\s+Written\s+By\s*:?\s*([^D]+?)\s+Design\s+Concept/is', $boxTextNorm, $m)) {
                    $entryWrittenBy = trim($m[1]);
                }
                if (($designConceptUser === null || $designConceptUser === '') && preg_match('/Design\s+Concept\s*:?\s*(\S+)/i', $boxTextNorm, $m)) {
                    $designConceptUser = trim($m[1]);
                }

                if (($entryWrittenBy !== null && $entryWrittenBy !== '') || ($designConceptUser !== null && $designConceptUser !== '')) {
                    break;
                }
            }
        }

        // Tags: a[href*="tag="]
        $tags = [];
        $tagLinks = $body->filter('a[href*="tag="]');
        foreach ($tagLinks as $a) {
            $link = new Crawler($a);
            $t = trim($link->text());
            if ($t !== '' && !in_array($t, $tags, true)) {
                $tags[] = $t;
            }
        }

        // image_urls: from stages first, then any other eggcave images
        $imageUrls = array_column($stages, 'image_url');
        $seen = array_fill_keys($imageUrls, true);
        $imgs = $body->filter('img');
        foreach ($imgs as $imgNode) {
            $img = new Crawler($imgNode);
            $src = $img->attr('src') ?? $img->attr('data-src');
            if ($src && $this->isImageUrl($src)) {
                $abs = $this->absoluteUrl($src);
                if (!isset($seen[$abs])) {
                    $seen[$abs] = true;
                    $imageUrls[] = $abs;
                }
            }
        }

        return [
            'title' => $title,
            'description' => $description,
            'image_urls' => $imageUrls,
            'stages' => $stages,
            'availability' => $availability,
            'dates' => $dates,
            'weight' => $weight,
            'length' => $length,
            'obtained_from' => $obtained_from,
            'gender_profile' => $gender_profile,
            'habitat' => $habitat,
            'about_eggs' => $aboutEggs,
            'about_creature' => $aboutCreature,
            'entry_written_by' => $entryWrittenBy,
            'design_concept_user' => $designConceptUser,
            'cdwc_entry_by' => $cdwcEntryBy,
            'tags' => $tags,
        ];
    }

    /**
     * Collect all slugs from the main listing, including pagination.
     */
    public function collectAllSlugs(): array
    {
        $allSlugs = [];
        $visitedUrls = [];
        $url = $this->archivesUrl;

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
            $before = count($allSlugs);
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
     * Scrape listing (with pagination) and detail pages; store in DB.
     * When $newOnly is true (default): only fetches detail pages for slugs not already in the DB.
     * We never request existing links unless doing a full refresh (e.g. manual CLI with --full).
     *
     * @param int|null $limit Max number of subpages to scrape (null = all)
     * @param bool $newOnly If true, only scrape slugs that don't exist yet; if false, re-scrape all (manual refresh only)
     */
    public function scrape(?int $limit = null, bool $newOnly = true): array
    {
        $this->log('Collecting all archive links (including pagination)...');
        $slugs = $this->collectAllSlugs();
        if ($limit !== null && $limit > 0) {
            $slugs = array_slice($slugs, 0, $limit);
            $this->log('Limiting to first ' . $limit . ' items.');
        }

        if ($newOnly) {
            $existingSlugs = ArchiveItem::whereIn('slug', $slugs)->pluck('slug')->all();
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
        $sortOrder = (int) (ArchiveItem::max('sort_order') ?? 0);

        foreach ($slugs as $slug) {
            $sortOrder++;
            $sourceUrl = rtrim($this->baseUrl, '/') . '/archives/' . $slug;

            try {
                $this->log('Scraping: ' . $slug);
                $data = $this->scrapeDetailPage($slug);
            } catch (\Throwable $e) {
                $this->log('  Error: ' . $e->getMessage());
                $data = [
                    'title' => Str::title(str_replace(['-', '_'], ' ', $slug)),
                    'description' => null,
                    'image_urls' => [],
                    'stages' => [],
                    'availability' => null,
                    'dates' => null,
                    'weight' => null,
                    'length' => null,
                    'obtained_from' => null,
                    'gender_profile' => null,
                    'habitat' => null,
                    'about_eggs' => null,
                    'about_creature' => null,
                    'entry_written_by' => null,
                    'design_concept_user' => null,
                    'cdwc_entry_by' => null,
                    'tags' => [],
                ];
            }

            $firstImage = $data['image_urls'][0] ?? null;

            $item = ArchiveItem::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'image_url' => $firstImage,
                    'source_url' => $sourceUrl,
                    'published_at' => null,
                    'sort_order' => $sortOrder,
                    'meta' => [],
                    'availability' => $data['availability'] ?? null,
                    'dates' => $data['dates'] ?? null,
                    'weight' => $data['weight'] ?? null,
                    'length' => $data['length'] ?? null,
                    'obtained_from' => $data['obtained_from'] ?? null,
                    'gender_profile' => $data['gender_profile'] ?? null,
                    'habitat' => $data['habitat'] ?? null,
                    'about_eggs' => $data['about_eggs'] ?? null,
                    'about_creature' => $data['about_creature'] ?? null,
                    'entry_written_by' => $data['entry_written_by'] ?? null,
                    'design_concept_user' => $data['design_concept_user'] ?? null,
                    'cdwc_entry_by' => $data['cdwc_entry_by'] ?? null,
                    'tags' => $data['tags'] ?? [],
                ]
            );

            if ($item->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            // Sync stages (evolution images + requirements) - use upsert for better performance
            $stageUrls = array_column($data['stages'] ?? [], 'image_url');
            $stagesToUpsert = [];
            foreach ($data['stages'] ?? [] as $stage) {
                $stagesToUpsert[] = [
                    'archive_item_id' => $item->id,
                    'stage_number' => $stage['stage_number'],
                    'image_url' => $stage['image_url'],
                    'requirement' => $stage['requirement'] ?? null,
                    'sort_order' => $stage['sort_order'] ?? 0,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
            if (!empty($stagesToUpsert)) {
                // Delete old stages and insert new ones in batch
                $item->stages()->delete();
                DB::table('archive_stages')->insert($stagesToUpsert);
            } else {
                $item->stages()->delete();
            }

            // Sync extra images to archive_item_images (non-stage images) - use batch insert
            $imagesToUpsert = [];
            $order = 0;
            foreach ($data['image_urls'] ?? [] as $imgUrl) {
                if (in_array($imgUrl, $stageUrls, true)) {
                    continue;
                }
                $imagesToUpsert[] = [
                    'archive_item_id' => $item->id,
                    'url' => $imgUrl,
                    'caption' => null,
                    'sort_order' => $order++,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
            if (!empty($imagesToUpsert)) {
                // Delete old images and insert new ones in batch
                $item->images()->delete();
                DB::table('archive_item_images')->insert($imagesToUpsert);
            } else {
                $item->images()->delete();
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($slugs),
        ];
    }
}

