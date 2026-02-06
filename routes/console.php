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

// Scrape commands: always run "new only" (no --full) when triggered from dashboard or daily schedule
$scrapeCommandOptions = ['--full' => false];

// Helper: run a command and write output to its dashboard log file (so "Last run" on dashboard always shows something)
$runJobAndLog = function (string $command, string $trigger = 'scheduled', array $parameters = []): void {
    $logPath = RunJobController::getLogPathForCommand($command);
    if (! $logPath) {
        return;
    }
    $startLine = 'Started at ' . now()->toDateTimeString() . " ({$trigger})\n";
    File::put($logPath, $startLine);
    @chmod($logPath, 0644);
    try {
        Artisan::call($command, $parameters);
        $output = Artisan::output();
        if ($output !== '') {
            File::append($logPath, $output);
        }
    } catch (\Throwable $e) {
        File::append($logPath, "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    @chmod($logPath, 0644);
};

// Daily at 12:30 AM: run creature and item scrapers (new only) and write to dashboard logs
Schedule::call(fn () => $runJobAndLog('archive:scrape', 'scheduled', $scrapeCommandOptions))->dailyAt('00:30');
Schedule::call(fn () => $runJobAndLog('items:scrape', 'scheduled', $scrapeCommandOptions))->dailyAt('00:30');

// Every minute: run any job requested from the dashboard "Run now" button (scrapers run new-only)
Schedule::call(function () use ($runJobAndLog, $scrapeCommandOptions) {
    $scrapeCommands = ['archive:scrape', 'items:scrape'];
    foreach (RunJobController::getAllowedCommands() as $command) {
        $cacheKey = RunJobController::PENDING_CACHE_PREFIX . $command;
        if (! Cache::pull($cacheKey)) {
            continue;
        }
        $params = in_array($command, $scrapeCommands, true) ? $scrapeCommandOptions : [];
        $runJobAndLog($command, 'run now', $params);
    }
})->everyMinute();
