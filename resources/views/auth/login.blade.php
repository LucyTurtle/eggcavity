@extends('layouts.app')

@section('title', 'Log in')

@section('content')
<div class="card" style="max-width: 400px; margin-bottom: 1rem; border-color: #dc2626; background: #fef2f2;">
    <p style="margin: 0; color: #b91c1c; font-weight: 600;">All previous data was lost. You will need to create a new account.</p>
</div>
@if (session('status'))
    <div class="card" style="max-width: 400px; margin-bottom: 1rem; border-color: var(--accent); background: var(--accent-muted);">
        <p style="margin: 0; font-weight: 500;">{{ session('status') }}</p>
    </div>
@endif
@if(request('from') === 'wishlist')
    <div class="card" style="max-width: 400px; margin-bottom: 1rem; border-color: var(--accent); background: var(--accent-muted);">
        <p style="margin: 0; font-weight: 500;">Please log in to use the wishlist.</p>
    </div>
@endif
<div class="page-header">
    <h1>Log in</h1>
    <p class="lead">Sign in to your eggcavity account.</p>
</div>

<div class="card" style="max-width: 400px;">
    <form method="post" action="{{ route('login') }}">
        @csrf
        <div style="margin-bottom: 1rem;">
            <label for="login" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Email or username</label>
            <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
            @error('login')
                <p style="color: #dc2626; font-size: 0.875rem; margin: 0.25rem 0 0 0;">{{ $message }}</p>
            @enderror
        </div>
        <div style="margin-bottom: 1rem;">
            <label for="password" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Password</label>
            <input type="password" name="password" id="password" required autocomplete="current-password"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
            @error('password')
                <p style="color: #dc2626; font-size: 0.875rem; margin: 0.25rem 0 0 0;">{{ $message }}</p>
            @enderror
            <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">
                <a href="{{ route('password.request') }}" style="color: var(--accent); font-weight: 500;">Forgot your password?</a>
            </p>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9375rem;">
                <input type="checkbox" name="remember">
                Remember me
            </label>
        </div>
        <button type="submit" style="padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.9375rem; cursor: pointer;">Log in</button>
    </form>
</div>

<p style="margin-top: 1rem; font-size: 0.9375rem;">
    Don't have an account? <a href="{{ route('register') }}" style="color: var(--accent); font-weight: 500;">Register</a>
</p>
@endsection
