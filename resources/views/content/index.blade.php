@extends('layouts.app')

@section('title', 'Manage content')

@section('content')
<div class="page-header">
    <h1>Manage content</h1>
    <p class="lead">Add, edit, or remove creatures and items.</p>
</div>

<style>
    .content-section { margin-bottom: 2.5rem; }
    .content-section h2 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.75rem 0; color: var(--text); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
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
</style>

<div class="content-section">
    <h2>
        Creatures
        <a href="{{ route('content.creature.create') }}" class="btn-add">Add creature</a>
    </h2>
    @if($creatures->isEmpty())
        <p style="color: var(--text-secondary); font-size: 0.9375rem;">No creatures yet. <a href="{{ route('content.creature.create') }}">Add one</a>.</p>
    @else
        <table class="content-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th style="width: 12rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creatures as $creature)
                    <tr>
                        <td><a href="{{ route('archive.show', $creature->slug) }}">{{ $creature->title }}</a></td>
                        <td><code style="font-size: 0.8125rem;">{{ $creature->slug }}</code></td>
                        <td>
                            <div class="content-actions">
                                <a href="{{ route('content.creature.edit', $creature) }}" class="btn-sm">Edit</a>
                                <form method="post" action="{{ route('content.creature.destroy', $creature) }}" onsubmit="return confirm('Remove this creature?');">
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
    @endif
</div>

<div class="content-section">
    <h2>
        Travel suggestions
        <a href="{{ route('content.travel-suggestions.index') }}" class="btn-sm">Manage</a>
        <a href="{{ route('content.travel-suggestions.create') }}" class="btn-add">Add suggestion</a>
    </h2>
    <p style="color: var(--text-secondary); font-size: 0.9375rem; margin-bottom: 1rem;">Suggest which travel items go well with specific creature stages.</p>
</div>

<div class="content-section">
    <h2>
        Items
        <a href="{{ route('content.item.create') }}" class="btn-add">Add item</a>
    </h2>
    @if($items->isEmpty())
        <p style="color: var(--text-secondary); font-size: 0.9375rem;">No items yet. <a href="{{ route('content.item.create') }}">Add one</a>.</p>
    @else
        <table class="content-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Use</th>
                    <th style="width: 12rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td><a href="{{ route('items.show', $item->slug) }}">{{ $item->name }}</a></td>
                        <td><code style="font-size: 0.8125rem;">{{ $item->slug }}</code></td>
                        <td>{{ $item->use ?? '—' }}</td>
                        <td>
                            <div class="content-actions">
                                <a href="{{ route('content.item.edit', $item) }}" class="btn-sm">Edit</a>
                                <form method="post" action="{{ route('content.item.destroy', $item) }}" onsubmit="return confirm('Remove this item?');">
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
    @endif
</div>

<p style="margin-top: 1.5rem;"><a href="{{ route('dashboard') }}">← Back to dashboard</a></p>
@endsection
