const fields = ['full_name', 'email', 'phone', 'district', 'interest', 'message', 'consent'];
const interests = ['communications', 'technology', 'training', 'general'];
const length = text => Array.from(text).length;

export function initializeRegistration(d, w, options) {
    const form = d.getElementById('registrationForm');
    if (!form) return { refreshLanguage() {}, destroy() {} };
    const t = options.translate;
    const dialog = d.getElementById('registrationDialog');
    const success = d.getElementById('registrationSuccess');
    const alert = d.getElementById('registrationAlert');
    const submitButton = d.getElementById('registrationSubmit');
    const errors = new Map();
    const controller = new w.AbortController();
    const on = (node, event, handler, settings = {}) => node?.addEventListener(event, handler, { ...settings, signal: controller.signal });
    const input = name => form.elements.namedItem(name);
    let opener = null;
    let pending = false;
    let destroyed = false;
    let requestController = null;
    let disabledFields = [];
    form.noValidate = true;
    form.querySelectorAll('[data-error-for]').forEach(node => { if (node.dataset.errorKey) errors.set(node.dataset.errorFor, node.dataset.errorKey); });

    function setError(name, code) {
        const node = form.querySelector(`[data-error-for="${name}"]`);
        if (!node) return;
        if (code) errors.set(name, code); else errors.delete(name);
        node.dataset.errorKey = code ?? '';
        node.textContent = code ? t(code) : '';
        node.hidden = !code;
        input(name)?.setAttribute('aria-invalid', String(Boolean(code)));
    }
    function showAlert(code) {
        alert.dataset.errorKey = code ?? '';
        alert.textContent = code ? t(code) : '';
        alert.hidden = !code;
    }
    function refreshLanguage() {
        errors.forEach((code, name) => setError(name, code));
        if (alert.dataset.errorKey) showAlert(alert.dataset.errorKey);
        submitButton.querySelector('[data-registration-submit]').textContent = t(pending ? 'regSending' : 'regSubmit');
        d.getElementById('registrationMessageCount').textContent = `${length(input('message').value)} / 1000`;
    }
    function setPending(value) {
        pending = value;
        if (value) {
            disabledFields = [...form.querySelectorAll('input:not([type="hidden"]), select, textarea, button')].map(node => [node, node.disabled]);
            disabledFields.forEach(([node]) => { node.disabled = true; });
        } else {
            disabledFields.forEach(([node, disabled]) => { node.disabled = disabled; });
            disabledFields = [];
        }
        form.setAttribute('aria-busy', String(value));
        submitButton.classList.toggle('is-submitting', value);
        refreshLanguage();
    }
    function validateField(name) {
        const field = input(name);
        const value = field.value.trim();
        if (name === 'full_name' && (length(value) < 2 || length(value) > 120 || /[\r\n]/.test(value))) return 'regErrorName';
        if (name === 'email' && (value.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(value))) return 'regErrorEmail';
        if (name === 'phone' && !/^\+?[0-9]{7,15}$/.test(value.replace(/[\s().-]/g, ''))) return 'regErrorPhone';
        if (name === 'district' && (length(value) < 2 || length(value) > 80 || /[\r\n]/.test(value))) return 'regErrorDistrict';
        if (name === 'interest' && !interests.includes(value)) return 'regErrorInterest';
        if (name === 'message' && length(value) > 1000) return 'regErrorMessage';
        if (name === 'consent' && !field.checked) return 'regErrorConsent';
        return '';
    }
    function open(event) {
        if (!dialog) return; // The link remains a working standalone registration route.
        event.preventDefault();
        if (dialog.open || d.querySelector('dialog[open]')) return;
        opener = event.currentTarget;
        options.onOpen?.();
        if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', '');
        d.body.classList.add('modal-open');
        if (success.hidden) input('full_name').focus(); else success.focus();
        options.onModalChange?.();
    }
    function finishClose() {
        if (!d.querySelector('dialog[open]')) d.body.classList.remove('modal-open');
        opener?.focus({ preventScroll: true });
        options.onModalChange?.();
    }
    function close() {
        if (!dialog?.open) return;
        if (typeof dialog.close === 'function') dialog.close(); else { dialog.removeAttribute('open'); finishClose(); }
    }
    function startNew() {
        form.reset();
        const bytes = w.crypto.getRandomValues(new Uint8Array(16));
        input('submission_id').value = [...bytes].map(value => value.toString(16).padStart(2, '0')).join('');
        fields.forEach(name => setError(name, ''));
        showAlert('');
        success.hidden = true;
        form.hidden = false;
        d.getElementById('registrationReference').textContent = '';
        refreshLanguage();
        input('full_name').focus();
    }

    d.querySelectorAll('[data-open-registration]').forEach(node => on(node, 'click', open));
    d.querySelectorAll('[data-close-registration]').forEach(node => on(node, 'click', close));
    on(d.getElementById('newRegistration'), 'click', startNew);
    on(dialog, 'close', finishClose);
    on(dialog, 'cancel', event => { event.preventDefault(); close(); });
    on(dialog, 'click', event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) close();
    });
    on(dialog, 'keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key !== 'Tab') return;
        const controls = [...dialog.querySelectorAll('a[href],button,input,textarea,select')].filter(node => node.tabIndex >= 0 && !node.disabled && !node.closest('[hidden]'));
        const first = controls[0]; const last = controls.at(-1);
        if (event.shiftKey && d.activeElement === first) { event.preventDefault(); last?.focus(); }
        if (!event.shiftKey && d.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
    on(form, 'input', event => {
        const name = event.target.name;
        if (errors.has(name)) setError(name, validateField(name));
        if (name === 'message') refreshLanguage();
    });
    on(form, 'change', event => { if (errors.has(event.target.name)) setError(event.target.name, validateField(event.target.name)); });
    on(form, 'submit', async event => {
        event.preventDefault();
        if (pending) return;
        fields.forEach(name => setError(name, validateField(name)));
        if (errors.size) {
            showAlert('regFixErrors');
            input(errors.keys().next().value)?.focus();
            return;
        }
        if (!w.fetch) { form.noValidate = false; form.submit(); return; }
        showAlert('');
        const payload = new w.FormData(form);
        let focusAfterSubmit = null;
        requestController = new w.AbortController();
        setPending(true);
        try {
            const response = await w.fetch(new w.URL(form.dataset.endpoint, d.baseURI).href, {
                method: 'POST', body: payload, credentials: 'same-origin',
                headers: { Accept: 'application/json' }, signal: requestController.signal,
            });
            const result = await response.json();
            if (destroyed) return;
            if (!response.ok || !result.ok) {
                for (const [name, code] of Object.entries(result.errors ?? {})) if (fields.includes(name) && typeof code === 'string') setError(name, code);
                showAlert(typeof result.code === 'string' ? result.code : 'regSaveError');
                focusAfterSubmit = errors.size ? input(errors.keys().next().value) : alert;
                return;
            }
            if (typeof result.reference !== 'string' || !/^SLSC-\d{6}-[A-F0-9]{12}$/.test(result.reference)) throw new Error('Registration reference is missing.');
            d.getElementById('registrationReference').textContent = result.reference;
            form.hidden = true;
            success.hidden = false;
            success.focus();
        } catch (error) {
            if (!destroyed && error.name !== 'AbortError') { showAlert('regSaveError'); focusAfterSubmit = alert; }
        } finally {
            requestController = null;
            if (!destroyed) { setPending(false); focusAfterSubmit?.focus(); }
        }
    });
    refreshLanguage();
    return { refreshLanguage, destroy() { destroyed = true; controller.abort(); requestController?.abort(); } };
}
