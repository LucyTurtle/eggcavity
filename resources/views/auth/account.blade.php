@extends('layouts.app')

@section('title', 'Account')

@section('content')
<div class="page-header">
    <h1>Manage account</h1>
    <p class="lead">Signed in as <strong>{{ auth()->user()->name }}</strong>.</p>
</div>

<div class="card">
    <h3>Your details</h3>
    <p style="margin: 0.5rem 0;"><strong>Name:</strong> {{ auth()->user()->name }}</p>
    <p style="margin: 0.5rem 0;"><strong>Email:</strong> {{ auth()->user()->email }}</p>
    <p style="margin: 0.5rem 0 0 0;"><strong>Role:</strong> {{ auth()->user()->role }}</p>
</div>

<div class="card" style="max-width: 400px;">
    <h3>Change password</h3>
    @if(session('password_changed'))
        <p style="margin: 0 0 1rem 0; color: var(--accent); font-size: 0.9375rem;">Your password has been updated.</p>
    @endif
    <form method="post" action="{{ route('account.password.update') }}">
        @csrf
        <div style="margin-bottom: 1rem;">
            <label for="current_password" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Current password</label>
            <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
            @error('current_password')
                <p style="color: #dc2626; font-size: 0.875rem; margin: 0.25rem 0 0 0;">{{ $message }}</p>
            @enderror
        </div>
        <div style="margin-bottom: 1rem;">
            <label for="password" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">New password</label>
            <input type="password" name="password" id="password" required autocomplete="new-password"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
            @error('password')
                <p style="color: #dc2626; font-size: 0.875rem; margin: 0.25rem 0 0 0;">{{ $message }}</p>
            @enderror
        </div>
        <div style="margin-bottom: 1rem;">
            <label for="password_confirmation" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Confirm new password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
        </div>
        <button type="submit" style="padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.9375rem; cursor: pointer;">Update password</button>
    </form>
</div>
@endsection
