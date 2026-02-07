<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\Item;
use Illuminate\Http\Request;

class TravelViewerController extends Controller
{
    private function travelViewerData(): array
    {
        $creatures = ArchiveItem::with('stages')->orderBy('title')->get();
        $availableOnly = request()->filled('available');
        $travelsQuery = Item::whereRaw('LOWER(use) = ?', ['travel']);
        if ($availableOnly) {
            $travelsQuery->where('is_retired', false)->where('is_cavecash', false);
        }
        $allTravels = $travelsQuery->get(['id', 'name', 'slug', 'image_url']);

        $travels = $allTravels->filter(fn ($t) => ! $this->isTrinketTravel($t->name));
        $travels = $travels->sortBy('name', SORT_NATURAL)->values();

        $trinketTravels = $allTravels->filter(fn ($t) => $this->isTrinketTravel($t->name));
        $trinketTravels = $trinketTravels->sort(function ($a, $b) {
            [$keyA, $keyB] = [$this->trinketTravelSortKey($a->name), $this->trinketTravelSortKey($b->name)];
            return strcasecmp($keyA, $keyB);
        })->values();

        $creaturesForJs = $creatures->map(fn ($c) => [
            'slug' => $c->slug,
            'title' => $c->title,
            'stages' => $c->stages->map(fn ($s) => [
                'stage_number' => $s->stage_number,
                'requirement' => $s->requirement,
                'image_url' => $s->image_url,
            ])->values()->all(),
        ])->values()->all();

        $travelsForJs = $travels->map(fn ($t) => [
            'slug' => $t->slug,
            'name' => $t->name,
            'image_url' => $t->image_url,
        ])->values()->all();

        $trinketTravelsForJs = $trinketTravels->map(fn ($t) => [
            'slug' => $t->slug,
            'name' => $t->name,
            'image_url' => $t->image_url,
        ])->values()->all();

        $allTravelsForJs = array_merge($travelsForJs, $trinketTravelsForJs);

        return [
            'creatures' => $creatures,
            'travels' => $travels,
            'trinketTravels' => $trinketTravels,
            'creaturesForJs' => $creaturesForJs,
            'travelsForJs' => $travelsForJs,
            'trinketTravelsForJs' => $trinketTravelsForJs,
            'allTravelsForJs' => $allTravelsForJs,
            'filterAvailable' => $availableOnly,
        ];
    }

    private function isTrinketTravel(string $name): bool
    {
        return (bool) preg_match('/\s+Stage\s+\d+/i', $name);
    }

    private function trinketTravelSortKey(string $name): string
    {
        if (preg_match('/^(.+?)\s+Stage\s+(\d+)\s*$/i', $name, $m)) {
            $creaturePart = trim($m[1]);
            $stageNum = (int) $m[2];
            return $creaturePart . ' Stage ' . str_pad((string) $stageNum, 4, '0', STR_PAD_LEFT);
        }
        return $name;
    }

    /**
     * Simple travel viewer: see travel on all stages. Result above selectors; default first creature + travel; searchable dropdowns.
     */
    public function index(Request $request)
    {
        $data = $this->travelViewerData();
        $creatures = $data['creatures'];
        $travels = $data['travels'];
        $trinketTravels = $data['trinketTravels'];
        $firstCreature = $creatures->first();
        $firstTravel = $travels->first() ?? $trinketTravels->first();

        return view('travel-viewer.index', array_merge($data, [
            'initialCreature' => $request->get('creature') ?: ($firstCreature ? $firstCreature->slug : null),
            'initialTravel' => $request->get('travel') ?: ($firstTravel ? $firstTravel->slug : null),
        ]));
    }

    /**
     * By-creature viewer: dropdowns for creature + travel, stage tabs, one composite. No refresh.
     */
    public function byCreature()
    {
        return view('travel-viewer.by-creature', $this->travelViewerData());
    }

    /**
     * By-travel viewer: dropdowns for travel + creature, stage tabs, one composite. No refresh.
     */
    public function byTravel()
    {
        return view('travel-viewer.by-travel', $this->travelViewerData());
    }
}
