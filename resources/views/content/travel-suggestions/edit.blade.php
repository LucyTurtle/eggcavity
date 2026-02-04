@extends('layouts.app')

@section('title', 'Edit travel suggestion')

@section('content')
<div class="page-header">
    <h1>Edit travel suggestion</h1>
    <p class="lead"><a href="{{ route('content.travel-suggestions.index') }}">← Back to travel suggestions</a></p>
</div>

<style>
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .form-group select, .form-group textarea, .form-group input { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; }
    .form-group textarea { min-height: 6rem; resize: vertical; }
    .form-group small { display: block; font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }
    .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .btn-submit:hover { background: var(--accent-hover); }
</style>

<div class="card">
    <form method="post" action="{{ route('content.travel-suggestions.update', $suggestion) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="creature">Creature *</label>
            <select name="creature" id="creature" required onchange="updateStages()">
                <option value="">— Choose creature —</option>
                @foreach($creatures as $creature)
                    <option value="{{ $creature->id }}" {{ $creature->id === $suggestion->archiveStage->archiveItem->id ? 'selected' : '' }}>
                        {{ $creature->title }}
                    </option>
                @endforeach
            </select>
            @error('creature')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="archive_stage_id">Stage *</label>
            <select name="archive_stage_id" id="archive_stage_id" required>
                <option value="">— Choose stage —</option>
            </select>
            @error('archive_stage_id')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="item_id">Travel *</label>
            <select name="item_id" id="item_id" required>
                <option value="">— Choose travel —</option>
                @foreach($travels as $travel)
                    <option value="{{ $travel->id }}" {{ $travel->id === $suggestion->item_id ? 'selected' : '' }}>
                        {{ $travel->name }}
                    </option>
                @endforeach
            </select>
            @error('item_id')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" placeholder="Optional notes about this suggestion">{{ old('notes', $suggestion->notes) }}</textarea>
            @error('notes')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="sort_order">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $suggestion->sort_order) }}" min="0">
            <small>Lower numbers appear first when multiple suggestions exist for the same stage.</small>
            @error('sort_order')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <button type="submit" class="btn-submit">Update suggestion</button>
    </form>
</div>

<script>
var creatures = @json($creatures->map(function($c) { return ['id' => $c->id, 'stages' => $c->stages->map(function($s) { return ['id' => $s->id, 'stage_number' => $s->stage_number]; })]; }));
var currentStageId = {{ $suggestion->archive_stage_id }};

function updateStages() {
    var creatureId = parseInt(document.getElementById('creature').value, 10);
    var stageSelect = document.getElementById('archive_stage_id');
    stageSelect.innerHTML = '<option value="">— Choose stage —</option>';
    if (!creatureId) return;
    var creature = creatures.find(function(c) { return c.id === creatureId; });
    if (creature && creature.stages) {
        creature.stages.forEach(function(stage) {
            var opt = document.createElement('option');
            opt.value = stage.id;
            opt.textContent = 'Stage ' + stage.stage_number;
            if (stage.id === currentStageId) opt.selected = true;
            stageSelect.appendChild(opt);
        });
    }
}
// Initialize on page load
updateStages();
</script>
@endsection
