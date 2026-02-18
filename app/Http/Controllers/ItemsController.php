<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemsController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query();

        // Search: name or description
        if ($search = $request->filled('q') ? $request->q : null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by shop
        if ($shop = $request->filled('shop') ? $request->shop : null) {
            $query->where('associated_shop', $shop);
        }

        // Filter by use type (item/travel/other)
        if ($useType = $request->filled('use_type') ? $request->use_type : null) {
            if ($useType === 'travel') {
                $query->where('use', 'like', '%Travel%');
            } elseif ($useType === 'item') {
                // Items that contain "Item" (like "Food Item") but not "Travel"
                $query->where('use', 'like', '%Item%')
                    ->where('use', 'not like', '%Travel%');
            } elseif ($useType === 'other') {
                // Items that don't have use, or use doesn't contain Travel/Item
                $query->where(function ($q) {
                    $q->whereNull('use')
                        ->orWhere(function ($q2) {
                            $q2->where('use', 'not like', '%Travel%')
                                ->where('use', 'not like', '%Item%');
                        });
                });
            }
        }

        // Filter by tags: retired, cavecash, available (same UI as creature archive tags)
        $selectedTags = array_values(array_filter((array) $request->input('tags', []), fn ($t) => is_string($t) && $t !== ''));
        if (in_array('retired', $selectedTags, true)) {
            $query->where('is_retired', true);
        }
        if (in_array('cavecash', $selectedTags, true)) {
            $query->where('is_cavecash', true);
        }
        if (in_array('available', $selectedTags, true)) {
            $query->where('is_retired', false);
        }

        // Filter by on wishlist (item wishlist for current user)
        if ($request->filled('on_wishlist') && $request->user()) {
            $query->whereHas('itemWishlists', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        // Sort: name, first appeared, date added, or price (restock_price)
        $sort = $request->get('sort', 'name');
        $dir = $request->get('dir', 'asc');
        if (! in_array($dir, ['asc', 'desc'])) {
            $dir = 'asc';
        }
        $driver = $query->getConnection()->getDriverName();
        $priceExpr = $driver === 'sqlite'
            ? "CAST(TRIM(REPLACE(REPLACE(REPLACE(COALESCE(restock_price, '0'), ',', ''), ' EC', ''), ' ', '')) AS INTEGER)"
            : "CAST(TRIM(REPLACE(REPLACE(REPLACE(COALESCE(restock_price, '0'), ',', ''), ' EC', ''), ' ', '')) AS UNSIGNED)";
        $allowedSort = ['name', 'first_appeared', 'created_at', 'restock_price'];
        if ($sort === 'restock_price') {
            $query->orderByRaw("({$priceExpr}) {$dir}")->orderBy('name', 'asc');
        } elseif (in_array($sort, ['name', 'first_appeared', 'created_at'])) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('name', 'asc');
        }

        $items = $query->paginate(30)->withQueryString();

        // Get unique shops for filter
        $shops = Item::whereNotNull('associated_shop')
            ->distinct()
            ->orderBy('associated_shop')
            ->pluck('associated_shop')
            ->toArray();

        $itemFilterTagOptions = [
            ['value' => 'retired', 'label' => 'Retired only'],
            ['value' => 'cavecash', 'label' => 'Cave cash only'],
            ['value' => 'available', 'label' => 'Available only'],
        ];

        return view('items.index', [
            'items' => $items,
            'search' => $request->get('q'),
            'shop' => $request->get('shop'),
            'use_type' => $request->get('use_type'),
            'on_wishlist_filter' => $request->filled('on_wishlist') && $request->user(),
            'selectedTags' => $selectedTags ?? [],
            'itemFilterTagOptions' => $itemFilterTagOptions,
            'sort' => $sort,
            'dir' => $dir,
            'shops' => $shops,
        ]);
    }

    public function show(string $slug)
    {
        $item = Item::where('slug', $slug)->firstOrFail();
        
        // Find associated creature for trinket travel items
        // Pattern: "{CreatureName} Stage {Number}" or "{Number} Prints {CreatureName} Stage {Number}"
        $associatedCreature = null;
        if ($item->use === 'travel') {
            $itemName = $item->name;
            
            // Try to extract creature name from patterns like:
            // "Kittynk Stage 2" -> "Kittynk"
            // "13 Prints Kittynk Stage 2" -> "Kittynk"
            if (preg_match('/(?:^\d+\s+Prints\s+)?([A-Za-z0-9_]+)\s+Stage\s+\d+/i', $itemName, $matches)) {
                $creatureName = trim($matches[1]);
                
                // Try to find archive item by title (case-insensitive)
                $associatedCreature = ArchiveItem::whereRaw('LOWER(title) = ?', [strtolower($creatureName)])
                    ->first();
            }
        }
        
        $user = request()->user();
        $canEdit = $user && ($user->hasRole('admin') || $user->hasRole('developer') || $user->hasRole('content_manager'));
        $onWishlist = $user && $item->itemWishlists()->where('user_id', $user->id)->exists();

        return view('items.show', [
            'item' => $item,
            'associatedCreature' => $associatedCreature,
            'canEdit' => $canEdit,
            'onWishlist' => $onWishlist,
        ]);
    }

    /**
     * Reverse travel viewer: this travel on any creature / any stage; switch creature and stage at top (no reload).
     */
    public function travelOnCreaturesViewer(string $slug)
    {
        $item = Item::where('slug', $slug)->firstOrFail();
        if (! $item->isTravel()) {
            abort(404, 'This item is not a travel.');
        }

        $creatures = ArchiveItem::with('stages')->orderBy('title')->get();
        $creaturesForJs = $creatures->map(fn ($c) => [
            'slug' => $c->slug,
            'title' => $c->title,
            'stages' => $c->stages->map(fn ($s) => [
                'stage_number' => $s->stage_number,
                'requirement' => $s->requirement,
                'image_url' => $s->image_url,
            ])->values()->all(),
        ])->values()->all();

        return view('items.travel-on-creatures', [
            'travel' => $item,
            'creatures' => $creatures,
            'creaturesForJs' => $creaturesForJs,
        ]);
    }
}
