// Run with NAVIGATION_DOM_MODULE pointing to linkedom's standalone worker.js (saved as .mjs).
// This keeps the DOM test dependency outside the application's production bundle.
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { initializeNavigation } from '../../themes/slsc/assets/js/navigation.js';
import { pathToFileURL } from 'node:url';

const { parseHTML } = await import(pathToFileURL(process.env.NAVIGATION_DOM_MODULE).href);
globalThis.DOMParser = class {
    parseFromString(html) {
        return parseHTML(`<!doctype html><html><head></head><body>${html}</body></html>`).document;
    }
};
globalThis.NodeFilter = { SHOW_TEXT: 4 };
globalThis.window = { confirm: () => true };
const { validNavigationHref } = await import('../../resources/js/navigation-editor.js');

const existing = `<header class="keep-header"><a class="brand" href="[[site_url:/]]">School</a>
<nav class="desktop-nav"><a href="[[site_url:/]]">Home</a><div class="drop"><a class="drop-toggle" href="#">History<i class="fa-chevron-down"></i></a><div class="dropdown"><a href="[[site_url:/history]]">School History</a></div></div><a href="[[site_url:/courses]]">Courses</a></nav>
<div id="mobileMenu"><div class="container sos-mobile-navigation"><a href="[[site_url:/]]">Home</a><details><summary>History</summary><a href="[[site_url:/history]]">School History</a></details><a href="[[site_url:/courses]]">Courses</a><a class="conduct" href="[[site_url:/conduct]]">Code of Conduct</a></div></div></header>`;
const baseCss = readFileSync(new URL('../../resources/css/navigation-starter.css', import.meta.url), 'utf8');
const config = {
    defaultHtml: '<header>[[site_name]]<nav class="aw-navigation"><a href="[[site_url:/]]">Home</a><a href="[[site_url:/news]]">News</a></nav></header>',
    baseCss, links: [{ label: 'Courses', href: '[[site_url:/courses]]' }], pages: [],
    icons: [{ label: 'Home', class: 'fa-solid fa-house' }, { label: 'Education', class: 'fa-solid fa-graduation-cap' }, { label: 'People', class: 'fa-solid fa-users' }],
    siteMenuHtml: '<nav class="aw-navigation"><ul><li><a href="/">Shared Home</a></li><li><a href="/news">Shared News</a></li></ul></nav>',
};
function editor(html = existing, css = '.keep-header { padding: 17px; }', configuration = config) {
    const wire = { layoutHtml: html, layoutCss: css, $set(name, value) { this[name] = value; } };
    const value = window.awcmsNavigationEditor(wire, configuration);
    value.$watch = () => {};
    value.$nextTick = (callback) => callback();
    value.$refs = { menuList: {}, preview: {}, linkLabel: { focus() {} } };
    value.queuePreview = () => {};
    value.init();
    return { value, wire };
}
const doc = (html) => new DOMParser().parseFromString(html);

const referenceHeader = `<header class="aw-header aw-header-reference">
<div class="aw-topbar"><span class="aw-topbar-text">School, Kandy</span><span class="aw-topbar-motto">Technological Sound</span><div class="aw-header-languages">[[language_switcher]]</div></div>
<div class="aw-header-inner"><a class="aw-brand" href="[[site_url:/]]"><span class="aw-brand-logo">[[site_logo]]</span><span class="aw-brand-copy"><span class="aw-brand-title">School of Signals</span><span class="aw-brand-subtitle">Sri Lanka Army</span></span></a>
<nav class="aw-navigation"><a class="custom-home" href="[[site_url:/]]">Home</a><a href="[[site_url:/courses]]">Courses</a></nav>
<div class="aw-header-actions"><a class="aw-header-conduct" href="[[site_url:/documents]]"><span class="aw-action-label">Code of Conduct</span></a><span class="aw-header-search">[[search_button]]</span><a class="aw-header-portal" href="https://lms.army.lk"><span class="aw-action-label">LMS Portal</span></a>[[mobile_menu_toggle]]</div></div>
<div id="mobileMenu"><div class="aw-mobile-navigation"><a href="[[site_url:/]]">Home</a><a href="[[site_url:/courses]]">Courses</a></div><div class="aw-mobile-actions"><a class="aw-header-conduct" href="[[site_url:/documents]]"><span class="aw-action-label">Code of Conduct</span></a><a class="aw-header-portal" href="https://lms.army.lk"><span class="aw-action-label">LMS Portal</span></a></div></div></header>`;

test('header branding, layout, language controls and actions round-trip through Visual and HTML', () => {
    const { value, wire } = editor(referenceHeader, baseCss);
    assert.equal(value.header.available, true);
    assert.equal(value.header.brandTitle, 'School of Signals');
    assert.equal(value.updateHeader('brandTitle', 'සංඥා පාසල'), true);
    value.updateHeader('topbarTitle', 'මහනුවර');
    value.updateHeader('conductLabel', 'චර්යාධර්ම');
    value.updateHeader('conductHref', '/si/documents');
    value.updateHeader('portalHref', 'https://learning.example.test');
    value.updateHeader('showSearch', false);
    value.updateHeader('layout', 'stacked');
    value.updateHeader('logoUrl', '/images/custom-logo.png');
    let result = doc(wire.layoutHtml);
    assert.equal(result.querySelectorAll('a[href="[[site_url:/si/documents]]"]').length, 2);
    assert.equal(result.querySelectorAll('a[href="https://learning.example.test"]').length, 2);
    assert.equal(result.querySelector('.aw-header-search').classList.contains('aw-hidden'), true);
    assert.equal(result.querySelector('.aw-header').classList.contains('aw-header-stacked'), true);
    assert.equal(result.querySelector('.aw-brand-logo img').getAttribute('src'), '/images/custom-logo.png');
    value.html = value.html.replace('>සංඥා පාසල<', '>New school name<');
    value.switchMode('visual');
    assert.equal(value.header.brandTitle, 'New school name');
    assert.equal(value.header.showSearch, false);
    assert.equal(value.header.layout, 'stacked');
    value.update('label', 'Homepage');
    result = doc(value.html);
    assert.equal(result.querySelector('.aw-brand-title').textContent, 'New school name');
    assert.equal(result.querySelectorAll('.aw-mobile-actions a').length, 2);
    assert.equal(result.querySelectorAll('#mobileMenu a[href="[[site_url:/si/documents]]"]').length, 1);
});

test('visual menu edits preserve CSS changes made inside the base block and custom layouts', () => {
    const customCss = baseCss.replace('grid-template-columns: auto minmax(0, 1fr) auto', 'grid-template-columns: 240px 1fr 260px') + '\n.aw-topbar { border-bottom: 3px solid red; }';
    const { value } = editor(referenceHeader, customCss);
    value.update('label', 'Updated');
    value.chooseIcon('fa-solid fa-house');
    assert.equal(value.css, customCss);
    value.updateHeader('motto', 'A custom motto');
    assert.equal(value.css, customCss);
});

test('header colours can be edited from either mode and undo restores the header fields', () => {
    const { value } = editor(referenceHeader, baseCss);
    value.headerStyle.background = '#abc123';
    value.applyHeaderStyle();
    value.css = value.css.replace('--aw-header-background: #abc123', '--aw-header-background: #123abc');
    value.codeChanged();
    assert.equal(value.headerStyle.background, '#123abc');
    value.updateHeader('brandTitle', 'Edited name');
    value.undo(-1);
    assert.equal(value.header.brandTitle, 'School of Signals');
});

test('the reference layout preserves current menu links and custom CSS when applied', () => {
    const { value } = editor(existing, '.custom { color: red; }', { ...config, defaultHtml: referenceHeader });
    value.applyHeaderPreset();
    assert.equal(value.header.available, true);
    assert.deepEqual(value.rows().map((item) => item.label), ['Home', 'History', 'School History', 'Courses']);
    assert.ok(value.css.includes('.custom { color: red; }'));
    assert.ok(value.html.includes('[[language_switcher]]'));
    assert.ok(doc(value.html).querySelector('#mobileMenu .conduct'));
});

test('unsafe header addresses cannot be saved and labels are inserted as text', () => {
    const { value } = editor(referenceHeader, baseCss);
    const original = value.html;
    assert.equal(value.updateHeader('portalHref', 'javascript:alert(1)'), false);
    assert.equal(value.updateHeader('logoUrl', 'javascript:alert(1)'), false);
    assert.equal(value.html, original);
    value.header.portalHref = 'javascript:alert(1)';
    assert.equal(value.flush(), false);
    value.header.portalHref = 'https://lms.army.lk';
    value.updateHeader('brandTitle', '<img src=x onerror=alert(1)>');
    assert.equal(doc(value.html).querySelector('.aw-brand-title img'), null);
});

test('removing optional header components in HTML does not block a later visual save', () => {
    const { value } = editor(referenceHeader, baseCss);
    const document = doc(value.html);
    document.querySelectorAll('.aw-header-portal, .aw-header-conduct, .aw-brand-logo').forEach((node) => node.remove());
    value.html = document.body.innerHTML;
    value.switchMode('visual');
    assert.equal(value.header.showPortal, false);
    assert.equal(value.flushHeader(), true);
    assert.equal(doc(value.html).querySelector('.aw-header-portal'), null);
});

test('opening existing HTML preserves the header and CSS exactly', () => {
    const { value, wire } = editor();
    assert.equal(value.problem, '');
    assert.equal(value.items.length, 4);
    assert.equal(value.html, existing);
    assert.equal(wire.layoutCss, '.keep-header { padding: 17px; }');
    assert.deepEqual(value.rows().map((item) => item.depth), [0, 0, 1, 0]);
});

test('new headers receive defaults while an intentionally empty menu stays empty', () => {
    const fresh = editor('', '');
    assert.equal(fresh.value.items.length, 2);
    assert.equal(fresh.wire.layoutHtml, config.defaultHtml);
    const empty = editor('<header>School<nav></nav></header>');
    assert.equal(empty.value.items.length, 0);
    assert.equal(empty.value.html, '<header>School<nav></nav></header>');
});

test('visual edits update both menus while preserving branding and mobile action links', () => {
    const { value, wire } = editor();
    value.selectedId = value.items.find((item) => item.label === 'Courses').id;
    value.update('label', 'Training');
    value.update('href', '/training');
    assert.equal(value.displayHref(), '/training');
    const result = doc(wire.layoutHtml);
    assert.equal(result.querySelector('.brand').textContent, 'School');
    assert.equal(result.querySelector('nav a[href="[[site_url:/training]]"]').textContent, 'Training');
    assert.equal(result.querySelector('#mobileMenu a[href="[[site_url:/training]]"]').textContent, 'Training');
    assert.equal(result.querySelectorAll('#mobileMenu .conduct').length, 1);
    assert.ok(wire.layoutCss.startsWith('.keep-header { padding: 17px; }'));
});

test('adding a submenu creates nested desktop and mobile markup', () => {
    const { value } = editor();
    const parent = value.items.find((item) => item.label === 'History');
    value.add(parent.id);
    value.update('label', 'Commandants');
    value.update('href', '/commandants');
    const result = doc(value.html);
    assert.equal(result.querySelectorAll('nav .aw-nav-submenu a').length, 2);
    assert.ok(result.querySelector('#mobileMenu details').textContent.includes('Commandants'));
});

test('HTML changes reappear visually and update mobile without stale duplicated links', () => {
    const { value, wire } = editor();
    value.html = value.html.replace('<a href="[[site_url:/courses]]">Courses</a>', '<a class="custom-link" href="[[site_url:/training]]">Training HTML</a>');
    value.codeChanged('html');
    value.switchMode('visual');
    assert.ok(value.items.some((item) => item.label === 'Training HTML'));
    const result = doc(wire.layoutHtml);
    assert.ok(result.querySelector('nav .custom-link'));
    assert.equal(result.querySelectorAll('#mobileMenu a[href="[[site_url:/courses]]"]').length, 0);
    assert.equal(result.querySelectorAll('#mobileMenu a[href="[[site_url:/training]]"]').length, 1);
});

test('moving a parent moves its subtree and prevents cyclic parents', () => {
    const { value } = editor();
    const parent = value.items.find((item) => item.label === 'History');
    const child = value.items.find((item) => item.parentId === parent.id);
    value.move(parent.id, -1);
    assert.deepEqual(value.rows().map((item) => item.label), ['History', 'School History', 'Home', 'Courses']);
    value.selectedId = parent.id;
    value.update('parentId', child.id);
    assert.equal(value.selected().parentId, '');
    assert.ok(!value.parentChoices().some((item) => item.id === child.id));
});

test('drag reordering keeps descendants attached to the dragged parent', () => {
    const { value } = editor();
    const parent = value.items.find((item) => item.label === 'History');
    const target = value.items.find((item) => item.label === 'Courses');
    value.reorder(parent.id, target.id, true);
    assert.deepEqual(value.rows().map((item) => item.label), ['Home', 'Courses', 'History', 'School History']);
});

test('removing a parent removes its children but preserves unrelated links', () => {
    const { value } = editor();
    value.selectedId = value.items.find((item) => item.label === 'History').id;
    value.remove();
    assert.deepEqual(value.rows().map((item) => item.label), ['Home', 'Courses']);
    assert.ok(doc(value.html).querySelector('#mobileMenu .conduct'));
});

test('undo and redo restore both source and CSS after replacing a draft with defaults', () => {
    const { value, wire } = editor();
    value.defaults();
    assert.equal(value.html, config.defaultHtml);
    value.undo(-1);
    assert.equal(value.html, existing);
    assert.equal(wire.layoutCss, '.keep-header { padding: 17px; }');
    value.undo(1);
    assert.equal(value.html, config.defaultHtml);
});

test('appearance changes preserve custom CSS and are read back from the code', () => {
    const { value, wire } = editor();
    value.style.color = '#abcdef';
    value.style.gap = 33;
    value.applyStyle();
    assert.ok(wire.layoutCss.includes('--aw-nav-color: #abcdef'));
    assert.ok(wire.layoutCss.includes('.keep-header { padding: 17px; }'));
    value.css = value.css.replace('--aw-nav-gap: 33px', '--aw-nav-gap: 19px');
    value.codeChanged();
    assert.equal(value.style.gap, 19);
});

test('unsupported HTML stays intact and never becomes a partially parsed menu', () => {
    const html = '<header><nav><section><h2>Custom mega menu</h2><a href="/one">One</a></section></nav></header>';
    const { value } = editor(html);
    assert.ok(value.problem);
    value.write();
    assert.equal(value.html, html);
    assert.equal(value.items.length, 0);
});

test('shared menu placeholders can be edited without losing the rest of the header', () => {
    for (const html of ['<header>Brand [[primary_menu]]</header>', '<header>Brand<nav>[[primary_menu]]</nav></header>']) {
        const { value } = editor(html);
        assert.equal(value.problem, '');
        assert.equal(value.items.length, 2);
        value.update('label', 'Our Home');
        assert.ok(value.html.includes('Brand'));
        assert.ok(!value.html.includes('[[primary_menu]]'));
        assert.ok(doc(value.html).querySelector('nav').textContent.includes('Our Home'));
    }
});

test('nested list markup and link icons remain editable', () => {
    const { value } = editor('<header><nav><ul><li><a class="keep-link" href="/one"><i class="fa-star"></i><span>One</span></a><ul><li><a href="/two">Two</a></li></ul></li></ul></nav></header>');
    assert.equal(value.problem, '');
    value.update('label', 'First');
    const link = doc(value.html).querySelector('nav .keep-link');
    assert.ok(link.querySelector('.fa-star'));
    assert.equal(link.querySelector('span').textContent, 'First');
});

test('link validation accepts public links and rejects executable or malformed addresses', () => {
    for (const href of ['/courses', '[[site_url:/si/courses]]', '#history', 'https://example.com/page', 'mailto:a@example.com', 'tel:+94123456789']) assert.equal(validNavigationHref(href), true, href);
    for (const href of ['javascript:alert(1)', 'data:text/html,test', '//example.com', '\\example.com', '/path with spaces', '', '/path\nnext']) assert.equal(validNavigationHref(href), false, href);
});

test('pending input is flushed before saving and an invalid link blocks the save', () => {
    const { value, wire } = editor();
    value.$refs.linkLabel.value = 'මුල් පිටුව';
    value.$refs.linkAddress = { value: '/new-home' };
    assert.equal(value.flush(), true);
    assert.ok(wire.layoutHtml.includes('මුල් පිටුව'));
    assert.ok(wire.layoutHtml.includes('[[site_url:/new-home]]'));
    value.$refs.linkLabel.value = value.selected().label;
    value.$refs.linkAddress.value = 'javascript:alert(1)';
    assert.equal(value.flush(), false);
    assert.ok(!wire.layoutHtml.includes('javascript:'));
});

test('zero spacing survives switching between code and appearance controls', () => {
    const { value } = editor();
    value.style.gap = 0;
    value.applyStyle();
    value.readStyle();
    assert.equal(value.style.gap, 0);
});

test('chosen Font Awesome icons round-trip through HTML and both menu layouts', () => {
    const { value } = editor();
    value.chooseIcon('fa-solid fa-house');
    let result = doc(value.html);
    assert.equal(result.querySelectorAll('nav > a:first-child .fa-house').length, 1);
    assert.equal(result.querySelectorAll('#mobileMenu a:first-child .fa-house').length, 1);
    assert.equal(result.querySelector('nav .fa-house').getAttribute('aria-hidden'), 'true');
    assert.equal(result.querySelector('nav > a:first-child').textContent, 'Home');
    value.switchMode('visual');
    assert.equal(value.selected().icon, 'fa-solid fa-house');
    value.chooseIcon('fa-solid fa-users');
    result = doc(value.html);
    assert.equal(result.querySelectorAll('nav > a:first-child .fa-house').length, 0);
    assert.equal(result.querySelectorAll('nav > a:first-child .fa-users').length, 1);
    value.switchMode('visual');
    value.chooseIcon('');
    assert.equal(doc(value.html).querySelectorAll('nav > a:first-child .aw-nav-icon').length, 0);
});

test('dropdown arrows stay separate from chosen icons and menu labels', () => {
    const { value } = editor();
    value.selectedId = value.items.find((item) => item.label === 'History').id;
    assert.equal(value.selected().icon, '');
    value.chooseIcon('fa-solid fa-house');
    const result = doc(value.html);
    assert.equal(result.querySelectorAll('nav .drop-toggle .aw-nav-icon').length, 1);
    assert.equal(result.querySelectorAll('nav .drop-toggle .aw-nav-indicator').length, 1);
    assert.equal(result.querySelectorAll('#mobileMenu summary .aw-nav-icon').length, 1);
    value.read();
    assert.ok(value.items.some((item) => item.label === 'History' && item.icon === 'fa-solid fa-house'));
});

test('icon search is case-insensitive and unknown icon selections do not alter the draft', () => {
    const { value } = editor();
    value.iconSearch = 'EDUCATION';
    assert.deepEqual(value.filteredIcons().map((icon) => icon.class), ['fa-solid fa-graduation-cap']);
    const original = value.html;
    value.chooseIcon('unknown-class');
    assert.equal(value.html, original);
});

test('old default headers gain a mobile toggle without losing custom CSS', () => {
    const { value } = editor('<header class="aw-header"><div class="aw-header-inner">Brand<nav class="aw-navigation"><a href="/">Home</a></nav></div></header>');
    value.update('label', 'Updated home');
    const result = doc(value.html);
    assert.ok(result.querySelector('.aw-header-actions').textContent.includes('[[mobile_menu_toggle]]'));
    assert.equal(result.querySelectorAll('#mobileMenu .aw-mobile-navigation a').length, 1);
    assert.ok(value.css.includes('.keep-header { padding: 17px; }'));
    assert.ok(value.css.includes('.aw-header nav.aw-desktop-navigation'));
});

test('desktop, tablet and mobile previews retain their real viewport widths when scaled', () => {
    const { value } = editor();
    value.$refs.previewStage = { clientWidth: 700 };
    value.setPreviewWidth('desktop');
    assert.equal(value.frameWidth(), 1280);
    assert.equal(value.previewScale, 676 / 1280);
    value.setPreviewWidth('tablet');
    assert.equal(value.frameWidth(), 820);
    value.setPreviewWidth('mobile');
    assert.equal(value.frameWidth(), 390);
    assert.equal(value.previewScale, 1);
});

test('an invalid preview response preserves the last valid preview', async () => {
    const { value, wire } = editor();
    value.$refs.preview.srcdoc = 'Last valid preview';
    wire.previewNavigation = async () => null;
    await value.refreshPreview();
    assert.equal(value.$refs.preview.srcdoc, 'Last valid preview');
    assert.ok(value.previewError);
    assert.equal(value.previewBusy, false);
});

test('preview controls open and close the mobile menu without executing iframe scripts', () => {
    const { value } = editor();
    value.setPreviewWidth('mobile');
    const preview = doc('<button id="mobileToggle"><i class="fa-solid fa-bars"></i></button><div id="mobileMenu" class="mobile-menu"></div>');
    preview.body.getBoundingClientRect = () => ({ height: 420 });
    value.$refs.preview.contentDocument = preview;
    value.setPreviewMenu(true);
    assert.equal(preview.querySelector('#mobileToggle').getAttribute('aria-expanded'), 'true');
    assert.ok(preview.querySelector('#mobileMenu').classList.contains('open'));
    assert.ok(preview.querySelector('#mobileToggle .fa-xmark'));
    assert.equal(value.previewHeight, 452);
    value.setPreviewMenu(false);
    assert.ok(!preview.querySelector('#mobileMenu').classList.contains('open'));
    assert.ok(preview.querySelector('#mobileToggle .fa-bars'));
});

test('preview highlights the language home page without using the admin iframe location', () => {
    const { value } = editor();
    const preview = doc('<base href="https://example.test/school/"><body><nav class="aw-navigation"><a id="home" href="https://example.test/school/si">Home</a><a id="other" class="active" href="https://example.test/school/courses" aria-current="page">Courses</a><a id="external" href="https://other.test/school/si">Other site</a></nav></body>');
    preview.body.dataset.previewCurrentUrl = 'https://example.test/school/si';
    value.highlightPreviewLinks(preview);
    assert.equal(preview.querySelector('#home').getAttribute('aria-current'), 'page');
    assert.ok(!preview.querySelector('#other').classList.contains('active'));
    assert.equal(preview.querySelector('#other').getAttribute('aria-current'), null);
    assert.ok(!preview.querySelector('#external').classList.contains('active'));
});

function publicNavigation() {
    const document = doc('<base href="https://example.test/school/"><header><nav class="desktop-nav"><a id="current" href="https://example.test/school/news">News</a><a id="different" href="https://example.test/other/news">Other news</a><a id="external" href="https://other.test/school/news">External</a></nav><button id="mobileToggle"><i class="fa-solid fa-bars"></i></button><div id="mobileMenu" class="mobile-menu"><details><summary>Updates</summary><a href="/school/news">News</a></details></div></header><main id="outside">Content</main>');
    const bus = document.createElement('div');
    const browser = {
        location: new URL('https://example.test/school/news'), innerWidth: 390,
        addEventListener: bus.addEventListener.bind(bus), dispatchEvent: bus.dispatchEvent.bind(bus),
    };
    initializeNavigation(document, browser);
    return { document, browser, Event: parseHTML('<html></html>').window.Event };
}

test('public navigation highlights exact local routes and expands the current submenu', () => {
    const { document } = publicNavigation();
    assert.equal(document.querySelector('#current').getAttribute('aria-current'), 'page');
    assert.ok(!document.querySelector('#different').classList.contains('active'));
    assert.ok(!document.querySelector('#external').classList.contains('active'));
    assert.equal(document.querySelector('details').open, true);
});

test('public mobile toggle closes with Escape, outside click, navigation and desktop resize', () => {
    const { document, browser, Event } = publicNavigation();
    const toggle = document.querySelector('#mobileToggle');
    const menu = document.querySelector('#mobileMenu');
    let focusReturned = false;
    toggle.focus = () => { focusReturned = true; };
    const open = () => toggle.dispatchEvent(new Event('click', { bubbles: true }));
    open();
    assert.equal(toggle.getAttribute('aria-expanded'), 'true');
    const escape = new Event('keydown'); escape.key = 'Escape'; document.dispatchEvent(escape);
    assert.ok(!menu.classList.contains('open'));
    assert.equal(focusReturned, true);
    open();
    document.querySelector('#outside').dispatchEvent(new Event('click', { bubbles: true }));
    assert.ok(!menu.classList.contains('open'));
    open();
    document.querySelector('#mobileMenu a').dispatchEvent(new Event('click', { bubbles: true }));
    assert.ok(!menu.classList.contains('open'));
    open(); browser.innerWidth = 1280; browser.dispatchEvent(new Event('resize'));
    assert.ok(!menu.classList.contains('open'));
    assert.equal(toggle.getAttribute('aria-expanded'), 'false');
    assert.ok(toggle.querySelector('.fa-bars'));
});
