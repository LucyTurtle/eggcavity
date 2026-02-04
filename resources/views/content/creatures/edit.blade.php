@extends('layouts.app')

@section('title', 'Edit creature')

@section('content')
<div class="page-header">
    <h1>Edit creature</h1>
    <p class="lead"><a href="{{ route('content.index') }}">← Back to manage content</a> · <a href="{{ route('archive.show', $creature->slug) }}">View on site</a></p>
</div>

<style>
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .form-group input, .form-group textarea { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; }
    .form-group textarea { min-height: 6rem; resize: vertical; }
    .form-group small { display: block; font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }
    .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .btn-submit:hover { background: var(--accent-hover); }
</style>

<div class="card">
    <form method="post" action="{{ route('content.creature.update', $creature) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" name="title" id="title" value="{{ old('title', $creature->title) }}" required>
            @error('title')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="slug">Slug *</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $creature->slug) }}" required>
            @error('slug')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description">{{ old('description', $creature->description) }}</textarea>
            @error('description')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="image_url">Image URL</label>
            <input type="url" name="image_url" id="image_url" value="{{ old('image_url', $creature->image_url) }}">
            @error('image_url')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="source_url">Source URL (EggCave.com)</label>
            <input type="url" name="source_url" id="source_url" value="{{ old('source_url', $creature->source_url) }}">
            @error('source_url')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="published_at">Published at</label>
            <input type="date" name="published_at" id="published_at" value="{{ old('published_at', $creature->published_at?->format('Y-m-d')) }}">
            @error('published_at')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="sort_order">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $creature->sort_order) }}" min="0">
            @error('sort_order')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <button type="submit" class="btn-submit">Save changes</button>
    </form>
</div>
@endsection
