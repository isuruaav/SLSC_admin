import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import { portableSitePath, resolveSitePath, resolveEditorPaths, storeEditorPaths } from '../../resources/js/site-paths.js';

const code = fs.readFileSync(path.resolve('resources/js/page-content-editor.js'), 'utf8')
    .replace(/^import .* from '\.\/site-paths\.js';\r?\n/m, '');

function setupEditor(initialContent = '', options = {}) {
    const sandbox = {
        window: {
            location: { origin: 'http://localhost' },
        },
        document: {},
        console,
        Event: class {},
        FileReader: class {},
        FormData: class {},
        fetch: async () => ({ ok: true, json: async () => ({}) }),
        portableSitePath,
        resolveSitePath,
        resolveEditorPaths,
        storeEditorPaths,
    };
    sandbox.window.window = sandbox.window;
    vm.createContext(sandbox);
    vm.runInContext(code, sandbox);

    return sandbox.window.awcmsPageContentEditor(initialContent, 'visual', options);
}

test('resolveMediaUrl correctly resolves relative media paths with basePath', () => {
    const editor = setupEditor('', { basePath: '/slsc_admin/public' });

    assert.equal(
        editor.resolveMediaUrl('/media-files/slsc.jpg'),
        '/slsc_admin/public/media-files/slsc.jpg'
    );

    assert.equal(
        editor.resolveMediaUrl('media-files/slsc.jpg'),
        '/slsc_admin/public/media-files/slsc.jpg'
    );

    assert.equal(
        editor.resolveMediaUrl('slsc.jpg'),
        '/slsc_admin/public/media-files/slsc.jpg'
    );

    assert.equal(
        editor.resolveMediaUrl('https://example.com/banner.png'),
        'https://example.com/banner.png'
    );
});

test('canonicalMediaSrc strips basePath to maintain clean database storage', () => {
    const editor = setupEditor('', { basePath: '/slsc_admin/public' });

    assert.equal(
        editor.canonicalMediaSrc('/slsc_admin/public/media-files/slsc.jpg'),
        '/media-files/slsc.jpg'
    );

    assert.equal(
        editor.canonicalMediaSrc('http://localhost/slsc_admin/public/media-files/slsc.jpg'),
        '/media-files/slsc.jpg'
    );
});

test('choosing a library image adds its existing reference without uploading it again', () => {
    const editor = setupEditor();
    assert.equal(editor.imageTab, 'library');
    let uploads = 0;
    const inserted = [];
    editor.performFileUpload = () => { uploads += 1; throw new Error('Unexpected upload'); };
    editor.insertImageElement = (...args) => inserted.push(args);
    editor.selectedLibraryImage = { id: 12, url: 'http://localhost/media-files/flag.jpg', path: '/media-files/flag.jpg', alt: 'Regimental flag' };
    editor.submitModalLibrary();
    editor.submitModalLibrary();
    assert.equal(uploads, 0);
    assert.equal(inserted.length, 2);
    assert.equal(inserted[0][0], editor.selectedLibraryImage.url);
    assert.equal(inserted[0][2], '/media-files/flag.jpg');
});

test('setImageAlign applies proper float and margin styles', () => {
    const editor = setupEditor('', { basePath: '/slsc_admin/public' });

    const mockImg = {
        style: {},
    };
    editor.selectedImage = mockImg;
    editor.syncFromVisual = () => {};
    editor.$nextTick = () => {};

    // Left alignment with text wrap
    editor.setImageAlign('left');
    assert.equal(mockImg.style.float, 'left');
    assert.equal(mockImg.style.margin, '0 1.25rem 1rem 0');
    assert.equal(mockImg.style.display, 'inline-block');

    // Right alignment with text wrap
    editor.setImageAlign('right');
    assert.equal(mockImg.style.float, 'right');
    assert.equal(mockImg.style.margin, '0 0 1rem 1.25rem');
    assert.equal(mockImg.style.display, 'inline-block');

    // Center alignment
    editor.setImageAlign('center');
    assert.equal(mockImg.style.float, 'none');
    assert.equal(mockImg.style.margin, '1.25rem auto');
    assert.equal(mockImg.style.display, 'block');
    assert.equal(mockImg.style.clear, 'both');

    // Full width
    editor.setImageAlign('full');
    assert.equal(mockImg.style.width, '100%');
    assert.equal(mockImg.style.display, 'block');
    assert.equal(mockImg.style.clear, 'both');
});

