@php
    $activeSettings = $siteSettings ?? $settings ?? null;
    $publicSiteName = $activeSettings?->localized('site_name') ?: __('theme-slsc::interface.brandName');
    $publicDescription = $activeSettings?->localized('site_tagline') ?: __('theme-slsc::interface.metaDescription');
    $currentLocale = in_array(app()->getLocale(), ['en', 'si', 'ta'], true) ? app()->getLocale() : 'en';
    $publicHomeUrl = $currentLocale === 'en' ? route('home') : route('home.localized', ['locale' => $currentLocale]);
    $publicLanguageLinks = app(\App\Services\SiteLanguageService::class)->switcherLinks(($languageOptions ?? []) ?: ($languageVersions ?? []));
    $themeAssetBase = asset('themes/slsc/assets');
    $mediaUrls = app(\App\Services\MediaUrlService::class);
    $logoUrl = $activeSettings?->logo
        ? $mediaUrls->mediumOrOriginal($activeSettings->logo)
        : $themeAssetBase . '/images/logo/signals-logo.png';
    $faviconUrl = $activeSettings?->favicon ? $mediaUrls->original($activeSettings->favicon) : $themeAssetBase . '/images/logo/signals-logo.png';

    $enUrl = route('home');
    $siUrl = route('home.localized', ['locale' => 'si']);

    $cleanSiteSettings = $activeSettings instanceof \App\Models\SiteSetting ? $activeSettings : null;
    $cleanPrimaryMenu = isset($primaryMenu) && $primaryMenu instanceof \App\Models\Menu ? $primaryMenu : null;
    $cleanSocialLinks = isset($publicSocialLinks) && is_iterable($publicSocialLinks) && ! ($publicSocialLinks instanceof \__PHP_Incomplete_Class) ? $publicSocialLinks : [];

    $themeLayoutRenderer = app(\App\Services\ThemeLayoutRenderer::class);
    $managedHeaderLayout = $themeLayoutRenderer->render(
        themeSlug: config('awcms.active_theme', 'slsc'),
        region: \App\Enums\ThemeLayoutRegion::Header,
        siteSettings: $cleanSiteSettings,
        primaryMenu: $cleanPrimaryMenu,
        socialLinks: $cleanSocialLinks,
        languageOptions: $publicLanguageLinks,
        currentLocale: $currentLocale,
        siteName: $publicSiteName,
        siteTagline: $publicDescription,
        logoUrl: $logoUrl,
    );

    $managedFooterLayout = $themeLayoutRenderer->render(
        themeSlug: config('awcms.active_theme', 'slsc'),
        region: \App\Enums\ThemeLayoutRegion::Footer,
        siteSettings: $cleanSiteSettings,
        primaryMenu: $cleanPrimaryMenu,
        socialLinks: $cleanSocialLinks,
        languageOptions: $publicLanguageLinks,
        currentLocale: $currentLocale,
        siteName: $publicSiteName,
        siteTagline: $publicDescription,
        logoUrl: $logoUrl,
    );

    $navItems = [
        ['top', 'navHome', 'Home', $publicHomeUrl],
        ['about', 'navAbout', 'Leadership', $publicHomeUrl.'#about'],
        ['news', 'navNews', 'News & Events', $publicHomeUrl.'#news'],
        ['special-programs', 'navPrograms', 'Special Programs', $publicHomeUrl.'#special-programs'],
        ['publications', 'navPublications', 'Publications', $publicHomeUrl.'#publications'],
        ['join-us', 'navJoin', 'Join Us', $publicHomeUrl.'#join-us'],
        ['contact', 'navContact', 'Contact', $publicHomeUrl.'#contact'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" class="{{ $currentLocale === 'si' ? 'is-si' : '' }}" data-site-locale="{{ $currentLocale }}" data-site-name="{{ $publicSiteName }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $publicSiteName . ' | Swift & Sure')</title>
    <meta name="description" content="@yield('meta_description', $publicDescription)">
    <meta name="theme-color" content="#071b3a">
    @hasSection('canonical')<link rel="canonical" href="@yield('canonical')">@endif
    @hasSection('meta')
        @yield('meta')
    @else
        @foreach ($publicLanguageLinks as $languageLink)
            @if (($languageLink['available'] ?? false) && is_string($languageLink['url'] ?? null))
                <link rel="alternate" hreflang="{{ $languageLink['code'] }}" href="{{ $languageLink['url'] }}">
            @endif
        @endforeach
    @endif

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $publicSiteName }}">
    <meta property="og:title" content="@yield('title', $publicSiteName)">
    <meta property="og:description" content="@yield('meta_description', $publicDescription)">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $logoUrl }}">

    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="preload" as="image" href="{{ $themeAssetBase }}/images/programs-sample.jpg" fetchpriority="high">
    <link rel="preload" as="font" type="font/woff2" href="{{ $themeAssetBase }}/fonts/inter-latin.woff2" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ $themeAssetBase }}/fonts/noto-sinhala.woff2" crossorigin>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo:ital,wght@0,100..900;1,100..900&family=Maname&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ $themeAssetBase }}/fonts/fontawesome-7/css/all.min.css?v=7.0.0">
    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/fonts.css?v={{ file_exists(public_path('themes/slsc/assets/css/fonts.css')) ? filemtime(public_path('themes/slsc/assets/css/fonts.css')) : time() }}">
    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/base.css?v={{ file_exists(public_path('themes/slsc/assets/css/base.css')) ? filemtime(public_path('themes/slsc/assets/css/base.css')) : time() }}">
    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/navbar.css?v={{ file_exists(public_path('themes/slsc/assets/css/navbar.css')) ? filemtime(public_path('themes/slsc/assets/css/navbar.css')) : time() }}">
    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/news.css?v={{ file_exists(public_path('themes/slsc/assets/css/news.css')) ? filemtime(public_path('themes/slsc/assets/css/news.css')) : time() }}">
    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/registration.css?v={{ file_exists(public_path('themes/slsc/assets/css/registration.css')) ? filemtime(public_path('themes/slsc/assets/css/registration.css')) : time() }}">
    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/responsive.css?v={{ file_exists(public_path('themes/slsc/assets/css/responsive.css')) ? filemtime(public_path('themes/slsc/assets/css/responsive.css')) : time() }}">
    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/style.css?v={{ file_exists(public_path('themes/slsc/assets/css/style.css')) ? filemtime(public_path('themes/slsc/assets/css/style.css')) : time() }}">
    <script type="module" src="{{ $themeAssetBase }}/js/script.js?v={{ file_exists(public_path('themes/slsc/assets/js/script.js')) ? filemtime(public_path('themes/slsc/assets/js/script.js')) : time() }}"></script>
    <script type="module" src="{{ $themeAssetBase }}/js/search.js?v={{ file_exists(public_path('themes/slsc/assets/js/search.js')) ? filemtime(public_path('themes/slsc/assets/js/search.js')) : time() }}"></script>

    @if ($managedHeaderLayout !== null && $managedHeaderLayout['css'] !== '')
        <style data-awcms-theme-layout="header">
            {!! $managedHeaderLayout['css'] !!}
        </style>
    @endif
    @if ($managedFooterLayout !== null && $managedFooterLayout['css'] !== '')
        <style data-awcms-theme-layout="footer">
            {!! $managedFooterLayout['css'] !!}
        </style>
    @endif

    @stack('styles')
</head>
<body id="top" class="{{ $currentLocale === 'si' ? 'is-si' : '' }}">
    @include('theme-slsc::partials.icons')

    <a class="skip-link" href="#mainContent" data-i18n="skipContent">{{ __('theme-slsc::interface.skipContent') }}</a>

    @if ($managedHeaderLayout !== null)
        {!! $managedHeaderLayout['html'] !!}
    @else
    {{-- Top Utility Bar --}}
    <div class="topbar">
        <div class="container topbar-inner">
            <span class="topbar-label">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-shield"></use></svg>
                <span data-i18n="topOfficial">{{ __('theme-slsc::interface.topOfficial') }}</span>
            </span>
            <a href="https://www.army.lk/" target="_blank" rel="noopener noreferrer">
                <span data-i18n="armyPortal">{{ __('theme-slsc::interface.armyPortal') }}</span>
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
            </a>
        </div>
    </div>

    {{-- Site Header --}}
    <header class="site-header" id="siteHeader">
        <div class="container brand-bar">
            <a class="brand" href="{{ $publicHomeUrl }}">
                <img class="brand-logo" src="{{ $logoUrl }}" alt="{{ __('public.logo_label', ['name' => $publicSiteName]) }}" width="3239" height="4412">
                <span class="brand-copy">
                    <span class="brand-eyebrow">{{ __('theme-slsc::interface.armyName') }}</span>
                    <span class="brand-title">{{ $publicSiteName }}</span>
                    <span class="brand-motto" data-i18n="motto">{{ __('theme-slsc::interface.motto') }}</span>
                </span>
            </a>
            <div class="header-actions">
                <button class="search-trigger enhanced-only" type="button" data-open-search data-open-search-modal data-i18n-aria="searchSite" aria-label="{{ __('theme-slsc::interface.searchSite') }}" aria-haspopup="dialog">
                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
                    <span data-i18n="searchShort">{{ __('theme-slsc::interface.searchShort') }}</span>
                    <kbd>Ctrl K</kbd>
                </button>
                <nav class="language-switcher" aria-label="{{ __('public.language_selection') }}">
                    @foreach ($publicLanguageLinks as $languageLink)
                        @if (($languageLink['available'] ?? false) && is_string($languageLink['url'] ?? null))
                            <a href="{{ $languageLink['url'] }}" class="lang-btn {{ $currentLocale === $languageLink['code'] ? 'active' : '' }}"
                                data-lang="{{ $languageLink['code'] }}" data-language-option lang="{{ $languageLink['code'] }}" hreflang="{{ $languageLink['code'] }}"
                                @if ($currentLocale === $languageLink['code']) aria-current="page" @endif>{{ $languageLink['code'] === 'si' ? 'සිංහල' : 'English' }}</a>
                        @endif
                    @endforeach
                </nav>
                <button id="mobileMenuBtn" class="menu-button enhanced-only" type="button" aria-controls="navPanel" aria-expanded="false" aria-label="{{ __('theme-slsc::interface.openMenu') }}" data-i18n-aria="openMenu">
                    <svg class="icon menu-open-icon" aria-hidden="true" focusable="false"><use href="#i-menu"></use></svg>
                    <svg class="icon menu-close-icon" aria-hidden="true" focusable="false"><use href="#i-close"></use></svg>
                </button>
            </div>
        </div>
        <nav class="main-nav" aria-label="{{ __('theme-slsc::interface.mainNavigation') }}" data-i18n-aria="mainNavigation">
            <div class="container nav-shell">
                <div id="navPanel" class="nav-panel">
                    @foreach ($navItems as [$id, $key, $label, $url])
                        <a class="nav-link {{ request()->is('/') && $id === 'top' ? 'current' : '' }}"
                           href="{{ $url }}"
                           data-i18n="{{ $key }}"
                           data-page-link
                           {{ request()->is('/') && $id === 'top' ? 'aria-current="location"' : '' }}>
                            {{ __('theme-slsc::interface.'.$key) }}
                        </a>
                    @endforeach
                </div>

            </div>
        </nav>
        <div class="scroll-progress" aria-hidden="true"><span id="scrollProgress"></span></div>
    </header>
    @endif

    {{-- Main Content --}}
    <main id="mainContent" tabindex="-1">
        @yield('content')
    </main>

    @if ($managedFooterLayout !== null)
        {!! $managedFooterLayout['html'] !!}
    @else
    {{-- Site Footer --}}
    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a class="brand" href="{{ $publicHomeUrl }}">
                    <img class="brand-logo" src="{{ $logoUrl }}" alt="" width="3239" height="4412" loading="lazy">
                    <span class="brand-copy">
                        <span class="brand-title">{{ $publicSiteName }}</span>
                        <span class="brand-motto" data-i18n="motto">{{ __('theme-slsc::interface.motto') }}</span>
                    </span>
                </a>
                <p data-i18n="footerDesc">{{ __('theme-slsc::interface.footerDesc') }}</p>
            </div>
            <div>
                <h2 data-i18n="footerLinks">{{ __('theme-slsc::interface.footerLinks') }}</h2>
                <div class="footer-links">
                    @foreach (array_slice($navItems, 1) as [$id, $key, $label, $url])
                        <a href="{{ $url }}" data-i18n="{{ $key }}">{{ __('theme-slsc::interface.'.$key) }}</a>
                    @endforeach
                </div>
            </div>
            <div>
                <h2 data-i18n="footerResources">{{ __('theme-slsc::interface.footerResources') }}</h2>
                <div class="footer-links">
                    <a href="https://www.army.lk/" target="_blank" rel="noopener noreferrer">
                        <span data-i18n="armyPortal">{{ __('theme-slsc::interface.armyPortal') }}</span>
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
                    </a>
                    <a href="https://alt.army.lk/signaller/organization" target="_blank" rel="noopener noreferrer">
                        <span data-i18n="navOrganization">{{ __('theme-slsc::interface.navOrganization') }}</span>
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
                    </a>
                    <a href="https://alt.army.lk/slsc_va/" target="_blank" rel="noopener noreferrer">
                        <span data-i18n="programVeterans">{{ __('theme-slsc::interface.programVeterans') }}</span>
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
                    </a>
                    <a href="https://alt.army.lk/signaller/schoolofsignals/" target="_blank" rel="noopener noreferrer">
                        <span data-i18n="schoolSignals">{{ __('theme-slsc::interface.schoolSignals') }}</span>
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container footer-bottom-inner">
                <span>© {{ date('Y') }} <span data-i18n="footerCopyright">{{ __('theme-slsc::interface.footerCopyright') }}</span></span>
                <a href="#top">
                    <span data-i18n="backTop">{{ __('theme-slsc::interface.backTop') }}</span>
                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-up"></use></svg>
                </a>
            </div>
        </div>
    </footer>
    @endif

    <button id="backToTop" class="back-to-top enhanced-only" type="button" aria-label="{{ __('theme-slsc::interface.backTop') }}" data-i18n-aria="backTop">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-up"></use></svg>
    </button>

    {{-- Global Site Search Modal --}}
    <div id="slscSearchModal" class="slsc-search-modal-backdrop" role="dialog" aria-modal="true" aria-label="{{ $currentLocale === 'si' ? 'වෙබ් අඩවිය තුළ සොයන්න' : 'Search the portal' }}" hidden data-search-modal data-suggest-url="{{ $currentLocale === 'si' ? url('/si/search/suggest') : url('/search/suggest') }}" data-search-url="{{ $currentLocale === 'si' ? url('/si/search') : url('/search') }}" data-locale="{{ $currentLocale }}">
        <div class="slsc-search-modal-card">
            <form action="{{ $currentLocale === 'si' ? url('/si/search') : url('/search') }}" method="GET" class="slsc-search-modal-form" role="search" data-modal-search-form>
                <div class="slsc-search-modal-input-row">
                    <i class="fa-solid fa-magnifying-glass slsc-search-modal-icon" aria-hidden="true"></i>
                    <input type="search" name="q" class="slsc-search-modal-input" placeholder="{{ $currentLocale === 'si' ? 'පුවත්, පාඨමාලා, ලේඛන, ප්‍රකාශන සොයන්න...' : 'Search news, courses, documents, publications...' }}" autocomplete="off" autocorrect="off" spellcheck="false" data-modal-search-input>
                    <button type="button" class="slsc-search-modal-esc" data-close-search-modal aria-label="{{ $currentLocale === 'si' ? 'වසන්න' : 'Close' }}">
                        <span>ESC</span>
                    </button>
                </div>
            </form>

            <!-- Suggested Topics Section -->
            <div class="slsc-search-suggested" data-suggested-topics>
                <div class="slsc-suggested-label">
                    <span class="slsc-fire-icon">🔥</span>
                    <span>{{ $currentLocale === 'si' ? 'යෝජිත මාතෘකා' : 'SUGGESTED TOPICS' }}</span>
                </div>
                <div class="slsc-suggested-pills">
                    @if ($currentLocale === 'si')
                        <button type="button" class="slsc-suggested-pill" data-topic="පාඨමාලා"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> පාඨමාලා</button>
                        <button type="button" class="slsc-suggested-pill" data-topic="සංඥා සඟරාව"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> සංඥා සඟරාව</button>
                        <button type="button" class="slsc-suggested-pill" data-topic="සේනාවිධායකවරු"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> සේනාවිධායකවරු</button>
                        <button type="button" class="slsc-suggested-pill" data-topic="පාසල් ඉතිහාසය"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> පාසල් ඉතිහාසය</button>
                        <button type="button" class="slsc-suggested-pill" data-topic="ගැසට් පත්‍ර"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> ගැසට් පත්‍ර</button>
                    @else
                        <button type="button" class="slsc-suggested-pill" data-topic="Courses"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Courses</button>
                        <button type="button" class="slsc-suggested-pill" data-topic="Signals Journal"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Signals Journal</button>
                        <button type="button" class="slsc-suggested-pill" data-topic="Commandants"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Commandants</button>
                        <button type="button" class="slsc-suggested-pill" data-topic="School History"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> School History</button>
                        <button type="button" class="slsc-suggested-pill" data-topic="Gazette"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Gazette</button>
                    @endif
                </div>
            </div>

            <!-- Live Results Container -->
            <div class="slsc-search-modal-results" data-modal-search-results hidden></div>

            <!-- Modal Footer -->
            <div class="slsc-search-modal-footer">
                <span class="slsc-kbd-hint"><kbd>↑</kbd><kbd>↓</kbd> {{ $currentLocale === 'si' ? 'සංචාලනය' : 'Navigate' }}</span>
                <span class="slsc-kbd-hint"><kbd>↵</kbd> {{ $currentLocale === 'si' ? 'තෝරන්න' : 'Open' }}</span>
                <span class="slsc-kbd-hint"><kbd>ESC</kbd> {{ $currentLocale === 'si' ? 'වසන්න' : 'Close' }}</span>
            </div>
        </div>
    </div>

    {{-- Interactive Registration Dialog --}}
    <dialog id="registrationDialog" class="registration-dialog" aria-labelledby="registrationTitle" aria-describedby="registrationIntro">
        <div class="registration-dialog-top">
            <span class="registration-brand-mark">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-shield"></use></svg>
                <span>{{ $publicSiteName }}</span>
            </span>
            <button type="button" class="icon-button" data-close-registration aria-label="{{ __('theme-slsc::interface.regClose') }}" data-i18n-aria="regClose">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-close"></use></svg>
            </button>
        </div>
        <div class="registration-dialog-content">
            <header class="registration-heading">
                <p class="eyebrow" data-i18n="joinEyebrow">{{ __('theme-slsc::interface.joinEyebrow') }}</p>
                <h2 id="registrationTitle" data-i18n="regModalTitle">{{ __('theme-slsc::interface.regModalTitle') }}</h2>
                <p id="registrationIntro" data-i18n="regIntro">{{ __('theme-slsc::interface.regIntro') }}</p>
            </header>
            @include('theme-slsc::partials.registration-form')
        </div>
    </dialog>

    @stack('scripts')
</body>
</html>

