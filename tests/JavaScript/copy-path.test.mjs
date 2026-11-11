import assert from 'node:assert/strict';
import { test } from 'node:test';

globalThis.window = {};
const { copyPathToClipboard } = await import('../../resources/js/copy-path.js');

function inputFor(value = '/media-files/image.jpg') {
    return {
        value,
        focused: false,
        selected: false,
        focus() { this.focused = true; },
        select() { this.selected = true; },
    };
}

test('copies the displayed portable file path using the browser clipboard', async () => {
    const input = inputFor();
    let copied;
    const clipboard = { async writeText(value) { copied = value; } };
    assert.equal(await copyPathToClipboard(input, clipboard), true);
    assert.equal(copied, input.value);
    input.value = '/document-files/si/notice/view';
    await copyPathToClipboard(input, clipboard);
    assert.equal(copied, input.value);
});

for (const clipboard of [undefined, { async writeText() { throw new Error('denied'); } }]) {
    test(`copies by selection when clipboard is ${clipboard ? 'denied' : 'unavailable on HTTP'}`, async () => {
        const input = inputFor();
        const document = { execCommand(command) { assert.equal(command, 'copy'); return true; } };
        assert.equal(await copyPathToClipboard(input, clipboard, document), true);
        assert.equal(input.focused, true);
        assert.equal(input.selected, true);
    });
}

test('leaves the path selected and shows manual instructions when automatic copy fails', async () => {
    const control = window.awcmsCopyPath();
    control.$refs = { path: inputFor() };
    globalThis.document = { execCommand() { return false; } };
    await control.copy();
    assert.equal(control.copied, false);
    assert.match(control.message, /Ctrl\+C/);
    assert.equal(control.$refs.path.selected, true);
});
