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
@endsection
