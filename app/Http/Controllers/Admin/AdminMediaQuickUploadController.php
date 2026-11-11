<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaUploadService;
use App\Services\MediaUrlService;
use App\Services\SiteUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class AdminMediaQuickUploadController extends Controller
{
    public function upload(
        Request $request,
        MediaUploadService $uploadService,
        MediaUrlService $urlService,
    ): JsonResponse {
        abort_unless(
            Auth::check() && (
                Gate::allows('media.upload') ||
                Gate::allows('pages.create') ||
                Gate::allows('pages.edit') ||
                Gate::allows('news.create') ||
                Gate::allows('news.edit')
            ),
            403,
            'Unauthorized to upload media'
        );

        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:20480'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        $uploadedFile = $request->file('file');
        $rawTitle = $request->input('title');
        $title = is_string($rawTitle) && trim($rawTitle) !== ''
            ? trim($rawTitle)
            : pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

        $rawAlt = $request->input('alt_text');
        $altText = is_string($rawAlt) && trim($rawAlt) !== ''
            ? trim($rawAlt)
            : $title;

        $media = $uploadService->upload(
            file: $uploadedFile,
            type: MediaType::Image,
            visibility: MediaVisibility::Public,
            actor: $actor,
            title: $title,
            altText: $altText,
            reuseExisting: true,
        );

        $fullUrl = $urlService->namedOriginal($media) ?? $urlService->original($media);
        $rootPath = $fullUrl !== null ? app(SiteUrlService::class)->portable($fullUrl) : null;

        return response()->json([
            'success' => true,
            'reused' => ! $media->wasRecentlyCreated,
            'id' => $media->id,
            'url' => $fullUrl,
            'path' => $rootPath,
            'name' => $media->original_name,
            'alt' => $altText,
        ]);
    }

    public function recentImages(
        Request $request,
        MediaUrlService $urlService,
    ): JsonResponse {
        abort_unless(
            Auth::check() && (
                Gate::allows('media.view') ||
                Gate::allows('pages.view') ||
                Gate::allows('pages.create') ||
                Gate::allows('pages.edit') ||
                Gate::allows('news.view') ||
                Gate::allows('news.create') ||
                Gate::allows('news.edit')
            ),
            403,
            'Unauthorized to view media'
        );

        $search = trim((string) $request->query('q', ''));
        $query = MediaAsset::query()
            ->where('type', MediaType::Image->value)
            ->where('visibility', MediaVisibility::Public->value)
            ->where('source', 'upload')
            ->where('disk', config('media.disks.public', 'public'));

        if ($search !== '') {
            $query->where(static function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('public_filename', 'like', "%{$search}%");
            });
        }

        $images = $query->latest()
            ->take(32)
            ->get()
            ->map(static function (MediaAsset $m) use ($urlService) {
                $filename = (string) ($m->public_filename ?: $m->original_name);
                $fullUrl = $urlService->namedOriginal($m) ?? $urlService->original($m);
                $thumbUrl = $urlService->thumbnailOrOriginal($m) ?? $fullUrl;

                return [
                    'id' => $m->id,
                    'title' => (string) ($m->title ?: $m->original_name),
                    'filename' => $filename,
                    'url' => $fullUrl,
                    'path' => $fullUrl !== null ? app(SiteUrlService::class)->portable($fullUrl) : null,
                    'alt' => (string) ($m->alt_text ?: ($m->title ?: $m->original_name)),
                    'thumb' => $thumbUrl,
                ];
            });

        return response()->json([
            'success' => true,
            'images' => $images,
        ]);
    }
}
