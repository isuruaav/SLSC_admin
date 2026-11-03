@php
    $activeTheme = config('awcms.active_theme');
    $themeLayout = $activeTheme ? "theme-{$activeTheme}::layout" : null;
    $searchLayout = ($themeLayout && view()->exists($themeLayout))
        ? $themeLayout
        : (view()->exists('theme-slsc::layout') ? 'theme-slsc::layout' : 'layouts.public');
@endphp
@extends($searchLayout)

@php
    $isSinhala = $currentLocale === 'si';
    $pageTitle = $query !== '' 
        ? __('public.search_results_for', ['query' => $query])
        : __('public.search');

    $quickPills = $isSinhala ? [
        ['label' => 'පාඨමාලා', 'query' => 'පාඨමාලා'],
        ['label' => 'සිග්නල් සඟරාව', 'query' => 'සඟරාව'],
        ['label' => 'සේනාවිධායකවරු', 'query' => 'සේනාවිධායක'],
        ['label' => 'පාසලේ ඉතිහාසය', 'query' => 'ඉතිහාසය'],
        ['label' => 'ගැසට් පත්‍ර', 'query' => 'ගැසට්'],
    ] : [
        ['label' => 'Courses', 'query' => 'Courses'],
        ['label' => 'Signals Journal', 'query' => 'Journal'],
        ['label' => 'Past Commandants', 'query' => 'Commandants'],
        ['label' => 'School History', 'query' => 'History'],
        ['label' => 'Gazette', 'query' => 'Gazette'],
    ];

    $highlight = function (?string $text) use ($query): string {
        if (!$text || $query === '') {
            return e($text ?? '');
        }
        $escaped = preg_quote($query, '/');
        $safeText = e($text);
        return preg_replace('/(' . $escaped . ')/iu', '<mark class="aw-search-mark">$1</mark>', $safeText);
    };
@endphp

@section('title', $pageTitle.' | '.config('app.name'))
@section('meta_description', $isSinhala ? 'සංඥා පාසලේ නිල පුවත්, පාඨමාලා, ලේඛන සහ ප්‍රකාශන සෙවීම.' : 'Search official news, courses, documents and publications at the School of Signals.')
@section('canonical', $currentLocale === 'si' ? route('search.localized', ['locale' => 'si']) : route('search'))

@push('styles')
    <style>
        .aw-search-page {
            background: #f8fafc;
            color: #111936;
            min-height: 75vh;
            padding-bottom: 70px;
        }
        .aw-search-hero {
            background: linear-gradient(135deg, #071b43 0%, #0d3866 50%, #146944 100%);
            color: #fff;
            padding: 56px 0 44px;
            box-shadow: 0 4px 25px rgba(7, 27, 67, 0.2);
            position: relative;
            overflow: hidden;
        }
        .aw-search-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }
        .aw-search-container {
            width: min(1080px, calc(100% - 40px));
            margin-inline: auto;
            position: relative;
        }
        .aw-search-kicker {
            color: #86efac;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin: 0 0 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .aw-search-hero h1 {
            color: #fff;
            font-size: clamp(28px, 4vw, 40px);
            font-weight: 800;
            margin: 0 0 24px;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }
        .aw-search-form {
            display: flex;
            gap: 12px;
            max-width: 820px;
            background: #ffffff;
            border-radius: 18px;
            padding: 6px 8px 6px 20px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.24);
            align-items: center;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .aw-search-form:focus-within {
            box-shadow: 0 20px 42px rgba(0, 0, 0, 0.32), 0 0 0 3px rgba(134, 239, 172, 0.4);
            transform: translateY(-1px);
        }
        .aw-search-form i.search-icon {
            color: #14804c;
            font-size: 20px;
        }
        .aw-search-input {
            flex: 1;
            border: 0;
            background: transparent;
            font-size: 17px;
            color: #0f172a;
            outline: none;
            padding: 12px 0;
            font-family: inherit;
        }
        .aw-search-input::placeholder {
            color: #94a3b8;
        }
        .aw-search-clear {
            border: 0;
            background: #f1f5f9;
            color: #64748b;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.15s, color 0.15s;
        }
        .aw-search-clear:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .aw-search-btn {
            background: #14804c;
            color: #fff;
            border: 0;
            padding: 13px 30px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        .aw-search-btn:hover {
            background: #0f683e;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(20, 128, 76, 0.35);
        }

        /* Hero Pills */
        .aw-hero-pills {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
            font-size: 13px;
        }
        .aw-hero-pills-label {
            color: rgba(255, 255, 255, 0.75);
            font-weight: 600;
            margin-right: 4px;
        }
        .aw-hero-pill {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.18s ease;
        }
        .aw-hero-pill:hover {
            background: #ffffff;
            color: #071b43;
            transform: translateY(-1px);
        }

        /* Results Meta Bar */
        .aw-search-meta-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin: 32px 0 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .aw-search-stat {
            color: #475569;
            font-size: 15px;
            font-weight: 600;
        }
        .aw-search-stat strong {
            color: #071b43;
        }

        /* Filter Tabs */
        .aw-search-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .aw-filter-tab {
            padding: 8px 16px;
            border-radius: 9999px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.18s;
        }
        .aw-filter-tab:hover {
            border-color: #0d3866;
            color: #0d3866;
            background: #f8fafc;
        }
        .aw-filter-tab.active {
            background: #071b43;
            color: #fff;
            border-color: #071b43;
            box-shadow: 0 4px 12px rgba(7, 27, 67, 0.15);
        }
        .aw-filter-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            font-size: 11.5px;
            padding: 1px 7px;
            border-radius: 9999px;
            font-weight: 700;
        }
        .aw-filter-tab.active .aw-filter-badge {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
        }

        /* Results List */
        .aw-results-list {
            display: grid;
            gap: 16px;
        }
        .aw-result-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 22px 26px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            text-decoration: none;
            display: flex;
            gap: 18px;
            align-items: flex-start;
            color: inherit;
        }
        .aw-result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(7, 27, 67, 0.09);
            border-color: #94a3b8;
        }
        .aw-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex: 0 0 44px;
            margin-top: 2px;
        }
        .aw-card-body {
            flex: 1;
            min-width: 0;
        }
        .aw-result-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .aw-type-badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .aw-type-news { background: #dcfce7; color: #166534; }
        .aw-type-courses { background: #dbeafe; color: #1e40af; }
        .aw-type-documents { background: #fef3c7; color: #92400e; }
        .aw-type-publications { background: #fce7f3; color: #9d174d; }
        .aw-type-pages { background: #f1f5f9; color: #334155; }

        .aw-result-date {
            color: #64748b;
            font-size: 12.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .aw-result-title {
            margin: 0 0 8px;
            color: #071b43;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.4;
            transition: color 0.15s;
        }
        .aw-result-card:hover .aw-result-title {
            color: #14804c;
        }
        .aw-result-excerpt {
            margin: 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.6;
        }
        .aw-card-arrow {
            color: #cbd5e1;
            font-size: 16px;
            transition: transform 0.2s, color 0.2s;
            align-self: center;
            flex: 0 0 auto;
        }
        .aw-result-card:hover .aw-card-arrow {
            color: #14804c;
            transform: translateX(4px);
        }

        /* Keyword Highlighting */
        mark.aw-search-mark {
            background: #fef08a;
            color: #854d0e;
            padding: 0 3px;
            border-radius: 3px;
            font-weight: 600;
        }

        /* Empty States */
        .aw-search-empty {
            background: #ffffff;
            border-radius: 20px;
            padding: 56px 32px;
            text-align: center;
            border: 1px dashed #cbd5e1;
            margin-top: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
        }
        .aw-search-empty i.empty-icon {
            font-size: 52px;
            color: #94a3b8;
            margin-bottom: 18px;
        }
        .aw-search-empty h2 {
            margin: 0 0 10px;
            color: #0f172a;
            font-size: 22px;
            font-weight: 700;
        }
        .aw-search-empty p.empty-desc {
            margin: 0 auto 28px;
            color: #64748b;
            font-size: 15px;
            max-width: 500px;
            line-height: 1.6;
        }
        .aw-empty-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .aw-btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #071b43;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.18s;
        }
        .aw-btn-action:hover {
            border-color: #14804c;
            color: #14804c;
            background: #f0fdf4;
            transform: translateY(-1px);
        }

        /* Default Explore Cards */
        .aw-explore-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 28px;
            text-align: left;
        }
        .aw-explore-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.18s;
        }
        .aw-explore-card:hover {
            background: #ffffff;
            border-color: #14804c;
            box-shadow: 0 8px 20px rgba(20, 128, 76, 0.08);
            transform: translateY(-2px);
        }
        .aw-explore-card i {
            font-size: 24px;
            color: #14804c;
        }
        .aw-explore-card h3 {
            margin: 0 0 4px;
            font-size: 15px;
            color: #071b43;
            font-weight: 700;
        }
        .aw-explore-card p {
            margin: 0;
            font-size: 12.5px;
            color: #64748b;
        }

        @media (max-width: 680px) {
            .aw-search-form {
                flex-direction: column;
                padding: 12px;
                border-radius: 14px;
            }
            .aw-search-btn {
                width: 100%;
                justify-content: center;
            }
            .aw-result-card {
                padding: 16px;
                gap: 12px;
            }
            .aw-card-icon {
                display: none;
            }
            .aw-search-meta-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
<div class="aw-search-page" lang="{{ $currentLocale }}">
    {{-- Hero Section --}}
    <header class="aw-search-hero">
        <div class="aw-search-container">
            <p class="aw-search-kicker">
                <i class="fa-solid fa-shield-halved"></i> {{ $isSinhala ? 'සංඥා පාසල · වෙබ් අඩවි සෙවුම' : 'School of Signals · Official Search' }}
            </p>
            <h1>{{ __('public.search') }}</h1>

            <form action="{{ $currentLocale === 'en' ? route('search') : route('search.localized', ['locale' => $currentLocale]) }}" 
                  method="GET" 
                  class="aw-search-form"
                  role="search">
                <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                <input type="search" 
                       id="pageSearchInput"
                       name="q" 
                       class="aw-search-input" 
                       value="{{ $query }}" 
                       placeholder="{{ __('public.search_placeholder') }}" 
                       aria-label="{{ __('public.search') }}"
                       autofocus 
                       required>
                @if ($query !== '')
                    <a href="{{ $currentLocale === 'en' ? route('search') : route('search.localized', ['locale' => $currentLocale]) }}" 
                       class="aw-search-clear" 
                       title="{{ __('public.clear') }}">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
                @if ($category !== 'all')
                    <input type="hidden" name="type" value="{{ $category }}">
                @endif
                <button type="submit" class="aw-search-btn">
                    <span>{{ __('public.search') }}</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            {{-- Suggested Topic Pills --}}
            <div class="aw-hero-pills">
                <span class="aw-hero-pills-label">{{ __('public.suggested_searches') }}:</span>
                @foreach ($quickPills as $pill)
                    <a href="{{ route($currentLocale === 'en' ? 'search' : 'search.localized', $currentLocale === 'si' ? ['locale' => 'si', 'q' => $pill['query']] : ['q' => $pill['query']]) }}" 
                       class="aw-hero-pill">
                        {{ $pill['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </header>

    {{-- Main Results Section --}}
    <main class="aw-search-main">
        <div class="aw-search-container">
            @if ($query !== '')
                {{-- Meta and Category Filters --}}
                <div class="aw-search-meta-bar">
                    <div class="aw-search-stat">
                        @if ($counts['all'] > 0)
                            {{ __('public.matching_results', ['count' => $counts['all']]) }}
                        @else
                            {{ __('public.no_results_found') }}
                        @endif
                    </div>

                    {{-- Category Filter Tabs --}}
                    <div class="aw-search-filters" role="tablist" aria-label="Filter results">
                        @php
                            $tabRoute = $currentLocale === 'en' ? 'search' : 'search.localized';
                            $baseParams = $currentLocale === 'si' ? ['locale' => 'si', 'q' => $query] : ['q' => $query];
                        @endphp

                        <a href="{{ route($tabRoute, $baseParams) }}" 
                           class="aw-filter-tab {{ $category === 'all' ? 'active' : '' }}">
                            <i class="fa-solid fa-layer-group"></i> {{ __('public.search_all') }}
                            <span class="aw-filter-badge">{{ $counts['all'] }}</span>
                        </a>

                        @if ($counts['news'] > 0 || $category === 'news')
                            <a href="{{ route($tabRoute, [...$baseParams, 'type' => 'news']) }}" 
                               class="aw-filter-tab {{ $category === 'news' ? 'active' : '' }}">
                                <i class="fa-solid fa-newspaper"></i> {{ $isSinhala ? 'පුවත්' : 'News' }}
                                <span class="aw-filter-badge">{{ $counts['news'] }}</span>
                            </a>
                        @endif

                        @if ($counts['courses'] > 0 || $category === 'courses')
                            <a href="{{ route($tabRoute, [...$baseParams, 'type' => 'courses']) }}" 
                               class="aw-filter-tab {{ $category === 'courses' ? 'active' : '' }}">
                                <i class="fa-solid fa-graduation-cap"></i> {{ $isSinhala ? 'පාඨමාලා' : 'Courses' }}
                                <span class="aw-filter-badge">{{ $counts['courses'] }}</span>
                            </a>
                        @endif

                        @if ($counts['documents'] > 0 || $category === 'documents')
                            <a href="{{ route($tabRoute, [...$baseParams, 'type' => 'documents']) }}" 
                               class="aw-filter-tab {{ $category === 'documents' ? 'active' : '' }}">
                                <i class="fa-solid fa-file-lines"></i> {{ $isSinhala ? 'ලේඛන' : 'Documents' }}
                                <span class="aw-filter-badge">{{ $counts['documents'] }}</span>
                            </a>
                        @endif

                        @if ($counts['publications'] > 0 || $category === 'publications')
                            <a href="{{ route($tabRoute, [...$baseParams, 'type' => 'publications']) }}" 
                               class="aw-filter-tab {{ $category === 'publications' ? 'active' : '' }}">
                                <i class="fa-solid fa-book-open"></i> {{ $isSinhala ? 'ප්‍රකාශන' : 'Publications' }}
                                <span class="aw-filter-badge">{{ $counts['publications'] }}</span>
                            </a>
                        @endif

                        @if ($counts['pages'] > 0 || $category === 'pages')
                            <a href="{{ route($tabRoute, [...$baseParams, 'type' => 'pages']) }}" 
                               class="aw-filter-tab {{ $category === 'pages' ? 'active' : '' }}">
                                <i class="fa-solid fa-file"></i> {{ $isSinhala ? 'පිටු' : 'Pages' }}
                                <span class="aw-filter-badge">{{ $counts['pages'] }}</span>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Results --}}
                @if ($results->isNotEmpty())
                    <div class="aw-results-list">
                        @foreach ($results as $item)
                            <a href="{{ $item['url'] }}" class="aw-result-card">
                                <div class="aw-card-icon aw-type-{{ $item['type'] }}">
                                    <i class="fa-solid {{ $item['icon'] ?? 'fa-file-lines' }}"></i>
                                </div>
                                <div class="aw-card-body">
                                    <div class="aw-result-meta">
                                        <span class="aw-type-badge aw-type-{{ $item['type'] }}">{{ $item['type_label'] }}</span>
                                        @if ($item['date'])
                                            <span class="aw-result-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $item['date'] }}</span>
                                        @endif
                                    </div>
                                    <h2 class="aw-result-title">{!! $highlight($item['title']) !!}</h2>
                                    @if ($item['excerpt'])
                                        <p class="aw-result-excerpt">{!! $highlight($item['excerpt']) !!}</p>
                                    @endif
                                </div>
                                <i class="fa-solid fa-arrow-right aw-card-arrow" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                @else
                    {{-- 0 Matches State --}}
                    <div class="aw-search-empty">
                        <i class="fa-solid fa-magnifying-glass-chart empty-icon" aria-hidden="true"></i>
                        <h2>{{ __('public.no_results_found') }}</h2>
                        <p class="empty-desc">{{ __('public.no_results_help') }}</p>

                        <div class="aw-empty-actions">
                            <a href="{{ $currentLocale === 'en' ? route('courses.index') : route('courses.index.localized', ['locale' => 'si']) }}" class="aw-btn-action">
                                <i class="fa-solid fa-graduation-cap"></i> {{ __('public.browse_courses') }}
                            </a>
                            <a href="{{ $currentLocale === 'en' ? route('news.index') : route('news.index.localized', ['locale' => 'si']) }}" class="aw-btn-action">
                                <i class="fa-solid fa-newspaper"></i> {{ __('public.browse_news') }}
                            </a>
                            <a href="{{ $currentLocale === 'en' ? route('publications.index') : route('publications.index.localized', ['locale' => 'si']) }}" class="aw-btn-action">
                                <i class="fa-solid fa-book-open"></i> {{ __('public.browse_publications') }}
                            </a>
                        </div>
                    </div>
                @endif
            @else
                {{-- Initial Search Prompt (empty query) --}}
                <div class="aw-search-empty">
                    <i class="fa-solid fa-magnifying-glass empty-icon" aria-hidden="true"></i>
                    <h2>{{ __('public.search') }}</h2>
                    <p class="empty-desc">{{ __('public.enter_search_term') }}</p>

                    <div class="aw-explore-grid">
                        <a href="{{ $currentLocale === 'en' ? route('courses.index') : route('courses.index.localized', ['locale' => 'si']) }}" class="aw-explore-card">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <div>
                                <h3>{{ $isSinhala ? 'පුහුණු පාඨමාලා' : 'Training Courses' }}</h3>
                                <p>{{ $isSinhala ? 'නිලධාරී සහ සෙසු නිල පාඨමාලා විස්තර' : 'Officer & soldier training programs' }}</p>
                            </div>
                        </a>
                        <a href="{{ $currentLocale === 'en' ? route('news.index') : route('news.index.localized', ['locale' => 'si']) }}" class="aw-explore-card">
                            <i class="fa-solid fa-newspaper"></i>
                            <div>
                                <h3>{{ $isSinhala ? 'නවතම පුවත්' : 'Latest News' }}</h3>
                                <p>{{ $isSinhala ? 'පාසලේ විශේෂ සිදුවීම් සහ සැමරුම්' : 'Events, ceremonies & announcements' }}</p>
                            </div>
                        </a>
                        <a href="{{ $currentLocale === 'en' ? route('publications.index') : route('publications.index.localized', ['locale' => 'si']) }}" class="aw-explore-card">
                            <i class="fa-solid fa-book-open"></i>
                            <div>
                                <h3>{{ $isSinhala ? 'ප්‍රකාශන සහ සඟරා' : 'Publications' }}</h3>
                                <p>{{ $isSinhala ? 'නිල සඟරා සහ පර්යේෂණ ලේඛන' : 'Official journals & research papers' }}</p>
                            </div>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </main>
</div>
@endsection
