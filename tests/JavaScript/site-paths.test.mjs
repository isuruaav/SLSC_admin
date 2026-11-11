import test from 'node:test';
import assert from 'node:assert/strict';
import { portableSitePath, resolveSitePath } from '../../resources/js/site-paths.js';

const options = { origin: 'https://site.test', baseUrl: 'https://site.test/slsc_admin/public', basePath: '/slsc_admin/public' };

test('local asset paths omit the domain and deployment folder while preserving URL suffixes', () => {
    for (const source of ['https://site.test/slsc_admin/public/media-files/photo.jpg?v=2#preview', '/slsc_admin/public/media-files/photo.jpg?v=2#preview', '/media-files/photo.jpg?v=2#preview']) {
        assert.equal(portableSitePath(source, options), '/media-files/photo.jpg?v=2#preview');
    }
});

test('editor media filenames and file paths resolve against the current installation', () => {
    for (const name of ['photo.jpg', 'guide.pdf', 'report.docx', 'clip.mp4']) {
        assert.equal(portableSitePath(name, options, true), '/media-files/' + name);
        assert.equal(resolveSitePath(name, options, true), '/slsc_admin/public/media-files/' + name);
    }
    assert.equal(resolveSitePath('document-files/en/notice/view', options), '/slsc_admin/public/document-files/en/notice/view');
    assert.equal(resolveSitePath('/pages/about', options), '/slsc_admin/public/pages/about');
});

test('existing deployment prefixes are applied only once', () => {
    const path = '/slsc_admin/public/media-files/photo.jpg';
    assert.equal(resolveSitePath(path, options), path);
    assert.equal(resolveSitePath(resolveSitePath('/media-files/photo.jpg', options), options), path);
    assert.equal(resolveSitePath('/media-files/photo.jpg', { origin: options.origin }), '/media-files/photo.jpg');
});

test('external assets and similar-looking domains retain their complete URLs', () => {
    for (const value of ['https://cdn.test/photo.jpg', '//cdn.test/photo.jpg', 'https://site.test.evil.example/photo.jpg', 'https://site.test:8443/photo.jpg', 'https://site.test/other-app/photo.jpg']) {
        assert.equal(portableSitePath(value, options), value);
        assert.equal(resolveSitePath(value, options), value);
    }
});

test('anchors, query links and contact protocols keep their meaning', () => {
    for (const value of ['#chapter', '?page=2', 'mailto:editor@example.test', 'tel:+94112345678', '../related']) {
        assert.equal(portableSitePath(value, options), value);
        assert.equal(resolveSitePath(value, options), value);
    }
});
