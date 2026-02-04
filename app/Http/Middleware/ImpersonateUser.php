<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateUser
{
    /**
     * When a developer is impersonating, swap the auth user to the impersonated user for this request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $impersonateId = session('impersonate_id');
        $originalId = session('impersonate_original_id');

        if ($impersonateId && $originalId && Auth::check() && Auth::id() === (int) $originalId) {
            $user = User::find($impersonateId);
            if ($user) {
                Auth::setUser($user);
            } else {
                session()->forget(['impersonate_id', 'impersonate_original_id']);
            }
        }

        return $next($request);
    }
}
