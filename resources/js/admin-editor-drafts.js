const textFields = /^(title|titleEn|titleSi|name|nameEn|nameSi|name_en|name_si|description_en|description_si|summary|content|sinhalaTitle|sinhalaSummary|sinhalaContent|englishTitle|englishSubtitle|englishButtonLabel|englishButtonUrl|sinhalaSubtitle|sinhalaButtonLabel|sinhalaButtonUrl|excerpt|slug|seoTitle|seoDescription|customCss|editorMode|locale|translations|blocks|qualifications|qualificationsSi|imageCaptions|imageAltTexts|profileHtmlEn|profileHtmlSi|profileCss|profileEditorModeEn|profileEditorModeSi|layoutHtml|layoutCss|startDate|endDate|startsAt|endsAt|appointmentEn|appointmentSi|periodEn|periodSi|secondaryTenureEn|secondaryTenureSi|sourcePath|destinationUrl|url|categoryName|categoryNameSi|description|descriptionSi|caption|altText|shortName|telephone|email|location|website|notes|issueNumber|issueYear|pageTitleEn|pageTitleSi|pageKickerEn|pageKickerSi|pageSubtitleEn|pageSubtitleSi|siteName|siteNameSi|siteTagline|siteTaglineSi|address|addressSi|phonePrimary|phoneSecondary|mapUrl|commanderName|commanderNameSi|commanderTitle|commanderTitleSi|commanderMessage|commanderMessageSi|footerText|footerTextSi|maintenanceMessage|maintenanceMessageSi|socialLabel|socialUrl|sourceUrl|fax|frequency|time|timezone)$/;

export function draftText(data) {
    return Object.fromEntries(Object.entries(data).filter(([key, value]) => textFields.test(key) && (typeof value === 'string' || Array.isArray(value) || (value !== null && typeof value === 'object'))));
}

export class EditorRecovery {
    constructor(storage, key) { this.storage = storage; this.key = key; }
    read() {
        try {
            const draft = JSON.parse(this.storage.getItem(this.key));
            return draft && typeof draft.fields === 'object' && draft.fields && Date.now() - draft.savedAt < 86400000
                ? { ...draft, fields: draftText(draft.fields) } : null;
        } catch { return null; }
    }
    save(fields) {
        try {
            this.storage.setItem(this.key, JSON.stringify({ fields: draftText(fields), savedAt: Date.now() }));
            return true;
        } catch { return false; }
    }
    discard() { try { this.storage.removeItem(this.key); } catch { /* Storage may be disabled. */ } }
}

export function registerEditorDrafts(doc, livewire, storage) {
    const states = new Map();
    const componentFor = (form) => {
        const root = form.closest('[wire\\:id]');
        return root ? livewire.find(root.getAttribute('wire:id'))?.__instance : undefined;
    };
    const fieldsFor = (state) => {
        const fields = draftText(componentFor(state.form)?.reactive || {});
        state.form.querySelectorAll('input,select,textarea,trix-editor').forEach((input) => {
            const attribute = input.getAttributeNames().find((name) => /^wire:model(?:\.|$)/.test(name));
            const key = attribute ? input.getAttribute(attribute) : input.dataset.draftModel;
            if (key && textFields.test(key) && typeof input.value === 'string') fields[key] = input.value;
        });
        return fields;
    };
    const status = (state, text) => { const el = state.form.querySelector('[data-draft-status]'); if (el) el.textContent = text; };
    const active = () => [...states.values()].filter((state) => state.form.isConnected && state.dirty);

    function ensureFeedback(form) {
        if (form.querySelector('[data-draft-feedback]')) return;
        const feedback = doc.createElement('div');
        feedback.dataset.draftFeedback = '';
        feedback.setAttribute('wire:ignore', '');
        feedback.className = 'mb-4 rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-600';
        feedback.innerHTML = '<p data-draft-status role="status" aria-live="polite">Text recovery is available in this tab. Uploaded files must be selected again after a reload.</p><div data-draft-recovery hidden class="mt-2 flex flex-wrap items-center gap-3"><span>A previous text draft is available.</span><button type="button" data-draft-restore class="font-semibold text-emerald-800 underline">Recover Text</button><button type="button" data-draft-discard class="font-semibold text-zinc-600 underline">Discard Recovery Copy</button></div>';
        const feedbackContainer = form.querySelector('[data-draft-feedback-container]');
        if (feedbackContainer) feedbackContainer.prepend(feedback);
        else form.prepend(feedback);
    }

    function actionsFor(form) {
        return (form.dataset.saveActions || form.getAttribute('wire:submit') || 'save').split(',').map((action) => action.trim().match(/^\w+/)?.[0]);
    }

    function contextFor(form) {
        const data = componentFor(form)?.reactive || {};
        const context = ['editingId', 'editingCategoryId', 'selectedLeaderId', 'selectedMessageId', 'galleryId', 'locale', 'region', 'activeLocale']
            .filter((key) => Object.hasOwn(data, key)).map((key) => `${key}=${data[key] ?? 'new'}`);
        return [form.dataset.draftKey, ...context].join(':');
    }

    function scan() {
        for (const [form, state] of states) {
            if (!form.isConnected) { clearTimeout(state.timer); states.delete(form); }
        }
        doc.querySelectorAll('[data-unsaved-form], form[wire\\:submit]').forEach((form, index) => {
            const action = (form.getAttribute('wire:submit') || '').match(/^\s*(\w+)/)?.[1] || '';
            if (!form.hasAttribute?.('data-unsaved-form') && !/^(save|update|upload)/i.test(action)) return;
            form.dataset.draftKey ||= `${componentFor(form)?.name || 'editor'}:${action}:${index}`;
            form.setAttribute?.('data-unsaved-form', '');
            const context = contextFor(form);
            const previous = states.get(form);
            if (previous?.context === context) { ensureFeedback(form); return; }
            if (previous) {
                clearTimeout(previous.timer);
                if (previous.dirty && previous.latestFields) previous.recovery.save(previous.latestFields);
            }
            if (!componentFor(form)) return;
            const key = `awcms-text:${doc.body.dataset.adminUser}:${location.pathname}${location.search}:${context}`;
            const state = { form, context, recovery: new EditorRecovery(storage, key), dirty: false, revision: 0, timer: null };
            state.baseline = JSON.stringify(draftText(componentFor(form).reactive));
            states.set(form, state);
            ensureFeedback(form);
            const cached = state.recovery.read();
            const prompt = form.querySelector('[data-draft-recovery]');
            if (prompt) prompt.hidden = !cached || JSON.stringify(cached.fields) === state.baseline;
        });
    }

    function changed(event) {
        const form = event.target.closest('[data-unsaved-form]');
        const state = states.get(form);
        if (!state || event.target.closest('[data-draft-feedback]')) return;
        const fields = fieldsFor(state);
        const attribute = event.target.getAttributeNames().find((name) => /^wire:model(?:\.|$)/.test(name));
        const model = attribute ? event.target.getAttribute(attribute) : event.target.dataset.draftModel;
        if (['search', 'statusFilter', 'sortField', 'sortDirection', 'perPage', 'categoryFilter', 'languageFilter', 'selectedLeaderId'].includes(model)) return;
        if (model && textFields.test(model) && typeof event.target.value === 'string') fields[model] = event.target.value;
        if (JSON.stringify(fields) === state.baseline && !event.isTrusted && event.target.type !== 'file') return;
        state.dirty = true;
        state.revision++;
        state.latestFields = JSON.parse(JSON.stringify(fields));
        status(state, 'Unsaved changes. Saving a text recovery copy…');
        clearTimeout(state.timer);
        state.timer = setTimeout(() => {
            const saved = state.recovery.save({ ...fields, ...fieldsFor(state) });
            status(state, saved ? `Text recovery copy saved in this tab at ${new Date().toLocaleTimeString()}. Save the form to update the website.` : 'Unsaved changes. Recovery storage is unavailable; save the form before leaving.');
        }, 700);
    }

    function allowLeaving(items = active()) {
        if (!items.length) return true;
        if (!doc.defaultView.confirm('You have unsaved changes. Leave this editor? Uploaded files will need to be selected again.')) return false;
        items.forEach((state) => {
            clearTimeout(state.timer);
            state.recovery.save(fieldsFor(state));
            state.dirty = false;
        });
        return true;
    }

    doc.addEventListener('change', (event) => {
        const attribute = event.target.getAttributeNames?.().find((name) => /^wire:model(?:\.|$)/.test(name));
        const property = attribute && event.target.getAttribute(attribute);
        if (property !== 'selectedLeaderId') return;
        const componentRoot = event.target.closest('[wire\\:id]');
        const items = active().filter((state) => state.form.closest('[wire\\:id]') === componentRoot);
        if (!allowLeaving(items)) {
            const wire = componentRoot && livewire.find(componentRoot.getAttribute('wire:id'));
            event.target.value = wire?.__instance?.reactive.selectedLeaderId || '';
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);
    doc.addEventListener('input', changed);
    doc.addEventListener('change', changed);
    doc.addEventListener('trix-change', changed);
    doc.defaultView.addEventListener('beforeunload', (event) => {
        if (!active().length) return;
        active().forEach((state) => state.recovery.save(fieldsFor(state)));
        event.preventDefault();
        event.returnValue = '';
    });
    doc.addEventListener('livewire:navigate', (event) => { if (!allowLeaving()) event.preventDefault(); });
    doc.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank' || link.hasAttribute('download')) return;
        const url = new URL(link.href, location.href);
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
        if (!allowLeaving()) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);
    doc.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-draft-restore], [data-draft-discard]');
        if (!button) return;
        const state = states.get(button.closest('[data-unsaved-form]'));
        if (!state) return;
        const panel = state.form.querySelector('[data-draft-recovery]');
        if (button.matches('[data-draft-discard]')) { state.recovery.discard(); panel.hidden = true; return; }
        const draft = state.recovery.read();
        if (!draft) return;
        const wire = componentFor(state.form)?.$wire;
        if (!wire) return;
        state.dirty = true;
        state.revision++;
        button.disabled = true;
        try {
            for (const [key, value] of Object.entries(draft.fields)) {
                if (Object.hasOwn(fieldsFor(state), key)) await wire.set(key, value, false);
            }
            await wire.$refresh();
            state.form.querySelectorAll('[data-draft-model]').forEach((editor) => {
                const html = draft.fields[editor.dataset.draftModel];
                if (typeof html !== 'string') return;
                if (editor.editor) editor.editor.loadHTML(html);
                else {
                    const data = doc.defaultView.Alpine?.$data(editor);
                    if (data?.syncLivewireInputs) {
                        data.content = html;
                        data.$refs.visual.innerHTML = html;
                        data.$refs.code.value = data.prettyFormatHtml(html);
                        data.syncLivewireInputs();
                    }
                }
            });
            panel.hidden = true;
            status(state, 'Text recovered. Check the content and reselect any files, then save the form.');
        } catch { status(state, 'Recovery could not finish. Your recovery copy is still available; try again.'); }
        finally { button.disabled = false; }
    });
    livewire.interceptAction(({ action }) => {
        if (!['cancel', 'create', 'edit', 'startCreate', 'startEdit', 'toggleHeaderSettings', 'closeEditor', 'closeForm', 'cancelEdit', 'closeCategoryForm', 'selectRegion', 'selectLocale'].includes(action.name)) return;
        const items = active().filter((state) => state.form.closest('[wire\\:id]') === action.component.el);
        if (!allowLeaving(items)) action.cancel();
    });
    livewire.interceptMessage(({ message, onSuccess }) => {
        const pending = [...states.values()].filter((state) => state.form.closest('[wire\\:id]') === message.component.el && [...message.actions].some((action) => actionsFor(state.form).includes(action.name) || (action.name === 'saveAndPublish' && actionsFor(state.form).includes('save')))).map((state) => [state, state.revision]);
        if (!pending.length) return;
        onSuccess(({ payload, onSync }) => {
            if (Object.keys(payload.snapshot.memo.errors || {}).length || Object.values(payload.effects.returnsMeta || {}).some((meta) => meta?.errors || meta?.status)) return;
            onSync(() => pending.forEach(([state, revision]) => {
                if (state.revision !== revision) return;
                if ([...message.actions].some((action) => ['uploadBatch', 'retryBatch', 'uploadImages', 'retryImageUpload'].includes(action.name)) && (Object.keys(message.component.reactive.batchFiles || {}).length || Object.keys(message.component.reactive.uploads || {}).length)) return;
                clearTimeout(state.timer);
                state.recovery.discard();
                state.dirty = false;
                state.baseline = JSON.stringify(draftText(message.component.reactive || {}));
                status(state, 'Saved successfully.');
            }));
        });
    });
    const scheduleScan = () => doc.defaultView.Alpine?.nextTick ? doc.defaultView.Alpine.nextTick(scan) : scan();
    livewire.hook('morphed', scheduleScan);
    doc.addEventListener('livewire:initialized', scheduleScan);
    doc.addEventListener('livewire:navigated', scheduleScan);
    scheduleScan();
}

if (typeof document !== 'undefined') {
    let registered;
    const start = () => {
        if (!document.body?.dataset.adminUser || !window.Livewire || registered === window.Livewire) return;
        registered = window.Livewire;
        let storage;
        try { storage = window.sessionStorage; } catch { storage = null; }
        registerEditorDrafts(document, registered, storage);
    };
    document.addEventListener('livewire:init', start);
    document.addEventListener('livewire:navigated', start);
    document.addEventListener('DOMContentLoaded', start);
    start();
}
