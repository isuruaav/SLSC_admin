<?php

namespace App\Http\Controllers;

use App\Models\BrigadeCommander;
use App\Models\Course;
use App\Models\Document;
use App\Models\FirstAppointmentHolder;
use App\Models\Gallery;
use App\Models\News;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Publication;
use App\Models\SchoolLeader;
use App\Services\DocumentUploadManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class PublicSearchController extends Controller
{
    public function __construct(private readonly DocumentUploadManager $documents) {}

    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:all,news,courses,documents,publications,pages,organizations,leadership,galleries,appointments,commanders'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $query = trim((string) $request->query('q', ''));
        $category = (string) ($request->query('type') ?: 'all');
        $page = (int) $request->query('page', 1);

        $searchData = $this->performSearch($query, $locale, 20, $page, $category);
        $counts = $searchData['counts'];
        $results = $searchData['all'];

        $searchParams = array_filter(['q' => $query !== '' ? $query : null, 'type' => $category !== 'all' ? $category : null]);

        return view('public.search.index', [
            'query' => $query,
            'category' => $category,
            'results' => $results,
            'pagination' => new LengthAwarePaginator($results, $counts[$category], 20, $page, ['path' => $request->url(), 'query' => $request->query()]),
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
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);

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

        $searchData = $this->performSearch($query, $locale, 6);

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
     *     counts: array<string, int>
     * }
     */
    private function performSearch(string $query, string $locale, int $limitPerType = 20, int $page = 1, string $category = 'all'): array
    {
        $counts = [
            'all' => 0,
            'news' => 0,
            'courses' => 0,
            'documents' => 0,
            'publications' => 0,
            'pages' => 0,
            'organizations' => 0, 'leadership' => 0, 'galleries' => 0, 'appointments' => 0, 'commanders' => 0,
        ];

        if (mb_strlen($query) < 2) {
            return [
                'all' => collect(),
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
            ->latest('id')
            ->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'news', $counts, $category, $page, $limitPerType);
            })
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

        // 2. Courses
        $courseResults = Course::query()
            ->where('is_active', true)
            ->where(function ($q) use ($escaped): void {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('name_si', 'like', "%{$escaped}%");
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'courses', $counts, $category, $page, $limitPerType);
            })
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

        // 3. Documents
        $documentResults = $this->documents->publicQuery($locale)
            ->where(function ($q) use ($escaped): void {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('description', 'like', "%{$escaped}%")
                    ->orWhere('title_si', 'like', "%{$escaped}%")
                    ->orWhere('description_si', 'like', "%{$escaped}%");
            })
            ->latest('published_at')
            ->latest('id')
            ->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'documents', $counts, $category, $page, $limitPerType);
            })
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

        // 4. Publications
        $publicationResults = Publication::query()
            ->published()
            ->where('locale', $locale)
            ->where(function ($q) use ($escaped): void {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('title_si', 'like', "%{$escaped}%");
            })
            ->latest('published_at')
            ->latest('id')
            ->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'publications', $counts, $category, $page, $limitPerType);
            })
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

        // 5. Pages
        $pageResults = Page::query()
            ->published()
            ->orderBy('id')
            ->where('locale', $locale)
            ->where(function ($q) use ($escaped): void {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('content', 'like', "%{$escaped}%");
            })
            ->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'pages', $counts, $category, $page, $limitPerType);
            })
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

        $organizationsResults = Organization::query()->published()->with('translations')->whereHas('translations', fn ($translation) => $translation->where('locale', $locale)->where(fn ($translation) => $translation->where('title', 'like', "%{$escaped}%")->orWhere('summary', 'like', "%{$escaped}%")))
            ->orderBy('id')->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'organizations', $counts, $category, $page, $limitPerType);
            })->get()->map(fn (Organization $item): array => [
                'type' => 'organizations', 'type_label' => $locale === 'si' ? 'ආයතන' : 'Organizations', 'icon' => 'fa-sitemap',
                'title' => $item->titleForLocale($locale), 'excerpt' => Str::limit(strip_tags((string) $item->summaryForLocale($locale)), 160), 'date' => null,
                'url' => $locale === 'si' ? route('organization.show.localized', ['locale' => 'si', 'slug' => $item->slug]) : route('organization.show', ['slug' => $item->slug]),
            ]);

        $leadershipResults = SchoolLeader::query()->where('is_active', true)->whereHas('position', fn ($position) => $position->where('is_active', true))->where(fn ($person) => $person->where('name_en', 'like', "%{$escaped}%")->orWhere('name_si', 'like', "%{$escaped}%"))
            ->orderBy('id')->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'leadership', $counts, $category, $page, $limitPerType);
            })->get()->map(fn (SchoolLeader $item): array => [
                'type' => 'leadership', 'type_label' => $locale === 'si' ? 'නායකත්වය' : 'Leadership', 'icon' => 'fa-user-tie',
                'title' => $item->displayNameWithRankForLocale($locale), 'excerpt' => $item->appointmentLabelForLocale($locale), 'date' => null,
                'url' => $item->appointmentUrlForLocale($locale) ?? url(($locale === 'si' ? '/si' : '').'/school-leadership'),
            ]);

        $galleriesResults = Gallery::query()->published()->where(fn ($gallery) => $gallery->where('title', 'like', "%{$escaped}%")->orWhere('title_si', 'like', "%{$escaped}%"))
            ->orderBy('id')->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'galleries', $counts, $category, $page, $limitPerType);
            })->get()->map(fn (Gallery $item): array => [
                'type' => 'galleries', 'type_label' => $locale === 'si' ? 'ඡායාරූප එකතු' : 'Galleries', 'icon' => 'fa-images',
                'title' => (string) ($locale === 'si' && $item->title_si ? $item->title_si : $item->title), 'excerpt' => Str::limit(strip_tags((string) $item->description), 160), 'date' => null,
                'url' => $locale === 'si' ? route('galleries.show.localized', ['locale' => 'si', 'slug' => $item->slug]) : route('galleries.show', ['slug' => $item->slug]),
            ]);

        $appointmentsResults = FirstAppointmentHolder::query()->active()->where(fn ($person) => $person->where('name_en', 'like', "%{$escaped}%")->orWhere('name_si', 'like', "%{$escaped}%")->orWhere('appointment_en', 'like', "%{$escaped}%")->orWhere('appointment_si', 'like', "%{$escaped}%"))
            ->orderBy('id')->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'appointments', $counts, $category, $page, $limitPerType);
            })->get()->map(fn (FirstAppointmentHolder $item): array => [
                'type' => 'appointments', 'type_label' => $locale === 'si' ? 'පළමු පත්වීම්' : 'First Appointments', 'icon' => 'fa-award',
                'title' => $item->nameForLocale($locale), 'excerpt' => $item->appointmentForLocale($locale), 'date' => null,
                'url' => ($locale === 'si' ? route('first-appointments.index.localized', ['locale' => 'si']) : route('first-appointments.index')).'#holder-'.$item->id,
            ]);

        $commandersResults = BrigadeCommander::query()->active()->where(fn ($person) => $person->where('name_en', 'like', "%{$escaped}%")->orWhere('name_si', 'like', "%{$escaped}%"))
            ->orderBy('id')->tap(function ($builder) use (&$counts, $category, $page, $limitPerType): void {
                $this->limitResults($builder, 'commanders', $counts, $category, $page, $limitPerType);
            })->get()->map(fn (BrigadeCommander $item): array => [
                'type' => 'commanders', 'type_label' => $locale === 'si' ? 'බලසේනාධිපතිවරු' : 'Brigade Commanders', 'icon' => 'fa-medal',
                'title' => $item->nameForLocale($locale), 'excerpt' => (string) $item->periodForLocale($locale), 'date' => null,
                'url' => ($locale === 'si' ? route('brigade-commanders.index.localized', ['locale' => 'si']) : route('brigade-commanders.index')).'#commander-'.$item->id,
            ]);

        $all = $newsResults
            ->concat($courseResults)
            ->concat($documentResults)
            ->concat($publicationResults)
            ->concat($pageResults)
            ->concat($organizationsResults)
            ->concat($leadershipResults)
            ->concat($galleriesResults)
            ->concat($appointmentsResults)
            ->concat($commandersResults);

        $counts['all'] = array_sum($counts);

        return [
            'all' => $all,
            'counts' => $counts,
        ];
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, int>  $counts
     */
    private function limitResults(Builder $query, string $type, array &$counts, string $category, int $page, int $perPage): void
    {
        $counts[$type] = (clone $query)->count();
        $start = ($page - 1) * $perPage;
        if ($category !== 'all') {
            $query->offset($start)->limit($category === $type ? $perPage : 0);

            return;
        }
        $preceding = array_sum($counts) - $counts[$type];
        $offset = max(0, $start - $preceding);
        $take = max(0, $start + $perPage - max($start, $preceding));
        $query->offset($offset)->limit($take);
    }
}
