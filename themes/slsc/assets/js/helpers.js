export function normalizeText(value = '') {
    // Preserve Sinhala combining characters; remove only redundant whitespace.
    return String(value).normalize('NFKC').toLocaleLowerCase('en').replace(/\s+/gu, ' ').trim();
}

export function matchesQuery(text, query) {
    const normalized = normalizeText(text);
    return normalizeText(query).split(' ').filter(Boolean).every(term => normalized.includes(term));
}

export function formatMessage(template, values = {}) {
    return String(template).replace(/\{(\w+)\}/g, (match, key) => Object.hasOwn(values, key) ? String(values[key]) : match);
}

export function formatDisplayDate(value, language) {
    const source = /^\d{4}-\d{2}-\d{2}$/.test(value) ? `${value}T00:00:00+05:30` : value;
    const date = new Date(source);
    if (Number.isNaN(date.getTime())) return null;
    return new Intl.DateTimeFormat(language === 'si' ? 'si-LK' : 'en-GB', {
        day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Colombo',
    }).format(date);
}

export function activeSectionId(sections, scrollY, offset = 0) {
    return sections.reduce((active, section) => section.top <= scrollY + offset ? section.id : active, 'top');
}
