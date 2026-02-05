@extends('layouts.app')

@section('title', 'Wishlists')

@section('content')
<div class="page-header">
    <h1>Wishlists</h1>
    <p class="lead">Your creature, item, and travel wishlists.</p>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">Share your wishlists</h3>
    <p style="margin: 0 0 0.75rem 0; font-size: 0.9375rem;">Your wishlists are public at these links (read-only). Anyone with the link can view.</p>
    <div class="share-links" style="display: flex; flex-direction: column; gap: 0.75rem;">
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
            <strong style="min-width: 7rem;">All wishlists:</strong>
            <input type="text" readonly value="{{ $shareUrl }}" id="share-url-input" style="flex: 1; min-width: 12rem; padding: 0.4rem 0.6rem; font-size: 0.875rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg);">
            <button type="button" class="btn-copy" data-copy-target="share-url-input" style="padding: 0.4rem 0.75rem; font-size: 0.875rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); cursor: pointer; font-family: inherit;">Copy link</button>
        </div>
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
            <strong style="min-width: 7rem;">Creatures:</strong>
            <input type="text" readonly value="{{ $shareCreaturesUrl }}" id="share-creatures-input" style="flex: 1; min-width: 12rem; padding: 0.4rem 0.6rem; font-size: 0.875rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg);">
            <button type="button" class="btn-copy" data-copy-target="share-creatures-input" style="padding: 0.4rem 0.75rem; font-size: 0.875rem; background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; font-family: inherit;">Copy link</button>
        </div>
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
            <strong style="min-width: 7rem;">Items:</strong>
            <input type="text" readonly value="{{ $shareItemsUrl }}" id="share-items-input" style="flex: 1; min-width: 12rem; padding: 0.4rem 0.6rem; font-size: 0.875rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg);">
            <button type="button" class="btn-copy" data-copy-target="share-items-input" style="padding: 0.4rem 0.75rem; font-size: 0.875rem; background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; font-family: inherit;">Copy link</button>
        </div>
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
            <strong style="min-width: 7rem;">Travels:</strong>
            <input type="text" readonly value="{{ $shareTravelsUrl }}" id="share-travels-input" style="flex: 1; min-width: 12rem; padding: 0.4rem 0.6rem; font-size: 0.875rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg);">
            <button type="button" class="btn-copy" data-copy-target="share-travels-input" style="padding: 0.4rem 0.75rem; font-size: 0.875rem; background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; font-family: inherit;">Copy link</button>
        </div>
    </div>
</div>

<style>
.wishlist-overview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); gap: 1rem; }
.wishlist-overview-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem;
    text-decoration: none;
    color: inherit;
    display: block;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.wishlist-overview-card:hover { border-color: var(--accent); box-shadow: var(--shadow-lg); }
.wishlist-overview-card h2 { font-size: 1.125rem; margin: 0 0 0.25rem 0; font-weight: 600; }
.wishlist-overview-card .count { font-size: 0.875rem; color: var(--text-secondary); }
.wishlist-overview-card .action { font-size: 0.875rem; margin-top: 0.75rem; font-weight: 500; color: var(--accent); }
</style>

<h2 style="font-size: 1.25rem; margin: 0 0 1rem 0;">Your wishlists</h2>
<div class="wishlist-overview-grid">
    <a href="{{ route('wishlists.creatures') }}" class="wishlist-overview-card">
        <h2>Creature wishlist</h2>
        <p class="count">{{ $creatureWishlists->count() }} {{ Str::plural('creature', $creatureWishlists->count()) }}</p>
        <span class="action">View &amp; edit →</span>
    </a>
    <a href="{{ route('wishlists.items') }}" class="wishlist-overview-card">
        <h2>Item wishlist</h2>
        <p class="count">{{ $itemWishlists->count() }} {{ Str::plural('item', $itemWishlists->count()) }}</p>
        <span class="action">View &amp; edit →</span>
    </a>
    <a href="{{ route('wishlists.travels') }}" class="wishlist-overview-card">
        <h2>Travel wishlist</h2>
        <p class="count">{{ $travelWishlists->count() }} {{ Str::plural('travel', $travelWishlists->count()) }}</p>
        <span class="action">View &amp; edit →</span>
    </a>
</div>

<p style="margin-top: 1.5rem; font-size: 0.9375rem;">
    <a href="{{ route('wishlists.add.creatures') }}">Add creatures</a> ·
    <a href="{{ route('wishlists.add.items') }}">Add items</a> ·
    <a href="{{ route('wishlists.add.travels') }}">Add travels</a>
</p>

<script>
document.querySelectorAll('.btn-copy').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-copy-target');
        var input = document.getElementById(id);
        if (input) {
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(function() {
                var t = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(function() { btn.textContent = t; }, 1500);
            });
        }
    });
});
</script>
@endsection
