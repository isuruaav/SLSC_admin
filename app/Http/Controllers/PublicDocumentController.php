<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\MediaAsset;
use App\Services\DocumentUploadManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicDocumentController extends Controller
{
    public function __construct(
        private readonly DocumentUploadManager $uploads,
    ) {}

    public function index(string $locale = 'en'): View
    {
        $this->setLocale($locale);

        $documents = $this->query($locale)
            ->recent()
            ->paginate(15)
            ->withQueryString();

        return view('public.documents.upload-index', [
            'documents' => $documents,
            'currentLocale' => $locale,
            'languageVersions' => $this->languageVersions($locale),
        ]);
    }

    public function show(string $slug): View
    {
        return $this->showLocalized('en', $slug);
    }

    public function showLocalized(string $locale, string $slug): View
    {
        $this->setLocale($locale);

        $document = $this->query($locale)
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->detail($document, $locale);
    }

    public function view(string $slug): StreamedResponse
    {
        $document = $this->query('en')
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->stream($document, 'en', false);
    }

    public function download(string $slug): StreamedResponse
    {
        $document = $this->query('en')
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->stream($document, 'en', true);
    }

    public function viewFile(
        string $locale,
        string $slug,
    ): View|StreamedResponse {
        $this->setLocale($locale);

        $document = $this->query($locale)
            ->where('slug', $slug)
            ->firstOrFail();

        $version = $this->version($document, $locale);
        $media = $version->media;

        abort_unless($media instanceof MediaAsset, 404);

        if ($media->getAttribute('extension') === 'pdf') {
            return $this->stream($document, $locale, false);
        }

        return $this->detail($document, $locale);
    }

    public function downloadFile(
        string $locale,
        string $slug,
    ): StreamedResponse {
        $this->setLocale($locale);

        $document = $this->query($locale)
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->stream($document, $locale, true);
    }

    /**
     * @return Builder<Document>
     */
    private function query(string $locale): Builder
    {
        return $this->uploads->publicQuery($locale);
    }

    private function detail(Document $document, string $locale): View
    {
        return view('public.documents.upload-show', [
            'document' => $document,
            'currentVersion' => $this->version($document, $locale),
            'currentLocale' => $locale,
            'languageVersions' => $this->languageVersions($locale, $document),
        ]);
    }

    private function version(Document $document, string $locale): DocumentVersion
    {
        $version = $this->uploads->currentVersion($document, $locale);

        abort_unless($version instanceof DocumentVersion, 404);

        return $version;
    }

    private function stream(
        Document $document,
        string $locale,
        bool $download,
    ): StreamedResponse {
        $version = $this->version($document, $locale);
        $media = $version->media;

        abort_unless($media instanceof MediaAsset, 404);

        $disk = $this->requiredString($media->getAttribute('disk'));
        $path = $this->requiredString($media->getAttribute('path'));
        $extension = $this->requiredString($media->getAttribute('extension'));

        $mime = DocumentUploadManager::TYPES[$extension] ?? null;

        abort_unless(is_string($mime), 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $base = Str::slug($document->titleForLocale('en'));

        $filename = ($base !== '' ? $base : 'document')
            .'-v'.$version->version
            .'.'.$extension;

        $headers = [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
        ];

        if ($download || $extension !== 'pdf') {
            return Storage::disk($disk)->download($path, $filename, $headers);
        }

        return Storage::disk($disk)->response(
            $path,
            $filename,
            $headers,
            'inline',
        );
    }

    /**
     * @return list<array{
     *     code: string,
     *     label: string,
     *     native_label: string,
     *     available: bool,
     *     active: bool,
     *     url: string
     * }>
     */
    private function languageVersions(
        string $locale,
        ?Document $document = null,
    ): array {
        $versions = [];

        foreach (['en' => 'English', 'si' => 'සිංහල'] as $code => $label) {
            if ($document === null) {
                $url = $code === 'en'
                    ? route('documents.index')
                    : route('documents.index.localized', ['locale' => $code]);
            } else {
                $url = $code === 'en'
                    ? route('documents.show', ['slug' => $document->slug])
                    : route('documents.show.localized', [
                        'locale' => $code,
                        'slug' => $document->slug,
                    ]);
            }

            $versions[] = [
                'code' => $code,
                'label' => $code === 'si' ? 'Sinhala' : 'English',
                'native_label' => $label,
                'available' => true,
                'active' => $code === $locale,
                'url' => $url,
            ];
        }

        return $versions;
    }

    private function setLocale(string $locale): void
    {
        abort_unless(in_array($locale, ['en', 'si'], true), 404);

        app()->setLocale($locale);
    }

    private function requiredString(mixed $value): string
    {
        abort_unless(is_string($value) && trim($value) !== '', 404);

        return trim($value);
    }
}
