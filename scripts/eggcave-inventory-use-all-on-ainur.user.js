// ==UserScript==
// @name         EggCave Inventory - Use All on Ainur
// @namespace    https://eggcave.com/
// @version      1.0
// @description  Use every item in your inventory on Ainur once. Builds a list at start so multi-use items are not used repeatedly. Updates the shopping script's purchased list.
// @author       You
// @match        https://eggcave.com/inventory
// @match        https://eggcave.com/inventory/*
// @grant        GM_setValue
// @grant        GM_getValue
// @run-at       document-idle
// ==/UserScript==

(function () {
    'use strict';

    // Same key as shopping script so we can remove used items from its "purchased" list
    const PURCHASED_KEY = 'eggcave_shop_purchased';
    const USEALL_PENDING_KEY = 'eggcave_useall_pending';
    const USEALL_JUST_USED_KEY = 'eggcave_useall_just_used';
    const USEALL_USED_ID_KEY = 'eggcave_useall_used_id';
    const USEALL_USED_NAME_KEY = 'eggcave_useall_used_name';

    const CREATURE_NAME = 'Ainur';

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

    function getPending() {
        const raw = GM_getValue(USEALL_PENDING_KEY, null);
        return Array.isArray(raw) ? raw : [];
    }

    function savePending(list) {
        GM_setValue(USEALL_PENDING_KEY, list);
    }

    function getPurchased() {
        const raw = GM_getValue(PURCHASED_KEY, null);
        return Array.isArray(raw) ? raw : [];
    }

    function savePurchased(list) {
        GM_setValue(PURCHASED_KEY, list);
    }

    function isInventoryPage() {
        return window.location.pathname === '/inventory';
    }

    function isInventoryShowPage() {
        return /^\/inventory\/\d+\/show$/.test(window.location.pathname);
    }

    // ----- After using an item: remove from pending, update shopping script list, go to next or done -----
    function runAfterUse() {
        if (!GM_getValue(USEALL_JUST_USED_KEY, false)) return Promise.resolve();
        const usedId = (GM_getValue(USEALL_USED_ID_KEY, '') || '').toString();
        const usedName = (GM_getValue(USEALL_USED_NAME_KEY, '') || '').trim();
        GM_setValue(USEALL_JUST_USED_KEY, false);
        GM_setValue(USEALL_USED_ID_KEY, '');
        GM_setValue(USEALL_USED_NAME_KEY, '');

        var pending = getPending().filter(function (entry) {
            return (entry.id || '').toString() !== usedId;
        });
        savePending(pending);

        var purchased = getPurchased().filter(function (n) {
            return normalizeName(n) !== normalizeName(usedName);
        });
        savePurchased(purchased);

        if (pending.length > 0) {
            var next = pending[0];
            var nextUrl = (next.href && next.href.indexOf('http') === 0)
                ? next.href
                : (window.location.origin + (next.href && next.href.indexOf('/') === 0 ? next.href : '/' + (next.href || '')));
            return randomDelay(1500, 4000).then(function () {
                window.location.href = nextUrl;
            });
        }
        return Promise.resolve();
    }

    // ----- Inventory page: build full list of all items (by id) and go to first -----
    function runOnInventoryPage() {
        var links = document.querySelectorAll('#eggcave a[href*="/inventory/"][href*="/show"]');
        var list = [];
        links.forEach(function (a) {
            var href = (a.getAttribute('href') || '').trim();
            var name = (a.textContent || '').trim().replace(/\s+/g, ' ');
            if (!href) return;
            var idMatch = href.match(/\/inventory\/(\d+)\/show/);
            if (!idMatch) return;
            list.push({ id: idMatch[1], name: name, href: href });
        });
        if (list.length === 0) return Promise.resolve();
        savePending(list);
        var first = list[0];
        var fullHref = first.href.indexOf('http') === 0 ? first.href : (window.location.origin + (first.href.indexOf('/') === 0 ? first.href : '/' + first.href));
        return randomDelay(1500, 4000).then(function () {
            window.location.href = fullHref;
        });
    }

    // ----- Item show page: select Ainur and submit -----
    function runOnItemShowPage() {
        var eggcave = document.getElementById('eggcave');
        if (!eggcave) return Promise.resolve();
        var form = eggcave.querySelector('form[action*="/inventory/"][action*="/use"]');
        if (!form) return Promise.resolve();
        var select = form.querySelector('select[name="creature"]#creature');
        if (!select || !select.options.length) return Promise.resolve();

        var idMatch = window.location.pathname.match(/\/inventory\/(\d+)\/show/);
        var itemId = idMatch ? idMatch[1] : '';
        var itemNameEl = eggcave.querySelector('h1');
        var itemName = (itemNameEl && itemNameEl.textContent.replace(/^View Item:\s*/i, '').trim()) || '';

        var wantName = (CREATURE_NAME || '').trim().toLowerCase();
        var optionToUse = null;
        for (var i = 0; i < select.options.length; i++) {
            var opt = select.options[i];
            if (!opt.value) continue;
            var text = (opt.textContent || '').trim();
            if (wantName && text.toLowerCase().indexOf(wantName) !== -1) {
                optionToUse = opt;
                break;
            }
            if (!optionToUse) optionToUse = opt;
        }
        if (!optionToUse) optionToUse = select.options[1];
        if (!optionToUse || !optionToUse.value) return Promise.resolve();

        select.value = optionToUse.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));

        GM_setValue(USEALL_JUST_USED_KEY, true);
        GM_setValue(USEALL_USED_ID_KEY, itemId);
        GM_setValue(USEALL_USED_NAME_KEY, itemName);

        return randomDelay(1000, 3500).then(function () {
            form.submit();
        });
    }

    // ----- Main -----
    var wasJustUsed = GM_getValue(USEALL_JUST_USED_KEY, false);
    var p = runAfterUse();
    if (p && p.then) p = p.catch(function () {});
    else p = Promise.resolve();
    if (!wasJustUsed) {
        if (isInventoryShowPage()) {
            p = p.then(function () { return runOnItemShowPage(); });
        } else if (isInventoryPage()) {
            p = p.then(function () { return runOnInventoryPage(); });
        }
    }
    p.catch(function (err) {
        console.warn('[EggCave Use-All]', err);
    });
})();
