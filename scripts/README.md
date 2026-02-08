# Scripts

## Tampermonkey: EggCave shop wishlist auto-buy

`eggcave-shop-wishlist-autobuy.user.js` is a userscript for [Tampermonkey](https://www.tampermonkey.net/) that runs on **eggcave.com/shops/***. It:

- Keeps a **wishlist** (edit the `DEFAULT_WISHLIST` array in the script).
- On a **shop listing page** (e.g. General Food Store): finds any in-stock item whose name matches the wishlist, waits a random 2–8 seconds, then opens that item’s haggle page.
- On a **haggle page**: fills the offer with the **suggested amount** (the EC price shown on the page), waits a random 1–4 seconds, submits the offer, then **removes that item from the wishlist** (stored via Tampermonkey) so it isn’t bought again.
- If no wishlist items are in stock, **refreshes the shop page** after a random **~5 minutes** (4.5–5.5).

**Install:** Install Tampermonkey, then create a new script and paste the contents of `eggcave-shop-wishlist-autobuy.user.js`, or install from file. Edit `DEFAULT_WISHLIST` with the exact item names as shown on the shop (e.g. `"Juicy Jigsaw Gummies"`). Leave the tab open on any shop page (e.g. General Food Store or Travel Agency) and it will run automatically.

---

## Tampermonkey: EggCave shop reprice

`eggcave-shop-reprice.user.js` runs on **eggcave.com/usershops/stock** (Manage Stock). When you open the page it **searches each item** on the user shop search (exact match for full names; partial match when the name ends with “…”), takes the **best user-shop price**, and sets your price to **5% under** that; if no user-shop results, it uses the **buy (restock) price** from the eggcavity API or `DEFAULT_PRICE_LIST`. Optional: set `DEFAULT_PRICE_LIST` in the script to `[{ name: "Item Name", price: 494 }, ...]` for items the API doesn’t have; developers can copy the “Reprice” array from the item wishlist page.

---

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
