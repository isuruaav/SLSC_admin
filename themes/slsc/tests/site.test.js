import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { execFileSync } from 'node:child_process';
import { JSDOM } from 'jsdom';
import { initializeSite } from '../assets/js/script.js';
import { translations } from '../assets/js/i18n.js';

const html = execFileSync('php', ['-n', 'index.php'], { encoding: 'utf8' });
const standaloneHtml = execFileSync('php', ['-n', 'register.php'], { encoding: 'utf8' });

function setup(options = {}) {
    const dom = new JSDOM(options.standalone ? standaloneHtml : html, { url: 'https://example.test/slsc/', pretendToBeVisual: true });
    const w = dom.window;
    const d = w.document;
    w.matchMedia = query => Object.assign(new w.EventTarget(), { media: query, matches: query.includes('reduced-motion') ? Boolean(options.reducedMotion) : false });
    if (options.blockStorage) Object.defineProperty(w, 'localStorage', { get() { throw new Error('Storage blocked'); } });
    const frames = new Map(); let frameId = 0;
    w.requestAnimationFrame = callback => { frames.set(++frameId, callback); return frameId; };
    w.cancelAnimationFrame = id => frames.delete(id);
    d.querySelectorAll('dialog').forEach(dialog => {
        dialog.showModal = function () { this.setAttribute('open', ''); };
        dialog.close = function () { this.removeAttribute('open'); this.dispatchEvent(new w.Event('close')); };
    });
    const app = initializeSite(d, w);
    const $ = selector => d.querySelector(selector);
    const visible = selector => [...d.querySelectorAll(selector)].filter(node => !node.hidden);
    const input = (selector, value, event = 'input') => { $(selector).value = value; $(selector).dispatchEvent(new w.Event(event, { bubbles: true })); };
    const key = (value, extra = {}, target = d) => target.dispatchEvent(new w.KeyboardEvent('keydown', { key: value, bubbles: true, cancelable: true, ...extra }));
    return { w, d, $, visible, input, key, advanceFrame(time) { const callbacks = [...frames.values()]; frames.clear(); callbacks.forEach(callback => callback(time)); }, close() { app.destroy(); w.close(); } };
}

function fillRegistration(s) {
    for (const [name, value] of Object.entries({ full_name: 'Test Applicant', email: 'applicant@example.test', phone: '077 123 4567', district: 'Colombo', interest: 'technology', message: 'I am interested in technology.' })) {
        s.input(`#reg-${name}`, value, name === 'interest' ? 'change' : 'input');
    }
    s.$('#reg-consent').checked = true;
    s.$('#reg-consent').dispatchEvent(new s.w.Event('change', { bubbles: true }));
}
function submitRegistration(s) { s.$('#registrationForm').dispatchEvent(new s.w.Event('submit', { bubbles: true, cancelable: true })); }
const flush = () => new Promise(resolve => setImmediate(resolve));

test('rendered PHP keeps all program links and only valid local anchors', () => {
    const dom = new JSDOM(html);
    const d = dom.window.document;
    assert.equal(d.querySelectorAll('.program-card[href^="https://"]').length, 15);
    assert.equal(d.querySelectorAll('.news-card').length, 4);
    assert.equal(d.querySelectorAll('#organization, #gallery, .quick-access').length, 0);
    const ids = [...d.querySelectorAll('[id]')].map(node => node.id);
    assert.equal(new Set(ids).size, ids.length, 'IDs must be unique');
    for (const anchor of d.querySelectorAll('a[href^="#"], use[href^="#"]')) {
        assert.ok(d.getElementById(anchor.getAttribute('href').slice(1)), `Missing anchor ${anchor.getAttribute('href')}`);
    }
    for (const image of d.querySelectorAll('img[src]')) assert.ok(fs.existsSync(image.getAttribute('src')), image.getAttribute('src'));
    for (const resource of d.querySelectorAll('script[src], link[rel="stylesheet"]')) assert.ok(!resource.getAttribute('src')?.startsWith('https:'), 'Runtime scripts must be local');
    dom.window.close();
});

test('every visible label and accessible label has both translations', () => {
    const dom = new JSDOM(html);
    for (const node of dom.window.document.querySelectorAll('[data-i18n], [data-i18n-aria], [data-i18n-placeholder]')) {
        for (const key of [node.dataset.i18n, node.dataset.i18nAria, node.dataset.i18nPlaceholder].filter(Boolean)) {
            assert.ok(translations.en[key], `Missing English label: ${key}`);
            assert.ok(translations.si[key], `Missing Sinhala label: ${key}`);
        }
    }
    dom.window.close();
});

test('language switching updates content and keeps English program searches working', () => {
    const s = setup();
    try {
        s.$('[data-lang="si"]').click();
        assert.equal(s.d.documentElement.lang, 'si');
        assert.equal(s.$('[data-lang="si"]').getAttribute('aria-pressed'), 'true');
        assert.equal(s.$('#programsTitle').textContent, translations.si.programsTitle);
        assert.equal(s.w.localStorage.getItem('slsc-language'), 'si');
        s.input('#programSearch', 'rugby');
        assert.equal(s.visible('.program-item').length, 1);
        assert.ok(s.visible('.program-item')[0].textContent.includes(translations.si.programRugby));
        s.$('[data-lang="en"]').click();
        assert.equal(s.visible('.program-item').length, 1);
        assert.equal(s.$('#programCount').textContent, '1 program');
    } finally { s.close(); }
});

test('program search combines category and text, and reset restores all cards', () => {
    const s = setup();
    try {
        s.input('#programCategory', 'community', 'change');
        assert.equal(s.visible('.program-item').length, 4);
        s.input('#programSearch', 'seva');
        assert.equal(s.visible('.program-item').length, 1);
        s.input('#programSearch', 'no-such-program');
        assert.equal(s.visible('.program-item').length, 0);
        assert.equal(s.$('#noPrograms').hidden, false);
        s.$('[data-reset="programs"]').click();
        assert.equal(s.visible('.program-item').length, 15);
        assert.equal(s.$('#programCategory').value, 'all');
        assert.equal(s.d.activeElement, s.$('#programSearch'));
    } finally { s.close(); }
});

test('news filters, empty state and reset use the same visible result count', () => {
    const s = setup();
    try {
        s.$('[data-news-filter="welfare"]').click();
        assert.equal(s.visible('.news-card').length, 1);
        assert.equal(s.$('#newsCount').textContent, '1 story');
        s.input('#newsSearch', 'not-a-story');
        assert.equal(s.$('#noNews').hidden, false);
        assert.equal(s.$('#newsCount').textContent, '0 stories');
        s.$('[data-reset="news"]').click();
        assert.equal(s.visible('.news-card').length, 4);
        assert.equal(s.$('#noNews').hidden, true);
        assert.equal(s.$('#newsGrid').classList.contains('is-filtered'), false);
    } finally { s.close(); }
});

test('site search has real links, scoped results and no HTML injection', () => {
    const s = setup();
    try {
        s.$('[data-open-search]').click();
        assert.equal(s.$('#siteSearchDialog').open, true);
        assert.equal(s.d.activeElement, s.$('#siteSearchInput'));
        s.input('#siteSearchInput', 'research development');
        assert.equal(s.d.querySelectorAll('#siteSearchResults a').length, 1);
        assert.match(s.$('#siteSearchResults a').href, /r-and-d-projects$/);
        s.input('#siteSearchInput', '<img src=x onerror=alert(1)>');
        assert.equal(s.d.querySelectorAll('#siteSearchResults img').length, 0);
        assert.equal(s.$('#siteSearchEmpty').hidden, false);
        s.input('#siteSearchInput', '');
        s.$('[data-search-scope="programs"]').click();
        assert.equal(s.d.querySelectorAll('#siteSearchResults a').length, 15);
        s.$('[data-search-scope="news"]').click();
        assert.equal(s.d.querySelectorAll('#siteSearchResults a').length, 4);
        s.$('[data-search-scope="pages"]').click();
        assert.equal(s.d.querySelectorAll('#siteSearchResults a').length, 7);
    } finally { s.close(); }
});

test('search shortcut, keyboard result selection, Escape and focus restoration', () => {
    const s = setup();
    try {
        const opener = s.$('[data-open-search]'); opener.focus();
        s.key('k', { ctrlKey: true });
        assert.equal(s.$('#siteSearchDialog').open, true);
        s.key('ArrowDown', {}, s.$('#siteSearchInput'));
        assert.equal(s.d.activeElement, s.$('#siteSearchResults a'));
        s.key('Escape', {}, s.d.activeElement);
        assert.equal(s.$('#siteSearchDialog').open, false);
        assert.equal(s.d.activeElement, opener);
        assert.equal(s.d.body.classList.contains('modal-open'), false);
    } finally { s.close(); }
});

test('site search finds the commandant, location and journal by their content', () => {
    const s = setup();
    try {
        s.$('[data-open-search]').click();
        for (const [query, target] of [['Bandaranayake', '#about'], ['Panagoda', '#contact'], ['Signaller', '#publications'], ['කර්නල් සේනාවිධායක', '#about']]) {
            s.input('#siteSearchInput', query);
            assert.ok([...s.d.querySelectorAll('#siteSearchResults a')].some(link => link.getAttribute('href') === target), query);
        }
    } finally { s.close(); }
});

test('compact program banners preserve their height and keyboard hover treatment', () => {
    const dom = new JSDOM('<!DOCTYPE html><html><head></head><body></body></html>');
    const style = dom.window.document.createElement('style');
    style.textContent = fs.readFileSync('assets/css/base.css', 'utf8') + fs.readFileSync('assets/css/responsive.css', 'utf8');
    dom.window.document.head.append(style);
    const rules = [...style.sheet.cssRules];
    const rule = rules.find(rule => rule.selectorText === '.program-card.program-card-compact');
    assert.equal(rule.style.getPropertyValue('min-height'), '82px');
    assert.equal(rule.style.getPropertyValue('max-height'), '82px');
    assert.ok(rules.find(rule => rule.selectorText?.includes('.program-card-compact:focus-visible')));
    assert.equal(rules.find(rule => rule.selectorText === '.program-card-compact:hover .program-banner-img').style.transform, 'scale(1.05)');
    dom.window.close();
});

test('mobile navigation supports Escape and outside clicks', () => {
    const s = setup();
    try {
        s.$('#mobileMenuBtn').click();
        assert.equal(s.$('#mobileMenuBtn').getAttribute('aria-expanded'), 'true');
        s.key('Escape');
        assert.equal(s.$('#navPanel').classList.contains('is-open'), false);
        assert.equal(s.d.activeElement, s.$('#mobileMenuBtn'));
        s.$('#mobileMenuBtn').click();
        s.$('#programsTitle').click();
        assert.equal(s.$('#navPanel').classList.contains('is-open'), false);
    } finally { s.close(); }
});

test('slider wraps in both directions and keeps inactive slides inaccessible', () => {
    const s = setup();
    try {
        s.$('#prevSlide').click();
        assert.equal(s.$('#activeSlideNumber').textContent, '03');
        s.$('#nextSlide').click();
        assert.equal(s.$('#activeSlideNumber').textContent, '01');
        s.key('ArrowRight', {}, s.$('#heroSlider'));
        assert.equal(s.$('#activeSlideNumber').textContent, '02');
        const slides = [...s.d.querySelectorAll('.hero-slide')];
        assert.equal(slides[0].inert, true);
        assert.equal(slides[1].getAttribute('aria-hidden'), 'false');
        assert.equal(slides[1].inert, false);
    } finally { s.close(); }
});

test('slider timer advances only while playback is enabled', () => {
    const s = setup();
    try {
        s.advanceFrame(0); s.advanceFrame(6600);
        assert.equal(s.$('#activeSlideNumber').textContent, '02');
        s.$('#toggleSlider').click();
        s.advanceFrame(15000);
        assert.equal(s.$('#activeSlideNumber').textContent, '02');
        assert.equal(s.$('#toggleSlider').getAttribute('aria-pressed'), 'true');
        s.$('#toggleSlider').click();
        s.advanceFrame(16000); s.advanceFrame(22600);
        assert.equal(s.$('#activeSlideNumber').textContent, '03');
    } finally { s.close(); }
});

test('reduced motion and unavailable storage do not break the page', () => {
    const s = setup({ reducedMotion: true, blockStorage: true });
    try {
        s.advanceFrame(0); s.advanceFrame(10000);
        assert.equal(s.$('#activeSlideNumber').textContent, '01');
        assert.equal(s.$('#toggleSlider').getAttribute('aria-pressed'), 'true');
        s.$('#nextSlide').click();
        assert.equal(s.$('#activeSlideNumber').textContent, '02');
        s.$('[data-lang="si"]').click();
        assert.equal(s.d.documentElement.lang, 'si');
    } finally { s.close(); }
});

test('Join Us opens a modal, preserves a draft and restores focus on Escape', () => {
    const s = setup();
    try {
        const opener = s.$('[data-open-registration]');
        assert.equal(opener.getAttribute('href'), 'register.php');
        opener.click();
        assert.equal(s.$('#registrationDialog').open, true);
        assert.equal(s.d.activeElement, s.$('#reg-full_name'));
        s.input('#reg-full_name', 'Draft Applicant');
        s.key('k', { ctrlKey: true }, s.$('#reg-full_name'));
        assert.equal(s.$('#siteSearchDialog').open, false, 'Dialogs must not stack');
        s.key('Escape', {}, s.$('#reg-full_name'));
        assert.equal(s.$('#registrationDialog').open, false);
        assert.equal(s.d.activeElement, opener);
        assert.equal(s.d.body.classList.contains('modal-open'), false);
        opener.click();
        assert.equal(s.$('#reg-full_name').value, 'Draft Applicant');
    } finally { s.close(); }
});

test('empty registration displays accessible field errors without sending a request', () => {
    const s = setup();
    try {
        let calls = 0; s.w.fetch = async () => { calls++; };
        s.$('[data-open-registration]').click();
        submitRegistration(s);
        assert.equal(calls, 0);
        assert.equal(s.$('#reg-full_name').getAttribute('aria-invalid'), 'true');
        assert.equal(s.$('[data-error-for="consent"]').hidden, false);
        assert.equal(s.$('#registrationAlert').hidden, false);
        assert.equal(s.d.activeElement, s.$('#reg-full_name'));
    } finally { s.close(); }
});

test('registration sends CSRF, prevents duplicate clicks and waits for a saved reference', async () => {
    const s = setup();
    try {
        let calls = 0; let resolveRequest;
        s.w.fetch = (url, options) => {
            calls++;
            assert.equal(url, 'https://example.test/slsc/api/register.php');
            assert.equal(options.credentials, 'same-origin');
            assert.match(options.body.get('_token'), /^[a-f0-9]{64}$/);
            assert.match(options.body.get('submission_id'), /^[a-f0-9]{32}$/);
            assert.equal(options.body.get('email'), 'applicant@example.test');
            return new Promise(resolve => { resolveRequest = resolve; });
        };
        s.$('[data-open-registration]').click(); fillRegistration(s);
        const initialId = s.$('[name="submission_id"]').value;
        submitRegistration(s); submitRegistration(s);
        assert.equal(calls, 1);
        assert.equal(s.$('#registrationSubmit').disabled, true);
        assert.equal(s.$('#registrationSuccess').hidden, true);
        resolveRequest({ ok: true, json: async () => ({ ok: true, reference: 'SLSC-261102-ABCDEF123456' }) });
        await flush();
        assert.equal(s.$('#registrationForm').hidden, true);
        assert.equal(s.$('#registrationSuccess').hidden, false);
        assert.equal(s.$('#registrationReference').textContent, 'SLSC-261102-ABCDEF123456');
        s.$('#newRegistration').click();
        assert.equal(s.$('#registrationForm').hidden, false);
        assert.equal(s.$('#reg-full_name').value, '');
        assert.equal(s.$('#reg-consent').checked, false);
        assert.notEqual(s.$('[name="submission_id"]').value, initialId);
    } finally { s.close(); }
});

test('server field errors retain the draft and focus the re-enabled input', async () => {
    const s = setup();
    try {
        s.w.fetch = async () => ({ ok: false, status: 422, json: async () => ({ ok: false, code: 'regFixErrors', errors: { email: 'regErrorEmail' } }) });
        s.$('[data-open-registration]').click(); fillRegistration(s); submitRegistration(s); await flush();
        assert.equal(s.$('#reg-full_name').value, 'Test Applicant');
        assert.equal(s.$('#reg-email').disabled, false);
        assert.equal(s.$('#reg-email').getAttribute('aria-invalid'), 'true');
        assert.equal(s.d.activeElement, s.$('#reg-email'));
        assert.equal(s.$('#registrationSuccess').hidden, true);
        assert.equal(s.$('#registrationSubmit').disabled, false);
    } finally { s.close(); }
});

test('network errors keep the same submission id for a safe retry', async () => {
    const s = setup();
    try {
        const ids = [];
        s.w.fetch = async (url, options) => { ids.push(options.body.get('submission_id')); throw new TypeError('Network unavailable'); };
        s.$('[data-open-registration]').click(); fillRegistration(s); submitRegistration(s); await flush();
        assert.equal(s.$('#registrationAlert').textContent, translations.en.regSaveError);
        assert.equal(s.$('#reg-full_name').value, 'Test Applicant');
        submitRegistration(s); await flush();
        assert.equal(ids.length, 2);
        assert.equal(ids[0], ids[1]);
        assert.equal(s.$('#registrationSuccess').hidden, true);
    } finally { s.close(); }
});

test('registration labels and validation follow the selected language', () => {
    const s = setup();
    try {
        s.$('[data-lang="si"]').click(); s.$('[data-open-registration]').click(); submitRegistration(s);
        assert.equal(s.$('#registrationTitle').textContent, translations.si.regModalTitle);
        assert.equal(s.$('[data-error-for="full_name"]').textContent, translations.si.regErrorName);
        assert.equal(s.$('#reg-full_name').placeholder, translations.si.regNamePlaceholder);
    } finally { s.close(); }
});

test('standalone registration remains a usable native POST form', () => {
    const s = setup({ standalone: true });
    try {
        assert.equal(s.$('#registrationDialog'), null);
        assert.equal(s.$('#registrationForm').getAttribute('action'), 'register.php');
        assert.equal(s.$('#registrationForm').method, 'post');
        submitRegistration(s);
        assert.equal(s.$('#registrationAlert').hidden, false);
        s.$('[data-lang="si"]').click();
        assert.ok(s.d.title.includes(translations.si.regModalTitle));
    } finally { s.close(); }
});
