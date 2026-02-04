<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $users = collect();
        if (Auth::user()->isDeveloper()) {
            $users = User::orderBy('name')->get(['id', 'name', 'email', 'role']);
        }

        return view('auth.dashboard', ['users' => $users]);
    }
}
