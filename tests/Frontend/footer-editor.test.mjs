// Uses the same external linkedom worker as navigation-editor.test.mjs.
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { pathToFileURL } from 'node:url';

const { parseHTML } = await import(pathToFileURL(process.env.NAVIGATION_DOM_MODULE).href);
globalThis.DOMParser = class { parseFromString(html) { return parseHTML(`<!doctype html><html><head></head><body>${html}</body></html>`).document; } };
globalThis.NodeFilter = { SHOW_TEXT: 4 };
globalThis.window = { confirm: () => true };
await import('../../resources/js/footer-editor.js');
const doc = (html) => new DOMParser().parseFromString(html);
const existing = `<aside>Keep outside content</aside><footer class="managed-school-footer">
<div class="managed-school-footer-accent"></div><div class="managed-school-footer-main"><div class="managed-school-footer-grid">
<section class="managed-school-footer-about"><a class="brand" href="[[site_url:/]]"><span>[[site_logo]]</span><span><strong>[[site_name]]</strong><small><i class="fa-solid fa-shield-halved"></i> Sri Lanka Army</small></span></a><p>School information</p></section>
<nav class="managed-school-footer-links"><h2><i class="fa-solid fa-link"></i> Quick Links</h2><div class="managed-school-footer-link-list"><a href="[[site_url:/]]"><i class="fa-solid fa-house"></i><span>Home</span></a><a href="[[site_url:/courses]]">Courses</a></div></nav>
<section><h2>Contact</h2>[[contact_details]]</section><section><h2>Follow Us</h2>[[social_links]]</section>
</div></div><div class="managed-school-footer-bottom"><div class="managed-school-footer-bottom-row"><p>© [[copyright_year]] [[site_name]]. All rights reserved.</p><p>Custom credit</p></div></div></footer>`;
const originalCss = '.managed-school-footer { color: #123456; }';
const config = {
    defaultHtml: '<footer class="aw-footer"><div class="aw-footer-grid"><section><h2>[[site_name]]</h2><p>[[site_tagline]]</p></section><section><h2>Contact</h2>[[contact_details]]</section></div><div class="aw-footer-bottom"><p>© [[copyright_year]]</p></div></footer>',
    baseCss: readFileSync(new URL('../../resources/css/footer-starter.css', import.meta.url), 'utf8'),
    links: [{ label: 'Training', href: '[[site_url:/courses]]' }], pages: [{ label: 'History', href: '[[site_url:/en/pages/history]]' }],
    icons: [{ label: 'Home', class: 'fa-solid fa-house' }, { label: 'Education', class: 'fa-solid fa-graduation-cap' }],
};
function editor(html = existing, css = originalCss) {
    const wire = { layoutHtml: html, layoutCss: css, $set(name, value) { this[name] = value; } };
    const value = window.awcmsFooterEditor(wire, config);
    value.$watch = () => {}; value.$nextTick = (callback) => callback();
    value.$refs = { columnList: {}, preview: {} }; value.queuePreview = () => {};
    value.init(); return { value, wire };
}

test('opening the supplied footer preserves HTML, icons, placeholders and CSS exactly', () => {
    const { value, wire } = editor();
    assert.equal(value.html, existing); assert.equal(wire.layoutHtml, existing); assert.equal(wire.layoutCss, originalCss);
    assert.equal(value.columns.length, 5); assert.equal(value.columns[4].label, 'Copyright bar');
    assert.deepEqual(value.blocks.map((block) => block.kind), ['brand', 'widget', 'text', 'text', 'text']);
});

test('brand subtitle and description edits retain logo, structure and decorative icons', () => {
    const { value } = editor();
    value.selectBlock(value.blocks.findIndex((block) => block.text === 'Sri Lanka Army'));
    value.update('text', 'Signal Corps');
    const result = doc(value.html);
    assert.equal(result.querySelector('small').textContent, 'Signal Corps');
    assert.ok(result.querySelector('small i.fa-shield-halved'));
    assert.ok(result.querySelector('.brand').textContent.includes('[[site_logo]]'));
    assert.equal(result.querySelector('aside').textContent, 'Keep outside content');
    assert.equal(value.css, originalCss);
});

test('footer links support local URLs, icons and page choices without rebuilding other columns', () => {
    const { value, wire } = editor(); value.selectColumn(1); value.selectBlock(1);
    value.update('text', 'Welcome'); value.update('href', '/welcome'); value.update('icon', 'fa-solid fa-graduation-cap');
    const result = doc(wire.layoutHtml);
    assert.equal(result.querySelector('nav a').getAttribute('href'), '[[site_url:/welcome]]');
    assert.equal(result.querySelector('nav a span').textContent, 'Welcome');
    assert.ok(result.querySelector('nav a i.fa-graduation-cap'));
    assert.ok(result.querySelector('footer').textContent.includes('[[contact_details]]'));
    value.useLink(1);
    assert.equal(doc(value.html).querySelector('nav a').getAttribute('href'), '[[site_url:/en/pages/history]]');
});

test('pending link input is saved and an unsafe address blocks saving', () => {
    const { value, wire } = editor(); value.selectColumn(1); value.selectBlock(1);
    value.$refs.blockText = { value: 'Latest news' }; value.$refs.blockAddress = { value: '/news' };
    assert.equal(value.flush(), true);
    assert.ok(wire.layoutHtml.includes('[[site_url:/news]]'));
    const saved = wire.layoutHtml;
    value.$refs.blockAddress.value = 'javascript:alert(1)';
    assert.equal(value.flush(), false); assert.equal(wire.layoutHtml, saved);
    value.$refs.blockAddress.value = '/contact'; assert.equal(value.flush(), true);
});

test('adding, moving and removing content blocks preserves existing link wrappers', () => {
    const { value } = editor(); value.selectColumn(1); value.addBlock('link'); value.update('text', 'New training');
    assert.equal(doc(value.html).querySelectorAll('.managed-school-footer-link-list a').length, 3);
    value.moveBlock(-1);
    assert.deepEqual([...doc(value.html).querySelectorAll('nav a')].map((a) => a.textContent.trim()), ['Home', 'New training', 'Courses']);
    value.removeBlock();
    assert.equal(doc(value.html).querySelectorAll('nav a').length, 2);
    value.addBlock('contact_details');
    assert.equal(value.selected().kind, 'widget'); value.removeBlock();
    assert.ok(!doc(value.html).querySelector('nav').textContent.includes('[[contact_details]]'));
});

test('columns reorder and remove as a unit while the copyright bar stays outside the grid', () => {
    const { value } = editor(); value.reorderColumns(1, 0);
    assert.ok(doc(value.html).querySelector('.managed-school-footer-grid').firstElementChild.matches('nav'));
    value.addColumn(); assert.equal(value.columns.filter((column) => column.movable).length, 5);
    value.removeColumn(); assert.equal(value.columns.filter((column) => column.movable).length, 4);
    value.reorderColumns(0, 4);
    assert.equal(doc(value.html).querySelector('.managed-school-footer-bottom-row').parentElement.className, 'managed-school-footer-bottom');
    value.selectColumn(4); value.selectBlock(0); value.update('text', '© [[copyright_year]] Training school');
    assert.ok(value.html.includes('Custom credit'));
});

test('appearance is opt-in, scoped, responsive and keeps unrelated CSS', () => {
    const { value } = editor();
    value.style.background = '#112233'; value.style.gap = 0; value.style.columns = 3; value.applyStyle();
    assert.ok(value.css.startsWith(originalCss)); assert.ok(value.css.includes('--aw-footer-gap: 0px'));
    assert.ok(value.css.includes('--aw-footer-columns: 3')); assert.ok(value.css.includes('@media (max-width: 600px)'));
    assert.ok(doc(value.html).querySelector('footer.aw-footer-styled .aw-footer-grid'));
    assert.equal(value.style.gap, 0);
    value.style.gap = 10; value.applyStyle();
    assert.equal(value.css.split('/* Footer editor appearance */').length, 2);
    assert.equal(value.style.gap, 10);
});

test('code, visual, undo and redo share one draft and defaults require confirmation', () => {
    const { value, wire } = editor();
    value.mode = 'code'; value.html = value.html.replace('School information', 'Edited in HTML'); value.flush(); value.switchMode('visual');
    assert.ok(value.blocks.some((block) => block.text === 'Edited in HTML'));
    value.defaults(); assert.equal(value.html, config.defaultHtml);
    value.undo(-1); assert.ok(value.html.includes('Edited in HTML')); assert.equal(value.css, originalCss);
    value.undo(1); assert.equal(wire.layoutHtml, config.defaultHtml);
    window.confirm = () => false; value.defaults(); assert.equal(value.html, config.defaultHtml); window.confirm = () => true;
});

test('empty drafts get a starter, intentionally empty columns stay empty and unsupported source is preserved', () => {
    assert.equal(editor('', '').wire.layoutHtml, config.defaultHtml);
    assert.equal(editor('  ', '').wire.layoutHtml, config.defaultHtml);
    const empty = editor('<footer><div class="aw-footer-grid"><section></section></div></footer>');
    assert.equal(empty.value.blocks.length, 0); assert.ok(!empty.value.html.includes('New text'));
    const custom = editor('<div>Custom layout without footer</div>');
    assert.ok(custom.value.problem); assert.equal(custom.wire.layoutHtml, '<div>Custom layout without footer</div>');
});

test('removing all columns does not reinsert defaults and new columns can still be added', () => {
    const { value } = editor('<footer><div class="aw-footer-grid"><section><p>Only column</p></section></div></footer>');
    value.removeColumn(); assert.equal(value.columns.length, 0);
    value.addColumn(); assert.equal(value.columns.length, 1); assert.ok(value.html.includes('New column'));
});

test('stale preview responses cannot replace newer footer content', async () => {
    const { value, wire } = editor(); const pending = [];
    wire.previewLayout = () => new Promise((resolve) => pending.push(resolve));
    const first = value.refreshPreview(), second = value.refreshPreview();
    pending[1]('<html>New footer</html>'); await second;
    pending[0]('<html>Old footer</html>'); await first;
    assert.equal(value.$refs.preview.srcdoc, '<html>New footer</html>'); assert.equal(value.previewBusy, false);
});
