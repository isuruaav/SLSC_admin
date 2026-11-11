const mediaFilename = /^[^/\\?#]+\.(?:jpe?g|png|gif|webp|svg|avif|pdf|docx?|xlsx?|pptx?|mp4|webm|mov|mp3|ogg|wav)$/i;
const mediaPath = /^\/(?:media-files|document-files|storage|publications)(?:\/|$)/i;

function basePath(options) {
    return (options.basePath || '').replace(/\/+$/, '');
}

export function portableSitePath(value, options = {}, filename = false) {
    if (typeof value !== 'string') return '';
    let path = value.trim();
    if (!path || /^[#?]/.test(path)) return path;
    const base = basePath(options);
    if (/^(?:https?:)?\/\//i.test(path)) {
        try {
            const origin = options.origin || globalThis.location?.origin || new URL(options.baseUrl).origin;
            const parsed = new URL(path, origin);
            const origins = new Set([origin]);
            if (options.baseUrl) origins.add(new URL(options.baseUrl).origin);
            if (!origins.has(parsed.origin) || parsed.username || parsed.password) return path;
            if (base && parsed.pathname !== base && !parsed.pathname.startsWith(base + '/') && !mediaPath.test(parsed.pathname)) return path;
            path = parsed.pathname + parsed.search + parsed.hash;
        } catch { return path; }
    } else if (/^[a-z][a-z0-9+.-]*:/i.test(path)) {
        return path;
    }
    if (base && (path === base || path.startsWith(base + '/') || path.startsWith(base + '?') || path.startsWith(base + '#'))) {
        path = path.slice(base.length) || '/';
    }
    if (filename && mediaFilename.test(path)) return '/media-files/' + path;
    if (!path.startsWith('/') && !/^\.{1,2}\//.test(path)) path = '/' + path;
    return path;
}

export function resolveSitePath(value, options = {}, filename = false) {
    const path = portableSitePath(value, options, filename);
    if (!path.startsWith('/') || path.startsWith('//')) return path;
    const base = basePath(options);
    return base + path;
}

const urlAttributes = [['a[href]', 'href'], ['img[src]', 'src'], ['video[src],audio[src],source[src],iframe[src]', 'src'], ['video[poster]', 'poster']];

export function resolveEditorPaths(root, options) {
    for (const [selector, attribute] of urlAttributes) {
        root.querySelectorAll(selector).forEach((element) => {
            const value = element.getAttribute(attribute);
            if (!value) return;
            const canonical = portableSitePath(value, options, attribute !== 'href');
            element.setAttribute('data-canonical-' + attribute, canonical);
            element.setAttribute(attribute, resolveSitePath(canonical, options, attribute !== 'href'));
        });
    }
}

export function storeEditorPaths(root, options) {
    for (const [selector, attribute] of urlAttributes) {
        root.querySelectorAll(selector).forEach((element) => {
            const value = element.getAttribute(attribute);
            if (value) element.setAttribute(attribute, portableSitePath(value, options, attribute !== 'href'));
            element.removeAttribute('data-canonical-' + attribute);
        });
    }
}
