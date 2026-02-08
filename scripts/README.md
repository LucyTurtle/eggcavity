# Scripts

## Sync creatures to wishlist

Add every archive creature you **don’t** have on Eggcave to your eggcavity creature wishlist.

**You only need:** Eggcave username + eggcavity login + password. The eggcavity URL is set once in the script (or via `EGGCAVITY_URL`).

## How to run

From the project root (or any machine with Python 3 and `requests`):

```bash
pip3 install -r scripts/requirements.txt   # once
python3 scripts/sync_creatures.py --username YOUR_EGGCAVE_USERNAME --login "your@email.com" --password "yourpassword"
```

Example (on the server after deploy):

```bash
cd /var/www/eggcave-fan-site
python3 scripts/sync_creatures.py --username lbowe_elbow --login "your@email.com" --password "yourpassword"
```

**Options:**

- `--clear` / `-c` — Clear your **creature wishlist** on eggcavity before adding. The script will remove all current creature wishlist entries, then add only the creatures you don’t have on Eggcave. Use this when you want the wishlist to exactly match “creatures I don’t have” with no leftover entries.

Example with clear:

```bash
python3 scripts/sync_creatures.py --username lbowe_elbow --login "your@email.com" --password "yourpassword" --clear
```

To change which site is used, set `EGGCAVITY_SITE_URL` at the top of `sync_creatures.py` or set the `EGGCAVITY_URL` env var.
