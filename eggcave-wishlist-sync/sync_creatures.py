#!/usr/bin/env python3
"""
Standalone script: sync Eggcave profile creatures to eggcavity wishlist.

- Scrapes your public Eggcave profile (e.g. https://eggcave.com/@lbowe_elbow) for creatures you HAVE (no Eggcave login).
- Fetches the archive creature list from your eggcavity (fan site) instance.
- Logs in to eggcavity and adds every archive creature you DON'T have on Eggcave to your creature wishlist.

Not part of the Laravel app. Copy this folder anywhere (or upload to share); run with Python 3.
Requires: requests

Usage:
  pip install requests
  python sync_creatures.py --eggcave-username lbowe_elbow --site-url https://eggcavity.example.com --login "your@email.com" --password "yourpassword"

Or set env vars: EGGCAVE_USERNAME, EGGCAVITY_URL, EGGCAVITY_LOGIN, EGGCAVITY_PASSWORD
"""

import argparse
import re
import sys
import urllib.parse

try:
    import requests
except ImportError:
    print("Need 'requests'. Run: pip install requests", file=sys.stderr)
    sys.exit(1)

EGGCAVE_BASE = "https://eggcave.com"
DEFAULT_HEADERS = {
    "User-Agent": "eggcave-wishlist-sync/1.0 (community script)",
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

    # Match href="/archives/slug" or href="https://eggcave.com/archives/slug"
    for m in re.finditer(r'href\s*=\s*["\'](?:https?://[^"\']*?/archives/([^/"\'\s?#]+)|/archives/([^/"\'\s?#]+))["\']', r.text, re.I):
        slug = (m.group(1) or m.group(2) or "").strip()
        if slug and slug != "archives":
            slugs.add(slug)

    # Also follow pagination on profile if present (e.g. "My creatures" with next page)
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


def _find_next_profile_page(html: str, current_url: str) -> str | None:
    """Find next page URL in profile pagination (rel=next or page=N+1)."""
    # rel="next"
    m = re.search(r'<a\s[^>]*rel\s*=\s*["\']next["\'][^>]*href\s*=\s*["\']([^"\']+)["\']', html, re.I)
    if m:
        href = m.group(1).strip()
        if href.startswith("/"):
            return urllib.parse.urljoin(EGGCAVE_BASE, href)
        if href.startswith("http"):
            return href
    # href with page=N (increment current page)
    parsed = urllib.parse.urlparse(current_url)
    qs = urllib.parse.parse_qs(parsed.query)
    page = int(qs.get("page", ["1"])[0])
    next_page = page + 1
    m = re.search(rf'href\s*=\s*["\']([^"\']*[\?&]page={next_page}\d*[^"\']*)["\']', html, re.I)
    if m:
        return urllib.parse.urljoin(current_url, m.group(1).strip())
    return None


def fetch_archive_creatures(site_url: str) -> list[dict]:
    """GET fan site's public API for archive creatures."""
    api_url = urllib.parse.urljoin(site_url.rstrip("/") + "/", "api/archive-creatures")
    try:
        r = requests.get(api_url, headers=DEFAULT_HEADERS, timeout=30)
        r.raise_for_status()
        data = r.json()
        return data.get("creatures") or []
    except requests.RequestException as e:
        print(f"Failed to fetch archive creatures from {api_url}: {e}", file=sys.stderr)
        return []
    except ValueError as e:
        print(f"Invalid JSON from {api_url}: {e}", file=sys.stderr)
        return []


def login_to_site(site_url: str, login: str, password: str) -> requests.Session | None:
    """Login to eggcavity; return session with cookies and CSRF for subsequent requests."""
    base = site_url.rstrip("/")
    session = requests.Session()
    session.headers.update(DEFAULT_HEADERS)

    # GET login page for CSRF token
    try:
        r = session.get(f"{base}/login", timeout=30)
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Failed to load login page: {e}", file=sys.stderr)
        return None

    m = re.search(r'<input[^>]*name\s*=\s*["\']_token["\'][^>]*value\s*=\s*["\']([^"\']+)["\']', r.text, re.I)
    if not m:
        m = re.search(r'name\s*=\s*["\']_token["\'][^>]*value\s*=\s*["\']([^"\']+)["\']', r.text, re.I)
    token = m.group(1) if m else ""

    # POST login
    try:
        r = session.post(
            f"{base}/login",
            data={
                "_token": token,
                "login": login,
                "password": password,
                "remember": "0",
            },
            allow_redirects=True,
            timeout=30,
        )
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Login failed: {e}", file=sys.stderr)
        return None

    # Check we're not on login page anymore (redirect to home or dashboard)
    if "/login" in r.url and "login" in r.text.lower():
        print("Login failed: still on login page (wrong credentials?).", file=sys.stderr)
        return None

    return session


def add_creatures_to_wishlist(session: requests.Session, site_url: str, archive_item_ids: list[int]) -> int:
    """POST batch add to wishlist. Returns number added."""
    if not archive_item_ids:
        return 0
    base = site_url.rstrip("/")

    # Get CSRF from wishlist or add-creatures page
    try:
        r = session.get(f"{base}/wishlists/add/creatures", timeout=30)
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Failed to load wishlist page for CSRF: {e}", file=sys.stderr)
        return 0

    m = re.search(r'<input[^>]*name\s*=\s*["\']_token["\'][^>]*value\s*=\s*["\']([^"\']+)["\']', r.text, re.I)
    if not m:
        m = re.search(r'name\s*=\s*["\']_token["\'][^>]*value\s*=\s*["\']([^"\']+)["\']', r.text, re.I)
    token = m.group(1) if m else ""

    # Form POST: creatures[id][amount]=1 (Laravel batch)
    data = {"_token": token, "redirect": ""}
    for aid in archive_item_ids:
        data[f"creatures[{aid}][amount]"] = 1

    try:
        r = session.post(f"{base}/wishlist/creatures/batch", data=data, allow_redirects=True, timeout=30)
        r.raise_for_status()
    except requests.RequestException as e:
        print(f"Failed to add creatures to wishlist: {e}", file=sys.stderr)
        return 0

    return len(archive_item_ids)


def main():
    ap = argparse.ArgumentParser(
        description="Add archive creatures you don't have on Eggcave to your eggcavity wishlist."
    )
    ap.add_argument("--eggcave-username", default=None, help="Eggcave profile username (e.g. lbowe_elbow)")
    ap.add_argument("--site-url", default=None, help="Eggcavity base URL (e.g. https://eggcavity.example.com)")
    ap.add_argument("--login", default=None, help="Eggcavity login (email or name)")
    ap.add_argument("--password", default=None, help="Eggcavity password")
    args = ap.parse_args()

    username = args.eggcave_username or __import__("os").environ.get("EGGCAVE_USERNAME")
    site_url = args.site_url or __import__("os").environ.get("EGGCAVITY_URL")
    login = args.login or __import__("os").environ.get("EGGCAVITY_LOGIN")
    password = args.password or __import__("os").environ.get("EGGCAVITY_PASSWORD")

    if not username or not site_url or not login or not password:
        print("Usage: python sync_creatures.py --eggcave-username USER --site-url URL --login LOGIN --password PASS", file=sys.stderr)
        print("Or set EGGCAVE_USERNAME, EGGCAVITY_URL, EGGCAVITY_LOGIN, EGGCAVITY_PASSWORD", file=sys.stderr)
        sys.exit(1)

    print(f"Fetching creatures you have from Eggcave profile @{username}...")
    have_slugs = fetch_eggcave_creature_slugs(username)
    print(f"  Found {len(have_slugs)} creature(s) on your profile.")

    print("Fetching archive creature list from eggcavity...")
    creatures = fetch_archive_creatures(site_url)
    print(f"  Archive has {len(creatures)} creature(s).")

    have_slugs_normalized = {s.lower().strip() for s in have_slugs}
    to_add = [
        c for c in creatures
        if (c.get("slug") or "").strip().lower() not in have_slugs_normalized
    ]
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
