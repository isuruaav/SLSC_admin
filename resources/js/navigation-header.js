const parseHeader = (html) => new DOMParser().parseFromString(html, 'text/html');
const displayAddress = (href = '') => href.replace(/^\[\[site_url:(\/[^\]]*)\]\]$/i, '$1');
const colors = { topbarStart: '#080d26', topbarEnd: '#005c8c', topbarText: '#ffffff', background: '#f8fcff', brand: '#111936', subtitle: '#0076b3', portal: '#00923e' };
const variable = (key) => key.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`);

export function headerEditor(config, validHref) {
    return {
        header: {}, headerStyle: { ...colors }, headerError: '',

        readHeader(doc = parseHeader(this.html)) {
            const root = doc.querySelector('.aw-header');
            const text = (selector) => root?.querySelector(selector)?.textContent.trim() || '';
            const address = (selector) => displayAddress(root?.querySelector(selector)?.getAttribute('href') || '');
            const visible = (selector) => Boolean(root?.querySelector(selector) && !root.querySelector(selector).classList.contains('aw-hidden'));
            this.header = {
                available: Boolean(root?.querySelector('.aw-brand-title')),
                layout: root?.classList.contains('aw-header-stacked') ? 'stacked' : 'inline',
                topbarTitle: text('.aw-topbar-text'), motto: text('.aw-topbar-motto'),
                brandTitle: text('.aw-brand-title'), brandSubtitle: text('.aw-brand-subtitle'),
                homeHref: address('.aw-brand'), logoUrl: root?.querySelector('.aw-brand-logo img')?.getAttribute('src') || '',
                conductLabel: text('.aw-header-conduct .aw-action-label'), conductHref: address('.aw-header-conduct'),
                portalLabel: text('.aw-header-portal .aw-action-label'), portalHref: address('.aw-header-portal'),
                showTopbar: visible('.aw-topbar'), showLanguages: visible('.aw-header-languages'),
                showSearch: visible('.aw-header-search'), showConduct: visible('.aw-header-conduct'), showPortal: visible('.aw-header-portal'),
                showIcons: Boolean(root?.classList.contains('aw-show-menu-icons')),
            };
            for (const [key, fallback] of Object.entries(colors)) {
                const matches = [...this.css.matchAll(new RegExp(`--aw-header-${variable(key)}\\s*:\\s*(#[0-9a-f]{6})`, 'gi'))];
                this.headerStyle[key] = matches.at(-1)?.[1] || fallback;
            }
        },

        updateHeader(field, value) {
            const doc = parseHeader(this.html);
            const root = doc.querySelector('.aw-header');
            if (!root || !this.header.available) return false;
            this.headerError = '';
            const textFields = {
                topbarTitle: '.aw-topbar-text', motto: '.aw-topbar-motto', brandTitle: '.aw-brand-title', brandSubtitle: '.aw-brand-subtitle',
                conductLabel: '.aw-header-conduct .aw-action-label', portalLabel: '.aw-header-portal .aw-action-label',
            };
            const links = { homeHref: '.aw-brand', conductHref: '.aw-header-conduct', portalHref: '.aw-header-portal' };
            const toggles = { showTopbar: '.aw-topbar', showLanguages: '.aw-header-languages', showSearch: '.aw-header-search', showConduct: '.aw-header-conduct', showPortal: '.aw-header-portal' };
            if (textFields[field]) root.querySelectorAll(textFields[field]).forEach((element) => { element.textContent = value; });
            else if (links[field]) {
                value = value.trim();
                if (!validHref(value)) { this.headerError = 'Enter a local path such as /courses or a complete https:// address.'; return false; }
                const href = /^\/[A-Za-z0-9._~\/%-]*$/.test(value) ? `[[site_url:${value}]]` : value;
                root.querySelectorAll(links[field]).forEach((element) => element.setAttribute('href', href));
            } else if (toggles[field]) root.querySelectorAll(toggles[field]).forEach((element) => element.classList.toggle('aw-hidden', !value));
            else if (field === 'layout') {
                root.classList.toggle('aw-header-stacked', value === 'stacked');
            } else if (field === 'showIcons') root.classList.toggle('aw-show-menu-icons', value);
            else if (field === 'logoUrl') {
                value = value.trim();
                if (value && (!/^(https?:\/\/|\/(?!\/))/i.test(value) || /[\s\\]/.test(value))) {
                    this.headerError = 'Use an image URL from the Media Library, or leave it empty to use the site logo.';
                    return false;
                }
                const logo = root.querySelector('.aw-brand-logo');
                if (!logo) return false;
                if (value) {
                    const img = doc.createElement('img');
                    img.setAttribute('src', value);
                    img.setAttribute('alt', this.header.brandTitle);
                    logo.replaceChildren(img);
                } else logo.textContent = '[[site_logo]]';
            } else return false;
            if (field === 'topbarTitle') root.querySelector('.aw-topbar-title')?.classList.toggle('aw-hidden', !value.trim());
            if (field === 'motto') root.querySelector('.aw-topbar-motto')?.classList.toggle('aw-hidden', !value.trim());
            if (field === 'showConduct' || field === 'showPortal') {
                const actions = root.querySelector('.aw-mobile-actions');
                if (actions) actions.classList.toggle('aw-hidden', ![...actions.children].some((element) => !element.classList.contains('aw-hidden')));
            }
            this.header[field] = value;
            this.html = doc.body.innerHTML;
            this.commit();
            return true;
        },

        applyHeaderStyle() {
            if (!Object.values(this.headerStyle).every((color) => /^#[0-9a-f]{6}$/i.test(color))) return;
            this.css = this.css.replace(/\/\* Header appearance \*\/[\s\S]*?\/\* End header appearance \*\//g, '').trim();
            const declarations = Object.entries(this.headerStyle).map(([key, value]) => `--aw-header-${variable(key)}: ${value};`).join(' ');
            this.css += `\n\n/* Header appearance */\n.aw-header-reference { ${declarations} }\n/* End header appearance */`;
            this.commit();
        },

        applyHeaderPreset() {
            const original = this.expandedDocument();
            const nav = this.navigation(original);
            const mobile = this.mobileNavigation(original);
            const fresh = parseHeader(config.defaultHtml);
            const newNav = fresh.querySelector('nav.aw-navigation');
            if (!newNav) return;
            if (nav) newNav.innerHTML = nav.innerHTML;
            if (mobile) fresh.querySelector('.aw-mobile-navigation').innerHTML = mobile.innerHTML;
            this.html = fresh.body.innerHTML;
            const base = /\/\* Navigation editor base \*\/[\s\S]*?\/\* End navigation editor base \*\//;
            this.css = base.test(this.css) ? this.css.replace(base, () => config.baseCss.trim()) : `${this.css}\n\n${config.baseCss}`;
            this.navIndex = 0;
            this.read();
            this.commit();
        },

        flushHeader() {
            if (!this.header.available) return true;
            const doc = parseHeader(this.html);
            const fields = { homeHref: '.aw-brand', conductHref: '.aw-header-conduct', portalHref: '.aw-header-portal', logoUrl: '.aw-brand-logo' };
            for (const [field, selector] of Object.entries(fields)) {
                if (doc.querySelector(selector) && !this.updateHeader(field, this.header[field])) return false;
            }
            return true;
        },
    };
}
