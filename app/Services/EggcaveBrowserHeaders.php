<?php

namespace App\Services;

/**
 * Build HTTP headers that mimic a real browser (Chrome) when requesting EggCave.
 * Use for scrapers and sync services to reduce bot detection (e.g. 403 from datacenter IPs).
 */
class EggcaveBrowserHeaders
{
    private const BASE_URL = 'https://eggcave.com';

    /**
     * Headers for a GET request to $url, with optional referer (defaults to site home).
     * Order and values match what Chrome typically sends for a navigation.
     */
    public static function forRequest(string $url, ?string $referer = null): array
    {
        $referer = $referer ?? self::BASE_URL . '/';

        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'DNT' => '1',
            'Referer' => $referer,
            'Sec-Ch-Ua' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => self::refererToFetchSite($url, $referer),
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ];
    }

    private static function refererToFetchSite(string $url, string $referer): string
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $refHost = parse_url($referer, PHP_URL_HOST);
        if ($urlHost && $refHost && strtolower($urlHost) === strtolower($refHost)) {
            return 'same-origin';
        }
        return $refHost ? 'cross-site' : 'none';
    }

    /**
     * Sleep for a random duration between min and max seconds (human-like jitter).
     */
    public static function delayWithJitter(float $minSeconds = 0.15, float $maxSeconds = 0.45): void
    {
        $sec = $minSeconds + (mt_rand() / mt_getrandmax()) * ($maxSeconds - $minSeconds);
        usleep((int) round($sec * 1_000_000));
    }
}
