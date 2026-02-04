<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\ArchiveStage;
use App\Models\Item;
use App\Models\TravelSuggestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TravelSuggestionController extends Controller
{
    public function index()
    {
        $suggestions = TravelSuggestion::with(['archiveStage.archiveItem', 'item'])
            ->orderBy('archive_stage_id')
            ->orderBy('sort_order')
            ->paginate(100);

        return view('content.travel-suggestions.index', [
            'suggestions' => $suggestions,
        ]);
    }

    public function create()
    {
        $creatures = ArchiveItem::with('stages')->orderBy('title')->get();
        $travels = Item::whereRaw('LOWER(use) = ?', ['travel'])->orderBy('name')->get(['id', 'name', 'slug']);

        return view('content.travel-suggestions.create', [
            'creatures' => $creatures,
            'travels' => $travels,
        ]);
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'archive_stage_id' => ['required', 'exists:archive_stages,id'],
            'item_id' => ['required', 'exists:items,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [], [
            'archive_stage_id' => 'creature stage',
            'item_id' => 'travel',
        ]);

        // Check if suggestion already exists
        $exists = TravelSuggestion::where('archive_stage_id', $valid['archive_stage_id'])
            ->where('item_id', $valid['item_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['item_id' => 'This travel is already suggested for this stage.']);
        }

        // Verify item is a travel
        $item = Item::findOrFail($valid['item_id']);
        if (!$item->isTravel()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['item_id' => 'Selected item must be a travel item.']);
        }

        $valid['sort_order'] = $valid['sort_order'] ?? 0;
        TravelSuggestion::create($valid);

        return redirect()->route('content.travel-suggestions.index')
            ->with('success', 'Travel suggestion added.');
    }

    public function edit(TravelSuggestion $travelSuggestion)
    {
        $creatures = ArchiveItem::with('stages')->orderBy('title')->get();
        $travels = Item::whereRaw('LOWER(use) = ?', ['travel'])->orderBy('name')->get(['id', 'name', 'slug']);

        return view('content.travel-suggestions.edit', [
            'suggestion' => $travelSuggestion->load(['archiveStage.archiveItem', 'item']),
            'creatures' => $creatures,
            'travels' => $travels,
        ]);
    }

    public function update(Request $request, TravelSuggestion $travelSuggestion)
    {
        $valid = $request->validate([
            'archive_stage_id' => ['required', 'exists:archive_stages,id'],
            'item_id' => ['required', 'exists:items,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [], [
            'archive_stage_id' => 'creature stage',
            'item_id' => 'travel',
        ]);

        // Check if suggestion already exists (excluding current)
        $exists = TravelSuggestion::where('archive_stage_id', $valid['archive_stage_id'])
            ->where('item_id', $valid['item_id'])
            ->where('id', '!=', $travelSuggestion->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['item_id' => 'This travel is already suggested for this stage.']);
        }

        // Verify item is a travel
        $item = Item::findOrFail($valid['item_id']);
        if (!$item->isTravel()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['item_id' => 'Selected item must be a travel item.']);
        }

        $valid['sort_order'] = $valid['sort_order'] ?? $travelSuggestion->sort_order;
        $travelSuggestion->update($valid);

        return redirect()->route('content.travel-suggestions.index')
            ->with('success', 'Travel suggestion updated.');
    }

    public function destroy(TravelSuggestion $travelSuggestion)
    {
        $travelSuggestion->delete();

        return redirect()->route('content.travel-suggestions.index')
            ->with('success', 'Travel suggestion removed.');
    }
}
