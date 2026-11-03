@php
    $publicSiteName = $siteSettings?->localized('site_name') ?: config('app.name', 'AWCMS');
    $publicDescription = $siteSettings?->localized('site_tagline') ?: __('public.school_name');
    $publicAddress = $siteSettings?->localized('address');
    $publicFooterText = $siteSettings?->localized('footer_text');
    $currentLocale = in_array(app()->getLocale(), ['en', 'si', 'ta'], true) ? app()->getLocale() : 'en';
    $publicHomeUrl = $currentLocale === 'en' ? route('home') : route('home.localized', ['locale' => $currentLocale]);
    $publicNewsUrl = $currentLocale === 'en' ? route('news.index') : route('news.index.localized', ['locale' => $currentLocale]);
    $themeLabels = collect(['open_menu', 'close_menu', 'scroll_top', 'go_to_slide', 'previous_image', 'next_image', 'close_image', 'gallery_image', 'course_count'])
        ->mapWithKeys(static fn(string $key): array => [$key => __('public.'.$key)]);
    $themeAssetBase = asset('themes/school-of-signals/assets');
    $themeCssPath = public_path('themes/school-of-signals/assets/css/theme.css');
    $themeCssVersion = is_file($themeCssPath) ? filemtime($themeCssPath) : null;
    $navbarCssPath = public_path('themes/school-of-signals/assets/css/navbar.css');
    $navbarCssVersion = is_file($navbarCssPath) ? filemtime($navbarCssPath) : null;
    $themeJsPath = public_path('themes/school-of-signals/assets/js/theme.js');
    $themeJsVersion = is_file($themeJsPath) ? filemtime($themeJsPath) : null;
    $mediaUrls = app(\App\Services\MediaUrlService::class);
    $logoUrl = $siteSettings?->logo
        ? $mediaUrls->mediumOrOriginal($siteSettings->logo)
        : $themeAssetBase . '/images/logo.png';
    $faviconUrl = $siteSettings?->favicon ? $mediaUrls->original($siteSettings->favicon) : null;
    $supportedThemeLocales = app(\App\Services\SiteLanguageService::class)->codes();
    $languageOptions =
        isset($languageVersions) && is_array($languageVersions)
            ? array_values(
                array_filter(
                    $languageVersions,
                    static fn(mixed $language): bool => is_array($language) &&
                        isset($language['code']) &&
                        is_string($language['code']) &&
                        in_array($language['code'], $supportedThemeLocales, true),
                ),
            )
            : [];
    $localeNames = ['en' => 'English', 'si' => 'සිංහල', 'ta' => 'தமிழ்'];
    $themeLayoutRenderer = app(\App\Services\ThemeLayoutRenderer::class);

    $cleanSiteSettings = $siteSettings instanceof \App\Models\SiteSetting ? $siteSettings : null;
    $cleanPrimaryMenu = $primaryMenu instanceof \App\Models\Menu ? $primaryMenu : null;
    $cleanSocialLinks = (is_iterable($publicSocialLinks) && ! ($publicSocialLinks instanceof \__PHP_Incomplete_Class)) ? $publicSocialLinks : [];

    $managedHeaderLayout = $themeLayoutRenderer->render(
        themeSlug: 'school-of-signals',
        region: \App\Enums\ThemeLayoutRegion::Header,
        siteSettings: $cleanSiteSettings,
        primaryMenu: $cleanPrimaryMenu,
        socialLinks: $cleanSocialLinks,
        languageOptions: $languageOptions,
        currentLocale: $currentLocale,
        siteName: $publicSiteName,
        siteTagline: $publicDescription,
        logoUrl: $logoUrl,
    );

    $managedFooterLayout = $themeLayoutRenderer->render(
        themeSlug: 'school-of-signals',
        region: \App\Enums\ThemeLayoutRegion::Footer,
        siteSettings: $cleanSiteSettings,
        primaryMenu: $cleanPrimaryMenu,
        socialLinks: $cleanSocialLinks,
        languageOptions: $languageOptions,
        currentLocale: $currentLocale,
        siteName: $publicSiteName,
        siteTagline: $publicDescription,
        logoUrl: $logoUrl,
    );
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" data-theme-asset-base="{{ $themeAssetBase }}" data-theme-labels="{{ $themeLabels->toJson() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $publicSiteName)</title>

    <meta name="description" content="@yield('meta_description', $publicDescription)">

    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif

    {{-- Open Graph / Social Media Preview (WhatsApp, Facebook, Twitter) --}}
    <meta property="og:site_name" content="{{ $publicSiteName }}">
    <meta property="og:locale" content="{{ $currentLocale === 'si' ? 'si_LK' : 'en_US' }}">
    @hasSection('og_tags')
        @yield('og_tags')
    @else
        <meta property="og:type" content="website">
        <meta property="og:title" content="@yield('title', $publicSiteName)">
        <meta property="og:description" content="@yield('meta_description', $publicDescription)">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $logoUrl ?: asset('logo.png') }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="@yield('title', $publicSiteName)">
        <meta name="twitter:description" content="@yield('meta_description', $publicDescription)">
        <meta name="twitter:image" content="{{ $logoUrl ?: asset('logo.png') }}">
    @endif

    @yield('meta')

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    <link rel="stylesheet" href="{{ $themeAssetBase }}/fonts/fontawesome-7/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/theme.css{{ $themeCssVersion !== null ? '?v='.$themeCssVersion : '' }}">
    <link rel="stylesheet" href="{{ $themeAssetBase }}/css/navbar.css{{ $navbarCssVersion !== null ? '?v='.$navbarCssVersion : '' }}">

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

<body>
    <a class="skip-link" href="#main-content">{{ __('public.skip_content') }}</a>
    @if ($managedHeaderLayout !== null)
        {!! $managedHeaderLayout['html'] !!}
    @else
        <div class="topbar">
            <div class="container">
                <div class="topbar-left">
                    <span>
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        {{ $publicDescription }}
                    </span>
                </div>
                <div class="topbar-right">
                    <span class="motto">{{ __('public.motto') }}</span>
                    <div class="managed-school-topbar-languages">
                        <nav class="cms-language-switcher" aria-label="{{ __('public.language_selection') }}">
                            @foreach ($languageOptions as $language)
                                @if (($language['available'] ?? false) && isset($language['url'], $language['code']))
                                    <a class="cms-language-option {{ $language['code'] === $currentLocale ? 'active' : '' }}"
                                       href="{{ $language['url'] }}" 
                                       lang="{{ $language['code'] }}"
                                       data-language-option 
                                       data-lang="{{ $language['code'] }}">
                                        {{ $localeNames[$language['code']] ?? strtoupper($language['code']) }}
                                    </a>
                                @endif
                            @endforeach
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <header class="header">
            <div class="container nav-wrap">
                <a class="brand" href="{{ $publicHomeUrl }}" aria-label="{{ __('public.home_label', ['name' => $publicSiteName]) }}">
                    <span class="brand-logo">
                        <img src="{{ $logoUrl }}" alt="{{ __('public.logo_label', ['name' => $publicSiteName]) }}">
                    </span>
                    <span>
                        <span class="brand-title">{{ $publicSiteName }}</span>
                        <span class="brand-subtitle">{{ __('public.army') }}</span>
                    </span>
                </a>

                <nav class="desktop-nav" aria-label="{{ __('public.primary_navigation') }}">
                    <a href="{{ $publicHomeUrl }}">{{ __('public.home') }}</a>
                    <a href="{{ \App\Services\CourseLocale::indexUrl($currentLocale) }}">{{ __('public.courses') }}</a>
                    <a href="{{ $currentLocale === 'en' ? url('/en/pages/aboutus') : url('/si/pages/siaboutus') }}">{{ $currentLocale === 'si' ? 'අප ගැන' : 'About Us' }}</a>
                    <a href="{{ \App\Models\Publication::indexUrl($currentLocale) }}">{{ __('public.publications') }}</a>
                    <div class="drop">
                        <a class="drop-toggle"
                            href="{{ $currentLocale === 'en' ? route('history.past-commandants') : route('history.past-commandants.localized') }}">
                            {{ $currentLocale === 'si' ? 'ඉතිහාසය' : 'History' }}
                            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                        </a>
                        <div class="dropdown">
                            <a href="{{ $currentLocale === 'en' ? url('/en/pages/history-of-school') : url('/si/pages/history-of-school') }}">
                                {{ $currentLocale === 'si' ? 'පාසලේ ඉතිහාසය' : 'School History' }}
                            </a>
                            <a
                                href="{{ $currentLocale === 'en' ? route('history.past-commandants') : route('history.past-commandants.localized') }}">
                                {{ $currentLocale === 'si' ? 'හිටපු සේනාවිධායකවරු' : 'Past Commandants' }}
                            </a>
                            <a
                                href="{{ $currentLocale === 'en' ? route('history.past-chief-instructors') : route('history.past-chief-instructors.localized') }}">
                                {{ $currentLocale === 'si' ? 'හිටපු ප්‍රධාන උපදේශකවරු' : 'Past Chief Instructors' }}
                            </a>
                            <a
                                href="{{ $currentLocale === 'en' ? route('history.past-sgt-major') : route('history.past-sgt-major.localized') }}">
                                {{ $currentLocale === 'si' ? 'හිටපු රෙජිමේන්තු සැරයන් මේජර්වරු' : 'Past Regimental Sergeant Majors' }}
                            </a>
                        </div>
                    </div>
                    <a href="{{ \App\Services\GalleryLocale::indexUrl($currentLocale) }}">{{ $currentLocale === 'si' ? 'ඡායාරූප' : 'Gallery' }}</a>
                    <a href="{{ $currentLocale === 'en' ? url('/en/pages/contact-us') : url('/si/pages/contact-us') }}">{{ $currentLocale === 'si' ? 'අප අමතන්න' : 'Contact Us' }}</a>
                </nav>

                <div class="nav-actions">
                    <a class="managed-school-conduct-link conduct-link" href="{{ url('/document-files/en/code-of-conduct-5/view') }}">
                        {{ $currentLocale === 'si' ? 'චර්යාධර්ම සංග්‍රහය' : 'Code of Conduct' }}
                    </a>

                    <a href="{{ $currentLocale === 'en' ? route('search') : route('search.localized', ['locale' => $currentLocale]) }}" 
                       class="search-toggle-btn" 
                       aria-label="{{ __('public.search') }}" 
                       title="{{ __('public.search') }}">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </a>

                    <div class="language-switch" data-language-switch>
                        <button class="language-toggle" type="button" aria-label="{{ __('public.change_language') }}" aria-haspopup="true"
                            aria-expanded="false" data-language-toggle>
                            <i class="fa-solid fa-language" aria-hidden="true"></i>
                            <span class="language-current">{{ strtoupper($currentLocale) }}</span>
                        </button>
                        <div class="language-menu" role="menu" aria-label="{{ __('public.language_options') }}">
                            @forelse ($languageOptions as $language)
                                @if (($language['available'] ?? false) && isset($language['url'], $language['code']))
                                    <a class="language-option {{ $language['code'] === $currentLocale ? 'active' : '' }}"
                                        href="{{ $language['url'] }}" lang="{{ $language['code'] }}" role="menuitem"
                                        data-language-option data-lang="{{ $language['code'] }}">
                                        <span>{{ $localeNames[$language['code']] ?? strtoupper($language['code']) }}</span>
                                        <strong>{{ strtoupper($language['code']) }}</strong>
                                    </a>
                                @endif
                            @empty
                                <span
                                    class="language-option active"><span>{{ $localeNames[$currentLocale] }}</span><strong>{{ strtoupper($currentLocale) }}</strong></span>
                            @endforelse
                        </div>
                    </div>

                    <a href="https://lms.army.lk" target="_blank" rel="noopener noreferrer"
                        class="btn btn-green portal-button managed-school-portal">
                        <i class="fa-solid fa-laptop-code" aria-hidden="true"></i><span>{{ __('public.lms_portal') }}</span>
                    </a>

                    <button class="mobile-btn" id="mobileToggle" type="button" aria-expanded="false"
                        aria-label="{{ __('public.open_menu') }}" data-mobile-toggle>
                        <i class="fa-solid fa-bars" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="mobile-menu" id="mobileMenu" data-mobile-menu>
                <div class="container">
                    <form action="{{ $currentLocale === 'en' ? route('search') : route('search.localized', ['locale' => $currentLocale]) }}" 
                          method="GET" 
                          class="mobile-search-bar" 
                          role="search">
                        <input type="search" 
                               name="q" 
                               placeholder="{{ __('public.search_placeholder') }}" 
                               aria-label="{{ __('public.search') }}" 
                               required>
                        <button type="submit" aria-label="{{ __('public.search') }}">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        </button>
                    </form>

                    @if ($languageOptions !== [])
                        <div class="mobile-language" aria-label="{{ __('public.language_selection') }}">
                            @foreach ($languageOptions as $language)
                                @if (($language['available'] ?? false) && isset($language['url'], $language['code']))
                                    <a class="{{ $language['code'] === $currentLocale ? 'active' : '' }}"
                                        href="{{ $language['url'] }}" lang="{{ $language['code'] }}"
                                        data-language-option data-lang="{{ $language['code'] }}">
                                        <span><i class="fa-solid fa-language" aria-hidden="true"></i>
                                            {{ $localeNames[$language['code']] ?? strtoupper($language['code']) }}</span>
                                        <strong>{{ strtoupper($language['code']) }}</strong>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <a href="{{ $publicHomeUrl }}"><span>{{ __('public.home') }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ \App\Services\CourseLocale::indexUrl($currentLocale) }}"><span>{{ __('public.courses') }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ $currentLocale === 'en' ? url('/en/pages/aboutus') : url('/si/pages/siaboutus') }}"><span>{{ $currentLocale === 'si' ? 'අප ගැන' : 'About Us' }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ \App\Models\Publication::indexUrl($currentLocale) }}"><span>{{ __('public.publications') }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>

                    <details class="sos-mobile-dropdown">
                        <summary>
                            <span>{{ $currentLocale === 'si' ? 'ඉතිහාසය' : 'History' }}</span>
                            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                        </summary>
                        <div class="sos-mobile-submenu">
                            <a href="{{ $currentLocale === 'en' ? url('/en/pages/history-of-school') : url('/si/pages/history-of-school') }}">
                                <span>{{ $currentLocale === 'si' ? 'පාසලේ ඉතිහාසය' : 'School History' }}</span>
                                <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                            </a>
                            <a href="{{ $currentLocale === 'en' ? route('history.past-commandants') : route('history.past-commandants.localized') }}">
                                <span>{{ $currentLocale === 'si' ? 'හිටපු සේනාවිධායකවරු' : 'Past Commandants' }}</span>
                                <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                            </a>
                            <a href="{{ $currentLocale === 'en' ? route('history.past-chief-instructors') : route('history.past-chief-instructors.localized') }}">
                                <span>{{ $currentLocale === 'si' ? 'හිටපු ප්‍රධාන උපදේශකවරු' : 'Past Chief Instructors' }}</span>
                                <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                            </a>
                            <a href="{{ $currentLocale === 'en' ? route('history.past-sgt-major') : route('history.past-sgt-major.localized') }}">
                                <span>{{ $currentLocale === 'si' ? 'හිටපු රෙජිමේන්තු සැරයන් මේජර්වරු' : 'Past Regimental Sergeant Majors' }}</span>
                                <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </details>

                    <a href="{{ \App\Services\GalleryLocale::indexUrl($currentLocale) }}"><span>{{ $currentLocale === 'si' ? 'ඡායාරූප' : 'Gallery' }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ $currentLocale === 'en' ? url('/en/pages/contact-us') : url('/si/pages/contact-us') }}"><span>{{ $currentLocale === 'si' ? 'අප අමතන්න' : 'Contact Us' }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ url('/document-files/en/code-of-conduct-5/view') }}"><span>{{ $currentLocale === 'si' ? 'චර්යාධර්ම සංග්‍රහය' : 'Code of Conduct' }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="https://lms.army.lk" target="_blank" rel="noopener noreferrer"><span>{{ __('public.lms_portal') }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </header>
    @endif
    <main id="main-content">
        @yield('content')
    </main>
    @if ($managedFooterLayout !== null)
        {!! $managedFooterLayout['html'] !!}
    @else
        <footer class="footer">
            <div class="container footer-grid">
                <section>
                    <h2>{{ $publicSiteName }}</h2>
                    <p>{{ $publicFooterText ?: $publicDescription }}</p>
                </section>
                <section>
                    <h2>{{ __('public.quick_links') }}</h2>
                    <a href="{{ $publicHomeUrl }}">{{ __('public.home') }}</a>
                    <a
                        href="{{ $publicNewsUrl }}">{{ __('public.news') }}</a>
                    <a href="{{ \App\Services\GalleryLocale::indexUrl($currentLocale) }}">{{ $currentLocale === 'si' ? 'ඡායාරූප ගැලරිය' : 'Gallery' }}</a>
                </section>
                <section>
                    <h2>{{ __('public.contact') }}</h2>
                    @if ($siteSettings?->email)
                        <a href="mailto:{{ $siteSettings->email }}">{{ $siteSettings->email }}</a>
                    @endif
                    @if ($siteSettings?->phone_primary)
                        <a href="tel:{{ $siteSettings->phone_primary }}">{{ $siteSettings->phone_primary }}</a>
                    @endif
                    @if ($publicAddress)
                        <p>{{ $publicAddress }}</p>
                    @endif
                </section>
                @if ($publicSocialLinks->isNotEmpty())
                    <section>
                        <h2>{{ __('public.follow_us') }}</h2>
                        <div class="footer-socials">
                            @foreach ($publicSocialLinks as $socialLink)
                                <a href="{{ $socialLink->url }}" target="_blank" rel="noopener noreferrer"
                                    aria-label="{{ $socialLink->platform }}">
                                    <i class="{{ $socialLink->iconClass() }}"
                                        aria-hidden="true"></i>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
            <div class="footer-bottom">
                <div class="container">
                    <p>&copy; {{ now()->year }} {{ $publicSiteName }}. {{ __('public.rights_reserved') }}</p>
                </div>
            </div>
        </footer>
    @endif
    <button class="scroll-top" type="button" aria-label="{{ __('public.scroll_top') }}">
        <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
    </button>

    @include('public.search.modal')

    <script src="{{ $themeAssetBase }}/js/theme.js{{ $themeJsVersion !== null ? '?v='.$themeJsVersion : '' }}" defer></script>
    @stack('scripts')
</body>

</html>
