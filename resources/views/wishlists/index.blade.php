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
    <div style="margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
        <form method="post" action="{{ route('wishlists.share.regenerate') }}" style="margin: 0; display: inline;">
            @csrf
            <button type="submit" style="padding: 0.4rem 0.75rem; font-size: 0.875rem; background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; font-family: inherit;">Regenerate link</button>
        </form>
        <form method="post" action="{{ route('wishlists.share.disable') }}" style="margin: 0; display: inline;" onsubmit="return confirm('Disable the share link? The current link will stop working.');">
            @csrf
            <button type="submit" style="padding: 0.4rem 0.75rem; font-size: 0.875rem; color: #dc2626; background: none; border: 1px solid #dc2626; border-radius: var(--radius-sm); cursor: pointer; font-family: inherit;">Disable link</button>
        </form>
    </div>
</div>

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

<style>
    .wishlist-section { margin-bottom: 2.5rem; }
    .wishlist-section h2 { font-size: 1.25rem; font-weight: 600; margin: 0 0 1rem 0; color: var(--text); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .wishlist-section h2 .btn-add { padding: 0.35rem 0.75rem; font-size: 0.875rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; text-decoration: none; display: inline-block; }
    .wishlist-section h2 .btn-add:hover { background: var(--accent-hover); }
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
        transition: box-shadow 0.15s, border-color 0.15s;
        padding: 0.5rem;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .wishlist-card:hover { border-color: var(--accent); box-shadow: var(--shadow-lg); }
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
    .wishlist-card .actions { margin-top: 0.5rem; display: flex; gap: 0.35rem; flex-wrap: wrap; }
    .wishlist-card .actions button, .wishlist-card .actions a.btn-sm {
        padding: 0.25rem 0.5rem; font-size: 0.8125rem; border-radius: var(--radius-sm); cursor: pointer; font-family: inherit; text-decoration: none; border: 1px solid var(--border); background: var(--surface); color: var(--text);
    }
    .wishlist-card .actions button:hover, .wishlist-card .actions a.btn-sm:hover { border-color: var(--accent); color: var(--accent); }
    .wishlist-card .actions button.btn-danger { border-color: #dc2626; color: #dc2626; }
    .wishlist-card .actions button.btn-danger:hover { background: #fef2f2; }
    .wishlist-card .edit-form { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border); display: none; }
    .wishlist-card .edit-form.show { display: block; }
    .wishlist-card .edit-form .form-row { margin-bottom: 0.5rem; }
    .wishlist-card .edit-form label { display: block; font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.15rem; }
    .wishlist-card .edit-form input, .wishlist-card .edit-form select, .wishlist-card .edit-form textarea { width: 100%; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid var(--border); border-radius: var(--radius-sm); }
    .wishlist-card .edit-form textarea { min-height: 2.5rem; resize: vertical; }
    .wishlist-empty { color: var(--text-secondary); font-size: 0.9375rem; }
</style>

<div class="wishlist-section">
    <h2>Creature wishlist <a href="{{ route('wishlists.add.creatures') }}" class="btn-add">Add to wishlist</a></h2>
    @if($creatureWishlists->isNotEmpty())
        <div class="wishlist-grid">
            @foreach($creatureWishlists as $entry)
                @php($creature = $entry->archiveItem)
                @if($creature)
                    <article class="wishlist-card" data-entry-id="{{ $entry->id }}">
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
                        <div class="actions">
                            <button type="button" class="btn-sm toggle-edit">Edit</button>
                            <form method="post" action="{{ route('wishlist.creature.remove', $entry) }}" style="display: inline;" onsubmit="return confirm('Remove from wishlist?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger">Remove</button>
                            </form>
                        </div>
                        <div class="edit-form" id="edit-creature-{{ $entry->id }}">
                            <form method="post" action="{{ route('wishlist.creature.update', $entry) }}">
                                @csrf
                                @method('PUT')
                                <div class="form-row">
                                    <label>Amount</label>
                                    <input type="number" name="amount" value="{{ $entry->amount }}" min="1" max="9999">
                                </div>
                                <div class="form-row">
                                    <label>Gender</label>
                                    <select name="gender">
                                        <option value="">— No preference —</option>
                                        <option value="male" {{ $entry->gender === 'male' ? 'selected' : '' }}>Male</option>
                                        <option value="female" {{ $entry->gender === 'female' ? 'selected' : '' }}>Female</option>
                                        <option value="non-binary" {{ $entry->gender === 'non-binary' ? 'selected' : '' }}>Non-binary</option>
                                        <option value="no_preference" {{ $entry->gender === 'no_preference' ? 'selected' : '' }}>No preference</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="2">{{ $entry->notes }}</textarea>
                                </div>
                                <button type="submit" class="btn-sm" style="margin-top: 0.35rem;">Save</button>
                            </form>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    @else
        <p class="wishlist-empty">No creatures on your wishlist. <a href="{{ route('wishlists.add.creatures') }}">Add some</a>.</p>
    @endif
</div>

<div class="wishlist-section">
    <h2>Item wishlist <a href="{{ route('wishlists.add.items') }}" class="btn-add">Add to wishlist</a></h2>
    <p style="font-size: 0.8125rem; color: var(--text-secondary); margin: -0.5rem 0 0.75rem 0;">Non-travel items only.</p>
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
                        <div class="actions">
                            <button type="button" class="btn-sm toggle-edit">Edit</button>
                            <form method="post" action="{{ route('wishlist.item.remove', $entry) }}" style="display: inline;" onsubmit="return confirm('Remove from wishlist?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger">Remove</button>
                            </form>
                        </div>
                        <div class="edit-form" id="edit-item-{{ $entry->id }}">
                            <form method="post" action="{{ route('wishlist.item.update', $entry) }}">
                                @csrf
                                @method('PUT')
                                <div class="form-row">
                                    <label>Amount</label>
                                    <input type="number" name="amount" value="{{ $entry->amount }}" min="1" max="9999">
                                </div>
                                <div class="form-row">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="2">{{ $entry->notes }}</textarea>
                                </div>
                                <button type="submit" class="btn-sm" style="margin-top: 0.35rem;">Save</button>
                            </form>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    @else
        <p class="wishlist-empty">No items on your wishlist. <a href="{{ route('wishlists.add.items') }}">Add some</a> (non-travel items).</p>
    @endif
</div>

<div class="wishlist-section">
    <h2>Travel wishlist <a href="{{ route('wishlists.add.travels') }}" class="btn-add">Add to wishlist</a></h2>
    <p style="font-size: 0.8125rem; color: var(--text-secondary); margin: -0.5rem 0 0.75rem 0;">Trinket travels and travel items.</p>
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
                        <div class="actions">
                            <button type="button" class="btn-sm toggle-edit">Edit</button>
                            <form method="post" action="{{ route('wishlist.travel.remove', $entry) }}" style="display: inline;" onsubmit="return confirm('Remove from wishlist?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger">Remove</button>
                            </form>
                        </div>
                        <div class="edit-form" id="edit-travel-{{ $entry->id }}">
                            <form method="post" action="{{ route('wishlist.travel.update', $entry) }}">
                                @csrf
                                @method('PUT')
                                <div class="form-row">
                                    <label>Amount</label>
                                    <input type="number" name="amount" value="{{ $entry->amount }}" min="1" max="9999">
                                </div>
                                <div class="form-row">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="2">{{ $entry->notes }}</textarea>
                                </div>
                                <button type="submit" class="btn-sm" style="margin-top: 0.35rem;">Save</button>
                            </form>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    @else
        <p class="wishlist-empty">No travels on your wishlist. <a href="{{ route('wishlists.add.travels') }}">Add some</a>.</p>
    @endif
</div>

<script>
document.querySelectorAll('.wishlist-card .toggle-edit').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var card = btn.closest('.wishlist-card');
        var form = card.querySelector('.edit-form');
        form.classList.toggle('show');
    });
});
</script>
@endsection
