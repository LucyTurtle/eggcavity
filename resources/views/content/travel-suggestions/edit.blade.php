@extends('layouts.app')

@section('title', 'Edit travel suggestion')

@section('content')
<div class="page-header">
    <h1>Edit travel suggestion</h1>
    <p class="lead"><a href="{{ route('content.travel-suggestions.index') }}">← Back to travel suggestions</a></p>
</div>

<style>
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .form-group select, .form-group textarea, .form-group input { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; }
    .form-group textarea { min-height: 6rem; resize: vertical; }
    .form-group small { display: block; font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }
    .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .btn-submit:hover { background: var(--accent-hover); }
</style>

<div class="card">
    <form method="post" action="{{ route('content.travel-suggestions.update', $suggestion) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="archive_item_id">Creature *</label>
            <select name="archive_item_id" id="archive_item_id" required>
                <option value="">— Choose creature —</option>
                @foreach($creatures as $creature)
                    <option value="{{ $creature->id }}" {{ $creature->id === $suggestion->archive_item_id ? 'selected' : '' }}>{{ $creature->title }}</option>
                @endforeach
            </select>
            @error('archive_item_id')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="item_id">Travel *</label>
            <select name="item_id" id="item_id" required>
                <option value="">— Choose travel —</option>
                @foreach($travels as $travel)
                    <option value="{{ $travel->id }}" {{ $travel->id === $suggestion->item_id ? 'selected' : '' }}>{{ $travel->name }}</option>
                @endforeach
            </select>
            @error('item_id')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" placeholder="Optional notes about this suggestion">{{ old('notes', $suggestion->notes) }}</textarea>
            @error('notes')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="sort_order">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $suggestion->sort_order) }}" min="0">
            <small>Lower numbers appear first when multiple suggestions exist for the same creature.</small>
            @error('sort_order')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <button type="submit" class="btn-submit">Update suggestion</button>
    </form>
</div>
@endsection
