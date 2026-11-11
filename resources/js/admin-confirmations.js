export function confirmationForAction(name = '') {
    const option = (title, message, confirmLabel, danger = false) => ({
        title, message, confirmLabel, danger,
    });

    if (name === 'saveAndPublish') {
        return option('Save and publish', 'Save these changes and publish this item?', 'Yes, publish');
    }
    if (/^unpublish(?:[A-Z]|$)/.test(name)) {
        return option('Unpublish item', 'Unpublish this item and return it to Draft?', 'Yes, unpublish');
    }
    if (/^publish(?:[A-Z]|$)/.test(name)) {
        return option('Publish item', 'Publish this item now?', 'Yes, publish');
    }
    if (/^forceDelete(?:[A-Z]|$)/.test(name)) {
        return option('Delete permanently', 'Permanently delete this item? This action cannot be undone.', 'Yes, delete', true);
    }
    if (/^(?:delete|trash)(?:[A-Z]|$)/.test(name)) {
        return option('Delete item', 'Are you sure you want to delete this item?', 'Yes, delete', true);
    }
    if (/^remove(?:[A-Z]|$)/.test(name)) {
        return option('Remove item', 'Are you sure you want to remove this item?', 'Yes, remove', true);
    }
    if (/^restore(?:[A-Z]|$)/.test(name)) {
        return option('Restore item', 'Are you sure you want to restore this item?', 'Yes, restore');
    }
    if (name === 'replaceFile') {
        return option('Replace file', 'Replace the current file with the selected file?', 'Yes, replace', true);
    }
    if (name === 'toggleActive') {
        return option('Change status', 'Change the active status of this item?', 'Yes, change');
    }
    if (name === 'resolve') {
        return option('Resolve message', 'Mark this contact message as resolved?', 'Yes, resolve');
    }
    if (name === 'addSocialLink' || name === 'createRole') {
        return option('Confirm changes', 'Save this new item?', 'Yes, save');
    }

    return null;
}

function wireConfirmation(element) {
    const attribute = element?.getAttributeNames().find((name) =>
        name === 'wire:confirm' || name.startsWith('wire:confirm.'));

    return attribute ? {
        prompt: attribute.split('.').includes('prompt'),
        message: element.getAttribute(attribute).replaceAll('\\n', '\n').trim() || 'Are you sure?',
    } : null;
}

function elementExpression(element) {
    const attribute = element?.getAttributeNames().find((name) =>
        /^wire:(?:click|submit)(?:\.|$)/.test(name));

    return (attribute ? element.getAttribute(attribute) : '') || '';
}

function elementAction(element) {
    return elementExpression(element).match(/^\s*(?:\$wire\.)?(\w+)/)?.[1] || '';
}

export function registerAdminConfirmations(livewire, ask, isAdmin) {
    const activeActions = new WeakMap();

    function reserve(action) {
        let actions = activeActions.get(action.component);
        if (!actions) activeActions.set(action.component, actions = new Map());
        const key = JSON.stringify([action.name, action.params]);
        action.promise.catch(() => {});
        if (actions.has(key)) {
            action.fire = () => {};
            action.cancel();
            return false;
        }
        actions.set(key, action);
        const release = () => {
            if (actions.get(key) === action) actions.delete(key);
        };
        action.promise.then(release, release);
        return true;
    }

    function enhance(element, cleanup) {
        const existing = wireConfirmation(element);
        if (!isAdmin() || !existing || existing.prompt || element.__livewire_confirm?.awcmsConfirmation) return;

        let pending = false;
        // Livewire 4's wire:confirm callback delays the original click/submit action.
        const handler = (proceed, cancel) => {
            const confirmation = wireConfirmation(element);
            if (pending || !isAdmin() || !element.isConnected || !confirmation || confirmation.prompt) {
                cancel();
                return;
            }
            pending = true;
            const expression = elementExpression(element);
            const isCurrent = () => isAdmin() && element.isConnected
                && elementExpression(element) === expression
                && wireConfirmation(element)?.message === confirmation.message;
            const options = confirmationForAction(elementAction(element)) || {
                title: 'Confirm action', confirmLabel: 'Yes, continue', danger: false,
            };

            Promise.resolve().then(() => isCurrent() && ask({
                ...options,
                message: confirmation.message,
            }, element)).then((accepted) => {
                pending = false;
                if (accepted && isCurrent()) proceed();
                else cancel();
            }, () => {
                pending = false;
                cancel();
            });
        };
        handler.awcmsConfirmation = true;
        element.__livewire_confirm = handler;
        cleanup?.(() => {
            if (element.__livewire_confirm === handler) delete element.__livewire_confirm;
        });
    }

    livewire.hook('directive.init', ({ el, directive, cleanup }) => {
        if (directive.value === 'confirm') enhance(el, cleanup);
    });

    livewire.interceptAction(({ action }) => {
        if (!isAdmin() || !action.component.name.startsWith('admin.') || action.isCancelled()) return;
        const options = confirmationForAction(action.name);
        if (!options) {
            if (/^save(?:[A-Z]|$)/.test(action.name)) reserve(action);
            return;
        }
        if (!reserve(action)) return;
        // An existing wire:confirm has already asked before constructing this action.
        if (wireConfirmation(action.origin?.el)) return;

        const buttonConfirmation = wireConfirmation(action.origin?.targetEl);
        if (buttonConfirmation?.prompt) return;
        if (buttonConfirmation) options.message = buttonConfirmation.message;
        const origin = action.origin?.targetEl || action.origin?.el || action.component.el;
        const source = action.origin?.el || action.component.el;
        const expression = elementExpression(source);
        const isCurrent = () => isAdmin() && origin?.isConnected
            && elementExpression(source) === expression;

        // Livewire may already be waiting for an earlier request. Its onFinish
        // callback must not bypass the user's decision or send a cancelled action.
        let ready = !action.isDeferred();
        let accepted = false;
        let fired = false;
        const fire = action.fire.bind(action);
        const fireWhenReady = () => {
            if (!ready || !accepted || fired || action.isCancelled()) return;
            if (!isCurrent()) {
                action.cancel();
                return;
            }
            fired = true;
            fire();
        };
        action.fire = () => {
            ready = true;
            fireWhenReady();
        };

        action.defer();
        Promise.resolve().then(() => !action.isCancelled() && isCurrent() && ask(options, origin)).then((answer) => {
            if (answer && isCurrent() && !action.isCancelled()) {
                accepted = true;
                fireWhenReady();
            } else action.cancel();
        }, () => action.cancel());
    });

    return { enhance };
}

export function createAdminConfirmationDialog(doc) {
    let dialog = null;
    let listeners = null;
    let pending = null;

    function finish(accepted) {
        const request = pending;
        pending = null;
        if (dialog?.open) dialog.close();
        if (!request) return;
        request.resolve(accepted);
        if (request.focusTarget?.isConnected) request.focusTarget.focus({ preventScroll: true });
    }

    function mount() {
        const current = doc.getElementById('admin-confirmation-dialog');
        if (current === dialog) return dialog;
        finish(false);
        listeners?.abort();
        dialog = current;
        if (!dialog) return null;
        listeners = new AbortController();
        const events = { signal: listeners.signal };
        dialog.querySelector('[data-confirm-no]').addEventListener('click', () => finish(false), events);
        dialog.querySelector('[data-confirm-yes]').addEventListener('click', () => finish(true), events);
        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            finish(false);
        }, events);
        dialog.addEventListener('close', () => {
            if (!dialog.open) finish(false);
        }, events);
        dialog.addEventListener('click', (event) => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right
                || event.clientY < bounds.top || event.clientY > bounds.bottom) finish(false);
        }, events);

        return dialog;
    }

    return {
        cancel: () => finish(false),
        ask(options, origin) {
            const element = mount();
            if (pending) return Promise.resolve(false);
            if (!element || typeof element.showModal !== 'function') {
                return Promise.resolve(doc.defaultView.confirm(options.message));
            }
            element.querySelector('[data-confirm-title]').textContent = options.title;
            element.querySelector('[data-confirm-message]').textContent = options.message;
            const yes = element.querySelector('[data-confirm-yes]');
            yes.textContent = options.confirmLabel;
            yes.dataset.tone = options.danger ? 'danger' : 'primary';

            return new Promise((resolve) => {
                pending = {
                    resolve,
                    focusTarget: origin?.matches('form') ? doc.activeElement : origin || doc.activeElement,
                };
                try {
                    element.showModal();
                    element.querySelector('[data-confirm-no]').focus();
                } catch {
                    finish(false);
                }
            });
        },
    };
}

if (typeof document !== 'undefined') {
    const dialog = createAdminConfirmationDialog(document);
    const isAdmin = () => Boolean(document.getElementById('admin-confirmation-dialog'));
    let registered = null;
    let handlers = null;

    const start = () => {
        if (!isAdmin() || !window.Livewire) return;
        if (registered !== window.Livewire) {
            handlers = registerAdminConfirmations(window.Livewire, dialog.ask, isAdmin);
            registered = window.Livewire;
        }
        // Covers modules loaded after Livewire has already initialized the page.
        document.querySelectorAll('[wire\\:id], [wire\\:id] *').forEach((element) => handlers.enhance(element));
    };

    document.addEventListener('livewire:init', start);
    document.addEventListener('livewire:navigated', start);
    document.addEventListener('livewire:navigating', dialog.cancel);
    document.addEventListener('DOMContentLoaded', start);
    start();
}
