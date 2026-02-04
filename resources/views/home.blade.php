@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="page-header">
    <h1>eggcavity</h1>
    <p class="lead">The EggCave community site that existed in the past is back. Links, resources, and a home for fans.</p>
</div>

<p>This site is run by fans of <a href="https://eggcave.com" target="_blank" rel="noopener">EggCave.com</a> — the adoptables game where you collect eggs, raise creatures, and grow your collection through views, clicks, and feeds from the community.</p>

<div class="card">
    <div class="mascot-block">
        <a href="https://eggcave.com/archives/goblar" target="_blank" rel="noopener" class="mascot-figure" title="Goblar on EggCave">
            <img src="https://eggcave.com/archives/goblar.png" alt="" role="presentation" loading="lazy" onerror="this.remove()">
            <span class="mascot-fallback" aria-hidden="true">Goblar</span>
        </a>
        <div class="mascot-body">
            <h3>Our mascot: <a href="https://eggcave.com/archives/goblar" target="_blank" rel="noopener">Goblar</a></h3>
            <p>Goblar is an EggCave creature and the eggcavity mascot. <a href="https://eggcave.com/archives/goblar" target="_blank" rel="noopener">View Goblar in the EggCave archives →</a></p>
        </div>
    </div>
</div>

<div class="card">
    <h3>What is EggCave?</h3>
    <p>EggCave is a browser-based adoptables game. You adopt eggs and creatures from places like The Cave (daily adoptables) and The Mysterious Asteroid (rare finds). Level them up by sharing creature codes and getting views, clicks, and feeds from other players. Check the CaveDex for evolution requirements and work toward immortality for your favorites.</p>
</div>

<div class="card">
    <h3>Get started</h3>
    <p>Head over to <a href="https://eggcave.com" target="_blank" rel="noopener">EggCave.com</a> to create an account and start adopting.</p>
</div>
@endsection
