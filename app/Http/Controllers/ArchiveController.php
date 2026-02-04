<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\Item;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $query = ArchiveItem::query();

        // Search: title or description
        if ($search = $request->filled('q') ? $request->q : null) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by tag
        if ($tag = $request->filled('tag') ? $request->tag : null) {
            $query->whereJsonContains('tags', $tag);
        }

        // Sort
        $sort = $request->get('sort', 'title');
        $dir = $request->get('dir', 'asc');
        if (!in_array($dir, ['asc', 'desc'])) {
            $dir = 'asc';
        }
        $allowedSort = ['title', 'published_at', 'sort_order', 'created_at'];
        if (in_array($sort, $allowedSort)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('title', 'asc');
        }

        $items = $query->paginate(30)->withQueryString(); // 6 rows × 5 columns = 30 items

        return view('archive.index', [
            'items' => $items,
            'search' => $request->get('q'),
            'tag' => $request->get('tag'),
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function show(string $slug)
    {
        $item = ArchiveItem::where('slug', $slug)->with(['images', 'stages.travelSuggestions.item'])->firstOrFail();
        
        // Find trinket travels for this creature's stages
        // Pattern: "{CreatureName} Stage {Number}" like "Kittynk Stage 3" or "13 Prints Kittynk Stage 3"
        $creatureName = $item->title;
        $trinketTravels = [];
        $recommendedTravels = [];
        
        foreach ($item->stages as $stage) {
            // Look for items matching "{creatureName} Stage {stageNumber}" (case-insensitive, with or without prefix)
            // Pattern: "%Kittynk Stage 3%" or "%kittynk stage 3%"
            $pattern = '%' . $creatureName . ' Stage ' . $stage->stage_number . '%';
            $trinketItem = Item::where('use', 'travel')
                ->whereRaw('LOWER(name) LIKE ?', [strtolower($pattern)])
                ->first();
            
            if ($trinketItem) {
                $trinketTravels[$stage->id] = $trinketItem;
                // Add to recommended travels if not already added (by slug to avoid duplicates)
                if (!isset($recommendedTravels[$trinketItem->slug])) {
                    $recommendedTravels[$trinketItem->slug] = $trinketItem;
                }
            }

            // Add suggested travels for this stage
            foreach ($stage->travelSuggestions as $suggestion) {
                $suggestedTravel = $suggestion->item;
                if ($suggestedTravel && !isset($recommendedTravels[$suggestedTravel->slug])) {
                    $recommendedTravels[$suggestedTravel->slug] = $suggestedTravel;
                }
            }
        }
        
        return view('archive.show', [
            'item' => $item,
            'trinketTravels' => $trinketTravels,
            'recommendedTravels' => array_values($recommendedTravels),
        ]);
    }

    /**
     * Creature travel viewer: one creature, switch stages at top, pick any travel (no reload).
     */
    public function creatureTravelViewer(string $slug)
    {
        $item = ArchiveItem::where('slug', $slug)->with(['images', 'stages'])->firstOrFail();
        $travels = Item::whereRaw('LOWER(use) = ?', ['travel'])->orderBy('name')->get(['id', 'name', 'slug', 'image_url']);

        $stagesForJs = $item->stages->map(fn ($s) => [
            'id' => $s->id,
            'stage_number' => $s->stage_number,
            'requirement' => $s->requirement,
            'image_url' => $s->image_url,
        ])->values()->all();

        $travelsForJs = $travels->map(fn ($t) => [
            'slug' => $t->slug,
            'name' => $t->name,
            'image_url' => $t->image_url,
        ])->values()->all();

        return view('archive.creature-travels', [
            'creature' => $item,
            'travels' => $travels,
            'stagesForJs' => $stagesForJs,
            'travelsForJs' => $travelsForJs,
        ]);
    }
}
