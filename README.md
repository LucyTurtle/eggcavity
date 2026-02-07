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
   - `APP_CANONICAL_URL=https://your-domain.com` — use your real domain here so nav and redirects never use the server IP. (Defaults to `APP_URL` if unset.)
   - `SESSION_SECURE_COOKIE=true` so session cookies are sent only over HTTPS.
   - `SESSION_DOMAIN=.your-domain.com` (leading dot) so the session cookie is sent for your domain and login persists after redirect (e.g. after login you stay on the site instead of ending up back on /login).

2. **SSL on the server** — Your web server (e.g. nginx, Apache, Caddy) must:

   - Listen on port 443 and have a valid SSL certificate (e.g. [Let’s Encrypt](https://letsencrypt.org/) with certbot).
   - Forward the protocol to the app. For nginx with `proxy_pass` to PHP/Laravel, include:
     - `proxy_set_header X-Forwarded-Proto $scheme;`
     - (and typically `X-Forwarded-For` / `Host` as well.)

   The app trusts proxy headers and forces HTTPS when `APP_URL` starts with `https://`.

## Production: SQLite permissions

If you see **"attempt to write a readonly database"** (e.g. when registering or logging in), the PHP process user (e.g. `www-data`, `nginx`) cannot write to the SQLite file or its directory.

**Fix on the server:**

1. **Find the real database path** — On the server, the file might not be at `database/database.sqlite` (e.g. if `DB_DATABASE` is set in `.env`). From the project root:

   ```bash
   cd /path/to/your/app
   php artisan tinker --execute="echo config('database.connections.sqlite.database');"
   ```

   Or check the server’s `.env` for `DB_DATABASE`. Note the path it prints (e.g. `/var/www/eggcavity/storage/app/database.sqlite`).

2. **Make that path writable** — Use the path from step 1. Replace `www-data` with your PHP user (e.g. `nginx`, `apache`):

   ```bash
   # If the DB is at the default project path:
   sudo chown -R www-data:www-data database storage bootstrap/cache
   sudo chmod -R 775 database storage bootstrap/cache
   ```

   If the DB is elsewhere (e.g. `storage/app/database.sqlite`), make that file and its directory writable:

   ```bash
   sudo chown www-data:www-data /path/from/step/1
   sudo chmod 664 /path/from/step/1
   sudo chown www-data:www-data /path/to/parent/dir
   sudo chmod 775 /path/to/parent/dir
   ```

3. If your deploy user is different from the web server user, run the `chown`/`chmod` after deploy so the web server can write.

**If the database file doesn’t exist yet** (new server): create it at the path your app will use, then migrate and set permissions:

   ```bash
   touch database/database.sqlite   # or the path you set in DB_DATABASE
   php artisan migrate
   sudo chown -R www-data:www-data database storage bootstrap/cache
   ```

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

## Scheduled tasks

The scrapers and "Run now" jobs only run when Laravel’s scheduler is invoked. You need **one cron entry** on the server.

### 1. Add a cron entry

On the machine where the app runs (e.g. your production server), run:

```bash
crontab -e
```

Add this line (replace `/path/to/eggcave-fan-site` with your app’s path):

```cron
* * * * * cd /path/to/eggcave-fan-site && php artisan schedule:run >> /dev/null 2>&1
```

That runs every minute and Laravel then runs whatever is due (daily scrapers at 00:30, and any "Run now" requests from the dashboard).

### 2. Run cron as the web server user (recommended)

If you run cron as the same user as the web server (e.g. `www-data`), the scraper log files in `storage/logs/` will be readable by the app and the dashboard "Last run" output will work. Example:

```bash
sudo crontab -u www-data -e
# same line as above, with the correct path
```

If cron runs as root, the log files may be owned by root and the dashboard might not be able to read them; you’d need to fix permissions (e.g. `chmod -R 755 storage/logs`) or run cron as the web user.

### Scrapers and HTTP 403

If the archive or items scraper fails with **HTTP 403** for `https://eggcave.com/archives` (or similar), EggCave is rejecting the request. Common causes:

- **Server/datacenter IP** — Many sites block data-center IPs (e.g. AWS, DigitalOcean, Linode). The same scraper may work from your home connection but not from the server. The app sends browser-like headers (User-Agent, Referer, Accept-Language); if 403 persists, the host is likely blocking by IP.
- **Rate limiting or bot protection** — Too many requests or patterns that look automated can trigger blocks.

Options: run the scraper from a machine with a residential IP (e.g. a home server or a different host), or accept that automated scraping may not be allowed from your current server.

### What runs when

- **00:30 daily** — `archive:scrape` and `items:scrape` (creature and item data; "new only").
- **Every minute** — Any job queued from Dashboard → "Run now" (runs within about a minute).

## AI travel suggestions (optional)

Suggest travels per creature using **free local image color analysis** (no API key). Run **Dashboard → Run jobs manually → Suggest travels (image match)**. Approve or reject from **Dashboard → Manage content → Pending travel suggestions**.

## Artisan commands

- `php artisan items:scrape` — Scrape items from EggCave (run when needed to refresh catalog)
- `php artisan archive:scrape` — Scrape archive creature/stage data
- `php artisan travels:suggest-by-image` — Suggest travels by comparing creature and travel images (free; requires `image_url` on creatures and travels; use `--limit`, `--travel-limit`, `--min-score`)
- `php artisan wishlist:sync-creatures {username}` — Sync creature wishlist: uses your username (same on eggcavity and Eggcave) to find your account and your Eggcave profile, then adds every archive creature you don’t have to your creature wishlist. Use `--clear` to clear the wishlist first. Example: `php artisan wishlist:sync-creatures lbowe_elbow --clear`

## Tech stack

- Laravel 11
- PHP 8.2+
- SQLite (default) or MySQL
- Blade views, minimal frontend (no build step required for basic UI)

## License

MIT.

---

*Unofficial fan project. EggCave and related content are property of their respective owners.*
