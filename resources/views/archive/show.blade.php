@extends('layouts.app')

@section('title', $item->title)

@section('content')
<div class="page-header">
    <nav style="font-size: 0.9375rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <a href="{{ route('archive.index') }}">← Back to archive</a>
        <span style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            @if($item->source_url)
                <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer">Open on EggCave.com →</a>
            @endif
        </span>
    </nav>
    <h1>{{ $item->title }}</h1>
</div>

<style>
    .archive-detail .main-img { max-width: 100%; border: 1px solid var(--border); background: var(--bg); }
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
    .archive-detail .tags-list { margin-top: 0.5rem; }
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
    .archive-detail .tags-list a:hover { background: var(--accent); color: white; }
    .archive-detail .recommended-travels-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
        margin: 1rem 0;
    }
    @media (max-width: 1200px) {
        .archive-detail .recommended-travels-grid { grid-template-columns: repeat(4, 1fr); }
    }
    @media (max-width: 900px) {
        .archive-detail .recommended-travels-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 600px) {
        .archive-detail .recommended-travels-grid { grid-template-columns: repeat(2, 1fr); }
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
        display: block;
    }
    .archive-detail .travel-card .thumb {
        aspect-ratio: 1;
        background: var(--bg);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .archive-detail .travel-card .thumb img {
        width: 100%;
        height: 100%;
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
</style>

<div class="archive-detail">
    {{-- All stages with images and level-up requirements --}}
    @if($item->stages->isNotEmpty())
        <div class="stages-grid">
            @foreach($item->stages as $stage)
                <div class="stage-card">
                    <div class="stage-image-wrapper">
                        @if(isset($trinketTravels[$stage->id]) && $trinketTravels[$stage->id]->image_url)
                            @php $trinket = $trinketTravels[$stage->id]; @endphp
                            <img src="{{ $trinket->image_url }}" alt="{{ $trinket->name }}" class="trinket-background" loading="lazy" referrerpolicy="no-referrer">
                        @endif
                        <img src="{{ $stage->image_url }}" alt="{{ $item->title }} stage {{ $stage->stage_number }}" loading="lazy" referrerpolicy="no-referrer">
                    </div>
                    @if($stage->requirement)
                        <p class="stage-requirement">{{ $stage->requirement }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Creature stats --}}
    @if($item->availability || $item->dates || $item->weight || $item->length || $item->obtained_from || $item->gender_profile || $item->habitat)
        <div class="card">
            <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem;">Details</h3>
            <dl class="stats-grid">
                @if($item->availability)<div class="stat-item"><dt>Availability</dt><dd>{{ $item->availability }}</dd></div>@endif
                @if($item->dates)<div class="stat-item"><dt>Dates</dt><dd>{{ $item->dates }}</dd></div>@endif
                @if($item->weight)<div class="stat-item"><dt>Weight</dt><dd>{{ $item->weight }}</dd></div>@endif
                @if($item->length)<div class="stat-item"><dt>Length</dt><dd>{{ $item->length }}</dd></div>@endif
                @if($item->obtained_from)<div class="stat-item"><dt>Obtained from</dt><dd>{{ $item->obtained_from }}</dd></div>@endif
                @if($item->gender_profile)<div class="stat-item"><dt>Gender profile</dt><dd>{{ $item->gender_profile }}</dd></div>@endif
                @if($item->habitat)<div class="stat-item"><dt>Habitat</dt><dd>{{ $item->habitat }}</dd></div>@endif
            </dl>
        </div>
    @endif

    @if($item->about_eggs)
        <div class="card">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">About {{ $item->title }} eggs</h3>
            <div style="white-space: pre-line;">{{ $item->about_eggs }}</div>
        </div>
    @endif

    @if($item->about_creature)
        <div class="card">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">About the {{ $item->title }} creature</h3>
            <div style="white-space: pre-line;">{{ $item->about_creature }}</div>
        </div>
    @endif

    @if($item->entry_written_by || $item->design_concept_user)
        <div class="card" style="margin-top: 1rem;">
            @if($item->entry_written_by)
                <p style="margin: 0 0 0.5rem 0;">
                    <strong>Entry written by:</strong> 
                    <a href="https://eggcave.com/{{ '@' . $item->entry_written_by }}" target="_blank" rel="noopener noreferrer">{{ $item->entry_written_by }}</a>
                </p>
            @endif
            @if($item->design_concept_user)
                <p style="margin: 0;">
                    <strong>Design Concept:</strong> 
                    <a href="https://eggcave.com/{{ '@' . $item->design_concept_user }}" target="_blank" rel="noopener noreferrer">{{ $item->design_concept_user }}</a>
                </p>
            @endif
        </div>
    @endif

    @if(!empty($recommendedTravels))
        <h3 style="font-size: 0.9375rem; margin: 1rem 0 0.25rem 0;">Recommended Travels</h3>
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

    @if($item->tags && count($item->tags) > 0)
        <h3 style="font-size: 0.9375rem; margin: 1rem 0 0.25rem 0;">Tags</h3>
        <div class="tags-list">
            @foreach($item->tags as $tag)
                <a href="{{ route('archive.index', ['tag' => $tag]) }}" style="display: inline-block; font-size: 0.8125rem; padding: 0.25rem 0.5rem; margin-right: 0.5rem; margin-bottom: 0.5rem; border-radius: var(--radius-sm); background: var(--accent-muted); color: var(--accent); text-decoration: none;">{{ $tag }}</a>
            @endforeach
        </div>
    @endif

</div>
@endsection
