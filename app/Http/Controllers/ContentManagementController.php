<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContentManagementController extends Controller
{
    public function index()
    {
        $creatures = ArchiveItem::orderBy('title')->get(['id', 'title', 'slug', 'created_at']);
        $items = Item::orderBy('name')->get(['id', 'name', 'slug', 'use', 'created_at']);

        return view('content.index', [
            'creatures' => $creatures,
            'items' => $items,
        ]);
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
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $valid['slug'] = $valid['slug'] ?? Str::slug($valid['title']);
        $valid['sort_order'] = $valid['sort_order'] ?? 0;

        ArchiveItem::create($valid);

        return redirect()->route('content.index')->with('success', 'Creature added.');
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
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $archiveItem->update($valid);

        return redirect()->route('content.index')->with('success', 'Creature updated.');
    }

    public function destroyCreature(ArchiveItem $archiveItem)
    {
        $archiveItem->delete();
        return redirect()->route('content.index')->with('success', 'Creature removed.');
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

        Item::create($valid);

        return redirect()->route('content.index')->with('success', 'Item added.');
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

        return redirect()->route('content.index')->with('success', 'Item updated.');
    }

    public function destroyItem(Item $item)
    {
        $item->delete();
        return redirect()->route('content.index')->with('success', 'Item removed.');
    }
}
