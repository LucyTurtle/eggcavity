@extends('layouts.app')

@section('title', 'Items')

@section('content')
@if(session('success'))
    <div class="card" style="background: var(--accent-muted); border-color: var(--accent); margin-bottom: 1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="card" style="border-color: #dc2626; background: #fef2f2; margin-bottom: 1rem;">{{ session('error') }}</div>
@endif
<div class="page-header">
    <h1>Items</h1>
    <p class="lead">
        @if(!empty($selectedTags))
            @php
                $tagLabels = collect($itemFilterTagOptions ?? [])->keyBy('value');
            @endphp
            <span style="font-size: 0.9375rem;">Tags: <strong>{{ implode(', ', array_map(fn($v) => $tagLabels->get($v)['label'] ?? $v, $selectedTags)) }}</strong></span>
        @endif
        <span style="font-size: 0.9375rem;">{{ number_format($items->total()) }} {{ Str::plural('item', $items->total()) }}</span>
        @if($shop || $use_type)
        <span style="font-size: 0.9375rem;">
            @if($shop) · Shop: <strong>{{ $shop }}</strong>
            @endif
            @if($use_type) · Type: <strong>{{ ucfirst($use_type) }}</strong>
            @endif
        </span>
        @endif
    </p>
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
    .items-toolbar--filters { display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: 1rem 1.25rem; align-items: end; margin-top: -0.5rem; }
    .items-toolbar--filters .items-toolbar__field { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
    .items-toolbar--filters .items-toolbar__field label { font-size: 0.9375rem; color: var(--text-secondary); margin: 0; }
    .items-toolbar--filters .items-toolbar__field select,
    .items-toolbar--filters .items-toolbar__field input[type="text"],
    .items-toolbar--filters .items-toolbar__field input[type="number"] { width: 100%; min-width: 0; }
    .items-toolbar--filters .items-toolbar__field--checkbox { flex-direction: row; align-items: center; }
    .items-toolbar--filters .items-toolbar__field--checkbox label { display: flex; align-items: center; gap: 0.35rem; }
    .items-toolbar input[type="search"] {
        padding: 0.5rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 0.9375rem;
        min-width: 12rem;
    }
    .items-toolbar select,
    .items-toolbar input[type="number"] {
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
    .items-tag-chip { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.2rem 0.5rem; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.875rem; }
    .items-tag-chip-remove { color: var(--text-secondary); text-decoration: none; font-size: 1.1rem; line-height: 1; padding: 0 0.15rem; border-radius: 2px; }
    .items-tag-chip-remove:hover { color: var(--accent); background: var(--bg); }
    .items-tags-dropdown { position: relative; display: inline-block; }
    .items-tags-dropdown__trigger {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.5rem 0.75rem; font-size: 0.9375rem;
        background: var(--surface); color: var(--text);
        border: 1px solid var(--border); border-radius: var(--radius-sm);
        cursor: pointer; font-family: inherit;
    }
    .items-tags-dropdown__trigger:hover { border-color: var(--accent); color: var(--accent); }
    .items-tags-dropdown__trigger[aria-expanded="true"] { border-color: var(--accent); background: var(--accent-muted); color: var(--accent); }
    .items-tags-dropdown__badge { font-size: 0.75rem; padding: 0.1rem 0.4rem; border-radius: 999px; background: var(--accent); color: white; }
    .items-tags-dropdown__panel {
        display: none; position: absolute; top: 100%; left: 0; margin-top: 0.25rem;
        min-width: 14rem; max-width: 20rem; max-height: min(70vh, 24rem);
        background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
        box-shadow: var(--shadow-lg); z-index: 50;
        flex-direction: column;
    }
    .items-tags-dropdown__panel.is-open { display: flex; }
    .items-tags-dropdown__search { padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--border); }
    .items-tags-dropdown__search input { width: 100%; padding: 0.4rem 0.5rem; font-size: 0.875rem; border: 1px solid var(--border); border-radius: var(--radius-sm); box-sizing: border-box; }
    .items-tags-dropdown__list { overflow-y: auto; padding: 0.5rem; flex: 1; min-height: 0; }
    .items-tags-dropdown__list label { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.5rem; font-size: 0.875rem; cursor: pointer; border-radius: var(--radius-sm); }
    .items-tags-dropdown__list label:hover { background: var(--accent-muted); }
    .items-tags-dropdown__list label.tag-search-hidden { display: none; }
    .items-tags-dropdown__footer { padding: 0.5rem 0.75rem; border-top: 1px solid var(--border); }
    .items-tags-dropdown__footer .btn { padding: 0.4rem 0.75rem; font-size: 0.875rem; }
</style>

@php
    $hasItemTags = !empty($selectedTags);
    $hasItemFilters = $hasItemTags || $shop || $use_type;
    $itemTagLabels = collect($itemFilterTagOptions ?? [])->keyBy('value');
@endphp
@if($hasItemFilters)
    <div class="card" style="border-color: var(--accent); background: var(--accent-muted); margin-bottom: 1rem;">
        <p style="margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem;">
            @if($hasItemTags)
                <span>Filtering by tags:</span>
                @foreach($selectedTags as $t)
                    @php $label = $itemTagLabels->get($t)['label'] ?? $t; @endphp
                    <span class="items-tag-chip">
                        <strong>{{ $label }}</strong>
                        <a href="{{ route('items.index', array_merge(request()->only(['q', 'shop', 'use_type', 'sort', 'dir']), ['tags' => array_values(array_diff($selectedTags, [$t]))])) }}" class="items-tag-chip-remove" aria-label="Remove tag {{ $label }}">×</a>
                    </span>
                @endforeach
            @endif
            @if($shop)@if($hasItemTags)<span style="margin-left: 0.25rem;">·</span>@endif Shop: <strong>{{ $shop }}</strong>@endif
            @if($use_type)@if($hasItemTags || $shop)<span style="margin-left: 0.25rem;">·</span>@endif Type: <strong>{{ ucfirst($use_type) }}</strong>@endif
            <a href="{{ route('items.index', ['sort' => $sort, 'dir' => $dir]) }}" style="margin-left: 0.5rem; color: var(--accent); font-weight: 500;">Clear filters</a>
        </p>
    </div>
@endif

<form method="get" action="{{ route('items.index') }}" class="items-toolbar">
    <input type="search" name="q" value="{{ old('q', $search) }}" placeholder="Search items..." aria-label="Search">
    @foreach($selectedTags ?? [] as $t)<input type="hidden" name="tags[]" value="{{ $t }}">@endforeach
    @if($shop ?? null)<input type="hidden" name="shop" value="{{ $shop }}">@endif
    @if($use_type ?? null)<input type="hidden" name="use_type" value="{{ $use_type }}">@endif
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <button type="submit">Search</button>
</form>

<form method="get" action="{{ route('items.index') }}" class="items-toolbar items-toolbar--filters" id="items-filters-form">
    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    @if($shop ?? null)<input type="hidden" name="shop" value="{{ $shop }}">@endif
    @if($use_type ?? null)<input type="hidden" name="use_type" value="{{ $use_type }}">@endif
    <div class="items-toolbar__field">
        <label for="shop">Shop</label>
        <select name="shop" id="shop" onchange="this.form.submit()">
            <option value="">All shops</option>
            @foreach($shops as $s)
                <option value="{{ $s }}" {{ $shop === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div class="items-toolbar__field">
        <label for="use_type">Type</label>
        <select name="use_type" id="use_type" onchange="this.form.submit()">
            <option value="">All types</option>
            <option value="item" {{ $use_type === 'item' ? 'selected' : '' }}>Item</option>
            <option value="travel" {{ $use_type === 'travel' ? 'selected' : '' }}>Travel</option>
            <option value="other" {{ $use_type === 'other' ? 'selected' : '' }}>Other</option>
        </select>
    </div>
    <div class="items-toolbar__field items-tags-dropdown-wrap">
        <label id="items-tags-label">Tags</label>
        <div class="items-tags-dropdown" id="items-tags-dropdown">
            <button type="button" class="items-tags-dropdown__trigger" id="items-tags-trigger" aria-haspopup="true" aria-expanded="false" aria-labelledby="items-tags-label">
                Tags
                @if(!empty($selectedTags))
                    <span class="items-tags-dropdown__badge" id="items-tags-badge">{{ count($selectedTags) }}</span>
                @endif
            </button>
            <div class="items-tags-dropdown__panel" id="items-tags-panel" role="dialog" aria-label="Filter by tags" hidden>
                <div class="items-tags-dropdown__search">
                    <input type="text" id="items-tags-search" placeholder="Search tags…" autocomplete="off" aria-label="Search tags">
                </div>
                <div class="items-tags-dropdown__list" role="group" aria-label="Tag list">
                    @foreach($itemFilterTagOptions ?? [] as $opt)
                        <label class="items-tag-option" data-tag-text="{{ strtolower($opt['label']) }}">
                            <input type="checkbox" name="tags[]" value="{{ $opt['value'] }}" {{ in_array($opt['value'], $selectedTags ?? [], true) ? 'checked' : '' }}>
                            <span>{{ $opt['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="items-tags-dropdown__footer">
                    <button type="submit" class="btn">Apply</button>
                </div>
            </div>
        </div>
    </div>
    <div class="items-toolbar__field">
        <label for="sort">Sort by</label>
        <select name="sort" id="sort" onchange="this.form.submit()">
            <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name</option>
            <option value="first_appeared" {{ $sort === 'first_appeared' ? 'selected' : '' }}>First appeared</option>
            <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>Date added</option>
            <option value="restock_price" {{ $sort === 'restock_price' ? 'selected' : '' }}>Price</option>
        </select>
    </div>
    <div class="items-toolbar__field">
        <label for="dir">Direction</label>
        <select name="dir" id="dir" onchange="this.form.submit()">
            <option value="asc" {{ $dir === 'asc' ? 'selected' : '' }}>Ascending</option>
            <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Descending</option>
        </select>
    </div>
</form>

@if($items->isEmpty())
    <div class="card">
        <p>No items yet.</p>
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

<script>
(function() {
    var trigger = document.getElementById('items-tags-trigger');
    var panel = document.getElementById('items-tags-panel');
    var searchInput = document.getElementById('items-tags-search');
    if (!trigger || !panel) return;

    function open() {
        panel.classList.add('is-open');
        panel.removeAttribute('hidden');
        trigger.setAttribute('aria-expanded', 'true');
        if (searchInput) searchInput.value = '';
        filterTagOptions('');
        if (searchInput) searchInput.focus();
    }
    function close() {
        panel.classList.remove('is-open');
        panel.setAttribute('hidden', '');
        trigger.setAttribute('aria-expanded', 'false');
    }
    function filterTagOptions(q) {
        var lower = (q || '').toLowerCase().trim();
        panel.querySelectorAll('.items-tag-option').forEach(function(label) {
            var text = (label.getAttribute('data-tag-text') || '').toLowerCase();
            label.classList.toggle('tag-search-hidden', lower !== '' && text.indexOf(lower) === -1);
        });
    }

    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        if (panel.classList.contains('is-open')) close(); else open();
    });
    document.addEventListener('click', function() {
        if (panel.classList.contains('is-open')) close();
    });
    panel.addEventListener('click', function(e) { e.stopPropagation(); });
    panel.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { close(); trigger.focus(); }
    });
    if (searchInput) {
        searchInput.addEventListener('input', function() { filterTagOptions(this.value); });
    }
})();
</script>
@endsection
