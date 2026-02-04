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
