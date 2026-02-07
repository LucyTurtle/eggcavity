<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageMatchService
{
    private const CACHE_KEY_PREFIX = 'image_match_hsv:';
    private const CACHE_TTL_DAYS = 30;

    /**
     * Clear persisted HSV cache (e.g. to force re-fetch after images change).
     * With the default file driver this only affects stores that support flush.
     * Otherwise cache entries expire after 30 days.
     */
    public static function clearCache(): void
    {
        $store = Cache::getStore();
        if (method_exists($store, 'flush')) {
            $store->flush();
        }
    }

    /**
     * Score how well a creature image and a travel image go together visually (1–10).
     * Uses free local color analysis. Returns null if an image cannot be fetched or parsed.
     */
    public function scoreMatch(string $creatureImageUrl, string $travelImageUrl): ?float
    {
        $creatureHsv = $this->getDominantHsv($creatureImageUrl);
        $travelHsv = $this->getDominantHsv($travelImageUrl);

        return self::scoreFromHsv($creatureHsv, $travelHsv);
    }

    /**
     * Score from two precomputed HSV values [h, s, v]. Public so callers can cache and reuse.
     */
    public static function scoreFromHsv(?array $hsv1, ?array $hsv2): ?float
    {
        if ($hsv1 === null || $hsv2 === null) {
            return null;
        }

        $h1 = $hsv1[0];
        $h2 = $hsv2[0];
        $dh = abs($h1 - $h2);
        if ($dh > 180) {
            $dh = 360 - $dh;
        }
        $hueDiff = min($dh, 180 - $dh);
        $hueDiff = min($hueDiff, 90);
        $score = 10.0 * (1.0 - $hueDiff / 90.0);
        $score = max(1.0, min(10.0, $score));

        return round($score, 1);
    }

    /**
     * Fetch image from URL, resize, and return dominant color as HSV [h, s, v] or null.
     * Results are cached by URL (30 days) so repeat runs skip re-downloading.
     */
    public function getDominantHsv(string $imageUrl): ?array
    {
        $key = self::CACHE_KEY_PREFIX . md5($imageUrl);
        $cached = Cache::get($key);
        if (is_array($cached) && count($cached) === 3) {
            return $cached;
        }

        $hsv = $this->fetchAndComputeHsv($imageUrl);
        if ($hsv !== null) {
            Cache::put($key, $hsv, now()->addDays(self::CACHE_TTL_DAYS));
        }

        return $hsv;
    }

    /**
     * Download image and compute dominant HSV. No cache.
     * Requires the PHP GD extension (e.g. apt install php8.2-gd or php-gd).
     */
    private function fetchAndComputeHsv(string $imageUrl): ?array
    {
        if (! \function_exists('imagecreatefromstring')) {
            throw new \RuntimeException(
                'The PHP GD extension is required for the image-match job. Install it (e.g. apt install php-gd or php8.2-gd) and restart PHP or the web server.'
            );
        }

        try {
            $response = Http::timeout(15)->get($imageUrl);
            if (! $response->successful()) {
                return null;
            }
            $blob = $response->body();
            if (strlen($blob) === 0) {
                return null;
            }
        } catch (\Throwable $e) {
            Log::debug('ImageMatchService: fetch failed', ['url' => $imageUrl, 'message' => $e->getMessage()]);
            return null;
        }

        $img = @\imagecreatefromstring($blob);
        if ($img === false) {
            return null;
        }

        $w = \imagesx($img);
        $h = \imagesy($img);
        if ($w < 1 || $h < 1) {
            \imagedestroy($img);
            return null;
        }

        // Copy into a non-interlaced truecolor image to avoid libpng "Interlace handling" warnings
        // when we later resample (png_read_image is used on interlaced PNGs).
        $flat = \imagecreatetruecolor($w, $h);
        if ($flat === false) {
            \imagedestroy($img);
            return null;
        }
        \imagecopy($flat, $img, 0, 0, 0, 0, $w, $h);
        \imagedestroy($img);
        $img = $flat;

        $size = 32;
        $thumb = \imagecreatetruecolor($size, $size);
        if ($thumb === false) {
            \imagedestroy($img);
            return null;
        }
        \imagecopyresampled($thumb, $img, 0, 0, 0, 0, $size, $size, $w, $h);
        \imagedestroy($img);

        $sumR = $sumG = $sumB = $n = 0;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = \imagecolorat($thumb, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $sumR += $r;
                $sumG += $g;
                $sumB += $b;
                $n++;
            }
        }
        \imagedestroy($thumb);

        if ($n === 0) {
            return null;
        }
        $r = $sumR / $n;
        $g = $sumG / $n;
        $b = $sumB / $n;

        return $this->rgbToHsv($r, $g, $b);
    }

    private function rgbToHsv(float $r, float $g, float $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $v = $max;
        $d = $max - $min;
        $s = $max == 0 ? 0 : $d / $max;

        if ($d == 0) {
            $h = 0;
        } else {
            switch (true) {
                case $max == $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $max == $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                default:
                    $h = ($r - $g) / $d + 4;
                    break;
            }
            $h /= 6;
        }

        return [$h * 360, $s * 100, $v * 100];
    }
}
