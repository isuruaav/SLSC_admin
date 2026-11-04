import { translations } from './i18n.js';
import { matchesQuery, formatMessage, activeSectionId, formatDisplayDate } from './helpers.js';
import { initializeRegistration } from './registration.js';
import { initializeNavigation } from './navigation.js';

const instances = new WeakMap();

export function initializeSite(d = document, w = window) {
    if (instances.has(d)) return instances.get(d);
    const $ = selector => d.querySelector(selector);
    const $$ = selector => [...d.querySelectorAll(selector)];
    const controller = new w.AbortController();
    const observers = [];
    const on = (element, event, handler, options = {}) => element?.addEventListener?.(event, handler, { ...options, signal: controller.signal });
    const motion = w.matchMedia?.('(prefers-reduced-motion: reduce)') ?? { matches: false };
    const desktop = w.matchMedia?.('(min-width: 1024px)') ?? { matches: true };
    const slider = $('#heroSlider');
    const slides = $$('.hero-slide');
    const dots = $$('.slider-dot');
    const pauseButton = $('#toggleSlider');
    const menuButton = $('#mobileMenuBtn');
    const menu = $('#navPanel');
    const header = $('#siteHeader');
    const newsCards = $$('[data-news-category]');
    const programCards = $$('[data-program-category]');
    const dialog = $('#siteSearchDialog');
    const searchInput = $('#siteSearchInput');
    const serverLanguage = ['en', 'si'].includes(d.documentElement.dataset.siteLocale) ? d.documentElement.dataset.siteLocale : null;
    let language = serverLanguage || 'en';
    let newsCategory = 'all';
    let searchScope = 'all';
    let searchOpener = null;
    let activeSlide = 0;
    let userPaused = motion.matches;
    let hoverPaused = false;
    let focusPaused = false;
    let heroVisible = !w.IntersectionObserver;
    let sliderFrame = 0;
    let scrollFrame = 0;
    let previousTime = null;
    let elapsed = 0;
    let touchStart = null;
    let suppressClick = false;
    let destroyed = false;
    let registration = null;
    const duration = 6500;
    if (!serverLanguage) {
        try { language = w.localStorage.getItem('slsc-language') === 'si' ? 'si' : 'en'; } catch { /* Storage is optional. */ }
    }
    const t = (key, values) => formatMessage(
        (key === 'brandName' && d.documentElement.dataset.siteName) || translations[language][key] || translations.en[key] || key,
        values,
    );
    const countText = (key, count) => t(count === 1 ? `${key}One` : key, { count });
    const searchable = node => {
        const key = node.dataset.titleKey;
        return `${translations.en[key] ?? ''} ${translations.si[key] ?? ''} ${node.textContent}`;
    };

    // Language changes update content, labels, dates, counts and search results together.
    function applyLanguage(nextLanguage) {
        language = serverLanguage || (nextLanguage === 'si' ? 'si' : 'en');
        d.documentElement.lang = language;
        d.documentElement.classList.toggle('is-si', language === 'si');
        d.body.classList.toggle('is-si', language === 'si');
        $$('[data-i18n]').forEach(node => { node.textContent = t(node.dataset.i18n); });
        $$('[data-i18n-placeholder]').forEach(node => { node.placeholder = t(node.dataset.i18nPlaceholder); });
        $$('[data-i18n-aria]').forEach(node => { node.setAttribute('aria-label', t(node.dataset.i18nAria)); });
        $$('.lang-btn').forEach(button => {
            const current = button.dataset.lang === language;
            button.classList.toggle('active', current);
            if (button.tagName === 'A') {
                if (current) button.setAttribute('aria-current', 'page');
                else button.removeAttribute('aria-current');
            } else button.setAttribute('aria-pressed', String(current));
        });
        $$('time[datetime]').forEach(node => {
            const text = formatDisplayDate(node.dateTime, language);
            if (text !== null) node.textContent = text;
        });
        if (!serverLanguage) {
            d.title = d.body.classList.contains('registration-body') ? `${t('regModalTitle')} | ${t('brandName')}` : `${t('brandName')} | Swift & Sure`;
            $('meta[name="description"]')?.setAttribute('content', t('metaDescription'));
        }
        dots.forEach((dot, index) => dot.setAttribute('aria-label', t('slideLabel', { number: index + 1 })));
        try { w.localStorage.setItem('slsc-language', language); } catch { /* The page still works without storage. */ }
        updatePauseButton();
        filterNews();
        filterPrograms();
        if (dialog?.open) renderSearch();
        registration?.refreshLanguage();
        measureHeader();
    }
    // CMS language links navigate to the translated route and work without JS.
    $$('.lang-btn').filter(button => button.tagName !== 'A').forEach(button => on(button, 'click', () => applyLanguage(button.dataset.lang)));

    // Navigation works without JavaScript; enhancement adds closing and active section states.
    function closeMenu(restoreFocus = false) {
        const wasOpen = menu?.classList.contains('is-open');
        menu?.classList.remove('is-open');
        menuButton?.setAttribute('aria-expanded', 'false');
        if (menuButton) { menuButton.dataset.i18nAria = 'openMenu'; menuButton.setAttribute('aria-label', t('openMenu')); }
        if (wasOpen && restoreFocus) menuButton?.focus();
        measureHeader();
    }
    on(menuButton, 'click', () => {
        const opened = menu.classList.toggle('is-open');
        menuButton.setAttribute('aria-expanded', String(opened));
        menuButton.dataset.i18nAria = opened ? 'closeMenu' : 'openMenu';
        menuButton.setAttribute('aria-label', t(menuButton.dataset.i18nAria));
        measureHeader();
    });
    $$('.nav-link').forEach(link => on(link, 'click', () => closeMenu()));
    on(d, 'click', event => { if (!header?.contains(event.target)) closeMenu(); });
    on(w, 'resize', () => { if (desktop.matches) closeMenu(); measureHeader(); queueScroll(); });

    initializeNavigation(d, w, on, { open: t('openMenu'), close: t('closeMenu') });
    function measureHeader() {
        const node = menu?.classList.contains('is-open') ? $('.brand-bar') : header;
        const height = node?.getBoundingClientRect().height;
        if (height) d.documentElement.style.setProperty('--header-height', `${Math.round(height)}px`);
    }
    if (w.ResizeObserver && header) {
        const observer = new w.ResizeObserver(() => { measureHeader(); queueScroll(); });
        observer.observe(header); observers.push(observer);
    }

    // A single animation frame updates the visible slide's timer; background tabs stop work.
    function updatePauseButton() {
        if (!pauseButton) return;
        pauseButton.classList.toggle('is-paused', userPaused);
        pauseButton.setAttribute('aria-pressed', String(userPaused));
        pauseButton.dataset.i18nAria = userPaused ? 'playSlider' : 'pauseSlider';
        pauseButton.setAttribute('aria-label', t(pauseButton.dataset.i18nAria));
    }
    function showSlide(index, announce = false) {
        if (!slides.length) return;
        activeSlide = (index + slides.length) % slides.length;
        elapsed = 0;
        previousTime = null;
        slides.forEach((slide, i) => {
            const current = i === activeSlide;
            slide.classList.toggle('is-active', current);
            slide.setAttribute('aria-hidden', String(!current));
            slide.inert = !current;
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === activeSlide);
            if (i === activeSlide) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current');
        });
        if ($('#activeSlideNumber')) $('#activeSlideNumber').textContent = String(activeSlide + 1).padStart(2, '0');
        if ($('#heroProgress')) $('#heroProgress').style.transform = 'scaleX(0)';
        if (announce && $('#slideStatus')) $('#slideStatus').textContent = t('slideAnnouncement', { number: activeSlide + 1, total: slides.length, title: slides[activeSlide].querySelector('h1')?.textContent ?? '' });
    }
    const canPlay = () => !destroyed && !userPaused && !hoverPaused && !focusPaused && heroVisible && !d.hidden && slides.length > 1 && !dialog?.open && !$('#registrationDialog')?.open;
    function tick(timestamp) {
        sliderFrame = 0;
        if (!canPlay()) { previousTime = null; return; }
        if (previousTime !== null) elapsed += timestamp - previousTime;
        previousTime = timestamp;
        if (elapsed >= duration) showSlide(activeSlide + 1);
        if ($('#heroProgress')) $('#heroProgress').style.transform = `scaleX(${Math.min(1, elapsed / duration)})`;
        sliderFrame = w.requestAnimationFrame(tick);
    }
    function syncPlayback() {
        if (canPlay()) { if (!sliderFrame) sliderFrame = w.requestAnimationFrame(tick); }
        else { w.cancelAnimationFrame(sliderFrame); sliderFrame = 0; previousTime = null; }
    }
    const advance = delta => { showSlide(activeSlide + delta, true); syncPlayback(); };
    on($('#nextSlide'), 'click', () => advance(1));
    on($('#prevSlide'), 'click', () => advance(-1));
    dots.forEach(dot => on(dot, 'click', () => { showSlide(Number(dot.dataset.slide), true); syncPlayback(); }));
    on(pauseButton, 'click', () => { userPaused = !userPaused; if (!userPaused) { hoverPaused = false; focusPaused = false; } updatePauseButton(); syncPlayback(); });
    on(slider, 'pointerenter', event => { if (event.pointerType !== 'touch') { hoverPaused = true; syncPlayback(); } });
    on(slider, 'pointerleave', () => { hoverPaused = false; syncPlayback(); });
    on(slider, 'focusin', () => { focusPaused = true; syncPlayback(); });
    on(slider, 'focusout', event => { if (!slider.contains(event.relatedTarget)) { focusPaused = false; syncPlayback(); } });
    on(slider, 'keydown', event => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); advance(event.key === 'ArrowRight' ? 1 : -1); }
    });
    on(slider, 'pointerdown', event => { suppressClick = false; touchStart = event.pointerType === 'touch' ? { x: event.clientX, y: event.clientY } : null; });
    on(slider, 'pointerup', event => {
        if (!touchStart) return;
        const dx = event.clientX - touchStart.x; const dy = event.clientY - touchStart.y; touchStart = null;
        if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.4) { suppressClick = true; advance(dx < 0 ? 1 : -1); }
    });
    on(slider, 'pointercancel', () => { touchStart = null; });
    on(slider, 'click', event => { if (suppressClick) { event.preventDefault(); suppressClick = false; } }, { capture: true });
    on(d, 'visibilitychange', syncPlayback);
    on(motion, 'change', () => { if (motion.matches) userPaused = true; updatePauseButton(); syncPlayback(); });
    if (w.IntersectionObserver && slider) {
        const observer = new w.IntersectionObserver(entries => { heroVisible = entries[0].isIntersecting; syncPlayback(); }, { threshold: 0.1 });
        observer.observe(slider); observers.push(observer);
    }

    function filterNews() {
        const query = $('#newsSearch')?.value ?? '';
        let count = 0;
        newsCards.forEach(card => {
            const visible = (newsCategory === 'all' || card.dataset.newsCategory === newsCategory) && matchesQuery(searchable(card), query);
            card.hidden = !visible; if (visible) count++;
        });
        $('#newsGrid')?.classList.toggle('is-filtered', newsCategory !== 'all' || Boolean(query.trim()));
        if ($('#noNews')) $('#noNews').hidden = count !== 0;
        if ($('#newsCount')) $('#newsCount').textContent = countText('newsCount', count);
        $$('[data-news-filter]').forEach(button => { const active = button.dataset.newsFilter === newsCategory; button.classList.toggle('active', active); button.setAttribute('aria-pressed', String(active)); });
    }
    function filterPrograms() {
        const query = $('#programSearch')?.value ?? '';
        const category = $('#programCategory')?.value ?? 'all';
        let count = 0;
        programCards.forEach(card => {
            const visible = (category === 'all' || card.dataset.programCategory === category) && matchesQuery(searchable(card), query);
            card.hidden = !visible; if (visible) count++;
        });
        if ($('#noPrograms')) $('#noPrograms').hidden = count !== 0;
        if ($('#programCount')) $('#programCount').textContent = countText('programCount', count);
    }
    $$('[data-news-filter]').forEach(button => on(button, 'click', () => { newsCategory = button.dataset.newsFilter; filterNews(); }));
    on($('#newsSearch'), 'input', filterNews);
    on($('#programSearch'), 'input', filterPrograms);
    on($('#programCategory'), 'change', filterPrograms);
    $$('[data-reset]').forEach(button => on(button, 'click', () => {
        if (button.dataset.reset === 'news') { newsCategory = 'all'; if ($('#newsSearch')) $('#newsSearch').value = ''; filterNews(); $('#newsSearch')?.focus(); }
        else { $('#programSearch').value = ''; $('#programCategory').value = 'all'; filterPrograms(); $('#programSearch').focus(); }
    }));

    // Search is entirely local: visitors can search in English or Sinhala in either display language.
    function renderSearch() {
        const pageKeys = { about: ['commandantTitle'], contact: ['footerAddress', 'regimentalCentre'], publications: ['pubTitle', 'pubDesc'], 'join-us': ['regModalTitle', 'joinDescription'] };
        const entries = $$('[data-page-link]').map(link => {
            const id = link.hash.slice(1);
            const keys = [link.dataset.i18n, ...(pageKeys[id] ?? [])];
            const labels = keys.flatMap(key => [translations.en[key], translations.si[key]]).join(' ');
            const identity = id === 'about' ? $('.commandant-identity')?.textContent ?? '' : '';
            return { title: link.textContent, text: `${labels} ${identity}`, href: link.getAttribute('href'), type: 'pages', external: false };
        });
        $$('[data-search-item]').forEach(node => {
            const link = node.matches('a') ? node : node.querySelector('[data-entry-link]');
            entries.push({ title: node.querySelector('[data-entry-title]')?.textContent ?? '', text: searchable(node), href: link.getAttribute('href'), type: node.dataset.searchItem, external: true });
        });
        const matches = entries.filter(entry => (searchScope === 'all' || entry.type === searchScope) && matchesQuery(entry.text, searchInput?.value ?? ''));
        const fragment = d.createDocumentFragment();
        const typeLabels = { pages: 'searchPages', news: 'navNews', programs: 'navPrograms' };
        for (const entry of matches) {
            const item = d.createElement('li'); const link = d.createElement('a');
            link.href = entry.href;
            if (entry.external) { link.target = '_blank'; link.rel = 'noopener noreferrer'; }
            const copy = d.createElement('span'); copy.className = 'search-result-copy';
            const title = d.createElement('span'); title.textContent = entry.title;
            const type = d.createElement('span'); type.className = 'search-result-type'; type.textContent = t(typeLabels[entry.type]);
            copy.append(type, title); link.append(copy);
            const svg = d.createElementNS('http://www.w3.org/2000/svg', 'svg'); svg.setAttribute('class', 'icon'); svg.setAttribute('aria-hidden', 'true');
            const use = d.createElementNS('http://www.w3.org/2000/svg', 'use'); use.setAttribute('href', entry.external ? '#i-external' : '#i-arrow-right'); svg.append(use); link.append(svg);
            item.append(link); fragment.append(item);
        }
        $('#siteSearchResults')?.replaceChildren(fragment);
        if ($('#siteSearchCount')) $('#siteSearchCount').textContent = countText('searchResultCount', matches.length);
        if ($('#siteSearchEmpty')) $('#siteSearchEmpty').hidden = matches.length !== 0;
        $$('[data-search-scope]').forEach(button => { const active = button.dataset.searchScope === searchScope; button.classList.toggle('active', active); button.setAttribute('aria-pressed', String(active)); });
    }
    function openSearch(opener) {
        if (w.slscSearch && typeof w.slscSearch.open === 'function') {
            w.slscSearch.open();
            return;
        }
        const slscModal = d.querySelector('[data-search-modal]');
        if (slscModal) {
            slscModal.hidden = false;
            slscModal.classList.add('is-open');
            slscModal.style.display = 'flex';
            d.body.style.overflow = 'hidden';
            slscModal.querySelector('[data-modal-search-input]')?.focus();
            return;
        }
        if (!dialog || dialog.open || $('#registrationDialog')?.open) return;
        closeMenu(); searchOpener = opener ?? d.activeElement; searchScope = 'all'; searchInput.value = '';
        renderSearch();
        if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', '');
        d.body.classList.add('modal-open'); searchInput.focus(); syncPlayback();
    }
    function finishSearch() { d.body.classList.remove('modal-open'); searchOpener?.focus(); syncPlayback(); }
    function closeSearch() {
        if (w.slscSearch && typeof w.slscSearch.close === 'function') {
            w.slscSearch.close();
        }
        const slscModal = d.querySelector('[data-search-modal]');
        if (slscModal && !slscModal.hidden) {
            slscModal.hidden = true;
            slscModal.classList.remove('is-open');
            slscModal.style.display = 'none';
            d.body.style.overflow = '';
        }
        if (!dialog?.open) return;
        if (typeof dialog.close === 'function') dialog.close(); else { dialog.removeAttribute('open'); finishSearch(); }
    }
    $$('[data-open-search]').forEach(button => on(button, 'click', () => openSearch(button)));
    on($('#closeSearch'), 'click', closeSearch);
    on(dialog, 'close', finishSearch);
    on(dialog, 'cancel', event => { event.preventDefault(); closeSearch(); });
    on(dialog, 'click', event => { const rect = dialog.getBoundingClientRect(); if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) closeSearch(); });
    on(searchInput, 'input', renderSearch);
    $$('[data-search-scope]').forEach(button => on(button, 'click', () => { searchScope = button.dataset.searchScope; renderSearch(); }));
    on($('#siteSearchResults'), 'click', event => { if (event.target.closest('a')) closeSearch(); });
    on(dialog, 'keydown', event => {
        const links = $$('#siteSearchResults a');
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault(); const index = links.indexOf(d.activeElement); const next = event.key === 'ArrowDown' ? index + 1 : index - 1;
            if (next < 0) searchInput.focus(); else links[Math.min(next, links.length - 1)]?.focus();
        }
        if (event.key === 'Tab') {
            const controls = [...dialog.querySelectorAll('button,input,a[href]')].filter(node => !node.closest('[hidden]'));
            const first = controls[0]; const last = controls.at(-1);
            if (event.shiftKey && d.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && d.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    });
    on(d, 'keydown', event => {
        if ($('#registrationDialog')?.open) return;
        if (d.querySelector('[data-search-modal]')) return; // Handled by search.js
        const editable = event.target instanceof w.Element && event.target.closest('input,textarea,select,[contenteditable="true"]');
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); if (dialog?.open) closeSearch(); else openSearch(d.activeElement); }
        else if (event.key === '/' && !editable && !dialog?.open) { event.preventDefault(); openSearch(d.activeElement); }
        if (event.key === 'Escape') { if (dialog?.open) { event.preventDefault(); closeSearch(); } else closeMenu(true); }
    });

    function updateScroll() {
        scrollFrame = 0;
        const height = d.documentElement.scrollHeight - w.innerHeight;
        const progress = height > 0 ? Math.max(0, Math.min(1, w.scrollY / height)) : 0;
        if ($('#scrollProgress')) $('#scrollProgress').style.transform = `scaleX(${progress})`;
        header?.classList.toggle('is-scrolled', w.scrollY > 10);
        $('#backToTop')?.classList.toggle('show', w.scrollY > 450);
        const headerHeight = header?.getBoundingClientRect().height ?? 0;
        const sections = $$('[data-page-link]').map(link => { const id = link.hash.slice(1); return { id, top: (d.getElementById(id)?.getBoundingClientRect().top ?? 0) + w.scrollY }; });
        const activeId = activeSectionId(sections, w.scrollY, headerHeight + 55);
        $$('[data-page-link]').forEach(link => { const active = link.hash === `#${activeId}`; link.classList.toggle('current', active); if (active) link.setAttribute('aria-current', 'location'); else link.removeAttribute('aria-current'); });
    }
    function queueScroll() { if (!scrollFrame) scrollFrame = w.requestAnimationFrame(updateScroll); }
    on(w, 'scroll', queueScroll, { passive: true });
    on($('#backToTop'), 'click', () => { w.scrollTo({ top: 0, behavior: motion.matches ? 'instant' : 'smooth' }); });
    if (w.IntersectionObserver && !motion.matches) {
        d.documentElement.classList.add('js-motion');
        const observer = new w.IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); } }), { threshold: 0.05, rootMargin: '0px 0px 30px 0px' });
        $$('.reveal').forEach(node => observer.observe(node)); observers.push(observer);
    }
    $$('img').forEach(image => on(image, 'error', () => { if (!image.src.endsWith('/assets/images/programs-sample.jpg')) image.src = new URL('../images/programs-sample.jpg', import.meta.url).href; }, { once: true }));

    registration = initializeRegistration(d, w, { translate: t, onOpen: closeMenu, onModalChange: syncPlayback });
    d.documentElement.classList.add('js');
    applyLanguage(language); showSlide(0); updatePauseButton(); measureHeader(); updateScroll(); syncPlayback();
    const instance = { destroy() { destroyed = true; registration?.destroy(); controller.abort(); observers.forEach(observer => observer.disconnect()); w.cancelAnimationFrame(sliderFrame); w.cancelAnimationFrame(scrollFrame); instances.delete(d); } };
    instances.set(d, instance);
    return instance;
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initializeSite(), { once: true });
    else initializeSite();
}
