@extends('layouts.app')

@section('title', $item->name)

@section('content')
<div class="page-header">
    <nav style="font-size: 0.9375rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <a href="{{ route('items.index') }}">← Back to items</a>
        <span style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            @if($item->source_url)
                <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer">Open on EggCave.com →</a>
            @endif
        </span>
    </nav>
    <h1>{{ $item->name }}</h1>
</div>

<style>
    .item-detail .main-img {
        max-width: 100%;
        max-width: 300px;
        border: 1px solid var(--border);
        background: var(--bg);
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
        background: var(--bg);
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
</style>

<div class="item-detail">
    @if($item->image_url)
        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="main-img" loading="lazy" referrerpolicy="no-referrer">
    @endif

    @if($item->description)
        <div class="card">
            <p style="font-size: 1.0625rem; margin: 0;">{{ $item->description }}</p>
        </div>
    @endif

    @if($item->rarity || $item->use || $item->associated_shop || $item->restock_price || $item->first_appeared || $item->is_retired)
        <div class="card">
            <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem;">Details</h3>
            <dl class="stats-grid">
                @if($item->rarity)
                    <div class="stat-item">
                        <dt>Rarity</dt>
                        <dd class="rarity" style="color: {{ $item->rarity_color }};">{{ $item->rarity }}</dd>
                    </div>
                @endif
                @if($item->use)
                    <div class="stat-item">
                        <dt>Use</dt>
                        <dd>{{ $item->use }}</dd>
                    </div>
                @endif
                @if($item->associated_shop)
                    <div class="stat-item">
                        <dt>Associated shop</dt>
                        <dd>{{ $item->associated_shop }}</dd>
                    </div>
                @endif
                @if($item->restock_price)
                    <div class="stat-item">
                        <dt>Restock price</dt>
                        <dd>{{ $item->restock_price }}</dd>
                    </div>
                @endif
                @if($item->first_appeared)
                    <div class="stat-item">
                        <dt>First appeared</dt>
                        <dd>{{ $item->first_appeared->format('F j, Y') }}</dd>
                    </div>
                @endif
                <div class="stat-item">
                    <dt>Status</dt>
                    <dd>{{ $item->is_retired ? 'Retired' : 'Not retired' }}</dd>
                </div>
            </dl>
        </div>
    @endif

    @if($associatedCreature)
        <h3 style="font-size: 0.9375rem; margin: 1rem 0 0.25rem 0;">Recommended for</h3>
        <div class="recommended-for-grid">
            <article class="creature-card">
                <a href="{{ route('archive.show', $associatedCreature->slug) }}">
                    <div class="thumb">
                        @if($associatedCreature->image_url)
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

</div>
@endsection
