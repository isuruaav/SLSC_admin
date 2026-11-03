import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeText, matchesQuery, formatMessage, activeSectionId } from '../assets/js/helpers.js';

test('search ignores casing and redundant whitespace', () => {
    assert.equal(normalizeText('  Signal   CORPS '), 'signal corps');
    assert.ok(matchesQuery('Signal Rugby Theme Song', 'RUGBY song'));
    assert.ok(matchesQuery('Signal Rugby Theme Song', 'song rugby'));
    assert.ok(!matchesQuery('Signal Rugby Theme Song', 'rugby flag'));
});

test('Sinhala search keeps vowel signs and combining characters', () => {
    const title = 'සංඥා සේවා වනිතා ශාඛාව';
    assert.ok(matchesQuery(title, 'සේවා වනිතා'));
    assert.ok(!matchesQuery(title, 'පර්යේෂණ'));
    assert.equal(normalizeText(title), title.normalize('NFKC'));
});

test('empty search matches every entry and missing text is safe', () => {
    assert.ok(matchesQuery('Code of Conduct', '   '));
    assert.ok(matchesQuery('', ''));
    assert.ok(!matchesQuery('', 'conduct'));
});

test('localized result messages replace only named values', () => {
    assert.equal(formatMessage('ප්‍රතිඵල {count}', { count: 15 }), 'ප්‍රතිඵල 15');
    assert.equal(formatMessage('{number} / {total}: {title}', { number: 2, total: 3, title: 'Leadership' }), '2 / 3: Leadership');
    assert.equal(formatMessage('{unknown}', {}), '{unknown}');
});

test('active navigation accounts for the sticky header', () => {
    const sections = [{id:'top',top:0},{id:'about',top:700},{id:'news',top:1300}];
    assert.equal(activeSectionId(sections, 0, 150), 'top');
    assert.equal(activeSectionId(sections, 600, 150), 'about');
    assert.equal(activeSectionId(sections, 1200, 150), 'news');
});
