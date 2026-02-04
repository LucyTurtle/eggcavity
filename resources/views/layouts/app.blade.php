<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="eggcavity — the EggCave community site. Back again.">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-RG7P12FL6E"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-RG7P12FL6E');
    </script>
    <title>@yield('title', 'Home') — eggcavity</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f9f5;
            --bg-subtle: #eef5ef;
            --surface: #ffffff;
            --border: #dce8de;
            --border-soft: #e6efe7;
            --text: #1a2e1d;
            --text-secondary: #4a5d4c;
            --accent: #2d7a3e;
            --accent-hover: #236532;
            --accent-muted: #dcfce7;
            --radius: 14px;
            --radius-sm: 10px;
            --shadow: 0 1px 3px rgba(26,46,29,0.06), 0 1px 2px rgba(26,46,29,0.04);
            --shadow-lg: 0 4px 14px rgba(26,46,29,0.08), 0 2px 6px rgba(26,46,29,0.04);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            background-image: radial-gradient(ellipse 120% 80% at 50% -20%, rgba(220,252,231,0.5), transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(220,252,231,0.2), transparent 35%);
            background-attachment: fixed;
            color: var(--text);
            line-height: 1.65;
            font-size: 15px;
        }
        .wrap {
            max-width: 720px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        .site-header {
            padding: 1.75rem 0;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            box-shadow: 0 1px 0 var(--border-soft);
        }
        .site-header .wrap {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex: 1 1 auto;
            min-width: 0;
        }
        .site-title {
            font-size: 1.375rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .site-title a {
            color: var(--text);
            text-decoration: none;
        }
        .site-title a:hover { color: var(--accent); }
        .tagline {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            margin: 0.25rem 0 0 0;
        }
        nav {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9375rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            transition: color 0.15s, background 0.15s;
        }
        nav a:hover { color: var(--text); background: var(--bg); }
        nav a.active { color: var(--accent); background: var(--accent-muted); }
        nav a.External {
            margin-left: 0.5rem;
            padding-left: 1rem;
            border-left: 1px solid var(--border);
        }
        .nav-dropdown {
            position: relative;
        }
        .nav-dropdown .nav-dropdown-trigger {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9375rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
        }
        .nav-dropdown .nav-dropdown-trigger:hover { color: var(--text); background: var(--bg); }
        .nav-dropdown .nav-dropdown-trigger.active { color: var(--accent); background: var(--accent-muted); }
        .nav-dropdown .nav-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 0.25rem;
            min-width: 200px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            padding: 0.5rem 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-4px);
            transition: opacity 0.15s, visibility 0.15s, transform 0.15s;
            z-index: 100;
        }
        .nav-dropdown:hover .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .nav-dropdown .nav-dropdown-menu a {
            display: block;
            padding: 0.5rem 1rem;
            font-size: 0.9375rem;
            color: var(--text-secondary);
            text-decoration: none;
        }
        .nav-dropdown .nav-dropdown-menu a:hover { color: var(--text); background: var(--bg); }
        .nav-dropdown .nav-dropdown-menu a.active { color: var(--accent); background: var(--accent-muted); }
        .user-menu {
            position: relative;
            flex-shrink: 0;
        }
        .user-menu-trigger {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.15s, background 0.15s;
        }
        .user-menu-trigger:hover {
            color: var(--text);
            background: var(--bg);
        }
        .user-menu-trigger svg {
            width: 20px;
            height: 20px;
        }
        .user-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.25rem;
            min-width: 180px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            padding: 0.5rem 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-4px);
            transition: opacity 0.15s, visibility 0.15s, transform 0.15s;
            z-index: 100;
        }
        .user-menu:hover .user-menu-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .user-menu-dropdown .dropdown-header {
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text);
            border-bottom: 1px solid var(--border);
            margin-bottom: 0.25rem;
        }
        .user-menu-dropdown a,
        .user-menu-dropdown button {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.5rem 1rem;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            transition: color 0.15s, background 0.15s;
        }
        .user-menu-dropdown a:hover,
        .user-menu-dropdown button:hover {
            color: var(--text);
            background: var(--bg);
        }
        .user-menu-dropdown a.active {
            color: var(--accent);
            background: var(--accent-muted);
        }
        .user-menu-dropdown .dropdown-divider {
            height: 1px;
            background: var(--border);
            margin: 0.25rem 0;
        }
        main { padding: 2.5rem 0 4rem; }
        main .page-header { padding-top: 1.5rem; margin-bottom: 1.75rem; }
        main h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0 0 0.5rem 0;
            color: var(--text);
        }
        main .lead {
            font-size: 1.0625rem;
            color: var(--text-secondary);
            margin: 0;
        }
        main h2 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text);
            margin: 1.75rem 0 0.5rem 0;
        }
        main p { margin: 0.75rem 0; color: var(--text); }
        main a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }
        main a:hover { text-decoration: underline; }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            margin: 1rem 0;
            box-shadow: var(--shadow);
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .card:hover { box-shadow: var(--shadow-lg); }
        .card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            color: var(--text);
        }
        .card p:first-of-type { margin-top: 0; }
        ul.links { list-style: none; padding: 0; margin: 0; }
        ul.links li { margin: 0.5rem 0; }
        ul.links li a { display: inline-block; }
        .footer {
            text-align: center;
            padding: 2rem 1.5rem;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            border-top: 1px solid var(--border);
            background: var(--surface);
        }
        .footer a { color: var(--text-secondary); }
        .footer a:hover { color: var(--accent); }
        .mascot-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            transition: border-color 0.15s, color 0.15s;
        }
        .mascot-link:hover { color: var(--accent); border-color: var(--accent); }
        .mascot-block {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.25rem 1.5rem;
        }
        .mascot-block .mascot-figure {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            border-radius: var(--radius);
            background: var(--accent-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .mascot-block .mascot-figure { text-decoration: none; color: var(--text); position: relative; }
        .mascot-block .mascot-figure img { width: 100%; height: 100%; object-fit: contain; position: relative; z-index: 1; }
        .mascot-block .mascot-fallback { display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 600; color: var(--accent); position: absolute; inset: 0; }
        .mascot-block .mascot-body h3 { margin: 0 0 0.25rem 0; }
        .mascot-block .mascot-body p { margin: 0; font-size: 0.9375rem; }
        body.body-bg-white {
            background: #ffffff;
            background-image: none;
        }
    </style>
</head>
<body class="@yield('bodyClass')">
    <header class="site-header">
        <div class="wrap">
            <div class="header-row">
                <div>
                    <h1 class="site-title"><a href="{{ route('home') }}">eggcavity</a></h1>
                    <p class="tagline">The EggCave community site — back again</p>
                </div>
                <div class="user-menu">
                    <button type="button" class="user-menu-trigger" aria-label="Account menu" aria-expanded="false" aria-haspopup="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </button>
                    <div class="user-menu-dropdown" role="menu">
                        @auth
                            <div class="dropdown-header">{{ auth()->user()->name }}</div>
                            @if(auth()->user()->isAdmin() || auth()->user()->isDeveloper())
                                <a href="{{ route('dashboard') }}" role="menuitem" @if(request()->routeIs('dashboard')) class="active" @endif>Dashboard</a>
                                <a href="{{ route('content.index') }}" role="menuitem" @if(request()->routeIs('content.*')) class="active" @endif>Manage content</a>
                            @endif
                            <a href="{{ route('account') }}" role="menuitem">Manage account</a>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="{{ route('logout') }}" style="margin: 0;">
                                @csrf
                                <button type="submit" role="menuitem">Log out</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" role="menuitem" @if(request()->routeIs('login')) class="active" @endif>Log in</a>
                            <a href="{{ route('register') }}" role="menuitem" @if(request()->routeIs('register')) class="active" @endif>Register</a>
                        @endauth
                    </div>
                </div>
            </div>
            <nav>
                <a href="{{ route('home') }}" @if(request()->routeIs('home')) class="active" @endif>Home</a>
                <a href="{{ route('archive.index') }}" @if(request()->routeIs('archive.*')) class="active" @endif>Archive</a>
                <a href="{{ route('items.index') }}" @if(request()->routeIs('items.*')) class="active" @endif>Items</a>
                @auth
                    <a href="{{ route('wishlists.index') }}" @if(request()->routeIs('wishlists.*')) class="active" @endif>Wishlist</a>
                @else
                    <a href="{{ route('login', ['from' => 'wishlist']) }}" @if(request()->routeIs('login')) class="active" @endif>Wishlist</a>
                @endauth
                <div class="nav-dropdown">
                    <button type="button" class="nav-dropdown-trigger" @if(request()->routeIs('travel-viewer.*') || request()->routeIs('archive.creature-travels') || request()->routeIs('items.travel-on-creatures')) class="active" @endif>Travel Viewer ▾</button>
                    <div class="nav-dropdown-menu">
                        <a href="{{ route('travel-viewer.index') }}" @if(request()->routeIs('travel-viewer.index')) class="active" @endif>Simple viewer</a>
                        <a href="{{ route('travel-viewer.by-creature') }}" @if(request()->routeIs('travel-viewer.by-creature')) class="active" @endif>By creature</a>
                        <a href="{{ route('travel-viewer.by-travel') }}" @if(request()->routeIs('travel-viewer.by-travel')) class="active" @endif>By travel</a>
                    </div>
                </div>
                <a href="https://eggcave.com" target="_blank" rel="noopener" class="External">EggCave.com →</a>
            </nav>
        </div>
    </header>

    <main class="wrap">
        @if(session('impersonate_id'))
            <div class="card" style="border-color: var(--accent); background: var(--accent-muted); margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                <span style="font-weight: 500;">Viewing as <strong>{{ auth()->user()->name }}</strong></span>
                <form method="post" action="{{ route('impersonate.stop') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" style="padding: 0.35rem 0.75rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; font-size: 0.875rem; cursor: pointer;">End impersonation</button>
                </form>
            </div>
        @endif
        @if(session('success'))
            <div class="card" style="border-color: var(--accent); background: var(--accent-muted);">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="card" style="border-color: #dc2626; background: #fef2f2;">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="footer">
        <div class="wrap">
            EggCave and related content are property of their respective owners.
            <br><a href="https://eggcave.com" target="_blank" rel="noopener">EggCave.com</a>
            <br><span style="font-size: 0.875rem; color: var(--text-secondary);">Contact: DM me on Eggcave — <a href="https://eggcave.com/{{ '@' }}lbowe_elbow" target="_blank" rel="noopener" style="color: var(--accent);">@lbowe_elbow</a></span>
        </div>
    </footer>
</body>
</html>
