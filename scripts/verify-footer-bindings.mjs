// Generate the fixture with FOOTER_EDITOR_WRITE_FIXTURE=1 and FooterEditorTest.php.
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';
import vm from 'node:vm';

const { parseHTML } = await import(pathToFileURL(process.env.NAVIGATION_DOM_MODULE).href);
const { window } = parseHTML('<html><body></body></html>');
window.document.body.setAttribute('x-data', '{}');
const fixture = parseHTML(readFileSync('storage/framework/testing/footer-editor.html', 'utf8')).document;
const form = fixture.querySelector('form[wire\\:submit="saveDraft"]');
assert.ok(form);
window.document.body.append(form);
Object.assign(globalThis, {
    window, document: window.document, MutationObserver: window.MutationObserver,
    CustomEvent: window.CustomEvent, Element: window.Element, HTMLElement: window.HTMLElement,
    Node: window.Node, ShadowRoot: window.ShadowRoot, NodeFilter: { SHOW_TEXT: 4 },
    DOMParser: class { parseFromString(html) { return parseHTML(`<!doctype html><html><head></head><body>${html}</body></html>`).document; } },
});
// In a browser template children live only in .content, outside Alpine's tree walk.
Object.defineProperty(window.HTMLTemplateElement.prototype, 'firstElementChild', { configurable: true, get() { return null; } });
// linkedom exposes a read-only select value; browsers provide a setter.
Object.defineProperty(window.HTMLSelectElement.prototype, 'value', {
    configurable: true,
    get() { return [...this.options].find((option) => option.selected)?.value || this.options[0]?.value || ''; },
    set(value) { for (const option of this.options) option.selected = option.value === String(value); },
});
globalThis.requestAnimationFrame = (callback) => setTimeout(callback, 0);
globalThis.getComputedStyle = () => ({ display: 'block', transitionDuration: '0s', transitionDelay: '0s', animationDuration: '0s' });
await import('../resources/js/footer-editor.js');
globalThis.awcmsFooterEditor = window.awcmsFooterEditor;
const source = readFileSync('vendor/livewire/livewire/dist/livewire.esm.js', 'utf8').split('// node_modules/@alpinejs/collapse/dist/module.cjs.js')[0];
vm.runInThisContext(source + '\nglobalThis.footerQaAlpine = require_module_cjs().Alpine;');
const initial = '<footer class="aw-footer"><div class="aw-footer-grid"><section><h2>School</h2><p>Welcome</p></section><section><h2>Quick Links</h2><a href="[[site_url:/]]">Home</a><a href="[[site_url:/courses]]">Courses</a></section><section><h2>Contact</h2>[[contact_details]]</section></div><div class="aw-footer-bottom"><p>© [[copyright_year]] [[site_name]]</p></div></footer>';
const wire = footerQaAlpine.reactive({
    layoutHtml: initial, layoutCss: '',
    $set(name, value) { this[name] = value; },
    async previewLayout() { return '<html><body>Preview</body></html>'; },
});
footerQaAlpine.magic('wire', () => wire);
footerQaAlpine.start();
const tick = () => new Promise((resolve) => setTimeout(resolve, 30));
await tick();
const root = form.querySelector('[x-data^="awcmsFooterEditor"]');
const state = root._x_dataStack[0];
const live = (selector) => [...root.querySelectorAll(selector)].filter((node) => !node.closest('template'));
const button = (text) => live('button').find((node) => node.textContent.trim() === text);
assert.equal(root.querySelectorAll('[data-column-index]').length, 4);
button('Quick Links').click(); await tick();
assert.equal(state.columnIndex, 1);
assert.equal(state.blocks.length, 3);
state.selectBlock(1); await tick();
let field = live('[x-ref="blockText"]')[0];
assert.equal(field.value, 'Home');
field.value = 'Start here'; field.dispatchEvent(new window.Event('input', { bubbles: true })); await tick();
assert.ok(wire.layoutHtml.includes('Start here'));
const address = live('[x-ref="blockAddress"]')[0];
address.value = '/welcome';
form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })); await tick();
assert.ok(wire.layoutHtml.includes('[[site_url:/welcome]]'));
address.value = 'javascript:alert(1)';
const blocked = new window.Event('submit', { bubbles: true, cancelable: true });
form.dispatchEvent(blocked); await tick();
assert.ok(blocked.defaultPrevented);
address.value = '/welcome'; address.dispatchEvent(new window.Event('change', { bubbles: true })); await tick();
button('Contact').click(); await tick();
state.selectBlock(1); await tick();
assert.equal(state.selected().kind, 'widget');
assert.equal(live('[x-ref="blockText"]').length, 0);
button('Add column').click(); await tick();
assert.equal(root.querySelectorAll('[data-column-index]').length, 5);
button('HTML / CSS').click(); await tick();
assert.equal(state.mode, 'code');
field = live('textarea[x-model="html"]')[0];
field.value = state.html.replace('New column', 'Edited in code');
field.dispatchEvent(new window.Event('input', { bubbles: true }));
button('Visual').click(); await tick();
assert.ok(state.html.includes('Edited in code'));
assert.ok(state.blocks.some((block) => block.text === 'Edited in code'));
button('Mobile').click(); await tick();
assert.equal(state.frameWidth(), 390);
assert.equal(root.querySelector('iframe').title, 'Footer preview');
state.destroy(); footerQaAlpine.stopObservingMutations();
console.log('PASS: rendered Alpine column selection, block fields, input sync, invalid-link save guard, CMS blocks, add column, code/visual switching and mobile preview.');
