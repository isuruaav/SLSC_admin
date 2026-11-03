// Generate fixtures with NEWS_UPLOAD_WRITE_FIXTURES=1 and NewsImageUploadUiTest.php first.
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';
import vm from 'node:vm';

const { parseHTML } = await import(pathToFileURL(process.env.NAVIGATION_DOM_MODULE).href);
const { window } = parseHTML('<html><body></body></html>');
const wires = new Map();
for (const mode of ['create', 'edit']) {
    const fixture = parseHTML(readFileSync(`storage/framework/testing/news-upload-${mode}.html`, 'utf8')).document;
    const root = fixture.querySelector('[x-data^="awcmsNewsImageUpload"]');
    const shell = root.cloneNode(false);
    shell.setAttribute('data-mode', mode);
    shell.append(fixture.querySelector('#news-gallery-uploads'));
    shell.append(fixture.querySelector('#news-gallery-upload-error').parentElement);
    shell.append(fixture.querySelector('button[type="submit"]'));
    const upload = fixture.querySelector('[wire\\:click="uploadGalleryImages"]');
    if (upload) shell.append(upload);
    window.document.body.append(shell);
}
Object.assign(globalThis, {
    window, document: window.document, MutationObserver: window.MutationObserver,
    CustomEvent: window.CustomEvent, Element: window.Element, HTMLElement: window.HTMLElement,
    Node: window.Node, ShadowRoot: window.ShadowRoot,
});
globalThis.requestAnimationFrame = (callback) => setTimeout(callback, 0);
globalThis.getComputedStyle = () => ({ display: 'block', transitionDuration: '0s', transitionDelay: '0s', animationDuration: '0s' });
await import('../resources/js/news-image-upload.js');
globalThis.awcmsNewsImageUpload = window.awcmsNewsImageUpload;
const source = readFileSync('vendor/livewire/livewire/dist/livewire.esm.js', 'utf8').split('// node_modules/@alpinejs/collapse/dist/module.cjs.js')[0];
vm.runInThisContext(source + '\nglobalThis.uploadQaAlpine = require_module_cjs().Alpine;');
for (const mode of ['create', 'edit']) {
    wires.set(mode, uploadQaAlpine.reactive({
        galleryUploads: [],
        $interceptRequest() { return () => {}; },
        cancelUpload(property) {
            document.querySelector(`[data-mode="${mode}"] input`).dispatchEvent(new CustomEvent('livewire-upload-cancel', { bubbles: true, detail: { property } }));
        },
    }));
}
uploadQaAlpine.magic('wire', (el) => wires.get(el.closest('[data-mode]').dataset.mode));
uploadQaAlpine.start();
const tick = () => new Promise((resolve) => setTimeout(resolve, 25));
await tick();
for (const mode of ['create', 'edit']) {
    const root = document.querySelector(`[data-mode="${mode}"]`);
    const state = root._x_dataStack[0];
    const input = root.querySelector('input');
    const submit = root.querySelector('[type="submit"]');
    const upload = root.querySelector('[wire\\:click="uploadGalleryImages"]');
    const error = root.querySelector('[role="alert"]');
    const progress = root.querySelector('progress').closest('[x-show]');
    assert.equal(progress.style.display, 'none');
    if (upload) assert.ok(upload.disabled);
    let started = 0;
    input.addEventListener('change', () => { started++; });
    Object.defineProperty(input, 'files', { configurable: true, value: [{ name: 'Screenshot.png', size: 1024, type: 'image/png' }] });
    input.dispatchEvent(new window.Event('change', { bubbles: true }));
    await tick();
    assert.equal(started, 0);
    assert.notEqual(error.style.display, 'none');
    assert.match(error.textContent, /screenshot\.png/);
    Object.defineProperty(input, 'files', { configurable: true, value: [{ name: 'training.jpg', size: 1024, type: 'image/jpeg' }] });
    input.dispatchEvent(new window.Event('change', { bubbles: true }));
    const emit = (name, detail = {}) => input.dispatchEvent(new CustomEvent(`livewire-upload-${name}`, { bubbles: true, detail: { property: 'galleryUploads', ...detail } }));
    emit('start');
    emit('progress', { progress: 50 });
    await tick();
    assert.ok(input.disabled && submit.disabled);
    if (upload) assert.ok(upload.disabled);
    assert.match(progress.textContent, /50%/);
    emit('progress', { progress: 100 });
    await tick();
    assert.match(progress.textContent, /Checking images on the server/);
    wires.get(mode).galleryUploads = ['prepared.jpg'];
    emit('finish');
    await tick();
    assert.ok(!input.disabled && !submit.disabled);
    if (upload) assert.ok(!upload.disabled);
    assert.equal(progress.style.display, 'none');
    wires.get(mode).galleryUploads = [];
    emit('start');
    state.timedOut();
    await tick();
    assert.equal(progress.style.display, 'none');
    assert.match(error.textContent, /not confirmed the images/);
    assert.ok(input.disabled);
    state.destroy();
}
console.log('PASS: real Alpine bindings for create/edit upload validation, progress, buttons, completion and stalled requests.');
