@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
<div class="page-header">
    <h1>Forgot password</h1>
    <p class="lead">Enter your email and we'll send you a link to reset your password.</p>
</div>

@if (session('status'))
    <div class="card" style="max-width: 400px; margin-bottom: 1rem; border-color: var(--accent); background: var(--accent-muted);">
        <p style="margin: 0; font-weight: 500;">{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="card" style="max-width: 400px; margin-bottom: 1rem; border-color: #dc2626; background: #fef2f2;">
        <p style="margin: 0; color: #b91c1c; font-weight: 500;">{{ $errors->first('email') }}</p>
    </div>
@endif

<div class="card" style="max-width: 400px;">
    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <div style="margin-bottom: 1rem;">
            <label for="email" style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9375rem;">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem;">
        </div>
        <button type="submit" style="padding: 0.5rem 1.25rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.9375rem; cursor: pointer;">Send reset link</button>
    </form>
</div>

<p style="margin-top: 1rem; font-size: 0.9375rem;">
    <a href="{{ route('login') }}" style="color: var(--accent); font-weight: 500;">Back to log in</a>
</p>
@endsection
