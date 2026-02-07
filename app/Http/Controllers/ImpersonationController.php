<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start viewing the site as another user (developer only).
     */
    public function start(Request $request, User $user)
    {
        if (! Auth::user()->isDeveloper()) {
            abort(403);
        }

        if ($user->id === Auth::id()) {
            return redirect()->route('admin')->with('success', 'You are already viewing as yourself.');
        }

        session([
            'impersonate_id' => $user->id,
            'impersonate_original_id' => Auth::id(),
        ]);

        return redirect()->route('home')->with('success', "Now viewing as {$user->name}.");
    }

    /**
     * Stop impersonating and return to the developer account.
     */
    public function stop(Request $request)
    {
        $originalId = session('impersonate_original_id');
        session()->forget(['impersonate_id', 'impersonate_original_id']);

        if ($originalId) {
            $original = User::find($originalId);
            if ($original) {
                Auth::setUser($original);
            }
        }

        return redirect()->route('admin')->with('success', 'Impersonation ended.');
    }
}
