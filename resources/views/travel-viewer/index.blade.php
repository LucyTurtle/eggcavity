@extends('layouts.app')

@section('title', 'Travel viewer')

@section('content')
<div class="page-header">
    <h1>Travel viewer</h1>
    <p class="lead">Select a creature and a travel to see the travel on every stage. Any travel can be shown on any creature.</p>
</div>

<style>
    .travel-viewer-result { margin-bottom: 1.5rem; }
    .travel-viewer-result .pairs-grid { margin-bottom: 1rem; display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .travel-viewer-result .pair-card { margin: 0; padding: 0; border: none; background: none; }
    .travel-viewer-result .stage-image-wrapper {
        position: relative;
        width: 90px;
        height: 90px;
        margin: 0;
    }
    .travel-viewer-result .stage-image-wrapper .trinket-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }
    .travel-viewer-result .stage-image-wrapper img:not(.trinket-background) {
        width: 100%;
        height: 100%;
        object-fit: contain;
        position: relative;
        z-index: 2;
    }
    .travel-viewer-no-selection { color: var(--text-secondary); }
    .travel-viewer-form .form-row { margin-bottom: 1rem; }
    .travel-viewer-form label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; }
    .searchable-select-wrap { position: relative; max-width: 20rem; }
    .searchable-select-wrap input { width: 100%; padding: 0.5rem 0.75rem; font-size: 0.9375rem; border: 1px solid var(--border); border-radius: var(--radius-sm); box-sizing: border-box; }
    .searchable-select-dropdown { position: absolute; top: 100%; left: 0; right: 0; max-height: 12rem; overflow-y: auto; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow); z-index: 10; display: none; margin-top: 2px; }
    .searchable-select-dropdown.show { display: block; }
    .searchable-select-dropdown option { display: block; padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.9375rem; }
    .searchable-select-dropdown option:hover, .searchable-select-dropdown option.highlight { background: var(--accent-muted); }
    .searchable-select-dropdown option.hidden { display: none; }
</style>

<div class="card" style="max-width: 36rem;">
    <div class="travel-viewer-result" id="result-area" aria-live="polite">
        <p class="travel-viewer-no-selection" id="no-selection-msg">Choose a creature and a travel below to see the travel on all stages.</p>
        <div id="pairs-container" class="pairs-grid" style="display: none;"></div>
    </div>
    <div class="travel-viewer-form">
        <div class="form-row">
            <label for="creature-input">Creature</label>
            <div class="searchable-select-wrap" id="creature-wrap">
                <input type="text" id="creature-input" autocomplete="off" placeholder="Type to search...">
                <input type="hidden" id="creature" value="{{ $initialCreature ?? '' }}">
                <div class="searchable-select-dropdown" id="creature-dropdown" role="listbox"></div>
            </div>
        </div>
        <div class="form-row">
            <label for="travel-input">Travel</label>
            <div class="searchable-select-wrap" id="travel-wrap">
                <input type="text" id="travel-input" autocomplete="off" placeholder="Type to search...">
                <input type="hidden" id="travel" value="{{ $initialTravel ?? '' }}">
                <div class="searchable-select-dropdown" id="travel-dropdown" role="listbox"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var creatures = @json($creaturesForJs);
    var travels = @json($travelsForJs);

    var creatureInput = document.getElementById('creature-input');
    var creatureHidden = document.getElementById('creature');
    var creatureDropdown = document.getElementById('creature-dropdown');
    var travelInput = document.getElementById('travel-input');
    var travelHidden = document.getElementById('travel');
    var travelDropdown = document.getElementById('travel-dropdown');
    var noSelectionMsg = document.getElementById('no-selection-msg');
    var pairsContainer = document.getElementById('pairs-container');

    function getCreatureBySlug(slug) { return creatures.find(function(c) { return c.slug === slug; }); }
    function getTravelBySlug(slug) { return travels.find(function(t) { return t.slug === slug; }); }

    function buildSearchable(nameKey, slugKey, options, inputEl, hiddenEl, dropdownEl, setDisplay, onChange) {
        function filter(q) {
            var qq = (q || '').toLowerCase().trim();
            return options.filter(function(o) { return o[nameKey].toLowerCase().indexOf(qq) !== -1; });
        }
        function renderList(filtered) {
            dropdownEl.innerHTML = '';
            filtered.forEach(function(opt) {
                var div = document.createElement('div');
                div.setAttribute('role', 'option');
                div.setAttribute('data-slug', opt[slugKey]);
                div.textContent = opt[nameKey];
                dropdownEl.appendChild(div);
            });
        }
        function showDropdown() {
            var q = inputEl.value.trim();
            renderList(q ? filter(q) : options);
            dropdownEl.classList.add('show');
        }
        function hideDropdown() {
            setTimeout(function() { dropdownEl.classList.remove('show'); }, 150);
        }
        function select(slug) {
            var opt = options.find(function(o) { return o[slugKey] === slug; });
            if (opt) {
                hiddenEl.value = slug;
                setDisplay(opt[nameKey]);
                hideDropdown();
                if (onChange) onChange();
            }
        }
        dropdownEl.addEventListener('click', function(e) {
            var opt = e.target.closest('[data-slug]');
            if (opt) select(opt.getAttribute('data-slug'));
        });
        inputEl.addEventListener('focus', showDropdown);
        inputEl.addEventListener('input', function() {
            renderList(filter(inputEl.value));
            dropdownEl.classList.add('show');
        });
        inputEl.addEventListener('blur', hideDropdown);
        inputEl.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { inputEl.blur(); return; }
            var opts = dropdownEl.querySelectorAll('[data-slug]');
            if (e.key === 'Enter' && opts.length) { select(opts[0].getAttribute('data-slug')); e.preventDefault(); }
        });
    }

    var initialCreatureSlug = creatureHidden.value;
    var initialTravelSlug = travelHidden.value;
    if (initialCreatureSlug) {
        var c = getCreatureBySlug(initialCreatureSlug);
        if (c) creatureInput.value = c.title;
    }
    if (initialTravelSlug) {
        var t = getTravelBySlug(initialTravelSlug);
        if (t) travelInput.value = t.name;
    }

    buildSearchable('title', 'slug', creatures, creatureInput, creatureHidden, creatureDropdown, function(v) { creatureInput.value = v; }, render);
    buildSearchable('name', 'slug', travels, travelInput, travelHidden, travelDropdown, function(v) { travelInput.value = v; }, render);

    function render() {
        var creatureSlug = creatureHidden.value;
        var travelSlug = travelHidden.value;
        if (!creatureSlug || !travelSlug) {
            noSelectionMsg.style.display = 'block';
            pairsContainer.style.display = 'none';
            pairsContainer.innerHTML = '';
            return;
        }
        var creature = getCreatureBySlug(creatureSlug);
        var travel = getTravelBySlug(travelSlug);
        if (!creature || !travel) {
            noSelectionMsg.style.display = 'block';
            pairsContainer.style.display = 'none';
            pairsContainer.innerHTML = '';
            return;
        }
        noSelectionMsg.style.display = 'none';
        pairsContainer.style.display = 'grid';
        var stages = creature.stages || [];

        var html = '';
        stages.forEach(function(stage) {
            var stageImg = stage.image_url ? '<img src="' + escapeHtml(stage.image_url) + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '';
            var travelBg = travel.image_url ? '<img src="' + escapeHtml(travel.image_url) + '" alt="" class="trinket-background" loading="lazy" referrerpolicy="no-referrer">' : '';
            html += '<div class="pair-card">';
            html += '<div class="stage-image-wrapper">' + travelBg + stageImg + '</div>';
            html += '</div>';
        });
        pairsContainer.innerHTML = html || '<p class="travel-viewer-no-selection">No stages for this creature.</p>';
    }

    function escapeHtml(s) { var div = document.createElement('div'); div.textContent = s; return div.innerHTML; }

    render();
})();
</script>
@endsection
