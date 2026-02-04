@extends('layouts.app')

@section('title', $owner->name . "'s wishlists")

@section('content')
<div class="page-header">
    <h1>{{ $owner->name }}'s wishlists</h1>
    <p style="font-size: 0.9375rem; margin-top: 0.5rem;">
        <a href="{{ route('wishlists.shared.creatures', ['user' => $owner->id]) }}">Creature wishlist</a>
        · <a href="{{ route('wishlists.shared.items', ['user' => $owner->id]) }}">Item wishlist</a>
        · <a href="{{ route('wishlists.shared.travels', ['user' => $owner->id]) }}">Travel wishlist</a>
    </p>
</div>

<style>
    .wishlist-section { margin-bottom: 2.5rem; }
    .wishlist-section h2 { font-size: 1.25rem; font-weight: 600; margin: 0 0 1rem 0; color: var(--text); }
    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.25rem;
    }
    @media (max-width: 1200px) { .wishlist-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (max-width: 900px) { .wishlist-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 600px) { .wishlist-grid { grid-template-columns: repeat(2, 1fr); } }
    .wishlist-card {
        background: var(--surface);
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow);
        padding: 0.5rem;
        display: flex;
        flex-direction: column;
    }
    .wishlist-card a { text-decoration: none; color: inherit; }
    .wishlist-card .thumb {
        aspect-ratio: 1;
        background: var(--bg);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .wishlist-card .thumb img { width: 100%; height: 100%; object-fit: contain; }
    .wishlist-card .thumb .fallback { font-size: 2rem; color: var(--text-secondary); }
    .wishlist-card .label { padding: 0.5rem 0 0 0; font-weight: 600; font-size: 0.9375rem; color: var(--text); text-align: center; }
    .wishlist-card .meta { font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; text-align: center; }
    .wishlist-card .notes { font-size: 0.8125rem; color: var(--text); margin-top: 0.35rem; padding: 0.35rem; background: var(--bg); border-radius: var(--radius-sm); max-height: 3em; overflow: hidden; text-overflow: ellipsis; }
    .wishlist-card .notes:empty { display: none; }
    .wishlist-empty { color: var(--text-secondary); font-size: 0.9375rem; }
</style>

<div class="wishlist-section">
    <h2>Creature wishlist</h2>
    @if($creatureWishlists->isNotEmpty())
        <div class="wishlist-grid">
            @foreach($creatureWishlists as $entry)
                @php($creature = $entry->archiveItem)
                @if($creature)
                    <article class="wishlist-card">
                        <a href="{{ route('archive.show', $creature->slug) }}">
                            <div class="thumb">
                                @if($creature->thumbnail_url)
                                    <img src="{{ $creature->thumbnail_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                    <span class="fallback" style="display: none;" aria-hidden="true">?</span>
                                @else
                                    <span class="fallback" aria-hidden="true">?</span>
                                @endif
                            </div>
                            <div class="label">{{ $creature->title }}</div>
                        </a>
                        <div class="meta">Qty: {{ $entry->amount }}@if($entry->gender) · {{ ucfirst(str_replace('_', ' ', $entry->gender)) }}@endif</div>
                        @if($entry->notes)<div class="notes" title="{{ $entry->notes }}">{{ $entry->notes }}</div>@endif
                    </article>
                @endif
            @endforeach
        </div>
    @else
        <p class="wishlist-empty">No creatures on this wishlist.</p>
    @endif
</div>

<div class="wishlist-section">
    <h2>Item wishlist</h2>
    @if($itemWishlists->isNotEmpty())
        <div class="wishlist-grid">
            @foreach($itemWishlists as $entry)
                @php($item = $entry->item)
                @if($item)
                    <article class="wishlist-card">
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
                        <div class="meta">Qty: {{ $entry->amount }}</div>
                        @if($entry->notes)<div class="notes" title="{{ $entry->notes }}">{{ $entry->notes }}</div>@endif
                    </article>
                @endif
            @endforeach
        </div>
    @else
        <p class="wishlist-empty">No items on this wishlist.</p>
    @endif
</div>

<div class="wishlist-section">
    <h2>Travel wishlist</h2>
    @if($travelWishlists->isNotEmpty())
        <div class="wishlist-grid">
            @foreach($travelWishlists as $entry)
                @php($travel = $entry->item)
                @if($travel)
                    <article class="wishlist-card">
                        <a href="{{ route('items.show', $travel->slug) }}">
                            <div class="thumb">
                                @if($travel->image_url)
                                    <img src="{{ $travel->image_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                    <span class="fallback" style="display: none;" aria-hidden="true">?</span>
                                @else
                                    <span class="fallback" aria-hidden="true">?</span>
                                @endif
                            </div>
                            <div class="label">{{ $travel->name }}</div>
                        </a>
                        <div class="meta">Qty: {{ $entry->amount }}</div>
                        @if($entry->notes)<div class="notes" title="{{ $entry->notes }}">{{ $entry->notes }}</div>@endif
                    </article>
                @endif
            @endforeach
        </div>
    @else
        <p class="wishlist-empty">No travels on this wishlist.</p>
    @endif
</div>
@endsection
