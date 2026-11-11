<?php

namespace App\Services;

use App\Enums\ThemeLayoutLocale;
use App\Models\SiteSetting;
use BackedEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

final class SiteLanguageService
{
    /** @return list<string> */
    public function codes(?SiteSetting $settings = null, ?string $themeSlug = null): array
    {
        if ($settings === null) {
            $cached = Cache::get('awcms_site_settings');
            if ($cached instanceof SiteSetting) {
                $settings = $cached;
            } elseif (Schema::hasTable('site_settings')) {
                $settings = SiteSetting::query()->first();
            }
        }
        $configured = $settings?->getAttribute('enabled_locales');

        if (! is_array($configured)) {
            $themeSlug ??= config('awcms.active_theme');
            $configured = is_string($themeSlug) && $themeSlug !== ''
                ? config('awcms.theme_locales.'.$themeSlug, ['en', 'si', 'ta'])
                : ['en', 'si', 'ta'];
        }

        return $this->normalize(is_array($configured) ? $configured : ['en', 'si', 'ta']);
    }

    /** @param array<mixed> $codes
     * @return list<string>
     */
    public function normalize(array $codes): array
    {
        return array_values(array_filter(
            array_column(ThemeLayoutLocale::cases(), 'value'),
            static fn (string $code): bool => $code === 'en' || in_array($code, $codes, true),
        ));
    }

    public function enabled(string $locale): bool
    {
        return in_array($locale, $this->codes(), true);
    }

    /**
     * Prefer published translations supplied by the content controller. For
     * listing pages, retain the current route, identifiers and search filters.
     *
     * @param  array<int|string, mixed>  $versions
     * @return list<array{code: string, available: bool, url: ?string}>
     */
    public function switcherLinks(array $versions = []): array
    {
        if ($versions !== []) {
            $links = [];
            foreach ($versions as $version) {
                if (! is_array($version) || ! is_string($version['code'] ?? null) || ! in_array($version['code'], $this->codes(), true)) {
                    continue;
                }
                $url = is_string($version['url'] ?? null) ? $version['url'] : null;
                $links[] = ['code' => $version['code'], 'available' => (bool) ($version['available'] ?? ($url !== null)), 'url' => $url];
            }

            return $links;
        }

        $request = request();
        $route = $request->route();
        $name = $route?->getName();
        $base = is_string($name) ? preg_replace('/\.localized$/', '', $name) : 'home';
        $localized = $base.'.localized';
        if (! Route::has($base) || ! Route::has($localized) || str_starts_with($base, 'admin.')) {
            $base = 'home';
            $localized = 'home.localized';
            $parameters = [];
        } else {
            $parameters = array_merge($request->query(), $route->parameters());
            unset($parameters['locale']);
        }

        return array_map(static fn (string $code): array => [
            'code' => $code,
            'available' => true,
            'url' => $code === 'en'
                ? route($base, $parameters)
                : route($localized, ['locale' => $code] + $parameters),
        ], $this->codes());
    }

    /** @template T of BackedEnum
     * @param  list<T>  $cases
     * @return list<T>
     */
    public function choices(array $cases, ?string $keepLocale = null): array
    {
        $enabled = $this->codes();

        return array_values(array_filter(
            $cases,
            static fn (BackedEnum $locale): bool => $locale->value === $keepLocale || in_array($locale->value, $enabled, true),
        ));
    }
}
