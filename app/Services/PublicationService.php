<?php

namespace App\Services;

use App\Models\Publication;
use App\Models\User;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class PublicationService
{
    // Publications use the existing document permissions and private file disk.
    public function save(User $actor, string $title, string $titleSi, bool $publish, ?UploadedFile $pdf, ?UploadedFile $cover, ?Publication $publication = null, string $locale = 'en', string $issueNumber = '', ?int $issueYear = null): Publication
    {
        Gate::forUser($actor)->authorize('documents.view');
        Gate::forUser($actor)->authorize($publication === null ? 'documents.create' : 'documents.update');
        if ($publish || $publication?->is_published) {
            Gate::forUser($actor)->authorize('documents.publish');
        }
        $title = trim($title);
        $titleSi = trim($titleSi);
        $isCreating = $publication === null;
        $replacingPdf = $pdf !== null;
        Validator::make(compact('title', 'titleSi', 'pdf', 'cover', 'locale', 'issueNumber', 'issueYear'), [
            'title' => ['required', 'string', 'max:255'],
            'titleSi' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', 'string', 'in:en,si'],
            'issueNumber' => ['nullable', 'string', 'max:80'],
            'issueYear' => ['nullable', 'integer', 'between:1900,9999'],
            'pdf' => [$isCreating ? 'required' : 'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'extensions:pdf', 'max:51200'],
            'cover' => [$isCreating || $replacingPdf ? 'required' : 'nullable', 'file', 'image', 'mimes:jpg,jpeg', 'max:2048', 'dimensions:max_width=1400,max_height=1600'],
        ], ['cover.required' => 'Upload the first-page cover image before saving.'])->validate();
        if ($pdf !== null && (new finfo(FILEINFO_MIME_TYPE))->file($pdf->getPathname()) !== 'application/pdf') {
            throw ValidationException::withMessages(['pdf' => 'The selected file is not a valid PDF.']);
        }
        if ($pdf !== null || $cover !== null) {
            Gate::forUser($actor)->authorize('media.upload');
        }
        $disk = (string) config('media.disks.private', 'local');
        if ($disk === '' || $disk === config('media.disks.public', 'public') || config('filesystems.disks.'.$disk.'.visibility') === 'public') {
            throw new RuntimeException('Publication uploads require a private filesystem disk.');
        }
        /** @var list<string> $newFiles */
        $newFiles = [];
        $oldFiles = null;
        try {
            $uploads = [];
            if ($pdf !== null) {
                $pdfPath = $pdf->store('publications/pdfs', $disk);
                if (! is_string($pdfPath) || $pdfPath === '') {
                    throw new RuntimeException('The PDF could not be stored.');
                }
                $newFiles[] = $pdfPath;
                $uploads = [
                    'pdf_path' => $pdfPath,
                    'original_name' => mb_substr(basename(str_replace('\\', '/', $pdf->getClientOriginalName())), 0, 255),
                    'size_bytes' => $pdf->getSize(),
                ];
            }
            if ($cover !== null) {
                $coverPath = $cover->store('publications/covers', $disk);
                if (! is_string($coverPath) || $coverPath === '') {
                    throw new RuntimeException('The publication cover could not be stored.');
                }
                $newFiles[] = $coverPath;
                $dimensions = getimagesize($cover->getPathname());
                if ($dimensions === false) {
                    throw ValidationException::withMessages(['cover' => 'The PDF preview image is invalid.']);
                }
                $uploads['cover_path'] = $coverPath;
                $uploads['cover_width'] = $dimensions[0];
                $uploads['cover_height'] = $dimensions[1];
            }
            if ($uploads !== []) {
                $uploads['disk'] = $disk;
            }
            $saved = DB::transaction(function () use ($actor, $title, $titleSi, $publish, $publication, $uploads, $locale, $issueNumber, $issueYear, &$oldFiles): Publication {
                $record = $publication === null ? new Publication : Publication::query()->lockForUpdate()->whereKey($publication->getKey())->firstOrFail();
                if ($record->is_published) {
                    Gate::forUser($actor)->authorize('documents.publish');
                }
                if ($record->exists && $uploads !== []) {
                    $paths = [];
                    if (array_key_exists('pdf_path', $uploads)) {
                        $paths[] = $record->pdf_path;
                    }
                    if (array_key_exists('cover_path', $uploads)) {
                        $paths[] = $record->cover_path;
                    }
                    $oldFiles = ['disk' => $record->disk, 'paths' => $paths];
                }
                $creating = ! $record->exists;
                $record->fill([
                    ...$uploads, 'title' => $title, 'title_si' => $titleSi !== '' ? $titleSi : null, 'locale' => $locale,
                    'is_published' => $publish, 'issue_number' => trim($issueNumber) ?: null, 'issue_year' => $issueYear,
                    'published_at' => $publish ? ($record->published_at ?? now()) : null,
                    'created_by' => $record->created_by ?? $actor->id, 'updated_by' => $actor->id,
                ])->save();
                app(AuditLogger::class)->log(
                    event: $creating ? 'publications.created' : 'publications.updated',
                    description: $creating ? 'Publication PDF uploaded.' : 'Publication updated.',
                    actor: $actor, subject: $record,
                    newValues: ['title' => $title, 'locale' => $locale, 'is_published' => $publish],
                );

                return $record;
            });
        } catch (Throwable $exception) {
            if ($newFiles !== []) {
                Storage::disk($disk)->delete($newFiles);
            }
            throw $exception;
        }
        if ($oldFiles !== null) {
            // The record is committed before removing a superseded private file.
            try {
                Storage::disk($oldFiles['disk'])->delete($oldFiles['paths']);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $saved;
    }

    public function setPublished(Publication $publication, User $actor, bool $published): void
    {
        Gate::forUser($actor)->authorize('documents.view');
        Gate::forUser($actor)->authorize('documents.publish');
        DB::transaction(function () use ($publication, $actor, $published): void {
            $record = Publication::query()->lockForUpdate()->whereKey($publication->getKey())->firstOrFail();
            $record->update(['is_published' => $published, 'published_at' => $published ? now() : null, 'updated_by' => $actor->id]);
            app(AuditLogger::class)->log(
                event: $published ? 'publications.published' : 'publications.unpublished',
                description: $published ? 'Publication published.' : 'Publication returned to draft.',
                actor: $actor, subject: $record,
            );
        });
    }

    public function delete(Publication $publication, User $actor): void
    {
        Gate::forUser($actor)->authorize('documents.view');
        Gate::forUser($actor)->authorize('documents.delete');
        DB::transaction(function () use ($publication, $actor): void {
            $publication->delete();
            app(AuditLogger::class)->log(event: 'publications.deleted', description: 'Publication moved to trash.', actor: $actor, subject: $publication);
        });
    }

    public function restore(Publication $publication, User $actor): void
    {
        Gate::forUser($actor)->authorize('documents.view');
        Gate::forUser($actor)->authorize('documents.delete');
        DB::transaction(function () use ($publication, $actor): void {
            $record = Publication::onlyTrashed()->lockForUpdate()->whereKey($publication->getKey())->firstOrFail();
            $record->forceFill(['is_published' => false, 'published_at' => null, 'updated_by' => $actor->id])->save();
            $record->restore();
            app(AuditLogger::class)->log(event: 'publications.restored', description: 'Publication restored as a draft.', actor: $actor, subject: $record);
        });
    }
}
