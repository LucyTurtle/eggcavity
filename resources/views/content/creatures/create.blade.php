@extends('layouts.app')

@section('title', 'Add creature')

@section('content')
<div class="page-header">
    <h1>Add creature</h1>
    <p class="lead"><a href="{{ route('content.index') }}">← Back to manage content</a></p>
</div>

<style>
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .form-group input, .form-group textarea { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; }
    .form-group textarea { min-height: 6rem; resize: vertical; }
    .form-group textarea.textarea { min-height: 4rem; }
    .form-group small { display: block; font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }
    .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .btn-submit:hover { background: var(--accent-hover); }
</style>

<div class="card">
    <form method="post" action="{{ route('content.creature.store') }}">
        @csrf
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required>
            @error('title')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" placeholder="Leave blank to generate from title">
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
            <label for="published_at">Published at</label>
            <input type="date" name="published_at" id="published_at" value="{{ old('published_at') }}">
            @error('published_at')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="availability">Availability</label>
            <input type="text" name="availability" id="availability" value="{{ old('availability') }}">
            @error('availability')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="dates">Dates</label>
            <input type="text" name="dates" id="dates" value="{{ old('dates') }}">
            @error('dates')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="weight">Weight</label>
            <input type="text" name="weight" id="weight" value="{{ old('weight') }}">
            @error('weight')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="length">Length</label>
            <input type="text" name="length" id="length" value="{{ old('length') }}">
            @error('length')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="obtained_from">Obtained from</label>
            <input type="text" name="obtained_from" id="obtained_from" value="{{ old('obtained_from') }}">
            @error('obtained_from')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="gender_profile">Gender profile</label>
            <input type="text" name="gender_profile" id="gender_profile" value="{{ old('gender_profile') }}">
            @error('gender_profile')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="habitat">Habitat</label>
            <input type="text" name="habitat" id="habitat" value="{{ old('habitat') }}">
            @error('habitat')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="about_eggs">About eggs</label>
            <textarea name="about_eggs" id="about_eggs" class="form-group textarea">{{ old('about_eggs') }}</textarea>
            @error('about_eggs')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="about_creature">About creature</label>
            <textarea name="about_creature" id="about_creature" class="form-group textarea">{{ old('about_creature') }}</textarea>
            @error('about_creature')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="entry_written_by">Entry written by</label>
            <input type="text" name="entry_written_by" id="entry_written_by" value="{{ old('entry_written_by') }}" placeholder="Username">
            @error('entry_written_by')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="design_concept_user">Design concept user</label>
            <input type="text" name="design_concept_user" id="design_concept_user" value="{{ old('design_concept_user') }}" placeholder="Username">
            @error('design_concept_user')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="cdwc_entry_by">CDWC winning entry by</label>
            <input type="text" name="cdwc_entry_by" id="cdwc_entry_by" value="{{ old('cdwc_entry_by') }}" placeholder="Username">
            @error('cdwc_entry_by')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="tags">Tags (comma-separated)</label>
            <input type="text" name="tags" id="tags" value="{{ old('tags') }}" placeholder="tag1, tag2, tag3">
            @error('tags')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <button type="submit" class="btn-submit">Add creature</button>
    </form>
</div>
@endsection
