import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { newsImageUpload, validateNewsImages } from '../../resources/js/news-image-upload.js';

const file = (name = 'training.jpg', size = 1024, type = 'image/jpeg') => ({ name, size, type });
const uploadEvent = (property = 'galleryUploads') => ({ detail: { property } });
const selectionEvent = (files, existing = 0) => ({
    target: { files, dataset: { existingImageCount: String(existing) }, value: 'selected' },
    preventDefault() { this.prevented = true; },
    stopImmediatePropagation() { this.stopped = true; },
});

test('allows supported lowercase and Unicode filenames at the one MB boundary', () => {
    for (const [name, type] of [['training.jpg', 'image/jpeg'], ['training.jpeg', 'image/jpeg'], ['පුහුණුව.png', 'image/png'], ['பயிற்சி.webp', 'image/webp']]) {
        assert.doesNotThrow(() => validateNewsImages([file(name, 1024 * 1024, type)]));
    }
});

test('rejects the screenshot filename before any upload starts and suggests the corrected name', () => {
    const state = newsImageUpload({ galleryUploads: [] });
    const event = selectionEvent([file('Screenshot 2026-10-26 140138.png', 1024, 'image/png')]);
    state.validateSelection(event);
    assert.ok(event.prevented && event.stopped);
    assert.equal(event.target.value, '');
    assert.match(state.error, /screenshot 2026-10-26 140138\.png/);
    assert.equal(state.uploading, false);
});

test('rejects oversized, empty, unsupported and mismatched files', () => {
    for (const invalid of [file('large.jpg', 1024 * 1024 + 1), file('empty.jpg', 0), file('script.svg', 1024, 'image/svg+xml'), file('fake.png', 1024, 'application/pdf')]) {
        assert.throws(() => validateNewsImages([invalid]));
    }
});

test('counts already attached and pending images before accepting more files', () => {
    assert.doesNotThrow(() => validateNewsImages([file()], 28, 1));
    assert.throws(() => validateNewsImages([file(), file()], 28, 1), /select 1 more/);
    assert.throws(() => validateNewsImages([file()], 30), /select 0 more/);
});

test('tracks preparation separately from server confirmation and releases on success', () => {
    const wire = { galleryUploads: [] };
    const state = newsImageUpload(wire);
    state.started(uploadEvent());
    assert.equal(state.uploading, true);
    state.advanced({ target: { getAttribute: () => 'galleryUploads' }, detail: { progress: 100 } });
    assert.equal(state.progress, 100);
    assert.equal(state.uploading, true);
    wire.galleryUploads = ['one.jpg'];
    state.finished(uploadEvent());
    assert.equal(state.uploading, false);
    assert.equal(state.ready, true);
    assert.equal(state.readyCount, 1);
    assert.equal(state._slowTimer, null);
});

test('blocks both file changes and submitting the article while preparation is pending', () => {
    const state = newsImageUpload({ galleryUploads: [] });
    state.started(uploadEvent());
    try {
        const submit = selectionEvent([]);
        state.guardSubmit(submit);
        assert.ok(submit.prevented && submit.stopped);
        const selection = selectionEvent([file()]);
        state.validateSelection(selection);
        assert.ok(selection.prevented && selection.stopped);
    } finally {
        state.destroy();
    }
});

test('shows upload errors and permits another selection after ordinary upload failures', () => {
    const state = newsImageUpload({ galleryUploads: [] });
    state.started(uploadEvent());
    state.failed();
    assert.equal(state.uploading, false);
    assert.match(state.error, /could not be prepared/);
    assert.equal(state.needsRefresh, false);
    const event = selectionEvent([file()]);
    state.validateSelection(event);
    assert.equal(event.prevented, undefined);
    assert.equal(state.error, '');
});

test('handles a failed Livewire handshake without leaving an endless loading state', () => {
    let intercept;
    let removed = false;
    const state = newsImageUpload({ galleryUploads: [], $interceptRequest(callback) { intercept = callback; return () => { removed = true; }; } });
    state.init();
    state.started(uploadEvent());
    let failed;
    intercept({ onError(callback) { failed = callback; }, onFailure() {} });
    failed();
    assert.equal(state.uploading, false);
    assert.equal(state.needsRefresh, true);
    assert.match(state.error, /Save any article text, then refresh/);
    const event = selectionEvent([file()]);
    state.validateSelection(event);
    assert.ok(event.stopped);
    state.destroy();
    assert.ok(removed);
});

test('cancels active transfers without racing signed URL or finalization requests', () => {
    const cancelled = [];
    const state = newsImageUpload({ galleryUploads: [], cancelUpload(name) { cancelled.push(name); } });
    state.started(uploadEvent());
    try {
        state.cancel();
        state.progress = 55;
        state.cancel();
        state.progress = 100;
        state.cancel();
        assert.deepEqual(cancelled, ['galleryUploads']);
        state.cancelled(uploadEvent());
        assert.equal(state.uploading, false);
        assert.match(state.error, /cancelled/);
    } finally {
        state.destroy();
    }
});

test('ignores unrelated uploads', () => {
    const state = newsImageUpload({ galleryUploads: [] });
    state.started(uploadEvent('other'));
    assert.equal(state.uploading, false);
    state.finished(uploadEvent('other'));
    assert.equal(state.ready, false);
});

test('ends a stalled preparation with an explicit unconfirmed state and handles late confirmation', () => {
    const wire = { galleryUploads: [] };
    const state = newsImageUpload(wire);
    state.started(uploadEvent());
    state.timedOut();
    assert.equal(state.uploading, false);
    assert.equal(state.needsRefresh, true);
    assert.equal(state._deadlineTimer, null);
    assert.match(state.error, /not confirmed/);
    assert.equal(state.readyCount, 0);
    wire.galleryUploads = ['confirmed.jpg'];
    state.finished(uploadEvent());
    assert.equal(state.needsRefresh, false);
    assert.equal(state.error, '');
    assert.equal(state.ready, true);
});

test('initializes and reorders pending image previews with the Alpine context', async () => {
    const source = readFileSync(new URL('../../resources/views/livewire/admin/news/news-create.blade.php', import.meta.url), 'utf8');
    const expression = source.match(/x-data="(\{\s*sortable:[\s\S]*?)">/)[1];
    let options;
    let destroyed = false;
    const window = { Sortable: class { constructor(element, config) { options = config; } destroy() { destroyed = true; } } };
    const state = new Function('window', `return (${expression})`)(window);
    const reordered = [];
    state.$refs = { pendingImageList: { querySelectorAll: () => [{ dataset: { pendingImageIndex: '1' } }, { dataset: { pendingImageIndex: '0' } }] } };
    state.$wire = { async reorderPendingGalleryImages(ids) { reordered.push(ids); } };
    state.init();
    await options.onEnd();
    assert.deepEqual(reordered, [[1, 0]]);
    assert.equal(state.savingOrder, false);
    state.destroy();
    assert.equal(destroyed, true);
});
