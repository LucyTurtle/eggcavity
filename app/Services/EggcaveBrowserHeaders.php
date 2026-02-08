<?php

namespace App\Services;

/**
 * HTTP headers for EggCave requests. Identifies as EggcavityBot with link to project.
 */
class EggcaveBrowserHeaders
{
    /**
     * Headers for a GET request to EggCave (scrapers and sync services).
     */
    public static function forRequest(string $url, ?string $referer = null): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (compatible; EggcavityBot/1.0; +https://eggcavity.com)',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
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
