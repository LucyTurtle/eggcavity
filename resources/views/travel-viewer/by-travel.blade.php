@extends('layouts.app')

@section('title', 'Travel viewer — by travel')

@section('content')
<div class="page-header">
    <h1>Travel viewer — by travel</h1>
    <p class="lead">Pick a travel and stage number to see every single creature with that travel.</p>
    <form method="get" action="{{ route('travel-viewer.by-travel') }}" style="margin-top: 0.75rem;">
        <label style="font-size: 0.9375rem; color: var(--text); margin: 0; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="available" value="1" {{ $filterAvailable ? 'checked' : '' }} onchange="this.form.submit()">
            Only show currently available travels
        </label>
    </form>
</div>

<style>
    .tv-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
    .tv-controls .stage-tabs { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .tv-controls .stage-tab { padding: 0.4rem 0.75rem; font-size: 0.9375rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); color: var(--text); cursor: pointer; font-family: inherit; }
    .tv-controls .stage-tab:hover { border-color: var(--accent); color: var(--accent); }
    .tv-controls .stage-tab.active { background: var(--accent); color: white; border-color: var(--accent); }
    .tv-controls .field-wrap { display: flex; align-items: center; gap: 0.5rem; }
    .tv-controls .field-wrap label { font-size: 0.9375rem; }
    .tv-grid { display: grid; grid-template-columns: repeat(auto-fill, 120px); gap: 0.75rem; justify-content: start; }
    .tv-result-card { background: var(--surface); border: 1px solid var(--border); padding: 0.75rem; text-align: center; box-shadow: var(--shadow); width: 120px; box-sizing: border-box; }
    .tv-result-card .stage-image-wrapper { position: relative; width: 90px; height: 90px; margin: 0 auto; flex-shrink: 0; }
    .tv-result-card .stage-image-wrapper .trinket-background { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1; }
    .tv-result-card .stage-image-wrapper img:not(.trinket-background) { position: relative; width: 100%; height: 100%; object-fit: contain; z-index: 2; }
    .tv-result-card .creature-name { font-size: 0.75rem; color: var(--text-secondary); margin: 0.35rem 0 0 0; line-height: 1.2; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    .tv-result-card { cursor: pointer; }
    .tv-result-card:hover { border-color: var(--accent); }
    .searchable-wrap { position: relative; max-width: 18rem; }
    .searchable-wrap input[type=text] { width: 100%; padding: 0.4rem 0.6rem; font-size: 0.9375rem; border: 1px solid var(--border); border-radius: var(--radius-sm); box-sizing: border-box; }
    .searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; max-height: 10rem; overflow-y: auto; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow); z-index: 10; display: none; margin-top: 2px; }
    .searchable-dropdown.show { display: block; }
    .searchable-dropdown [data-slug] { display: block; padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.9375rem; }
    .searchable-dropdown [data-slug]:hover { background: var(--accent-muted); }
    .tv-empty { color: var(--text-secondary); }
    .saved-combos { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); }
    .saved-combos h3 { font-size: 1.1rem; margin-bottom: 0.5rem; }
    .saved-combos-grid { display: flex; flex-wrap: wrap; gap: 0.75rem; }
    .saved-combo-card { position: relative; width: 120px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.5rem; box-shadow: var(--shadow); }
    .saved-combo-card .btn-remove { position: absolute; top: 0.25rem; right: 0.25rem; width: 1.25rem; height: 1.25rem; padding: 0; font-size: 1rem; line-height: 1; border: none; background: var(--surface-elevated); color: var(--text-secondary); cursor: pointer; border-radius: 50%; z-index: 3; }
    .saved-combo-card .btn-remove:hover { background: var(--danger-muted); color: var(--danger); }
    .saved-combo-card .thumb { position: relative; width: 90px; height: 90px; margin: 0 auto; }
    .saved-combo-card .thumb .bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1; }
    .saved-combo-card .thumb .fg { position: relative; width: 100%; height: 100%; object-fit: contain; z-index: 2; }
    .saved-combo-card .label { font-size: 0.7rem; margin: 0.35rem 0 0 0; line-height: 1.2; }
</style>

<div class="tv-controls">
    <div class="field-wrap">
        <label>Travels:</label>
        <div class="searchable-wrap" id="travel-wrap">
            <input type="text" id="travel-input" autocomplete="off" placeholder="Type to search...">
            <div class="searchable-dropdown" id="travel-dropdown"></div>
        </div>
    </div>
    <div class="field-wrap">
        <label>Trinket travels:</label>
        <div class="searchable-wrap" id="trinket-travel-wrap">
            <input type="text" id="trinket-travel-input" autocomplete="off" placeholder="Type to search...">
            <div class="searchable-dropdown" id="trinket-travel-dropdown"></div>
        </div>
    </div>
    <input type="hidden" id="travel-slug" value="">
    <div class="stage-tabs" id="stage-tabs" role="tablist"></div>
</div>

<p class="text-secondary small mt-2">Click any creature+travel card below to save that combination. Saved list is stored in your browser and shared with the other travel viewer pages.</p>

<div id="grid-container" class="tv-grid" style="display: none;"></div>
<p class="tv-empty" id="no-selection-msg">Choose a travel above to see every creature with it.</p>

<section class="saved-combos" id="saved-combos">
    <h3>Saved combinations</h3>
    <p id="saved-none" class="tv-empty">No saved combinations yet. Click any creature+travel card above to save it.</p>
    <div id="saved-combos-grid" class="saved-combos-grid" style="display: none;"></div>
</section>

<script>
(function() {
    var creatures = @json($creaturesForJs);
    var travels = @json($travelsForJs);
    var trinketTravels = @json($trinketTravelsForJs);
    var allTravels = @json($allTravelsForJs);

    var travelInput = document.getElementById('travel-input');
    var trinketTravelInput = document.getElementById('trinket-travel-input');
    var travelSlugEl = document.getElementById('travel-slug');
    var travelDropdown = document.getElementById('travel-dropdown');
    var trinketTravelDropdown = document.getElementById('trinket-travel-dropdown');
    var stageTabs = document.getElementById('stage-tabs');
    var gridContainer = document.getElementById('grid-container');
    var noSelectionMsg = document.getElementById('no-selection-msg');

    var currentStageNumber = 1;

    function getTravel(slug) { return allTravels.find(function(t) { return t.slug === slug; }); }

    function getStageNumbers() {
        var set = {};
        creatures.forEach(function(c) {
            (c.stages || []).forEach(function(s) {
                set[s.stage_number] = true;
            });
        });
        return Object.keys(set).map(Number).sort(function(a, b) { return a - b; });
    }

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

    function renderStageTabs() {
        var stageNumbers = getStageNumbers();
        stageTabs.innerHTML = '';
        stageNumbers.forEach(function(num) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'stage-tab' + (num === currentStageNumber ? ' active' : '');
            btn.setAttribute('role', 'tab');
            btn.setAttribute('data-stage', num);
            btn.textContent = 'Stage ' + num;
            stageTabs.appendChild(btn);
        });
        stageTabs.querySelectorAll('.stage-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentStageNumber = parseInt(btn.getAttribute('data-stage'), 10);
                stageTabs.querySelectorAll('.stage-tab').forEach(function(b) {
                    b.classList.toggle('active', parseInt(b.getAttribute('data-stage'), 10) === currentStageNumber);
                });
                renderGrid();
            });
        });
        if (stageNumbers.length && !stageNumbers.includes(currentStageNumber)) {
            currentStageNumber = stageNumbers[0];
        }
    }

    function renderGrid() {
        var tSlug = travelSlugEl.value;
        if (!tSlug) {
            gridContainer.style.display = 'none';
            noSelectionMsg.style.display = 'block';
            gridContainer.innerHTML = '';
            return;
        }
        var travel = getTravel(tSlug);
        if (!travel) return;

        noSelectionMsg.style.display = 'none';
        gridContainer.style.display = 'grid';
        var travelBg = travel.image_url ? '<img src="' + escapeHtml(travel.image_url) + '" alt="" class="trinket-background" loading="lazy" referrerpolicy="no-referrer">' : '';
        var html = '';
        creatures.forEach(function(creature) {
            var stage = (creature.stages || []).find(function(s) { return s.stage_number === currentStageNumber; });
            if (!stage) return;
            var stageImg = stage.image_url ? '<img src="' + escapeHtml(stage.image_url) + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '';
            html += '<div class="tv-result-card" data-creature-slug="' + escapeHtml(creature.slug) + '" data-creature-title="' + escapeHtml(creature.title) + '" data-stage="' + currentStageNumber + '" data-travel-slug="' + escapeHtml(travel.slug) + '" data-travel-name="' + escapeHtml(travel.name) + '" title="Click to save this combination"><div class="stage-image-wrapper">' + travelBg + stageImg + '</div><p class="creature-name">' + escapeHtml(creature.title) + '</p></div>';
        });
        gridContainer.innerHTML = html || '<p class="tv-empty">No creatures have stage ' + currentStageNumber + '.</p>';
        gridContainer.querySelectorAll('.tv-result-card').forEach(function(card) {
            card.addEventListener('click', function() { saveComboFromCard(card); });
        });
    }

    function getCreature(slug) { return creatures.find(function(c) { return c.slug === slug; }); }

    function setTravelFromTravels() {
        trinketTravelInput.value = '';
        renderGrid();
    }
    function setTravelFromTrinket() {
        travelInput.value = '';
        renderGrid();
    }

    buildSearchable('name', 'slug', travels, travelInput, travelSlugEl, travelDropdown, setTravelFromTravels);
    buildSearchable('name', 'slug', trinketTravels, trinketTravelInput, travelSlugEl, trinketTravelDropdown, setTravelFromTrinket);

    var stageNumbers = getStageNumbers();
    if (stageNumbers.length) currentStageNumber = stageNumbers[0];
    renderStageTabs();

    var SAVED_KEY = 'travel_viewer_saved';
    var savedNone = document.getElementById('saved-none');
    var savedGrid = document.getElementById('saved-combos-grid');

    function getSaved() { try { var raw = localStorage.getItem(SAVED_KEY); return raw ? JSON.parse(raw) : []; } catch (e) { return []; } }
    function setSaved(list) { try { localStorage.setItem(SAVED_KEY, JSON.stringify(list)); } catch (e) {} }
    function saveComboFromCard(card) {
        var list = getSaved();
        list.push({
            c: card.getAttribute('data-creature-slug'),
            ct: card.getAttribute('data-creature-title'),
            t: card.getAttribute('data-travel-slug'),
            tn: card.getAttribute('data-travel-name'),
            s: parseInt(card.getAttribute('data-stage'), 10)
        });
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
            var creature = getCreature(item.c);
            var travel = getTravel(item.t);
            var stage = (creature && creature.stages) ? creature.stages.find(function(s) { return s.stage_number === item.s; }) : null;
            if (!stage && creature && creature.stages && creature.stages[0]) stage = creature.stages[0];
            var stageImg = stage && stage.image_url ? escapeHtml(stage.image_url) : '';
            var travelBg = travel && travel.image_url ? escapeHtml(travel.image_url) : '';
            var card = document.createElement('div');
            card.className = 'saved-combo-card';
            card.innerHTML = '<button type="button" class="btn-remove" aria-label="Remove">×</button><div class="thumb">' +
                (travelBg ? '<img class="bg" src="' + travelBg + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '') +
                (stageImg ? '<img class="fg" src="' + stageImg + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '') +
                '</div><p class="label">' + escapeHtml(item.ct) + ' + ' + escapeHtml(item.tn) + (item.s != null ? ' (Stage ' + item.s + ')' : '') + '</p>';
            card.querySelector('.btn-remove').addEventListener('click', function() { removeSaved(i); });
            savedGrid.appendChild(card);
        });
    }

    var firstTravel = travels[0] || trinketTravels[0];
    if (firstTravel) {
        travelSlugEl.value = firstTravel.slug;
        if (travels.some(function(t) { return t.slug === firstTravel.slug; })) travelInput.value = firstTravel.name; else trinketTravelInput.value = firstTravel.name;
    }
    renderGrid();
    renderSaved();
})();
</script>
@endsection
