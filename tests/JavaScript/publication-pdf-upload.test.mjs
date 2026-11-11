import test from 'node:test';
import assert from 'node:assert/strict';
import { validatePublicationPdf, validatePublicationCover, uploadPublicationFile, preparePublicationUpload } from '../../resources/js/publication-pdf-upload.js';
import { publicationPdfAssets } from '../../resources/js/publication-pdf-assets.js';

test('PDF worker, fonts and WASM assets keep the Laravel subdirectory or CDN prefix', () => {
    assert.deepEqual(publicationPdfAssets('/build/assets/pdf.worker-123.mjs', 'http://localhost/sch_of_slsc/public/build/assets/renderer.js', '6.4.299'), {
        worker: 'http://localhost/sch_of_slsc/public/build/assets/pdf.worker-123.mjs',
        assets: 'http://localhost/sch_of_slsc/public/build/pdfjs/6.4.299/',
    });
    assert.equal(publicationPdfAssets('/build/assets/worker.mjs', 'https://cdn.example.com/school/build/assets/renderer.js', '6.4.299').worker, 'https://cdn.example.com/school/build/assets/worker.mjs');
    assert.equal(publicationPdfAssets('/node_modules/pdfjs-dist/build/pdf.worker.mjs', 'http://localhost:5173/resources/js/renderer.js', '6.4.299', true).assets, 'http://localhost:5173/node_modules/pdfjs-dist/');
});

test('publication selection accepts PDF filenames and enforces a 50 MB bound', () => {
    assert.doesNotThrow(() => validatePublicationPdf({ name: 'Journal.PDF', size: 1024 }));
    assert.doesNotThrow(() => validatePublicationPdf({ name: 'journal.pdf', size: 50 * 1024 * 1024 }));
    assert.throws(() => validatePublicationPdf({ name: 'journal.exe', size: 1024 }), /Select a PDF/);
    assert.throws(() => validatePublicationPdf({ name: 'journal.pdf', size: 0 }), /empty/);
    assert.throws(() => validatePublicationPdf({ name: 'journal.pdf', size: 50 * 1024 * 1024 + 1 }), /50 MB/);
});

test('publication cover selection accepts JPEG images up to 2 MB', () => {
    assert.doesNotThrow(() => validatePublicationCover({ name: 'cover.JPG', type: 'image/jpeg', size: 2 * 1024 * 1024 }));
    assert.throws(() => validatePublicationCover({ name: 'cover.png', type: 'image/png', size: 100 }), /JPG or JPEG/);
    assert.throws(() => validatePublicationCover({ name: 'cover.jpg', type: 'image/jpeg', size: 2 * 1024 * 1024 + 1 }), /2 MB/);
});

test('upload waits for Livewire completion rather than the return value from upload', async () => {
    let success;
    const wire = { upload(property, file, done) { assert.equal(property, 'cover'); success = done; } };
    let finished = false;
    const pending = uploadPublicationFile(wire, 'cover', {}).then(() => { finished = true; });
    await Promise.resolve();
    assert.equal(finished, false);
    success('temporary-cover.jpg');
    await pending;
    assert.equal(finished, true);
});

test('failed and cancelled uploads reject instead of allowing a save with a stale file', async () => {
    await assert.rejects(uploadPublicationFile({ upload(property, file, done, error) { error(); } }, 'pdf', {}), /failed/);
    await assert.rejects(uploadPublicationFile({ upload(property, file, done, error, progress, cancel) { cancel(); } }, 'cover', {}), /cancelled/);
});

function uploadFixture(wire, file = new File(['pdf bytes'], 'signals-journal.pdf', { type: 'application/pdf' }), type = 'pdf') {
    const status = { textContent: '' };
    const preview = { hidden: true };
    const placeholder = { hidden: false };
    const title = { value: '' };
    const events = [];
    const autoCover = { checked: false };
    const elements = { '[data-publication-auto-cover]': autoCover, '[data-publication-upload-status]': status, '[data-publication-cover-preview]': preview, '[data-publication-cover-placeholder]': placeholder, '[data-publication-title]': title };
    const form = { querySelector: (selector) => elements[selector], dispatchEvent: (event) => events.push(event.type) };
    const component = { getAttribute: () => 'publication-test' };
    const input = { files: [file], disabled: false, value: file.name, closest: (selector) => selector === '[data-publication-upload]' ? form : component };
    globalThis.window = { Livewire: { find: () => wire } };
    return { input, status, preview, placeholder, title, events, type, autoCover };
}

test('PDF upload waits for completion and asks for a manual cover image', async () => {
    const uploads = [];
    const assignments = [];
    let completePdf;
    let pdfStarted;
    const started = new Promise((resolve) => { pdfStarted = resolve; });
    const wire = {
        set: async (...args) => assignments.push(args),
        upload(property, file, success) {
            uploads.push(property);
            if (property === 'cover') success('cover-ready');
            else { completePdf = success; pdfStarted(); }
        },
    };
    const fixture = uploadFixture(wire);
    const pending = preparePublicationUpload(fixture.input, 'pdf');
    await started;
    assert.equal(fixture.input.disabled, true);
    assert.deepEqual(uploads, ['pdf']);
    assert.equal(fixture.title.value, 'signals journal');
    assert.equal(fixture.preview.hidden, true);
    await preparePublicationUpload(fixture.input, 'pdf');
    completePdf('pdf-ready');
    await pending;
    assert.equal(fixture.input.disabled, false);
    assert.match(fixture.status.textContent, /upload its first-page cover image/);
    assert.deepEqual(fixture.events, ['publication-upload-start', 'publication-upload-end']);
    assert.ok(assignments.some(([name, value]) => name === 'title' && value === 'signals journal'));
    delete globalThis.window;
});

test('cover image uploads separately and previews the selected image', async () => {
    const file = new File(['jpeg bytes'], 'cover.jpg', { type: 'image/jpeg' });
    const uploads = [];
    const wire = {
        set: async () => {},
        upload(property, uploadedFile, success) {
            uploads.push([property, uploadedFile]);
            success('cover-ready');
        },
    };
    const fixture = uploadFixture(wire, file, 'cover');
    await preparePublicationUpload(fixture.input, 'cover');
    assert.equal(uploads.length, 1);
    assert.equal(uploads[0][0], 'cover');
    assert.equal(fixture.preview.hidden, false);
    assert.equal(fixture.placeholder.hidden, true);
    assert.match(fixture.status.textContent, /PDF and cover image are ready/);
    URL.revokeObjectURL(fixture.preview.src);
    delete globalThis.window;
});

test('failed PDF upload clears only its temporary property and releases the form', async () => {
    const assignments = [];
    const fixture = uploadFixture({
        set: async (...args) => assignments.push(args),
        upload(property, file, done, error) { error(); },
    });
    await preparePublicationUpload(fixture.input, 'pdf');
    assert.equal(fixture.input.disabled, false);
    assert.equal(fixture.input.value, '');
    assert.match(fixture.status.textContent, /upload failed/);
    assert.ok(assignments.some(([name, value]) => name === 'pdf' && value === null));
    assert.deepEqual(fixture.events, ['publication-upload-start', 'publication-upload-end']);
    delete globalThis.window;
});

test('automatic cover generation uploads the PDF and generated JPEG before releasing the form', async () => {
    const uploads = [];
    const fixture = uploadFixture({ set: async () => {}, upload(property, file, success) { uploads.push(property); success('ready'); } });
    fixture.autoCover.checked = true;
    await preparePublicationUpload(fixture.input, 'pdf', async () => new File(['jpeg'], 'cover.jpg', { type: 'image/jpeg' }));
    assert.deepEqual(uploads, ['pdf', 'cover']);
    assert.equal(fixture.preview.hidden, false);
    assert.match(fixture.status.textContent, /generated cover are ready/);
    assert.equal(fixture.input.disabled, false);
    URL.revokeObjectURL(fixture.preview.src);
    delete globalThis.window;
});

test('a failed cover generator preserves the completed PDF for manual cover upload', async () => {
    const assignments = [];
    const fixture = uploadFixture({ set: async (...args) => assignments.push(args), upload(property, file, success) { success('ready'); } });
    fixture.autoCover.checked = true;
    await preparePublicationUpload(fixture.input, 'pdf', async () => { throw new Error('Unreadable PDF'); });
    assert.match(fixture.status.textContent, /Select a JPG\/JPEG cover/);
    assert.equal(assignments.filter(([key, value]) => key === 'pdf' && value === null).length, 1);
    assert.equal(fixture.input.value, 'signals-journal.pdf');
    assert.equal(fixture.input.disabled, false);
    delete globalThis.window;
});
