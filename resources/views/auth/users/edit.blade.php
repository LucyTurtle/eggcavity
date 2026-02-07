@extends('layouts.app')

@section('title', 'Edit user')

@section('content')
<div class="page-header">
    <h1>Edit user</h1>
    <p class="lead"><a href="{{ route('users.index') }}">← Back to user manager</a></p>
</div>

<style>
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .form-group input, .form-group select { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; }
    .form-group small { display: block; font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }
    .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .btn-submit:hover { background: var(--accent-hover); }
    .card-section { margin-bottom: 1.5rem; }
    .card-section h2 { font-size: 1.125rem; margin: 0 0 0.75rem 0; }
</style>

@if(session('success'))
    <div class="card" style="background: color-mix(in srgb, var(--accent) 12%, transparent); border-color: var(--accent); margin-bottom: 1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="card" style="background: #fef2f2; border-color: #dc2626; margin-bottom: 1rem;">{{ session('error') }}</div>
@endif

@if($managedUser->isBanned())
    <div class="card" style="margin-bottom: 1rem; border-color: #dc2626; background: #fef2f2;">
        <form method="post" action="{{ route('users.unban', $managedUser) }}" style="margin: 0;">
        @csrf
        <p style="margin: 0;">This user is <strong>banned</strong> and cannot log in. <button type="submit" style="background: none; border: none; padding: 0; color: var(--accent); font-weight: 500; cursor: pointer; font-family: inherit; font-size: inherit;">Unban</button></p>
    </form>
    </div>
@endif

<div class="card card-section">
    <h2>Profile</h2>
    <form method="post" action="{{ route('users.update', $managedUser) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" name="name" id="name" value="{{ old('name', $managedUser->name) }}" required autocomplete="name">
            @error('name')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" name="email" id="email" value="{{ old('email', $managedUser->email) }}" required autocomplete="email">
            @error('email')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="role">Role *</label>
            <select name="role" id="role" required>
                <option value="user" {{ old('role', $managedUser->role) === 'user' ? 'selected' : '' }}>user</option>
                <option value="admin" {{ old('role', $managedUser->role) === 'admin' ? 'selected' : '' }}>admin</option>
                <option value="developer" {{ old('role', $managedUser->role) === 'developer' ? 'selected' : '' }}>developer</option>
                <option value="content_manager" {{ old('role', $managedUser->role) === 'content_manager' ? 'selected' : '' }}>content_manager</option>
                <option value="travel_suggestor" {{ old('role', $managedUser->role) === 'travel_suggestor' ? 'selected' : '' }}>travel_suggestor</option>
            </select>
            @error('role')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <button type="submit" class="btn-submit">Save profile</button>
    </form>
</div>

<div class="card card-section">
    <h2>Reset password</h2>
    <p style="font-size: 0.9375rem; color: var(--text-secondary); margin: 0 0 1rem 0;">Set a new password for this user. They can change it later from their account page.</p>
    <form method="post" action="{{ route('users.reset-password', $managedUser) }}">
        @csrf
        <div class="form-group">
            <label for="password">New password *</label>
            <input type="password" name="password" id="password" required autocomplete="new-password" minlength="8">
            <small>At least 8 characters.</small>
            @error('password')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm new password *</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn-submit">Reset password</button>
    </form>
</div>

@if($managedUser->id !== auth()->id() && !$managedUser->isBanned())
    <div class="card card-section">
        <h2>Ban user</h2>
        <p style="font-size: 0.9375rem; color: var(--text-secondary); margin: 0 0 0.75rem 0;">Banned users cannot log in. You can unban them later from the user list.</p>
        <form method="post" action="{{ route('users.ban', $managedUser) }}" onsubmit="return confirm('Ban this user? They will not be able to log in.');">
            @csrf
            <button type="submit" style="padding: 0.35rem 0.75rem; font-size: 0.875rem; border: 1px solid #dc2626; background: transparent; color: #dc2626; border-radius: var(--radius-sm); cursor: pointer; font-family: inherit;">Ban user</button>
        </form>
    </div>
@endif
@endsection
