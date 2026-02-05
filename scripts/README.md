# Sync creatures to wishlist

Add every archive creature you **don’t** have on Eggcave to your eggcavity creature wishlist.

**You only need:** Eggcave username + eggcavity login + password. The eggcavity URL is set once in the script (or via `EGGCAVITY_URL`).

## On the server (after deploy)

```bash
cd /var/www/eggcave-fan-site
pip3 install -r scripts/requirements.txt   # or: apt install python3-pip first if needed
python3 scripts/sync_creatures.py --username lbowe_elbow --login "your@email.com" --password "yourpassword"
```

To change which site is used, set the `EGGCAVITY_SITE_URL` at the top of `sync_creatures.py` or set env `EGGCAVITY_URL`.
