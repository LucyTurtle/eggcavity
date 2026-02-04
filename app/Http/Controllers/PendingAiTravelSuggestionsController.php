<?php

namespace App\Http\Controllers;

use App\Models\PendingAiTravelSuggestion;
use App\Models\TravelSuggestion;
use Illuminate\Http\Request;

class PendingAiTravelSuggestionsController extends Controller
{
    public function index()
    {
        $pending = PendingAiTravelSuggestion::with(['archiveItem.stages', 'item'])
            ->orderBy('archive_item_id')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('archive_item_id');

        return view('content.pending-ai-travel-suggestions.index', [
            'pendingByCreature' => $pending,
        ]);
    }

    public function approve(PendingAiTravelSuggestion $pendingAiTravelSuggestion)
    {
        $creature = $pendingAiTravelSuggestion->archiveItem;
        if (! $creature) {
            return redirect()->route('content.pending-ai-travel-suggestions.index')
                ->with('error', 'Creature not found.');
        }

        $stages = $creature->stages;
        $itemId = $pendingAiTravelSuggestion->item_id;

        foreach ($stages as $stage) {
            $maxOrder = TravelSuggestion::where('archive_stage_id', $stage->id)->max('sort_order') ?? -1;
            TravelSuggestion::create([
                'archive_stage_id' => $stage->id,
                'item_id' => $itemId,
                'sort_order' => $maxOrder + 1,
            ]);
        }

        $pendingAiTravelSuggestion->delete();

        return redirect()->route('content.pending-ai-travel-suggestions.index')
            ->with('success', "Approved: {$creature->title} → " . $pendingAiTravelSuggestion->item->name . '. Added to all stages.');
    }

    public function reject(PendingAiTravelSuggestion $pendingAiTravelSuggestion)
    {
        $pendingAiTravelSuggestion->delete();

        return redirect()->route('content.pending-ai-travel-suggestions.index')
            ->with('success', 'Suggestion rejected and removed.');
    }
}
