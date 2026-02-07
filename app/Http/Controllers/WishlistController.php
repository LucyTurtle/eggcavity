<?php

namespace App\Http\Controllers;

use App\Models\ArchiveItem;
use App\Models\CreatureWishlist;
use App\Models\Item;
use App\Models\ItemWishlist;
use App\Models\TravelWishlist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $creatureWishlists = $user->creatureWishlists()->with('archiveItem.stages')->orderBy('created_at', 'desc')->get();
        $itemWishlists = $user->itemWishlists()->with('item')->orderBy('created_at', 'desc')->get();
        $travelWishlists = $user->travelWishlists()->with('item')->orderBy('created_at', 'desc')->get();

        return view('wishlists.index', [
            'creatureWishlists' => $creatureWishlists,
            'itemWishlists' => $itemWishlists,
            'travelWishlists' => $travelWishlists,
            'shareUrl' => $user->wishlist_share_url,
            'shareCreaturesUrl' => $user->wishlist_share_creatures_url,
            'shareItemsUrl' => $user->wishlist_share_items_url,
            'shareTravelsUrl' => $user->wishlist_share_travels_url,
        ]);
    }

    public function showCreatures()
    {
        $user = Auth::user();
        $creatureWishlists = $user->creatureWishlists()->with('archiveItem.stages')->orderBy('created_at', 'desc')->get();
        return view('wishlists.creatures', [
            'creatureWishlists' => $creatureWishlists,
            'shareCreaturesUrl' => $user->wishlist_share_creatures_url,
        ]);
    }

    public function showItems()
    {
        $user = Auth::user();
        $itemWishlists = $user->itemWishlists()->with('item')->orderBy('created_at', 'desc')->get();
        return view('wishlists.items', [
            'itemWishlists' => $itemWishlists,
            'shareItemsUrl' => $user->wishlist_share_items_url,
        ]);
    }

    public function showTravels()
    {
        $user = Auth::user();
        $travelWishlists = $user->travelWishlists()->with('item')->orderBy('created_at', 'desc')->get();
        return view('wishlists.travels', [
            'travelWishlists' => $travelWishlists,
            'shareTravelsUrl' => $user->wishlist_share_travels_url,
        ]);
    }

    /** Find user by wishlist slug (slug of name). Name is unique so at most one match. */
    private function findOwnerBySlug(string $slug): User
    {
        if (in_array($slug, ['add', 'share', 'creatures', 'items', 'travels'], true)) {
            abort(404);
        }
        $owner = User::query()
            ->get()
            ->first(fn (User $u): bool => Str::slug($u->name) === $slug);

        if (! $owner) {
            abort(404);
        }

        return $owner;
    }

    public function showShared(string $slug)
    {
        $owner = $this->findOwnerBySlug($slug);
        $creatureWishlists = $owner->creatureWishlists()->with('archiveItem.stages')->orderBy('created_at', 'desc')->get();
        $itemWishlists = $owner->itemWishlists()->with('item')->orderBy('created_at', 'desc')->get();
        $travelWishlists = $owner->travelWishlists()->with('item')->orderBy('created_at', 'desc')->get();

        return view('wishlists.shared', [
            'owner' => $owner,
            'creatureWishlists' => $creatureWishlists,
            'itemWishlists' => $itemWishlists,
            'travelWishlists' => $travelWishlists,
        ]);
    }

    public function showSharedCreatures(string $slug)
    {
        $owner = $this->findOwnerBySlug($slug);
        $creatureWishlists = $owner->creatureWishlists()->with('archiveItem.stages')->orderBy('created_at', 'desc')->get();

        return view('wishlists.shared-creatures', [
            'owner' => $owner,
            'creatureWishlists' => $creatureWishlists,
        ]);
    }

    public function showSharedItems(string $slug)
    {
        $owner = $this->findOwnerBySlug($slug);
        $itemWishlists = $owner->itemWishlists()->with('item')->orderBy('created_at', 'desc')->get();

        return view('wishlists.shared-items', [
            'owner' => $owner,
            'itemWishlists' => $itemWishlists,
        ]);
    }

    public function showSharedTravels(string $slug)
    {
        $owner = $this->findOwnerBySlug($slug);
        $travelWishlists = $owner->travelWishlists()->with('item')->orderBy('created_at', 'desc')->get();

        return view('wishlists.shared-travels', [
            'owner' => $owner,
            'travelWishlists' => $travelWishlists,
        ]);
    }

    public function showAddCreatures(Request $request)
    {
        $query = ArchiveItem::with('images');

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $creatures = $query->orderBy('title')->paginate(30)->withQueryString();

        return view('wishlists.add-creatures', [
            'creatures' => $creatures,
            'search' => $request->get('q'),
        ]);
    }

    public function showAddItems(Request $request)
    {
        $items = Item::where(function ($q) {
            $q->whereNull('use')->orWhereRaw('LOWER(use) != ?', ['travel']);
        })->orderBy('name')->paginate(30)->withQueryString();
        return view('wishlists.add-items', ['items' => $items]);
    }

    public function showAddTravels(Request $request)
    {
        $travels = Item::whereRaw('LOWER(use) = ?', ['travel'])->orderBy('name')->paginate(30)->withQueryString();
        return view('wishlists.add-travels', ['travels' => $travels]);
    }

    public function storeCreatures(Request $request)
    {
        $creatures = $request->input('creatures', []);
        $user = Auth::user();
        $added = 0;
        foreach ($creatures as $archiveItemId => $data) {
            $amount = isset($data['amount']) ? (int) $data['amount'] : 0;
            if ($amount < 1) {
                continue;
            }
            $archiveItemId = (int) $archiveItemId;
            if (! ArchiveItem::where('id', $archiveItemId)->exists()) {
                continue;
            }
            $stageNumber = isset($data['stage_number']) && $data['stage_number'] !== '' && $data['stage_number'] !== '0'
                ? min(max((int) $data['stage_number'], 1), 20)
                : null;
            $user->creatureWishlists()->updateOrCreate(
                ['archive_item_id' => $archiveItemId],
                [
                    'amount' => min(max($amount, 1), 9999),
                    'gender' => isset($data['gender']) && in_array($data['gender'], ['male', 'female', 'non-binary', 'no_preference'], true) ? $data['gender'] : 'no_preference',
                    'notes' => isset($data['notes']) ? mb_substr($data['notes'], 0, 2000) : null,
                    'stage_number' => $stageNumber,
                ]
            );
            $added++;
        }
        $redirect = $request->input('redirect');
        if ($redirect && is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect()->to($redirect)->with('success', $added > 0 ? "Added {$added} creature(s). Continuing to next page." : 'No creatures added on this page.');
        }
        return redirect()->back()->with('success', $added > 0 ? "Added {$added} creature(s) to your wishlist." : 'No creatures added (enter at least 1 for amount).');
    }

    public function storeItems(Request $request)
    {
        $items = $request->input('items', []);
        $user = Auth::user();
        $added = 0;
        foreach ($items as $itemId => $data) {
            $amount = isset($data['amount']) ? (int) $data['amount'] : 0;
            if ($amount < 1) {
                continue;
            }
            $itemId = (int) $itemId;
            $item = Item::find($itemId);
            if (! $item || $item->isTravel()) {
                continue;
            }
            $user->itemWishlists()->updateOrCreate(
                ['item_id' => $itemId],
                [
                    'amount' => min(max($amount, 1), 9999),
                    'notes' => isset($data['notes']) ? mb_substr($data['notes'], 0, 2000) : null,
                ]
            );
            $added++;
        }
        $redirect = $request->input('redirect');
        if ($redirect && is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect()->to($redirect)->with('success', $added > 0 ? "Added {$added} item(s). Continuing to next page." : 'No items added on this page.');
        }
        return redirect()->back()->with('success', $added > 0 ? "Added {$added} item(s) to your wishlist." : 'No items added (enter at least 1 for amount).');
    }

    public function storeTravels(Request $request)
    {
        $travels = $request->input('travels', []);
        $user = Auth::user();
        $added = 0;
        foreach ($travels as $itemId => $data) {
            $amount = isset($data['amount']) ? (int) $data['amount'] : 0;
            if ($amount < 1) {
                continue;
            }
            $itemId = (int) $itemId;
            $item = Item::find($itemId);
            if (! $item || ! $item->isTravel()) {
                continue;
            }
            $user->travelWishlists()->updateOrCreate(
                ['item_id' => $itemId],
                [
                    'amount' => min(max($amount, 1), 9999),
                    'notes' => isset($data['notes']) ? mb_substr($data['notes'], 0, 2000) : null,
                ]
            );
            $added++;
        }
        $redirect = $request->input('redirect');
        if ($redirect && is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect()->to($redirect)->with('success', $added > 0 ? "Added {$added} travel(s). Continuing to next page." : 'No travels added on this page.');
        }
        return redirect()->back()->with('success', $added > 0 ? "Added {$added} travel(s) to your wishlist." : 'No travels added (enter at least 1 for amount).');
    }

    public function storeCreature(Request $request)
    {
        $valid = $request->validate([
            'archive_item_id' => ['required', 'exists:archive_items,id'],
            'amount' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non-binary', 'no_preference'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'stage_number' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $stageNumber = isset($valid['stage_number']) && $valid['stage_number'] >= 1 ? (int) $valid['stage_number'] : null;
        $user = Auth::user();
        $user->creatureWishlists()->updateOrCreate(
            ['archive_item_id' => $valid['archive_item_id']],
            [
                'amount' => $valid['amount'] ?? 1,
                'gender' => $valid['gender'] ?? 'no_preference',
                'notes' => $valid['notes'] ?? null,
                'stage_number' => $stageNumber,
            ]
        );

        $redirect = $request->input('redirect');
        if ($redirect && is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect()->to($redirect)->with('success', 'Added to creature wishlist.');
        }

        return redirect()->route('wishlists.index')->with('success', 'Added to creature wishlist.');
    }

    public function storeItem(Request $request)
    {
        $valid = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'amount' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = Item::findOrFail($valid['item_id']);
        if ($item->isTravel()) {
            return back()->with('error', 'Travel items go on the travel wishlist.');
        }

        $user = Auth::user();
        $user->itemWishlists()->updateOrCreate(
            ['item_id' => $valid['item_id']],
            [
                'amount' => $valid['amount'] ?? 1,
                'notes' => $valid['notes'] ?? null,
            ]
        );

        $redirect = $request->input('redirect');
        if ($redirect && is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect()->to($redirect)->with('success', 'Added to item wishlist.');
        }

        return redirect()->route('wishlists.index')->with('success', 'Added to item wishlist.');
    }

    public function storeTravel(Request $request)
    {
        $valid = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'amount' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = Item::findOrFail($valid['item_id']);
        if (! $item->isTravel()) {
            return back()->with('error', 'Only travel items can be added to the travel wishlist.');
        }

        $user = Auth::user();
        $user->travelWishlists()->updateOrCreate(
            ['item_id' => $valid['item_id']],
            [
                'amount' => $valid['amount'] ?? 1,
                'notes' => $valid['notes'] ?? null,
            ]
        );

        $redirect = $request->input('redirect');
        if ($redirect && is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect()->to($redirect)->with('success', 'Added to travel wishlist.');
        }

        return redirect()->route('wishlists.index')->with('success', 'Added to travel wishlist.');
    }

    public function updateCreature(Request $request, CreatureWishlist $creatureWishlist)
    {
        if ($creatureWishlist->user_id !== Auth::id()) {
            abort(403);
        }
        $valid = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non-binary', 'no_preference'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'stage_number' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);
        $stageNumber = isset($valid['stage_number']) && $valid['stage_number'] >= 1 ? (int) $valid['stage_number'] : null;
        $creatureWishlist->update([
            'amount' => $valid['amount'] ?? 1,
            'gender' => $valid['gender'] ?? 'no_preference',
            'notes' => $valid['notes'] ?? null,
            'stage_number' => $stageNumber,
        ]);
        return back()->with('success', 'Creature wishlist entry updated.');
    }

    public function updateItem(Request $request, ItemWishlist $itemWishlist)
    {
        if ($itemWishlist->user_id !== Auth::id()) {
            abort(403);
        }
        $valid = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $itemWishlist->update([
            'amount' => $valid['amount'] ?? 1,
            'notes' => $valid['notes'] ?? null,
        ]);
        return back()->with('success', 'Item wishlist entry updated.');
    }

    public function updateTravel(Request $request, TravelWishlist $travelWishlist)
    {
        if ($travelWishlist->user_id !== Auth::id()) {
            abort(403);
        }
        $valid = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $travelWishlist->update([
            'amount' => $valid['amount'] ?? 1,
            'notes' => $valid['notes'] ?? null,
        ]);
        return back()->with('success', 'Travel wishlist entry updated.');
    }

    /**
     * Clear all creature wishlist entries for the authenticated user.
     * Used by the sync script with --clear so the wishlist is repopulated from scratch.
     */
    public function clearCreatures()
    {
        $deleted = Auth::user()->creatureWishlists()->delete();
        return back()->with('success', "Cleared creature wishlist ({$deleted} removed).");
    }

    public function removeCreature(CreatureWishlist $creatureWishlist)
    {
        if ($creatureWishlist->user_id !== Auth::id()) {
            abort(403);
        }
        $creatureWishlist->delete();
        return back()->with('success', 'Removed from creature wishlist.');
    }

    public function removeItem(ItemWishlist $itemWishlist)
    {
        if ($itemWishlist->user_id !== Auth::id()) {
            abort(403);
        }
        $itemWishlist->delete();
        return back()->with('success', 'Removed from item wishlist.');
    }

    public function removeTravel(TravelWishlist $travelWishlist)
    {
        if ($travelWishlist->user_id !== Auth::id()) {
            abort(403);
        }
        $travelWishlist->delete();
        return back()->with('success', 'Removed from travel wishlist.');
    }
}
