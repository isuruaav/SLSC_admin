<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicPublicationController extends Controller
{
    public function index(string $locale = 'en'): View
    {
        abort_unless(in_array($locale, ['en', 'si'], true), 404);
        app()->setLocale($locale);

        return view('public.publications.index', [
            'publications' => Publication::query()->published()->where('locale', $locale)->orderByDesc('published_at')->orderByDesc('id')->paginate(5)->withQueryString(),
            'currentLocale' => $locale,
            'languageVersions' => [
                ['code' => 'en', 'available' => true, 'url' => Publication::indexUrl('en')],
                ['code' => 'si', 'available' => true, 'url' => Publication::indexUrl('si')],
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

    private function file(Publication $publication, bool $cover, bool $isPublic = false): StreamedResponse
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
        ], 'inline');
    }
}
