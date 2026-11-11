import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { initializeSite } from '../assets/js/script.js';

function setup(t, desktop = false) {
    const dom = new JSDOM(`<!doctype html><html data-site-locale="en"><body>
        <header id="siteHeader"><button id="mobileMenuBtn">Menu</button>
            <div id="navPanel" class="slsc-nav-panel">
                <a class="nav-link" href="/#">Home</a>
                <details data-slsc-nav-group><summary class="nav-link">Appointments</summary><ul class="slsc-nav-submenu"><li><a href="/#">Colonel Commandant</a></li></ul></details>
                <details data-slsc-nav-group><summary class="nav-link">History</summary><ul class="slsc-nav-submenu"><li><a href="/#">History of Signal Corps</a></li></ul></details>
            </div>
        </header><button id="outside">Outside</button>
    </body></html>`, { url: 'https://example.test/', pretendToBeVisual: true });
    const w = dom.window;
    const d = w.document;
    w.matchMedia = query => ({ matches: desktop && query.includes('min-width') });
    const app = initializeSite(d, w);
    const groups = [...d.querySelectorAll('[data-slsc-nav-group]')];
    const key = (node, value) => node.dispatchEvent(new w.KeyboardEvent('keydown', { key: value, bubbles: true, cancelable: true }));
    t.after(() => { app.destroy(); w.close(); });
    return { w, d, groups, key };
}

test('mobile dropdown summaries open without collapsing the navigation panel', t => {
    const s = setup(t);
    s.d.querySelector('#mobileMenuBtn').click();
    s.groups[0].querySelector('summary').click();
    assert.equal(s.groups[0].open, true);
    assert.equal(s.d.querySelector('#navPanel').classList.contains('is-open'), true);
    s.groups[0].querySelector('a').addEventListener('click', event => event.preventDefault());
    s.groups[0].querySelector('a').click();
    assert.equal(s.d.querySelector('#navPanel').classList.contains('is-open'), false);
    assert.equal(s.groups[0].open, false);
});

test('Escape closes a dropdown and restores its summary focus before closing the mobile panel', t => {
    const s = setup(t);
    s.d.querySelector('#mobileMenuBtn').click();
    s.groups[0].open = true;
    const link = s.groups[0].querySelector('a');
    link.focus(); s.key(link, 'Escape');
    assert.equal(s.groups[0].open, false);
    assert.equal(s.d.activeElement, s.groups[0].querySelector('summary'));
    assert.equal(s.d.querySelector('#navPanel').classList.contains('is-open'), true);
    s.key(s.d.activeElement, 'Escape');
    assert.equal(s.d.querySelector('#navPanel').classList.contains('is-open'), false);
});

test('desktop dropdowns support hover, outside click and keyboard focus leaving the group', t => {
    const s = setup(t, true);
    const first = s.groups[0];
    first.dispatchEvent(new s.w.Event('pointerenter'));
    assert.equal(first.open, true);
    first.dispatchEvent(new s.w.Event('pointerleave'));
    assert.equal(first.open, false);
    first.open = true;
    s.d.querySelector('#outside').click();
    assert.equal(first.open, false);
    first.open = true;
    s.d.querySelector('#outside').focus();
    assert.equal(first.open, false);
});

test('opening a different dropdown closes the earlier group', async t => {
    const s = setup(t);
    s.groups[0].querySelector('summary').click();
    await new Promise(resolve => setTimeout(resolve, 5));
    s.groups[1].querySelector('summary').click();
    await new Promise(resolve => setTimeout(resolve, 5));
    assert.equal(s.groups[0].open, false);
    assert.equal(s.groups[1].open, true);
});
