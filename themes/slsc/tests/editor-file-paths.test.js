import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { resolveEditorPaths, storeEditorPaths } from '../../../resources/js/site-paths.js';

globalThis.window = {};
await import('../../../resources/js/page-content-editor.js');
const createEditor = globalThis.window.awcmsPageContentEditor;
delete globalThis.window;

test('the editor previews local files with its base path and stores portable references', () => {
    const dom = new JSDOM('<div id="editor"><img src="/media-files/photo.jpg"><a href="/document-files/en/guide/view">Guide</a><video src="/media-files/clip.mp4" poster="/media-files/poster.jpg"><source src="/media-files/clip.webm"></video></div>');
    const root = dom.window.document.getElementById('editor');
    const options = { origin: 'https://site.test', baseUrl: 'https://site.test/cms', basePath: '/cms' };
    resolveEditorPaths(root, options);
    assert.equal(root.querySelector('img').getAttribute('src'), '/cms/media-files/photo.jpg');
    assert.equal(root.querySelector('a').getAttribute('href'), '/cms/document-files/en/guide/view');
    assert.equal(root.querySelector('video').getAttribute('poster'), '/cms/media-files/poster.jpg');
    // Repeated visual refreshes must neither duplicate the base nor undo edits.
    root.querySelector('a').setAttribute('href', '/document-files/en/updated/view');
    resolveEditorPaths(root, options);
    assert.equal(root.querySelector('img').getAttribute('src'), '/cms/media-files/photo.jpg');
    const saved = root.cloneNode(true);
    storeEditorPaths(saved, options);
    assert.equal(saved.querySelector('img').getAttribute('src'), '/media-files/photo.jpg');
    assert.equal(saved.querySelector('a').getAttribute('href'), '/document-files/en/updated/view');
    assert.equal(saved.querySelector('source').getAttribute('src'), '/media-files/clip.webm');
    assert.ok(!saved.innerHTML.includes('data-canonical-'));
    dom.window.close();
});

test('external image and video URLs survive preview and saving', () => {
    const dom = new JSDOM('<div><img src="https://cdn.test/photo.jpg"><a href="https://video.test/watch?v=1">Video</a></div>');
    const root = dom.window.document.body.firstElementChild;
    const options = { origin: 'https://site.test', basePath: '/cms' };
    resolveEditorPaths(root, options);
    storeEditorPaths(root, options);
    assert.equal(root.querySelector('img').getAttribute('src'), 'https://cdn.test/photo.jpg');
    assert.equal(root.querySelector('a').getAttribute('href'), 'https://video.test/watch?v=1');
    dom.window.close();
});

test('visual editor saving passes portable paths to the Livewire form inputs', () => {
    const dom = new JSDOM('<div id="editor"><img src="https://site.test/cms/media-files/photo.jpg"><a href="/document-files/en/guide/view">Guide</a></div><textarea></textarea><input id="content"><input id="mode">', { url: 'https://site.test/cms/admin/pages/create' });
    const previousGlobals = new Map(['window', 'document', 'Event', 'Node', 'Element'].map((key) => [key, Object.getOwnPropertyDescriptor(globalThis, key)]));
    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.Event = dom.window.Event;
    globalThis.Node = dom.window.Node;
    globalThis.Element = dom.window.Element;
    try {
        const document = dom.window.document;
        const root = document.getElementById('editor');
        const editor = createEditor(root.innerHTML, 'visual', { baseUrl: 'https://site.test/cms', basePath: '/cms' });
        editor.$refs = { visual: root, code: document.querySelector('textarea'), contentInput: document.getElementById('content'), modeInput: document.getElementById('mode') };
        editor.setupImagesInVisual();
        assert.equal(root.querySelector('img').getAttribute('src'), '/cms/media-files/photo.jpg');
        editor.syncFromVisual();
        assert.ok(editor.content.includes('src="/media-files/photo.jpg"'));
        assert.ok(editor.content.includes('href="/document-files/en/guide/view"'));
        assert.ok(!editor.content.includes('/cms/'));
        assert.ok(!editor.content.includes('data-canonical-'));
        assert.ok(!editor.content.includes('draggable'));
        assert.equal(editor.$refs.contentInput.value, editor.content);
        assert.equal(editor.$refs.modeInput.value, 'visual');
    } finally {
        for (const [key, descriptor] of previousGlobals) {
            if (descriptor) Object.defineProperty(globalThis, key, descriptor);
            else delete globalThis[key];
        }
        dom.window.close();
    }
});
