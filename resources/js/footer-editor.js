import { validNavigationHref } from './navigation-editor.js';
import { layoutPreview } from './theme-layout-preview.js';

const parse = (html) => new DOMParser().parseFromString(html, 'text/html');
const widgets = { site_logo: 'Site logo', site_name: 'Site name', site_tagline: 'Site tagline', contact_details: 'Contact details', social_links: 'Social links', primary_menu: 'Site menu' };
const textOf = (node) => {
    const copy = node.cloneNode(true);
    copy.querySelectorAll?.('i, svg').forEach((icon) => icon.remove());
    return copy.textContent.replace(/\s+/g, ' ').trim();
};
const pathOf = (node) => {
    const path = [];
    while (node?.parentNode && node.nodeName !== 'BODY') {
        path.unshift([...node.parentNode.childNodes].indexOf(node));
        node = node.parentNode;
    }
    return path;
};
const atPath = (doc, path) => path.reduce((node, index) => node?.childNodes[index], doc.body);
const iconOf = (node) => [...(node.querySelectorAll?.('i') || [])].find((icon) => /(?:^|\s)(?:fa-solid|fa-regular|fa-brands|fas|far|fab)(?:\s|$)/.test(icon.className));

window.awcmsFooterEditor = function (wire, config) {
    const preview = layoutPreview(wire, 'previewLayout');
    return {
        ...preview,
        html: '', css: '', mode: 'visual', columns: [], blocks: [], columnIndex: 0, blockIndex: 0,
        problem: '', error: '', history: [], historyIndex: -1, sort: null, hasGrid: false,
        style: { background: '#070d2c', color: '#c6d3e5', heading: '#ffffff', accent: '#8ffbc3', size: 14, gap: 32, padding: 48, columns: 4 },

        init() {
            this.html = wire.layoutHtml?.trim() ? wire.layoutHtml : config.defaultHtml;
            this.css = wire.layoutHtml?.trim() ? (wire.layoutCss || '') : config.baseCss;
            if (!wire.layoutHtml?.trim()) this.sync();
            this.read(); this.remember(); this.queuePreview();
            this.$watch('$wire.layoutHtml', (value) => {
                if (value === this.html) return;
                this.html = value || ''; this.read(); this.queuePreview();
            });
            this.$watch('$wire.layoutCss', (value) => {
                if (value === this.css) return;
                this.css = value || ''; this.readStyle(); this.queuePreview();
            });
            this.$nextTick(() => {
                this.initPreview();
                if (!window.Sortable) return;
                this.sort = new window.Sortable(this.$refs.columnList, {
                    animation: 150, handle: '.footer-drag', draggable: '[data-column-index]',
                    onEnd: (event) => {
                        const reference = event.from.children[event.oldIndex + (event.newIndex < event.oldIndex ? 1 : 0)];
                        event.from.insertBefore(event.item, reference || null);
                        this.reorderColumns(event.oldIndex, event.newIndex);
                    },
                });
            });
        },

        destroy() { this.destroyPreview(); this.sort?.destroy(); },
        footer(doc) { return doc.querySelector('footer'); },
        grid(doc) { return this.footer(doc)?.querySelector('.managed-school-footer-grid, .aw-footer-grid, .footer-grid'); },
        columnNodes(doc) {
            const footer = this.footer(doc);
            if (!footer) return [];
            const grid = this.grid(doc);
            const columns = grid ? [...grid.children] : [...footer.children].filter((node) => node.matches('section, nav'));
            if (!columns.length && !grid) return [footer];
            const bottom = footer.querySelector('.managed-school-footer-bottom-row, .aw-footer-bottom, .footer-bottom .container, .footer-bottom');
            if (bottom && !columns.some((node) => node === bottom || node.contains(bottom))) columns.push(bottom);
            return columns;
        },

        read() {
            this.problem = ''; this.error = '';
            const doc = parse(this.html);
            const footer = this.footer(doc);
            this.columns = []; this.blocks = []; this.hasGrid = false;
            if (!footer || doc.querySelectorAll('footer').length !== 1) {
                this.problem = 'Visual editing needs one footer element. Keep editing this layout in HTML / CSS, or use the default footer.';
                return;
            }
            const grid = this.grid(doc);
            this.hasGrid = Boolean(grid);
            this.columns = this.columnNodes(doc).map((node, index) => ({
                index, path: pathOf(node), movable: node.parentElement === grid,
                label: node.matches('.managed-school-footer-bottom-row, .aw-footer-bottom, .footer-bottom, .footer-bottom .container')
                    ? 'Copyright bar' : (textOf(node.querySelector('h1, h2, h3, h4, strong') || node).replace(/\[\[([a-z_]+)\]\]/g, (token, key) => widgets[key] || token).slice(0, 55) || `Column ${index + 1}`),
            }));
            this.columnIndex = Math.max(0, Math.min(this.columnIndex, this.columns.length - 1));
            const column = this.columns[this.columnIndex];
            if (!column) { this.readStyle(); return; }
            const add = (node, kind, text, href = '') => this.blocks.push({
                index: this.blocks.length, path: pathOf(node), kind, text, href,
                icon: iconOf(node)?.className || '', label: kind === 'widget' ? (widgets[text] || text) : (text || 'Empty text'),
            });
            const walk = (node) => {
                if (node.nodeType === 3) {
                    const text = node.textContent.trim();
                    if (!text) return;
                    const token = text.match(/^\[\[([a-z_]+)\]\]$/);
                    add(node, token && widgets[token[1]] ? 'widget' : 'text', token && widgets[token[1]] ? token[1] : text);
                    return;
                }
                if (node.nodeType !== 1 || node.matches('i, svg, img, script, style')) return;
                if (node.matches('a')) {
                    if (node.querySelector('img, strong, small') || /\[\[site_logo\]\]/.test(node.textContent)) {
                        add(node, 'brand', 'Brand link', node.getAttribute('href') || '#');
                    } else { add(node, 'link', textOf(node), node.getAttribute('href') || '#'); return; }
                } else if (node.matches('h1, h2, h3, h4, h5, h6, p, strong, small') && !node.querySelector('a, p, strong, small')) {
                    add(node, node.matches('h1, h2, h3, h4, h5, h6') ? 'heading' : 'text', textOf(node));
                    return;
                }
                [...node.childNodes].forEach(walk);
            };
            [...atPath(doc, column.path).childNodes].forEach(walk);
            this.blockIndex = Math.max(0, Math.min(this.blockIndex, this.blocks.length - 1));
            this.readStyle();
        },

        selected() { return this.blocks[this.blockIndex]; },
        selectedColumn() { return this.columns[this.columnIndex]; },
        displayHref() { return (this.selected()?.href || '').replace(/^\[\[site_url:(\/[^\]]*)\]\]$/i, '$1'); },
        selectColumn(index) { if (!this.flush()) return; this.columnIndex = index; this.blockIndex = 0; this.read(); },
        selectBlock(index) { if (!this.flush()) return; this.blockIndex = index; this.error = ''; },

        replaceText(node, value) {
            if (node.nodeType === 3) { node.textContent = value; return; }
            const walker = node.ownerDocument.createTreeWalker(node, NodeFilter.SHOW_TEXT);
            const text = [];
            while (walker.nextNode()) {
                if (!walker.currentNode.parentElement?.closest('i, svg') && walker.currentNode.textContent.trim()) text.push(walker.currentNode);
            }
            if (text.length) { text[0].textContent = value; text.slice(1).forEach((part) => { part.textContent = ''; }); }
            else node.append(node.ownerDocument.createTextNode(value));
        },

        update(field, value) {
            const block = this.selected();
            if (!block || block.kind === 'widget') return false;
            const doc = parse(this.html);
            const node = atPath(doc, block.path);
            if (!node) return false;
            if (field === 'href') {
                value = value.trim();
                if (!validNavigationHref(value)) { this.error = 'Enter a local path, #anchor, https://, mailto: or tel: address.'; return false; }
                if (/^\/[A-Za-z0-9._~\/%-]*$/.test(value)) value = `[[site_url:${value}]]`;
                node.setAttribute('href', value);
            } else if (field === 'text') {
                if (block.kind === 'brand') return false;
                if (block.kind === 'link' && !value.trim()) { this.error = 'Enter a name for this link.'; return false; }
                this.replaceText(node, value);
            } else if (field === 'icon' && block.kind === 'link') {
                if (value && !config.icons.some((icon) => icon.class === value)) return false;
                iconOf(node)?.remove();
                if (value) {
                    const icon = doc.createElement('i'); icon.className = value; icon.setAttribute('aria-hidden', 'true'); node.prepend(icon);
                }
            } else return false;
            block[field] = value;
            if (field === 'text') block.label = value;
            this.error = ''; this.html = doc.body.innerHTML; this.read(); this.commit();
            return true;
        },

        useLink(index) {
            if (index === '' || this.selected()?.kind !== 'link') return;
            const option = [...config.links, ...config.pages][Number(index)];
            if (!option) return;
            this.update('text', option.label); this.update('href', option.href);
        },

        addBlock(kind) {
            if (!this.flush() || !this.selectedColumn()) return;
            const doc = parse(this.html);
            const column = atPath(doc, this.selectedColumn().path);
            const node = doc.createElement(kind === 'heading' ? 'h2' : kind === 'link' ? 'a' : kind === 'text' ? 'p' : 'div');
            if (kind === 'link') {
                node.className = 'aw-footer-link'; node.setAttribute('href', '#'); node.textContent = 'New link';
                (column.querySelector('.managed-school-footer-link-list, .aw-footer-links') || column).append(node);
            } else {
                node.textContent = widgets[kind] ? `[[${kind}]]` : kind === 'heading' ? 'New heading' : 'New text';
                column.append(node);
            }
            this.ensureBaseCss(); this.html = doc.body.innerHTML; this.read();
            const path = pathOf(node);
            this.blockIndex = this.blocks.findIndex((block) => path.every((index, depth) => block.path[depth] === index));
            this.commit();
        },

        removeBlock() {
            if (!this.selected() || !window.confirm('Remove this block from the footer draft?')) return;
            const doc = parse(this.html);
            atPath(doc, this.selected().path)?.remove();
            this.html = doc.body.innerHTML; this.read(); this.commit();
        },

        siblingBlock(direction) {
            const current = this.selected();
            if (!current) return null;
            const parent = JSON.stringify(current.path.slice(0, -1));
            const siblings = this.blocks.filter((block) => JSON.stringify(block.path.slice(0, -1)) === parent);
            return siblings[siblings.indexOf(current) + direction];
        },
        moveBlock(direction) {
            if (!this.flush()) return;
            const target = this.siblingBlock(direction);
            if (!target) return;
            const doc = parse(this.html), node = atPath(doc, this.selected().path), other = atPath(doc, target.path);
            if (direction < 0) other.before(node); else other.after(node);
            const path = JSON.stringify(pathOf(node));
            this.html = doc.body.innerHTML; this.read();
            this.blockIndex = this.blocks.findIndex((block) => JSON.stringify(block.path) === path); this.commit();
        },

        addColumn() {
            if (!this.hasGrid || !this.flush()) return;
            const doc = parse(this.html), grid = this.grid(doc), section = doc.createElement('section');
            section.innerHTML = '<h2>New column</h2><p>Add your content here.</p>';
            grid.append(section); grid.classList.add('aw-footer-grid'); this.ensureBaseCss();
            this.columnIndex = [...grid.children].indexOf(section); this.blockIndex = 0;
            this.html = doc.body.innerHTML; this.read(); this.commit();
        },
        reorderColumns(from, to) {
            if (from === to || !this.flush()) return;
            const doc = parse(this.html), nodes = this.columnNodes(doc), node = nodes[from], target = nodes[to];
            if (!node || !target || node.parentElement !== this.grid(doc) || target.parentElement !== this.grid(doc)) return;
            if (to < from) target.before(node); else target.after(node);
            this.columnIndex = to; this.html = doc.body.innerHTML; this.read(); this.commit();
        },
        canMoveColumn(index, direction) { return this.columns[index]?.movable && Boolean(this.columns[index + direction]?.movable); },
        removeColumn() {
            const column = this.selectedColumn();
            if (!column?.movable || !window.confirm('Remove this column and all its content from the footer draft?')) return;
            const doc = parse(this.html); atPath(doc, column.path)?.remove();
            this.html = doc.body.innerHTML; this.read(); this.commit();
        },

        ensureBaseCss() {
            if (!this.css.includes('/* Footer editor base */')) this.css += `\n\n${config.baseCss}`;
        },
        readStyle() {
            const defaults = { background: '#070d2c', color: '#c6d3e5', heading: '#ffffff', accent: '#8ffbc3', size: 14, gap: 32, padding: 48, columns: 4 };
            for (const [key, fallback] of Object.entries(defaults)) {
                const value = [...this.css.matchAll(new RegExp(`--aw-footer-${key}\\s*:\\s*([^;}]+)`, 'g'))].at(-1)?.[1]?.trim();
                this.style[key] = typeof fallback === 'number' ? (Number.isFinite(parseInt(value, 10)) ? parseInt(value, 10) : fallback) : (/^#[0-9a-f]{6}$/i.test(value || '') ? value : fallback);
            }
        },
        applyStyle() {
            if (!this.flush()) return;
            const doc = parse(this.html), footer = this.footer(doc);
            if (!footer) return;
            footer.classList.add('aw-footer-styled'); this.grid(doc)?.classList.add('aw-footer-grid');
            this.ensureBaseCss();
            this.css = this.css.replace(/\/\* Footer editor appearance \*\/[\s\S]*?\/\* End footer editor appearance \*\//g, '').trim();
            const variables = Object.entries(this.style).map(([key, value]) => `--aw-footer-${key}: ${value}${typeof value === 'number' && key !== 'columns' ? 'px' : ''};`).join(' ');
            this.css += `\n\n/* Footer editor appearance */
footer.aw-footer-styled { ${variables} background: var(--aw-footer-background); color: var(--aw-footer-color); }
.aw-footer-styled :is(.managed-school-footer-main, .managed-school-footer-bottom, .footer-bottom) { background: var(--aw-footer-background); }
.aw-footer-styled :is(.aw-footer-shell, .managed-school-footer-main) { padding-top: var(--aw-footer-padding); }
.aw-footer-styled :is(p, a, small, .cms-contact-address, .cms-contact-phone, .cms-contact-email) { color: var(--aw-footer-color); font-size: var(--aw-footer-size); }
.aw-footer-styled :is(h1, h2, h3, strong) { color: var(--aw-footer-heading); }
.aw-footer-styled a:hover, .aw-footer-styled i { color: var(--aw-footer-accent); }
footer.aw-footer-styled .aw-footer-grid { display: grid; grid-template-columns: repeat(var(--aw-footer-columns), minmax(0, 1fr)); gap: var(--aw-footer-gap); }
@media (max-width: 900px) { footer.aw-footer-styled .aw-footer-grid { grid-template-columns: repeat(${Math.min(2, this.style.columns)}, minmax(0, 1fr)); } }
@media (max-width: 600px) { footer.aw-footer-styled .aw-footer-grid { grid-template-columns: minmax(0, 1fr); } }
/* End footer editor appearance */`;
            this.html = doc.body.innerHTML; this.read(); this.commit();
        },

        switchMode(mode) { if (!this.flush()) return; this.mode = mode; if (mode === 'visual') this.read(); },
        codeChanged() { this.read(); this.commit(); },
        flush() {
            if (this.mode === 'code') this.codeChanged();
            else {
                const block = this.selected();
                if (block && this.$refs.blockText && this.$refs.blockText.value !== block.text && !this.update('text', this.$refs.blockText.value)) return false;
                if (block && this.$refs.blockAddress && this.$refs.blockAddress.value !== this.displayHref() && !this.update('href', this.$refs.blockAddress.value)) return false;
                if (this.error) return false;
                this.sync();
            }
            return true;
        },
        sync() { wire.$set('layoutHtml', this.html, false); wire.$set('layoutCss', this.css, false); },
        commit() { this.sync(); this.remember(); this.queuePreview(); },
        remember() {
            const state = JSON.stringify([this.html, this.css]);
            if (this.history[this.historyIndex] === state) return;
            this.history = this.history.slice(0, this.historyIndex + 1); this.history.push(state);
            if (this.history.length > 60) this.history.shift();
            this.historyIndex = this.history.length - 1;
        },
        undo(direction) {
            const index = this.historyIndex + direction;
            if (!this.history[index]) return;
            this.historyIndex = index; [this.html, this.css] = JSON.parse(this.history[index]);
            this.read(); this.sync(); this.queuePreview();
        },
        defaults() {
            if (this.html.trim() && !window.confirm('Replace this footer draft with the default footer? You can undo this change.')) return;
            this.html = config.defaultHtml; this.css = config.baseCss; this.columnIndex = 0; this.blockIndex = 0;
            this.read(); this.commit();
        },
        previewLoaded() {
            preview.previewLoaded.call(this);
            const doc = this.$refs.preview?.contentDocument;
            doc?.addEventListener('click', (event) => {
                const index = this.columnNodes(doc).findIndex((column) => column.contains(event.target));
                if (index >= 0) this.selectColumn(index);
            });
        },
    };
};
