@extends('layouts.app')

@section('title', 'Travel viewer')

@section('content')
<div class="page-header">
    <h1>Travel viewer</h1>
    <p class="lead">Select a creature and a travel to see the travel on every stage.</p>
    <p style="font-size: 0.9375rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">Click a creature+travel stage below to save that combination; saved items are stored in your browser and can be removed anytime.</p>
</div>

<style>
    .travel-viewer-result { margin-bottom: 1.5rem; }
    .travel-viewer-result .pairs-grid {
        margin-bottom: 1rem;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.35rem;
        align-items: flex-start;
    }
    .travel-viewer-result .pair-card {
        margin: 0;
        padding: 0;
        border: none;
        background: none;
        flex: 0 0 auto;
        cursor: pointer;
    }
    .travel-viewer-result .pair-card:hover { opacity: 0.9; }
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
    .searchable-select-dropdown { position: absolute; top: 100%; left: 0; right: 0; max-height: 12rem; overflow-y: auto; overflow-x: hidden; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow); z-index: 10; display: none; margin-top: 2px; }
    .searchable-select-dropdown.show { display: block; }
    .searchable-select-dropdown .option-item { display: block; padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.9375rem; }
    .searchable-select-dropdown .option-item:hover, .searchable-select-dropdown .option-item.highlight { background: var(--accent-muted); }
    .searchable-select-dropdown .option-item.hidden { display: none; }
    .saved-combos { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); }
    .saved-combos h3 { font-size: 1rem; margin: 0 0 0.75rem 0; }
    .saved-combos-grid { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-start; }
    .saved-combo-card { position: relative; width: 100px; flex-shrink: 0; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.5rem; box-shadow: var(--shadow); }
    .saved-combo-card .thumb { position: relative; width: 80px; height: 80px; margin: 0 auto; }
    .saved-combo-card .thumb .bg, .saved-combo-card .thumb .fg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .saved-combo-card .thumb .fg { object-fit: contain; z-index: 2; }
    .saved-combo-card .label { font-size: 0.7rem; color: var(--text-secondary); margin: 0.35rem 0 0 0; text-align: center; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .saved-combo-card .btn-remove { position: absolute; top: 2px; right: 2px; width: 20px; height: 20px; padding: 0; border: none; background: var(--surface); color: var(--text-secondary); border-radius: var(--radius-sm); cursor: pointer; font-size: 1rem; line-height: 1; z-index: 3; }
    .saved-combo-card .btn-remove:hover { background: #fef2f2; color: #dc2626; }
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
            <label for="travel-input">Travels</label>
            <div class="searchable-select-wrap" id="travel-wrap">
                <input type="text" id="travel-input" autocomplete="off" placeholder="Type to search...">
                <div class="searchable-select-dropdown" id="travel-dropdown" role="listbox"></div>
            </div>
        </div>
        <div class="form-row">
            <label for="trinket-travel-input">Trinket travels</label>
            <div class="searchable-select-wrap" id="trinket-travel-wrap">
                <input type="text" id="trinket-travel-input" autocomplete="off" placeholder="Type to search...">
                <div class="searchable-select-dropdown" id="trinket-travel-dropdown" role="listbox"></div>
            </div>
        </div>
        <input type="hidden" id="travel" value="{{ $initialTravel ?? '' }}">
    </div>
    <form method="get" action="{{ route('travel-viewer.index') }}" style="margin-top: 0.75rem;">
        @if(request('creature'))<input type="hidden" name="creature" value="{{ request('creature') }}">@endif
        @if(request('travel'))<input type="hidden" name="travel" value="{{ request('travel') }}">@endif
        <label style="font-size: 0.9375rem; color: var(--text); margin: 0; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="available" value="1" {{ $filterAvailable ? 'checked' : '' }} onchange="this.form.submit()">
            Only show currently available travels
        </label>
    </form>
</div>

<div class="saved-combos card" id="saved-combos" style="max-width: 36rem; margin-top: 1.5rem;">
    <h3>Saved combinations</h3>
    <p class="travel-viewer-no-selection" id="saved-none">No saved combinations yet. Click a creature+travel stage above to save it.</p>
    <div id="saved-combos-grid" class="saved-combos-grid" style="display: none;"></div>
</div>

<script>
(function() {
    var creatures = @json($creaturesForJs);
    var travels = @json($travelsForJs);
    var trinketTravels = @json($trinketTravelsForJs);
    var allTravels = @json($allTravelsForJs);

    var creatureInput = document.getElementById('creature-input');
    var creatureHidden = document.getElementById('creature');
    var creatureDropdown = document.getElementById('creature-dropdown');
    var travelInput = document.getElementById('travel-input');
    var trinketTravelInput = document.getElementById('trinket-travel-input');
    var travelHidden = document.getElementById('travel');
    var travelDropdown = document.getElementById('travel-dropdown');
    var trinketTravelDropdown = document.getElementById('trinket-travel-dropdown');
    var noSelectionMsg = document.getElementById('no-selection-msg');
    var pairsContainer = document.getElementById('pairs-container');

    function getCreatureBySlug(slug) { return creatures.find(function(c) { return c.slug === slug; }); }
    function getTravelBySlug(slug) { return allTravels.find(function(t) { return t.slug === slug; }); }

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
                div.className = 'option-item';
                div.textContent = (opt[nameKey] != null ? opt[nameKey] : '').toString();
                dropdownEl.appendChild(div);
            });
        }
        function showDropdown() {
            /* Show full list on open so user sees all options; filter happens on input. */
            renderList(options);
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
        if (t) {
            var inTravels = travels.some(function(x) { return x.slug === t.slug; });
            if (inTravels) travelInput.value = t.name; else trinketTravelInput.value = t.name;
        }
    }

    function setTravelFromTravels(name) {
        travelInput.value = name;
        trinketTravelInput.value = '';
    }
    function setTravelFromTrinket(name) {
        trinketTravelInput.value = name;
        travelInput.value = '';
    }

    buildSearchable('title', 'slug', creatures, creatureInput, creatureHidden, creatureDropdown, function(v) { creatureInput.value = v; }, render);
    buildSearchable('name', 'slug', travels, travelInput, travelHidden, travelDropdown, setTravelFromTravels, render);
    buildSearchable('name', 'slug', trinketTravels, trinketTravelInput, travelHidden, trinketTravelDropdown, setTravelFromTrinket, render);

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
        pairsContainer.style.display = 'flex';
        var stages = creature.stages || [];

        var html = '';
        stages.forEach(function(stage) {
            var stageImg = stage.image_url ? '<img src="' + escapeHtml(stage.image_url) + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '';
            var travelBg = travel.image_url ? '<img src="' + escapeHtml(travel.image_url) + '" alt="" class="trinket-background" loading="lazy" referrerpolicy="no-referrer">' : '';
            var stageNum = stage.stage_number != null ? stage.stage_number : '';
            html += '<div class="pair-card" data-creature-slug="' + escapeHtml(creature.slug) + '" data-creature-title="' + escapeHtml(creature.title) + '" data-travel-slug="' + escapeHtml(travel.slug) + '" data-travel-name="' + escapeHtml(travel.name) + '" data-stage="' + escapeHtml(String(stageNum)) + '" title="Click to save this combination">';
            html += '<div class="stage-image-wrapper">' + travelBg + stageImg + '</div>';
            html += '</div>';
        });
        pairsContainer.innerHTML = html || '<p class="travel-viewer-no-selection">No stages for this creature.</p>';
        pairsContainer.querySelectorAll('.pair-card').forEach(function(card) {
            card.addEventListener('click', function() {
                var cSlug = card.getAttribute('data-creature-slug');
                var ct = card.getAttribute('data-creature-title');
                var tSlug = card.getAttribute('data-travel-slug');
                var tn = card.getAttribute('data-travel-name');
                var s = card.getAttribute('data-stage');
                if (cSlug && tSlug) saveStageCombo({ c: cSlug, ct: ct, t: tSlug, tn: tn, s: s ? parseInt(s, 10) : null });
            });
        });
    }

    function escapeHtml(s) { var div = document.createElement('div'); div.textContent = s; return div.innerHTML; }

    var SAVED_KEY = 'travel_viewer_saved';
    var savedNone = document.getElementById('saved-none');
    var savedGrid = document.getElementById('saved-combos-grid');

    function getSaved() {
        try {
            var raw = localStorage.getItem(SAVED_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) { return []; }
    }
    function setSaved(list) {
        try { localStorage.setItem(SAVED_KEY, JSON.stringify(list)); } catch (e) {}
    }
    function saveStageCombo(item) {
        var list = getSaved();
        list.push(item);
        setSaved(list);
        renderSaved();
    }
    function removeSaved(index) {
        var list = getSaved();
        list.splice(index, 1);
        setSaved(list);
        renderSaved();
    }
    function renderSaved() {
        var list = getSaved();
        savedNone.style.display = list.length ? 'none' : 'block';
        savedGrid.style.display = list.length ? 'flex' : 'none';
        savedGrid.innerHTML = '';
        list.forEach(function(item, i) {
            var creature = getCreatureBySlug(item.c);
            var travel = getTravelBySlug(item.t);
            var stage = (creature && creature.stages) ? creature.stages.find(function(s) { return s.stage_number === item.s; }) : null;
            if (!stage && creature && creature.stages && creature.stages[0]) stage = creature.stages[0];
            var stageImg = stage && stage.image_url ? escapeHtml(stage.image_url) : '';
            var travelBg = travel && travel.image_url ? escapeHtml(travel.image_url) : '';
            var card = document.createElement('div');
            card.className = 'saved-combo-card';
            card.innerHTML = '<button type="button" class="btn-remove" aria-label="Remove">×</button>' +
                '<div class="thumb">' +
                (travelBg ? '<img class="bg" src="' + travelBg + '" alt="" referrerpolicy="no-referrer">' : '') +
                (stageImg ? '<img class="fg" src="' + stageImg + '" alt="" referrerpolicy="no-referrer">' : '') +
                '</div><p class="label">' + escapeHtml(item.ct) + ' + ' + escapeHtml(item.tn) + (item.s != null ? ' (Stage ' + item.s + ')' : '') + '</p>';
            card.querySelector('.btn-remove').addEventListener('click', function() { removeSaved(i); });
            savedGrid.appendChild(card);
        });
    }

    render();
    renderSaved();
})();
</script>
@endsection
