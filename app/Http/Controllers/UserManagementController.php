<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->orderBy('name');

        $q = $request->input('q');
        if ($q !== null && trim($q) !== '') {
            $term = '%' . trim($q) . '%';
            $query->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        $users = $query->paginate(20, ['id', 'name', 'email', 'role', 'banned_at', 'created_at'])->withQueryString();

        return view('auth.users.index', [
            'users' => $users,
            'search' => $q ?? '',
        ]);
    }

    public function create()
    {
        return view('auth.users.create');
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in([User::ROLE_USER, User::ROLE_ADMIN, User::ROLE_DEVELOPER, User::ROLE_CONTENT_MANAGER, User::ROLE_TRAVEL_SUGGESTOR])],
        ]);

        $valid['password'] = Hash::make($valid['password']);
        unset($valid['password_confirmation']);
        User::create($valid);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('auth.users.edit', ['managedUser' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in([User::ROLE_USER, User::ROLE_ADMIN, User::ROLE_DEVELOPER, User::ROLE_CONTENT_MANAGER, User::ROLE_TRAVEL_SUGGESTOR])],
        ]);

        if ($user->id === Auth::id() && $valid['role'] !== User::ROLE_DEVELOPER) {
            return redirect()->back()->with('error', 'You cannot change your own role away from developer.');
        }

        if ($user->isDeveloper() && $valid['role'] !== User::ROLE_DEVELOPER) {
            $developerCount = User::where('role', User::ROLE_DEVELOPER)->count();
            if ($developerCount <= 1) {
                return redirect()->back()->with('error', 'Cannot change role: at least one developer must remain.');
            }
        }

        if ($user->id === Auth::id() && isset($valid['banned_at'])) {
            return redirect()->back()->with('error', 'You cannot ban yourself.');
        }

        $user->name = $valid['name'];
        $user->email = $valid['email'];
        $user->role = $valid['role'];
        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function updateRole(Request $request, User $user)
    {
        $valid = $request->validate([
            'role' => ['required', 'string', Rule::in([User::ROLE_USER, User::ROLE_ADMIN, User::ROLE_DEVELOPER, User::ROLE_CONTENT_MANAGER, User::ROLE_TRAVEL_SUGGESTOR])],
        ]);

        if ($user->id === Auth::id() && $valid['role'] !== User::ROLE_DEVELOPER) {
            return redirect()->back()->with('error', 'You cannot change your own role away from developer.');
        }

        if ($user->isDeveloper() && $valid['role'] !== User::ROLE_DEVELOPER) {
            $developerCount = User::where('role', User::ROLE_DEVELOPER)->count();
            if ($developerCount <= 1) {
                return redirect()->back()->with('error', 'Cannot change role: at least one developer must remain.');
            }
        }

        $user->update(['role' => $valid['role']]);

        return redirect()->back()->with('success', 'Role updated to ' . $valid['role'] . '.');
    }

    public function ban(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'You cannot ban yourself.');
        }

        $user->banned_at = now();
        $user->save();

        return redirect()->route('users.index')->with('success', 'User banned.');
    }

    public function unban(User $user)
    {
        $user->banned_at = null;
        $user->save();

        return redirect()->route('users.index')->with('success', 'User unbanned.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $valid = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($valid['password']);
        $user->save();

        return redirect()->route('users.edit', $user)->with('success', 'Password reset.');
    }
}
