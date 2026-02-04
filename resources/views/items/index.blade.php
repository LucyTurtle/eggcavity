@extends('layouts.app')

@section('title', 'Items')

@section('content')
<div class="page-header">
    <h1>Items</h1>
    @if($shop || $use_type || $filter_retired || $filter_cavecash)
    <p class="lead">
        <span style="font-size: 0.9375rem;">
            @if($shop)Shop: <strong>{{ $shop }}</strong>
            @endif
            @if($shop && ($use_type || $filter_retired || $filter_cavecash)) ·
            @endif
            @if($use_type)Type: <strong>{{ ucfirst($use_type) }}</strong>
            @endif
            @if($use_type && ($filter_retired || $filter_cavecash)) ·
            @endif
            @if($filter_retired)Retired only
            @endif
            @if($filter_retired && $filter_cavecash) ·
            @endif
            @if($filter_cavecash)Cave cash only
            @endif
        </span>
    </p>
    @endif
</div>

<style>
    .items-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .items-toolbar form { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
    .items-toolbar input[type="search"] {
        padding: 0.5rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 0.9375rem;
        min-width: 12rem;
    }
    .items-toolbar select {
        padding: 0.5rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 0.9375rem;
        background: var(--surface);
    }
    .items-toolbar button, .items-toolbar .btn {
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
    .items-toolbar button:hover, .items-toolbar .btn:hover { background: var(--accent-hover); }
    .items-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.25rem;
    }
    @@media (max-width: 1200px) {
        .items-grid { grid-template-columns: repeat(4, 1fr); }
    }
    @@media (max-width: 900px) {
        .items-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @@media (max-width: 600px) {
        .items-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .item-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: box-shadow 0.15s, border-color 0.15s;
        padding: 0.5rem;
    }
    .item-card:hover { border-color: var(--accent); box-shadow: var(--shadow-lg); }
    .item-card a { text-decoration: none; color: inherit; display: block; }
    .item-card .thumb {
        aspect-ratio: 1;
        background: var(--surface);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .item-card .thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .item-card .thumb .fallback {
        font-size: 2rem;
        color: var(--text-secondary);
    }
    .item-card .label {
        padding: 0.75rem 1rem 0 1rem;
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--text);
        text-align: center;
    }
    .items-pagination { margin-top: 2rem; margin-bottom: 2rem; }
    .items-pagination nav { display: flex; justify-content: center; flex-wrap: wrap; gap: 0.25rem; }
    .items-pagination ul.pagination { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 0.25rem; justify-content: center; }
    .items-pagination ul.pagination li { display: inline-block; }
    .items-pagination ul.pagination a, .items-pagination ul.pagination span {
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-sm);
        font-size: 0.875rem;
        text-decoration: none;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        display: inline-block;
    }
    .items-pagination ul.pagination a:hover { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
    .items-pagination ul.pagination span { background: var(--bg); color: var(--text-secondary); }
    .items-pagination ul.pagination li.disabled span { cursor: not-allowed; }
    .items-pagination ul.pagination li.active span { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
</style>

@if($shop || $use_type || $filter_retired || $filter_cavecash)
    <div class="card" style="border-color: var(--accent); background: var(--accent-muted); margin-bottom: 1rem;">
        <p style="margin: 0;">
            @if($shop)Filtering by shop: <strong>{{ $shop }}</strong>
            @endif
            @if($shop && ($use_type || $filter_retired || $filter_cavecash)) ·
            @endif
            @if($use_type)Filtering by type: <strong>{{ ucfirst($use_type) }}</strong>
            @endif
            @if($use_type && ($filter_retired || $filter_cavecash)) ·
            @endif
            @if($filter_retired)Retired only
            @endif
            @if($filter_retired && $filter_cavecash) ·
            @endif
            @if($filter_cavecash)Cave cash only
            @endif
            <a href="{{ route('items.index', array_merge(request()->except(['shop', 'use_type', 'retired', 'cavecash']), ['sort' => $sort, 'dir' => $dir])) }}" style="margin-left: 0.5rem; color: var(--accent); font-weight: 500;">Clear filters</a>
        </p>
    </div>
@endif

<form method="get" action="{{ route('items.index') }}" class="items-toolbar">
    <input type="search" name="q" value="{{ old('q', $search) }}" placeholder="Search items..." aria-label="Search">
    @if($shop)<input type="hidden" name="shop" value="{{ $shop }}">
    @endif
    @if($use_type)<input type="hidden" name="use_type" value="{{ $use_type }}">
    @endif
    @if($filter_retired)<input type="hidden" name="retired" value="1">
    @endif
    @if($filter_cavecash)<input type="hidden" name="cavecash" value="1">
    @endif
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <button type="submit">Search</button>
</form>

<form method="get" action="{{ route('items.index') }}" class="items-toolbar" style="margin-top: -0.5rem;">
    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">
    @endif
    @if($shop)<input type="hidden" name="shop" value="{{ $shop }}">
    @endif
    @if($use_type)<input type="hidden" name="use_type" value="{{ $use_type }}">
    @endif
    <label for="shop" style="font-size: 0.9375rem; color: var(--text-secondary);">Shop</label>
    <select name="shop" id="shop" onchange="this.form.submit()">
        <option value="">All shops</option>
        @foreach($shops as $s)
            <option value="{{ $s }}" {{ $shop === $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
    </select>
    <label for="use_type" style="font-size: 0.9375rem; color: var(--text-secondary); margin-left: 0.5rem;">Type</label>
    <select name="use_type" id="use_type" onchange="this.form.submit()">
        <option value="">All types</option>
        <option value="item" {{ $use_type === 'item' ? 'selected' : '' }}>Item</option>
        <option value="travel" {{ $use_type === 'travel' ? 'selected' : '' }}>Travel</option>
        <option value="other" {{ $use_type === 'other' ? 'selected' : '' }}>Other</option>
    </select>
    <span style="margin-left: 0.5rem; display: inline-flex; align-items: center; gap: 0.5rem;">
        <label style="font-size: 0.9375rem; color: var(--text-secondary); margin: 0;"><input type="checkbox" name="retired" value="1" {{ $filter_retired ? 'checked' : '' }} onchange="this.form.submit()"> Retired only</label>
        <label style="font-size: 0.9375rem; color: var(--text-secondary); margin: 0;"><input type="checkbox" name="cavecash" value="1" {{ $filter_cavecash ? 'checked' : '' }} onchange="this.form.submit()"> Cave cash only</label>
    </span>
    <label for="sort" style="font-size: 0.9375rem; color: var(--text-secondary); margin-left: 0.5rem;">Sort by</label>
    <select name="sort" id="sort" onchange="this.form.submit()">
        <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name</option>
        <option value="first_appeared" {{ $sort === 'first_appeared' ? 'selected' : '' }}>Date</option>
        <option value="sort_order" {{ $sort === 'sort_order' ? 'selected' : '' }}>Order</option>
        <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>Added</option>
    </select>
    <select name="dir" onchange="this.form.submit()">
        <option value="asc" {{ $dir === 'asc' ? 'selected' : '' }}>A–Z / Oldest first</option>
        <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Z–A / Newest first</option>
    </select>
</form>

@if($items->isEmpty())
    <div class="card">
        <p>No items yet. Run <code>php artisan items:scrape</code> to import from EggCave.com.</p>
        <p><a href="https://eggcave.com/items" target="_blank" rel="noopener">View items on EggCave.com →</a></p>
    </div>
@endif

@if(!$items->isEmpty())
    <div class="items-grid">
        @foreach($items as $item)
            <article class="item-card">
                <a href="{{ route('items.show', $item->slug) }}">
                    <div class="thumb">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                            <span class="fallback" style="display: none;" aria-hidden="true">?</span>
                        @else
                            <span class="fallback" aria-hidden="true">?</span>
                        @endif
                    </div>
                    <div class="label">{{ $item->name }}</div>
                </a>
            </article>
        @endforeach
    </div>
    <div class="items-pagination">
        {{ $items->links('pagination::custom') }}
    </div>
@endif
@endsection
