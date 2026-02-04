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

        $appUrl = config('app.url');
        if (! $appUrl) {
            return;
        }

        $host = request()->getHost();
        $isLocalHost = in_array(strtolower($host), ['localhost', '127.0.0.1'], true);

        if (! $isLocalHost) {
            // Use canonical domain (APP_URL) for all generated URLs so redirects go to
            // egcavity.com, not the server IP.
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
