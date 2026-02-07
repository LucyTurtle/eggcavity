<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\PendingTravelSuggestion;
use App\Models\TravelSuggestion;
use Illuminate\Http\Request;

class PendingTravelSuggestionsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(5, min(50, $perPage));

        $creaturesPage = ArchiveItem::query()
            ->whereIn('id', PendingTravelSuggestion::select('archive_item_id'))
            ->with([
                'stages',
                'pendingTravelSuggestions' => fn ($q) => $q->with('item')->orderBy('sort_order'),
            ])
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();

        return view('content.pending-travel-suggestions.index', [
            'creaturesPage' => $creaturesPage,
        ]);
    }

    public function approve(PendingTravelSuggestion $pendingTravelSuggestion)
    {
        $creature = $pendingTravelSuggestion->archiveItem;
        if (! $creature) {
            return redirect()->back()
                ->with('error', 'Creature not found.');
        }

        $itemId = $pendingTravelSuggestion->item_id;
        $maxOrder = TravelSuggestion::where('archive_item_id', $creature->id)->max('sort_order') ?? -1;

        TravelSuggestion::firstOrCreate(
            [
                'archive_item_id' => $creature->id,
                'item_id' => $itemId,
            ],
            [
                'sort_order' => $maxOrder + 1,
            ]
        );

        $pendingTravelSuggestion->delete();

        return redirect()->back()
            ->with('success', "Approved: {$creature->title} → " . $pendingTravelSuggestion->item->name . '.');
    }

    public function reject(PendingTravelSuggestion $pendingTravelSuggestion)
    {
        $pendingTravelSuggestion->delete();

        return redirect()->back()
            ->with('success', 'Suggestion rejected and removed.');
    }
}
