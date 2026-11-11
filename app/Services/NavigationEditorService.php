<?php

namespace App\Services;

use App\Enums\ThemeLayoutLocale;
use App\Models\Menu;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\SocialLink;

final class NavigationEditorService
{
    /** @var array<string, string> */
    private const ICONS = [
        'house' => 'Home', 'newspaper' => 'News', 'graduation-cap' => 'Education',
        'images' => 'Gallery', 'book-open' => 'Publications', 'envelope' => 'Email',
        'circle-info' => 'Information', 'clock-rotate-left' => 'History', 'users' => 'People',
        'user-tie' => 'Leadership', 'shield-halved' => 'Shield', 'building-columns' => 'Institution',
        'phone' => 'Phone', 'location-dot' => 'Location', 'globe' => 'Website',
        'file-lines' => 'Document', 'file-pdf' => 'PDF', 'download' => 'Download',
        'calendar-days' => 'Events', 'bullhorn' => 'Announcements', 'link' => 'Link',
        'star' => 'Star', 'award' => 'Award', 'laptop-code' => 'Learning portal',
        'briefcase' => 'Services', 'sitemap' => 'Organization', 'circle-question' => 'Help',
        'camera' => 'Camera', 'video' => 'Video', 'magnifying-glass' => 'Search',
    ];

    /** @return array<string, mixed> */
    public function configuration(string $locale, string $themeSlug): array
    {
        $locale = ThemeLayoutLocale::fromApplicationLocale($locale)->value;
        $treeLinks = [];
        $flatLinks = [];

        $themeNavPath = base_path('themes/'.$themeSlug.'/navigation/'.$locale.'.json');
        if (is_file($themeNavPath)) {
            $raw = json_decode((string) file_get_contents($themeNavPath), true);
            if (is_array($raw) && count($raw) > 0) {
                [$treeLinks, $flatLinks] = $this->parseThemeNavigation($raw, $locale);
            }
        }

        if (empty($treeLinks)) {
            [$treeLinks, $flatLinks] = $this->defaultStarterLinks($locale);
        }

        $pages = Page::query()->published()->where('locale', $locale)->orderBy('title')->get()
            ->map(static fn (Page $page): array => [
                'label' => (string) $page->getAttribute('title'),
                'href' => '[[site_url:/'.$locale.'/pages/'.$page->getAttribute('slug').']]',
            ])->all();

        return [
            'defaultHtml' => view('admin.theme-layouts.navigation-starter', [
                'links' => $treeLinks,
                'headerContent' => $this->headerContent($locale, $themeSlug),
            ])->render(),
            'baseCss' => (string) file_get_contents(resource_path('css/navigation-starter.css')),
            'links' => $flatLinks,
            'icons' => collect(self::ICONS)->map(static fn (string $label, string $icon): array => [
                'label' => $label, 'class' => 'fa-solid fa-'.$icon,
            ])->values()->all(),
            'pages' => $pages,
            'siteMenuHtml' => $this->render('<nav class="aw-navigation">[[primary_menu]]</nav>', '', $locale, $themeSlug)['html'],
        ];
    }

    /** @return array<string, string> */
    private function headerContent(string $locale, string $themeSlug): array
    {
        $sinhala = $locale === 'si';
        $corps = $themeSlug === 'slsc';

        return [
            'topbar' => $corps ? '' : '[[site_name]]',
            'motto' => $corps ? '' : '[[site_tagline]]',
            'title' => $corps ? ($sinhala ? 'ශ්‍රී ලංකා සංඥා බලකාය' : 'Sri Lanka Signal Corps') : '[[site_name]]',
            'subtitle' => $corps ? ($sinhala ? 'ශ්‍රී ලංකා යුද්ධ හමුදාව' : 'Sri Lanka Army') : '[[site_tagline]]',
            'conduct' => $corps ? '' : ($sinhala ? 'ලේඛන' : 'Documents'),
            'conduct_url' => $this->formatHref($corps ? '#' : '/documents'),
            'portal' => $corps ? '' : 'Portal',
            'portal_url' => '#',
        ];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, string>>}
     */
    private function defaultStarterLinks(string $locale): array
    {
        $prefix = $locale === 'si' ? '/si' : '';
        $links = [];
        $defaultIcons = ['home' => 'house', 'news' => 'newspaper', 'courses' => 'graduation-cap', 'gallery' => 'images', 'publications' => 'book-open', 'contact' => 'envelope'];

        foreach (['home' => '', 'news' => '/news', 'courses' => '/courses', 'gallery' => '/galleries', 'publications' => '/publications', 'contact' => '/contact'] as $key => $path) {
            $links[] = [
                'label' => __('public.'.$key, [], $locale),
                'href' => '[[site_url:'.($prefix.$path ?: '/').']]',
                'icon' => 'fa-solid fa-'.$defaultIcons[$key],
            ];
        }

        return [$links, $links];
    }

    /**
     * @param array<int, array<string, mixed>> $raw
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, string>>}
     */
    private function parseThemeNavigation(array $raw, string $locale): array
    {
        $tree = [];
        $flat = [];

        foreach ($raw as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $rawHref = (string) ($item['href'] ?? '#');
            $hasChildren = ! empty($item['children']) && is_array($item['children']);
            $href = $hasChildren && ($rawHref === '' || $rawHref === '#') ? '#' : $this->formatHref($rawHref);
            $icon = (string) ($item['icon'] ?? $this->resolveIcon($label, $rawHref));

            $treeItem = [
                'label' => $label,
                'href' => $href,
                'icon' => $icon,
            ];

            $flat[] = [
                'label' => $label,
                'href' => $href,
                'icon' => $icon,
            ];

            if ($hasChildren) {
                $treeItem['children'] = [];
                foreach ($item['children'] as $child) {
                    $childLabel = trim((string) ($child['label'] ?? ''));
                    if ($childLabel === '') {
                        continue;
                    }
                    $childRawHref = (string) ($child['href'] ?? '#');
                    $childHref = $this->formatHref($childRawHref);
                    $childIcon = (string) ($child['icon'] ?? $this->resolveIcon($childLabel, $childRawHref));

                    $childNode = [
                        'label' => $childLabel,
                        'href' => $childHref,
                        'icon' => $childIcon,
                    ];
                    if (! empty($child['new_tab'])) {
                        $childNode['new_tab'] = true;
                    }

                    $treeItem['children'][] = $childNode;
                    $flat[] = [
                        'label' => $label.' › '.$childLabel,
                        'href' => $childHref,
                        'icon' => $childIcon,
                    ];
                }
            }

            $tree[] = $treeItem;
        }

        return [$tree, $flat];
    }

    private function resolveIcon(string $label, ?string $href = null): string
    {
        $normalized = mb_strtolower($label.' '.($href ?? ''));

        if (str_contains($normalized, 'home') || str_contains($normalized, 'මුල් පිටුව')) {
            return 'fa-solid fa-house';
        }
        if (str_contains($normalized, 'appointment') || str_contains($normalized, 'පත්වීම්')
            || str_contains($normalized, 'commandant') || str_contains($normalized, 'officer')
            || str_contains($normalized, 'adjutant') || str_contains($normalized, 'rsm')
            || str_contains($normalized, 'director') || str_contains($normalized, 'බලකාධිපති')
            || str_contains($normalized, 'නිලධාරී') || str_contains($normalized, 'අධ්‍යක්ෂක')
            || str_contains($normalized, 'සේනාවිධායක') || str_contains($normalized, 'අධිකාරී')
            || str_contains($normalized, 'මේජර්')) {
            return 'fa-solid fa-user-tie';
        }
        if (str_contains($normalized, 'history') || str_contains($normalized, 'ඉතිහාසය')) {
            return 'fa-solid fa-clock-rotate-left';
        }
        if (str_contains($normalized, 'organization') || str_contains($normalized, 'සංවිධානය')) {
            return 'fa-solid fa-sitemap';
        }
        if (str_contains($normalized, 'brigade') || str_contains($normalized, 'regiment')
            || str_contains($normalized, 'centre') || str_contains($normalized, 'unit')
            || str_contains($normalized, 'බලසේනා') || str_contains($normalized, 'රෙජිමේන්තු')
            || str_contains($normalized, 'මධ්‍යස්ථාන') || str_contains($normalized, 'ඒකක')) {
            return 'fa-solid fa-shield-halved';
        }
        if (str_contains($normalized, 'magazine') || str_contains($normalized, 'journal')
            || str_contains($normalized, 'publication') || str_contains($normalized, 'සගරා')
            || str_contains($normalized, 'සඟරා')) {
            return 'fa-solid fa-book-open';
        }
        if (str_contains($normalized, 'sport') || str_contains($normalized, 'athletic')
            || str_contains($normalized, 'color') || str_contains($normalized, 'colours')
            || str_contains($normalized, 'ක්‍රීඩා') || str_contains($normalized, 'වරණ')) {
            return 'fa-solid fa-award';
        }
        if (str_contains($normalized, 'gallery') || str_contains($normalized, 'photo')
            || str_contains($normalized, 'video') || str_contains($normalized, 'album')
            || str_contains($normalized, 'රූප') || str_contains($normalized, 'මාධ්‍ය')) {
            return 'fa-solid fa-images';
        }
        if (str_contains($normalized, 'contact') || str_contains($normalized, 'email')
            || str_contains($normalized, 'අමතන්න')) {
            return 'fa-solid fa-envelope';
        }
        if (str_contains($normalized, 'news')) {
            return 'fa-solid fa-newspaper';
        }
        if (str_contains($normalized, 'course') || str_contains($normalized, 'school')) {
            return 'fa-solid fa-graduation-cap';
        }

        return 'fa-solid fa-link';
    }

    private function formatHref(string $href): string
    {
        $href = trim($href);
        if ($href === '' || $href === '#') {
            return '#';
        }
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')
            || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
            return $href;
        }
        if (str_starts_with($href, '[[site_url:')) {
            return $href;
        }

        return '[[site_url:'.(str_starts_with($href, '/') ? $href : '/'.$href).']]';
    }

    /** @return array{html: string, css: string} */
    public function render(string $html, string $css, string $locale, string $themeSlug): array
    {
        $settings = SiteSetting::query()->first();
        $menu = Menu::query()->active()->where('location', 'primary')
            ->with(['rootItems.translations', 'rootItems.children.translations'])->first();
        $logo = $settings?->logo;
        $fallbackLogo = 'themes/'.$themeSlug.'/assets/images/logo.png';
        if ($themeSlug === 'slsc' && is_file(base_path('themes/slsc/assets/images/logo/signals-logo.png'))) {
            $fallbackLogo = 'themes/slsc/assets/images/logo/signals-logo.png';
        }

        return app(ThemeLayoutRenderer::class)->renderSource(
            themeSlug: $themeSlug,
            html: $html,
            css: $css,
            siteSettings: $settings,
            primaryMenu: $menu,
            socialLinks: SocialLink::query()->active()->get(),
            languageOptions: array_map(
                static fn (string $code): array => ['code' => $code, 'available' => true, 'url' => url($code === 'en' ? '/' : '/'.$code)],
                app(SiteLanguageService::class)->codes($settings, $themeSlug),
            ),
            currentLocale: $locale,
            siteName: $settings?->localized('site_name', $locale) ?: (string) config('app.name', 'AWCMS'),
            siteTagline: $settings?->localized('site_tagline', $locale) ?: '',
            logoUrl: $logo ? app(MediaUrlService::class)->mediumOrOriginal($logo)
                : (is_file(base_path($fallbackLogo)) ? asset($fallbackLogo) : null),
        );
    }
}
