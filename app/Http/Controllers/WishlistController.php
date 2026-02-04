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
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $creatureWishlists = $user->creatureWishlists()->with('archiveItem')->orderBy('created_at', 'desc')->get();
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

    public function showShared(User $user)
    {
        $owner = $user;
        $creatureWishlists = $owner->creatureWishlists()->with('archiveItem')->orderBy('created_at', 'desc')->get();
        $itemWishlists = $owner->itemWishlists()->with('item')->orderBy('created_at', 'desc')->get();
        $travelWishlists = $owner->travelWishlists()->with('item')->orderBy('created_at', 'desc')->get();

        return view('wishlists.shared', [
            'owner' => $owner,
            'creatureWishlists' => $creatureWishlists,
            'itemWishlists' => $itemWishlists,
            'travelWishlists' => $travelWishlists,
        ]);
    }

    public function showSharedCreatures(User $user)
    {
        $owner = $user;
        $creatureWishlists = $owner->creatureWishlists()->with('archiveItem')->orderBy('created_at', 'desc')->get();

        return view('wishlists.shared-creatures', [
            'owner' => $owner,
            'creatureWishlists' => $creatureWishlists,
        ]);
    }

    public function showSharedItems(User $user)
    {
        $owner = $user;
        $itemWishlists = $owner->itemWishlists()->with('item')->orderBy('created_at', 'desc')->get();

        return view('wishlists.shared-items', [
            'owner' => $owner,
            'itemWishlists' => $itemWishlists,
        ]);
    }

    public function showSharedTravels(User $user)
    {
        $owner = $user;
        $travelWishlists = $owner->travelWishlists()->with('item')->orderBy('created_at', 'desc')->get();

        return view('wishlists.shared-travels', [
            'owner' => $owner,
            'travelWishlists' => $travelWishlists,
        ]);
    }

    public function showAddCreatures(Request $request)
    {
        $creatures = ArchiveItem::with('images')->orderBy('title')->paginate(30)->withQueryString();
        return view('wishlists.add-creatures', ['creatures' => $creatures]);
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
            $user->creatureWishlists()->updateOrCreate(
                ['archive_item_id' => $archiveItemId],
                [
                    'amount' => min(max($amount, 1), 9999),
                    'gender' => isset($data['gender']) && in_array($data['gender'], ['male', 'female', 'non-binary', 'no_preference'], true) ? $data['gender'] : 'no_preference',
                    'notes' => isset($data['notes']) ? mb_substr($data['notes'], 0, 2000) : null,
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
        ]);

        $user = Auth::user();
        $entry = $user->creatureWishlists()->updateOrCreate(
            ['archive_item_id' => $valid['archive_item_id']],
            [
                'amount' => $valid['amount'] ?? 1,
                'gender' => $valid['gender'] ?? 'no_preference',
                'notes' => $valid['notes'] ?? null,
            ]
        );

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
        ]);
        $creatureWishlist->update([
            'amount' => $valid['amount'] ?? 1,
            'gender' => $valid['gender'] ?? 'no_preference',
            'notes' => $valid['notes'] ?? null,
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
