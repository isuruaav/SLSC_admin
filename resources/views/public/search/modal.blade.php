@php
    $modalLocale = $currentLocale ?? app()->getLocale();
    $isSi = $modalLocale === 'si';
    $suggestUrl = $isSi ? route('search.suggest.localized', ['locale' => 'si']) : route('search.suggest');
    $fullSearchUrl = $isSi ? route('search.localized', ['locale' => 'si']) : route('search');

    $quickPills = $isSi ? [
        ['label' => 'පාඨමාලා', 'query' => 'පාඨමාලා'],
        ['label' => 'සිග්නල් සඟරාව', 'query' => 'සඟරාව'],
        ['label' => 'සේනාවිධායකවරු', 'query' => 'සේනාවිධායක'],
        ['label' => 'පාසලේ ඉතිහාසය', 'query' => 'ඉතිහාසය'],
        ['label' => 'ගැසට් පත්‍ර', 'query' => 'ගැසට්'],
    ] : [
        ['label' => 'Courses', 'query' => 'Courses'],
        ['label' => 'Signals Journal', 'query' => 'Journal'],
        ['label' => 'Commandants', 'query' => 'Commandants'],
        ['label' => 'School History', 'query' => 'History'],
        ['label' => 'Gazette', 'query' => 'Gazette'],
    ];
@endphp

<div id="awQuickSearchModal" class="aw-search-modal-backdrop" aria-hidden="true" role="dialog" aria-modal="true" aria-label="{{ __('public.quick_search') }}">
    <div class="aw-search-modal-card">
        {{-- Search Input Header --}}
        <div class="aw-search-modal-header">
            <i class="fa-solid fa-magnifying-glass aw-modal-search-icon" aria-hidden="true"></i>
            <input type="search" 
                   id="awQuickSearchInput" 
                   class="aw-modal-input" 
                   placeholder="{{ __('public.search_placeholder') }}" 
                   autocomplete="off" 
                   spellcheck="false"
                   aria-label="{{ __('public.search') }}">
            <button type="button" id="awQuickSearchClear" class="aw-modal-clear-btn" aria-label="{{ __('public.clear') }}" style="display: none;">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <button type="button" id="awQuickSearchClose" class="aw-modal-close-btn" aria-label="{{ __('public.close_menu') }}">
                <span class="aw-kbd-badge">ESC</span>
            </button>
        </div>

        {{-- Modal Content Body --}}
        <div class="aw-search-modal-body">
            {{-- Default State: Suggested / Quick Chips --}}
            <div id="awModalDefaultState" class="aw-modal-default-state">
                <p class="aw-modal-section-title">
                    <i class="fa-solid fa-fire" aria-hidden="true"></i> {{ __('public.suggested_searches') }}
                </p>
                <div class="aw-modal-pills">
                    @foreach ($quickPills as $pill)
                        <button type="button" class="aw-quick-pill" data-query="{{ $pill['query'] }}">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> {{ $pill['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Loading State --}}
            <div id="awModalLoading" class="aw-modal-loading" style="display: none;">
                <div class="aw-spinner"></div>
                <span>{{ $isSi ? 'සොයමින් පවතී...' : 'Searching...' }}</span>
            </div>

            {{-- Live Results List --}}
            <div id="awModalResultsList" class="aw-modal-results-list" style="display: none;"></div>

            {{-- Empty / No Results State --}}
            <div id="awModalEmpty" class="aw-modal-empty-state" style="display: none;">
                <i class="fa-solid fa-magnifying-glass-chart" aria-hidden="true"></i>
                <p class="aw-empty-title">{{ __('public.no_results_found') }}</p>
                <p class="aw-empty-help">{{ __('public.no_results_help') }}</p>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="aw-search-modal-footer">
            <div class="aw-modal-footer-hints">
                <span><kbd class="aw-kbd">↑</kbd><kbd class="aw-kbd">↓</kbd> {{ $isSi ? 'තෝරන්න' : 'Navigate' }}</span>
                <span><kbd class="aw-kbd">↵</kbd> {{ $isSi ? 'විවෘත කරන්න' : 'Open' }}</span>
                <span><kbd class="aw-kbd">ESC</kbd> {{ $isSi ? 'වසන්න' : 'Close' }}</span>
            </div>
            <a href="{{ $fullSearchUrl }}" id="awModalViewAllLink" class="aw-modal-view-all" style="display: none;">
                {{ __('public.view_all_results') }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<style>
/* Quick Search Modal Overlay */
.aw-search-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    height: 100dvh;
    background: rgba(7, 27, 67, 0.72);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 999999;
    display: none;
    align-items: flex-start;
    justify-content: center;
    padding: 60px 16px 24px;
    opacity: 0;
    transition: opacity 0.22s ease;
    box-sizing: border-box;
}

.aw-search-modal-backdrop.is-open {
    display: flex;
    opacity: 1;
}

.aw-search-modal-card {
    width: 100%;
    max-width: 680px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: calc(85vh - 40px);
    transform: translateY(-12px) scale(0.98);
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.aw-search-modal-backdrop.is-open .aw-search-modal-card {
    transform: translateY(0) scale(1);
}

/* Modal Header */
.aw-search-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
    position: relative;
}

.aw-modal-search-icon {
    font-size: 20px;
    color: #14804c;
    flex: 0 0 auto;
}

.aw-modal-input {
    flex: 1;
    border: 0;
    outline: none;
    font-size: 17px;
    color: #0f172a;
    background: transparent;
    font-family: inherit;
    padding: 4px 0;
}

.aw-modal-input::placeholder {
    color: #94a3b8;
}

.aw-modal-clear-btn {
    border: 0;
    background: #f1f5f9;
    color: #64748b;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    transition: background 0.15s, color 0.15s;
}

.aw-modal-clear-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.aw-modal-close-btn {
    border: 0;
    background: transparent;
    padding: 0;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
}

.aw-kbd-badge {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    font-family: monospace;
}

/* Modal Body */
.aw-search-modal-body {
    padding: 18px 20px;
    overflow-y: auto;
    max-height: 440px;
    overscroll-behavior: contain;
}

/* Section Title */
.aw-modal-section-title {
    margin: 0 0 12px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.aw-modal-section-title i {
    color: #ca8a04;
}

/* Pills */
.aw-modal-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.aw-quick-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    transition: all 0.18s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: inherit;
}

.aw-quick-pill i {
    font-size: 11px;
    color: #94a3b8;
}

.aw-quick-pill:hover {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
    transform: translateY(-1px);
}

.aw-quick-pill:hover i {
    color: #166534;
}

/* Spinner / Loading */
.aw-modal-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 36px 16px;
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
}

.aw-spinner {
    width: 22px;
    height: 22px;
    border: 3px solid #e2e8f0;
    border-top-color: #14804c;
    border-radius: 50%;
    animation: awSpin 0.75s linear infinite;
}

@keyframes awSpin {
    to { transform: rotate(360deg); }
}

/* Results List */
.aw-modal-results-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.aw-modal-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    border: 1px solid transparent;
    transition: background 0.15s, border-color 0.15s;
}

.aw-modal-item:hover,
.aw-modal-item.is-selected {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.aw-modal-item-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex: 0 0 36px;
}

.aw-item-type-news { background: #dcfce7; color: #166534; }
.aw-item-type-courses { background: #dbeafe; color: #1e40af; }
.aw-item-type-documents { background: #fef3c7; color: #92400e; }
.aw-item-type-publications { background: #fce7f3; color: #9d174d; }
.aw-item-type-pages { background: #f1f5f9; color: #334155; }

.aw-modal-item-content {
    flex: 1;
    min-width: 0;
}

.aw-modal-item-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 2px;
}

.aw-modal-item-type-badge {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    padding: 1px 6px;
    border-radius: 4px;
}

.aw-modal-item-date {
    font-size: 11px;
    color: #94a3b8;
}

.aw-modal-item-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.aw-modal-item-title mark {
    background: #fef08a;
    color: #854d0e;
    padding: 0 2px;
    border-radius: 2px;
}

.aw-modal-item-arrow {
    color: #cbd5e1;
    font-size: 14px;
    transition: transform 0.15s, color 0.15s;
    flex: 0 0 auto;
}

.aw-modal-item:hover .aw-modal-item-arrow,
.aw-modal-item.is-selected .aw-modal-item-arrow {
    color: #14804c;
    transform: translateX(3px);
}

/* Empty State */
.aw-modal-empty-state {
    text-align: center;
    padding: 32px 16px;
}

.aw-modal-empty-state i {
    font-size: 38px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.aw-empty-title {
    margin: 0 0 6px;
    font-size: 17px;
    font-weight: 700;
    color: #1e293b;
}

.aw-empty-help {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

/* Modal Footer */
.aw-search-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: 12px;
    color: #64748b;
}

.aw-modal-footer-hints {
    display: flex;
    align-items: center;
    gap: 12px;
}

.aw-kbd {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    box-shadow: 0 1px 1px rgba(0,0,0,0.06);
    color: #334155;
    font-size: 11px;
    padding: 1px 5px;
    border-radius: 4px;
    font-family: monospace;
    font-weight: 600;
}

.aw-modal-view-all {
    color: #14804c;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.15s;
}

.aw-modal-view-all:hover {
    color: #0f683e;
}

@media (max-width: 640px) {
    .aw-search-modal-backdrop {
        padding: 16px 12px;
        align-items: center;
    }
    .aw-search-modal-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        text-align: center;
    }
    .aw-modal-footer-hints {
        justify-content: center;
    }
    .aw-modal-view-all {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('awQuickSearchModal');
    const input = document.getElementById('awQuickSearchInput');
    const clearBtn = document.getElementById('awQuickSearchClear');
    const closeBtn = document.getElementById('awQuickSearchClose');
    const defaultState = document.getElementById('awModalDefaultState');
    const loadingState = document.getElementById('awModalLoading');
    const resultsList = document.getElementById('awModalResultsList');
    const emptyState = document.getElementById('awModalEmpty');
    const viewAllLink = document.getElementById('awModalViewAllLink');

    const suggestEndpoint = '{{ $suggestUrl }}';
    const fullSearchBaseUrl = '{{ $fullSearchUrl }}';
    let debounceTimer = null;
    let selectedIndex = -1;

    function openModal() {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(() => input?.focus(), 50);
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (input) input.value = '';
        resetToDefault();
    }

    function resetToDefault() {
        if (clearBtn) clearBtn.style.display = 'none';
        if (loadingState) loadingState.style.display = 'none';
        if (resultsList) {
            resultsList.innerHTML = '';
            resultsList.style.display = 'none';
        }
        if (emptyState) emptyState.style.display = 'none';
        if (defaultState) defaultState.style.display = 'block';
        if (viewAllLink) viewAllLink.style.display = 'none';
        selectedIndex = -1;
    }

    // Bind all search toggle buttons across the page (header, floating, etc.)
    document.querySelectorAll('.search-toggle-btn, .managed-school-search-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });
    });

    // Keyboard Shortcuts: Ctrl+K or '/' to open, Escape to close
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (modal.classList.contains('is-open')) closeModal();
            else openModal();
            return;
        }

        if (e.key === '/' && !modal.classList.contains('is-open')) {
            const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            if (activeTag !== 'input' && activeTag !== 'textarea') {
                e.preventDefault();
                openModal();
                return;
            }
        }

        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            e.preventDefault();
            closeModal();
            return;
        }

        // Arrow navigation inside live results
        if (modal.classList.contains('is-open') && resultsList && resultsList.style.display !== 'none') {
            const items = resultsList.querySelectorAll('.aw-modal-item');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelectedItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateSelectedItem(items);
            } else if (e.key === 'Enter') {
                if (selectedIndex >= 0 && selectedIndex < items.length) {
                    e.preventDefault();
                    items[selectedIndex].click();
                } else if (input && input.value.trim().length >= 2) {
                    e.preventDefault();
                    window.location.href = fullSearchBaseUrl + '?q=' + encodeURIComponent(input.value.trim());
                }
            }
        } else if (modal.classList.contains('is-open') && e.key === 'Enter') {
            if (input && input.value.trim().length >= 2) {
                e.preventDefault();
                window.location.href = fullSearchBaseUrl + '?q=' + encodeURIComponent(input.value.trim());
            }
        }
    });

    function updateSelectedItem(items) {
        items.forEach((item, idx) => {
            if (idx === selectedIndex) {
                item.classList.add('is-selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('is-selected');
            }
        });
    }

    // Close buttons
    closeBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // Clear input
    clearBtn?.addEventListener('click', function () {
        if (input) {
            input.value = '';
            input.focus();
        }
        resetToDefault();
    });

    // Click on suggestion pills
    document.querySelectorAll('.aw-quick-pill').forEach(pill => {
        pill.addEventListener('click', function () {
            const query = this.getAttribute('data-query');
            if (input && query) {
                input.value = query;
                executeLiveSearch(query);
            }
        });
    });

    // Input debounce handler
    input?.addEventListener('input', function () {
        const val = this.value.trim();
        if (clearBtn) clearBtn.style.display = val.length > 0 ? 'inline-flex' : 'none';

        clearTimeout(debounceTimer);
        if (val.length < 2) {
            resetToDefault();
            if (clearBtn) clearBtn.style.display = val.length > 0 ? 'inline-flex' : 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            executeLiveSearch(val);
        }, 220);
    });

    function highlightMatch(text, query) {
        if (!text || !query) return text || '';
        const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp('(' + escaped + ')', 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    function executeLiveSearch(query) {
        defaultState.style.display = 'none';
        emptyState.style.display = 'none';
        resultsList.style.display = 'none';
        loadingState.style.display = 'flex';
        selectedIndex = -1;

        const url = new URL(suggestEndpoint, window.location.origin);
        url.searchParams.set('q', query);

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            loadingState.style.display = 'none';

            if (!data.results || data.results.length === 0) {
                emptyState.style.display = 'block';
                resultsList.style.display = 'none';
                viewAllLink.style.display = 'none';
                return;
            }

            resultsList.innerHTML = '';
            data.results.forEach(item => {
                const el = document.createElement('a');
                el.href = item.url;
                el.className = 'aw-modal-item';

                const icon = item.icon || 'fa-file-lines';
                const highlightedTitle = highlightMatch(item.title, query);
                const dateHtml = item.date ? `<span class="aw-modal-item-date"><i class="fa-regular fa-calendar"></i> ${item.date}</span>` : '';

                el.innerHTML = `
                    <div class="aw-modal-item-icon aw-item-type-${item.type}">
                        <i class="fa-solid ${icon}"></i>
                    </div>
                    <div class="aw-modal-item-content">
                        <div class="aw-modal-item-header">
                            <span class="aw-modal-item-type-badge aw-item-type-${item.type}">${item.type_label}</span>
                            ${dateHtml}
                        </div>
                        <h4 class="aw-modal-item-title">${highlightedTitle}</h4>
                    </div>
                    <i class="fa-solid fa-chevron-right aw-modal-item-arrow"></i>
                `;
                resultsList.appendChild(el);
            });

            resultsList.style.display = 'flex';

            if (data.total > 0 && viewAllLink) {
                viewAllLink.href = data.view_all_url || (fullSearchBaseUrl + '?q=' + encodeURIComponent(query));
                viewAllLink.style.display = 'inline-flex';
            } else if (viewAllLink) {
                viewAllLink.style.display = 'none';
            }
        })
        .catch(() => {
            loadingState.style.display = 'none';
            emptyState.style.display = 'block';
        });
    }
});
</script>

