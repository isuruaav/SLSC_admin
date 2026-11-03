export function layoutPreview(wire, method = 'previewNavigation') {
    return {
        previewError: '', previewBusy: false, previewWidth: 'desktop',
        timer: null, previewSequence: 0, alive: true,
        previewHeight: 300, previewScale: 1, previewMenuExpanded: true, previewObserver: null, stageObserver: null,
        previewCleanup: null, previewMeasureTimer: null,

        initPreview() {
            if (typeof ResizeObserver !== 'undefined' && this.$refs.previewStage) {
                this.stageObserver = new ResizeObserver(() => this.fitPreview());
                this.stageObserver.observe(this.$refs.previewStage);
            }
            this.fitPreview();
        },

        destroyPreview() {
            this.alive = false;
            clearTimeout(this.timer);
            this.previewObserver?.disconnect();
            this.stageObserver?.disconnect();
            this.previewCleanup?.();
            clearTimeout(this.previewMeasureTimer);
        },

        queuePreview() {
            clearTimeout(this.timer);
            // Invalidate responses as soon as the source changes, before the next request starts.
            this.previewSequence++;
            this.timer = setTimeout(() => this.refreshPreview(), 700);
        },

        frameWidth() { return ({ desktop: 1280, tablet: 820, mobile: 390 })[this.previewWidth] || 1280; },

        setPreviewWidth(width) {
            this.previewWidth = width;
            this.fitPreview();
        },

        fitPreview() {
            const available = this.$refs.previewStage?.clientWidth;
            if (available) this.previewScale = Math.min(1, Math.max(1, available - 24) / this.frameWidth());
        },

        previewLoaded() {
            this.previewObserver?.disconnect();
            this.previewCleanup?.();
            clearTimeout(this.previewMeasureTimer);
            const doc = this.$refs.preview?.contentDocument;
            if (!doc?.body) return;
            const listeners = [];
            const listen = (target, name, handler, capture = false) => {
                if (!target?.addEventListener) return;
                const options = { capture };
                target.addEventListener(name, handler, options);
                listeners.push(() => target.removeEventListener(name, handler, options));
            };
            this.previewCleanup = () => listeners.forEach((remove) => remove());
            listen(doc, 'click', (event) => {
                if (event.target.closest('a')) event.preventDefault();
                if (event.target.closest('#mobileToggle, [data-mobile-toggle]')) this.setPreviewMenu(!this.previewMenuExpanded);
                this.schedulePreviewMeasure();
            });
            listen(doc, 'keydown', (event) => {
                if (event.key === 'Escape') {
                    this.setPreviewMenu(false);
                    doc.querySelector('#mobileToggle, [data-mobile-toggle]')?.focus();
                }
            });
            // Absolutely positioned dropdowns do not resize the body. Reserve their
            // footprint, then remeasure after hover, disclosure and responsive changes.
            for (const name of ['pointerover', 'pointerout', 'focusin', 'focusout', 'toggle', 'transitionend', 'animationend', 'load']) {
                listen(doc, name, () => this.schedulePreviewMeasure(), true);
            }
            listen(doc.defaultView, 'resize', () => this.schedulePreviewMeasure());
            this.setPreviewMenu(this.previewMenuExpanded);
            if (typeof ResizeObserver !== 'undefined') {
                this.previewObserver = new ResizeObserver(() => this.schedulePreviewMeasure());
                this.previewObserver.observe(doc.body);
                for (const element of doc.body.querySelectorAll('*')) this.previewObserver.observe(element);
            }
            doc.fonts?.ready.then(() => {
                if (this.alive && this.$refs.preview?.contentDocument === doc) this.measurePreview();
            });
            this.fitPreview();
            this.measurePreview();
        },

        setPreviewMenu(open) {
            this.previewMenuExpanded = open;
            const doc = this.$refs.preview?.contentDocument;
            const menu = doc?.querySelector('#mobileMenu');
            const toggle = doc?.querySelector('#mobileToggle, [data-mobile-toggle]');
            menu?.classList.toggle('open', open);
            toggle?.setAttribute('aria-expanded', String(open));
            const icon = toggle?.querySelector('i');
            icon?.classList.toggle('fa-bars', !open);
            icon?.classList.toggle('fa-xmark', open);
            this.measurePreview();
        },

        schedulePreviewMeasure() {
            clearTimeout(this.previewMeasureTimer);
            this.previewMeasureTimer = setTimeout(() => {
                if (this.alive) this.measurePreview();
            }, 0);
        },

        measurePreview() {
            const doc = this.$refs.preview?.contentDocument;
            if (!doc?.body) return;
            const scrollTop = doc.defaultView?.scrollY || 0;
            let bottom = doc.body.getBoundingClientRect().height;
            for (const element of doc.body.querySelectorAll('*')) {
                const rect = element.getBoundingClientRect?.();
                // visibility:hidden menus retain a footprint, so opening them does
                // not move the editor. display:none elements have no rendered box.
                if (rect?.width > 0 && rect.height > 0) bottom = Math.max(bottom, rect.bottom + scrollTop);
            }
            this.previewHeight = Math.max(240, Math.ceil(bottom) + 32);
        },

        async refreshPreview() {
            const sequence = ++this.previewSequence;
            this.previewBusy = true;
            try {
                const preview = await wire[method](this.html, this.css);
                if (!this.alive || sequence !== this.previewSequence) return;
                if (typeof preview !== 'string' || !preview.trim()) throw new Error('Invalid preview');
                this.$refs.preview.srcdoc = preview;
                this.previewError = '';
            } catch {
                if (this.alive && sequence === this.previewSequence) this.previewError = 'Preview could not be updated. Check the HTML / CSS for unsupported content and try again.';
            } finally {
                if (this.alive && sequence === this.previewSequence) this.previewBusy = false;
            }
        },
    };
}
