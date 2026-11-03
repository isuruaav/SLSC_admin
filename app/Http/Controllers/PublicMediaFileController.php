<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Services\MediaUrlService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicMediaFileController extends Controller
{
    public function show(string $filename, MediaUrlService $urls): StreamedResponse
    {
        $media = MediaAsset::query()
            ->where('public_filename', $filename)
            ->firstOrFail();

        return $this->__invoke($media, $urls->originalFileName($media), $urls);
    }

    public function __invoke(
        MediaAsset $media,
        string $filename,
        MediaUrlService $urls,
    ): StreamedResponse {
        abort_unless($urls->original($media) !== null, 404);
        abort_unless($filename === $urls->originalFileName($media), 404);

        $disk = (string) $media->getAttribute('disk');
        $path = (string) $media->getAttribute('path');

        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, $filename, [
            'Content-Type' => $media->getAttribute('mime_type') ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
        ], 'inline');
    }
}
