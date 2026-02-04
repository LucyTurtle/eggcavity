# Eggcavity

A Laravel community site for [EggCave.com](https://eggcave.com) — the adoptables game community. Browse the archive, preview travels on creatures, and manage wishlists.

## Features

- **Archive** — Browse creature stages and see recommended travels (manual suggestions + algorithm)
- **Travel viewers** — Preview any travel on any creature/stage (simple, by-creature, by-travel)
- **Items** — Item catalog with travel-on-creature previews
- **Wishlists** — Per-user wishlists for creatures, items, and travels with shareable public links
- **Content management** — Admin area for creatures, items, and travel suggestions

## Requirements

- PHP 8.2+
- Composer

## Setup

1. **Clone and install dependencies**

   ```bash
   git clone https://github.com/YOUR_USERNAME/eggcavity.git
   cd eggcavity
   composer install
   ```

2. **Environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database** (SQLite by default)

   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

   Optional: run seeders for a dev user (see `database/seeders/`).

4. **Run the app**

   ```bash
   php artisan serve
   ```

   Open [http://localhost:8000](http://localhost:8000).

## HTTPS (production)

For the site to work over HTTPS:

1. **Server `.env`** (on the machine serving the app):

   - `APP_URL=https://your-domain.com` (or `https://YOUR_SERVER_IP` if you have no domain). Must use `https://`.
   - `SESSION_SECURE_COOKIE=true` so session cookies are sent only over HTTPS.

2. **SSL on the server** — Your web server (e.g. nginx, Apache, Caddy) must:

   - Listen on port 443 and have a valid SSL certificate (e.g. [Let’s Encrypt](https://letsencrypt.org/) with certbot).
   - Forward the protocol to the app. For nginx with `proxy_pass` to PHP/Laravel, include:
     - `proxy_set_header X-Forwarded-Proto $scheme;`
     - (and typically `X-Forwarded-For` / `Host` as well.)

   The app trusts proxy headers and forces HTTPS when `APP_URL` starts with `https://`.

## Optional: MySQL

Set in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eggcave_fans
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then run `php artisan migrate`.

## Artisan commands

- `php artisan items:scrape` — Scrape items from EggCave (run when needed to refresh catalog)
- `php artisan archive:scrape` — Scrape archive creature/stage data

## Tech stack

- Laravel 11
- PHP 8.2+
- SQLite (default) or MySQL
- Blade views, minimal frontend (no build step required for basic UI)

## License

MIT.

---

*Unofficial fan project. EggCave and related content are property of their respective owners.*
