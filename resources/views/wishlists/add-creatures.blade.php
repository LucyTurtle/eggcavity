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
    .add-pagination { margin-top: 1.5rem; }
    .add-pagination nav { display: flex; justify-content: center; flex-wrap: wrap; gap: 0.25rem; }
</style>

@if($creatures->isEmpty())
    <div class="card">
        <p>No creatures in the archive yet.</p>
        <p><a href="{{ route('archive.index') }}">View archive</a></p>
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
                            <label for="creatures-{{ $c->id }}-gender">Gender</label>
                            <select name="creatures[{{ $c->id }}][gender]" id="creatures-{{ $c->id }}-gender">
                                <option value="">—</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="non-binary">Non-binary</option>
                                <option value="no_preference">No preference</option>
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
    <script>
        document.querySelectorAll('#add-creatures-form .add-pagination a[href]').forEach(function(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var href = this.getAttribute('href');
                if (href && href !== '#') {
                    document.getElementById('wishlist-redirect').value = href;
                    document.getElementById('add-creatures-form').submit();
                }
            });
        });
    </script>
@endif
@endsection
