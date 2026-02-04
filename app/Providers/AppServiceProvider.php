<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $host = request()->getHost();
        $isLocalHost = in_array(strtolower($host), ['localhost', '127.0.0.1'], true);
        if ($isLocalHost) {
            return;
        }

        // Use canonical URL for all generated links so nav/redirects use the domain, not the IP.
        $canonical = config('app.canonical_url') ?: config('app.url');
        if ($canonical) {
            URL::forceRootUrl(rtrim($canonical, '/'));
            if (str_starts_with($canonical, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
