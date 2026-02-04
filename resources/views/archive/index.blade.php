@extends('layouts.app')

@section('title', 'Archive')

@section('content')
<div class="page-header">
    <h1>Archive</h1>
    @if($tag)
    <p class="lead"><span style="font-size: 0.9375rem;">Showing creatures tagged: <strong>{{ $tag }}</strong></span></p>
    @endif
</div>

<style>
    .archive-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .archive-toolbar form { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
    .archive-toolbar input[type="search"] {
        padding: 0.5rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 0.9375rem;
        min-width: 12rem;
    }
    .archive-toolbar select {
        padding: 0.5rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 0.9375rem;
        background: var(--surface);
    }
    .archive-toolbar button, .archive-toolbar .btn {
        padding: 0.5rem 1rem;
        background: var(--accent);
        color: white;
        border: none;
        border-radius: var(--radius-sm);
        font-weight: 500;
        font-size: 0.9375rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .archive-toolbar button:hover, .archive-toolbar .btn:hover { background: var(--accent-hover); }
    .archive-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.25rem;
    }
    @media (max-width: 1200px) {
        .archive-grid { grid-template-columns: repeat(4, 1fr); }
    }
    @media (max-width: 900px) {
        .archive-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 600px) {
        .archive-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .archive-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: box-shadow 0.15s, border-color 0.15s;
        padding: 0.5rem;
    }
    .archive-card:hover { border-color: var(--accent); box-shadow: var(--shadow-lg); }
    .archive-card a { text-decoration: none; color: inherit; display: block; }
    .archive-card .thumb {
        aspect-ratio: 1;
        background: var(--surface);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .archive-card .thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .archive-card .thumb .fallback {
        font-size: 2rem;
        color: var(--text-secondary);
    }
    .archive-card .label {
        padding: 0.75rem 1rem 0 1rem;
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--text);
        text-align: center;
    }
    .archive-pagination { margin-top: 2rem; margin-bottom: 2rem; }
    .archive-pagination nav { display: flex; justify-content: center; flex-wrap: wrap; gap: 0.25rem; }
    .archive-pagination ul.pagination { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 0.25rem; justify-content: center; }
    .archive-pagination ul.pagination li { display: inline-block; }
    .archive-pagination ul.pagination a, .archive-pagination ul.pagination span {
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-sm);
        font-size: 0.875rem;
        text-decoration: none;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        display: inline-block;
    }
    .archive-pagination ul.pagination a:hover { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
    .archive-pagination ul.pagination span { background: var(--bg); color: var(--text-secondary); }
    .archive-pagination ul.pagination li.disabled span { cursor: not-allowed; }
    .archive-pagination ul.pagination li.active span { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
</style>

@if($tag)
    <div class="card" style="border-color: var(--accent); background: var(--accent-muted); margin-bottom: 1rem;">
        <p style="margin: 0;">
            Filtering by tag: <strong>{{ $tag }}</strong>
            <a href="{{ route('archive.index', array_merge(request()->except('tag'), ['sort' => $sort, 'dir' => $dir])) }}" style="margin-left: 0.5rem; color: var(--accent); font-weight: 500;">Clear filter</a>
        </p>
    </div>
@endif

<form method="get" action="{{ route('archive.index') }}" class="archive-toolbar">
    <input type="search" name="q" value="{{ old('q', $search) }}" placeholder="Search archive..." aria-label="Search">
    @if($tag)<input type="hidden" name="tag" value="{{ $tag }}">@endif
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <button type="submit">Search</button>
</form>

<form method="get" action="{{ route('archive.index') }}" class="archive-toolbar" style="margin-top: -0.5rem;">
    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    @if($tag)<input type="hidden" name="tag" value="{{ $tag }}">@endif
    <label for="sort" style="font-size: 0.9375rem; color: var(--text-secondary);">Sort by</label>
    <select name="sort" id="sort" onchange="this.form.submit()">
        <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>Title</option>
        <option value="published_at" {{ $sort === 'published_at' ? 'selected' : '' }}>Date</option>
        <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>Added</option>
    </select>
    <select name="dir" onchange="this.form.submit()">
        <option value="asc" {{ $dir === 'asc' ? 'selected' : '' }}>A–Z / Oldest first</option>
        <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Z–A / Newest first</option>
    </select>
</form>

@if($items->isEmpty())
    <div class="card">
        <p>No archive items yet. Run <code>php artisan archive:scrape</code> to import from EggCave.com.</p>
        <p><a href="https://eggcave.com/archives" target="_blank" rel="noopener">View archives on EggCave.com →</a></p>
    </div>
@else
    <div class="archive-grid">
        @foreach($items as $item)
            <article class="archive-card">
                <a href="{{ route('archive.show', $item->slug) }}">
                    <div class="thumb">
                        @if($item->thumbnail_url)
                            <img src="{{ $item->thumbnail_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                            <span class="fallback" style="display: none;" aria-hidden="true">?</span>
                        @else
                            <span class="fallback" aria-hidden="true">?</span>
                        @endif
                    </div>
                    <div class="label">{{ $item->title }}</div>
                </a>
            </article>
        @endforeach
    </div>

    @if($items->hasPages())
        <div class="archive-pagination">
            {{ $items->links('pagination::custom') }}
        </div>
    @endif
@endif
@endsection
