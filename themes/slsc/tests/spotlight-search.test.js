import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { initializeSearch } from '../assets/js/search.js';

function setup(t) {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const dom = new JSDOM(`<!doctype html><body>
        <button data-open-search-modal>Search</button><input id="outside">
        <div data-search-modal data-suggest-url="/search/suggest" hidden>
            <button data-close-search-modal>Close</button>
            <form data-modal-search-form><input data-modal-search-input></form>
            <div data-suggested-topics><button class="slsc-suggested-pill" data-topic="Signal">Signal</button></div>
            <div data-modal-search-results hidden></div>
        </div></body>`, { url: 'https://example.test/' });
    const { window: w } = dom;
    const d = w.document;
    w.HTMLElement.prototype.scrollIntoView = function () {};
    initializeSearch(d, w);
    const input = d.querySelector('[data-modal-search-input]');
    const modal = d.querySelector('[data-search-modal]');
    const opener = d.querySelector('[data-open-search-modal]');
    const key = (target, value, options = {}) => target.dispatchEvent(new w.KeyboardEvent('keydown', { key: value, bubbles: true, cancelable: true, ...options }));
    const type = value => { input.value = value; input.dispatchEvent(new w.Event('input', { bubbles: true })); };
    const open = () => { opener.focus(); opener.click(); t.mock.timers.tick(60); };
    t.after(() => dom.window.close());
    return { w, d, input, modal, opener, key, type, open };
}

test('Spotlight opens, focuses the input and restores focus through every keyboard shortcut', t => {
    const s = setup(t);
    for (const [key, options] of [['k', { ctrlKey: true }], ['k', { metaKey: true }], ['/', {}]]) {
        s.opener.focus();
        s.key(s.d, key, options);
        t.mock.timers.tick(60);
        assert.equal(s.modal.hidden, false);
        assert.equal(s.d.activeElement, s.input);
        s.key(s.input, 'Escape');
        assert.equal(s.modal.hidden, true);
        assert.equal(s.d.activeElement, s.opener);
    }
    s.d.querySelector('#outside').focus();
    s.key(s.d, '/');
    assert.equal(s.modal.hidden, true);
});

test('Spotlight traps Tab and Shift+Tab inside the open modal', t => {
    const s = setup(t);
    s.open();
    const first = s.modal.querySelector('[data-close-search-modal]');
    const last = s.modal.querySelector('.slsc-suggested-pill');
    last.focus();
    assert.equal(s.key(last, 'Tab'), false);
    assert.equal(s.d.activeElement, first);
    s.key(first, 'Tab', { shiftKey: true });
    assert.equal(s.d.activeElement, last);
});

test('Spotlight cancels pending searches when cleared or closed and cancels delayed focus', t => {
    const s = setup(t);
    const fetch = t.mock.method(globalThis, 'fetch', async () => ({ ok: true, json: async () => ({ results: [] }) }));
    s.open(); s.type('Signal'); s.type(''); t.mock.timers.tick(200);
    assert.equal(fetch.mock.callCount(), 0);
    s.type('Signal'); s.key(s.input, 'Escape'); t.mock.timers.tick(200);
    assert.equal(fetch.mock.callCount(), 0);
    s.opener.click(); s.key(s.d, 'Escape'); t.mock.timers.tick(60);
    assert.equal(s.d.activeElement, s.opener);
});

test('Spotlight debounces requests, escapes results and navigates them with arrows and Enter', async t => {
    const s = setup(t);
    const fetch = t.mock.method(globalThis, 'fetch', async () => ({ ok: true, json: async () => ({ total: 2, results: [
        { title: '<img src=x onerror=alert(1)> Signal', excerpt: '<script>bad()</script>', type: 'news', type_label: 'News', url: '/news/one' },
        { title: 'Signal Two', type: 'news', type_label: 'News', url: '/news/two' },
    ] }) }));
    s.open(); s.type('Sig'); s.type('Signal'); t.mock.timers.tick(200);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(fetch.mock.callCount(), 1);
    const items = [...s.modal.querySelectorAll('.search-result-item')];
    assert.equal(items.length, 2);
    assert.equal(s.modal.querySelector('img, script'), null);
    s.key(s.input, 'ArrowUp');
    assert.equal(items[1].classList.contains('is-selected'), true);
    assert.equal(items[1].getAttribute('aria-selected'), 'true');
    s.key(s.input, 'ArrowDown');
    assert.equal(items[0].classList.contains('is-selected'), true);
    let clicked = false;
    items[0].addEventListener('click', event => { event.preventDefault(); clicked = true; });
    s.key(s.input, 'Enter');
    assert.equal(clicked, true);
});

test('Spotlight closes with the close button and backdrop and restores opener focus', t => {
    const s = setup(t);
    for (const target of [s.modal.querySelector('[data-close-search-modal]'), s.modal]) {
        s.open(); target.click();
        assert.equal(s.modal.hidden, true);
        assert.equal(s.d.activeElement, s.opener);
    }
});

test('Spotlight shows loading and empty states for a suggested topic', async t => {
    const s = setup(t);
    let finish;
    const fetch = t.mock.method(globalThis, 'fetch', () => new Promise(resolve => { finish = resolve; }));
    s.open(); s.modal.querySelector('.slsc-suggested-pill').click();
    assert.equal(s.input.value, 'Signal');
    assert.match(s.modal.textContent, /Searching/);
    finish({ ok: true, json: async () => ({ results: [] }) });
    await new Promise(resolve => setImmediate(resolve));
    assert.match(s.modal.textContent, /No results found/);
    assert.equal(fetch.mock.callCount(), 1);
});

test('Spotlight aborts earlier requests and aborts the current request on close', t => {
    const s = setup(t);
    const signals = [];
    t.mock.method(globalThis, 'fetch', (url, options) => { signals.push(options.signal); return new Promise(() => {}); });
    s.open(); s.type('Signal'); t.mock.timers.tick(200);
    s.type('News'); t.mock.timers.tick(200);
    assert.equal(signals[0].aborted, true);
    assert.equal(signals[1].aborted, false);
    s.key(s.input, 'Escape');
    assert.equal(signals[1].aborted, true);
});

test('Spotlight recovers from a failed suggestion request with the full-search hint', async t => {
    const s = setup(t);
    t.mock.method(globalThis, 'fetch', async () => ({ ok: false }));
    s.open(); s.type('Signal'); t.mock.timers.tick(200);
    await new Promise(resolve => setImmediate(resolve));
    assert.match(s.modal.textContent, /Press Enter to view full search results/);
    assert.doesNotMatch(s.modal.textContent, /Searching/);
});
