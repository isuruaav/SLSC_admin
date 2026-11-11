<?php

namespace App\Http\Controllers\Admin;

use App\Models\Document;
use App\Services\DocumentUploadManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentVersionFileController
{
    public function __invoke(Document $document, int $version): StreamedResponse
    {
        Gate::authorize('documents.view');
        $file = $document->versions()->whereKey($version)->with('media')->firstOrFail()->media;
        abort_unless($file !== null && ! $file->trashed(), 404);
        abort_unless(in_array($file->mime_type, DocumentUploadManager::TYPES, true), 404);
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        return $disk->response($file->path, basename($file->original_name), [
            'Content-Type' => $file->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ], $file->extension === 'pdf' ? 'inline' : 'attachment');
    }
}
