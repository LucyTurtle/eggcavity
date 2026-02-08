// ==UserScript==
// @name         EggCave Shop Wishlist Auto-Buy
// @namespace    https://eggcave.com/
// @version      2.1
// @description  Auto-buy wishlist items from EggCave shops (cycles through shops). When nothing to buy, goes to next shop or waits 4–5 min and refreshes. Uses items on a creature, then send to shop.
// @author       You
// @match        https://eggcave.com/shops/*
// @match        https://eggcave.com/usershops/search*
// @match        https://eggcave.com/inventory
// @match        https://eggcave.com/inventory/*
// @grant        GM_setValue
// @grant        GM_getValue
// @run-at       document-idle
// ==/UserScript==

(function () {
    'use strict';

    const WISHLIST_KEY = 'eggcave_shop_wishlist';
    const PURCHASED_KEY = 'eggcave_shop_purchased';
    const RETURN_URL_KEY = 'eggcave_shop_return_url';
    const JUST_USED_KEY = 'eggcave_just_used_item';
    const USED_ITEM_NAME_KEY = 'eggcave_used_item_name';
    const PENDING_USE_KEY = 'eggcave_pending_use';
    const SEND_TO_SHOP_PHASE_KEY = 'eggcave_send_to_shop_phase';

    // When nothing on the wishlist is available on this shop page, wait this long then refresh (after trying all shops).
    const REFRESH_WAIT_MS_MIN = 4 * 60 * 1000;
    const REFRESH_WAIT_MS_MAX = 5 * 60 * 1000;

    // Shop pathnames (cycle through these when nothing to buy on current shop).
    const SHOP_PATHS = [
        '/shops/general-food-store',
        '/shops/travel-agency',
        '/shops/toy-shop',
        '/shops/bakery',
        '/shops/bean-sack',
        '/shops/leila-library',
        '/shops/trinket-travels',
        '/shops/finleys-flavors'
    ];

    // Creature to apply Travel/items to (e.g. "Ainur" for "Ainur (Currently Traveling)"). Leave empty to use first creature not traveling.
    const CREATURE_NAME = 'Ainur';

    const INVENTORY_MAX = 50;

    // Edit this array: item names as they appear on the shop (e.g. "Juicy Jigsaw Gummies"). Bought items are removed automatically.
    // To refresh the list from this array: add ?wishlist=reset to any shop URL (e.g. .../shops/general-food-store?wishlist=reset) and load the page.
    const DEFAULT_WISHLIST = [
        'Juicy Jigsaw Gummies',
        'Celery Sticks',
        'Pink Easter Egg Basket',
        // Add more item names from the shop here…
    ];

    function randomBetween(min, max) {
        return min + Math.random() * (max - min);
    }

    function delay(ms) {
        return new Promise(function (resolve) {
            setTimeout(resolve, ms);
        });
    }

    function randomDelay(minMs, maxMs) {
        return delay(randomBetween(minMs, maxMs));
    }

    function normalizeName(str) {
        return (str || '').trim().toLowerCase();
    }

    function getWishlist() {
        const raw = GM_getValue(WISHLIST_KEY, null);
        if (Array.isArray(raw) && raw.length > 0) return raw;
        const list = DEFAULT_WISHLIST.slice();
        GM_setValue(WISHLIST_KEY, list);
        return list;
    }

    function saveWishlist(list) {
        GM_setValue(WISHLIST_KEY, list);
    }

    function getPurchased() {
        const raw = GM_getValue(PURCHASED_KEY, null);
        return Array.isArray(raw) ? raw : [];
    }

    function savePurchased(list) {
        GM_setValue(PURCHASED_KEY, list);
    }

    function setReturnUrl(url) {
        if (url) GM_setValue(RETURN_URL_KEY, url);
    }

    function getReturnUrl() {
        return GM_getValue(RETURN_URL_KEY, '') || '';
    }

    function clearReturnUrl() {
        GM_setValue(RETURN_URL_KEY, '');
    }

    function getShopList() {
        return SHOP_PATHS.slice();
    }

    function getPendingUse() {
        const raw = GM_getValue(PENDING_USE_KEY, null);
        return Array.isArray(raw) ? raw : [];
    }

    function savePendingUse(list) {
        GM_setValue(PENDING_USE_KEY, list);
    }

    function getSendToShopPhase() {
        return GM_getValue(SEND_TO_SHOP_PHASE_KEY, false);
    }

    function setSendToShopPhase(v) {
        GM_setValue(SEND_TO_SHOP_PHASE_KEY, !!v);
    }

    function isHagglePage() {
        return /^\/shops\/haggle\/[^/]+\/\d+$/.test(window.location.pathname);
    }

    function isShopListingPage() {
        const p = window.location.pathname;
        return p.startsWith('/shops/') && !p.startsWith('/shops/haggle/');
    }

    function isInventoryPage() {
        return window.location.pathname === '/inventory';
    }

    function isInventoryShowPage() {
        return /^\/inventory\/\d+\/show$/.test(window.location.pathname);
    }

    // Parse sidebar: inventory count (e.g. "1 Item" or "35 Items"). Returns number or -1 if not found.
    function getSidebarInventoryCount() {
        const link = document.querySelector('a.sidenav-link[href*="inventory"]');
        if (!link) return -1;
        const text = (link.textContent || '').trim();
        const m = text.match(/(\d+)\s*Item(s)?/i);
        return m ? parseInt(m[1], 10) : -1;
    }

    // Parse sidebar: Egg Coins (e.g. "74,539 EC"). Returns number or -1 if not found.
    function getSidebarEC() {
        const link = document.getElementById('egg-coins') || document.querySelector('a.sidenav-link[href*="strongroom"]');
        if (!link) return -1;
        const text = (link.textContent || '').trim();
        const m = text.replace(/,/g, '').match(/(\d+)\s*EC/i);
        return m ? parseInt(m[1], 10) : -1;
    }

    // ----- Return from "use on creature": use next pending item or go back to shop -----
    function runReturnFromUse() {
        if (!GM_getValue(JUST_USED_KEY, false)) return Promise.resolve();
        const returnUrl = getReturnUrl();
        const usedName = (GM_getValue(USED_ITEM_NAME_KEY, '') || '').trim();
        const purchased = getPurchased().filter(function (n) {
            return normalizeName(n) !== normalizeName(usedName);
        });
        savePurchased(purchased);
        GM_setValue(JUST_USED_KEY, false);
        GM_setValue(USED_ITEM_NAME_KEY, '');
        let pending = getPendingUse().filter(function (entry) {
            return normalizeName(entry.name) !== normalizeName(usedName);
        });
        savePendingUse(pending);
        if (pending.length > 0) {
            const next = pending[0];
            const nextUrl = (next.href && next.href.indexOf('http') === 0)
                ? next.href
                : (window.location.origin + (next.href && next.href.indexOf('/') === 0 ? next.href : '/' + (next.href || '')));
            return randomDelay(1000, 3000).then(function () {
                    window.location.href = nextUrl;
                });
        }
        setSendToShopPhase(true);
        return randomDelay(1000, 3000).then(function () {
            window.location.href = 'https://eggcave.com/inventory';
        });
    }

    // ----- Haggle page: fill suggested price and submit -----
    function runOnHagglePage(wishlist) {
        const eggcave = document.getElementById('eggcave');
        if (!eggcave) return Promise.resolve();

        const h1 = eggcave.querySelector('h1');
        const itemName = h1 ? h1.textContent.replace(/^Haggling:\s*/i, '').trim() : '';
        if (!itemName) return Promise.resolve();

        const isWanted = wishlist.some(function (w) {
            return normalizeName(w) === normalizeName(itemName);
        });
        if (!isWanted) return Promise.resolve();

        const priceEl = eggcave.querySelector('strong.is-size-3');
        const priceText = priceEl ? priceEl.textContent : '';
        const priceMatch = priceText.match(/[\d,]+/);
        const price = priceMatch ? priceMatch[0].replace(/,/g, '') : '';
        const form = document.getElementById('haggle-form');
        const input = form && form.querySelector('input[name="egg_coins"]');

        if (!price || !form || !input) return Promise.resolve();

        return randomDelay(1000, 3000)
            .then(function () {
                if (getSidebarEC() === 0) return;
                input.value = price;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                return randomDelay(500, 1500);
            })
            .then(function () {
                if (getSidebarEC() === 0) return;
                const newList = wishlist.filter(function (w) {
                    return normalizeName(w) !== normalizeName(itemName);
                });
                saveWishlist(newList);
                const purchased = getPurchased();
                if (purchased.indexOf(itemName) === -1) purchased.push(itemName);
                savePurchased(purchased);
                form.submit();
            });
    }

    // ----- Shop listing page: find wishlist items and go to first match (random pick), or refresh -----
    function runOnShopListingPage(wishlist) {
        const offerAcceptedClose = document.querySelector('button.close-modal[data-modal-id="#flash-notification"], .modal-card-head button.close-modal');
        const closeModalThenRun = offerAcceptedClose
            ? randomDelay(500, 1500).then(function () {
                offerAcceptedClose.click();
                return randomDelay(400, 900);
            })
            : Promise.resolve();

        return closeModalThenRun.then(function () {
            return runOnShopListingPageCore(wishlist);
        });
    }

    function runOnShopListingPageCore(wishlist) {
        const columns = document.querySelectorAll('#eggcave .column a[href^="/shops/haggle/"]');
        const matches = [];

        columns.forEach(function (a) {
            const strong = a.querySelector('strong');
            const name = (strong && strong.textContent.trim()) ||
                (a.getAttribute('title') || '').trim() ||
                (a.querySelector('img') && a.querySelector('img').getAttribute('alt')) || '';
            if (!name) return;
            const wanted = wishlist.some(function (w) {
                return normalizeName(w) === normalizeName(name);
            });
            if (wanted) {
                matches.push({
                    name: name,
                    href: a.getAttribute('href')
                });
            }
        });

        const ec = getSidebarEC();
        if (ec === 0) return Promise.resolve();
        const invCount = getSidebarInventoryCount();
        const purchased = getPurchased();
        const fullOrNoPurchased = invCount >= INVENTORY_MAX && purchased.length === 0;

        if (matches.length === 0 || fullOrNoPurchased) {
            const shopList = getShopList();
            const currentPath = window.location.pathname.replace(/\/$/, '');
            const idx = shopList.indexOf(currentPath);
            const nextIdx = idx >= 0 ? idx + 1 : 0;
            if (nextIdx < shopList.length) {
                const nextPath = shopList[nextIdx];
                const nextUrl = nextPath.startsWith('http') ? nextPath : (window.location.origin + nextPath);
                return randomDelay(2000, 10000).then(function () {
                    window.location.href = nextUrl;
                });
            }
            if (purchased.length > 0) {
                setReturnUrl(window.location.origin + (shopList[0] || '/shops/general-food-store'));
                return randomDelay(1000, 3000).then(function () {
                    window.location.href = 'https://eggcave.com/inventory';
                });
            }
            const firstPath = shopList[0];
            const firstUrl = firstPath && firstPath.startsWith('http') ? firstPath : (window.location.origin + (firstPath || '/shops/general-food-store'));
            return randomDelay(2000, 10000).then(function () {
                window.location.href = firstUrl;
            });
        }

        if (invCount >= INVENTORY_MAX && purchased.length > 0) {
            setReturnUrl(window.location.href);
            return randomDelay(1500, 4500).then(function () {
                window.location.href = 'https://eggcave.com/inventory';
            });
        }

        const pick = matches[Math.floor(Math.random() * matches.length)];
        const fullHref = pick.href.startsWith('http') ? pick.href : (window.location.origin + pick.href);

        return randomDelay(2000, 5000).then(function () {
            window.location.href = fullHref;
        });
    }

    // ----- Inventory page: send-all-to-shop phase (check all + Send to Shop), or queue purchased items for use -----
    function runOnInventoryPage(purchased) {
        if (getSendToShopPhase()) {
            setSendToShopPhase(false);
            const form = document.getElementById('inventoryForm') || document.querySelector('#eggcave form[action*="inventory/move"]');
            if (!form) return Promise.resolve();
            const actionInput = form.querySelector('input[name="action"]');
            const checkboxes = form.querySelectorAll('input.move_items, input[name="move_items[]"]');
            if (!actionInput || !checkboxes.length) return Promise.resolve();
            checkboxes.forEach(function (cb) {
                if (!cb.checked) {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            actionInput.value = 'usershop';
            return randomDelay(1000, 3500).then(function () {
                form.submit();
            });
        }

        const links = document.querySelectorAll('#eggcave a[href*="/inventory/"][href*="/show"]');
        const matches = [];
        links.forEach(function (a) {
            const href = (a.getAttribute('href') || '').trim();
            const name = (a.textContent || '').trim().replace(/\s+/g, ' ');
            if (!href || !name) return;
            const wanted = purchased.some(function (p) {
                return normalizeName(p) === normalizeName(name) ||
                    name.toLowerCase().indexOf(normalizeName(p)) !== -1 ||
                    normalizeName(p).indexOf(normalizeName(name)) !== -1;
            });
            if (wanted) {
                const idMatch = href.match(/\/inventory\/(\d+)\/show/);
                if (idMatch) matches.push({ name: name, id: idMatch[1], href: href });
            }
        });

        if (matches.length === 0) {
            savePendingUse([]);
            const returnUrl = getReturnUrl();
            if (returnUrl) {
                clearReturnUrl();
                return randomDelay(1200, 3300).then(function () { window.location.href = returnUrl; });
            }
            return Promise.resolve();
        }
        savePendingUse(matches);
        const first = matches[0];
        const fullHref = first.href.indexOf('http') === 0 ? first.href : (window.location.origin + (first.href.indexOf('/') === 0 ? first.href : '/' + first.href));
        return randomDelay(1500, 4500).then(function () {
            window.location.href = fullHref;
        });
    }

    // ----- Inventory item show page: select creature and submit (Travel "Apply Background" or Food/Drink "Consume") -----
    function runOnInventoryShowPage(purchased) {
        const eggcave = document.getElementById('eggcave');
        if (!eggcave) return Promise.resolve();
        // Same form pattern for Travel (backgrounds) and Food/Drink items
        const form = eggcave.querySelector('form[action*="/inventory/"][action*="/use"]');
        if (!form) return Promise.resolve();
        const select = form.querySelector('select[name="creature"]#creature');
        if (!select || !select.options.length) return Promise.resolve();

        const itemNameEl = eggcave.querySelector('h1');
        const itemName = (itemNameEl && itemNameEl.textContent.replace(/^View Item:\s*/i, '').trim()) || '';
        const isPurchased = !itemName || purchased.some(function (p) {
            return normalizeName(p) === normalizeName(itemName) ||
                (itemName.toLowerCase().indexOf(normalizeName(p)) !== -1) ||
                (normalizeName(p).indexOf(normalizeName(itemName)) !== -1);
        });
        if (!isPurchased) {
            const returnUrl = getReturnUrl();
            if (returnUrl) return randomDelay(800, 2700).then(function () { window.location.href = returnUrl; });
            return Promise.resolve();
        }

        let optionToUse = null;
        const wantName = (CREATURE_NAME || '').trim().toLowerCase();
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            if (!opt.value) continue;
            const text = (opt.textContent || '').trim();
            if (wantName && text.toLowerCase().indexOf(wantName) !== -1) {
                optionToUse = opt;
                break;
            }
            // Fallback: first creature (Travel pages show "Currently Traveling", Food/Drink show "Happiness: 100")
            if (!optionToUse) optionToUse = opt;
        }
        if (!optionToUse) optionToUse = select.options[1]; // skip "Please Select"
        if (!optionToUse || !optionToUse.value) return Promise.resolve();

        select.value = optionToUse.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));

        GM_setValue(JUST_USED_KEY, true);
        GM_setValue(USED_ITEM_NAME_KEY, itemName);

        return randomDelay(1000, 3500).then(function () {
            form.submit();
        });
    }

    // ----- Main -----
    if (/[?&]wishlist=reset(?=&|$)/i.test(window.location.search)) {
        GM_setValue(WISHLIST_KEY, null);
        if (window.history && window.history.replaceState) {
            var q = window.location.search.replace(/\?wishlist=reset&?/i, '?').replace(/&wishlist=reset(?=&|$)/i, '').replace(/\?$/, '');
            window.history.replaceState(null, '', window.location.pathname + q + window.location.hash);
        }
    }
    var wishlist = getWishlist();
    var purchased = getPurchased();
    var wasJustUsed = GM_getValue(JUST_USED_KEY, false);
    var p = runReturnFromUse();
    if (p && p.then) p = p.catch(function () {});
    else p = Promise.resolve();
    if (!wasJustUsed) {
        if (isHagglePage()) {
            p = p.then(function () { return runOnHagglePage(wishlist); });
        } else if (isShopListingPage()) {
            p = p.then(function () { return runOnShopListingPage(wishlist); });
        } else if (isInventoryShowPage()) {
            p = p.then(function () { return runOnInventoryShowPage(purchased); });
        } else if (isInventoryPage()) {
            p = p.then(function () { return runOnInventoryPage(purchased); });
        }
    }
    p.catch(function (err) {
        console.warn('[EggCave Auto-Buy]', err);
    });
})();
