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
        // These sections currently have English and Sinhala public routes.
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

        $pages = Page::query()->published()->where('locale', $locale)->orderBy('title')->get()
            ->map(static fn (Page $page): array => [
                'label' => (string) $page->getAttribute('title'),
                'href' => '[[site_url:/'.$locale.'/pages/'.$page->getAttribute('slug').']]',
            ])->all();

        return [
            'defaultHtml' => view('admin.theme-layouts.navigation-starter', ['links' => $links])->render(),
            'baseCss' => (string) file_get_contents(resource_path('css/navigation-starter.css')),
            'links' => $links,
            'icons' => collect(self::ICONS)->map(static fn (string $label, string $icon): array => [
                'label' => $label, 'class' => 'fa-solid fa-'.$icon,
            ])->values()->all(),
            'pages' => $pages,
            'siteMenuHtml' => $this->render('<nav class="aw-navigation">[[primary_menu]]</nav>', '', $locale, $themeSlug)['html'],
        ];
    }

    /** @return array{html: string, css: string} */
    public function render(string $html, string $css, string $locale, string $themeSlug): array
    {
        $settings = SiteSetting::query()->first();
        $menu = Menu::query()->active()->where('location', 'primary')
            ->with(['rootItems.translations', 'rootItems.children.translations'])->first();
        $logo = $settings?->logo;

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
                : (is_file(base_path('themes/'.$themeSlug.'/assets/images/logo.png'))
                    ? asset('themes/'.$themeSlug.'/assets/images/logo.png') : null),
        );
    }
}
