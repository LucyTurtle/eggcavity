// ==UserScript==
// @name         EggCave User Shop Wishlist Buy
// @namespace    https://eggcave.com/
// @version      1.0
// @description  Search user shops for wishlist items (exact match). Auto-buy under 5,000 EC; prompt for 5,000+ EC. Retired items go to stronghold (not used on creature). Uses same wishlist as shop auto-buy. Add ?wishlist=reset to reset.
// @author       You
// @match        https://eggcave.com/usershops/search*
// @match        https://eggcave.com/@*/shop*
// @match        https://eggcave.com/inventory
// @match        https://eggcave.com/inventory/*
// @grant        GM_getValue
// @grant        GM_setValue
// @run-at       document-idle
// ==/UserScript==

(function () {
    'use strict';

    const WISHLIST_KEY = 'eggcave_shop_wishlist';
    const INDEX_KEY = 'eggcave_usershop_buy_index';
    const PENDING_BUY_KEY = 'eggcave_usershop_pending_buy';
    const JUST_BOUGHT_KEY = 'eggcave_usershop_just_bought';
    const STOPPED_KEY = 'eggcave_usershop_stopped';
    const PURCHASED_KEY = 'eggcave_shop_purchased';
    const USE_BOUGHT_KEY = 'eggcave_usershop_use_bought';
    const USE_BOUGHT_PENDING_KEY = 'eggcave_usershop_use_bought_pending';
    const USE_BOUGHT_JUST_USED_KEY = 'eggcave_usershop_use_just_used';
    const USE_BOUGHT_USED_NAME_KEY = 'eggcave_usershop_use_used_name';
    const USE_BOUGHT_SUBMIT_PATH_KEY = 'eggcave_usershop_use_submit_path';
    const USE_BOUGHT_SEND_TO_SHOP_KEY = 'eggcave_usershop_send_to_shop';
    const INVENTORY_FULL_COUNT = 50;
    const MAX_AUTO_EC = 5000;
    const IGNORE_SHOP_USERNAME = 'lbowe_elbow';
    const USE_ON_CREATURE = 'Ainur';

    /** If the item page shows "Retired", we move to stronghold instead of using on creature. */
    function isItemRetired(container) {
        if (!container) return false;
        const text = (container.textContent || '').toLowerCase();
        return text.indexOf('retired') !== -1;
    }

    /** Find a form that moves the item to stronghold (e.g. action contains "stronghold" or move destination). */
    function findStrongholdForm(eggcave) {
        if (!eggcave) return null;
        const forms = eggcave.querySelectorAll('form[action*="/inventory/"]');
        for (let i = 0; i < forms.length; i++) {
            const f = forms[i];
            const action = (f.getAttribute('action') || '').toLowerCase();
            if (action.indexOf('/use') !== -1) continue;
            if (action.indexOf('stronghold') !== -1) return f;
            const formText = (f.textContent || '').toLowerCase();
            if (formText.indexOf('stronghold') !== -1) return f;
        }
        return null;
    }

    /** Find a form that moves the item to strongroom (item didn't disappear after use). */
    function findStrongroomForm(eggcave) {
        if (!eggcave) return null;
        const forms = eggcave.querySelectorAll('form[action*="/inventory/"]');
        for (let i = 0; i < forms.length; i++) {
            const f = forms[i];
            const action = (f.getAttribute('action') || '').toLowerCase();
            if (action.indexOf('/use') !== -1) continue;
            if (action.indexOf('strongroom') !== -1) return f;
            const formText = (f.textContent || '').toLowerCase();
            if (formText.indexOf('strongroom') !== -1) return f;
        }
        return null;
    }

    const DEFAULT_WISHLIST = [
        'Juicy Jigsaw Gummies',
        'Celery Sticks',
        'Pink Easter Egg Basket',
    ];

    function normalizeName(str) {
        return (str || '').trim().toLowerCase();
    }

    function getWishlist() {
        const raw = GM_getValue(WISHLIST_KEY, null);
        if (Array.isArray(raw) && raw.length > 0) return raw;
        return DEFAULT_WISHLIST.slice();
    }

    function saveWishlist(list) {
        GM_setValue(WISHLIST_KEY, Array.isArray(list) ? list : []);
    }

    function getIndex() {
        const v = GM_getValue(INDEX_KEY, 0);
        return typeof v === 'number' && v >= 0 ? v : 0;
    }

    function setIndex(v) {
        GM_setValue(INDEX_KEY, Math.max(0, parseInt(v, 10)));
    }

    function getPendingBuy() {
        const raw = GM_getValue(PENDING_BUY_KEY, null);
        return raw && typeof raw === 'object' && raw.name && raw.href != null ? raw : null;
    }

    function setPendingBuy(obj) {
        GM_setValue(PENDING_BUY_KEY, obj ? { name: obj.name, href: obj.href } : null);
    }

    function parseEC(str) {
        if (!str || typeof str !== 'string') return -1;
        const m = str.replace(/,/g, '').match(/(\d+)\s*EC/i);
        return m ? parseInt(m[1], 10) : -1;
    }

    function getSidebarEC() {
        const link = document.getElementById('egg-coins') || document.querySelector('a.sidenav-link[href*="strongroom"]');
        if (!link) return -1;
        const text = (link.textContent || '').trim();
        const m = text.replace(/,/g, '').match(/(\d+)\s*EC/i);
        return m ? parseInt(m[1], 10) : -1;
    }

    /** Return inventory item count from sidebar (e.g. "50 Items"), or -1 if not found. */
    function getSidebarItemCount() {
        const links = document.querySelectorAll('a.sidenav-link[href*="/inventory"]');
        for (let i = 0; i < links.length; i++) {
            const href = (links[i].getAttribute('href') || '').trim();
            const path = href.replace(/^https?:\/\/[^/]+/, '') || href;
            if (!/^\/inventory\/?$/.test(path)) continue;
            const text = (links[i].textContent || '').trim();
            const m = text.replace(/,/g, '').match(/(\d+)\s*Items?/i);
            if (m) return parseInt(m[1], 10);
        }
        return -1;
    }

    function getPurchased() {
        const raw = GM_getValue(PURCHASED_KEY, null);
        return Array.isArray(raw) ? raw : [];
    }

    function savePurchased(list) {
        GM_setValue(PURCHASED_KEY, list);
    }

    function isStopped() {
        return GM_getValue(STOPPED_KEY, false) === true;
    }

    function setStopped(stopped) {
        GM_setValue(STOPPED_KEY, !!stopped);
    }

    /** Random delay (ms) to vary timing between actions. */
    function randomDelay(minMs, maxMs) {
        const ms = minMs + Math.random() * (maxMs - minMs);
        return new Promise(function (r) { setTimeout(r, ms); });
    }

    function isSearchFormPage() {
        const p = window.location.pathname;
        if (p !== '/usershops/search') return false;
        const q = (window.location.search || '').trim();
        return q.indexOf('q=') === -1;
    }

    function isSearchResultsPage() {
        const p = window.location.pathname;
        const q = (window.location.search || '').trim();
        return p === '/usershops/search' && q.indexOf('q=') !== -1;
    }

    function isShopPage() {
        return /^\/@[^/]+\/shop\/?(\?|$)/.test(window.location.pathname) || /^\/@[^/]+\/shop/.test(window.location.pathname);
    }

    function isInventoryPage() {
        return window.location.pathname === '/inventory';
    }

    function isInventoryShowPage() {
        return /^\/inventory\/\d+\/show$/.test(window.location.pathname);
    }

    function isInventoryUsedPage() {
        return window.location.pathname === '/inventory/used';
    }

    function detectOutOfSpace() {
        const text = (document.body && document.body.textContent) || '';
        const lower = text.toLowerCase();
        return (lower.indexOf('inventory') !== -1 && (lower.indexOf('full') !== -1 || lower.indexOf('no space') !== -1 || lower.indexOf('not enough space') !== -1)) ||
            lower.indexOf('no room') !== -1 || lower.indexOf('cannot hold') !== -1;
    }

    /** Return current inventory item count (sidebar first, then page), or -1 if unknown. */
    function getInventoryCount() {
        const sidebar = getSidebarItemCount();
        if (sidebar >= 0) return sidebar;
        const eggcave = document.getElementById('eggcave');
        const root = eggcave || document.body;
        if (!root) return -1;
        const links = root.querySelectorAll('a[href*="/inventory/"][href*="/show"]');
        if (links && links.length > 0) return links.length;
        const text = (root.textContent || '').trim();
        const of50 = text.match(/(\d+)\s*of\s*50/i) || text.match(/\/\s*50\b/);
        if (of50) return parseInt(of50[1], 10) || 50;
        const totalMatch = text.match(/(\d+)\s*item/i);
        if (totalMatch) return parseInt(totalMatch[1], 10);
        return -1;
    }

    function runOnSearchForm() {
        if (isStopped()) return;
        function fillAndSubmit() {
            let form = document.querySelector('#eggcave form[action*="usershops/search"]') || document.querySelector('form[action*="usershops/search"]');
            if (!form) {
                const byInput = document.querySelector('#eggcave input[name="q"]') || document.querySelector('input[name="q"]');
                if (byInput && byInput.form) form = byInput.form;
            }
            if (!form) return false;
            const qInput = form.querySelector('input[name="q"]') || document.getElementById('q') || form.querySelector('input[type="search"]') || form.querySelector('input[type="text"]');
            const matchSelect = form.querySelector('select[name="match"]') || form.querySelector('select');
            if (!qInput || !matchSelect) return false;
            const wishlist = getWishlist();
            let idx = getIndex();
            if (wishlist.length === 0) return true;
            if (idx >= wishlist.length) {
                setIndex(0);
                idx = 0;
            }
            const itemName = wishlist[idx];
            qInput.value = itemName;
            qInput.setAttribute('value', itemName);
            qInput.dispatchEvent(new Event('input', { bubbles: true }));
            qInput.dispatchEvent(new Event('change', { bubbles: true }));
            const exactOpt = Array.prototype.find.call(matchSelect.options, function (o) { return (o.value || '').toLowerCase() === 'exact'; });
            if (exactOpt) matchSelect.value = exactOpt.value;
            matchSelect.dispatchEvent(new Event('change', { bubbles: true }));
            randomDelay(800, 2200).then(function () { form.submit(); });
            return true;
        }
        function tryFill(attempt) {
            if (isStopped()) return;
            if (fillAndSubmit()) return;
            if (attempt < 8) setTimeout(function () { tryFill(attempt + 1); }, 400 + attempt * 200);
        }
        tryFill(0);
    }

    function parseResultsFromPage() {
        const eggcave = document.getElementById('eggcave');
        if (!eggcave) return [];
        const ignore = (IGNORE_SHOP_USERNAME || '').trim().toLowerCase();
        const columns = eggcave.querySelectorAll('.columns .column');
        const results = [];
        columns.forEach(function (col) {
            const link = col.querySelector('a[href*="/shop"]');
            if (!link) return;
            const href = (link.getAttribute('href') || '').trim();
            if (href.indexOf('/shop') === -1) return;
            const fullHref = href.indexOf('http') === 0 ? href : (window.location.origin + (href.indexOf('/') === 0 ? href : '/' + href));
            const strong = link.querySelector('strong');
            const name = (strong && strong.textContent.trim()) || '';
            const userLink = col.querySelector('a[href^="/@"]');
            const username = (userLink && userLink.textContent.trim()) || '';
            const userHref = (userLink && userLink.getAttribute('href')) || '';
            if (ignore && (normalizeName(username).indexOf(ignore) !== -1 || normalizeName(userHref).indexOf('/@' + ignore) !== -1)) return;
            const rowText = (col.textContent || '');
            const priceMatch = rowText.replace(/,/g, '').match(/(\d+)\s*EC/);
            const price = priceMatch ? parseInt(priceMatch[1], 10) : -1;
            if (name && fullHref && price > 0) results.push({ name: name, href: fullHref, price: price });
        });
        return results;
    }

    function runOnSearchResults() {
        if (isStopped()) return;
        const wishlist = getWishlist();
        let idx = getIndex();
        if (idx >= wishlist.length) return;
        const currentItem = wishlist[idx];
        const results = parseResultsFromPage();
        if (results.length === 0) {
            setIndex(idx + 1);
            randomDelay(1000, 2500).then(function () {
                window.location.href = 'https://eggcave.com/usershops/search';
            });
            return;
        }
        results.sort(function (a, b) { return a.price - b.price; });
        const pick = results[0];
        const ec = getSidebarEC();
        if (ec >= 0 && ec < pick.price) {
            setStopped(true);
            window.alert("Out of EC (have " + ec + ", need " + pick.price + "). Stopping.");
            return;
        }
        function goNext() {
            setIndex(idx + 1);
            setPendingBuy(null);
            if (idx + 1 >= wishlist.length) {
                window.location.href = 'https://eggcave.com/usershops/search';
                return;
            }
            const nextName = wishlist[idx + 1];
            window.location.href = 'https://eggcave.com/usershops/search?q=' + encodeURIComponent(nextName) + '&match=exact';
        }
        if (pick.price >= MAX_AUTO_EC) {
            const ok = window.confirm("Buy \"" + pick.name + "\" for " + pick.price + " EC?");
            if (!ok) {
                goNext();
                return;
            }
        }
        setPendingBuy({ name: pick.name, href: pick.href });
        randomDelay(1000, 3000).then(function () {
            window.location.href = pick.href;
        });
    }

    function runOnShopPage() {
        const pending = getPendingBuy();
        if (!pending || !pending.name) return;
        const eggcave = document.getElementById('eggcave');
        if (!eggcave) return;
        const wantName = normalizeName(pending.name);
        const columns = eggcave.querySelectorAll('.column');
        let formToSubmit = null;
        let price = 0;
        columns.forEach(function (col) {
            const strong = col.querySelector('strong');
            const name = (strong && strong.textContent.trim()) || '';
            if (normalizeName(name) !== wantName) return;
            const f = col.querySelector('form[action*="usershops/buy"]');
            if (f) {
                formToSubmit = f;
                const priceInput = f.querySelector('input[name="original_price"]');
                if (priceInput && priceInput.value) price = parseInt(priceInput.value, 10) || 0;
            }
        });
        if (!formToSubmit) {
            setPendingBuy(null);
            setIndex(getIndex() + 1);
            return;
        }
        const ec = getSidebarEC();
        if (ec >= 0 && ec < price) {
            setStopped(true);
            setPendingBuy(null);
            setIndex(getIndex() + 1);
            window.alert("Out of EC (have " + ec + ", need " + price + "). Stopping.");
            return;
        }
        setPendingBuy(null);
        setStopped(false);
        const purchased = getPurchased();
        if (purchased.indexOf(pending.name) === -1) {
            purchased.push(pending.name);
            savePurchased(purchased);
        }
        const wishlist = getWishlist().filter(function (w) {
            return normalizeName(w) !== normalizeName(pending.name);
        });
        saveWishlist(wishlist);
        setIndex(getIndex());
        GM_setValue(JUST_BOUGHT_KEY, Date.now());
        randomDelay(1000, 3000).then(function () {
            formToSubmit.submit();
        });
    }

    function checkJustBoughtRedirect() {
        if (!isShopPage()) return;
        if (getPendingBuy()) return;
        const t = GM_getValue(JUST_BOUGHT_KEY, 0);
        if (!t || Date.now() - t > 15000) return;
        GM_setValue(JUST_BOUGHT_KEY, 0);
        if (detectOutOfSpace()) {
            const count = getSidebarItemCount();
            if (count >= INVENTORY_FULL_COUNT) {
                GM_setValue(USE_BOUGHT_KEY, true);
                randomDelay(1200, 3500).then(function () {
                    window.location.href = 'https://eggcave.com/inventory';
                });
            } else {
                const wishlist = getWishlist();
                const idx = getIndex();
                const go = function () {
                    if (idx >= wishlist.length) {
                        window.location.href = 'https://eggcave.com/usershops/search';
                        return;
                    }
                    const nextName = wishlist[idx];
                    window.location.href = 'https://eggcave.com/usershops/search?q=' + encodeURIComponent(nextName) + '&match=exact';
                };
                randomDelay(1200, 3500).then(go);
            }
            return;
        }
        const wishlist = getWishlist();
        const idx = getIndex();
        const go = function () {
            if (idx >= wishlist.length) {
                window.location.href = 'https://eggcave.com/usershops/search';
                return;
            }
            const nextName = wishlist[idx];
            window.location.href = 'https://eggcave.com/usershops/search?q=' + encodeURIComponent(nextName) + '&match=exact';
        };
        randomDelay(1200, 3500).then(go);
    }

    function getUseBoughtPending() {
        const raw = GM_getValue(USE_BOUGHT_PENDING_KEY, null);
        return Array.isArray(raw) ? raw : [];
    }

    function saveUseBoughtPending(list) {
        GM_setValue(USE_BOUGHT_PENDING_KEY, list);
    }

    function getSendToShopPhase() {
        return GM_getValue(USE_BOUGHT_SEND_TO_SHOP_KEY, false) === true;
    }

    function setSendToShopPhase(v) {
        GM_setValue(USE_BOUGHT_SEND_TO_SHOP_KEY, !!v);
    }

    function runAfterUseBought() {
        if (!GM_getValue(USE_BOUGHT_JUST_USED_KEY, false)) return Promise.resolve();
        GM_setValue(USE_BOUGHT_JUST_USED_KEY, false);
        GM_setValue(USE_BOUGHT_SUBMIT_PATH_KEY, '');
        const usedName = (GM_getValue(USE_BOUGHT_USED_NAME_KEY, '') || '').trim();
        GM_setValue(USE_BOUGHT_USED_NAME_KEY, '');
        let pending = getUseBoughtPending().filter(function (entry) {
            return normalizeName(entry.name) !== normalizeName(usedName);
        });
        saveUseBoughtPending(pending);
        let purchased = getPurchased().filter(function (n) {
            return normalizeName(n) !== normalizeName(usedName);
        });
        savePurchased(purchased);
        if (pending.length > 0) {
            const next = pending[0];
            const nextUrl = next.href.indexOf('http') === 0 ? next.href : (window.location.origin + (next.href.indexOf('/') === 0 ? next.href : '/' + next.href));
            return randomDelay(1500, 4000).then(function () {
                window.location.href = nextUrl;
            });
        }
        setSendToShopPhase(true);
        return randomDelay(1500, 4000).then(function () {
            window.location.href = 'https://eggcave.com/inventory';
        });
    }

    function runOnInventoryPageUseBought() {
        if (!GM_getValue(USE_BOUGHT_KEY, false) && !getSendToShopPhase()) return;

        if (getSendToShopPhase()) {
            setSendToShopPhase(false);
            GM_setValue(USE_BOUGHT_KEY, false);
            const form = document.getElementById('inventoryForm') || document.querySelector('#eggcave form[action*="inventory/move"]');
            if (form) {
                const actionInput = form.querySelector('input[name="action"]');
                const checkboxes = form.querySelectorAll('input.move_items, input[name="move_items[]"]');
                if (actionInput && checkboxes.length) {
                    checkboxes.forEach(function (cb) {
                        if (!cb.checked) {
                            cb.checked = true;
                            cb.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                    actionInput.value = 'usershop';
                    randomDelay(1000, 3500).then(function () {
                        form.submit();
                    });
                    return;
                }
            }
            randomDelay(1000, 2500).then(function () {
                window.location.href = 'https://eggcave.com/usershops/stock';
            });
            return;
        }

        const purchased = getPurchased();
        if (purchased.length === 0) {
            GM_setValue(USE_BOUGHT_KEY, false);
            randomDelay(1000, 2500).then(function () {
                window.location.href = 'https://eggcave.com/usershops/stock';
            });
            return;
        }
        const links = document.querySelectorAll('#eggcave a[href*="/inventory/"][href*="/show"]');
        const list = [];
        links.forEach(function (a) {
            const href = (a.getAttribute('href') || '').trim();
            const name = (a.textContent || '').trim().replace(/\s+/g, ' ');
            if (!href) return;
            const idMatch = href.match(/\/inventory\/(\d+)\/show/);
            if (!idMatch) return;
            const inPurchased = purchased.some(function (p) {
                return normalizeName(p) === normalizeName(name) || name.toLowerCase().indexOf(normalizeName(p)) !== -1 || normalizeName(p).indexOf(normalizeName(name)) !== -1;
            });
            if (inPurchased) list.push({ id: idMatch[1], name: name, href: href });
        });
        if (list.length === 0) {
            GM_setValue(USE_BOUGHT_KEY, false);
            randomDelay(1000, 2500).then(function () {
                window.location.href = 'https://eggcave.com/usershops/stock';
            });
            return;
        }
        saveUseBoughtPending(list);
        const first = list[0];
        const fullHref = first.href.indexOf('http') === 0 ? first.href : (window.location.origin + (first.href.indexOf('/') === 0 ? first.href : '/' + first.href));
        randomDelay(1500, 4000).then(function () {
            window.location.href = fullHref;
        });
    }

    function runOnInventoryShowPageUseBought() {
        if (!GM_getValue(USE_BOUGHT_KEY, false)) return;
        const pathname = window.location.pathname || '';
        const eggcave = document.getElementById('eggcave');
        if (!eggcave) return;
        const idMatch = pathname.match(/\/inventory\/(\d+)\/show/);
        const itemId = idMatch ? idMatch[1] : '';
        const itemNameEl = eggcave.querySelector('h1');
        const itemName = (itemNameEl && itemNameEl.textContent.replace(/^View Item:\s*/i, '').trim()) || '';
        const pending = getUseBoughtPending();
        const isInList = pending.some(function (entry) {
            return normalizeName(entry.name) === normalizeName(itemName) || normalizeName(entry.id) === itemId;
        });

        if (GM_getValue(USE_BOUGHT_SUBMIT_PATH_KEY, '') === pathname) {
            const strongroomForm = findStrongroomForm(eggcave);
            if (strongroomForm) {
                GM_setValue(USE_BOUGHT_JUST_USED_KEY, true);
                GM_setValue(USE_BOUGHT_USED_NAME_KEY, itemName);
                randomDelay(1000, 3500).then(function () {
                    strongroomForm.submit();
                });
                return;
            }
            GM_setValue(USE_BOUGHT_SUBMIT_PATH_KEY, '');
            GM_setValue(USE_BOUGHT_JUST_USED_KEY, false);
            GM_setValue(USE_BOUGHT_USED_NAME_KEY, '');
            const nextPending = pending.filter(function (entry) {
                return normalizeName(entry.name) !== normalizeName(itemName) && String(entry.id) !== String(itemId);
            });
            saveUseBoughtPending(nextPending);
            const nextPurchased = getPurchased().filter(function (n) {
                return normalizeName(n) !== normalizeName(itemName);
            });
            savePurchased(nextPurchased);
            if (nextPending.length > 0) {
                const next = nextPending[0];
                const nextUrl = next.href.indexOf('http') === 0 ? next.href : (window.location.origin + (next.href.indexOf('/') === 0 ? next.href : '/' + next.href));
                randomDelay(1500, 4000).then(function () {
                    window.location.href = nextUrl;
                });
            } else {
                GM_setValue(USE_BOUGHT_KEY, false);
                randomDelay(1500, 4000).then(function () {
                    window.location.href = 'https://eggcave.com/usershops/stock';
                });
            }
            return;
        }
        if (!isInList) return;
        GM_setValue(USE_BOUGHT_SUBMIT_PATH_KEY, pathname);

        if (isItemRetired(eggcave)) {
            const strongholdForm = findStrongholdForm(eggcave);
            if (strongholdForm) {
                GM_setValue(USE_BOUGHT_JUST_USED_KEY, true);
                GM_setValue(USE_BOUGHT_USED_NAME_KEY, itemName);
                randomDelay(1000, 3500).then(function () {
                    strongholdForm.submit();
                });
                return;
            }
            const nextPending = pending.filter(function (entry) {
                return normalizeName(entry.name) !== normalizeName(itemName) && String(entry.id) !== String(itemId);
            });
            saveUseBoughtPending(nextPending);
            const nextPurchased = getPurchased().filter(function (n) {
                return normalizeName(n) !== normalizeName(itemName);
            });
            savePurchased(nextPurchased);
            if (nextPending.length > 0) {
                const next = nextPending[0];
                const nextUrl = next.href.indexOf('http') === 0 ? next.href : (window.location.origin + (next.href.indexOf('/') === 0 ? next.href : '/' + next.href));
                randomDelay(1500, 4000).then(function () {
                    window.location.href = nextUrl;
                });
            } else {
                GM_setValue(USE_BOUGHT_KEY, false);
                randomDelay(1500, 4000).then(function () {
                    window.location.href = 'https://eggcave.com/usershops/stock';
                });
            }
            return;
        }

        const form = eggcave.querySelector('form[action*="/inventory/"][action*="/use"]');
        if (!form) return;
        const select = form.querySelector('select[name="creature"]#creature');
        if (!select || !select.options.length) return;
        GM_setValue(USE_BOUGHT_JUST_USED_KEY, true);
        GM_setValue(USE_BOUGHT_USED_NAME_KEY, itemName);
        const wantName = (USE_ON_CREATURE || '').trim().toLowerCase();
        let optionToUse = null;
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            if (!opt.value) continue;
            const text = (opt.textContent || '').trim();
            if (wantName && text.toLowerCase().indexOf(wantName) !== -1) {
                optionToUse = opt;
                break;
            }
            if (!optionToUse) optionToUse = opt;
        }
        if (!optionToUse) optionToUse = select.options[0];
        if (!optionToUse || !optionToUse.value) return;
        select.value = optionToUse.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        randomDelay(1000, 3500).then(function () {
            form.submit();
        });
    }

    function run() {
        if (/[?&]wishlist=reset(?=&|$)/i.test(window.location.search)) {
            GM_setValue(WISHLIST_KEY, null);
            setIndex(0);
            setStopped(false);
            if (window.history && window.history.replaceState) {
                const q = window.location.search.replace(/\?wishlist=reset&?/i, '?').replace(/&wishlist=reset(?=&|$)/i, '').replace(/\?$/, '');
                window.history.replaceState(null, '', window.location.pathname + q + window.location.hash);
            }
        }
        if (isInventoryPage() || isInventoryShowPage() || isInventoryUsedPage()) {
            const useBought = GM_getValue(USE_BOUGHT_KEY, false);
            const sendToShop = getSendToShopPhase();
            if (useBought || sendToShop) {
                const wasJustUsed = GM_getValue(USE_BOUGHT_JUST_USED_KEY, false);
                const p = runAfterUseBought();
                const runPage = function () {
                    if (!wasJustUsed) {
                        if (isInventoryShowPage()) runOnInventoryShowPageUseBought();
                        else if (isInventoryPage()) runOnInventoryPageUseBought();
                    }
                };
                if (p && typeof p.then === 'function') {
                    p.then(runPage);
                } else {
                    runPage();
                }
                return;
            }
        }
        if (isSearchFormPage()) {
            runOnSearchForm();
            return;
        }
        if (isSearchResultsPage()) {
            runOnSearchResults();
            return;
        }
        if (isShopPage()) {
            if (getPendingBuy()) runOnShopPage();
            else checkJustBoughtRedirect();
            return;
        }
    }

    run();
})();
