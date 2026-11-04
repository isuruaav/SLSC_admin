<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Document;
use App\Models\News;
use App\Models\Page;
use App\Models\Publication;
use App\Services\DocumentUploadManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class PublicSearchController extends Controller
{
    public function __construct(private readonly DocumentUploadManager $documents) {}

    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
        ]);

        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $query = trim((string) $request->query('q', ''));
        $category = (string) $request->query('type', 'all');

        $searchData = $this->performSearch($query, $locale, 20);
        $counts = $searchData['counts'];
        $grouped = $searchData['grouped'];

        $results = match ($category) {
            'news' => $grouped['news'],
            'courses' => $grouped['courses'],
            'documents' => $grouped['documents'],
            'publications' => $grouped['publications'],
            'pages' => $grouped['pages'],
            default => $searchData['all'],
        };

        $searchParams = array_filter(['q' => $query !== '' ? $query : null, 'type' => $category !== 'all' ? $category : null]);

        return view('public.search.index', [
            'query' => $query,
            'category' => $category,
            'results' => $results,
            'counts' => $counts,
            'currentLocale' => $locale,
            'languageVersions' => [
                [
                    'code' => 'en',
                    'available' => true,
                    'url' => route('search', $searchParams),
                ],
                [
                    'code' => 'si',
                    'available' => true,
                    'url' => route('search.localized', ['locale' => 'si', ...$searchParams]),
                ],
            ],
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string']]);

        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $query = mb_convert_encoding(trim((string) $request->query('q', '')), 'UTF-8', 'UTF-8');

        if (mb_strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'total' => 0,
                'results' => [],
                'view_all_url' => $locale === 'si' ? route('search.localized', ['locale' => 'si']) : route('search'),
            ], 200, ['Content-Type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        $searchData = $this->performSearch($query, $locale, 4);

        $results = $searchData['all']->take(6)->values()->all();
        $total = $searchData['counts']['all'];

        $viewAllUrl = $locale === 'si'
            ? route('search.localized', ['locale' => 'si', 'q' => $query])
            : route('search', ['q' => $query]);

        return response()->json([
            'query' => $query,
            'total' => $total,
            'results' => $results,
            'view_all_url' => $viewAllUrl,
        ], 200, ['Content-Type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function resolveLocale(Request $request): string
    {
        $locale = $request->route('locale');

        return is_string($locale) && in_array($locale, ['en', 'si'], true) ? $locale : 'en';
    }

    /**
     * @return array{
     *     all: Collection<int, array{type: string, type_label: string, icon: string, title: string, excerpt: string, date: string|null, url: string}>,
     *     grouped: array<string, Collection<int, array{type: string, type_label: string, icon: string, title: string, excerpt: string, date: string|null, url: string}>>,
     *     counts: array<string, int>
     * }
     */
    private function performSearch(string $query, string $locale, int $limitPerType = 20): array
    {
        $counts = [
            'all' => 0,
            'news' => 0,
            'courses' => 0,
            'documents' => 0,
            'publications' => 0,
            'pages' => 0,
        ];

        $grouped = [
            'news' => collect(),
            'courses' => collect(),
            'documents' => collect(),
            'publications' => collect(),
            'pages' => collect(),
        ];

        if (mb_strlen($query) < 2) {
            return [
                'all' => collect(),
                'grouped' => $grouped,
                'counts' => $counts,
            ];
        }

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        // 1. News
        $newsResults = News::query()
            ->published()
            ->where('locale', $locale)
            ->where(function ($q) use ($escaped): void {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('summary', 'like', "%{$escaped}%")
                    ->orWhere('content', 'like', "%{$escaped}%");
            })
            ->latest('published_at')
            ->limit($limitPerType)
            ->get()
            ->map(fn (News $item): array => [
                'type' => 'news',
                'type_label' => $locale === 'si' ? 'පුවත්' : 'News',
                'icon' => 'fa-newspaper',
                'title' => $item->title,
                'excerpt' => Str::limit(strip_tags((string) ($item->summary ?: $item->content)), 160),
                'date' => $item->published_at?->format('d M Y'),
                'url' => $locale === 'en'
                    ? route('news.show', ['slug' => $item->slug])
                    : route('news.show.localized', ['locale' => $locale, 'slug' => $item->slug]),
            ]);
        $counts['news'] = $newsResults->count();
        $grouped['news'] = $newsResults;

        // 2. Courses
        $courseResults = Course::query()
            ->where('is_active', true)
            ->where(function ($q) use ($escaped): void {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('name_si', 'like', "%{$escaped}%");
            })
            ->orderBy('sort_order')
            ->limit($limitPerType)
            ->get()
            ->map(fn (Course $item): array => [
                'type' => 'courses',
                'type_label' => $locale === 'si' ? 'පාඨමාලා' : 'Courses',
                'icon' => 'fa-graduation-cap',
                'title' => (string) ($locale === 'si' && $item->name_si ? $item->name_si : $item->name),
                'excerpt' => ($item->start_date && $item->end_date)
                    ? ($locale === 'si'
                        ? "පාඨමාලා කාලය: {$item->start_date->format('d M Y')} සිට {$item->end_date->format('d M Y')} දක්වා"
                        : "Duration: {$item->start_date->format('d M Y')} to {$item->end_date->format('d M Y')}")
                    : ($locale === 'si' ? 'සක්‍රීය පුහුණු පාඨමාලාව' : 'Active training course'),
                'date' => $item->start_date?->format('d M Y'),
                'url' => $locale === 'en'
                    ? route('courses.index')
                    : route('courses.index.localized', ['locale' => $locale]),
            ]);
        $counts['courses'] = $courseResults->count();
        $grouped['courses'] = $courseResults;

        // 3. Documents
        $documentResults = $this->documents->publicQuery($locale)
            ->where(function ($q) use ($escaped): void {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('description', 'like', "%{$escaped}%")
                    ->orWhere('title_si', 'like', "%{$escaped}%")
                    ->orWhere('description_si', 'like', "%{$escaped}%");
            })
            ->latest('published_at')
            ->limit($limitPerType)
            ->get()
            ->map(fn (Document $item): array => [
                'type' => 'documents',
                'type_label' => $locale === 'si' ? 'ලේඛන' : 'Documents',
                'icon' => 'fa-file-lines',
                'title' => $item->titleForLocale($locale),
                'excerpt' => Str::limit(strip_tags((string) ($locale === 'si' ? ($item->description_si ?: $item->description) : $item->description)), 160),
                'date' => $item->published_at?->format('d M Y'),
                'url' => $locale === 'en'
                    ? route('documents.show', ['slug' => $item->slug])
                    : route('documents.show.localized', ['locale' => $locale, 'slug' => $item->slug]),
            ]);
        $counts['documents'] = $documentResults->count();
        $grouped['documents'] = $documentResults;

        // 4. Publications
        $publicationResults = Publication::query()
            ->published()
            ->where(function ($q) use ($escaped): void {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('title_si', 'like', "%{$escaped}%");
            })
            ->latest('published_at')
            ->limit($limitPerType)
            ->get()
            ->map(fn (Publication $item): array => [
                'type' => 'publications',
                'type_label' => $locale === 'si' ? 'ප්‍රකාශන' : 'Publications',
                'icon' => 'fa-book-open',
                'title' => $item->titleForLocale($locale),
                'excerpt' => $locale === 'si' ? 'නිල සඟරාව / ප්‍රකාශනය (PDF)' : 'Official publication / journal (PDF)',
                'date' => $item->published_at?->format('d M Y'),
                'url' => route('publications.pdf', ['publication' => $item->uuid]),
            ]);
        $counts['publications'] = $publicationResults->count();
        $grouped['publications'] = $publicationResults;

        // 5. Pages
        $pageResults = Page::query()
            ->published()
            ->where('locale', $locale)
            ->where(function ($q) use ($escaped): void {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('content', 'like', "%{$escaped}%");
            })
            ->limit($limitPerType)
            ->get()
            ->map(fn (Page $item): array => [
                'type' => 'pages',
                'type_label' => $locale === 'si' ? 'පිටු' : 'Pages',
                'icon' => 'fa-file',
                'title' => $item->title,
                'excerpt' => Str::limit(strip_tags((string) $item->content), 160),
                'date' => null,
                'url' => $locale === 'en'
                    ? route('pages.show', ['slug' => $item->slug])
                    : route('pages.show.localized', ['locale' => $locale, 'slug' => $item->slug]),
            ]);
        $counts['pages'] = $pageResults->count();
        $grouped['pages'] = $pageResults;

        $all = $newsResults
            ->concat($courseResults)
            ->concat($documentResults)
            ->concat($publicationResults)
            ->concat($pageResults);

        $counts['all'] = $all->count();

        return [
            'all' => $all,
            'grouped' => $grouped,
            'counts' => $counts,
        ];
    }
}
