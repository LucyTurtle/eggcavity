@extends('layouts.app')

@section('title', 'Add user')

@section('content')
<div class="page-header">
    <h1>Add user</h1>
    <p class="lead"><a href="{{ route('users.index') }}">← Back to user manager</a></p>
</div>

<style>
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 500; font-size: 0.9375rem; margin-bottom: 0.35rem; color: var(--text); }
    .form-group input, .form-group select { width: 100%; max-width: 28rem; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9375rem; font-family: inherit; }
    .form-group small { display: block; font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }
    .btn-submit { padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.9375rem; cursor: pointer; font-family: inherit; }
    .btn-submit:hover { background: var(--accent-hover); }
</style>

<div class="card">
    <form method="post" action="{{ route('users.store') }}">
        @csrf
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" name="password" id="password" required autocomplete="new-password" minlength="8">
            <small>At least 8 characters.</small>
            @error('password')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm password *</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="role">Role *</label>
            <select name="role" id="role" required>
                <option value="user" {{ old('role', 'user') === 'user' ? 'selected' : '' }}>user</option>
                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>admin</option>
                <option value="developer" {{ old('role') === 'developer' ? 'selected' : '' }}>developer</option>
            </select>
            @error('role')<small style="color: #dc2626;">{{ $message }}</small>@enderror
        </div>
        <button type="submit" class="btn-submit">Create user</button>
    </form>
</div>
@endsection
