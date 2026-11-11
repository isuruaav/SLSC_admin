import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import {
    confirmationForAction,
    createAdminConfirmationDialog,
    registerAdminConfirmations,
} from '../../resources/js/admin-confirmations.js';

// Exercise defer/fire/cancel against the installed Livewire 4 Action implementation.
const livewireSource = readFileSync(new URL('../../vendor/livewire/livewire/dist/livewire.js', import.meta.url), 'utf8');
const actionStart = livewireSource.indexOf('  var Action = class {');
const actionEnd = livewireSource.indexOf('\n  // js/request/legacy.js', actionStart);
assert.ok(actionStart >= 0 && actionEnd > actionStart);
const Action = new Function(livewireSource.slice(actionStart, actionEnd) + '\nreturn Action;')();
const interactionsStart = livewireSource.indexOf('  function coordinateNetworkInteractions(');
const interactionsEnd = livewireSource.indexOf('\n  // js/request/request.js', interactionsStart);
assert.ok(interactionsStart >= 0 && interactionsEnd > interactionsStart);
const networkCoordination = new Function('interceptAction', 'interceptPartition',
    livewireSource.slice(interactionsStart, interactionsEnd) + '\nreturn coordinateNetworkInteractions;');
const confirmStart = livewireSource.indexOf('  // js/directives/wire-confirm.js');
const confirmEnd = livewireSource.indexOf('\n  // js/directives/wire-offline.js', confirmStart);
assert.ok(confirmStart >= 0 && confirmEnd > confirmStart);
const installNativeConfirmation = new Function('directive2', 'confirm', 'prompt', livewireSource.slice(confirmStart, confirmEnd));
const flush = () => new Promise((resolve) => setImmediate(resolve));

class Element extends EventTarget {
    constructor(attributes = {}, tag = 'button') {
        super();
        this.attributes = attributes;
        this.tag = tag;
        this.isConnected = true;
        this.focused = false;
        this.textContent = '';
        this.dataset = {};
    }
    getAttributeNames() { return Object.keys(this.attributes); }
    getAttribute(name) { return this.attributes[name] ?? null; }
    matches(selector) { return this.tag === selector; }
    focus() { this.focused = true; }
    click() { this.dispatchEvent(new Event('click')); }
}

function hooks(ask, isAdmin = () => true) {
    const listeners = {};
    const livewire = {
        hook(name, callback) { listeners[name] = callback; },
        interceptAction(callback) { listeners.action = callback; },
    };
    const bindings = registerAdminConfirmations(livewire, ask, isAdmin);
    return { listeners, bindings };
}

function actionFor(name, element = new Element(), componentName = 'admin.settings.site-settings-index') {
    const action = new Action({ name: componentName, el: element, snapshot: { memo: {} } }, name, [], {}, { el: element });
    action.sent = 0;
    action._fire = () => {
        action.sent++;
        action.resolvePromise('sent');
    };
    return action;
}

function deferBehindActiveRequest(action) {
    let intercept;
    let finish;
    networkCoordination((callback) => { intercept = callback; }, () => {})({
        activeMessageMatchingScope: () => ({
            isAsync: () => false,
            actions: new Set([{ metadata: { type: 'model.live' } }]),
            addInterceptor(callback) { callback({ onFinish(handler) { finish = handler; } }); },
        }),
    });
    intercept({ action });
    return () => finish();
}

function dialogFixture() {
    const no = new Element();
    const yes = new Element();
    const title = new Element();
    const message = new Element();
    const trigger = new Element();
    const dialog = new Element({}, 'dialog');
    const controls = {
        '[data-confirm-no]': no, '[data-confirm-yes]': yes,
        '[data-confirm-title]': title, '[data-confirm-message]': message,
    };
    dialog.open = false;
    dialog.opened = 0;
    dialog.querySelector = (selector) => controls[selector];
    dialog.showModal = () => { dialog.open = true; dialog.opened++; };
    dialog.close = () => {
        dialog.open = false;
        dialog.dispatchEvent(new Event('close'));
    };
    dialog.getBoundingClientRect = () => ({ left: 100, right: 500, top: 100, bottom: 400 });
    const doc = {
        getElementById: () => dialog,
        activeElement: trigger,
        defaultView: { confirm() { throw new Error('Native confirm should not be used'); } },
    };
    return { ...controls, no, yes, title, message, trigger, dialog, controller: createAdminConfirmationDialog(doc) };
}

test('save, publish, unpublish and deletion methods have appropriate confirmation choices', () => {
    for (const method of ['saveAndPublish', 'publishArticle', 'unpublishPage', 'delete', 'forceDeleteMedia']) {
        const options = confirmationForAction(method);
        assert.ok(options?.confirmLabel.startsWith('Yes, '), method);
    }
    assert.equal(confirmationForAction('unpublishPage').confirmLabel, 'Yes, unpublish');
    assert.equal(confirmationForAction('forceDeleteArticle').danger, true);
    assert.match(confirmationForAction('forceDeleteArticle').message, /cannot be undone/);
});

test('searching, opening editors, file uploads and background polling are not confirmation actions', () => {
    for (const method of ['save', 'saveDraft', 'savePermissions', 'refreshUnreadCount', '$refresh', 'openMessage', 'edit', 'startCreate', 'create', 'search', 'uploadImages', '_finishUpload']) {
        assert.equal(confirmationForAction(method), null, method);
    }
});

test('existing wire confirmations preserve the warning and replace the native callback', async () => {
    let proceeded = 0;
    let cancelled = 0;
    let nativeCalls = 0;
    let observed;
    const element = new Element({
        'wire:click': 'forceDeleteArticle(12)',
        'wire:confirm': 'Delete this article?\\nThis action cannot be undone.',
    });
    element.__livewire_confirm = () => { nativeCalls++; };
    const { listeners } = hooks((options) => { observed = options; return false; });
    let cleanup;
    listeners['directive.init']({ el: element, directive: { value: 'confirm' }, cleanup: (callback) => { cleanup = callback; } });
    element.__livewire_confirm(() => proceeded++, () => cancelled++);
    await flush();
    assert.equal(proceeded, 0);
    assert.equal(cancelled, 1);
    assert.equal(nativeCalls, 0);
    assert.equal(observed.message, 'Delete this article?\nThis action cannot be undone.');
    assert.equal(observed.danger, true);
    cleanup();
    assert.equal(element.__livewire_confirm, undefined);
});

test('Yes executes an existing confirmed click once, while No allows a later retry', async () => {
    let proceeded = 0;
    let cancelled = 0;
    const choices = [false, true];
    const element = new Element({ 'wire:click': 'publish', 'wire:confirm': 'Publish this gallery?' });
    const { bindings } = hooks(() => choices.shift());
    bindings.enhance(element);
    element.__livewire_confirm(() => proceeded++, () => cancelled++);
    await flush();
    element.__livewire_confirm(() => proceeded++, () => cancelled++);
    await flush();
    assert.equal(proceeded, 1);
    assert.equal(cancelled, 1);
});

test('a save waits for Yes and sends exactly the original Livewire action', async () => {
    let choose;
    const { listeners } = hooks(() => new Promise((resolve) => { choose = resolve; }));
    const action = actionFor('saveAndPublish');
    listeners.action({ action });
    assert.equal(action.isDeferred(), true);
    assert.equal(action.sent, 0);
    await flush();
    choose(true);
    await flush();
    assert.equal(action.sent, 1);
    assert.equal(await action.promise, 'sent');
});

test('No cancels deferred publishing without sending it', async () => {
    const { listeners } = hooks(() => false);
    const action = actionFor('saveAndPublish');
    listeners.action({ action });
    await flush();
    assert.equal(action.sent, 0);
    assert.equal(action.isCancelled(), true);
    await assert.rejects(action.promise);
});

test('ordinary saves run without a modal and still reject duplicate submissions', async () => {
    const { listeners } = hooks(() => { throw new Error('Ordinary saves should not ask for confirmation'); });
    const first = actionFor('save');
    first._fire = () => { first.sent++; };
    listeners.action({ action: first });
    assert.equal(first.isDeferred(), false);
    const duplicate = actionFor('save');
    duplicate.component = first.component;
    listeners.action({ action: duplicate });
    assert.equal(duplicate.isCancelled(), true);
    first.resolvePromise('saved');
    await flush();
});

test('an action already gated by wire:confirm does not ask twice', () => {
    let asked = 0;
    const { listeners } = hooks(() => { asked++; return true; });
    const action = actionFor('deleteArticle', new Element({ 'wire:confirm': 'Move to Trash?' }));
    listeners.action({ action });
    assert.equal(asked, 0);
    assert.equal(action.isDeferred(), false);
});

test('typed confirmations and public components retain their existing behavior', () => {
    const typed = new Element({ 'wire:confirm.prompt': 'Delete?|DELETE' });
    const original = () => {};
    typed.__livewire_confirm = original;
    const { listeners, bindings } = hooks(() => { throw new Error('Should not ask'); });
    bindings.enhance(typed);
    assert.equal(typed.__livewire_confirm, original);
    const action = actionFor('saveAndPublish', new Element(), 'public.contact-form');
    listeners.action({ action });
    assert.equal(action.isDeferred(), false);
});

test('a removed button cannot execute an action after a stale confirmation', async () => {
    let choose;
    const { listeners } = hooks(() => new Promise((resolve) => { choose = resolve; }));
    const button = new Element();
    const action = actionFor('delete', button);
    listeners.action({ action });
    await flush();
    button.isConnected = false;
    choose(true);
    await flush();
    assert.equal(action.sent, 0);
    assert.equal(action.isCancelled(), true);
});

test('the modal defaults focus to No and renders warning text as text', async () => {
    const fixture = dialogFixture();
    const answer = fixture.controller.ask({
        title: 'Delete item',
        message: '<strong>Keep this text literal</strong>',
        confirmLabel: 'Yes, delete',
        danger: true,
    }, fixture.trigger);
    assert.equal(fixture.no.focused, true);
    assert.equal(fixture.message.textContent, '<strong>Keep this text literal</strong>');
    assert.equal(fixture.yes.textContent, 'Yes, delete');
    assert.equal(fixture.yes.dataset.tone, 'danger');
    fixture.no.click();
    assert.equal(await answer, false);
    assert.equal(fixture.trigger.focused, true);
});

test('Yes accepts one pending request and a second request cannot replace its text', async () => {
    const fixture = dialogFixture();
    const first = fixture.controller.ask(confirmationForAction('saveAndPublish'), fixture.trigger);
    const second = fixture.controller.ask(confirmationForAction('delete'), fixture.trigger);
    assert.equal(await second, false);
    assert.equal(fixture.title.textContent, 'Save and publish');
    fixture.yes.click();
    assert.equal(await first, true);
    assert.equal(fixture.dialog.opened, 1);
});

test('Escape, backdrop clicks and navigation all mean No', async () => {
    for (const dismiss of ['escape', 'backdrop', 'navigation']) {
        const fixture = dialogFixture();
        const answer = fixture.controller.ask(confirmationForAction('publish'), fixture.trigger);
        if (dismiss === 'escape') {
            const event = new Event('cancel', { cancelable: true });
            fixture.dialog.dispatchEvent(event);
            assert.equal(event.defaultPrevented, true);
        } else if (dismiss === 'backdrop') {
            const event = Object.assign(new Event('click'), { clientX: 0, clientY: 0 });
            fixture.dialog.dispatchEvent(event);
        } else {
            fixture.controller.cancel();
        }
        assert.equal(await answer, false, dismiss);
    }
});

test('a finishing Livewire request cannot send a save before Yes is selected', async () => {
    let choose;
    const { listeners } = hooks(() => new Promise((resolve) => { choose = resolve; }));
    const action = actionFor('saveAndPublish', new Element(), 'admin.news.news-edit');
    const finishRequest = deferBehindActiveRequest(action);
    listeners.action({ action });
    await flush();
    finishRequest();
    assert.equal(action.sent, 0);
    choose(true);
    await flush();
    assert.equal(action.sent, 1);
});

test('Yes preserves Livewire request ordering and a queued callback sends only once', async () => {
    const { listeners } = hooks(() => true);
    const action = actionFor('saveAndPublish', new Element(), 'admin.pages.page-edit');
    const finishRequest = deferBehindActiveRequest(action);
    listeners.action({ action });
    await flush();
    assert.equal(action.sent, 0);
    finishRequest();
    finishRequest();
    assert.equal(action.sent, 1);
});

test('No still cancels a save when the earlier Livewire request later finishes', async () => {
    const { listeners } = hooks(() => false);
    const action = actionFor('saveAndPublish');
    const finishRequest = deferBehindActiveRequest(action);
    listeners.action({ action });
    await flush();
    finishRequest();
    assert.equal(action.sent, 0);
    assert.equal(action.isCancelled(), true);
});

test('a changed wire action cannot reuse a confirmation for the previous news article', async () => {
    let choose;
    let proceeded = 0;
    let cancelled = 0;
    const button = new Element({ 'wire:click': 'deleteArticle(1)', 'wire:confirm': 'Move this draft to Trash?' });
    const { bindings } = hooks(() => new Promise((resolve) => { choose = resolve; }));
    bindings.enhance(button);
    button.__livewire_confirm(() => proceeded++, () => cancelled++);
    await flush();
    button.attributes['wire:click'] = 'deleteArticle(2)';
    choose(true);
    await flush();
    assert.equal(proceeded, 0);
    assert.equal(cancelled, 1);
});

test('a pending save is cancelled if its form action changes', async () => {
    let choose;
    const form = new Element({ 'wire:submit': 'saveAndPublish' }, 'form');
    const action = actionFor('saveAndPublish', form, 'admin.news.news-edit');
    const { listeners } = hooks(() => new Promise((resolve) => { choose = resolve; }));
    listeners.action({ action });
    await flush();
    form.attributes['wire:submit'] = 'save';
    choose(true);
    await flush();
    assert.equal(action.sent, 0);
    assert.equal(action.isCancelled(), true);
});

test('duplicate saves are blocked until the current action finishes and a later retry is allowed', async () => {
    let asked = 0;
    const { listeners } = hooks(() => { asked++; return true; });
    const first = actionFor('saveAndPublish');
    first._fire = () => { first.sent++; };
    listeners.action({ action: first });
    await flush();

    const second = actionFor('saveAndPublish');
    second.component = first.component;
    listeners.action({ action: second });
    await flush();
    assert.equal(asked, 1);
    assert.equal(second.sent, 0);
    assert.equal(second.isCancelled(), true);

    first.resolvePromise('saved');
    await flush();
    const third = actionFor('saveAndPublish');
    third.component = first.component;
    listeners.action({ action: third });
    await flush();
    assert.equal(asked, 2);
    assert.equal(third.sent, 1);
});

test('Livewire cannot resume a duplicate save that was already cancelled', async () => {
    const { listeners } = hooks(() => true);
    const first = actionFor('saveAndPublish');
    first._fire = () => { first.sent++; };
    listeners.action({ action: first });
    await flush();

    const duplicate = actionFor('saveAndPublish');
    duplicate.component = first.component;
    const finishRequest = deferBehindActiveRequest(duplicate);
    listeners.action({ action: duplicate });
    finishRequest();
    assert.equal(duplicate.isCancelled(), true);
    assert.equal(duplicate.sent, 0);
    first.resolvePromise('saved');
});

test('news and page actions replace the installed native dialog on initial render and after updates', async () => {
    for (const method of ['publishArticle', 'unpublishArticle', 'deleteArticle', 'save', 'saveAndPublish', 'publishPage', 'unpublishPage', 'deletePage']) {
        let nativeCalls = 0;
        let nativeDirective;
        let asked = 0;
        let sent = 0;
        const choices = [false, true];
        const message = `Confirm ${method}?`;
        const isForm = method === 'save';
        const element = new Element({
            [isForm ? 'wire:submit' : 'wire:click']: `${method}(3)`,
            'wire:confirm': message,
        }, isForm ? 'form' : 'button');
        installNativeConfirmation((_name, callback) => { nativeDirective = callback; }, () => { nativeCalls++; return true; }, () => {});
        const { listeners } = hooks((options) => {
            asked++;
            assert.equal(options.message, message);
            return choices.shift();
        });

        for (let render = 0; render < 2; render++) {
            nativeDirective({ el: element, directive: { expression: message, modifiers: [] } });
            let cleanup;
            listeners['directive.init']({ el: element, directive: { value: 'confirm' }, cleanup(callback) { cleanup = callback; } });
            element.__livewire_confirm(() => {
                const action = actionFor(method, element, 'admin.news.news-index');
                action._fire = () => { sent++; action.resolvePromise('sent'); };
                listeners.action({ action });
                if (!action.isDeferred() && !action.isCancelled()) action.fire();
            }, () => {});
            await flush();
            cleanup();
        }
        assert.equal(asked, 2, method);
        assert.equal(sent, 1, method);
        assert.equal(nativeCalls, 0, method);
    }
});
