<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\Item;
use App\Models\TravelSuggestion;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $query = ArchiveItem::with('images');

        // Search: title or description
        if ($search = $request->filled('q') ? $request->q : null) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by tags (any of the selected tags)
        $selectedTags = array_values(array_filter((array) $request->input('tags', []), fn ($t) => is_string($t) && (string) $t !== ''));
        if (count($selectedTags) > 0) {
            $query->where(function ($q) use ($selectedTags) {
                foreach ($selectedTags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Filter by gender profile
        if ($request->filled('gender_profile')) {
            $query->where('gender_profile', 'like', '%' . $request->gender_profile . '%');
        }

        // Filter by availability (text search)
        if ($request->filled('availability')) {
            $query->where('availability', 'like', '%' . $request->availability . '%');
        }

        // Filter by dates (e.g. year or month text)
        if ($request->filled('dates_filter')) {
            $query->where('dates', 'like', '%' . $request->dates_filter . '%');
        }

        // Filter by number of evolutions (1 = 2 stages, 2 = 3 stages, 3 = 4 stages)
        $evolutions = $request->integer('evolutions', 0);
        if ($evolutions >= 1 && $evolutions <= 3) {
            $stageCount = $evolutions + 1;
            $query->withCount('stages')->having('stages_count', '=', $stageCount);
        }

        // Filter by habitat
        if ($request->filled('habitat')) {
            $query->where('habitat', 'like', '%' . $request->habitat . '%');
        }

        // Filter by evolves by stat (stage requirement contains Views, Clicks, or Feeds)
        $allowedStats = ['views', 'clicks', 'feeds'];
        $evolvesByStat = $request->get('evolves_by_stat');
        if (is_string($evolvesByStat) && in_array(strtolower($evolvesByStat), $allowedStats, true)) {
            $stat = strtolower($evolvesByStat);
            $query->whereHas('stages', function ($q) use ($stat) {
                $q->where('requirement', 'like', '%' . $stat . '%');
            });
        }

        // Sort: title or date added (created_at when we added to DB)
        $sort = $request->get('sort', 'title');
        $dir = $request->get('dir', 'asc');
        if (! in_array($dir, ['asc', 'desc'])) {
            $dir = 'asc';
        }
        $allowedSort = ['title', 'created_at'];
        if (in_array($sort, $allowedSort)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('title', 'asc');
        }

        $items = $query->paginate(30)->withQueryString();

        // Distinct values for filter dropdowns
        $genderProfiles = ArchiveItem::whereNotNull('gender_profile')
            ->where('gender_profile', '!=', '')
            ->distinct()
            ->orderBy('gender_profile')
            ->pluck('gender_profile')
            ->toArray();

        $availabilities = ArchiveItem::whereNotNull('availability')
            ->where('availability', '!=', '')
            ->distinct()
            ->orderBy('availability')
            ->pluck('availability')
            ->toArray();

        $habitats = ArchiveItem::whereNotNull('habitat')
            ->where('habitat', '!=', '')
            ->distinct()
            ->orderBy('habitat')
            ->pluck('habitat')
            ->toArray();

        // Distinct tags from all creatures (tags stored as JSON array)
        $allTags = ArchiveItem::whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        return view('archive.index', [
            'items' => $items,
            'search' => $request->get('q'),
            'selectedTags' => $selectedTags ?? [],
            'gender_profile' => $request->get('gender_profile'),
            'availability_filter' => $request->get('availability'),
            'dates_filter' => $request->get('dates_filter'),
            'evolutions_filter' => $evolutions >= 1 && $evolutions <= 3 ? $evolutions : null,
            'habitat_filter' => $request->filled('habitat') ? $request->habitat : null,
            'evolves_by_stat_filter' => is_string($evolvesByStat) && in_array(strtolower($evolvesByStat), $allowedStats, true) ? strtolower($evolvesByStat) : null,
            'sort' => $sort,
            'dir' => $dir,
            'genderProfiles' => $genderProfiles,
            'availabilities' => $availabilities,
            'habitats' => $habitats,
            'tags' => $allTags,
        ]);
    }

    public function show(string $slug)
    {
        $item = ArchiveItem::where('slug', $slug)->with(['images', 'stages.travelSuggestions.item'])->firstOrFail();
        [$trinketTravels, $recommendedTravels] = $this->computeRecommendedTravels($item);

        $user = request()->user();
        $canApplyRecommendations = $user && ($user->hasRole('admin') || $user->hasRole('developer'));

        return view('archive.show', [
            'item' => $item,
            'trinketTravels' => $trinketTravels,
            'recommendedTravels' => $recommendedTravels,
            'canApplyRecommendations' => $canApplyRecommendations,
        ]);
    }

    /**
     * Apply the current recommended travels (trinket + manual suggestions) as travel_suggestions for every stage.
     * Admin/developer only.
     */
    public function applyRecommendedToAllStages(Request $request, string $slug)
    {
        $item = ArchiveItem::where('slug', $slug)->with(['stages.travelSuggestions.item'])->firstOrFail();
        [$trinketTravels, $recommendedTravels] = $this->computeRecommendedTravels($item);

        $travelIds = $request->input('travel_ids', []);
        if (!is_array($travelIds)) {
            $travelIds = [];
        }
        $travelIds = array_filter(array_map('intval', $travelIds));

        if (empty($travelIds)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No travels selected'], 400);
            }
            return redirect()->route('archive.show', $item->slug);
        }

        $travelIdsSet = array_flip($travelIds);
        $allowedInOrder = [];
        foreach ($recommendedTravels as $t) {
            if (isset($travelIdsSet[$t->id])) {
                $allowedInOrder[] = $t->id;
            }
        }

        foreach ($item->stages as $stage) {
            $stage->travelSuggestions()->delete();
            foreach ($allowedInOrder as $sortOrder => $itemId) {
                TravelSuggestion::create([
                    'archive_stage_id' => $stage->id,
                    'item_id' => $itemId,
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('archive.show', $item->slug);
    }

    /**
     * @return array{0: array<int, \App\Models\Item>, 1: list<\App\Models\Item>}
     */
    private function computeRecommendedTravels(ArchiveItem $item): array
    {
        $creatureName = $item->title;
        $trinketTravels = [];
        $recommendedTravels = [];

        foreach ($item->stages as $stage) {
            $pattern = '%' . $creatureName . ' Stage ' . $stage->stage_number . '%';
            $trinketItem = Item::where('use', 'travel')
                ->whereRaw('LOWER(name) LIKE ?', [strtolower($pattern)])
                ->first();

            if ($trinketItem) {
                $trinketTravels[$stage->id] = $trinketItem;
                if (!isset($recommendedTravels[$trinketItem->slug])) {
                    $recommendedTravels[$trinketItem->slug] = $trinketItem;
                }
            }

            foreach ($stage->travelSuggestions as $suggestion) {
                $suggestedTravel = $suggestion->item;
                if ($suggestedTravel && !isset($recommendedTravels[$suggestedTravel->slug])) {
                    $recommendedTravels[$suggestedTravel->slug] = $suggestedTravel;
                }
            }
        }

        return [$trinketTravels, array_values($recommendedTravels)];
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
