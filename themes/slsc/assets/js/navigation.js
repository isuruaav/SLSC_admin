export function initializeNavigation(d, w, on = (node, event, handler) => node?.addEventListener(event, handler), labels = { open: 'Open menu', close: 'Close menu' }) {
    const groups = [...d.querySelectorAll('[data-slsc-nav-group]')];
    const closeOtherGroups = current => groups.forEach(group => { if (group !== current) group.open = false; });
    const desktopHover = () => w.matchMedia?.('(min-width: 1024px) and (hover: hover)').matches;
    groups.forEach(group => {
        on(group, 'toggle', () => { if (group.open) closeOtherGroups(group); });
        on(group, 'pointerenter', () => { if (desktopHover()) { closeOtherGroups(group); group.open = true; } });
        on(group, 'pointerleave', () => { if (desktopHover()) group.open = false; });
        on(group, 'keydown', event => {
            if (event.key === 'Escape' && group.open) {
                event.preventDefault();
                event.stopPropagation();
                group.open = false;
                group.querySelector('summary')?.focus();
            }
        });
    });
    on(d, 'click', event => groups.forEach(group => { if (!group.contains(event.target)) group.open = false; }));
    on(d, 'focusin', event => groups.forEach(group => { if (!group.contains(event.target)) group.open = false; }));
    on(w, 'resize', () => closeOtherGroups(null));

    const path = w.location.pathname.replace(/\/+$/, '') || '/';
    d.querySelectorAll('.desktop-nav a, .mobile-menu a, .aw-navigation a').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#')) return;
        let target;
        try { target = new URL(href, d.baseURI); } catch { return; }
        const current = target.origin === w.location.origin && (target.pathname.replace(/\/+$/, '') || '/') === path;
        link.classList.toggle('active', current);
        if (current) {
            link.setAttribute('aria-current', 'page');
            link.closest('.drop')?.querySelector('.drop-toggle')?.classList.add('active');
            let disclosure = link.closest('details');
            while (disclosure) {
                disclosure.open = true;
                disclosure = disclosure.parentElement?.closest('details');
            }
        } else link.removeAttribute('aria-current');
    });

    const toggle = d.querySelector('#mobileToggle, [data-mobile-toggle]');
    const menu = d.querySelector('#mobileMenu, [data-mobile-menu]');
    if (!toggle || !menu) return;
    const setOpen = open => {
        menu.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? labels.close : labels.open);
        const icon = toggle.querySelector('i');
        icon?.classList.toggle('fa-bars', !open);
        icon?.classList.toggle('fa-xmark', open);
    };
    setOpen(false);
    toggle.setAttribute('aria-controls', menu.id);
    on(toggle, 'click', () => { menu.classList.remove('hidden'); setOpen(!menu.classList.contains('open')); });
    menu.querySelectorAll('a').forEach(link => on(link, 'click', () => setOpen(false)));
    on(d, 'keydown', event => {
        if (event.key === 'Escape' && menu.classList.contains('open')) {
            setOpen(false);
            toggle.focus();
        }
    });
    on(d, 'click', event => { if (!menu.contains(event.target) && !toggle.contains(event.target)) setOpen(false); });
    on(w, 'resize', () => { if (w.innerWidth > 1120) setOpen(false); });
}
