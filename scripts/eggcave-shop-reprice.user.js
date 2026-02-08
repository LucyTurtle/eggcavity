// ==UserScript==
// @name         EggCave Shop Reprice
// @namespace    https://eggcave.com/
// @version      2.2
// @description  On Manage Stock: search each item on user shop search, price 5% under best user price (ignoring your own shop); else use DEFAULT_PRICE_LIST (no cross-origin).
// @author       You
// @match        https://eggcave.com/usershops/stock
// @run-at       document-idle
// ==/UserScript==

(function () {
    'use strict';

    const EGGCAVE_ORIGIN = 'https://eggcave.com';

    // Username of your own shop: prices from this shop are ignored when finding the best user price.
    const IGNORE_SHOP_USERNAME = 'lbowe_elbow';

    // Fallback when user-shop search returns no price. Edit with { name: 'Item Name', price: 494 }.
    const DEFAULT_PRICE_LIST = [
        // { name: 'Item Name', price: 494 },
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

    function parseEC(str) {
        if (!str || typeof str !== 'string') return -1;
        const m = str.replace(/,/g, '').match(/(\d+)\s*EC/i);
        return m ? parseInt(m[1], 10) : -1;
    }

    // 5% under base price, at least 10 EC discount, minimum 1 EC
    function computeOurPrice(priceVal) {
        if (priceVal <= 0) return 1;
        return Math.max(1, priceVal - Math.max(Math.floor(priceVal * 0.05), 10));
    }

    function getStockItemNameFromRow(row) {
        const firstTd = row.querySelector('td');
        if (!firstTd) return '';
        const text = (firstTd.textContent || '').replace(/\s*\(r\d+\)\s*$/i, '').trim().replace(/\s+/g, ' ');
        return text;
    }

    // Parse HTML: split into result blocks (column divs), skip blocks that contain IGNORE_SHOP_USERNAME, return minimum EC price from the rest, or -1
    function parseLowestECFromHtml(html) {
        if (!html || typeof html !== 'string') return -1;
        const ignore = (IGNORE_SHOP_USERNAME || '').trim().toLowerCase();
        const blocks = html.split(/<div\s+class="column\s/is);
        let min = -1;
        blocks.forEach(function (block) {
            if (ignore && (block.indexOf('@' + ignore) !== -1 || block.toLowerCase().indexOf(ignore) !== -1)) return;
            const text = block.replace(/,/g, '');
            const matches = text.match(/\d+\s*EC/gi);
            if (!matches) return;
            matches.forEach(function (s) {
                const n = parseInt(s.replace(/\s*EC/i, '').trim(), 10);
                if (!isNaN(n) && n > 0 && (min < 0 || n < min)) min = n;
            });
        });
        return min;
    }

    function getDefaultPriceMap() {
        const map = {};
        (DEFAULT_PRICE_LIST || []).forEach(function (entry) {
            if (!entry || !entry.name) return;
            const name = entry.name;
            let price = -1;
            if (typeof entry.price === 'number' && entry.price > 0) price = entry.price;
            else if (typeof entry.price === 'string') price = parseEC(entry.price);
            if (price > 0) map[normalizeName(name)] = price;
        });
        return map;
    }

    function findFallbackPrice(itemName, fallbackMap) {
        const want = normalizeName(itemName).replace(/\.+$/, '');
        if (fallbackMap && fallbackMap[want] > 0) return fallbackMap[want];
        let bestVal = -1;
        Object.keys(fallbackMap || {}).forEach(function (key) {
            if (key.indexOf(want) !== -1 || want.indexOf(key) !== -1) {
                const val = fallbackMap[key];
                if (val > 0 && (bestVal < 0 || val < bestVal)) bestVal = val;
            }
        });
        return bestVal > 0 ? bestVal : -1;
    }

    function fetchUserShopSearch(itemName) {
        const trimmed = (itemName || '').trim();
        const useExact = !trimmed.endsWith('...');
        const match = useExact ? 'exact' : 'partial';
        const q = trimmed.replace(/\.+$/, '').trim();
        if (q.length < 3) return Promise.resolve(-1);
        const url = EGGCAVE_ORIGIN + '/usershops/search?q=' + encodeURIComponent(q) + '&match=' + match;
        return fetch(url).then(function (res) {
            return res.text();
        }).then(function (html) {
            const low = parseLowestECFromHtml(html);
            return low > 0 ? low : -1;
        }).catch(function () {
            return -1;
        });
    }

    function run() {
        const eggcave = document.getElementById('eggcave');
        if (!eggcave) return;

        const updateForm = eggcave.querySelector('form[action*="update_price"]');
        const dataRows = [];
        eggcave.querySelectorAll('tbody tr').forEach(function (tr) {
            const priceInput = tr.querySelector('input[name^="prices["]');
            if (!priceInput) return;
            const itemName = getStockItemNameFromRow(tr);
            if (!itemName) return;
            dataRows.push({ itemName: itemName, priceInput: priceInput });
        });

        if (dataRows.length === 0 || !updateForm) return;

        const fallbackMap = getDefaultPriceMap();

        function processAll() {
            var index = 0;

            function next() {
                if (index >= dataRows.length) {
                    randomDelay(800, 2500).then(function () {
                        updateForm.submit();
                    });
                    return;
                }
                const entry = dataRows[index];
                index += 1;
                fetchUserShopSearch(entry.itemName).then(function (userPrice) {
                    let basePrice = userPrice;
                    if (basePrice <= 0) {
                        basePrice = findFallbackPrice(entry.itemName, fallbackMap);
                    }
                    if (basePrice <= 0) basePrice = 10000;
                    const ourPrice = computeOurPrice(basePrice);
                    entry.priceInput.value = String(ourPrice);
                    entry.priceInput.dispatchEvent(new Event('input', { bubbles: true }));
                    randomDelay(600, 1800).then(next);
                });
            }

            next();
        }

        processAll();
    }

    run();
})();
