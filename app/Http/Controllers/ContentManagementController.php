<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\ArchiveStage;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContentManagementController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard');
    }

    public function indexCreatures()
    {
        $creatures = ArchiveItem::orderBy('title')->paginate(100, ['id', 'title', 'slug', 'created_at']);

        return view('content.creatures.index', ['creatures' => $creatures]);
    }

    public function indexItems()
    {
        $items = Item::orderBy('name')->paginate(100, ['id', 'name', 'slug', 'use', 'created_at']);

        return view('content.items.index', ['items' => $items]);
    }

    // --- Creatures ---

    public function createCreature()
    {
        return view('content.creatures.create');
    }

    public function storeCreature(Request $request)
    {
        $valid = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:archive_items,slug'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'source_url' => ['nullable', 'string', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'availability' => ['nullable', 'string', 'max:255'],
            'dates' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'string', 'max:255'],
            'length' => ['nullable', 'string', 'max:255'],
            'obtained_from' => ['nullable', 'string', 'max:255'],
            'gender_profile' => ['nullable', 'string', 'max:255'],
            'habitat' => ['nullable', 'string', 'max:255'],
            'about_eggs' => ['nullable', 'string'],
            'about_creature' => ['nullable', 'string'],
            'entry_written_by' => ['nullable', 'string', 'max:255'],
            'design_concept_user' => ['nullable', 'string', 'max:255'],
            'cdwc_entry_by' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:1000'],
        ]);

        $valid['slug'] = $valid['slug'] ?? Str::slug($valid['title']);
        $valid['tags'] = isset($valid['tags']) && $valid['tags'] !== ''
            ? array_map('trim', explode(',', $valid['tags']))
            : null;

        $creature = ArchiveItem::create($valid);

        return redirect()->route('archive.show', $creature->slug)->with('success', 'Creature added.');
    }

    public function editCreature(ArchiveItem $archiveItem)
    {
        return view('content.creatures.edit', ['creature' => $archiveItem]);
    }

    public function updateCreature(Request $request, ArchiveItem $archiveItem)
    {
        $valid = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('archive_items', 'slug')->ignore($archiveItem->id)],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'source_url' => ['nullable', 'string', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'availability' => ['nullable', 'string', 'max:255'],
            'dates' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'string', 'max:255'],
            'length' => ['nullable', 'string', 'max:255'],
            'obtained_from' => ['nullable', 'string', 'max:255'],
            'gender_profile' => ['nullable', 'string', 'max:255'],
            'habitat' => ['nullable', 'string', 'max:255'],
            'about_eggs' => ['nullable', 'string'],
            'about_creature' => ['nullable', 'string'],
            'entry_written_by' => ['nullable', 'string', 'max:255'],
            'design_concept_user' => ['nullable', 'string', 'max:255'],
            'cdwc_entry_by' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:1000'],
        ]);

        $valid['tags'] = isset($valid['tags']) && $valid['tags'] !== ''
            ? array_map('trim', explode(',', $valid['tags']))
            : null;

        $archiveItem->update($valid);

        // Sync stages: update existing, create new, delete removed
        $stagesInput = $request->input('stages', []);
        $keptIds = [];
        $sortOrder = 0;
        foreach ($stagesInput as $row) {
            $stageNumber = isset($row['stage_number']) ? (int) $row['stage_number'] : 0;
            $imageUrl = isset($row['image_url']) ? trim($row['image_url']) : '';
            $requirement = isset($row['requirement']) ? trim($row['requirement']) : null;
            // Skip empty rows (no image URL)
            if ($imageUrl === '') {
                continue;
            }
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            if ($id > 0) {
                $stage = ArchiveStage::where('id', $id)->where('archive_item_id', $archiveItem->id)->first();
                if ($stage) {
                    $stage->update([
                        'stage_number' => $stageNumber ?: 1,
                        'image_url' => $imageUrl,
                        'requirement' => $requirement ?: null,
                        'sort_order' => $sortOrder++,
                    ]);
                    $keptIds[] = $stage->id;
                }
            } else {
                $stage = ArchiveStage::create([
                    'archive_item_id' => $archiveItem->id,
                    'stage_number' => $stageNumber ?: ($sortOrder + 1),
                    'image_url' => $imageUrl,
                    'requirement' => $requirement ?: null,
                    'sort_order' => $sortOrder++,
                ]);
                $keptIds[] = $stage->id;
            }
        }
        // Delete stages that were removed from the form
        ArchiveStage::where('archive_item_id', $archiveItem->id)->whereNotIn('id', $keptIds)->delete();

        return redirect()->route('archive.show', $archiveItem->slug)->with('success', 'Creature updated.');
    }

    public function destroyCreature(ArchiveItem $archiveItem)
    {
        $archiveItem->delete();
        return redirect()->route('content.creature.index')->with('success', 'Creature removed.');
    }

    // --- Items ---

    public function createItem()
    {
        return view('content.items.create');
    }

    public function storeItem(Request $request)
    {
        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:items,slug'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'source_url' => ['nullable', 'string', 'max:2048'],
            'rarity' => ['nullable', 'string', 'max:255'],
            'use' => ['nullable', 'string', 'max:255'],
            'associated_shop' => ['nullable', 'string', 'max:255'],
            'restock_price' => ['nullable', 'string', 'max:255'],
            'is_retired' => ['nullable', 'boolean'],
            'is_cavecash' => ['nullable', 'boolean'],
            'first_appeared' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $valid['slug'] = $valid['slug'] ?? Str::slug($valid['name']);
        $valid['sort_order'] = $valid['sort_order'] ?? 0;
        $valid['is_retired'] = $request->boolean('is_retired');
        $valid['is_cavecash'] = $request->boolean('is_cavecash');

        $item = Item::create($valid);

        return redirect()->route('items.show', $item->slug)->with('success', 'Item added.');
    }

    public function editItem(Item $item)
    {
        return view('content.items.edit', ['item' => $item]);
    }

    public function updateItem(Request $request, Item $item)
    {
        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('items', 'slug')->ignore($item->id)],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'source_url' => ['nullable', 'string', 'max:2048'],
            'rarity' => ['nullable', 'string', 'max:255'],
            'use' => ['nullable', 'string', 'max:255'],
            'associated_shop' => ['nullable', 'string', 'max:255'],
            'restock_price' => ['nullable', 'string', 'max:255'],
            'is_retired' => ['nullable', 'boolean'],
            'is_cavecash' => ['nullable', 'boolean'],
            'first_appeared' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $valid['is_retired'] = $request->boolean('is_retired');
        $valid['is_cavecash'] = $request->boolean('is_cavecash');

        $item->update($valid);

        return redirect()->route('items.show', $item->slug)->with('success', 'Item updated.');
    }

    public function destroyItem(Item $item)
    {
        $item->delete();
        return redirect()->route('content.item.index')->with('success', 'Item removed.');
    }
}
