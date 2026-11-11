<?php

namespace App\Services;

use App\Enums\ThemeLayoutLocale;
use App\Enums\ThemeLayoutRegion;
use App\Enums\ThemeLayoutStatus;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Publication;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Models\ThemeLayout;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class ThemeLayoutRenderer
{
    public function __construct(
        private readonly ThemeLayoutSanitizer $sanitizer,
    ) {}

    /**
     * Render a published layout without evaluating database content as Blade,
     * PHP or JavaScript.
     *
     * @param  iterable<SocialLink>  $socialLinks
     * @param  array<int|string, mixed>  $languageOptions
     * @return array{html: string, css: string}|null
     */
    public function render(
        string $themeSlug,
        ThemeLayoutRegion $region,
        mixed $siteSettings = null,
        mixed $primaryMenu = null,
        iterable $socialLinks = [],
        array $languageOptions = [],
        string $currentLocale = 'en',
        string $siteName = '',
        string $siteTagline = '',
        ?string $logoUrl = null,
    ): ?array {
        $siteSettings = $siteSettings instanceof SiteSetting ? $siteSettings : null;
        $primaryMenu = $primaryMenu instanceof Menu ? $primaryMenu : null;

        if (
            preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $themeSlug) !== 1
            || ! Schema::hasTable('theme_layouts')
        ) {
            return null;
        }

        $requestedLocale = ThemeLayoutLocale::fromApplicationLocale($currentLocale);

        $layout = $this->publishedLayout(
            themeSlug: $themeSlug,
            region: $region,
            locale: $requestedLocale,
        );

        if (
            ! $layout instanceof ThemeLayout
            && $requestedLocale !== ThemeLayoutLocale::English
        ) {
            $layout = $this->publishedLayout(
                themeSlug: $themeSlug,
                region: $region,
                locale: ThemeLayoutLocale::English,
            );
        }

        if (
            ! $layout instanceof ThemeLayout
            || ! is_string($layout->published_html)
        ) {
            return null;
        }

        try {
            $rendered = $this->renderSource(
                themeSlug: $themeSlug,
                html: $layout->published_html,
                css: $layout->published_css ?? '',
                siteSettings: $siteSettings,
                primaryMenu: $primaryMenu,
                socialLinks: $socialLinks,
                languageOptions: $languageOptions,
                currentLocale: $requestedLocale->value,
                siteName: $siteName,
                siteTagline: $siteTagline,
                logoUrl: $logoUrl,
            );

            return $rendered['html'] !== '' ? $rendered : null;
        } catch (ValidationException) {
            return null;
        }
    }

    /**
     * Render editor previews through the same sanitization and placeholders as published layouts.
     *
     * @param  iterable<SocialLink>  $socialLinks
     * @param  array<int|string, mixed>  $languageOptions
     * @return array{html: string, css: string}
     */
    public function renderSource(
        string $themeSlug,
        string $html,
        string $css,
        mixed $siteSettings = null,
        mixed $primaryMenu = null,
        iterable $socialLinks = [],
        array $languageOptions = [],
        string $currentLocale = 'en',
        string $siteName = '',
        string $siteTagline = '',
        ?string $logoUrl = null,
    ): array {
        $siteSettings = $siteSettings instanceof SiteSetting ? $siteSettings : null;
        $primaryMenu = $primaryMenu instanceof Menu ? $primaryMenu : null;
        $html = $this->sanitizer->sanitizeHtml($html);
        $css = $this->sanitizer->sanitizeCss($css);

        if ($html === '') {
            return ['html' => '', 'css' => $css];
        }

        $locale = ThemeLayoutLocale::fromApplicationLocale($currentLocale)->value;
        $siteName = $siteSettings?->localized('site_name', $locale) ?? $siteName;
        $siteTagline = $siteSettings?->localized('site_tagline', $locale) ?? $siteTagline;

        $replacements = [
            '[[site_logo]]' => $this->siteLogo($logoUrl, $siteName, $locale),
            '[[site_name]]' => $this->escape($siteName),
            '[[site_tagline]]' => $this->escape($siteTagline),
            '[[footer_text]]' => nl2br(
                $this->escape($siteSettings?->localized('footer_text', $locale) ?? ''),
                false,
            ),
            '[[primary_menu]]' => $this->primaryMenu($primaryMenu, $locale),
            '[[responsive_primary_navigation]]' => $this->responsivePrimaryNavigation(
                $primaryMenu,
                $locale,
            ),
            '[[mobile_menu_toggle]]' => $this->mobileMenuToggle($locale),
            '[[language_switcher]]' => $this->languageSwitcher(
                $languageOptions,
                $locale,
                $themeSlug,
            ),
            '[[social_links]]' => $this->socialLinks($socialLinks, $locale),
            '[[contact_details]]' => $this->contactDetails($siteSettings, $locale),
            '[[copyright_year]]' => (string) now()->year,
            '[[search_advanced]]' => $this->searchAdvanced($locale),
            '[[search_button]]' => $this->searchAdvanced($locale),
            '[[search_bar]]' => $this->searchBar($locale),
        ];
        $renderedHtml = str_ireplace(
            array_keys($replacements),
            array_values($replacements),
            $html,
        );

        return [
            'html' => app(SiteUrlService::class)->resolveHtml($this->replaceSiteUrlPlaceholders($renderedHtml, $locale)),
            'css' => $css,
        ];
    }

    private function replaceSiteUrlPlaceholders(string $html, string $locale): string
    {
        $renderedHtml = preg_replace_callback(
            '/\[\[site_url:(\/[A-Za-z0-9._~\/%\-]*)\]\]/i',
            function (array $matches) use ($locale): string {
                $path = $matches[1];

                if ($locale === 'si' && in_array(rtrim($path, '/') ?: '/', ['/', '/news', '/galleries', '/publications', '/courses', '/documents', '/contact', '/search', '/school-leadership'], true)) {
                    $path = '/si'.($path === '/' ? '' : $path);
                }

                return $this->escape(url($path));
            },
            $html,
        );

        return is_string($renderedHtml)
            ? $renderedHtml
            : $html;
    }

    private function publishedLayout(
        string $themeSlug,
        ThemeLayoutRegion $region,
        ThemeLayoutLocale $locale,
    ): ?ThemeLayout {
        $layout = ThemeLayout::query()
            ->where('theme_slug', $themeSlug)
            ->where('region', $region->value)
            ->where('locale', $locale->value)
            ->where('status', ThemeLayoutStatus::Published->value)
            ->first();

        return $layout instanceof ThemeLayout
            ? $layout
            : null;
    }

    private function siteLogo(?string $logoUrl, string $siteName, string $locale): string
    {
        if (! is_string($logoUrl) || trim($logoUrl) === '') {
            return '';
        }

        $safeLogoUrl = $this->safeHref($logoUrl);

        if ($safeLogoUrl === '#') {
            return '';
        }

        return '<img class="cms-site-logo" src="'
            .$this->escape($safeLogoUrl)
            .'" alt="'
            .$this->escape(__('public.logo_label', ['name' => $siteName], $locale))
            .'">';
    }

    private function mobileMenuToggle(string $locale): string
    {
        return '<button id="mobileToggle" class="mobile-btn cms-mobile-menu-toggle" type="button" aria-label="'
            .$this->escape(__('public.open_menu', [], $locale))
            .'" aria-controls="mobileMenu" aria-expanded="false" data-mobile-toggle>'
            .'<i class="fa-solid fa-bars" aria-hidden="true"></i>'
            .'<span class="cms-mobile-menu-toggle-label">'.$this->escape(__('public.menu', [], $locale)).'</span>'
            .'</button>';
    }

    private function responsivePrimaryNavigation(
        ?Menu $menu,
        string $locale,
    ): string {
        $menuHtml = $this->primaryMenu($menu, $locale);

        return '<div class="cms-responsive-navigation">'
            .'<button id="mobileToggle" class="cms-mobile-menu-toggle" type="button" aria-label="'
            .$this->escape(__('public.open_menu', [], $locale))
            .'" aria-expanded="false" data-mobile-toggle>'
            .'<i class="fa-solid fa-bars" aria-hidden="true"></i>'
            .'<span>'.$this->escape(__('public.menu', [], $locale)).'</span>'
            .'</button>'
            .'<div id="mobileMenu" class="cms-responsive-menu-panel" data-mobile-menu>'
            .$menuHtml
            .'</div>'
            .'</div>';
    }

    private function primaryMenu(?Menu $menu, string $locale): string
    {
        if (! $menu instanceof Menu) {
            return $this->fallbackMenu($locale);
        }

        $items = $menu->rootItems
            ->where('is_active', true);

        if ($items->isEmpty()) {
            return $this->fallbackMenu($locale);
        }

        $html = '<ul class="cms-primary-menu">';

        foreach ($items as $item) {
            $html .= $this->menuItem($item, $locale);
        }

        if (! $this->hasCoursesLink($items, $locale)) {
            $html .= '<li class="cms-menu-item"><a href="'
                .$this->escape($this->safeHref(CourseLocale::indexUrl($locale)))
                .'">'.$this->escape(__('public.courses', [], $locale)).'</a></li>';
        }

        return $html.'</ul>';
    }

    /** @param iterable<MenuItem> $items */
    private function hasCoursesLink(iterable $items, string $locale): bool
    {
        foreach ($items as $item) {
            $label = trim($item->labelForLocale($locale));
            $path = parse_url((string) $item->url, PHP_URL_PATH);
            $isCourseLink = in_array(mb_strtolower($label), ['courses', 'පාඨමාලා'], true)
                || in_array($item->route_name, ['courses.index', 'courses.index.localized'], true)
                || (is_string($path) && preg_match('~/(?:si/)?courses/?\z~', $path) === 1);

            if ($isCourseLink || $this->hasCoursesLink($item->children, $locale)) {
                return true;
            }
        }

        return false;
    }

    private function menuItem(MenuItem $item, string $locale): string
    {
        $children = $item->children
            ->where('is_active', true);

        $hasChildren = $children->isNotEmpty();

        $itemClass = $hasChildren
            ? 'cms-menu-item cms-menu-parent'
            : 'cms-menu-item';

        $attributes = $item->open_in_new_tab
            ? ' target="_blank" rel="noopener noreferrer"'
            : '';

        $html = '<li class="'.$itemClass.'"><a href="'
            .$this->escape(
                $this->safeHref($item->resolvedUrl($locale)),
            )
            .'"'
            .$attributes
            .'>'
            .$this->escape($item->labelForLocale($locale))
            .'</a>';

        if ($hasChildren) {
            $html .= '<ul class="cms-primary-submenu">';

            foreach ($children as $child) {
                $childAttributes = $child->open_in_new_tab
                    ? ' target="_blank" rel="noopener noreferrer"'
                    : '';

                $html .= '<li class="cms-menu-item"><a href="'
                    .$this->escape(
                        $this->safeHref($child->resolvedUrl($locale)),
                    )
                    .'"'
                    .$childAttributes
                    .'>'
                    .$this->escape($child->labelForLocale($locale))
                    .'</a></li>';
            }

            $html .= '</ul>';
        }

        return $html.'</li>';
    }

    private function fallbackMenu(string $locale): string
    {
        $newsUrl = $locale === ThemeLayoutLocale::English->value
            ? route('news.index')
            : route('news.index.localized', [
                'locale' => $locale,
            ]);

        $links = [
            __('public.home', [], $locale) => $locale === 'en' ? route('home') : route('home.localized', ['locale' => $locale]),
            __('public.news', [], $locale) => $newsUrl,
            __('public.gallery', [], $locale) => GalleryLocale::indexUrl($locale),
            __('public.publications', [], $locale) => Publication::indexUrl($locale),
            __('public.courses', [], $locale) => CourseLocale::indexUrl($locale),
        ];

        $html = '<ul class="cms-primary-menu">';

        foreach ($links as $label => $url) {
            $html .= '<li class="cms-menu-item"><a href="'
                .$this->escape($this->safeHref($url))
                .'">'
                .$this->escape($label)
                .'</a></li>';
        }

        return $html.'</ul>';
    }

    /**
     * @return list<array{code: string, available: true, url: string}>
     */
    private function homeLanguageOptions(string $themeSlug): array
    {
        $supportedLocales = $this->themeLocaleCodes($themeSlug);
        $options = [];

        if (in_array('en', $supportedLocales, true)) {
            $options[] = [
                'code' => 'en',
                'available' => true,
                'url' => url('/'),
            ];
        }

        if (! Schema::hasTable('pages')) {
            return $options;
        }

        foreach (['si', 'ta'] as $locale) {
            if (! in_array($locale, $supportedLocales, true)) {
                continue;
            }

            $homePageExists = Page::query()
                ->published()
                ->where('locale', $locale)
                ->where('slug', 'home')
                ->exists();

            if (! $homePageExists) {
                continue;
            }

            $options[] = [
                'code' => $locale,
                'available' => true,
                'url' => route('pages.show.localized', [
                    'locale' => $locale,
                    'slug' => 'home',
                ]),
            ];
        }

        return $options;
    }

    /**
     * @param  array<int|string, mixed>  $languageOptions
     */
    private function languageSwitcher(
        array $languageOptions,
        string $currentLocale,
        string $themeSlug,
    ): string {
        $supportedLocales = $this->themeLocaleCodes($themeSlug);

        if ($languageOptions === []) {
            $languageOptions = $this->homeLanguageOptions($themeSlug);
        }

        $localeNames = [
            'en' => 'English',
            'si' => 'සිංහල',
            'ta' => 'தமிழ்',
        ];
        $links = '';

        foreach ($languageOptions as $option) {
            if (
                ! is_array($option)
                || ($option['available'] ?? false) !== true
            ) {
                continue;
            }

            $code = $option['code'] ?? null;
            $url = $option['url'] ?? null;

            if (
                ! is_string($code)
                || ! array_key_exists($code, $localeNames)
                || ! is_string($url)
            ) {
                continue;
            }

            if (! in_array($code, $supportedLocales, true)) {
                continue;
            }

            $activeClass = $code === $currentLocale
                ? ' active'
                : '';

            $links .= '<a class="cms-language-option'
                .$activeClass
                .'" href="'
                .$this->escape($this->safeHref($url))
                .'" lang="'
                .$this->escape($code)
                .'" data-language-option data-lang="'
                .$this->escape($code)
                .'">'
                .$this->escape($localeNames[$code])
                .'</a>';
        }

        if ($links === '') {
            return '<span class="cms-language-current" lang="'
                .$this->escape($currentLocale)
                .'">'
                .$this->escape(strtoupper($currentLocale))
                .'</span>';
        }

        return '<nav class="cms-language-switcher" aria-label="'.$this->escape(__('public.language_selection', [], $currentLocale)).'">'
            .$links
            .'</nav>';
    }

    /**
     * @return list<string>
     */
    private function themeLocaleCodes(string $themeSlug): array
    {
        return app(SiteLanguageService::class)->codes(themeSlug: $themeSlug);
    }

    /**
     * @param  iterable<SocialLink>  $socialLinks
     */
    private function socialLinks(iterable $socialLinks, string $locale): string
    {
        $links = '';

        foreach ($socialLinks as $socialLink) {
            $url = $this->safeHref($socialLink->url);

            if ($url === '#') {
                continue;
            }

            $label = is_string($socialLink->label)
                && trim($socialLink->label) !== ''
                    ? $socialLink->label
                    : $socialLink->platform;

            $links .= '<a class="cms-social-link" href="'
                .$this->escape($url)
                .'" target="_blank" rel="noopener noreferrer"'
                .' aria-label="'.$this->escape($label).'" title="'.$this->escape($label).'">'
                .'<i class="'.$socialLink->iconClass().'" aria-hidden="true"></i>'
                .'</a>';
        }

        return $links === ''
            ? ''
            : '<nav class="cms-social-links" aria-label="'.$this->escape(__('public.social_media', [], $locale)).'">'
                .$links
                .'</nav>';
    }

    private function contactDetails(?SiteSetting $settings, string $locale): string
    {
        if (! $settings instanceof SiteSetting) {
            return '';
        }

        $details = '';
        $address = $settings->localized('address', $locale);

        if ($address !== null) {
            $details .= '<p class="cms-contact-address">'
                .nl2br(
                    $this->escape($address),
                    false,
                )
                .'</p>';
        }

        $details .= $this->phoneLink($settings->phone_primary);
        $details .= $this->phoneLink($settings->phone_secondary);

        if (
            is_string($settings->email)
            && filter_var(
                $settings->email,
                FILTER_VALIDATE_EMAIL,
            ) !== false
        ) {
            $email = trim($settings->email);

            $details .= '<a class="cms-contact-email" href="mailto:'
                .$this->escape($email)
                .'">'
                .$this->escape($email)
                .'</a>';
        }

        return $details === ''
            ? ''
            : '<div class="cms-contact-details">'
                .$details
                .'</div>';
    }

    private function phoneLink(?string $phone): string
    {
        if (! is_string($phone) || trim($phone) === '') {
            return '';
        }

        $number = preg_replace('/[^0-9+]/', '', $phone);

        if (! is_string($number) || $number === '') {
            return '';
        }

        return '<a class="cms-contact-phone" href="tel:'
            .$this->escape($number)
            .'">'
            .$this->escape(trim($phone))
            .'</a>';
    }

    private function searchAdvanced(string $locale): string
    {
        return $this->searchButton($locale);
    }

    private function searchButton(string $locale): string
    {
        $label = $locale === 'si' ? 'සොයන්න' : 'Search';

        return '<button type="button" class="search-toggle-btn managed-school-search-btn" aria-label="'.$this->escape($label).'" title="'.$this->escape($label).'" data-open-search-modal>'
            .'<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>'
            .'</button>';
    }

    private function searchBar(string $locale): string
    {
        $placeholder = $locale === 'si' ? 'වෙබ් අඩවිය තුළ සොයන්න...' : 'Search the website...';
        $label = $locale === 'si' ? 'සොයන්න' : 'Search';

        return '<div class="mobile-search-bar managed-school-search-bar" role="button" tabindex="0" data-open-search-modal aria-label="'.$this->escape($label).'">'
            .'<input type="text" placeholder="'.$this->escape($placeholder).'" aria-label="'.$this->escape($label).'" readonly style="cursor: pointer;">'
            .'<button type="button" aria-label="'.$this->escape($label).'"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>'
            .'</div>';
    }

    private function safeHref(string $url): string
    {
        $url = trim($url);

        if ($url === '#') {
            return $url;
        }

        if (
            str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
        ) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '#';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme)
            && in_array(
                strtolower($scheme),
                ['http', 'https', 'mailto', 'tel'],
                true,
            )
                ? $url
                : '#';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
    }
}
