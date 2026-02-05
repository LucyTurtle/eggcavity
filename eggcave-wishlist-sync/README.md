# Eggcave → Eggcavity wishlist sync

**Standalone script** — not part of the Laravel app. Copy this folder anywhere (or upload to a server) to run or share with others. Nothing is stored on the eggcavity server; the script runs on your machine (or wherever you run it).

## What it does

1. **Scrapes your Eggcave profile** (e.g. `https://eggcave.com/@lbowe_elbow`) and collects every creature you *have* (from links to `/archives/...`). Your profile is public — no Eggcave login.
2. **Fetches the archive creature list** from your eggcavity (fan site) via its public API.
3. **Logs in to eggcavity** (your fan site) and **adds to your creature wishlist** every archive creature that you *don’t* have on Eggcave.

So: “creatures I don’t have” (in the archive but not on your profile) get added to your wishlist.

## Requirements

- Python 3.9+
- `requests`: `pip install requests`

## Usage

```bash
pip install -r requirements.txt

python sync_creatures.py \
  --eggcave-username lbowe_elbow \
  --site-url https://your-eggcavity-site.com \
  --login "your@email.com" \
  --password "yourpassword"
```

Or use environment variables:

```bash
export EGGCAVE_USERNAME=lbowe_elbow
export EGGCAVITY_URL=https://your-eggcavity-site.com
export EGGCAVITY_LOGIN=your@email.com
export EGGCAVITY_PASSWORD=yourpassword
python sync_creatures.py
```

## Eggcavity requirement

The eggcavity site must expose the public API used by this script:

- `GET /api/archive-creatures` — returns JSON: `{ "creatures": [ { "id", "title", "slug" }, ... ] }`

If you’re running the eggcave-fan-site Laravel app, that route is already there.

## Sharing

You can upload this folder (e.g. to a gist, your server, or eggcave.com) so others can run it. They need their own Eggcave username and an eggcavity account (login/password) for the same fan site.
