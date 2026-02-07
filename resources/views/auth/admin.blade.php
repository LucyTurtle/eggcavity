@extends('layouts.app')

@section('title', 'Admin')

@section('content')
<div class="page-header">
    <h1>Admin</h1>
    <p class="lead">Admin area. You're signed in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }}).</p>
</div>

@if(auth()->user()->isAdmin())
<div class="card" style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.25rem; margin: 0 0 0.25rem 0;">Automatic jobs</h2>
    <p class="lead" style="margin: 0 0 1rem 0; font-size: 0.9375rem;">Scheduled tasks run via the Laravel scheduler. Ensure cron runs <code>php artisan schedule:run</code> every minute.</p>
    <table style="width: 100%; border-collapse: collapse; font-size: 0.9375rem;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border);">
                <th style="text-align: left; padding: 0.5rem 0.75rem 0.5rem 0; color: var(--text-secondary); font-weight: 600;">Command</th>
                <th style="text-align: left; padding: 0.5rem 0.75rem; color: var(--text-secondary); font-weight: 600;">Description</th>
                <th style="text-align: left; padding: 0.5rem 0.75rem; color: var(--text-secondary); font-weight: 600;">Schedule</th>
                <th style="text-align: left; padding: 0.5rem 0 0.5rem 0.75rem; color: var(--text-secondary); font-weight: 600;">Next run</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scheduledJobs as $job)
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 0.5rem 0.75rem 0.5rem 0;"><code style="font-size: 0.875rem;">{{ $job['command'] }}</code></td>
                    <td style="padding: 0.5rem 0.75rem; color: var(--text);">{{ $job['description'] }}</td>
                    <td style="padding: 0.5rem 0.75rem; color: var(--text-secondary);">{{ $job['schedule'] }}</td>
                    <td style="padding: 0.5rem 0 0.5rem 0.75rem; color: var(--text);">{{ $job['next_run']->format('D, M j, Y \a\t g:i A') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.25rem; margin: 0 0 0.25rem 0;">Run jobs manually</h2>
    <p class="lead" style="margin: 0 0 0.5rem 0; font-size: 0.9375rem;">Schedule a job to run in the backend (not in the browser). It runs on the next scheduler tick, usually within a minute. Archive and items scrapers run in <strong>new only</strong> mode (only creatures/items not already in the DB). Results appear below; refresh the page after the job runs to see the latest.</p>
    <p style="margin: 0 0 1rem 0; font-size: 0.875rem; color: var(--text-secondary);">Logs below are from the <strong>most recent run</strong> of each job—whether that run was triggered by "Run now" or by the <strong>daily schedule</strong> (00:30). Automatic runs use the same log files, so you can see scraper output after the nightly run by refreshing this page.</p>
    @foreach($jobLogs as $info)
        @php $hasLog = !empty(trim($info['last_log'])); @endphp
        <div class="manual-job-block" style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
                <code style="font-size: 0.875rem;">{{ $info['command'] }}</code>
                <span style="color: var(--text-secondary); font-size: 0.9375rem;">{{ $info['label'] }}</span>
                <form method="post" action="{{ route('admin.run-job') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="command" value="{{ $info['command'] }}">
                    <button type="submit" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.875rem;">Run now</button>
                </form>
                @if(!empty($info['last_at_eggcave']))
                    @php
                        $triggerLabel = match($info['last_trigger'] ?? 'schedule') {
                            'run now' => 'manual',
                            'scheduled' => 'scheduled',
                            default => $info['last_trigger'] ?? 'schedule',
                        };
                    @endphp
                    <span style="font-size: 0.8125rem; color: var(--text-secondary);">
                        Last run: {{ $info['last_at_eggcave'] }} ({{ $triggerLabel }})
                    </span>
                @endif
            </div>
            <details style="font-size: 0.875rem;">
                <summary style="cursor: pointer; color: var(--accent);">Last run output</summary>
                <pre class="job-log-pre" style="margin: 0.5rem 0 0; padding: 0.75rem; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: auto; max-height: 20rem; font-size: 0.8125rem; white-space: pre-wrap; word-break: break-all;">{{ isset($info['last_log_display']) && $info['last_log_display'] !== '' ? e($info['last_log_display']) : ($info['last_log'] ? e($info['last_log']) : '(No run yet. Click "Run now" or wait for the daily schedule, then refresh this page.)') }}</pre>
            </details>
        </div>
    @endforeach
</div>
@endif

<div class="page-header" style="margin-top: 1.5rem;">
    <h2 style="font-size: 1.25rem; margin: 0 0 0.25rem 0;">Content</h2>
    @php
        $canManageCreaturesItems = auth()->user()->isAdmin() || auth()->user()->isContentManager();
        $canManageTravelSuggestions = auth()->user()->isAdmin() || auth()->user()->isTravelSuggestor();
    @endphp
    <p class="lead" style="margin: 0;">
        @if($canManageCreaturesItems && $canManageTravelSuggestions)
            Add creatures or items, manage travel suggestions, or approve image-based suggestions below. Edit creatures and items from their archive or item page.
        @elseif($canManageCreaturesItems)
            Add creatures or items here. Edit them from their archive or item page.
        @else
            Manage travel suggestions and approve or reject image-based suggestions below.
        @endif
    </p>
</div>

<style>
    .content-hub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem; }
    .content-hub-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem;
        box-shadow: var(--shadow);
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .content-hub-card:hover { border-color: var(--accent); box-shadow: var(--shadow-lg); }
    .content-hub-card h3 { font-size: 1.125rem; margin: 0 0 0.5rem 0; }
    .content-hub-card p { font-size: 0.9375rem; color: var(--text-secondary); margin: 0 0 1rem 0; line-height: 1.5; }
    .content-hub-card a.btn { display: inline-block; padding: 0.4rem 0.85rem; background: var(--accent); color: white; text-decoration: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; }
    .content-hub-card a.btn:hover { background: var(--accent-hover); }
</style>

<div class="content-hub-grid">
    @if(auth()->user()->isAdmin() || auth()->user()->isContentManager())
    <div class="content-hub-card">
        <h3>Add creature</h3>
        <p>Add a new creature to the archive. Edit existing creatures from the archive page.</p>
        <a href="{{ route('content.creature.create') }}" class="btn">Add creature</a>
    </div>
    <div class="content-hub-card">
        <h3>Add item</h3>
        <p>Add a new item to the catalog. Edit existing items from the item page.</p>
        <a href="{{ route('content.item.create') }}" class="btn">Add item</a>
    </div>
    @endif
    @if(auth()->user()->isAdmin() || auth()->user()->isTravelSuggestor())
    <div class="content-hub-card">
        <h3>Travel suggestions</h3>
        <p>Suggest which travel items go with specific creature stages.</p>
        <a href="{{ route('content.travel-suggestions.index') }}" class="btn">Manage travel suggestions</a>
    </div>
    <div class="content-hub-card">
        <h3>Approve image-based suggestions</h3>
        <p>Review and approve or reject travel suggestions from the image-match job before they go live.</p>
        <a href="{{ route('content.pending-ai-travel-suggestions.index') }}" class="btn">Approve / reject suggestions</a>
    </div>
    @endif
</div>

@if(auth()->user()->isDeveloper())
<div class="card" style="margin-top: 1.5rem;">
    <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem;">User manager</h3>
    <p style="margin: 0 0 0.75rem 0; font-size: 0.9375rem; color: var(--text-secondary);">Add users, change roles, ban, reset passwords, or view the site as any user.</p>
    <p style="margin: 0 0 0.75rem 0;"><a href="{{ route('users.index') }}" class="btn" style="display: inline-block; padding: 0.4rem 0.85rem; background: var(--accent); color: white; text-decoration: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem;">User manager</a></p>
</div>
@endif
@endsection
