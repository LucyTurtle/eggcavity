@extends('layouts.app')

@section('title', $item->name)

@section('content')
<style>
    .item-detail .main-img {
        max-width: 100%;
        max-width: 300px;
        border: 1px solid var(--border);
        background: var(--surface);
        display: block;
        margin: 0 auto 1.5rem;
    }
    .item-detail .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem 1.5rem;
        margin: 1rem 0;
    }
    .item-detail .stats-grid .stat-item {
        display: flex;
        flex-direction: column;
    }
    .item-detail .stats-grid dt {
        font-size: 0.8125rem;
        color: var(--text-secondary);
        margin: 0 0 0.5rem 0;
        font-weight: 500;
    }
    .item-detail .stats-grid dd {
        margin: 0;
        font-size: 0.9375rem;
    }
    .item-detail .rarity {
        font-weight: 600;
    }
    .rarity-badge {
        font-weight: 600;
    }
    .item-detail .recommended-for-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 1rem;
        margin: 1rem 0;
    }
    .item-detail .creature-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: box-shadow 0.15s, border-color 0.15s;
        padding: 0.5rem;
    }
    .item-detail .creature-card:hover {
        border-color: var(--accent);
        box-shadow: var(--shadow-lg);
    }
    .item-detail .creature-card a {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .item-detail .creature-card .thumb {
        aspect-ratio: 1;
        background: var(--surface);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .item-detail .creature-card .thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .item-detail .creature-card .thumb .fallback {
        font-size: 2rem;
        color: var(--text-secondary);
    }
    .item-detail .creature-card .label {
        padding: 0.75rem 1rem 0 1rem;
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--text);
        text-align: center;
    }
    .item-detail .form-group { margin-bottom: 1rem; }
    .item-detail .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .item-detail .form-group input, .item-detail .form-group textarea { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; box-sizing: border-box; }
    .item-detail .form-group textarea { min-height: 4rem; resize: vertical; }
    .item-detail .form-group input[type="checkbox"] { width: auto; max-width: none; }
    .item-detail .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .item-detail .btn-cancel { padding: 0.5rem 1.25rem; background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; text-decoration: none; display: inline-block; margin-left: 0.5rem; }
    .item-detail .btn-cancel:hover { border-color: var(--accent); color: var(--accent); }
    .item-detail .edit-mode { display: none; }
    .item-detail.edit-mode .view-mode { display: none !important; }
    .item-detail.edit-mode .edit-mode { display: block !important; }
    .item-detail .edit-mode input, .item-detail .edit-mode textarea { max-width: 100%; }
</style>

@if(session('success'))
    <div class="card" style="background: var(--accent-muted); border-color: var(--accent); margin-bottom: 1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="card" style="border-color: #dc2626; background: #fef2f2; margin-bottom: 1rem;">{{ session('error') }}</div>
@endif
@if($errors->isNotEmpty())
    <div class="card" style="border-color: #dc2626; background: #fef2f2; margin-bottom: 1rem;">
        <p style="margin: 0 0 0.5rem 0; font-weight: 600; color: #dc2626;">Please fix the errors below.</p>
        <ul style="margin: 0; padding-left: 1.25rem; color: #dc2626;">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

@auth
@if($item->isTravel())
<form id="wishlist-item-form" method="post" action="{{ route('wishlist.travel.store') }}" style="display: none;">
    @csrf
    <input type="hidden" name="item_id" value="{{ $item->id }}">
    <input type="hidden" name="amount" value="1">
    <input type="hidden" name="redirect" value="{{ url()->current() }}">
</form>
@else
<form id="wishlist-item-form" method="post" action="{{ route('wishlist.item.store') }}" style="display: none;">
    @csrf
    <input type="hidden" name="item_id" value="{{ $item->id }}">
    <input type="hidden" name="amount" value="1">
    <input type="hidden" name="redirect" value="{{ url()->current() }}">
</form>
@endif
@endauth

@if($canEdit ?? false)
<form method="post" action="{{ route('content.item.update', $item) }}" id="item-edit-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="slug" value="{{ old('slug', $item->slug) }}">
    <div class="page-header">
        <nav style="font-size: 0.9375rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <a href="{{ route('items.index') }}">← Back to items</a>
            <span style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <button type="button" id="item-edit-toggle" style="padding: 0.35rem 0.75rem; font-size: 0.9375rem; background: var(--accent-muted); color: var(--accent); border: 1px solid var(--accent); border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Edit</button>
                @auth
                <button type="submit" form="wishlist-item-form" style="padding: 0.35rem 0.75rem; font-size: 0.9375rem; background: var(--accent-muted); color: var(--accent); border: 1px solid var(--accent); border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Add to wishlist</button>
                @endauth
                @if($item->source_url)
                    <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer">Open on EggCave.com →</a>
                @endif
            </span>
        </nav>
        <h1 style="margin: 0 0 0.5rem 0;">
            <span class="view-mode">{{ $item->name }}</span>
            <input class="edit-mode" name="name" id="item-name-input" value="{{ old('name', $item->name) }}" required style="display: none; width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1.75rem; font-weight: 700; font-family: inherit; box-sizing: border-box;">
        </h1>
    </div>
    <div class="item-detail @if($errors->isNotEmpty()) edit-mode @endif" id="item-detail">
@else
<div class="page-header">
    <nav style="font-size: 0.9375rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <a href="{{ route('items.index') }}">← Back to items</a>
        <span style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            @auth
            <button type="submit" form="wishlist-item-form" style="padding: 0.35rem 0.75rem; font-size: 0.9375rem; background: var(--accent-muted); color: var(--accent); border: 1px solid var(--accent); border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Add to wishlist</button>
            @endauth
            @if($item->source_url)
                <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer">Open on EggCave.com →</a>
            @endif
        </span>
    </nav>
    <h1 style="margin: 0 0 0.5rem 0;">{{ $item->name }}</h1>
</div>
<div class="item-detail">
@endif
    {{-- Main image + image URL in edit mode --}}
    <div class="view-mode">
        @if($item->image_url)
            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="main-img" loading="lazy" referrerpolicy="no-referrer">
        @endif
    </div>
    @if($canEdit ?? false)
    <div class="edit-mode" style="display: none;">
        <div class="form-group"><label>Image URL</label><input type="url" name="image_url" value="{{ old('image_url', $item->image_url) }}" placeholder="https://..."></div>
    </div>
    @endif

    @if($item->description || ($canEdit ?? false))
        <div class="card">
            <div class="view-mode">
                @if($item->description)
                    <p style="font-size: 1.0625rem; margin: 0;">{{ $item->description }}</p>
                @endif
            </div>
            @if($canEdit ?? false)
            <div class="edit-mode" style="display: none;"><div class="form-group" style="margin: 0;"><label>Description</label><textarea name="description" rows="4">{{ old('description', $item->description) }}</textarea></div></div>
            @endif
        </div>
    @endif

    @if($onWishlist ?? false)
        <div class="item-tags" style="margin-bottom: 1rem;">
            <span style="font-size: 0.8125rem; color: var(--text-secondary); margin-right: 0.5rem;">Tags:</span>
            <a href="{{ route('items.index', ['on_wishlist' => 1]) }}" style="display: inline-block; font-size: 0.8125rem; padding: 0.25rem 0.5rem; margin-right: 0.5rem; margin-bottom: 0.5rem; border-radius: var(--radius-sm); background: var(--accent-muted); color: var(--accent); text-decoration: none;">On wishlist</a>
        </div>
    @endif

    @if($item->rarity || $item->use || $item->associated_shop || $item->restock_price || $item->first_appeared || $item->is_retired || ($canEdit ?? false))
        <div class="card">
            <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem;">Details</h3>
            <dl class="stats-grid view-mode">
                @if($item->rarity)
                    <div class="stat-item">
                        <dt>Rarity</dt>
                        <dd class="rarity" style="color: {{ $item->rarity_color ?? 'inherit' }};">{{ $item->rarity }}</dd>
                    </div>
                @endif
                @if($item->use)
                    <div class="stat-item"><dt>Use</dt><dd>{{ $item->use }}</dd></div>
                @endif
                @if($item->associated_shop)
                    <div class="stat-item"><dt>Associated shop</dt><dd>{{ $item->associated_shop }}</dd></div>
                @endif
                @if($item->restock_price)
                    <div class="stat-item"><dt>Restock price</dt><dd>{{ $item->restock_price }}</dd></div>
                @endif
                @if($item->first_appeared)
                    <div class="stat-item"><dt>First appeared</dt><dd>{{ $item->first_appeared->format('F j, Y') }}</dd></div>
                @endif
                <div class="stat-item"><dt>Status</dt><dd>{{ $item->is_retired ? 'Retired' : 'Not retired' }}</dd></div>
            </dl>
            @if($canEdit ?? false)
            <div class="edit-mode" style="display: none;">
                <div class="stats-grid">
                    <div class="form-group"><label>Rarity</label><input type="text" name="rarity" value="{{ old('rarity', $item->rarity) }}"></div>
                    <div class="form-group"><label>Use</label><input type="text" name="use" value="{{ old('use', $item->use) }}"></div>
                    <div class="form-group"><label>Associated shop</label><input type="text" name="associated_shop" value="{{ old('associated_shop', $item->associated_shop) }}"></div>
                    <div class="form-group"><label>Restock price</label><input type="text" name="restock_price" value="{{ old('restock_price', $item->restock_price) }}"></div>
                    <div class="form-group"><label>First appeared</label><input type="date" name="first_appeared" value="{{ old('first_appeared', $item->first_appeared?->format('Y-m-d')) }}"></div>
                    <div class="form-group"><label><input type="checkbox" name="is_retired" value="1" {{ old('is_retired', $item->is_retired) ? 'checked' : '' }}> Retired</label></div>
                    <div class="form-group"><label><input type="checkbox" name="is_cavecash" value="1" {{ old('is_cavecash', $item->is_cavecash) ? 'checked' : '' }}> CaveCash</label></div>
                </div>
                <div class="form-group"><label>Source URL (EggCave.com)</label><input type="url" name="source_url" value="{{ old('source_url', $item->source_url) }}"></div>
            </div>
            @endif
        </div>
    @endif

    @if($associatedCreature)
        <h3 style="font-size: 0.9375rem; margin: 1rem 0 0.25rem 0;">Recommended for</h3>
        <div class="recommended-for-grid">
            <article class="creature-card">
                <a href="{{ route('archive.show', $associatedCreature->slug) }}">
                    <div class="thumb">
                        @if($associatedCreature->image_url ?? null)
                            <img src="{{ $associatedCreature->image_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                            <span class="fallback" style="display: none;" aria-hidden="true">?</span>
                        @else
                            <span class="fallback" aria-hidden="true">?</span>
                        @endif
                    </div>
                    <div class="label">{{ $associatedCreature->title }}</div>
                </a>
            </article>
        </div>
    @endif

    @if($canEdit ?? false)
    <div class="edit-mode" id="item-edit-actions" style="margin-top: 1.5rem; @if(!$errors->isNotEmpty()) display: none; @endif">
        <button type="submit" form="item-edit-form" class="btn-submit">Save changes</button>
        <button type="button" id="item-edit-cancel" class="btn-cancel">Cancel</button>
    </div>
    @endif

</div>
@if($canEdit ?? false)
</form>
<script>
(function() {
    var toggle = document.getElementById('item-edit-toggle');
    var detail = document.getElementById('item-detail');
    var actions = document.getElementById('item-edit-actions');
    if (!toggle || !detail) return;
    function updateToggleLabel() {
        var inEdit = detail.classList.contains('edit-mode');
        if (actions) actions.style.display = inEdit ? 'block' : 'none';
        toggle.textContent = inEdit ? 'Cancel' : 'Edit';
    }
    toggle.addEventListener('click', function() {
        detail.classList.toggle('edit-mode');
        updateToggleLabel();
    });
    var cancelBtn = document.getElementById('item-edit-cancel');
    if (cancelBtn) cancelBtn.addEventListener('click', function() {
        detail.classList.remove('edit-mode');
        updateToggleLabel();
    });
    updateToggleLabel();
})();
</script>
@endif
@endsection
