@extends('layouts.app')

@section('title', 'Add travels to wishlist')

@section('content')
<div class="page-header">
    <h1>Add travels to wishlist</h1>
    <p class="lead"><a href="{{ route('wishlists.index') }}">← Wishlists</a></p>
</div>
<p style="font-size: 0.9375rem; color: var(--text-secondary); margin: -0.5rem 0 1rem 0;">Trinket travels. Enter a number under each card to add that many to your wishlist.</p>

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
    .add-card .add-fields input { width: 100%; padding: 0.35rem 0.5rem; font-size: 0.8125rem; border: 1px solid var(--border); border-radius: var(--radius-sm); }
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
</style>

@if($travels->isEmpty())
    <div class="card">
        <p>No travel items yet.</p>
        <p><a href="{{ route('items.index', ['use_type' => 'travel']) }}">View items (Travel)</a></p>
    </div>
@else
    <form method="post" action="{{ route('wishlist.travels.store') }}" id="add-travels-form">
        @csrf
        <input type="hidden" name="redirect" id="wishlist-travels-redirect" value="">
        <div class="add-grid">
            @foreach($travels as $t)
                <article class="add-card">
                    <a href="{{ route('items.show', $t->slug) }}">
                        <div class="thumb">
                            @if($t->image_url)
                                <img src="{{ $t->image_url }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                <span class="fallback" style="display: none;" aria-hidden="true">?</span>
                            @else
                                <span class="fallback" aria-hidden="true">?</span>
                            @endif
                        </div>
                        <div class="label">{{ $t->name }}</div>
                    </a>
                    <div class="add-fields">
                        <div class="field">
                            <label for="travels-{{ $t->id }}-amount">Qty</label>
                            <input type="number" name="travels[{{ $t->id }}][amount]" id="travels-{{ $t->id }}-amount" value="0" min="0" max="9999" aria-label="Amount for {{ $t->name }}">
                        </div>
                        <div class="field">
                            <label for="travels-{{ $t->id }}-notes">Notes</label>
                            <input type="text" name="travels[{{ $t->id }}][notes]" id="travels-{{ $t->id }}-notes" placeholder="Optional" maxlength="500">
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="add-submit">
            <button type="submit">Add selected to travel wishlist</button>
        </div>
        @if($travels->hasPages())
            <div class="add-pagination">
                {{ $travels->links('pagination::custom') }}
            </div>
        @endif
    </form>
    <script>
        document.querySelectorAll('#add-travels-form .add-pagination a[href]').forEach(function(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var href = this.getAttribute('href');
                if (href && href !== '#') {
                    document.getElementById('wishlist-travels-redirect').value = href;
                    document.getElementById('add-travels-form').submit();
                }
            });
        });
    </script>
@endif
@endsection
