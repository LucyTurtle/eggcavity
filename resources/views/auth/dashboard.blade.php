@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1>Dashboard</h1>
    <p class="lead">Admin area. You're signed in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }}).</p>
</div>

<div class="page-header" style="margin-top: 1.5rem;">
    <h2 style="font-size: 1.25rem; margin: 0 0 0.25rem 0;">Content</h2>
    <p class="lead" style="margin: 0;">Add creatures or items here. Edit them from their archive or item page.</p>
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
    <div class="content-hub-card">
        <h3>Travel suggestions</h3>
        <p>Suggest which travel items go with specific creature stages.</p>
        <a href="{{ route('content.travel-suggestions.index') }}" class="btn">Manage travel suggestions</a>
    </div>
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
