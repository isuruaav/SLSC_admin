<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DocumentUploadManager
{
    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function save(
        User $actor,
        ?int $documentId,
        string $title,
        string $titleSi,
        ?MediaAsset $englishFile,
        ?MediaAsset $sinhalaFile,
        bool $publish = true,
    ): Document {
        Gate::forUser($actor)->authorize(
            $documentId === null
                ? 'documents.create'
                : 'documents.update',
        );

        if ($publish) {
            Gate::forUser($actor)->authorize('documents.publish');
        }

        $title = trim(strip_tags($title));
        $titleSi = trim(strip_tags($titleSi));

        if ($title === '' || mb_strlen($title) > 255) {
            throw ValidationException::withMessages([
                'title' => 'Enter an English title of up to 255 characters.',
            ]);
        }

        if (mb_strlen($titleSi) > 255) {
            throw ValidationException::withMessages([
                'titleSi' => 'The Sinhala title may not exceed 255 characters.',
            ]);
        }

        if ($englishFile !== null) {
            $this->assertFile($englishFile, 'englishFile');
        }

        if ($sinhalaFile !== null) {
            $this->assertFile($sinhalaFile, 'sinhalaFile');
        }

        return DB::transaction(function () use (
            $actor,
            $documentId,
            $title,
            $titleSi,
            $englishFile,
            $sinhalaFile,
            $publish,
        ): Document {
            $document = $documentId === null
                ? new Document
                : Document::query()
                    ->lockForUpdate()
                    ->whereKey($documentId)
                    ->firstOrFail();

            if ($document->exists && $document->isPublished()) {
                Gate::forUser($actor)->authorize('documents.publish');
            }
            $isNew = ! $document->exists;

            $oldValues = $isNew ? [] : [
                'title' => $document->getAttribute('title'),
                'title_si' => $document->getAttribute('title_si'),
                'current_version' => $document->getAttribute('current_version'),
                'current_version_si' => $document->getAttribute('current_version_si'),
                'status' => $document->getRawOriginal('status'),
            ];

            if ($isNew) {
                $document->forceFill([
                    'slug' => 'document-'.Str::uuid()->toString(),
                    'created_by' => $actor->id,
                    'current_version' => 0,
                    'current_version_si' => 0,
                    'status' => DocumentStatus::Draft,
                ]);
            }

            $document->forceFill([
                'title' => $title,
                'title_si' => $titleSi !== '' ? $titleSi : null,
                'updated_by' => $actor->id,
            ])->save();

            $currentSlug = $document->getAttribute('slug');

            $hasGeneratedSlug = is_string($currentSlug)
                && preg_match(
                    '/^document-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                    $currentSlug,
                ) === 1;

            if ($isNew || $hasGeneratedSlug) {
                $base = Str::slug($title);
                $base = $base !== ''
                    ? rtrim(Str::limit($base, 180, ''), '-')
                    : 'document';

                $candidate = $base.'-'.$document->getKey();
                $slug = $candidate;
                $suffix = 2;

                while (
                    Document::withTrashed()
                        ->where('slug', $slug)
                        ->where('id', '!=', $document->getKey())
                        ->exists()
                ) {
                    $slug = $candidate.'-'.$suffix;
                    $suffix++;
                }

                $document->forceFill([
                    'slug' => $slug,
                ])->save();
            }

            if ($englishFile !== null) {
                $this->attachVersion($document, $englishFile, $actor, 'en');
            }

            if ($sinhalaFile !== null) {
                $this->attachVersion($document, $sinhalaFile, $actor, 'si');
            }

            $englishVersion = (int) $document->getAttribute('current_version');
            $sinhalaVersion = (int) $document->getAttribute('current_version_si');

            if ($englishVersion < 1 && $sinhalaVersion < 1) {
                throw ValidationException::withMessages([
                    'englishFile' => 'Upload at least one English or Sinhala file.',
                ]);
            }

            foreach ([
                'englishFile' => $englishVersion,
                'sinhalaFile' => $sinhalaVersion,
            ] as $field => $number) {
                if ($number < 1) {
                    continue;
                }

                $version = DocumentVersion::query()
                    ->where('document_id', $document->getKey())
                    ->where('version', $number)
                    ->with('media')
                    ->first();

                $this->assertFile($version?->media, $field);
            }

            $document->forceFill([
                'status' => $publish ? DocumentStatus::Published : DocumentStatus::Draft,
                'published_at' => $publish ? ($document->published_at ?? now()) : null,
                'published_by' => $publish ? $actor->id : null,
                'archived_at' => null,
                'archived_by' => null,
            ])->save();

            app(AuditLogger::class)->log(
                event: $isNew
                    ? 'documents.upload-created'
                    : 'documents.upload-updated',
                description: $publish ? 'A bilingual document upload was published.' : 'A bilingual document draft was saved.',
                actor: $actor,
                subject: $document,
                oldValues: $oldValues,
                newValues: [
                    'title' => $title,
                    'title_si' => $titleSi,
                    'current_version' => $document->getAttribute('current_version'),
                    'current_version_si' => $document->getAttribute('current_version_si'),
                    'status' => $publish ? DocumentStatus::Published->value : DocumentStatus::Draft->value,
                ],
            );

            return $document->refresh();
        });
    }

    public function delete(int $documentId, User $actor): void
    {
        Gate::forUser($actor)->authorize('documents.delete');

        DB::transaction(function () use ($documentId, $actor): void {
            $document = Document::query()
                ->lockForUpdate()
                ->whereKey($documentId)
                ->firstOrFail();

            if ($document->getAttribute('status') === DocumentStatus::Published) {
                Gate::forUser($actor)->authorize('documents.publish');
            }

            $document->delete();

            app(AuditLogger::class)->log(
                event: 'documents.upload-deleted',
                description: 'A document upload was deleted.',
                actor: $actor,
                subject: $document,
            );
        });
    }

    /** @return Builder<Document> */
    public function publicQuery(string $locale): Builder
    {
        $primary = $locale === 'si' ? 'current_version_si' : 'current_version';
        $fallback = $locale === 'si' ? 'current_version' : 'current_version_si';

        return Document::query()->published()
            ->where(function (Builder $query) use ($primary, $fallback): void {
                $query->where(function (Builder $preferred) use ($primary): void {
                    $preferred->where($primary, '>', 0)
                        ->whereHas('versions', function (Builder $versions) use ($primary): void {
                            $versions->whereColumn('document_versions.version', 'documents.'.$primary)
                                ->whereHas('media', function (Builder $media): void {
                                    /** @var Builder<MediaAsset> $media */
                                    $this->applyFileRules($media);
                                });
                        });
                })->orWhere(function (Builder $other) use ($primary, $fallback): void {
                    $other->where($primary, 0)->where($fallback, '>', 0)
                        ->whereHas('versions', function (Builder $versions) use ($fallback): void {
                            $versions->whereColumn('document_versions.version', 'documents.'.$fallback)
                                ->whereHas('media', function (Builder $media): void {
                                    /** @var Builder<MediaAsset> $media */
                                    $this->applyFileRules($media);
                                });
                        });
                });
            });
    }

    public function versionNumber(Document $document, string $locale): int
    {
        $english = (int) $document->getAttribute('current_version');
        $sinhala = (int) $document->getAttribute('current_version_si');

        return $locale === 'si'
            ? ($sinhala > 0 ? $sinhala : $english)
            : ($english > 0 ? $english : $sinhala);
    }

    public function currentVersion(
        Document $document,
        string $locale,
    ): ?DocumentVersion {
        $number = $this->versionNumber($document, $locale);

        if ($number < 1) {
            return null;
        }

        return DocumentVersion::query()
            ->where('document_id', $document->getKey())
            ->where('version', $number)
            ->whereHas('media', function (Builder $query): void {
                /** @var Builder<MediaAsset> $query */
                $this->applyFileRules($query);
            })
            ->with('media')
            ->first();
    }

    /**
     * @param  Builder<MediaAsset>  $query
     */
    public function applyFileRules(Builder $query): void
    {
        $query
            ->where('type', MediaType::Document->value)
            ->where('visibility', MediaVisibility::Public->value)
            ->where('source', MediaSource::Upload->value)
            ->whereNotNull('disk')
            ->where('disk', '!=', '')
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->where(function (Builder $types): void {
                foreach (self::TYPES as $extension => $mime) {
                    $types->orWhere(function (Builder $pair) use ($extension, $mime): void {
                        $pair->where('extension', $extension)
                            ->where('mime_type', $mime);
                    });
                }
            });
    }

    private function attachVersion(
        Document $document,
        MediaAsset $media,
        User $actor,
        string $locale,
    ): void {
        $latest = DocumentVersion::query()
            ->where('document_id', $document->getKey())
            ->max('version');

        $number = (is_numeric($latest) ? (int) $latest : 0) + 1;

        DocumentVersion::query()->create([
            'document_id' => $document->getKey(),
            'media_asset_id' => $media->getKey(),
            'version' => $number,
            'version_label' => strtoupper($locale).' file',
            'change_note' => null,
            'uploaded_by' => $actor->id,
        ]);

        $document->forceFill([
            $locale === 'si' ? 'current_version_si' : 'current_version' => $number,
        ])->save();
    }

    private function assertFile(?MediaAsset $media, string $field): void
    {
        if (
            $media === null
            || $media->trashed()
            || ! MediaAsset::query()
                ->whereKey($media->getKey())
                ->where(function (Builder $query): void {
                    $this->applyFileRules($query);
                })
                ->exists()
        ) {
            throw ValidationException::withMessages([
                $field => 'Select a valid public PDF, Word or Excel file.',
            ]);
        }

        $disk = $media->getAttribute('disk');
        $path = $media->getAttribute('path');

        if (
            ! is_string($disk)
            || ! is_string($path)
            || ! Storage::disk($disk)->exists($path)
        ) {
            throw ValidationException::withMessages([
                $field => 'The stored document file could not be found.',
            ]);
        }
    }

    public function restore(int $id, User $actor): void
    {
        Gate::forUser($actor)->authorize('documents.delete');
        DB::transaction(function () use ($id, $actor): void {
            $document = Document::onlyTrashed()->lockForUpdate()->whereKey($id)->firstOrFail();
            $document->forceFill(['status' => DocumentStatus::Draft, 'published_at' => null, 'published_by' => null, 'updated_by' => $actor->id])->save();
            $document->restore();
            app(AuditLogger::class)->log(event: 'documents.restored', description: 'Document restored as a private draft.', actor: $actor, subject: $document);
        });
    }

    public function restoreUploadVersion(Document $document, int $versionId, User $actor): void
    {
        Gate::forUser($actor)->authorize('documents.update');
        DB::transaction(function () use ($document, $versionId, $actor): void {
            $record = Document::query()->lockForUpdate()->whereKey($document->getKey())->firstOrFail();
            if ($record->isPublished()) {
                Gate::forUser($actor)->authorize('documents.publish');
            }
            $version = $record->versions()->with('media')->whereKey($versionId)->firstOrFail();
            $this->assertFile($version->media, 'version');
            $column = str_starts_with((string) $version->version_label, 'SI ') ? 'current_version_si' : 'current_version';
            $record->forceFill([$column => $version->version, 'updated_by' => $actor->id])->save();
            app(AuditLogger::class)->log(event: 'documents.version-restored', description: 'A previous document file was restored.', actor: $actor, subject: $record, newValues: [$column => $version->version]);
        });
    }
}
