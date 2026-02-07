@extends('layouts.app')

@section('title', 'Travel suggestions')

@section('content')
<div class="page-header">
    <h1>Travel suggestions</h1>
    <p class="lead">Manage travel suggestions per creature (recommended for the whole creature).</p>
</div>

<style>
    .content-table { width: 100%; border-collapse: collapse; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
    .content-table th, .content-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
    .content-table th { background: var(--bg); font-weight: 600; font-size: 0.875rem; color: var(--text-secondary); }
    .content-table tr:last-child td { border-bottom: none; }
    .content-table a { color: var(--accent); font-weight: 500; }
    .content-table a:hover { text-decoration: underline; }
    .content-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .content-actions form { margin: 0; }
    .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8125rem; border-radius: var(--radius-sm); cursor: pointer; font-family: inherit; text-decoration: none; display: inline-block; border: 1px solid var(--border); background: var(--surface); color: var(--text); }
    .btn-sm:hover { border-color: var(--accent); color: var(--accent); }
    .btn-sm.btn-danger { border-color: #dc2626; color: #dc2626; }
    .btn-sm.btn-danger:hover { background: #fef2f2; }
    .btn-add { padding: 0.35rem 0.75rem; font-size: 0.875rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-add:hover { background: var(--accent-hover); }
    .content-pagination { margin-top: 1.5rem; }
    .content-pagination nav { display: flex; justify-content: center; flex-wrap: wrap; gap: 0.25rem; }
    .content-pagination ul.pagination { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 0.25rem; justify-content: center; }
    .content-pagination ul.pagination li { display: inline-block; }
    .content-pagination ul.pagination a, .content-pagination ul.pagination span { padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.875rem; text-decoration: none; border: 1px solid var(--border); background: var(--surface); color: var(--text); display: inline-block; }
    .content-pagination ul.pagination a:hover { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
    .content-pagination ul.pagination span { background: var(--bg); color: var(--text-secondary); }
    .content-pagination ul.pagination li.disabled span { cursor: not-allowed; }
    .content-pagination ul.pagination li.active span { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
</style>

@if(session('success'))
    <div class="card" style="background: #f0fdf4; border-color: #22c55e; margin-bottom: 1rem;">
        <p style="margin: 0; color: #15803d;">{{ session('success') }}</p>
    </div>
@endif

<div style="margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;">
    <a href="{{ route('content.travel-suggestions.create') }}" class="btn-add">Add travel suggestion</a>
    @if($suggestions->total() > 0)
        <span style="font-size: 0.9375rem; color: var(--text-secondary);">Page {{ $suggestions->currentPage() }} of {{ $suggestions->lastPage() }} ({{ $suggestions->total() }} total)</span>
    @endif
</div>

@if($suggestions->isEmpty())
    <p style="color: var(--text-secondary); font-size: 0.9375rem;">No travel suggestions yet. <a href="{{ route('content.travel-suggestions.create') }}">Add one</a>.</p>
@else
    <table class="content-table">
        <thead>
            <tr>
                <th>Creature</th>
                <th>Travel</th>
                <th>Notes</th>
                <th style="width: 12rem;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($suggestions as $suggestion)
                <tr>
                    <td>
                        <a href="{{ route('archive.show', $suggestion->archiveItem->slug) }}">
                            {{ $suggestion->archiveItem->title }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('items.show', $suggestion->item->slug) }}">
                            {{ $suggestion->item->name }}
                        </a>
                    </td>
                    <td>{{ $suggestion->notes ?: '—' }}</td>
                    <td>
                        <div class="content-actions">
                            <a href="{{ route('content.travel-suggestions.edit', $suggestion) }}" class="btn-sm">Edit</a>
                            <form method="post" action="{{ route('content.travel-suggestions.destroy', $suggestion) }}" onsubmit="return confirm('Remove this travel suggestion?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-sm btn-danger">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($suggestions->hasPages())
        <div class="content-pagination">
            {{ $suggestions->links('pagination::custom') }}
        </div>
    @endif
@endif

<p style="margin-top: 1.5rem;"><a href="{{ route('content.index') }}">← Back to manage content</a></p>
@endsection
