import assert from 'node:assert/strict';
import test from 'node:test';
import { layoutPreview } from '../../resources/js/theme-layout-preview.js';

const settle = () => new Promise((resolve) => setTimeout(resolve, 10));
const box = (bottom, height = 100, width = 240) => ({
    getBoundingClientRect: () => ({ bottom, height, width }),
});

function fixture(elements = [], bodyHeight = 132) {
    const doc = new EventTarget();
    doc.defaultView = new EventTarget();
    doc.defaultView.scrollY = 0;
    doc.body = { ...box(bodyHeight, bodyHeight), querySelectorAll: () => elements };
    doc.querySelector = () => null;
    const preview = { contentDocument: doc, srcdoc: '' };
    const value = layoutPreview({});
    value.$refs = { preview, previewStage: { clientWidth: 940 } };
    return { value, doc, preview, elements };
}

test('reserves the full dropdown footprint outside the normal header flow', () => {
    // The header body is 132px. Its hidden absolute dropdown already has a
    // rendered box ending at 438px, even before it becomes visible on hover.
    const { value } = fixture([box(132), box(438, 260)]);
    value.measurePreview();
    assert.equal(value.previewHeight, 470);
    value.fitPreview();
    assert.equal(value.previewScale, 916 / 1280);
    assert.ok(value.previewHeight * value.previewScale > 438 * value.previewScale);
});

test('ignores menus with no rendered box and can shrink after closing a disclosure', () => {
    const { value, elements } = fixture([box(0, 0, 0)], 132);
    value.measurePreview();
    assert.equal(value.previewHeight, 240);
    elements.push(box(820, 600));
    value.measurePreview();
    assert.equal(value.previewHeight, 852);
    elements.pop();
    value.measurePreview();
    assert.equal(value.previewHeight, 240);
});

test('shows complete long mobile menus and footers beyond the previous 1800px cap', () => {
    const { value } = fixture([box(2450, 2250)], 120);
    value.measurePreview();
    assert.equal(value.previewHeight, 2482);
    const footer = fixture([], 2180);
    footer.value.measurePreview();
    assert.equal(footer.value.previewHeight, 2212);
});

test('remeasures responsive geometry after the iframe changes viewport', async () => {
    const { value, doc, elements } = fixture([box(438)]);
    value.previewLoaded();
    assert.equal(value.previewHeight, 470);
    elements[0] = box(1120, 990);
    value.setPreviewWidth('mobile');
    doc.defaultView.dispatchEvent(new Event('resize'));
    await settle();
    assert.equal(value.frameWidth(), 390);
    assert.equal(value.previewScale, 1);
    assert.equal(value.previewHeight, 1152);
    value.destroyPreview();
});

test('updates after hover, keyboard focus, disclosure, animation and asset loading', async () => {
    const { value, doc, elements } = fixture();
    value.previewLoaded();
    for (const name of ['pointerover', 'pointerout', 'focusin', 'focusout', 'toggle', 'transitionend', 'animationend', 'load']) {
        const bottom = 400 + elements.length * 100;
        elements.push(box(bottom));
        doc.dispatchEvent(new Event(name));
        await settle();
        assert.equal(value.previewHeight, bottom + 32, name);
    }
    value.destroyPreview();
});

test('links stay in the editor while mobile controls remain interactive', () => {
    const { value, doc } = fixture();
    value.setPreviewWidth('mobile');
    const classes = new Set();
    const menu = { classList: { toggle: (name, on) => on ? classes.add(name) : classes.delete(name) } };
    doc.querySelector = (selector) => selector === '#mobileMenu' ? menu : null;
    value.previewLoaded();
    assert.ok(!classes.has('open'));
    const target = { closest: (selector) => selector === 'a' || selector.includes('#mobileToggle') ? {} : null };
    const click = new Event('click', { cancelable: true });
    Object.defineProperty(click, 'target', { value: target });
    doc.dispatchEvent(click);
    assert.ok(click.defaultPrevented);
    assert.ok(classes.has('open'));
    value.destroyPreview();
});

test('desktop never expands the mobile menu and mobile expansion survives viewport changes', () => {
    const { value, doc } = fixture();
    const classes = new Set();
    const attributes = {};
    const menu = { classList: { toggle: (name, on) => on ? classes.add(name) : classes.delete(name) } };
    const toggle = { setAttribute: (name, value) => { attributes[name] = value; }, querySelector: () => null };
    doc.querySelector = (selector) => selector === '#mobileMenu' ? menu : toggle;
    value.previewLoaded();
    assert.equal(value.previewMenuExpanded, false);
    assert.equal(attributes['aria-expanded'], 'false');
    value.setPreviewWidth('mobile');
    value.setPreviewMenu(true);
    assert.ok(classes.has('open'));
    value.setPreviewWidth('desktop');
    assert.ok(!classes.has('open'));
    assert.equal(attributes['aria-expanded'], 'false');
    value.setPreviewMenu(true);
    assert.ok(!classes.has('open'));
    value.setPreviewWidth('tablet');
    assert.ok(classes.has('open'));
    value.setPreviewMenu(false);
    assert.ok(!classes.has('open'));
    value.destroyPreview();
});

test('reloading and leaving the editor remove old listeners and pending measurements', async () => {
    const { value, doc, preview, elements } = fixture([box(400)]);
    value.previewLoaded();
    const next = fixture([box(600)]);
    preview.contentDocument = next.doc;
    value.previewLoaded();
    assert.equal(value.previewHeight, 632);
    let measurements = 0;
    value.measurePreview = () => measurements++;
    doc.dispatchEvent(new Event('pointerover'));
    await settle();
    assert.equal(measurements, 0);
    next.doc.dispatchEvent(new Event('pointerover'));
    value.destroyPreview();
    await settle();
    next.doc.defaultView.dispatchEvent(new Event('resize'));
    next.doc.dispatchEvent(new Event('toggle'));
    elements.push(box(1000));
    await settle();
    assert.equal(measurements, 0);
});
