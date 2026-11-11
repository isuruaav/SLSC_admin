import test from 'node:test';
import assert from 'node:assert/strict';
import { draftText, EditorRecovery, registerEditorDrafts } from '../../resources/js/admin-editor-drafts.js';

function storage() {
    const values = new Map();
    return { getItem: (key) => values.get(key) ?? null, setItem: (key, value) => values.set(key, value), removeItem: (key) => values.delete(key), values };
}

test('recovery only retains editable text and never restores publishing, permissions or files', () => {
    const data = { title: 'Draft', content: '<p>Hello</p>', locale: 'si', isPublished: true, pdf: 'secret', cover: {}, password: 'private', status: 'published' };
    assert.deepEqual(draftText(data), { title: 'Draft', content: '<p>Hello</p>', locale: 'si' });
    const cache = new EditorRecovery(storage(), 'user:page');
    assert.equal(cache.save(data), true);
    assert.deepEqual(cache.read().fields, draftText(data));
    cache.discard();
    assert.equal(cache.read(), null);
});

test('corrupt, expired and unavailable recovery storage do not break editing', () => {
    const store = storage();
    const cache = new EditorRecovery(store, 'draft');
    store.setItem('draft', '{invalid');
    assert.equal(cache.read(), null);
    store.setItem('draft', JSON.stringify({ fields: { title: 'Old' }, savedAt: Date.now() - 86400001 }));
    assert.equal(cache.read(), null);
    const blocked = new EditorRecovery(null, 'draft');
    assert.equal(blocked.save({ title: 'Work' }), false);
    assert.equal(blocked.read(), null);
    assert.doesNotThrow(() => blocked.discard());
});

function fixture(initialized = true, extra = {}) {
    const handlers = {};
    const root = { getAttribute: () => 'editor-id' };
    const feedback = { textContent: '' };
    const prompt = { hidden: true };
    const input = { value: 'Original', type: 'text', dataset: {}, getAttributeNames: () => ['wire:model'], getAttribute: () => 'title', closest: (selector) => selector === '[data-unsaved-form]' ? form : null };
    const form = { isConnected: true, dataset: { draftKey: 'news-edit' }, closest: () => root, getAttribute: () => 'save', querySelectorAll: () => [input], querySelector: (selector) => selector === '[data-draft-status]' ? feedback : prompt };
    const component = { el: root, reactive: { title: 'Original', ...extra } };
    component.$wire = { set: async (key, value) => { component.reactive[key] = value; }, $refresh: async () => { input.value = component.reactive.title; } };
    const store = storage();
    const doc = { body: { dataset: { adminUser: '7' } }, querySelectorAll: () => [form], addEventListener: (name, fn) => { (handlers[name] ??= []).push(fn); }, defaultView: { addEventListener(name, fn) { handlers[name] = [fn]; }, confirm: () => false } };
    const livewire = { find: () => initialized ? { __instance: component } : undefined, hook: (name, fn) => { handlers[name] = fn; }, interceptAction: (fn) => { handlers.action = fn; }, interceptMessage: (fn) => { handlers.message = fn; } };
    globalThis.location = { pathname: '/admin/news/5/edit', search: '' };
    registerEditorDrafts(doc, livewire, store);
    function edit() {
        input.value = 'Changed'; component.reactive.title = 'Changed';
        handlers.input[0]({ target: input, isTrusted: true });
    }
    function response(errors = {}) {
        let success;
        let sync;
        handlers.message({ message: { component, actions: [{ name: 'save' }] }, onSuccess: (fn) => { success = fn; } });
        success({ payload: { snapshot: { memo: { errors } }, effects: {} }, onSync: (fn) => { sync = fn; } });
        sync?.();
    }
    function navigate() {
        let prevented = false;
        handlers['livewire:navigate'][0]({ preventDefault() { prevented = true; } });
        return prevented;
    }
    function cleanup() { response(); delete globalThis.location; }
    function initialize() { initialized = true; handlers['livewire:initialized'][0](); handlers.input[0]({ target: input, isTrusted: false }); }
    async function recover() {
        const button = { disabled: false, matches: () => false, closest: () => form };
        const event = { target: { closest: () => button } };
        await handlers.click[1](event);
    }
    return { store, feedback, edit, response, navigate, cleanup, handlers, component, initialize, recover, input };
}

test('validation errors retain unsaved protection and recovery; a successful save clears both', async () => {
    const state = fixture();
    state.edit();
    await new Promise((resolve) => setTimeout(resolve, 750));
    assert.equal(state.store.values.size, 1);
    assert.equal(state.navigate(), true);
    state.response({ title: ['Required'] });
    assert.equal(state.store.values.size, 1);
    assert.equal(state.navigate(), true);
    state.response();
    assert.equal(state.store.values.size, 0);
    assert.equal(state.navigate(), false);
    assert.equal(state.feedback.textContent, 'Saved successfully.');
    state.cleanup();
});

test('saving an earlier revision cannot clear newer unsaved edits', () => {
    const state = fixture();
    state.edit();
    let success;
    state.handlers.message({ message: { component: state.component, actions: [{ name: 'save' }] }, onSuccess: (fn) => { success = fn; } });
    state.edit();
    success({ payload: { snapshot: { memo: {} }, effects: {} }, onSync: (fn) => fn() });
    assert.equal(state.navigate(), true);
    state.cleanup();
});

test('initial Livewire and editor initialization do not report unsaved changes', () => {
    const state = fixture(false);
    state.initialize();
    assert.equal(state.navigate(), false);
    assert.equal(state.store.values.size, 0);
    state.cleanup();
});

test('recovering text updates the form and keeps it unsaved until the user saves', async () => {
    const state = fixture();
    state.store.setItem('awcms-text:7:/admin/news/5/edit:news-edit', JSON.stringify({ fields: { title: 'Recovered article', isPublished: true }, savedAt: Date.now() }));
    await state.recover();
    assert.equal(state.component.reactive.title, 'Recovered article');
    assert.equal(state.input.value, 'Recovered article');
    assert.equal(state.component.reactive.isPublished, undefined);
    assert.equal(state.navigate(), true);
    assert.match(state.feedback.textContent, /Text recovered/);
    state.cleanup();
});

test('recovery keeps modal records separate when the same form opens another item', async () => {
    const state = fixture(true, { editingId: 4 });
    state.edit();
    state.component.reactive.editingId = 5;
    state.component.reactive.title = 'Another record';
    state.input.value = 'Another record';
    state.handlers.morphed();
    await state.recover();
    assert.equal(state.component.reactive.title, 'Another record');
    state.component.reactive.editingId = 4;
    state.handlers.morphed();
    await state.recover();
    assert.equal(state.component.reactive.title, 'Changed');
    state.cleanup();
});

test('settings and translated category text recover without account credentials', () => {
    assert.deepEqual(draftText({ siteName: 'Signal Corps', name_en: 'Training', name_si: 'Category translation', administrator_password: 'secret', new_password: 'secret', invitationUrl: 'private' }),
        { siteName: 'Signal Corps', name_en: 'Training', name_si: 'Category translation' });
});
