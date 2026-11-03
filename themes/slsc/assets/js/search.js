/**
 * Spotlight Search Modal & Auto-complete Component
 */

export function initializeSearch(doc = document, win = window) {
    const modal = doc.querySelector('[data-search-modal]');
    if (!modal) return;

    const modalInput = modal.querySelector('[data-modal-search-input]');
    const modalForm = modal.querySelector('[data-modal-search-form]');
    const modalResults = modal.querySelector('[data-modal-search-results]');
    const suggestedSection = modal.querySelector('[data-suggested-topics]');
    const closeButtons = modal.querySelectorAll('[data-close-search-modal]');
    const openButtons = doc.querySelectorAll('[data-open-search-modal], [data-open-search], .search-trigger, .search-toggle-btn, .managed-school-search-btn');
    const suggestedPills = modal.querySelectorAll('.slsc-suggested-pill');

    const suggestUrl = modal.dataset.suggestUrl;
    const locale = modal.dataset.locale || 'en';
    const isSinhala = locale === 'si';

    let debounceTimer = null;
    let activeIndex = -1;
    let lastQuery = '';
    let currentAbortController = null;
    let previousActiveElement = null;

    const labels = {
        loading: isSinhala ? 'සොයමින් පවතී...' : 'Searching...',
        noResults: (q) => isSinhala 
            ? `"${escapeHtml(q)}" සඳහා කිසිවක් හමු නොවීය.` 
            : `No results found for "${escapeHtml(q)}".`,
        enterToSearch: isSinhala ? 'සම්පූර්ණ සෙවුම සඳහා Enter ඔබන්න.' : 'Press Enter to view full search results.',
        viewAll: (total, q) => isSinhala
            ? `"${escapeHtml(q)}" සඳහා ප්‍රතිඵල ${total} ම බලන්න`
            : `View all ${total} results for "${escapeHtml(q)}"`,
        resultsHeader: isSinhala ? 'ක්ෂණික ප්‍රතිඵල' : 'Quick Results'
    };

    function escapeHtml(str) {
        const div = doc.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function highlightMatch(text, query) {
        if (!query || !text) return escapeHtml(text || '');
        const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escaped})`, 'gi');
        return escapeHtml(text).replace(regex, '<mark>$1</mark>');
    }

    function openModal() {
        previousActiveElement = doc.activeElement;
        modal.hidden = false;
        modal.classList.add('is-open');
        modal.style.display = 'flex';
        doc.body.style.overflow = 'hidden';

        if (modalInput) {
            modalInput.value = '';
            lastQuery = '';
            showSuggestedTopics();
            setTimeout(() => {
                modalInput.focus();
            }, 60);
        }
    }

    function closeModal() {
        modal.hidden = true;
        modal.classList.remove('is-open');
        modal.style.display = 'none';
        doc.body.style.overflow = '';
        if (currentAbortController) currentAbortController.abort();
        if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
            previousActiveElement.focus();
        }
    }

    // Expose globally for cross-script integration
    win.slscSearch = {
        open: openModal,
        close: closeModal
    };

    function showSuggestedTopics() {
        if (suggestedSection) suggestedSection.hidden = false;
        if (modalResults) modalResults.hidden = true;
        activeIndex = -1;
    }

    function showResultsArea() {
        if (suggestedSection) suggestedSection.hidden = true;
        if (modalResults) modalResults.hidden = false;
    }

    function renderLoading() {
        showResultsArea();
        modalResults.innerHTML = `
            <div class="search-dropdown-loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <span>${labels.loading}</span>
            </div>
        `;
    }

    function renderResults(data, query) {
        showResultsArea();

        if (!data || !data.results || data.results.length === 0) {
            modalResults.innerHTML = `
                <div class="search-dropdown-empty">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 24px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 600; color: #1e293b;">${labels.noResults(query)}</div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 5px;">${labels.enterToSearch}</div>
                </div>
            `;
            return;
        }

        let html = `
            <div class="search-dropdown-header">
                <span>${labels.resultsHeader}</span>
                <span style="font-size: 11px; color: #94a3b8;">${data.total} matches</span>
            </div>
            <div class="search-results-list" role="listbox">
        `;

        data.results.forEach((item, index) => {
            const typeClass = `type-${item.type || 'pages'}`;
            const iconClass = item.icon || 'fa-file';
            const highlightedTitle = highlightMatch(item.title, query);
            const highlightedExcerpt = highlightMatch(item.excerpt, query);

            html += `
                <a href="${escapeHtml(item.url)}" class="search-result-item" role="option" data-result-index="${index}">
                    <div class="search-result-icon ${typeClass}">
                        <i class="fa-solid ${iconClass}"></i>
                    </div>
                    <div class="search-result-body">
                        <div class="search-result-meta">
                            <span class="search-badge ${typeClass}">${escapeHtml(item.type_label)}</span>
                            ${item.date ? `<span class="search-result-date"><i class="fa-regular fa-calendar" style="margin-right: 3px;"></i>${escapeHtml(item.date)}</span>` : ''}
                        </div>
                        <h4 class="search-result-title">${highlightedTitle}</h4>
                        ${item.excerpt ? `<p class="search-result-excerpt">${highlightedExcerpt}</p>` : ''}
                    </div>
                </a>
            `;
        });

        html += `</div>`;

        if (data.view_all_url) {
            html += `
                <div class="search-dropdown-footer">
                    <a href="${escapeHtml(data.view_all_url)}" class="search-view-all-link">
                        <span>${labels.viewAll(data.total, query)}</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            `;
        }

        modalResults.innerHTML = html;
        activeIndex = -1;
    }

    async function fetchSuggestions(query) {
        if (currentAbortController) {
            currentAbortController.abort();
        }

        currentAbortController = new AbortController();

        try {
            const url = new URL(suggestUrl, win.location.origin);
            url.searchParams.set('q', query);

            const res = await fetch(url.toString(), {
                signal: currentAbortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error('Search failed');

            const data = await res.json();
            if (modalInput.value.trim() === query) {
                renderResults(data, query);
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                modalResults.innerHTML = `
                    <div class="search-dropdown-empty">
                        <div>${labels.enterToSearch}</div>
                    </div>
                `;
            }
        }
    }

    // Direct click listeners on initially discovered buttons
    openButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });
    });

    // Delegated click listener on document for maximum reliability (works with dynamic header / layout items)
    doc.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-open-search-modal], [data-open-search], .search-trigger, .search-toggle-btn, .managed-school-search-btn');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            openModal();
        }
    }, true);

    // Close Trigger Buttons
    closeButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal();
        });
    });

    // Close on Backdrop Click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Suggested Pills Click
    suggestedPills.forEach(pill => {
        pill.addEventListener('click', (e) => {
            e.preventDefault();
            const topic = pill.dataset.topic;
            if (topic && modalInput) {
                modalInput.value = topic;
                modalInput.focus();
                renderLoading();
                fetchSuggestions(topic);
            }
        });
    });

    // Input Typing
    if (modalInput) {
        modalInput.addEventListener('input', () => {
            const query = modalInput.value.trim();

            if (query.length < 2) {
                showSuggestedTopics();
                if (currentAbortController) currentAbortController.abort();
                return;
            }

            if (query === lastQuery && !modalResults.hidden) {
                return;
            }

            lastQuery = query;
            renderLoading();

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchSuggestions(query);
            }, 200);
        });

        // Arrow Key & Enter Navigation in Modal
        modalInput.addEventListener('keydown', (e) => {
            const items = modalResults ? modalResults.querySelectorAll('.search-result-item') : [];

            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal();
                return;
            }

            if (items.length > 0 && !modalResults.hidden) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = (activeIndex + 1) % items.length;
                    updateActiveItem(items);
                    return;
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = (activeIndex - 1 + items.length) % items.length;
                    updateActiveItem(items);
                    return;
                } else if (e.key === 'Enter') {
                    if (activeIndex >= 0 && items[activeIndex]) {
                        e.preventDefault();
                        items[activeIndex].click();
                        return;
                    }
                }
            }
        });
    }

    function updateActiveItem(items) {
        items.forEach((item, idx) => {
            const isActive = idx === activeIndex;
            item.classList.toggle('is-selected', isActive);
            if (isActive) {
                item.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    // Global Keyboard Shortcuts (Esc to close, / or Ctrl+K to open)
    doc.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
            return;
        }

        // Open shortcut (Ctrl+K or Cmd+K)
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (modal.hidden) openModal();
            else closeModal();
            return;
        }

        // Open shortcut ('/' key when not focused in input/textarea)
        if (e.key === '/' && modal.hidden) {
            const tag = doc.activeElement?.tagName?.toLowerCase();
            if (tag !== 'input' && tag !== 'textarea' && !doc.activeElement?.isContentEditable) {
                e.preventDefault();
                openModal();
            }
        }
    });
}

// Auto-run when loaded
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initializeSearch(), { once: true });
    } else {
        initializeSearch();
    }
}
