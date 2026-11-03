<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Gallery;
use App\Models\News;
use App\Models\Page;
use App\Services\SiteLanguageService;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

class PublicSitemapController extends Controller
{
    public function index(SiteLanguageService $languageService): Response
    {
        $enabledLocales = $languageService->codes();
        $urls = [];

        // 1. Static Core Routes
        foreach ($enabledLocales as $loc) {
            $isEn = $loc === 'en';

            $urls[] = [
                'loc' => $isEn ? route('home') : route('home.localized', ['locale' => $loc]),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];

            $urls[] = [
                'loc' => $isEn ? route('courses.index') : route('courses.index.localized', ['locale' => $loc]),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];

            $urls[] = [
                'loc' => $isEn ? route('news.index') : route('news.index.localized', ['locale' => $loc]),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];

            $urls[] = [
                'loc' => $isEn ? route('publications.index') : route('publications.index.localized', ['locale' => $loc]),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];

            $urls[] = [
                'loc' => $isEn ? route('galleries.index') : route('galleries.index.localized', ['locale' => $loc]),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];

            $urls[] = [
                'loc' => $isEn ? route('contact.create') : route('contact.create.localized', ['locale' => $loc]),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];

            $urls[] = [
                'loc' => $isEn ? route('search') : route('search.localized', ['locale' => $loc]),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ];

            $urls[] = [
                'loc' => route($isEn ? 'history.past-commandants' : 'history.past-commandants.localized'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];

            $urls[] = [
                'loc' => route($isEn ? 'history.past-chief-instructors' : 'history.past-chief-instructors.localized'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];

            $urls[] = [
                'loc' => route($isEn ? 'history.past-sgt-major' : 'history.past-sgt-major.localized'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        // 2. Published CMS Pages
        try {
            $pages = Page::query()->published()->get();
            foreach ($pages as $page) {
                $rawLocale = $page->locale instanceof \BackedEnum ? $page->locale->value : (string) $page->locale;
                if (! in_array($rawLocale, $enabledLocales, true)) {
                    continue;
                }

                $pageUrl = $rawLocale === 'en'
                    ? route('pages.show', ['slug' => $page->slug])
                    : route('pages.show.localized', ['locale' => $rawLocale, 'slug' => $page->slug]);

                $lastMod = $this->formatDate($page->updated_at ?: $page->published_at);

                $urls[] = [
                    'loc' => $pageUrl,
                    'lastmod' => $lastMod,
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
        } catch (\Throwable) {
            // gracefully fallback if table query encounters error
        }

        // 3. Published News Articles
        try {
            $newsList = News::query()->published()->orderByDesc('published_at')->get();
            foreach ($newsList as $item) {
                $rawLocale = $item->locale instanceof \BackedEnum ? $item->locale->value : (string) $item->locale;
                if (! in_array($rawLocale, $enabledLocales, true)) {
                    continue;
                }

                $newsUrl = $rawLocale === 'en'
                    ? route('news.show', ['slug' => $item->slug])
                    : route('news.show.localized', ['locale' => $rawLocale, 'slug' => $item->slug]);

                $lastMod = $this->formatDate($item->updated_at ?: $item->published_at);

                $urls[] = [
                    'loc' => $newsUrl,
                    'lastmod' => $lastMod,
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }
        } catch (\Throwable) {
        }

        // 4. Published Galleries
        try {
            $galleries = Gallery::query()->published()->get();
            foreach ($galleries as $gallery) {
                $urls[] = [
                    'loc' => route('galleries.show', ['slug' => $gallery->slug]),
                    'lastmod' => $this->formatDate($gallery->updated_at ?: $gallery->published_at),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
        } catch (\Throwable) {
        }

        // 5. Published Documents
        try {
            $documents = Document::query()->published()->get();
            foreach ($documents as $doc) {
                $urls[] = [
                    'loc' => route('documents.show', ['slug' => $doc->slug]),
                    'lastmod' => $this->formatDate($doc->updated_at ?: $doc->published_at),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ];
            }
        } catch (\Throwable) {
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $entry) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>' . htmlspecialchars($entry['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            if (! empty($entry['lastmod'])) {
                $xml .= '        <lastmod>' . htmlspecialchars($entry['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            }
            $xml .= '        <changefreq>' . htmlspecialchars($entry['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
            $xml .= '        <priority>' . htmlspecialchars($entry['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof CarbonInterface) {
            return $date->toDateString();
        }

        if (is_string($date) && ! empty($date)) {
            try {
                return \Illuminate\Support\Carbon::parse($date)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}

