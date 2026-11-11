export function enhanceAdminTables(doc) {
    if (!doc.body?.dataset.adminUser) return;
    doc.querySelectorAll('#admin-main table').forEach((table) => {
        if (table.closest('[contenteditable],trix-editor,[data-draft-model],[data-preserve-table]')) return;
        const headings = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim().replace(/\s+/g, ' '));
        if (!headings.length) return;
        table.classList.add('admin-responsive-table');
        table.setAttribute('role', 'table');
        const titleColumn = Math.max(0, headings.findIndex((heading) => /name|title|publication|document|organization|user/i.test(heading)));
        table.querySelectorAll('tbody > tr').forEach((row) => {
            row.setAttribute('role', 'row');
            [...row.children].forEach((cell, index) => {
                if (cell.tagName !== 'TD' || cell.colSpan > 1) return;
                cell.setAttribute('role', 'cell');
                if (index === titleColumn) cell.dataset.cardTitle = '';
                else if (!cell.dataset.label) cell.dataset.label = headings[index] || '';
            });
        });
    });
}

if (typeof document !== 'undefined') {
    let registered;
    const start = () => {
        if (window.Livewire && registered !== window.Livewire) {
            registered = window.Livewire;
            registered.hook('morphed', () => enhanceAdminTables(document));
        }
        enhanceAdminTables(document);
    };
    document.addEventListener('DOMContentLoaded', start);
    document.addEventListener('livewire:init', start);
    document.addEventListener('livewire:navigated', start);
    start();
}
