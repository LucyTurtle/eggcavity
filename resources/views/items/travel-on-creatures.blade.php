@extends('layouts.app')

@section('title', $travel->name . ' — on creatures')

@section('content')
<div class="page-header">
    <nav style="font-size: 0.9375rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('items.show', $travel->slug) }}">← {{ $travel->name }}</a>
        <a href="{{ route('items.index', ['use_type' => 'travel']) }}">Travels</a>
    </nav>
    <h1>{{ $travel->name }} on creatures</h1>
    <p class="lead">Pick any creature and stage to see this travel on it. Switch using the controls below—no page reload.</p>
</div>

<style>
    .travel-on-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .travel-on-controls .creature-select-wrap label { margin-right: 0.5rem; font-size: 0.9375rem; }
    .travel-on-controls select { padding: 0.4rem 0.6rem; font-size: 0.9375rem; border: 1px solid var(--border); border-radius: var(--radius-sm); min-width: 12rem; }
    .travel-on-controls .stage-tabs { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .travel-on-controls .stage-tab {
        padding: 0.4rem 0.75rem;
        font-size: 0.9375rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--text);
        cursor: pointer;
        font-family: inherit;
    }
    .travel-on-controls .stage-tab:hover { border-color: var(--accent); color: var(--accent); }
    .travel-on-controls .stage-tab.active { background: var(--accent); color: white; border-color: var(--accent); }
    .travel-on-result-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 2rem;
        text-align: center;
        max-width: 320px;
        box-shadow: var(--shadow);
    }
    .travel-on-result-card .stage-image-wrapper {
        position: relative;
        width: 90px;
        height: 90px;
        margin: 0 auto 1rem;
    }
    .travel-on-result-card .stage-image-wrapper .trinket-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }
    .travel-on-result-card .stage-image-wrapper img:not(.trinket-background) {
        width: 100%;
        height: 100%;
        object-fit: contain;
        position: relative;
        z-index: 2;
    }
    .travel-on-empty { color: var(--text-secondary); }
</style>

<div class="travel-on-controls">
    <div class="creature-select-wrap">
        <label for="creature-select">Creature:</label>
        <select id="creature-select">
            <option value="">— Choose creature —</option>
            @foreach($creatures as $c)
                <option value="{{ $c->slug }}">{{ $c->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="stage-tabs" id="stage-tabs" role="tablist" style="display: none;">
        <!-- populated by JS when creature selected -->
    </div>
</div>

<div id="composite-card" class="travel-on-result-card" style="display: none;">
    <div class="stage-image-wrapper" id="composite-wrapper">
        @if($travel->image_url)
            <img id="composite-travel-bg" class="trinket-background" src="{{ $travel->image_url }}" alt="" referrerpolicy="no-referrer">
        @endif
        <img id="composite-stage-img" alt="" referrerpolicy="no-referrer">
    </div>
</div>
<p class="travel-on-empty" id="no-creature-msg">Choose a creature above to see this travel on its stages.</p>

<script>
(function() {
    var creatures = @json($creaturesForJs);
    var travel = {
        slug: @json($travel->slug),
        name: @json($travel->name),
        image_url: @json($travel->image_url)
    };
    var baseUrl = (function(){ var a = document.createElement('a'); a.href = '/'; return a.origin; })();

    var creatureSelect = document.getElementById('creature-select');
    var stageTabs = document.getElementById('stage-tabs');
    var compositeCard = document.getElementById('composite-card');
    var noCreatureMsg = document.getElementById('no-creature-msg');
    var compositeStageImg = document.getElementById('composite-stage-img');

    var currentStageIndex = 0;

    function getCreatureBySlug(slug) {
        return creatures.find(function(c) { return c.slug === slug; });
    }

    function renderStageTabs(creature) {
        stageTabs.innerHTML = '';
        if (!creature || !creature.stages || creature.stages.length === 0) {
            stageTabs.style.display = 'none';
            return;
        }
        stageTabs.style.display = 'flex';
        creature.stages.forEach(function(stage, i) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'stage-tab' + (i === 0 ? ' active' : '');
            btn.setAttribute('role', 'tab');
            btn.setAttribute('data-stage-index', i);
            btn.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
            btn.textContent = 'Stage ' + stage.stage_number;
            stageTabs.appendChild(btn);
        });
        currentStageIndex = 0;
        stageTabs.querySelectorAll('.stage-tab').forEach(function(btn, i) {
            btn.addEventListener('click', function() {
                currentStageIndex = i;
                stageTabs.querySelectorAll('.stage-tab').forEach(function(b, j) {
                    var isActive = j === i;
                    b.classList.toggle('active', isActive);
                    b.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                updateComposite();
            });
        });
    }

    function updateComposite() {
        var creatureSlug = creatureSelect.value;
        if (!creatureSlug) {
            compositeCard.style.display = 'none';
            noCreatureMsg.style.display = 'block';
            stageTabs.style.display = 'none';
            return;
        }
        var creature = getCreatureBySlug(creatureSlug);
        if (!creature) return;
        var stages = creature.stages || [];
        var stage = stages[currentStageIndex];
        if (!stage) {
            compositeCard.style.display = 'none';
            noCreatureMsg.style.display = 'block';
            return;
        }

        noCreatureMsg.style.display = 'none';
        compositeCard.style.display = 'block';

        compositeStageImg.src = stage.image_url || '';
        compositeStageImg.alt = creature.title + ' stage ' + stage.stage_number;
    }

    creatureSelect.addEventListener('change', function() {
        var creature = getCreatureBySlug(creatureSelect.value);
        renderStageTabs(creature);
        updateComposite();
    });

    renderStageTabs(null);
    updateComposite();
})();
</script>
@endsection
