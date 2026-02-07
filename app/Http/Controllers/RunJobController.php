<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class RunJobController extends Controller
{
    private const ALLOWED_JOBS = [
        'archive:scrape' => [
            'log_file' => 'archive-scrape-last.log',
            'label' => 'Archive scraper',
        ],
        'items:scrape' => [
            'log_file' => 'items-scrape-last.log',
            'label' => 'Items scraper',
        ],
        'travels:suggest-by-image' => [
            'log_file' => 'travels-suggest-by-image-last.log',
            'label' => 'Suggest travels (image match)',
        ],
    ];

    public const PENDING_CACHE_PREFIX = 'run_job_pending_';
    private const PENDING_TTL_MINUTES = 10;

    public function run(Request $request)
    {
        $command = $request->input('command');
        if (! is_string($command) || ! isset(self::ALLOWED_JOBS[$command])) {
            return redirect()->route('dashboard')->with('error', 'Invalid or disallowed command.');
        }

        Cache::put(self::PENDING_CACHE_PREFIX . $command, true, now()->addMinutes(self::PENDING_TTL_MINUTES));

        return redirect()->route('dashboard')->with('success', 'Job "' . $command . '" scheduled. It will run in the backend when the scheduler runs (usually within a minute). Check "Last run" logs below.');
    }

    /**
     * @return array<string>
     */
    public static function getAllowedCommands(): array
    {
        return array_keys(self::ALLOWED_JOBS);
    }

    public static function getLogPathForCommand(string $command): ?string
    {
        $logFile = self::ALLOWED_JOBS[$command]['log_file'] ?? null;

        return $logFile ? storage_path('logs/' . $logFile) : null;
    }

    /**
     * Parse the first line "Started at Y-m-d H:i:s (scheduled)" or "(run now)" to get trigger and time.
     *
     * @return array{trigger: string, at: string|null}
     */
    public static function parseLastRunFromLog(string $logContent): array
    {
        $trigger = 'unknown';
        $at = null;
        $firstLine = trim(explode("\n", $logContent)[0] ?? '');
        if (preg_match('/^Started at (.+?) \((scheduled|run now)\)\s*$/', $firstLine, $m)) {
            $at = trim($m[1]);
            $trigger = $m[2];
        }
        return ['trigger' => $trigger, 'at' => $at];
    }

    /**
     * @return array<string, array{command: string, label: string, log_file: string, last_log: string, last_trigger: string, last_at: string|null}>
     */
    public static function getJobLogs(): array
    {
        $out = [];
        foreach (self::ALLOWED_JOBS as $command => $config) {
            $path = storage_path('logs/' . $config['log_file']);
            $lastLog = '';
            if (File::exists($path)) {
                try {
                    $lastLog = File::get($path) ?: '';
                } catch (\Throwable $e) {
                    $lastLog = '(Could not read log file. If jobs run from cron as root, make storage/logs readable by the web server: chmod -R 755 storage/logs or run cron as the web user.)';
                }
            }
            $parsed = self::parseLastRunFromLog($lastLog);
            $out[$command] = [
                'command' => $command,
                'label' => $config['label'],
                'log_file' => $config['log_file'],
                'last_log' => $lastLog,
                'last_trigger' => $parsed['trigger'],
                'last_at' => $parsed['at'],
            ];
        }
        return $out;
    }
}
