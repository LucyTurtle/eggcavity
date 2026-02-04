@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1>Dashboard</h1>
    <p class="lead">Admin area. You're signed in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }}).</p>
</div>

<div class="card">
    <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem;">Quick actions</h3>
    <ul style="margin: 0; padding-left: 1.25rem;">
        <li><a href="{{ route('archive.index') }}">View archive</a></li>
        <li><a href="{{ route('items.index') }}">View items</a></li>
        @if(auth()->user()->isDeveloper())
            <li>Run scrapers from the command line: <code>php artisan archive:scrape</code>, <code>php artisan items:scrape</code></li>
        @endif
    </ul>
</div>

@if(auth()->user()->isDeveloper() && $users->isNotEmpty())
<div class="card" style="margin-top: 1.5rem;">
    <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem;">View as user</h3>
    <p style="margin: 0 0 0.75rem 0; font-size: 0.9375rem; color: var(--text-secondary);">See the site as any user would. Click to start, then use "End impersonation" in the banner to return.</p>
    <ul style="margin: 0; padding-left: 1.25rem; list-style: none; padding-left: 0;">
        @foreach($users as $u)
            <li style="margin-bottom: 0.5rem;">
                <form method="post" action="{{ route('impersonate.start', $u) }}" style="display: inline;">
                    @csrf
                    <button type="submit" style="background: none; border: none; color: var(--accent); font-weight: 500; font-size: 0.9375rem; cursor: pointer; padding: 0; font-family: inherit;">View as {{ $u->name }}</button>
                </form>
                <span style="color: var(--text-secondary); font-size: 0.875rem;"> — {{ $u->email }} ({{ $u->role }})</span>
            </li>
        @endforeach
    </ul>
</div>
@endif
@endsection
