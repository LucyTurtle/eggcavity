<?php

use App\Http\Controllers\RunJobController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Daily at 12:30 AM: run creature and item scrapers
Schedule::command('archive:scrape')->dailyAt('00:30');
Schedule::command('items:scrape')->dailyAt('00:30');

// Every minute: run any job that was requested from the dashboard "Run now" button (runs in backend, not in the browser)
Schedule::call(function () {
    foreach (RunJobController::getAllowedCommands() as $command) {
        $cacheKey = RunJobController::PENDING_CACHE_PREFIX . $command;
        if (! Cache::pull($cacheKey)) {
            continue;
        }
        $logPath = RunJobController::getLogPathForCommand($command);
        if (! $logPath) {
            continue;
        }
        $startLine = 'Started at ' . now()->toDateTimeString() . "\n";
        File::put($logPath, $startLine);
        Artisan::call($command);
        $output = Artisan::output();
        if ($output !== '') {
            File::append($logPath, $output);
        }
    }
})->everyMinute();
