<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\PendingAiTravelSuggestion;
use App\Models\TravelSuggestion;
use Illuminate\Http\Request;

class PendingAiTravelSuggestionsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(5, min(50, $perPage));

        $creaturesPage = ArchiveItem::query()
            ->whereIn('id', PendingAiTravelSuggestion::select('archive_item_id'))
            ->with([
                'stages',
                'pendingAiTravelSuggestions' => fn ($q) => $q->with('item')->orderBy('sort_order'),
            ])
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();

        return view('content.pending-ai-travel-suggestions.index', [
            'creaturesPage' => $creaturesPage,
        ]);
    }

    public function approve(PendingAiTravelSuggestion $pendingAiTravelSuggestion)
    {
        $creature = $pendingAiTravelSuggestion->archiveItem;
        if (! $creature) {
            return redirect()->back()
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

        return redirect()->back()
            ->with('success', "Approved: {$creature->title} → " . $pendingAiTravelSuggestion->item->name . '. Added to all stages.');
    }

    public function reject(PendingAiTravelSuggestion $pendingAiTravelSuggestion)
    {
        $pendingAiTravelSuggestion->delete();

        return redirect()->back()
            ->with('success', 'Suggestion rejected and removed.');
    }
}
