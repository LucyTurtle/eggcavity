#!/usr/bin/env python3
"""
Sync Eggcave profile creatures to your eggcavity wishlist.

- Scrapes your public Eggcave profile (eggcave.com/@USERNAME) for creatures you HAVE.
- Adds every archive creature you DON'T have to your eggcavity creature wishlist.

Set EGGCAVITY_SITE_URL below once (your fan site URL). Then you only need username + login + password.

Usage:
  pip3 install -r scripts/requirements.txt
  python3 scripts/sync_creatures.py --username lbowe_elbow --login "your@email.com" --password "yourpassword"
"""

import argparse
import os
import re
import sys
import urllib.parse
from typing import Optional

try:
    import requests
except ImportError:
    print("Need 'requests'. Run: pip3 install -r scripts/requirements.txt", file=sys.stderr)
    sys.exit(1)

# Set this to your eggcavity (fan site) base URL — not eggcave.com
EGGCAVITY_SITE_URL = os.environ.get("EGGCAVITY_URL", "https://eggcavity.com")
EGGCAVE_BASE = "https://eggcave.com"
DEFAULT_HEADERS = {
    "User-Agent": "eggcave-wishlist-sync/1.0",
    "Accept": "text/html,application/xhtml+xml,application/json",
    "Accept-Language": "en-US,en;q=0.9",
}


def fetch_eggcave_creature_slugs(username: str) -> set[str]:
    """Scrape Eggcave profile page for /archives/... links = creatures the user has."""
    url = f"{EGGCAVE_BASE}/@{username}"
    slugs = set()
    try:
        r = requests.get(url, headers=DEFAULT_HEADERS, timeout=30)
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Failed to fetch Eggcave profile {url}: {e}", file=sys.stderr)
        return slugs

    for m in re.finditer(r'href\s*=\s*["\'](?:https?://[^"\']*?/archives/([^/"\'\s?#]+)|/archives/([^/"\'\s?#]+))["\']', r.text, re.I):
        slug = (m.group(1) or m.group(2) or "").strip()
        if slug and slug != "archives":
            slugs.add(slug)

    next_url = _find_next_profile_page(r.text, url)
    while next_url:
        try:
            r = requests.get(next_url, headers=DEFAULT_HEADERS, timeout=30)
            r.raise_for_status()
        except requests.RequestException:
            break
        for m in re.finditer(r'href\s*=\s*["\'](?:https?://[^"\']*?/archives/([^/"\'\s?#]+)|/archives/([^/"\'\s?#]+))["\']', r.text, re.I):
            slug = (m.group(1) or m.group(2) or "").strip()
            if slug and slug != "archives":
                slugs.add(slug)
        next_url = _find_next_profile_page(r.text, next_url)

    return slugs


def _find_next_profile_page(html: str, current_url: str) -> Optional[str]:
    m = re.search(r'<a\s[^>]*rel\s*=\s*["\']next["\'][^>]*href\s*=\s*["\']([^"\']+)["\']', html, re.I)
    if m:
        href = m.group(1).strip()
        if href.startswith("/"):
            return urllib.parse.urljoin(EGGCAVE_BASE, href)
        if href.startswith("http"):
            return href
    parsed = urllib.parse.urlparse(current_url)
    qs = urllib.parse.parse_qs(parsed.query)
    page = int(qs.get("page", ["1"])[0])
    next_page = page + 1
    m = re.search(rf'href\s*=\s*["\']([^"\']*[\?&]page={next_page}\d*[^"\']*)["\']', html, re.I)
    if m:
        return urllib.parse.urljoin(current_url, m.group(1).strip())
    return None


def fetch_archive_creatures(site_url: str) -> list[dict]:
    api_url = urllib.parse.urljoin(site_url.rstrip("/") + "/", "api/archive-creatures")
    try:
        r = requests.get(api_url, headers=DEFAULT_HEADERS, timeout=30)
        r.raise_for_status()
        return (r.json() or {}).get("creatures") or []
    except (requests.RequestException, ValueError) as e:
        print(f"Failed to fetch archive from {api_url}: {e}", file=sys.stderr)
        return []


def login_to_site(site_url: str, login: str, password: str) -> Optional[requests.Session]:
    base = site_url.rstrip("/")
    session = requests.Session()
    session.headers.update(DEFAULT_HEADERS)
    try:
        r = session.get(f"{base}/login", timeout=30)
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Failed to load login page: {e}", file=sys.stderr)
        return None

    m = re.search(r'name\s*=\s*["\']_token["\'][^>]*value\s*=\s*["\']([^"\']+)["\']', r.text, re.I) or re.search(r'value\s*=\s*["\']([^"\']+)["\'][^>]*name\s*=\s*["\']_token["\']', r.text, re.I)
    token = m.group(1) if m else ""

    try:
        r = session.post(f"{base}/login", data={"_token": token, "login": login, "password": password, "remember": "0"}, allow_redirects=True, timeout=30)
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Login failed: {e}", file=sys.stderr)
        return None
    if "/login" in r.url and "login" in r.text.lower():
        print("Login failed: wrong credentials?", file=sys.stderr)
        return None
    return session


def add_creatures_to_wishlist(session: requests.Session, site_url: str, archive_item_ids: list[int]) -> int:
    if not archive_item_ids:
        return 0
    base = site_url.rstrip("/")
    try:
        r = session.get(f"{base}/wishlists/add/creatures", timeout=30)
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Failed to load wishlist page: {e}", file=sys.stderr)
        return 0

    m = re.search(r'name\s*=\s*["\']_token["\'][^>]*value\s*=\s*["\']([^"\']+)["\']', r.text, re.I) or re.search(r'value\s*=\s*["\']([^"\']+)["\'][^>]*name\s*=\s*["\']_token["\']', r.text, re.I)
    token = m.group(1) if m else ""
    data = {"_token": token, "redirect": ""}
    for aid in archive_item_ids:
        data[f"creatures[{aid}][amount]"] = 1

    try:
        r = session.post(f"{base}/wishlist/creatures/batch", data=data, allow_redirects=True, timeout=30)
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Failed to add creatures: {e}", file=sys.stderr)
        return 0
    return len(archive_item_ids)


def main():
    ap = argparse.ArgumentParser(description="Add archive creatures you don't have on Eggcave to your eggcavity wishlist.")
    ap.add_argument("--username", "-u", default=None, help="Eggcave profile username (e.g. lbowe_elbow)")
    ap.add_argument("--login", "-l", default=None, help="Eggcavity login (email or name)")
    ap.add_argument("--password", "-p", default=None, help="Eggcavity password")
    ap.add_argument("--site-url", default=None, help=f"Eggcavity URL (default: {EGGCAVITY_SITE_URL})")
    args = ap.parse_args()

    username = args.username or os.environ.get("EGGCAVE_USERNAME")
    login = args.login or os.environ.get("EGGCAVITY_LOGIN")
    password = args.password or os.environ.get("EGGCAVITY_PASSWORD")
    site_url = (args.site_url or os.environ.get("EGGCAVITY_URL") or EGGCAVITY_SITE_URL).rstrip("/")

    if not username or not login or not password:
        print("Usage: python3 scripts/sync_creatures.py --username lbowe_elbow --login YOUR_EMAIL --password YOUR_PASSWORD", file=sys.stderr)
        print("(Site URL is set in the script or EGGCAVITY_URL env.)", file=sys.stderr)
        sys.exit(1)

    print(f"Fetching creatures you have from Eggcave @{username}...")
    have_slugs = fetch_eggcave_creature_slugs(username)
    print(f"  Found {len(have_slugs)} creature(s) on your profile.")

    print("Fetching archive from eggcavity...")
    creatures = fetch_archive_creatures(site_url)
    print(f"  Archive has {len(creatures)} creature(s).")

    have_normalized = {s.lower().strip() for s in have_slugs}
    to_add = [c for c in creatures if (c.get("slug") or "").strip().lower() not in have_normalized]
    if not to_add:
        print("Nothing to add: you have every archive creature (or archive is empty).")
        return

    print(f"Adding {len(to_add)} creature(s) to your wishlist: {[c.get('title') for c in to_add]}")

    session = login_to_site(site_url, login, password)
    if not session:
        sys.exit(1)
    added = add_creatures_to_wishlist(session, site_url, [c["id"] for c in to_add])
    print(f"Done. Added {added} creature(s) to your wishlist.")


if __name__ == "__main__":
    main()
