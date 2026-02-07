@extends('layouts.app')

@section('title', $item->title)

@section('content')
<style>
    .archive-detail .main-img { max-width: 100%; border: 1px solid var(--border); background: var(--surface); }
    .archive-detail .thumb-wrap { display: inline-block; margin-bottom: 1rem; }
    .archive-detail .stages-grid {
        display: flex;
        align-items: stretch;
        justify-content: space-between;
        gap: 1rem;
        margin: 1.5rem 0;
        width: 100%;
    }
    .archive-detail .stage-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 1rem;
        text-align: center;
        flex: 1 1 0;
        min-width: 0;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        position: relative;
    }
    .archive-detail .stage-card .stage-image-wrapper {
        position: relative;
        width: 100%;
        max-width: 90px;
        max-height: 90px;
        margin: 0 auto 0.5rem;
        aspect-ratio: 1;
        background: var(--surface);
    }
    .archive-detail .stage-card .stage-image-wrapper .trinket-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }
    .archive-detail .stage-card .stage-image-wrapper img:not(.trinket-background) {
        width: 100%;
        max-width: 90px;
        max-height: 90px;
        height: auto;
        aspect-ratio: 1;
        object-fit: contain;
        display: block;
        position: relative;
        z-index: 2;
    }
    .archive-detail .stage-card .stage-requirement {
        min-height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .archive-detail .stage-card .stage-requirement {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--accent);
        margin: 0;
    }
    .archive-detail .stage-card .stage-num {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-bottom: 0.25rem;
    }
    .archive-detail .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem 1.5rem;
        margin: 1rem 0;
    }
    .archive-detail .stats-grid .stat-item {
        display: flex;
        flex-direction: column;
    }
    .archive-detail .stats-grid dt { 
        font-size: 0.8125rem; 
        color: var(--text-secondary); 
        margin: 0 0 0.5rem 0; 
        font-weight: 500; 
    }
    .archive-detail .stats-grid dd { 
        margin: 0; 
        font-size: 0.9375rem; 
    }
    .archive-detail .entry-by { font-size: 0.875rem; color: var(--text-secondary); margin-top: 1rem; }
    .archive-detail .tags-dropdown { margin-top: 1rem; }
    .archive-detail .tags-dropdown summary {
        font-size: 0.9375rem;
        font-weight: 600;
        cursor: pointer;
        list-style: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.5rem;
        border-radius: var(--radius-sm);
        background: var(--accent-muted);
        color: var(--accent);
        border: 1px solid transparent;
        user-select: none;
    }
    .archive-detail .tags-dropdown summary::-webkit-details-marker { display: none; }
    .archive-detail .tags-dropdown summary::after { content: '▼'; font-size: 0.65rem; opacity: 0.8; }
    .archive-detail .tags-dropdown[open] summary::after { transform: scaleY(-1); }
    .archive-detail .tags-dropdown__panel {
        margin-top: 0.5rem;
        padding: 0.75rem;
        max-height: 14rem;
        overflow-y: auto;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--surface);
        box-shadow: var(--shadow);
    }
    .archive-detail .tags-dropdown__panel .tags-list a,
    .archive-detail .tags-list a {
        display: inline-block;
        font-size: 0.8125rem;
        padding: 0.25rem 0.5rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        border-radius: var(--radius-sm);
        background: var(--accent-muted);
        color: var(--accent);
        text-decoration: none;
        transition: background 0.15s, color 0.15s;
    }
    .archive-detail .tags-dropdown__panel .tags-list a:hover,
    .archive-detail .tags-list a:hover { background: var(--accent); color: white; }
    .archive-detail .archive-tag-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .archive-detail .archive-tag-pill {
        display: inline-flex; align-items: center; gap: 0.25rem;
        padding: 0.2rem 0.5rem; font-size: 0.875rem;
        background: var(--accent-muted); color: var(--accent);
        border-radius: var(--radius-sm); border: 1px solid var(--border);
    }
    .archive-detail .archive-tag-pill-remove {
        padding: 0 0.2rem; font-size: 1.1rem; line-height: 1;
        background: none; border: none; color: var(--text-secondary);
        cursor: pointer; border-radius: 2px;
    }
    .archive-detail .archive-tag-pill-remove:hover { color: var(--accent); background: var(--bg); }
    .archive-detail .recommended-travels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 1rem;
        margin: 1rem 0;
    }
    .archive-detail .travel-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: box-shadow 0.15s, border-color 0.15s;
        padding: 0.5rem;
    }
    .archive-detail .travel-card:hover {
        border-color: var(--accent);
        box-shadow: var(--shadow-lg);
    }
    .archive-detail .travel-card a {
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .archive-detail .travel-card .thumb {
        width: 90px;
        height: 90px;
        flex-shrink: 0;
        background: var(--surface);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .archive-detail .travel-card .thumb img {
        width: 90px;
        height: 90px;
        object-fit: contain;
    }
    .archive-detail .travel-card .thumb .fallback {
        font-size: 2rem;
        color: var(--text-secondary);
    }
    .archive-detail .travel-card .label {
        padding: 0.75rem 1rem 0 1rem;
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--text);
        text-align: center;
    }
    .archive-detail .travel-card-selectable {
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .archive-detail .travel-card-selectable:has(input:checked) {
        border-color: var(--accent);
        box-shadow: var(--shadow-lg);
    }
    .archive-html-content { line-height: 1.6; }
    .archive-html-content h1, .archive-html-content h2, .archive-html-content h3, .archive-html-content h4 { font-size: 1rem; font-weight: 600; margin: 1em 0 0.5em 0; color: var(--text); }
    .archive-html-content h1:first-child, .archive-html-content h2:first-child, .archive-html-content h3:first-child, .archive-html-content h4:first-child { margin-top: 0; }
    .archive-html-content p { margin: 0.5em 0; }
    .archive-html-content ul, .archive-html-content ol { margin: 0.5em 0; padding-left: 1.5rem; }
    .archive-html-content li { margin: 0.25em 0; }
    .archive-html-content strong { font-weight: 600; }
    .archive-html-content a { color: var(--accent); font-weight: 500; text-decoration: none; }
    .archive-html-content a:hover { text-decoration: underline; }
    .archive-html-content .box { margin: 0.75em 0; padding: 0.5em 0; }
    .archive-html-content .is-inline-block { display: inline-block; }
    .archive-html-content .is-inline-block a { display: inline-block; font-size: 0.8125rem; padding: 0.25rem 0.5rem; margin: 0 0.5em 0.5em 0; border-radius: var(--radius-sm); background: var(--accent-muted); color: var(--accent); }
    .archive-html-content .is-inline-block a:hover { background: var(--accent); color: white; text-decoration: none; }
    .archive-detail .form-group { margin-bottom: 1rem; }
    .archive-detail .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .archive-detail .form-group input, .archive-detail .form-group textarea { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; box-sizing: border-box; }
    .archive-detail .form-group textarea { min-height: 4rem; resize: vertical; }
    .archive-detail .form-group small { display: block; font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }
    .archive-detail .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .archive-detail .btn-submit:hover { background: var(--accent-hover); }
    .archive-detail .btn-cancel { padding: 0.5rem 1.25rem; background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; text-decoration: none; display: inline-block; margin-left: 0.5rem; }
    .archive-detail .btn-cancel:hover { border-color: var(--accent); color: var(--accent); }
    .archive-detail .edit-mode { display: none; }
    .archive-detail.edit-mode .view-mode { display: none !important; }
    .archive-detail.edit-mode .edit-mode { display: block !important; }
    .archive-detail.edit-mode .stage-edit-fields.edit-mode { display: flex !important; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; }
    .archive-detail .edit-mode input[type="url"],
    .archive-detail .edit-mode input[type="text"],
    .archive-detail .edit-mode input[type="number"],
    .archive-detail .edit-mode input[type="date"],
    .archive-detail .edit-mode textarea { max-width: 100%; }
</style>

@if(session('success'))
    <div class="card" style="background: var(--accent-muted); border-color: var(--accent); margin-bottom: 1rem;">{{ session('success') }}</div>
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
<form id="wishlist-creature-form" method="post" action="{{ route('wishlist.creature.store') }}" style="display: none;">
    @csrf
    <input type="hidden" name="archive_item_id" value="{{ $item->id }}">
    <input type="hidden" name="amount" value="1">
    <input type="hidden" name="redirect" value="{{ url()->current() }}">
</form>
@endauth

@if($canApplyRecommendations)
<form method="post" action="{{ route('content.creature.update', $item) }}" id="archive-edit-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="slug" value="{{ old('slug', $item->slug) }}">
    <div class="page-header">
        <nav style="font-size: 0.9375rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <a href="{{ route('archive.index') }}">← Back to archive</a>
            <span style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <button type="button" id="archive-edit-toggle" style="padding: 0.35rem 0.75rem; font-size: 0.9375rem; background: var(--accent-muted); color: var(--accent); border: 1px solid var(--accent); border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Edit</button>
                @auth
                <button type="submit" form="wishlist-creature-form" style="padding: 0.35rem 0.75rem; font-size: 0.9375rem; background: var(--accent-muted); color: var(--accent); border: 1px solid var(--accent); border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Add to wishlist</button>
                @endauth
                @if($item->source_url)
                    <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer">Open on EggCave.com →</a>
                @endif
            </span>
        </nav>
        <h1 style="margin: 0 0 0.5rem 0;">
            <span class="view-mode">{{ $item->title }}</span>
            <input class="edit-mode" name="title" id="archive-title-input" value="{{ old('title', $item->title) }}" required style="display: none; width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1.75rem; font-weight: 700; font-family: inherit; box-sizing: border-box;">
        </h1>
    </div>
    <div class="archive-detail @if($errors->isNotEmpty()) edit-mode @endif" id="archive-detail">
@else
<div class="page-header">
    <nav style="font-size: 0.9375rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <a href="{{ route('archive.index') }}">← Back to archive</a>
        <span style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            @auth
            <button type="submit" form="wishlist-creature-form" style="padding: 0.35rem 0.75rem; font-size: 0.9375rem; background: var(--accent-muted); color: var(--accent); border: 1px solid var(--accent); border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Add to wishlist</button>
            @endauth
            @if($item->source_url)
                <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer">Open on EggCave.com →</a>
            @endif
        </span>
    </nav>
    <h1 style="margin: 0 0 0.5rem 0;">{{ $item->title }}</h1>
</div>
<div class="archive-detail">
@endif
    {{-- Read view with inline edit fields (shown when Edit is toggled) --}}
    {{-- Description (may contain HTML: headings, paragraphs, strong, em, etc.) --}}
    @if($item->description)
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">Description</h3>
            <div class="archive-html-content">{!! \App\Support\SafeArchiveHtml::sanitize($item->description) !!}</div>
        </div>
    @endif

    {{-- All stages with images and level-up requirements --}}
    @if($item->stages->isNotEmpty())
        <div class="stages-grid">
            @foreach($item->stages as $idx => $stage)
                <div class="stage-card">
                    <div class="stage-image-wrapper">
                        @php
                            $bgTravel = $trinketTravels[$stage->id] ?? null;
                        @endphp
                        @if($bgTravel && $bgTravel->image_url)
                            <img src="{{ $bgTravel->image_url }}" alt="{{ $bgTravel->name }}" class="trinket-background" loading="lazy" referrerpolicy="no-referrer">
                        @endif
                        <img src="{{ $stage->image_url }}" alt="{{ $item->title }} stage {{ $stage->stage_number }}" loading="lazy" referrerpolicy="no-referrer">
                    </div>
                    <p class="stage-requirement view-mode" @if(!$stage->requirement) style="min-height: 1.5rem;" @endif>{{ $stage->requirement }}</p>
                    @if($canApplyRecommendations)
                    <div class="edit-mode stage-edit-fields" style="display: none;">
                        <input type="hidden" name="stages[{{ $idx }}][id]" value="{{ $stage->id }}">
                        <input type="hidden" name="stages[{{ $idx }}][stage_number]" value="{{ $stage->stage_number }}">
                        <div class="form-group" style="margin: 0;"><label>Image URL</label><input type="url" name="stages[{{ $idx }}][image_url]" value="{{ $stage->image_url }}" placeholder="https://..."></div>
                        <div class="form-group" style="margin: 0;"><label>Requirement</label><input type="text" name="stages[{{ $idx }}][requirement]" value="{{ $stage->requirement }}" placeholder="e.g. 250 Views"></div>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Creature stats --}}
    @if($item->availability || $item->dates || $item->weight || $item->length || $item->obtained_from || $item->gender_profile || $item->habitat || $canApplyRecommendations)
        <div class="card">
            <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem;">Details</h3>
            <dl class="stats-grid view-mode">
                @if($item->availability)<div class="stat-item"><dt>Availability</dt><dd>{{ $item->availability }}</dd></div>@endif
                @if($item->dates)<div class="stat-item"><dt>Dates</dt><dd>{{ $item->dates }}</dd></div>@endif
                @if($item->weight)<div class="stat-item"><dt>Weight</dt><dd>{{ $item->weight }}</dd></div>@endif
                @if($item->length)<div class="stat-item"><dt>Length</dt><dd>{{ $item->length }}</dd></div>@endif
                @if($item->obtained_from)<div class="stat-item"><dt>Obtained from</dt><dd>{{ $item->obtained_from }}</dd></div>@endif
                @if($item->gender_profile)<div class="stat-item"><dt>Gender profile</dt><dd>{{ $item->gender_profile }}</dd></div>@endif
                @if($item->habitat)<div class="stat-item"><dt>Habitat</dt><dd>{{ $item->habitat }}</dd></div>@endif
            </dl>
            @if($canApplyRecommendations)
            <div class="edit-mode" style="display: none;">
                <div class="stats-grid">
                    <div class="form-group"><label>Availability</label><input type="text" name="availability" value="{{ old('availability', $item->availability) }}"></div>
                    <div class="form-group"><label>Dates</label><input type="text" name="dates" value="{{ old('dates', $item->dates) }}"></div>
                    <div class="form-group"><label>Weight</label><input type="text" name="weight" value="{{ old('weight', $item->weight) }}"></div>
                    <div class="form-group"><label>Length</label><input type="text" name="length" value="{{ old('length', $item->length) }}"></div>
                    <div class="form-group"><label>Obtained from</label><input type="text" name="obtained_from" value="{{ old('obtained_from', $item->obtained_from) }}"></div>
                    <div class="form-group"><label>Gender profile</label><input type="text" name="gender_profile" value="{{ old('gender_profile', $item->gender_profile) }}"></div>
                    <div class="form-group"><label>Habitat</label><input type="text" name="habitat" value="{{ old('habitat', $item->habitat) }}"></div>
                </div>
                <div class="form-group"><label>Source URL (EggCave.com)</label><input type="url" name="source_url" value="{{ old('source_url', $item->source_url) }}"></div>
                <div class="form-group"><label>Published at</label><input type="date" name="published_at" value="{{ old('published_at', $item->published_at?->format('Y-m-d')) }}"></div>
            </div>
            @endif
        </div>
    @endif

    @if($item->about_eggs || $canApplyRecommendations)
        <div class="card">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">About {{ $item->title }} eggs</h3>
            <div class="view-mode archive-html-content">@if($item->about_eggs){!! \App\Support\SafeArchiveHtml::sanitize($item->about_eggs) !!}@endif</div>
            @if($canApplyRecommendations)<div class="edit-mode" style="display: none;"><div class="form-group" style="margin: 0;"><label>About eggs</label><textarea name="about_eggs" rows="4">{{ old('about_eggs', $item->about_eggs) }}</textarea></div></div>@endif
        </div>
    @endif

    @if($item->about_creature || $canApplyRecommendations)
        <div class="card">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">About the {{ $item->title }} creature</h3>
            <div class="view-mode archive-html-content">@if($item->about_creature){!! \App\Support\SafeArchiveHtml::sanitize($item->about_creature) !!}@endif</div>
            @if($canApplyRecommendations)<div class="edit-mode" style="display: none;"><div class="form-group" style="margin: 0;"><label>About creature</label><textarea name="about_creature" rows="4">{{ old('about_creature', $item->about_creature) }}</textarea></div></div>@endif
        </div>
    @endif

    @if($item->entry_written_by || $item->design_concept_user || $item->cdwc_entry_by)
        @php
            $entryUsers = array_filter(array_map('trim', explode(',', $item->entry_written_by ?? '')));
            $conceptUsers = array_filter(array_map('trim', explode(',', $item->design_concept_user ?? '')));
            $cdwcUsers = array_filter(array_map('trim', explode(',', $item->cdwc_entry_by ?? '')));
        @endphp
        <div class="card" style="margin-top: 1rem;">
            <div class="view-mode">
                @if(!empty($entryUsers))
                    <p style="margin: 0 0 0.5rem 0;">
                        <strong>Entry written by:</strong>
                        @foreach($entryUsers as $index => $username)
                            @if($index > 0)<span aria-hidden="true">, </span>@endif
                            <a href="https://eggcave.com/{{ '@' . $username }}" target="_blank" rel="noopener noreferrer">{{ $username }}</a>
                        @endforeach
                    </p>
                @endif
                @if(!empty($conceptUsers))
                    <p style="margin: 0 0 0.5rem 0;">
                        <strong>Design Concept:</strong>
                        @foreach($conceptUsers as $index => $username)
                            @if($index > 0)<span aria-hidden="true">, </span>@endif
                            <a href="https://eggcave.com/{{ '@' . $username }}" target="_blank" rel="noopener noreferrer">{{ $username }}</a>
                        @endforeach
                    </p>
                @endif
                @if(!empty($cdwcUsers))
                    <p style="margin: 0;">
                        <strong>CDWC winning entry by:</strong>
                        @foreach($cdwcUsers as $index => $username)
                            @if($index > 0)<span aria-hidden="true">, </span>@endif
                            <a href="https://eggcave.com/{{ '@' . $username }}" target="_blank" rel="noopener noreferrer">{{ $username }}</a>
                        @endforeach
                    </p>
                @endif
            </div>
            @if($canApplyRecommendations)
            <div class="edit-mode" style="display: none;">
                <div class="form-group"><label>Entry written by</label><input type="text" name="entry_written_by" value="{{ old('entry_written_by', $item->entry_written_by) }}" placeholder="Username"></div>
                <div class="form-group"><label>Design concept user</label><input type="text" name="design_concept_user" value="{{ old('design_concept_user', $item->design_concept_user) }}" placeholder="Username"></div>
                <div class="form-group"><label>CDWC winning entry by</label><input type="text" name="cdwc_entry_by" value="{{ old('cdwc_entry_by', $item->cdwc_entry_by) }}" placeholder="Username"></div>
            </div>
            @endif
        </div>
    @elseif($canApplyRecommendations)
        <div class="card edit-mode" style="margin-top: 1rem; display: none;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">Credits</h3>
            <p style="font-size: 0.875rem; color: var(--text-secondary); margin: 0 0 0.5rem 0;">Optional: entry written by, design concept, CDWC winning entry.</p>
            <div class="form-group"><label>Entry written by</label><input type="text" name="entry_written_by" value="{{ old('entry_written_by', $item->entry_written_by) }}" placeholder="Username"></div>
            <div class="form-group"><label>Design concept user</label><input type="text" name="design_concept_user" value="{{ old('design_concept_user', $item->design_concept_user) }}" placeholder="Username"></div>
            <div class="form-group"><label>CDWC winning entry by</label><input type="text" name="cdwc_entry_by" value="{{ old('cdwc_entry_by', $item->cdwc_entry_by) }}" placeholder="Username"></div>
        </div>
    @endif

    @if(!empty($recommendedTravels))
        <div id="travel-suggestions-area">
        <h3 style="font-size: 0.9375rem; margin: 1rem 0 0.25rem 0;">Recommended Travels</h3>
        <p style="font-size: 0.875rem; color: var(--text-secondary); margin: 0 0 0.75rem 0;">Apply to preview a travel on the stages (preview only; reload restores trinket or blank).</p>
        @if(!empty($canApplyRecommendations))
            <div class="recommended-travels-grid" style="margin-bottom: 1rem;" id="recommended-travels-grid">
                @foreach($recommendedTravels as $travel)
                    <div class="travel-card" style="display: flex; flex-direction: column; align-items: center;">
                        <a href="{{ route('items.show', $travel->slug) }}" style="flex: 1 1 auto; display: flex; flex-direction: column; align-items: center; text-decoration: none; color: inherit;">
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
                        <button type="button" class="apply-travel-btn" data-action="{{ route('content.archive.apply-recommended-travels', $item->slug) }}" data-travel-id="{{ $travel->id }}" data-csrf="{{ csrf_token() }}" style="margin: 0.5rem 0 0 0; padding: 0.35rem 0.6rem; background: var(--accent-muted); color: var(--accent); border: 1px solid var(--accent); border-radius: var(--radius-sm); font-weight: 500; font-size: 0.8125rem; cursor: pointer;">Apply</button>
                    </div>
                @endforeach
            </div>
            <script>
            (function() {
                document.getElementById('travel-suggestions-area').addEventListener('click', function(e) {
                    var btn = e.target.closest('.apply-travel-btn');
                    if (!btn || btn.disabled) return;
                    var card = btn.closest('.travel-card');
                    var thumbImg = card ? card.querySelector('.thumb img') : null;
                    var imageUrl = thumbImg ? thumbImg.src : '';
                    var imageAlt = thumbImg ? (thumbImg.alt || (card.querySelector('.label') && card.querySelector('.label').textContent) || '') : '';
                    if (!imageUrl) return;
                    var stagesGrid = document.querySelector('.stages-grid');
                    if (stagesGrid) {
                        var rect = stagesGrid.getBoundingClientRect();
                        var inView = rect.bottom > 0 && rect.top < window.innerHeight;
                        if (!inView) stagesGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    var wrappers = document.querySelectorAll('.stage-image-wrapper');
                    wrappers.forEach(function(wrapper) {
                        var bg = wrapper.querySelector('.trinket-background');
                        if (bg) {
                            bg.src = imageUrl;
                            bg.alt = imageAlt;
                            bg.style.display = '';
                        } else {
                            var stageImg = wrapper.querySelector('img:not(.trinket-background)');
                            bg = document.createElement('img');
                            bg.src = imageUrl;
                            bg.alt = imageAlt;
                            bg.className = 'trinket-background';
                            bg.loading = 'lazy';
                            bg.referrerPolicy = 'no-referrer';
                            wrapper.insertBefore(bg, stageImg);
                        }
                    });
                });
            })();
            </script>
        @else
            <div class="recommended-travels-grid">
                @foreach($recommendedTravels as $travel)
                    <article class="travel-card">
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
                    </article>
                @endforeach
            </div>
        @endif
        </div>
    @endif

    @if(($item->tags && count($item->tags) > 0) || $canApplyRecommendations)
        <details class="tags-dropdown">
            <summary>Tags{{ ($item->tags && count($item->tags) > 0) ? ' (' . count($item->tags) . ')' : '' }}</summary>
            <div class="tags-dropdown__panel">
                <div class="view-mode tags-list">
                    @if($item->tags && count($item->tags) > 0)
                        @foreach($item->tags as $tag)
                            <a href="{{ route('archive.index', ['tags' => [$tag]]) }}">{{ $tag }}</a>
                        @endforeach
                    @else
                        <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">No tags</p>
                    @endif
                </div>
                @if($canApplyRecommendations)
                @php
                    $tagsEditValue = old('tags', $item->tags ? (is_array($item->tags) ? implode(', ', $item->tags) : (string) $item->tags) : '');
                    $tagsEditArray = $tagsEditValue !== '' ? array_values(array_filter(array_map('trim', explode(',', $tagsEditValue)))) : [];
                @endphp
                <div class="edit-mode archive-edit-tags" style="display: none; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border);">
                    <div class="form-group" style="margin: 0;">
                        <label>Tags</label>
                        <input type="hidden" name="tags" id="archive-tags-input" value="{{ $tagsEditValue }}">
                        <div class="archive-tag-pills" id="archive-tag-pills">
                            @foreach($tagsEditArray as $tagVal)
                                <span class="archive-tag-pill" data-tag="{{ e($tagVal) }}">
                                    {{ $tagVal }}
                                    <button type="button" class="archive-tag-pill-remove" aria-label="Remove tag {{ e($tagVal) }}">×</button>
                                </span>
                            @endforeach
                        </div>
                        <input type="text" id="archive-tag-new" placeholder="Add tag…" autocomplete="off" style="margin-top: 0.5rem; max-width: 16rem;">
                    </div>
                </div>
                @endif
            </div>
        </details>
    @endif

    @if($canApplyRecommendations)
    <div class="edit-mode" id="archive-edit-actions" style="margin-top: 1.5rem; @if(!$errors->isNotEmpty()) display: none; @endif">
        <button type="submit" form="archive-edit-form" class="btn-submit">Save changes</button>
        <button type="button" id="archive-edit-cancel" class="btn-cancel">Cancel</button>
    </div>
    @endif

</div>
@if($canApplyRecommendations)
</form>
<script>
(function() {
    var toggle = document.getElementById('archive-edit-toggle');
    var detail = document.getElementById('archive-detail');
    var actions = document.getElementById('archive-edit-actions');
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
    var cancelBtn = document.getElementById('archive-edit-cancel');
    if (cancelBtn) cancelBtn.addEventListener('click', function() {
        detail.classList.remove('edit-mode');
        updateToggleLabel();
    });
    updateToggleLabel();
})();
(function() {
    var input = document.getElementById('archive-tags-input');
    var pills = document.getElementById('archive-tag-pills');
    var newInput = document.getElementById('archive-tag-new');
    if (!input || !pills) return;
    function getTags() {
        var v = (input.value || '').trim();
        return v ? v.split(',').map(function(s) { return s.trim(); }).filter(Boolean) : [];
    }
    function setTags(arr) {
        input.value = arr.join(', ');
    }
    function syncPills() {
        var tags = getTags();
        pills.innerHTML = '';
        tags.forEach(function(tag) {
            var span = document.createElement('span');
            span.className = 'archive-tag-pill';
            span.setAttribute('data-tag', tag);
            span.appendChild(document.createTextNode(tag + ' '));
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'archive-tag-pill-remove';
            btn.setAttribute('aria-label', 'Remove tag ' + tag);
            btn.textContent = '×';
            btn.addEventListener('click', function() {
                var arr = getTags().filter(function(t) { return t !== tag; });
                setTags(arr);
                syncPills();
            });
            span.appendChild(btn);
            pills.appendChild(span);
        });
    }
    pills.querySelectorAll('.archive-tag-pill-remove').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tag = this.closest('.archive-tag-pill').getAttribute('data-tag');
            var arr = getTags().filter(function(t) { return t !== tag; });
            setTags(arr);
            syncPills();
        });
    });
    if (newInput) {
        function addTag() {
            var val = newInput.value.trim();
            if (!val) return;
            var arr = getTags();
            if (arr.indexOf(val) === -1) arr.push(val);
            setTags(arr);
            syncPills();
            newInput.value = '';
        }
        newInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); addTag(); }
        });
        newInput.addEventListener('blur', addTag);
    }
})();
</script>
@endif
@endsection
