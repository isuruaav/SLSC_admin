<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicPublicationController extends Controller
{
    public function index(Request $request, string $locale = 'en'): View
    {
        abort_unless(in_array($locale, ['en', 'si'], true), 404);
        app()->setLocale($locale);

        $settings = SiteSetting::current();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'between:1900,9999'],
            'sort' => ['nullable', 'in:newest,oldest'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $year = $filters['year'] ?? '';
        $sort = $filters['sort'] ?? 'newest';
        $query = Publication::query()->published()->where('locale', $locale);
        $years = (clone $query)->pluck('published_at')->map(fn ($date) => $date->year)->unique()->sortDesc()->values();
        $query->when($search !== '', function ($query) use ($search): void {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(fn ($query) => $query->where('title', 'like', $term)->orWhere('title_si', 'like', $term));
        })->when($year !== '', fn ($query) => $query->whereYear('published_at', $year));
        $direction = $sort === 'oldest' ? 'asc' : 'desc';
        $languageFilters = array_filter(['q' => $search, 'year' => $year, 'sort' => $sort]);

        return view('public.publications.index', [
            'publications' => $query->orderBy('published_at', $direction)->orderBy('id', $direction)->paginate(5)->withQueryString(),
            'search' => $search,
            'selectedYear' => $year,
            'sort' => $sort,
            'years' => $years,
            'currentLocale' => $locale,
            'pageTitle' => $settings->publicationTitle($locale),
            'pageKicker' => $settings->publicationKicker($locale),
            'pageSubtitle' => $settings->publicationSubtitle($locale),
            'languageVersions' => [
                ['code' => 'en', 'available' => true, 'url' => route('publications.index', $languageFilters)],
                ['code' => 'si', 'available' => true, 'url' => route('publications.index.localized', ['locale' => 'si', ...$languageFilters])],
            ],
        ]);
    }

    public function pdf(Publication $publication): StreamedResponse
    {
        abort_unless($publication->isPublic(), 404);

        return $this->file($publication, false, true);
    }

    public function cover(Publication $publication): StreamedResponse
    {
        abort_unless($publication->isPublic(), 404);

        return $this->file($publication, true, true);
    }

    public function download(Publication $publication): StreamedResponse
    {
        abort_unless($publication->isPublic(), 404);

        return $this->file($publication, false, true, true);
    }

    public function previewPdf(Publication $publication): StreamedResponse
    {
        Gate::authorize('documents.view');

        return $this->file($publication, false, false);
    }

    public function previewCover(Publication $publication): StreamedResponse
    {
        Gate::authorize('documents.view');

        return $this->file($publication, true, false);
    }

    private function file(Publication $publication, bool $cover, bool $isPublic = false, bool $download = false): StreamedResponse
    {
        $path = (string) ($cover ? $publication->cover_path : $publication->pdf_path);
        abort_unless(str_starts_with($path, 'publications/') && ! str_contains($path, '..') && ! str_contains($path, '\\'), 404);
        $disk = Storage::disk((string) $publication->disk);
        abort_unless($disk->exists($path), 404);
        $name = (Str::slug((string) $publication->title) ?: 'publication').($cover ? '.jpg' : '.pdf');

        $cacheControl = $isPublic
            ? ($cover ? 'public, max-age=86400, stale-while-revalidate=604800' : 'public, max-age=3600')
            : 'private, no-store';

        return $disk->response($path, $name, [
            'Content-Type' => $cover ? 'image/jpeg' : 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $cacheControl,
        ], $download ? 'attachment' : 'inline');
    }
}
