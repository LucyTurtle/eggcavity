@extends('layouts.app')

@section('title', 'Travel viewer — by creature')

@section('content')
<div class="page-header">
    <h1>Travel viewer — by creature</h1>
    <p class="lead">Pick a creature and stage to see that creature with every single travel below.</p>
</div>

<style>
    .tv-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
    .tv-controls .stage-tabs { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-end; }
    .tv-controls .stage-tab {
        display: flex; flex-direction: column; align-items: center; gap: 0.25rem;
        padding: 0.25rem; font-size: 0.75rem; border: 2px solid var(--border); border-radius: var(--radius-sm);
        background: var(--surface); color: var(--text); cursor: pointer; font-family: inherit;
    }
    .tv-controls .stage-tab:hover { border-color: var(--accent); color: var(--accent); }
    .tv-controls .stage-tab.active { border-color: var(--accent); background: var(--accent-muted); color: var(--accent); }
    .tv-controls .stage-tab .stage-tab-img { width: 48px; height: 48px; object-fit: contain; display: block; }
    .tv-controls .field-wrap { display: flex; align-items: center; gap: 0.5rem; }
    .tv-controls .field-wrap label { font-size: 0.9375rem; }
    .tv-grid { display: grid; grid-template-columns: repeat(auto-fill, 120px); gap: 0.75rem; justify-content: start; }
    .tv-result-card { background: var(--surface); border: 1px solid var(--border); padding: 0.75rem; text-align: center; box-shadow: var(--shadow); width: 120px; box-sizing: border-box; }
    .tv-result-card .stage-image-wrapper { position: relative; width: 90px; height: 90px; margin: 0 auto; flex-shrink: 0; }
    .tv-result-card .stage-image-wrapper .trinket-background { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1; }
    .tv-result-card .stage-image-wrapper img:not(.trinket-background) { position: relative; width: 100%; height: 100%; object-fit: contain; z-index: 2; }
    .tv-result-card .travel-name { font-size: 0.75rem; color: var(--text-secondary); margin: 0.35rem 0 0 0; line-height: 1.2; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    .searchable-wrap { position: relative; max-width: 18rem; }
    .searchable-wrap input[type=text] { width: 100%; padding: 0.4rem 0.6rem; font-size: 0.9375rem; border: 1px solid var(--border); border-radius: var(--radius-sm); box-sizing: border-box; }
    .searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; max-height: 10rem; overflow-y: auto; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow); z-index: 10; display: none; margin-top: 2px; }
    .searchable-dropdown.show { display: block; }
    .searchable-dropdown [data-slug] { display: block; padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.9375rem; }
    .searchable-dropdown [data-slug]:hover { background: var(--accent-muted); }
    .tv-empty { color: var(--text-secondary); }
</style>

<div class="tv-controls">
    <div class="field-wrap">
        <label>Creature:</label>
        <div class="searchable-wrap" id="creature-wrap">
            <input type="text" id="creature-input" autocomplete="off" placeholder="Type to search...">
            <input type="hidden" id="creature-slug" value="">
            <div class="searchable-dropdown" id="creature-dropdown"></div>
        </div>
    </div>
    <div class="stage-tabs" id="stage-tabs" role="tablist"></div>
</div>

<div id="grid-container" class="tv-grid" style="display: none;"></div>
<p class="tv-empty" id="no-selection-msg">Choose a creature above to see it with every travel.</p>

<script>
(function() {
    var creatures = @json($creaturesForJs);
    var travels = @json($travelsForJs);

    var creatureInput = document.getElementById('creature-input');
    var creatureSlugEl = document.getElementById('creature-slug');
    var creatureDropdown = document.getElementById('creature-dropdown');
    var stageTabs = document.getElementById('stage-tabs');
    var gridContainer = document.getElementById('grid-container');
    var noSelectionMsg = document.getElementById('no-selection-msg');

    var currentStageIndex = 0;

    function getCreature(slug) { return creatures.find(function(c) { return c.slug === slug; }); }

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function buildSearchable(nameKey, slugKey, options, inputEl, hiddenEl, dropdownEl, onChange) {
        function filter(q) {
            var qq = (q || '').toLowerCase().trim();
            return options.filter(function(o) { return o[nameKey].toLowerCase().indexOf(qq) !== -1; });
        }
        function renderList(filtered) {
            dropdownEl.innerHTML = '';
            filtered.forEach(function(opt) {
                var div = document.createElement('div');
                div.setAttribute('data-slug', opt[slugKey]);
                div.textContent = opt[nameKey];
                dropdownEl.appendChild(div);
            });
        }
        function select(slug) {
            var opt = options.find(function(o) { return o[slugKey] === slug; });
            if (opt) {
                hiddenEl.value = slug;
                inputEl.value = opt[nameKey];
                dropdownEl.classList.remove('show');
                if (onChange) onChange();
            }
        }
        dropdownEl.addEventListener('click', function(e) {
            var el = e.target.closest('[data-slug]');
            if (el) select(el.getAttribute('data-slug'));
        });
        inputEl.addEventListener('focus', function() { renderList(options); dropdownEl.classList.add('show'); });
        inputEl.addEventListener('input', function() { renderList(filter(inputEl.value)); dropdownEl.classList.add('show'); });
        inputEl.addEventListener('blur', function() { setTimeout(function() { dropdownEl.classList.remove('show'); }, 150); });
        inputEl.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') dropdownEl.classList.remove('show');
            if (e.key === 'Enter') { var first = dropdownEl.querySelector('[data-slug]'); if (first) select(first.getAttribute('data-slug')); e.preventDefault(); }
        });
    }

    function renderStageTabs(creature) {
        stageTabs.innerHTML = '';
        if (!creature || !creature.stages || creature.stages.length === 0) return;
        creature.stages.forEach(function(stage, i) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'stage-tab' + (i === 0 ? ' active' : '');
            btn.setAttribute('role', 'tab');
            btn.setAttribute('data-index', i);
            if (stage.image_url) {
                var img = document.createElement('img');
                img.src = stage.image_url;
                img.alt = 'Stage ' + stage.stage_number;
                img.className = 'stage-tab-img';
                img.setAttribute('referrerpolicy', 'no-referrer');
                btn.appendChild(img);
            }
            var label = document.createElement('span');
            label.textContent = 'Stage ' + stage.stage_number;
            btn.appendChild(label);
            stageTabs.appendChild(btn);
        });
        stageTabs.querySelectorAll('.stage-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentStageIndex = parseInt(btn.getAttribute('data-index'), 10);
                stageTabs.querySelectorAll('.stage-tab').forEach(function(b, j) {
                    b.classList.toggle('active', j === currentStageIndex);
                });
                renderGrid();
            });
        });
        currentStageIndex = 0;
    }

    function renderGrid() {
        var cSlug = creatureSlugEl.value;
        if (!cSlug) {
            gridContainer.style.display = 'none';
            noSelectionMsg.style.display = 'block';
            gridContainer.innerHTML = '';
            return;
        }
        var creature = getCreature(cSlug);
        if (!creature) return;
        var stage = (creature.stages || [])[currentStageIndex];
        if (!stage) {
            gridContainer.style.display = 'none';
            noSelectionMsg.style.display = 'block';
            gridContainer.innerHTML = '';
            return;
        }
        noSelectionMsg.style.display = 'none';
        gridContainer.style.display = 'grid';
        var stageImg = stage.image_url ? '<img src="' + escapeHtml(stage.image_url) + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '';
        var html = '';
        travels.forEach(function(travel) {
            var travelBg = travel.image_url ? '<img src="' + escapeHtml(travel.image_url) + '" alt="" class="trinket-background" loading="lazy" referrerpolicy="no-referrer">' : '';
            html += '<div class="tv-result-card"><div class="stage-image-wrapper">' + travelBg + stageImg + '</div><p class="travel-name">' + escapeHtml(travel.name) + '</p></div>';
        });
        gridContainer.innerHTML = html || '<p class="tv-empty">No travels.</p>';
    }

    buildSearchable('title', 'slug', creatures, creatureInput, creatureSlugEl, creatureDropdown, function() {
        var c = getCreature(creatureSlugEl.value);
        renderStageTabs(c);
        renderGrid();
    });

    var firstCreature = creatures[0];
    if (firstCreature) {
        creatureSlugEl.value = firstCreature.slug;
        creatureInput.value = firstCreature.title;
    }
    renderStageTabs(firstCreature ? getCreature(firstCreature.slug) : null);
    renderGrid();
})();
</script>
@endsection
