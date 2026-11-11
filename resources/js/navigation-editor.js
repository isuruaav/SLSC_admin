import { layoutPreview } from './theme-layout-preview.js';
import { headerEditor } from './navigation-header.js';

const parse = (html) => new DOMParser().parseFromString(html, 'text/html');
const cleanLabel = (link) => {
    const copy = link.cloneNode(true);
    copy.querySelectorAll('i, svg, .aw-nav-icon').forEach((icon) => icon.remove());
    return copy.textContent.replace(/\s+/g, ' ').trim();
};

const iconElementFromLink = (link) => [...link.querySelectorAll('i, span')].find((element) =>
        element.classList.contains('aw-nav-icon') || (
            /(?:^|\s)(?:fa-solid|fa-regular|fa-brands|fas|far|fab)(?:\s|$)/.test(element.className)
            && !element.matches('.fa-chevron-down, .fa-caret-down, .fa-angle-down, .fa-arrow-right, .aw-nav-indicator')));
const iconFromLink = (link) => {
    const icon = iconElementFromLink(link);
    if (!icon) return '';
    return [...icon.classList].filter((name) => /^(?:fa-[a-z0-9-]+|fas|far|fab)$/.test(name)).join(' ');
};

export function validNavigationHref(value) {
    return typeof value === 'string' && !/[\u0000-\u0020\\]/.test(value)
        && (/^\[\[site_url:\/[A-Za-z0-9._~\/%-]*\]\]$/i.test(value)
            || /^(?:https?:\/\/|mailto:|tel:|\/(?!\/)|#)/i.test(value));
}

window.awcmsNavigationEditor = function (wire, config) {
    return {
        ...layoutPreview(wire),
        ...headerEditor(config, validNavigationHref),
        html: '', css: '', mode: 'visual', items: [], selectedId: '', navIndex: 0,
        problem: '', error: '',
        history: [], historyIndex: -1, sort: null, desktopHrefs: [],
        iconSearch: '', iconPickerOpen: false,
        style: { color: '#162052', background: '#ffffff', accent: '#047857', size: 14, gap: 20 },

        init() {
            this.html = wire.layoutHtml || '';
            this.css = wire.layoutCss || '';
            if (!this.html.trim()) {
                this.html = config.defaultHtml;
                this.css = config.baseCss;
                this.sync();
            }
            this.read();
            this.remember();
            this.queuePreview();
            this.$watch('$wire.layoutHtml', (value) => {
                if (value === this.html) return;
                this.html = value || '';
                this.read();
                this.queuePreview();
            });
            this.$watch('$wire.layoutCss', (value) => {
                if (value === this.css) return;
                this.css = value || '';
                this.readHeader();
                this.readStyle();
                this.queuePreview();
            });
            this.$watch('selectedId', () => { this.iconPickerOpen = false; this.iconSearch = ''; this.error = ''; });
            this.$nextTick(() => {
                this.initPreview();
                if (!window.Sortable) return;
                this.sort = new window.Sortable(this.$refs.menuList, {
                    animation: 150, handle: '.navigation-drag', draggable: '[data-menu-id]',
                    onEnd: (event) => {
                        const ids = [...event.from.children].map((row) => row.dataset.menuId);
                        const targetId = ids[event.newIndex + (event.newIndex < event.oldIndex ? 1 : -1)];
                        const reference = event.from.children[event.oldIndex + (event.newIndex < event.oldIndex ? 1 : 0)];
                        event.from.insertBefore(event.item, reference || null);
                        if (targetId) this.reorder(event.item.dataset.menuId, targetId, event.newIndex > event.oldIndex);
                    },
                });
            });
        },

        destroy() {
            this.destroyPreview();
            this.sort?.destroy();
        },

        expandedDocument() {
            return parse(this.html.replace(/\[\[(?:primary_menu|responsive_primary_navigation)\]\]/gi, config.siteMenuHtml));
        },

        navigation(doc) {
            const navs = [...doc.querySelectorAll('nav')].filter((nav) => !nav.closest('#mobileMenu') && !nav.parentElement?.closest('nav'));
            return navs[this.navIndex] || null;
        },

        mobileNavigation(doc) {
            return doc.querySelector('#mobileMenu .aw-mobile-navigation')
                || doc.querySelector('#mobileMenu .sos-mobile-navigation')
                || doc.querySelector('#mobileMenu .container')
                || doc.querySelector('#mobileMenu');
        },

        navigationChoices() {
            return [...this.expandedDocument().querySelectorAll('nav')]
                .filter((nav) => !nav.closest('#mobileMenu') && !nav.parentElement?.closest('nav'))
                .map((nav, index) => ({ index, label: nav.getAttribute('aria-label') || nav.id || `Navigation ${index + 1}` }));
        },

        read() {
            this.problem = '';
            this.error = '';
            const doc = this.expandedDocument();
            this.readHeader(doc);
            const nav = this.navigation(doc);
            this.items = [];
            let sequence = 0;
            if (!nav) {
                this.problem = 'No navigation area was found. Add a default navbar below, or wrap your menu links in a <nav> element in HTML / CSS.';
                return;
            }
            const walk = (container, parentId = '', depth = 0) => {
                if (depth > 10) throw new Error('This menu has too many nested levels for the visual editor. Your HTML is preserved.');
                for (const child of container.children) {
                    if (child.matches('ul, ol, nav.aw-navigation')) { walk(child, parentId, depth); continue; }
                    if (child.matches('a')) {
                        this.items.push({ id: `item-${++sequence}`, parentId, label: cleanLabel(child), href: child.getAttribute('href') || '#', icon: iconFromLink(child), linkHtml: child.outerHTML });
                        continue;
                    }
                    const link = [...child.children].find((element) => element.matches('a'));
                    const submenu = [...child.children].find((element) => element.matches('ul, ol, .dropdown, .cms-primary-submenu, .aw-nav-submenu'));
                    if (link && (submenu || child.matches('li'))) {
                        const id = `item-${++sequence}`;
                        this.items.push({ id, parentId, label: cleanLabel(link), href: link.getAttribute('href') || '#', icon: iconFromLink(link), linkHtml: link.outerHTML });
                        if (submenu) walk(submenu, id, depth + 1);
                    } else {
                        throw new Error('This navigation contains a custom layout. Keep editing it in HTML / CSS, or select a navigation made from links and dropdowns. Your code has been preserved.');
                    }
                }
            };
            try { walk(nav); } catch (error) { this.problem = error.message; this.items = []; }
            this.desktopHrefs = [...nav.querySelectorAll('a')].map((link) => link.getAttribute('href'));
            if (!this.items.some((item) => item.id === this.selectedId)) this.selectedId = this.items[0]?.id || '';
            this.readStyle();
        },

        rows(parentId = '', depth = 0) {
            if (depth > 10) return [];
            return this.items.filter((item) => item.parentId === parentId)
                .flatMap((item) => [{ ...item, depth }, ...this.rows(item.id, depth + 1)]);
        },

        selected() { return this.items.find((item) => item.id === this.selectedId); },

        displayHref(item = this.selected()) {
            return (item?.href || '').replace(/^\[\[site_url:(\/[^\]]*)\]\]$/i, '$1');
        },

        filteredIcons() {
            const query = this.iconSearch.trim().toLowerCase();
            return (config.icons || []).filter((icon) => `${icon.label} ${icon.class}`.toLowerCase().includes(query));
        },

        iconName() {
            const icon = this.selected()?.icon;
            return (config.icons || []).find((option) => option.class === icon)?.label || (icon ? 'Custom icon' : 'No icon');
        },

        chooseIcon(icon = '') {
            const item = this.selected();
            if (!item || (icon && !(config.icons || []).some((option) => option.class === icon))) return;
            item.icon = icon;
            this.iconPickerOpen = false;
            this.iconSearch = '';
            this.write();
            this.$nextTick(() => this.$refs.iconPickerToggle?.focus());
        },

        descendants(id) {
            const found = new Set([id]);
            for (let count = 0; count < this.items.length; count++) {
                this.items.forEach((item) => { if (found.has(item.parentId)) found.add(item.id); });
            }
            return found;
        },

        parentChoices() {
            const excluded = this.descendants(this.selectedId);
            return this.rows().filter((item) => !excluded.has(item.id) && item.depth < 5);
        },

        add(parentId = '') {
            const id = `new-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
            this.items.push({ id, parentId, label: 'New link', href: '#', icon: '', linkHtml: '<a href="#">New link</a>' });
            this.selectedId = id;
            this.write();
            this.$nextTick(() => { this.$refs.linkLabel?.focus(); this.$refs.linkLabel?.select?.(); });
        },

        update(field, value) {
            const item = this.selected();
            if (!item) return;
            if (field === 'href') {
                value = value.trim();
                if (!validNavigationHref(value)) { this.error = 'Use a local path such as /courses, a #anchor, or an https://, mailto: or tel: link.'; return false; }
                if (/^\/[A-Za-z0-9._~\/%-]*$/.test(value)) value = `[[site_url:${value}]]`;
            }
            if (field === 'label') {
                value = value.trim();
                if (!value) { this.error = 'Enter a name for this menu link.'; return false; }
            }
            if (field === 'parentId' && this.descendants(item.id).has(value)) return;
            item[field] = value;
            this.error = '';
            this.write();
            return true;
        },

        useLink(index) {
            if (index === '') return;
            const option = [...config.links, ...config.pages][Number(index)];
            const item = this.selected();
            if (!option || !item) return;
            item.label = option.label;
            item.href = option.href;
            item.icon = option.icon || item.icon;
            this.write();
        },

        remove() {
            const item = this.selected();
            if (!item || !window.confirm(`Remove "${item.label}" and its submenus from this draft?`)) return;
            const removed = this.descendants(item.id);
            this.items = this.items.filter((candidate) => !removed.has(candidate.id));
            this.selectedId = this.items[0]?.id || '';
            this.write();
        },

        move(id, direction) {
            const item = this.items.find((candidate) => candidate.id === id);
            const siblings = this.items.filter((candidate) => candidate.parentId === item?.parentId);
            const target = siblings[siblings.indexOf(item) + direction];
            if (!target) return;
            const currentIndex = this.items.indexOf(item);
            const targetIndex = this.items.indexOf(target);
            [this.items[currentIndex], this.items[targetIndex]] = [target, item];
            this.write();
        },

        canMove(id, direction) {
            const item = this.items.find((candidate) => candidate.id === id);
            const siblings = this.items.filter((candidate) => candidate.parentId === item?.parentId);
            return Boolean(siblings[siblings.indexOf(item) + direction]);
        },

        reorder(id, targetId, after = false) {
            const item = this.items.find((candidate) => candidate.id === id);
            const target = this.items.find((candidate) => candidate.id === targetId);
            if (!item || !target || this.descendants(id).has(targetId)) return;
            this.items = this.items.filter((candidate) => candidate.id !== id);
            item.parentId = target.parentId;
            this.items.splice(this.items.indexOf(target) + (after ? 1 : 0), 0, item);
            this.write();
        },

        linkElement(doc, item) {
            const link = parse(item.linkHtml).querySelector('a') || doc.createElement('a');
            if (cleanLabel(link) !== item.label) {
                const walker = doc.createTreeWalker(link, NodeFilter.SHOW_TEXT);
                const text = [];
                while (walker.nextNode()) {
                    if (!walker.currentNode.parentElement?.closest('i, svg') && walker.currentNode.textContent.trim()) text.push(walker.currentNode);
                }
                if (text.length) {
                    text[0].textContent = item.label;
                    text.slice(1).forEach((node) => { node.textContent = ''; });
                } else { link.prepend(doc.createTextNode(item.label)); }
            }
            const previousIcon = iconFromLink(link);
            if ((item.icon || '') !== previousIcon) iconElementFromLink(link)?.remove();
            if (item.icon) {
                let icon = iconElementFromLink(link);
                if (!icon) { icon = doc.createElement('i'); link.prepend(icon); }
                icon.className = `aw-nav-icon ${item.icon}`;
                icon.setAttribute('aria-hidden', 'true');
            }
            link.setAttribute('href', item.href);
            return link;
        },

        build(doc, parentId = '', mobile = false) {
            const fragment = doc.createDocumentFragment();
            for (const item of this.items.filter((candidate) => candidate.parentId === parentId)) {
                const children = this.items.some((candidate) => candidate.parentId === item.id);
                const link = this.linkElement(doc, item);
                link.querySelectorAll('.aw-nav-indicator, .fa-chevron-down:not(.aw-nav-icon), .fa-caret-down:not(.aw-nav-icon), .fa-arrow-right:not(.aw-nav-icon)').forEach((icon) => icon.remove());
                if (!children) {
                    if (mobile) {
                        link.classList.add('sos-mobile-link');
                        const label = doc.createElement('span');
                        label.className = 'aw-nav-label';
                        label.append(...link.childNodes);
                        link.append(label, this.indicator(doc, 'arrow-right'));
                    }
                    fragment.append(link);
                    continue;
                }
                const group = doc.createElement(mobile ? 'details' : 'div');
                group.className = mobile ? 'sos-mobile-dropdown' : 'drop aw-nav-parent';
                const container = doc.createElement('div');
                container.className = mobile ? 'sos-mobile-submenu aw-mobile-submenu' : 'dropdown aw-nav-submenu';
                if (mobile) {
                    const summary = doc.createElement('summary');
                    const label = doc.createElement('span');
                    label.className = 'aw-nav-label';
                    if (item.icon) {
                        const icon = doc.createElement('i');
                        icon.className = `aw-nav-icon ${item.icon}`;
                        icon.setAttribute('aria-hidden', 'true');
                        label.append(icon);
                    }
                    label.append(doc.createTextNode(item.label));
                    summary.append(label, this.indicator(doc, 'chevron-down'));
                    group.append(summary);
                    if (item.href !== '#') container.append(link);
                } else { link.classList.add('drop-toggle'); link.append(this.indicator(doc, 'chevron-down')); group.append(link); }
                container.append(this.build(doc, item.id, mobile));
                group.append(container);
                fragment.append(group);
            }
            return fragment;
        },

        indicator(doc, name) {
            const icon = doc.createElement('i');
            icon.className = `aw-nav-indicator fa-solid fa-${name}`;
            icon.setAttribute('aria-hidden', 'true');
            return icon;
        },

        write() {
            if (this.problem) return;
            const doc = this.expandedDocument();
            const nav = this.navigation(doc);
            if (!nav) return;
            this.ensureMobileMenu(doc, nav);
            const oldLinks = new Set([...nav.querySelectorAll('a')].map((link) => link.getAttribute('href')));
            const mobile = this.navIndex === 0 ? this.mobileNavigation(doc) : null;
            let extras = [];
            if (mobile) extras = [...mobile.children].filter((child) => child.matches('a') && !oldLinks.has(child.getAttribute('href')));
            nav.classList.add('aw-navigation');
            nav.replaceChildren(this.build(doc));
            if (mobile) mobile.replaceChildren(this.build(doc, '', true), ...extras);
            this.html = doc.body.innerHTML;
            this.ensureBaseCss();
            this.commit();
        },

        ensureBaseCss() {
            const base = /\/\* Navigation editor base \*\/[\s\S]*?\/\* End navigation editor base \*\//;
            if (!base.test(this.css)) this.css += `\n\n${config.baseCss}`;
        },

        ensureMobileMenu(doc, nav) {
            const header = nav.closest('.aw-header');
            if (!header || doc.querySelector('#mobileMenu')) return;
            nav.classList.add('aw-desktop-navigation');
            const actions = doc.createElement('div');
            actions.className = 'aw-header-actions';
            actions.textContent = '[[mobile_menu_toggle]]';
            (header.querySelector('.aw-header-inner') || header).append(actions);
            const mobile = doc.createElement('div');
            mobile.id = 'mobileMenu';
            mobile.className = 'mobile-menu aw-mobile-menu';
            mobile.innerHTML = '<div class="aw-mobile-navigation"></div>';
            header.append(mobile);
        },

        readStyle() {
            const defaults = { color: '#162052', background: '#ffffff', accent: '#047857', size: 14, gap: 20 };
            if (this.header.available) Object.assign(defaults, { background: this.headerStyle.background, accent: '#0076b3', size: 13, gap: 2 });
            for (const [key, fallback] of Object.entries(defaults)) {
                const matches = [...this.css.matchAll(new RegExp(`--aw-nav-${key}\\s*:\\s*([^;}]+)`, 'g'))];
                const value = matches.at(-1)?.[1]?.trim();
                const number = parseInt(value, 10);
                this.style[key] = key === 'size' || key === 'gap' ? (Number.isFinite(number) ? number : fallback) : (/^#[0-9a-f]{6}$/i.test(value || '') ? value : fallback);
            }
        },

        applyStyle() {
            this.ensureBaseCss();
            this.css = this.css.replace(/\/\* Navigation editor appearance \*\/[\s\S]*?\/\* End navigation editor appearance \*\//g, '').trim();
            this.css += `\n\n/* Navigation editor appearance */\nnav.aw-navigation, #mobileMenu { --aw-nav-color: ${this.style.color}; --aw-nav-background: ${this.style.background}; --aw-nav-accent: ${this.style.accent}; --aw-nav-size: ${Number(this.style.size)}px; --aw-nav-gap: ${Number(this.style.gap)}px; }\n#mobileMenu { background: var(--aw-nav-background); }\n#mobileMenu :is(.sos-mobile-link, .sos-mobile-submenu > a, summary) { color: var(--aw-nav-color); font-size: var(--aw-nav-size); }\n#mobileMenu a:hover { color: var(--aw-nav-accent); }\n/* End navigation editor appearance */`;
            const doc = this.expandedDocument();
            const nav = this.navigation(doc);
            nav?.classList.add('aw-navigation');
            if (nav) {
                this.ensureMobileMenu(doc, nav);
                const mobile = this.mobileNavigation(doc);
                if (mobile && nav.closest('.aw-header')) mobile.replaceChildren(this.build(doc, '', true));
            }
            this.html = doc.body.innerHTML;
            this.commit();
        },

        switchMode(mode) {
            if (mode === 'visual') this.codeChanged('html');
            this.mode = mode;
        },

        codeChanged(source = 'css') {
            if (source === 'html') {
                const previousHrefs = this.desktopHrefs;
                this.read();
                if (!this.problem && this.navIndex === 0) {
                    const doc = this.expandedDocument();
                    const mobile = this.mobileNavigation(doc);
                    if (mobile) {
                        const menuHrefs = new Set([...previousHrefs, ...this.desktopHrefs]);
                        const extras = [...mobile.children].filter((child) => child.matches('a') && !menuHrefs.has(child.getAttribute('href')));
                        mobile.replaceChildren(this.build(doc, '', true), ...extras);
                        this.html = doc.body.innerHTML;
                    }
                }
            }
            this.readHeader(); this.readStyle(); this.commit();
        },
        flush() {
            if (this.mode === 'code') this.codeChanged('html');
            else {
                if (!this.flushHeader()) return false;
                const item = this.selected();
                if (item && this.$refs.linkLabel && this.$refs.linkAddress) {
                    if (this.$refs.linkLabel.value !== item.label && !this.update('label', this.$refs.linkLabel.value)) return false;
                    if (this.$refs.linkAddress.value !== this.displayHref(item) && !this.update('href', this.$refs.linkAddress.value)) return false;
                }
                this.sync();
            }
            return true;
        },
        sync() { wire.$set('layoutHtml', this.html, false); wire.$set('layoutCss', this.css, false); },
        commit() { this.sync(); this.remember(); this.queuePreview(); },

        remember() {
            const state = JSON.stringify([this.html, this.css]);
            if (this.history[this.historyIndex] === state) return;
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(state);
            if (this.history.length > 40) this.history.shift();
            this.historyIndex = this.history.length - 1;
        },

        undo(direction) {
            const index = this.historyIndex + direction;
            if (!this.history[index]) return;
            this.historyIndex = index;
            [this.html, this.css] = JSON.parse(this.history[index]);
            this.read(); this.sync(); this.queuePreview();
        },

        defaults() {
            if (this.html.trim() && !window.confirm('Replace this header draft with the default navbar? You can undo this change.')) return;
            this.html = config.defaultHtml;
            this.css = config.baseCss;
            this.navIndex = 0;
            this.read(); this.commit();
        },

    };
};
