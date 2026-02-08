@extends('layouts.app')

@section('title', $creature->title . ' — travel viewer')

@section('content')
<div class="page-header">
    <nav style="font-size: 0.9375rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('archive.show', $creature->slug) }}">← {{ $creature->title }}</a>
        <a href="{{ route('archive.index') }}">Archive</a>
    </nav>
    <h1>{{ $creature->title }} with travel</h1>
    <p class="lead">Pick a stage and any travel to see them together. Switch using the controls below—no page reload.</p>
</div>

<style>
    .travel-viewer-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .travel-viewer-controls .stage-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .travel-viewer-controls .stage-tab {
        padding: 0.4rem 0.75rem;
        font-size: 0.9375rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--text);
        cursor: pointer;
        font-family: inherit;
        text-decoration: none;
    }
    .travel-viewer-controls .stage-tab:hover {
        border-color: var(--accent);
        color: var(--accent);
    }
    .travel-viewer-controls .stage-tab.active {
        background: var(--accent);
        color: white;
        border-color: var(--accent);
    }
    .travel-viewer-controls .travel-select-wrap label { margin-right: 0.5rem; font-size: 0.9375rem; }
    .travel-viewer-controls select { padding: 0.4rem 0.6rem; font-size: 0.9375rem; border: 1px solid var(--border); border-radius: var(--radius-sm); min-width: 12rem; }
    .travel-viewer-result-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 2rem;
        text-align: center;
        max-width: 320px;
        box-shadow: var(--shadow);
    }
    .travel-viewer-result-card .stage-image-wrapper {
        position: relative;
        width: 90px;
        height: 90px;
        margin: 0 auto 1rem;
        background: var(--surface);
    }
    .travel-viewer-result-card .stage-image-wrapper .trinket-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }
    .travel-viewer-result-card .stage-image-wrapper img:not(.trinket-background) {
        width: 100%;
        height: 100%;
        object-fit: contain;
        position: relative;
        z-index: 2;
    }
    .travel-viewer-empty { color: var(--text-secondary); }
</style>

@if($creature->stages->isEmpty())
    <div class="card">
        <p class="travel-viewer-empty">This creature has no stages in the archive.</p>
    </div>
@else
    <div class="travel-viewer-controls">
        <div class="stage-tabs" id="stage-tabs" role="tablist">
            @foreach($creature->stages as $i => $s)
                <button type="button" class="stage-tab {{ $i === 0 ? 'active' : '' }}" role="tab" data-stage-index="{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                    Stage {{ $s->stage_number }}
                </button>
            @endforeach
        </div>
        <div class="travel-select-wrap">
            <label for="travel-select">Travel:</label>
            <select id="travel-select">
                <option value="">— Choose travel —</option>
                @foreach($travels as $t)
                    <option value="{{ $t->slug }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div id="composite-card" class="travel-viewer-result-card" style="display: none;">
        <div class="stage-image-wrapper" id="composite-wrapper">
            <img id="composite-travel-bg" class="trinket-background" alt="" loading="lazy" referrerpolicy="no-referrer" style="display: none;">
            <img id="composite-stage-img" alt="" loading="lazy" referrerpolicy="no-referrer">
        </div>
    </div>
    <p class="travel-viewer-empty" id="no-travel-msg">Choose a travel above to see it on the selected stage.</p>

    <script>
    (function() {
        var stages = @json($stagesForJs);
        var travels = @json($travelsForJs);
        var creatureTitle = @json($creature->title);

        var stageTabs = document.getElementById('stage-tabs');
        var travelSelect = document.getElementById('travel-select');
        var compositeCard = document.getElementById('composite-card');
        var noTravelMsg = document.getElementById('no-travel-msg');
        var compositeTravelBg = document.getElementById('composite-travel-bg');
        var compositeStageImg = document.getElementById('composite-stage-img');

        var currentStageIndex = 0;

        function getTravelBySlug(slug) {
            return travels.find(function(t) { return t.slug === slug; });
        }

        function setActiveTab(index) {
            currentStageIndex = index;
            stageTabs.querySelectorAll('.stage-tab').forEach(function(btn, i) {
                var isActive = i === index;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            updateComposite();
        }

        function updateComposite() {
            var travelSlug = travelSelect.value;
            if (!travelSlug) {
                compositeCard.style.display = 'none';
                noTravelMsg.style.display = 'block';
                return;
            }
            var travel = getTravelBySlug(travelSlug);
            var stage = stages[currentStageIndex];
            if (!stage) return;

            noTravelMsg.style.display = 'none';
            compositeCard.style.display = 'block';

            if (travel && travel.image_url) {
                compositeTravelBg.src = travel.image_url;
                compositeTravelBg.style.display = '';
            } else {
                compositeTravelBg.style.display = 'none';
            }
            compositeStageImg.src = stage.image_url || '';
            compositeStageImg.alt = creatureTitle + ' stage ' + stage.stage_number;
        }

        stageTabs.addEventListener('click', function(e) {
            var btn = e.target.closest('.stage-tab');
            if (!btn) return;
            var index = parseInt(btn.getAttribute('data-stage-index'), 10);
            if (!isNaN(index)) setActiveTab(index);
        });
        travelSelect.addEventListener('change', updateComposite);

        updateComposite();
    })();
    </script>
@endif
@endsection
