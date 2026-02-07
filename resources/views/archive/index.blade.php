@extends('layouts.app')

@section('title', 'Archive')

@section('content')
<div class="page-header">
    <h1>Archive</h1>
    <p class="lead">
        @if(!empty($selectedTags))
            <span style="font-size: 0.9375rem;">Showing tagged: <strong>{{ implode(', ', $selectedTags) }}</strong></span>
        @endif
        <span style="font-size: 0.9375rem;">{{ number_format($items->total()) }} {{ Str::plural('creature', $items->total()) }}</span>
    </p>
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
    .archive-toolbar--filters { display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: 1rem 1.25rem; align-items: end; margin-top: -0.5rem; }
    .archive-toolbar--filters .archive-toolbar__field { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
    .archive-toolbar--filters .archive-toolbar__field label { font-size: 0.9375rem; color: var(--text-secondary); margin: 0; }
    .archive-toolbar--filters .archive-toolbar__field select,
    .archive-toolbar--filters .archive-toolbar__field input[type="text"],
    .archive-toolbar--filters .archive-toolbar__field input[type="month"] { width: 100%; min-width: 0; }
    .archive-toolbar input[type="search"] {
        padding: 0.5rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 0.9375rem;
        min-width: 12rem;
    }
    .archive-toolbar select,
    .archive-toolbar input[type="month"] {
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
    .archive-tag-chip { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.2rem 0.5rem; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.875rem; }
    .archive-tag-chip-remove { color: var(--text-secondary); text-decoration: none; font-size: 1.1rem; line-height: 1; padding: 0 0.15rem; border-radius: 2px; }
    .archive-tag-chip-remove:hover { color: var(--accent); background: var(--bg); }
    .archive-tags-dropdown { position: relative; display: inline-block; }
    .archive-tags-dropdown__trigger {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.5rem 0.75rem; font-size: 0.9375rem;
        background: var(--surface); color: var(--text);
        border: 1px solid var(--border); border-radius: var(--radius-sm);
        cursor: pointer; font-family: inherit;
    }
    .archive-tags-dropdown__trigger:hover { border-color: var(--accent); color: var(--accent); }
    .archive-tags-dropdown__trigger[aria-expanded="true"] { border-color: var(--accent); background: var(--accent-muted); color: var(--accent); }
    .archive-tags-dropdown__badge { font-size: 0.75rem; padding: 0.1rem 0.4rem; border-radius: 999px; background: var(--accent); color: white; }
    .archive-tags-dropdown__panel {
        display: none; position: absolute; top: 100%; left: 0; margin-top: 0.25rem;
        min-width: 14rem; max-width: 20rem; max-height:  min(70vh, 24rem);
        background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
        box-shadow: var(--shadow-lg); z-index: 50;
        flex-direction: column;
    }
    .archive-tags-dropdown__panel.is-open { display: flex; }
    .archive-tags-dropdown__search { padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--border); }
    .archive-tags-dropdown__search input { width: 100%; padding: 0.4rem 0.5rem; font-size: 0.875rem; border: 1px solid var(--border); border-radius: var(--radius-sm); box-sizing: border-box; }
    .archive-tags-dropdown__list { overflow-y: auto; padding: 0.5rem; flex: 1; min-height: 0; }
    .archive-tags-dropdown__list label { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.5rem; font-size: 0.875rem; cursor: pointer; border-radius: var(--radius-sm); }
    .archive-tags-dropdown__list label:hover { background: var(--accent-muted); }
    .archive-tags-dropdown__list label.tag-search-hidden { display: none; }
    .archive-tags-dropdown__footer { padding: 0.5rem 0.75rem; border-top: 1px solid var(--border); }
    .archive-tags-dropdown__footer .btn { padding: 0.4rem 0.75rem; font-size: 0.875rem; }
</style>

@php
    $hasTags = !empty($selectedTags);
    $hasEvolutions = isset($evolutions_filter) && $evolutions_filter >= 1 && $evolutions_filter <= 3;
    $hasHabitat = !empty($habitat_filter);
    $hasEvolvesByStat = !empty($evolves_by_stat_filter);
    $hasFilters = $hasTags || ($gender_profile ?? '') !== '' || ($availability_filter ?? '') !== '' || ($dates_filter ?? '') !== '' || $hasEvolutions || $hasHabitat || $hasEvolvesByStat;
@endphp
@if($hasFilters)
    <div class="card archive-active-filters" style="border-color: var(--accent); background: var(--accent-muted); margin-bottom: 1rem;">
        <p style="margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem;">
            @if($hasTags)
                <span>Filtering by tags:</span>
                @foreach($selectedTags as $t)
                    <span class="archive-tag-chip">
                        <strong>{{ $t }}</strong>
                        <a href="{{ route('archive.index', array_merge(request()->only(['q', 'sort', 'dir', 'gender_profile', 'availability', 'dates_filter', 'evolutions', 'habitat', 'evolves_by_stat']), ['tags' => array_values(array_diff($selectedTags, [$t]))])) }}" class="archive-tag-chip-remove" aria-label="Remove tag {{ $t }}">×</a>
                    </span>
                @endforeach
            @endif
            @if($gender_profile ?? '')@if($hasTags)<span style="margin-left: 0.25rem;">·</span>@endif Gender: <strong>{{ $gender_profile }}</strong>@endif
            @if($availability_filter ?? '')@if($hasTags || ($gender_profile ?? ''))<span style="margin-left: 0.25rem;">·</span>@endif Availability: <strong>{{ $availability_filter }}</strong>@endif
            @if($dates_filter ?? '')@if($hasTags || ($gender_profile ?? '') || ($availability_filter ?? ''))<span style="margin-left: 0.25rem;">·</span>@endif Dates: <strong>{{ $dates_filter }}</strong>@endif
            @if($hasEvolutions)@if($hasTags || ($gender_profile ?? '') || ($availability_filter ?? '') || ($dates_filter ?? ''))<span style="margin-left: 0.25rem;">·</span>@endif Evolutions: <strong>{{ $evolutions_filter === 1 ? 'One (1)' : ($evolutions_filter === 2 ? 'Two (2)' : 'Three (3)') }}</strong>@endif
            @if($hasHabitat)@if($hasTags || ($gender_profile ?? '') || ($availability_filter ?? '') || ($dates_filter ?? '') || $hasEvolutions)<span style="margin-left: 0.25rem;">·</span>@endif Habitat: <strong>{{ $habitat_filter }}</strong>@endif
            @if($hasEvolvesByStat)@if($hasTags || ($gender_profile ?? '') || ($availability_filter ?? '') || ($dates_filter ?? '') || $hasEvolutions || $hasHabitat)<span style="margin-left: 0.25rem;">·</span>@endif Evolves by stat: <strong>{{ ucfirst($evolves_by_stat_filter) }}</strong>@endif
            <a href="{{ route('archive.index', ['sort' => $sort, 'dir' => $dir]) }}" style="margin-left: 0.5rem; color: var(--accent); font-weight: 500;">Clear filters</a>
        </p>
    </div>
@endif

<form method="get" action="{{ route('archive.index') }}" class="archive-toolbar">
    <input type="search" name="q" value="{{ old('q', $search) }}" placeholder="Search by name..." aria-label="Search by name">
    @foreach($selectedTags ?? [] as $t)<input type="hidden" name="tags[]" value="{{ $t }}">@endforeach
    @if($gender_profile ?? '')<input type="hidden" name="gender_profile" value="{{ $gender_profile }}">@endif
    @if($availability_filter ?? '')<input type="hidden" name="availability" value="{{ $availability_filter }}">@endif
    @if($dates_filter ?? '')<input type="hidden" name="dates_filter" value="{{ $dates_filter }}">@endif
    @if($evolutions_filter ?? '')<input type="hidden" name="evolutions" value="{{ $evolutions_filter }}">@endif
    @if($habitat_filter ?? '')<input type="hidden" name="habitat" value="{{ $habitat_filter }}">@endif
    @if($evolves_by_stat_filter ?? '')<input type="hidden" name="evolves_by_stat" value="{{ $evolves_by_stat_filter }}">@endif
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <button type="submit">Search</button>
</form>

<form method="get" action="{{ route('archive.index') }}" class="archive-toolbar archive-toolbar--filters" id="archive-filters-form">
    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    @if($gender_profile ?? '')<input type="hidden" name="gender_profile" value="{{ $gender_profile }}">@endif
    @if($availability_filter ?? '')<input type="hidden" name="availability" value="{{ $availability_filter }}">@endif
    @if($dates_filter ?? '')<input type="hidden" name="dates_filter" value="{{ $dates_filter }}">@endif
    @if($evolutions_filter ?? '')<input type="hidden" name="evolutions" value="{{ $evolutions_filter }}">@endif
    @if($habitat_filter ?? '')<input type="hidden" name="habitat" value="{{ $habitat_filter }}">@endif
    @if($evolves_by_stat_filter ?? '')<input type="hidden" name="evolves_by_stat" value="{{ $evolves_by_stat_filter }}">@endif
    <div class="archive-toolbar__field archive-tags-dropdown-wrap">
        <label id="archive-tags-label">Tags</label>
        <div class="archive-tags-dropdown" id="archive-tags-dropdown">
            <button type="button" class="archive-tags-dropdown__trigger" id="archive-tags-trigger" aria-haspopup="true" aria-expanded="false" aria-labelledby="archive-tags-label">
                Tags
                @if(!empty($selectedTags))
                    <span class="archive-tags-dropdown__badge" id="archive-tags-badge">{{ count($selectedTags) }}</span>
                @endif
            </button>
            <div class="archive-tags-dropdown__panel" id="archive-tags-panel" role="dialog" aria-label="Filter by tags" hidden>
                <div class="archive-tags-dropdown__search">
                    <input type="text" id="archive-tags-search" placeholder="Search tags…" autocomplete="off" aria-label="Search tags">
                </div>
                <div class="archive-tags-dropdown__list" role="group" aria-label="Tag list">
                    @if(!empty($tags))
                        @foreach($tags as $t)
                            <label class="archive-tag-option" data-tag-text="{{ strtolower($t) }}">
                                <input type="checkbox" name="tags[]" value="{{ $t }}" {{ in_array($t, $selectedTags ?? [], true) ? 'checked' : '' }}>
                                <span>{{ $t }}</span>
                            </label>
                        @endforeach
                    @else
                        <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary); padding: 0.5rem;">No tags in archive yet.</p>
                    @endif
                </div>
                <div class="archive-tags-dropdown__footer">
                    <button type="submit" class="btn">Apply</button>
                </div>
            </div>
        </div>
    </div>
    <div class="archive-toolbar__field">
        <label for="sort">Sort by</label>
        <select name="sort" id="sort" onchange="this.form.submit()">
            <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>Name</option>
            <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>Date added</option>
        </select>
    </div>
    <div class="archive-toolbar__field">
        <label for="dir">Direction</label>
        <select name="dir" id="dir" onchange="this.form.submit()">
            <option value="asc" {{ $dir === 'asc' ? 'selected' : '' }}>Ascending</option>
            <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Descending</option>
        </select>
    </div>
    <div class="archive-toolbar__field">
        <label for="gender_profile">Gender profile</label>
        <select name="gender_profile" id="gender_profile" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($genderProfiles ?? [] as $gp)
                <option value="{{ $gp }}" {{ ($gender_profile ?? '') === $gp ? 'selected' : '' }}>{{ $gp }}</option>
            @endforeach
        </select>
    </div>
    <div class="archive-toolbar__field">
        <label for="availability">Availability</label>
        <select name="availability" id="availability" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($availabilities ?? [] as $av)
                <option value="{{ $av }}" {{ ($availability_filter ?? '') === $av ? 'selected' : '' }}>{{ $av }}</option>
            @endforeach
        </select>
    </div>
    <div class="archive-toolbar__field">
        <label for="dates_filter">Dates (year/month)</label>
        <input type="month" name="dates_filter" id="dates_filter" value="{{ $dates_filter ?? '' }}" onchange="this.form.submit()">
    </div>
    <div class="archive-toolbar__field">
        <label for="evolutions">Number of evolutions</label>
        <select name="evolutions" id="evolutions" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="1" {{ ($evolutions_filter ?? '') === 1 ? 'selected' : '' }}>One (1)</option>
            <option value="2" {{ ($evolutions_filter ?? '') === 2 ? 'selected' : '' }}>Two (2)</option>
            <option value="3" {{ ($evolutions_filter ?? '') === 3 ? 'selected' : '' }}>Three (3)</option>
        </select>
    </div>
    <div class="archive-toolbar__field">
        <label for="habitat">Habitat</label>
        <select name="habitat" id="habitat" onchange="this.form.submit()">
            <option value="">Please Select</option>
            @foreach($habitats ?? [] as $h)
                <option value="{{ $h }}" {{ ($habitat_filter ?? '') === $h ? 'selected' : '' }}>{{ $h }}</option>
            @endforeach
        </select>
    </div>
    <div class="archive-toolbar__field">
        <label for="evolves_by_stat">Evolves by stat</label>
        <select name="evolves_by_stat" id="evolves_by_stat" onchange="this.form.submit()">
            <option value="">Please Select</option>
            <option value="views" {{ ($evolves_by_stat_filter ?? '') === 'views' ? 'selected' : '' }}>Views</option>
            <option value="clicks" {{ ($evolves_by_stat_filter ?? '') === 'clicks' ? 'selected' : '' }}>Clicks</option>
            <option value="feeds" {{ ($evolves_by_stat_filter ?? '') === 'feeds' ? 'selected' : '' }}>Feeds</option>
        </select>
    </div>
</form>

@if($items->isEmpty())
    <div class="card">
        <p>No archive items yet.</p>
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

<script>
(function() {
    var trigger = document.getElementById('archive-tags-trigger');
    var panel = document.getElementById('archive-tags-panel');
    var searchInput = document.getElementById('archive-tags-search');
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
        panel.querySelectorAll('.archive-tag-option').forEach(function(label) {
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
