@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="card" style="border-color: #dc2626; background: #fef2f2; margin-bottom: 1.5rem;">
    <p style="margin: 0; color: #b91c1c; font-weight: 600;">All previous data was lost. Users will need to create a new account.</p>
    <p style="margin: 0.5rem 0 0 0; color: #b91c1c;">Everything on this site is 100% new code — we lost everything and rebuilt from scratch.</p>
</div>
<div class="page-header">
    <p class="lead">The EggCave community site. Links, resources, and a home for fans.</p>
</div>

<div class="card">
    <h3>On this site</h3>
    <ul>
        <li><a href="{{ route('archive.index') }}">Archive</a> — Browse creature stages and see suggested travels.</li>
        <li><a href="{{ route('travel-viewer.index') }}">Travel viewer</a> — Preview any travel on any creature or stage.</li>
        <li><a href="{{ route('items.index') }}">Items</a> — Item catalog and travel-on-creature previews.</li>
        <li><a href="{{ route('login') }}">Wishlists</a> — Sign in to manage and share your creature, item, and travel wishlists.</li>
    </ul>
</div>

<div class="card">
    <p><a href="https://eggcave.com" target="_blank" rel="noopener">EggCave.com</a> — play and adopt.</p>
</div>
@endsection
