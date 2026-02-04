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
        $travels = Item::whereRaw('LOWER(use) = ?', ['travel'])->orderBy('name')->get(['id', 'name', 'slug', 'image_url']);

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

        return [
            'creatures' => $creatures,
            'travels' => $travels,
            'creaturesForJs' => $creaturesForJs,
            'travelsForJs' => $travelsForJs,
        ];
    }

    /**
     * Simple travel viewer: see travel on all stages. Result above selectors; default first creature + travel; searchable dropdowns.
     */
    public function index(Request $request)
    {
        $data = $this->travelViewerData();
        $creatures = $data['creatures'];
        $travels = $data['travels'];
        $firstCreature = $creatures->first();
        $firstTravel = $travels->first();

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
