<?php

namespace App\Services;

use App\Enums\ThemeLayoutLocale;

final class FooterEditorService
{
    /** @return array<string, mixed> */
    public function configuration(string $locale, string $themeSlug): array
    {
        $locale = ThemeLayoutLocale::fromApplicationLocale($locale)->value;
        $navigation = app(NavigationEditorService::class)->configuration($locale, $themeSlug);

        return [
            'defaultHtml' => view('admin.theme-layouts.footer-starter', [
                'links' => $navigation['links'],
                'footerLocale' => $locale,
            ])->render(),
            'baseCss' => (string) file_get_contents(resource_path('css/footer-starter.css')),
            'links' => $navigation['links'],
            'pages' => $navigation['pages'],
            'icons' => $navigation['icons'],
        ];
    }
}
