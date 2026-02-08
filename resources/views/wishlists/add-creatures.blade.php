@extends('layouts.app')

@section('title', 'Add creatures to wishlist')

@section('content')
<div class="page-header">
    <h1>Add creatures to wishlist</h1>
    <p class="lead"><a href="{{ route('wishlists.index') }}">← Wishlists</a></p>
</div>

<style>
    .add-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1.25rem; }
    @media (max-width: 1200px) { .add-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (max-width: 900px) { .add-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 600px) { .add-grid { grid-template-columns: repeat(2, 1fr); } }
    .add-card {
        background: var(--surface);
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow);
        padding: 0.5rem;
        display: flex;
        flex-direction: column;
    }
    .add-card a { text-decoration: none; color: inherit; display: block; }
    .add-card .thumb {
        aspect-ratio: 1;
        background: var(--bg);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .add-card .thumb img { width: 100%; height: 100%; object-fit: contain; }
    .add-card .thumb .fallback { font-size: 2rem; color: var(--text-secondary); }
    .add-card .label { padding: 0.5rem 0 0 0; font-weight: 600; font-size: 0.9375rem; color: var(--text); text-align: center; }
    .add-card .add-fields { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border); }
    .add-card .add-fields label { display: block; font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.2rem; }
    .add-card .add-fields input, .add-card .add-fields select { width: 100%; padding: 0.35rem 0.5rem; font-size: 0.8125rem; border: 1px solid var(--border); border-radius: var(--radius-sm); }
    .add-card .add-fields .field { margin-bottom: 0.35rem; }
    .add-card .add-fields .field:last-child { margin-bottom: 0; }
    .add-submit { margin-top: 1.5rem; }
    .add-submit button { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .add-submit button:hover { background: var(--accent-hover); }
    .add-pagination { margin-top: 1.5rem; margin-bottom: 2rem; }
    .add-pagination nav { display: flex; justify-content: center; flex-wrap: wrap; gap: 0.25rem; }
    .add-pagination ul.pagination { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 0.25rem; justify-content: center; }
    .add-pagination ul.pagination li { display: inline-block; }
    .add-pagination ul.pagination a, .add-pagination ul.pagination span {
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-sm);
        font-size: 0.875rem;
        text-decoration: none;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        display: inline-block;
    }
    .add-pagination ul.pagination a:hover { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
    .add-pagination ul.pagination span { background: var(--bg); color: var(--text-secondary); }
    .add-pagination ul.pagination li.disabled span { cursor: not-allowed; }
    .add-pagination ul.pagination li.active span { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
    .archive-toolbar { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
    .archive-toolbar input[type="search"] { padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; min-width: 12rem; }
    .archive-toolbar button { padding: 0.5rem 1rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; }
    .archive-toolbar button:hover { background: var(--accent-hover); }
    @media (max-width: 768px) {
        .archive-toolbar button { min-height: 44px; padding: 0.75rem 1rem; }
        .archive-toolbar input[type="search"] { min-height: 44px; font-size: 16px; }
        .add-card .label { font-size: 1rem; }
        .add-card .add-fields label, .add-card .add-fields input, .add-card .add-fields select { font-size: 0.875rem; }
        .add-card .add-fields input, .add-card .add-fields select { min-height: 44px; padding: 0.5rem 0.75rem; }
        .add-submit button { min-height: 44px; padding: 0.75rem 1.25rem; }
        .add-pagination ul.pagination a, .add-pagination ul.pagination span { min-height: 44px; display: inline-flex; align-items: center; padding: 0.75rem 1rem; }
    }
</style>

<form method="get" action="{{ route('wishlists.add.creatures') }}" class="archive-toolbar" style="margin-bottom: 1.25rem;">
    <input type="search" name="q" value="{{ old('q', $search ?? '') }}" placeholder="Search creatures..." aria-label="Search">
    <button type="submit">Search</button>
</form>

@if($creatures->isEmpty())
    <div class="card">
        <p>{{ ($search ?? '') !== '' ? 'No creatures match your search.' : 'No creatures in the archive yet.' }}</p>
        @if(($search ?? '') !== '')
            <p><a href="{{ route('wishlists.add.creatures') }}">Clear search</a></p>
        @else
            <p><a href="{{ route('archive.index') }}">View archive</a></p>
        @endif
    </div>
@else
    <form method="post" action="{{ route('wishlist.creatures.store') }}" id="add-creatures-form">
        @csrf
        <input type="hidden" name="redirect" id="wishlist-redirect" value="">
        <div class="add-grid">
            @foreach($creatures as $c)
                <article class="add-card">
                    <a href="{{ route('archive.show', $c->slug) }}">
                        <div class="thumb">
                            @if($c->thumbnail_url)
                                <img src="{{ $c->thumbnail_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                <span class="fallback" style="display: none;" aria-hidden="true">?</span>
                            @else
                                <span class="fallback" aria-hidden="true">?</span>
                            @endif
                        </div>
                        <div class="label">{{ $c->title }}</div>
                    </a>
                    <div class="add-fields">
                        <div class="field">
                            <label for="creatures-{{ $c->id }}-amount">Qty</label>
                            <input type="number" name="creatures[{{ $c->id }}][amount]" id="creatures-{{ $c->id }}-amount" value="0" min="0" max="9999" aria-label="Amount for {{ $c->title }}">
                        </div>
                        <div class="field">
                            <label for="creatures-{{ $c->id }}-stage">Stage</label>
                            <select name="creatures[{{ $c->id }}][stage_number]" id="creatures-{{ $c->id }}-stage">
                                <option value="" selected>No preference (stage 1)</option>
                                @for($s = 1; $s <= 20; $s++)
                                    <option value="{{ $s }}">Stage {{ $s }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="field">
                            <label for="creatures-{{ $c->id }}-gender">Gender</label>
                            <select name="creatures[{{ $c->id }}][gender]" id="creatures-{{ $c->id }}-gender">
                                <option value="no_preference" selected>No preference</option>
                                <option value="">—</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="non-binary">Non-binary</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="creatures-{{ $c->id }}-notes">Notes</label>
                            <input type="text" name="creatures[{{ $c->id }}][notes]" id="creatures-{{ $c->id }}-notes" placeholder="Optional" maxlength="500">
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="add-submit">
            <button type="submit">Add selected to creature wishlist</button>
        </div>
        @if($creatures->hasPages())
            <div class="add-pagination">
                {{ $creatures->links('pagination::custom') }}
            </div>
        @endif
    </form>
@endif
@endsection
