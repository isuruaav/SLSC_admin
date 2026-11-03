// Generate role-permissions.html with ROLE_UI_WRITE_FIXTURES=1 and RolePresetsTest.php.
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';
import vm from 'node:vm';

const { parseHTML } = await import(pathToFileURL(process.env.NAVIGATION_DOM_MODULE).href);
const { window } = parseHTML('<html><body></body></html>');
const fixture = parseHTML(readFileSync('storage/framework/testing/role-permissions.html', 'utf8')).document;
const root = fixture.querySelector('[x-data*="canLeave"]');
assert.ok(root);
window.document.body.append(root);
Object.assign(globalThis, {
    window, document: window.document, MutationObserver: window.MutationObserver,
    CustomEvent: window.CustomEvent, Element: window.Element, HTMLElement: window.HTMLElement,
    Node: window.Node, ShadowRoot: window.ShadowRoot,
});
globalThis.requestAnimationFrame = (callback) => setTimeout(callback, 0);
globalThis.getComputedStyle = () => ({ display: 'block', transitionDuration: '0s', transitionDelay: '0s', animationDuration: '0s' });
const source = readFileSync('vendor/livewire/livewire/dist/livewire.esm.js', 'utf8').split('// node_modules/@alpinejs/collapse/dist/module.cjs.js')[0];
vm.runInThisContext(source + '\nglobalThis.roleQaAlpine = require_module_cjs().Alpine;');
const permissionName = (input) => vm.runInNewContext(input.getAttribute('wire:click'), { togglePermission: (name) => name, JSON });
const selected = [...root.querySelectorAll('input[type="checkbox"][checked]')].map(permissionName);
const calls = [];
const wire = roleQaAlpine.reactive({
    selectedPermissions: selected,
    selectRole(id) { calls.push(['selectRole', id]); },
    createRole() { calls.push(['createRole']); },
    installRecommendedRoles() { calls.push(['installRecommendedRoles']); },
    togglePermission(name) { calls.push(['togglePermission', name]); },
    togglePermissionGroup(group) { calls.push(['togglePermissionGroup', group]); },
    applyTemplate() { calls.push(['applyTemplate']); },
    discardChanges() { calls.push(['discardChanges']); },
    savePermissions() { calls.push(['savePermissions']); },
});
roleQaAlpine.magic('wire', () => wire);
roleQaAlpine.start();
const tick = () => new Promise((resolve) => setTimeout(resolve, 25));
await tick();
const state = root._x_dataStack[0];
const groups = [...root.querySelectorAll('fieldset')];
assert.equal(groups.length, 19);
state.search = 'publish';
await tick();
const news = groups.find((group) => group.querySelector('legend').textContent.trim() === 'News');
const users = groups.find((group) => group.querySelector('legend').textContent.trim() === 'User accounts');
assert.notEqual(news.style.display, 'none');
assert.equal(users.style.display, 'none');
assert.equal([...news.querySelectorAll('label')].filter((label) => label.style.display !== 'none').length, 1);
state.search = 'not-a-real-module';
await tick();
assert.ok(groups.every((group) => group.style.display === 'none'));
const empty = [...root.querySelectorAll('p')].find((p) => p.textContent.includes('No access matches your search.'));
assert.notEqual(empty.style.display, 'none');
state.search = '';
state.dirty = true;
window.confirm = () => false;
root.querySelector('[wire\\:key^="role-choice-"]').click();
await tick();
assert.equal(calls.length, 0);
window.confirm = () => true;
root.querySelector('[wire\\:key^="role-choice-"]').click();
await tick();
assert.equal(calls[0][0], 'selectRole');
window.dispatchEvent(new CustomEvent('role-editor-reset'));
await tick();
assert.equal(state.dirty, false);
window.dispatchEvent(new CustomEvent('role-editor-changed'));
await tick();
assert.equal(state.dirty, true);
for (const input of root.querySelectorAll('input[type="checkbox"]')) {
    assert.equal(input.checked, wire.selectedPermissions.includes(permissionName(input)));
}
for (const button of root.querySelectorAll('[wire\\:click]')) {
    await roleQaAlpine.evaluate(button, '$wire.' + button.getAttribute('wire:click'));
}
console.log('PASS: real Alpine role search, empty results, checkbox states, unsaved-change confirmation, reset events and action expressions.');
