@extends('layouts.app')

@section('title', 'Add item')

@section('content')
<div class="page-header">
    <h1>Add item</h1>
    <p class="lead"><a href="{{ route('content.index') }}">← Back to manage content</a></p>
</div>

<style>
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .form-group input, .form-group textarea { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; }
    .form-group textarea { min-height: 6rem; resize: vertical; }
    .form-group input[type="checkbox"] { width: auto; max-width: none; }
    .form-group small { display: block; font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }
    .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .btn-submit:hover { background: var(--accent-hover); }
</style>

<div class="card">
    <form method="post" action="{{ route('content.item.store') }}">
        @csrf
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required>
            @error('name')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" placeholder="Leave blank to generate from name">
            @error('slug')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description">{{ old('description') }}</textarea>
            @error('description')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="image_url">Image URL</label>
            <input type="url" name="image_url" id="image_url" value="{{ old('image_url') }}">
            @error('image_url')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="source_url">Source URL (EggCave.com)</label>
            <input type="url" name="source_url" id="source_url" value="{{ old('source_url') }}">
            @error('source_url')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="rarity">Rarity</label>
            <input type="text" name="rarity" id="rarity" value="{{ old('rarity') }}" placeholder="e.g. r83 (uncommon)">
            @error('rarity')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="use">Use</label>
            <input type="text" name="use" id="use" value="{{ old('use') }}" placeholder="e.g. Food Item, travel">
            @error('use')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="associated_shop">Associated shop</label>
            <input type="text" name="associated_shop" id="associated_shop" value="{{ old('associated_shop') }}">
            @error('associated_shop')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="restock_price">Restock price</label>
            <input type="text" name="restock_price" id="restock_price" value="{{ old('restock_price') }}" placeholder="e.g. 1,502 EC">
            @error('restock_price')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_retired" value="1" {{ old('is_retired') ? 'checked' : '' }}> Retired</label>
            @error('is_retired')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_cavecash" value="1" {{ old('is_cavecash') ? 'checked' : '' }}> CaveCash</label>
            @error('is_cavecash')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="first_appeared">First appeared</label>
            <input type="date" name="first_appeared" id="first_appeared" value="{{ old('first_appeared') }}">
            @error('first_appeared')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="sort_order">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0">
            @error('sort_order')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <button type="submit" class="btn-submit">Add item</button>
    </form>
</div>
@endsection
