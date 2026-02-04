@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="page-header">
    <h1>Register</h1>
    <p class="lead">Create an eggcavity account. Please use your main EggCave username.</p>
</div>

<div class="card" style="max-width: 400px;">
    <form method="post" action="{{ route('register') }}">
        @csrf
        <div style="margin-bottom: 1rem;">
            <label for="name" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Username (your main EggCave username)</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus autocomplete="username" placeholder="EggCave username"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
            @error('name')
                <p style="color: #dc2626; font-size: 0.875rem; margin: 0.25rem 0 0 0;">{{ $message }}</p>
            @enderror
        </div>
        <div style="margin-bottom: 1rem;">
            <label for="email" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
            @error('email')
                <p style="color: #dc2626; font-size: 0.875rem; margin: 0.25rem 0 0 0;">{{ $message }}</p>
            @enderror
        </div>
        <div style="margin-bottom: 1rem;">
            <label for="password" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Password</label>
            <input type="password" name="password" id="password" required autocomplete="new-password"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
            @error('password')
                <p style="color: #dc2626; font-size: 0.875rem; margin: 0.25rem 0 0 0;">{{ $message }}</p>
            @enderror
        </div>
        <div style="margin-bottom: 1rem;">
            <label for="password_confirmation" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
        </div>
        <button type="submit" style="padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.9375rem; cursor: pointer;">Register</button>
    </form>
</div>

<p style="margin-top: 1rem; font-size: 0.9375rem;">
    Already have an account? <a href="{{ route('login') }}" style="color: var(--accent); font-weight: 500;">Log in</a>
</p>
@endsection
