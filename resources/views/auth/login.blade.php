@extends('layouts.app')

@section('title', 'Log in')

@section('content')
<div class="page-header">
    <h1>Log in</h1>
    <p class="lead">Sign in to your eggcavity account.</p>
</div>

<div class="card" style="max-width: 400px;">
    <form method="post" action="{{ route('login') }}">
        @csrf
        <div style="margin-bottom: 1rem;">
            <label for="email" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
            @error('email')
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
