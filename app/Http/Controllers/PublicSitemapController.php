<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Gallery;
use App\Models\News;
use App\Models\Organization;
use App\Models\Page;
use App\Models\SchoolLeader;
use App\Services\SiteLanguageService;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class PublicSitemapController extends Controller
{
    public function index(SiteLanguageService $languageService): Response
    {
        $enabledLocales = $languageService->codes();
        $urls = [];

        // 1. Static Core Routes
        foreach (array_intersect($enabledLocales, ['en', 'si']) as $loc) {
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
                $rawLocale = (string) $page->getRawOriginal('locale');
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
                $rawLocale = (string) $item->getRawOriginal('locale');
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

        foreach ($enabledLocales as $locale) {
            if (! in_array($locale, ['en', 'si'], true)) {
                continue;
            }
            foreach (['organization', 'first-appointments', 'brigade-commanders'] as $section) {
                $urls[] = ['loc' => route($section.'.index'.($locale === 'si' ? '.localized' : ''), $locale === 'si' ? ['locale' => 'si'] : []), 'changefreq' => 'weekly', 'priority' => '0.7'];
            }
            foreach (Organization::query()->published()->get() as $organization) {
                $urls[] = ['loc' => route('organization.show'.($locale === 'si' ? '.localized' : ''), ['slug' => $organization->slug, ...($locale === 'si' ? ['locale' => 'si'] : [])]), 'lastmod' => $this->formatDate($organization->updated_at), 'changefreq' => 'monthly', 'priority' => '0.7'];
            }
            foreach (SchoolLeader::query()->where('is_active', true)->whereHas('position', fn ($query) => $query->where('is_active', true))->with('position')->get() as $leader) {
                $url = $leader->appointmentUrlForLocale($locale);
                if ($url) {
                    $urls[] = ['loc' => $url, 'lastmod' => $this->formatDate($leader->updated_at), 'changefreq' => 'monthly', 'priority' => '0.6'];
                }
            }
            foreach (PublicPastAppointmentsController::APPOINTMENTS as $appointment) {
                $urls[] = ['loc' => url(($locale === 'si' ? '/si' : '').'/'.$appointment['slug']), 'changefreq' => 'monthly', 'priority' => '0.6'];
            }
            if ($locale === 'si') {
                foreach (Gallery::query()->published()->get() as $gallery) {
                    $urls[] = ['loc' => route('galleries.show.localized', ['locale' => 'si', 'slug' => $gallery->slug]), 'lastmod' => $this->formatDate($gallery->updated_at), 'changefreq' => 'weekly', 'priority' => '0.7'];
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $entry) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>'.htmlspecialchars($entry['loc'], ENT_XML1, 'UTF-8')."</loc>\n";
            if (! empty($entry['lastmod'])) {
                $xml .= '        <lastmod>'.htmlspecialchars($entry['lastmod'], ENT_XML1, 'UTF-8')."</lastmod>\n";
            }
            $xml .= '        <changefreq>'.htmlspecialchars($entry['changefreq'], ENT_XML1, 'UTF-8')."</changefreq>\n";
            $xml .= '        <priority>'.htmlspecialchars($entry['priority'], ENT_XML1, 'UTF-8')."</priority>\n";
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
                return Carbon::parse($date)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
