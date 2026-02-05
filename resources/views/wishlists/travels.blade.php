@extends('layouts.app')

@section('title', 'Travel wishlist')

@section('content')
<div class="page-header">
    <h1>Travel wishlist</h1>
    <p class="lead"><a href="{{ route('wishlists.index') }}">← Wishlists</a></p>
</div>

@include('wishlists._wishlist-section-styles')

<div class="wishlist-section">
    <h2>Travels <a href="{{ route('wishlists.add.travels') }}" class="btn-add">Add to wishlist</a></h2>
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

<p style="margin-top: 1rem; font-size: 0.875rem; color: var(--text-secondary);">Share: <input type="text" readonly value="{{ $shareTravelsUrl }}" id="share-url" style="width: 18rem; max-width: 100%; padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg);"> <button type="button" class="btn-copy-wishlist" data-copy="share-url" style="padding: 0.3rem 0.5rem; font-size: 0.8125rem;">Copy</button></p>

<script>
document.querySelectorAll('.toggle-edit').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var form = btn.closest('.wishlist-card').querySelector('.edit-form');
        form.classList.toggle('show');
    });
});
document.querySelectorAll('.btn-copy-wishlist').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-copy');
        var el = document.getElementById(id);
        if (el) { el.select(); navigator.clipboard.writeText(el.value).then(function() { btn.textContent = 'Copied!'; setTimeout(function() { btn.textContent = 'Copy'; }, 1500); }); }
    });
});
</script>
@endsection
