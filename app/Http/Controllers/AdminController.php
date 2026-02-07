<?php

namespace App\Http\Controllers;

use App\Http\Controllers\RunJobController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $showJobs = $user->isAdmin();

        return view('auth.admin', [
            'scheduledJobs' => $showJobs ? $this->getScheduledJobs() : [],
            'jobLogs' => $showJobs ? RunJobController::getJobLogs() : [],
        ]);
    }

    /**
     * @return array<int, array{command: string, description: string, schedule: string, next_run: Carbon}>
     */
    private function getScheduledJobs(): array
    {
        $now = Carbon::now();
        $jobs = [
            [
                'command' => 'archive:scrape',
                'description' => 'Scrape creature archive from EggCave',
                'schedule' => 'Daily at 00:30',
                'time' => [0, 30],
            ],
            [
                'command' => 'items:scrape',
                'description' => 'Scrape items catalog from EggCave',
                'schedule' => 'Daily at 00:30',
                'time' => [0, 30],
            ],
        ];

        foreach ($jobs as &$job) {
            [$hour, $minute] = $job['time'];
            $next = $now->copy()->setTime($hour, $minute, 0);
            if ($next->lte($now)) {
                $next->addDay();
            }
            $job['next_run'] = $next;
            unset($job['time']);
        }

        return $jobs;
    }
}
