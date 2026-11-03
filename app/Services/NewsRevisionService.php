<?php

namespace App\Services;

use App\Enums\NewsEditorMode;
use App\Enums\NewsLocale;
use App\Enums\NewsStatus;
use App\Models\MediaAsset;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class NewsRevisionService
{
    public function createSnapshot(
        News $news,
        User $actor,
        ?string $reason = null,
    ): NewsRevision {
        if (! $news->exists) {
            throw new RuntimeException(
                'A persisted news article is required to create a revision.',
            );
        }

        if ($news->trashed()) {
            throw new RuntimeException(
                'A deleted news article cannot create a revision.',
            );
        }

        return DB::transaction(
            function () use (
                $news,
                $actor,
                $reason,
            ): NewsRevision {
                $lockedNews = News::query()
                    ->whereKey(
                        $news->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                return $this->createSnapshotFromLockedNews(
                    news: $lockedNews,
                    actor: $actor,
                    reason: $reason,
                );
            },
            3,
        );
    }

    public function restore(
        News $news,
        NewsRevision $revision,
        User $actor,
    ): News {
        Gate::forUser(
            $actor,
        )->authorize(
            'news.update',
        );

        if (! $news->exists) {
            throw ValidationException::withMessages([
                'revision' => 'A persisted news article is required.',
            ]);
        }

        if ($news->trashed()) {
            throw ValidationException::withMessages([
                'revision' => 'A deleted news article cannot be restored.',
            ]);
        }

        if (! $revision->exists) {
            throw ValidationException::withMessages([
                'revision' => 'The selected revision does not exist.',
            ]);
        }

        return DB::transaction(
            function () use (
                $news,
                $revision,
                $actor,
            ): News {
                /*
                 * Lock the current article.
                 *
                 * This prevents another update/restore from
                 * modifying the same article while this restore
                 * operation is running.
                 */
                $lockedNews = News::query()
                    ->whereKey(
                        $news->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $status =
                    $lockedNews->getAttribute(
                        'status',
                    );

                /*
                 * Restoring old content is still an edit.
                 *
                 * Published / Approved / Archived content must
                 * not bypass the normal workflow.
                 */
                if (
                    ! $status instanceof NewsStatus
                    || $status === NewsStatus::Published
                ) {
                    throw ValidationException::withMessages([
                        'revision' => 'Unpublish the news article before restoring a revision.',
                    ]);
                }

                /*
                 * The revision must belong to this exact article.
                 */
                $revisionNewsId =
                    $revision->getAttribute(
                        'news_id',
                    );

                if (
                    ! is_numeric(
                        $revisionNewsId,
                    )
                    || (int) $revisionNewsId
                        !== (int) $lockedNews->getKey()
                ) {
                    throw ValidationException::withMessages([
                        'revision' => 'The selected revision does not belong to this news article.',
                    ]);
                }

                /*
                 * Reload the revision inside the transaction.
                 */
                $lockedRevision =
                    NewsRevision::query()
                        ->where(
                            'news_id',
                            $lockedNews->getKey(),
                        )
                        ->whereKey(
                            $revision->getKey(),
                        )
                        ->firstOrFail();

                $revisionNumber =
                    (int) $lockedRevision->getAttribute(
                        'revision_number',
                    );

                /*
                 * Validate relationships referenced by the old
                 * revision before touching the current article.
                 */
                $category =
                    $this->restorableCategory(
                        $lockedRevision,
                    );

                $featuredImage =
                    $this->restorableFeaturedImage(
                        $lockedRevision,
                    );

                /*
                 * Re-sanitize stored rich text before rendering
                 * it back into the live News record.
                 */
                $content =
                    $this->restorableContent(
                        $lockedRevision,
                    );

                $title =
                    $this->requiredRevisionText(
                        value: $lockedRevision->getAttribute(
                            'title',
                        ),
                        field: 'title',
                        maximumLength: 255,
                    );

                $summary =
                    $this->optionalRevisionText(
                        value: $lockedRevision->getAttribute(
                            'summary',
                        ),
                        maximumLength: 2000,
                    );

                $seoTitle =
                    $this->optionalRevisionText(
                        value: $lockedRevision->getAttribute(
                            'seo_title',
                        ),
                        maximumLength: 255,
                    );

                $seoDescription =
                    $this->optionalRevisionText(
                        value: $lockedRevision->getAttribute(
                            'seo_description',
                        ),
                        maximumLength: 320,
                    );

                $revisionSlug =
                    $this->requiredRevisionText(
                        value: $lockedRevision->getAttribute(
                            'slug',
                        ),
                        field: 'slug',
                        maximumLength: 255,
                    );

                $newsLocale = $this->newsLocale($lockedNews);

                $safeSlug =
                    $this->uniqueSlug(
                        candidate: $revisionSlug,
                        ignoreId: (int) $lockedNews->getKey(),
                        locale: $newsLocale,
                    );

                /*
                 * Capture the current state before restoring
                 * historical data.
                 *
                 * This makes every restore reversible.
                 */
                $backup =
                    $this->createSnapshotFromLockedNews(
                        news: $lockedNews,
                        actor: $actor,
                        reason: sprintf(
                            'Backup before restoring revision %d.',
                            $revisionNumber,
                        ),
                    );

                /*
                 * Keep bounded current values for the audit log.
                 */
                $oldTitle =
                    $lockedNews->getAttribute(
                        'title',
                    );

                $oldSlug =
                    $lockedNews->getAttribute(
                        'slug',
                    );

                $oldCategoryId =
                    $lockedNews->getAttribute(
                        'category_id',
                    );

                $oldFeaturedImageId =
                    $lockedNews->getAttribute(
                        'featured_image_id',
                    );

                $oldSummary =
                    $lockedNews->getAttribute(
                        'summary',
                    );

                $oldContent =
                    $lockedNews->getAttribute(
                        'content',
                    );

                /*
                 * IMPORTANT:
                 *
                 * We intentionally DO NOT restore:
                 *
                 * - status
                 * - submitted_at / submitted_by
                 * - approved_at / approved_by
                 * - published_by
                 * - archived_at / archived_by
                 *
                 * Historical content must never bypass the
                 * current workflow state.
                 */
                $lockedNews->forceFill([
                    'category_id' => (int) $category->getKey(),

                    'title' => $title,

                    'slug' => $safeSlug,

                    'summary' => $summary,

                    'content' => $content,

                    'editor_mode' => $lockedRevision->editor_mode->value,

                    'featured_image_id' => $featuredImage instanceof MediaAsset
                            ? (int) $featuredImage->getKey()
                            : null,

                    // Old revisions predate the gallery preference: preserve it.
                    'show_in_gallery' => $lockedRevision->getAttribute('show_in_gallery') === null
                        ? (bool) $lockedNews->getAttribute('show_in_gallery')
                        : (bool) $lockedRevision->getAttribute('show_in_gallery'),

                    'news_type' => $lockedRevision->getAttribute('news_type') ?? ($lockedNews->getAttribute('news_type') ?? 'news_feature'),

                    'is_featured' => (bool) $lockedRevision->getAttribute(
                        'is_featured',
                    ),

                    'published_at' => $lockedRevision->getAttribute(
                        'published_at',
                    ),

                    'seo_title' => $seoTitle,

                    'seo_description' => $seoDescription,

                    'updated_by' => $actor->id,
                ])->save();

                app(
                    AuditLogger::class,
                )->log(
                    event: 'news.revision-restored',

                    description: 'A news revision was restored.',

                    actor: $actor,

                    subject: $lockedNews,

                    oldValues: [
                        'title' => is_string(
                            $oldTitle,
                        )
                                ? $oldTitle
                                : null,

                        'slug' => is_string(
                            $oldSlug,
                        )
                                ? $oldSlug
                                : null,

                        'category_id' => is_numeric(
                            $oldCategoryId,
                        )
                                ? (int) $oldCategoryId
                                : null,

                        'featured_image_id' => is_numeric(
                            $oldFeaturedImageId,
                        )
                                ? (int) $oldFeaturedImageId
                                : null,

                        'summary_length' => is_string(
                            $oldSummary,
                        )
                                ? mb_strlen(
                                    $oldSummary,
                                )
                                : 0,

                        'content_length' => is_string(
                            $oldContent,
                        )
                                ? mb_strlen(
                                    $oldContent,
                                )
                                : 0,

                        'status' => $status->value,
                    ],

                    newValues: [
                        'restored_revision_id' => (int) $lockedRevision->getKey(),

                        'restored_revision_number' => $revisionNumber,

                        'backup_revision_id' => (int) $backup->getKey(),

                        'backup_revision_number' => (int) $backup->getAttribute(
                            'revision_number',
                        ),

                        'title' => $title,

                        'slug' => $safeSlug,

                        'category_id' => (int) $category->getKey(),

                        'featured_image_id' => $featuredImage instanceof MediaAsset
                                ? (int) $featuredImage->getKey()
                                : null,

                        'summary_length' => $summary !== null
                                ? mb_strlen(
                                    $summary,
                                )
                                : 0,

                        'content_length' => mb_strlen(
                            $content,
                        ),

                        /*
                         * Workflow state was preserved.
                         */
                        'status' => $status->value,
                    ],
                );

                return $lockedNews->refresh();
            },
            3,
        );
    }

    private function createSnapshotFromLockedNews(
        News $news,
        User $actor,
        ?string $reason,
    ): NewsRevision {
        $status =
            $news->getAttribute(
                'status',
            );

        if (! $status instanceof NewsStatus) {
            throw new RuntimeException(
                'News article has an invalid workflow status.',
            );
        }

        $revisionNumber =
            $this->nextRevisionNumber(
                $news,
            );

        $revision = new NewsRevision;

        $revision->forceFill([
            'news_id' => (int) $news->getKey(),

            'revision_number' => $revisionNumber,

            'locale' => $this->newsLocale($news)->value,

            'translation_group' => $this->nullableString(
                $news->getAttribute('translation_group'),
            ),

            'category_id' => $news->getAttribute(
                'category_id',
            ),

            'title' => (string) $news->getAttribute(
                'title',
            ),

            'slug' => (string) $news->getAttribute(
                'slug',
            ),

            'summary' => $this->nullableString(
                $news->getAttribute(
                    'summary',
                ),
            ),

            'content' => (string) $news->getAttribute(
                'content',
            ),

            'editor_mode' => $this->newsEditorMode($news)->value,

            'featured_image_id' => $news->getAttribute(
                'featured_image_id',
            ),

            'show_in_gallery' => (bool) $news->getAttribute('show_in_gallery'),

            'is_featured' => (bool) $news->getAttribute(
                'is_featured',
            ),

            'news_type' => $news->getAttribute('news_type') ?? 'news_feature',

            'status' => $status->value,

            'published_at' => $news->getAttribute(
                'published_at',
            ),

            'seo_title' => $this->nullableString(
                $news->getAttribute(
                    'seo_title',
                ),
            ),

            'seo_description' => $this->nullableString(
                $news->getAttribute(
                    'seo_description',
                ),
            ),

            'created_by' => (int) $actor->getKey(),

            'reason' => $this->normaliseReason(
                $reason,
            ),
        ])->save();

        return $revision;
    }

    private function restorableCategory(
        NewsRevision $revision,
    ): NewsCategory {
        $categoryId =
            $revision->getAttribute(
                'category_id',
            );

        if (! is_numeric($categoryId)) {
            throw ValidationException::withMessages([
                'revision' => 'The selected revision does not contain a valid category.',
            ]);
        }

        $category =
            NewsCategory::query()
                ->find(
                    (int) $categoryId,
                );

        if (! $category instanceof NewsCategory) {
            throw ValidationException::withMessages([
                'revision' => 'The category used by this revision is no longer available.',
            ]);
        }

        if (
            ! (bool) $category->getAttribute(
                'is_active',
            )
        ) {
            throw ValidationException::withMessages([
                'revision' => 'The category used by this revision is currently inactive.',
            ]);
        }

        return $category;
    }

    private function restorableFeaturedImage(
        NewsRevision $revision,
    ): ?MediaAsset {
        $featuredImageId =
            $revision->getAttribute(
                'featured_image_id',
            );

        if ($featuredImageId === null) {
            return null;
        }

        if (! is_numeric($featuredImageId)) {
            throw ValidationException::withMessages([
                'revision' => 'The featured image reference in this revision is invalid.',
            ]);
        }

        $media =
            MediaAsset::query()
                ->find(
                    (int) $featuredImageId,
                );

        if (! $media instanceof MediaAsset) {
            throw ValidationException::withMessages([
                'revision' => 'The featured image used by this revision is no longer available.',
            ]);
        }

        if (! $media->isImage()) {
            throw ValidationException::withMessages([
                'revision' => 'The featured media stored in this revision is not an image.',
            ]);
        }

        if (! $media->isPublic()) {
            throw ValidationException::withMessages([
                'revision' => 'The featured image stored in this revision is not Public media.',
            ]);
        }

        return $media;
    }

    private function restorableContent(
        NewsRevision $revision,
    ): string {
        $rawContent = $revision->getAttribute('content');

        if (! is_string($rawContent) || trim($rawContent) === '') {
            throw ValidationException::withMessages([
                'revision' => 'The selected revision does not contain valid content.',
            ]);
        }

        $editorMode = $revision->editor_mode;

        $sanitizer = app(PageHtmlSanitizer::class);

        $content = $editorMode === NewsEditorMode::Visual
            ? $sanitizer->sanitizeVisual($rawContent)
            : $sanitizer->sanitize($rawContent);

        if (trim(strip_tags($content)) === '') {
            throw ValidationException::withMessages([
                'revision' => 'The selected revision does not contain readable content.',
            ]);
        }

        return $content;
    }

    private function requiredRevisionText(
        mixed $value,
        string $field,
        int $maximumLength,
    ): string {
        if (! is_string($value)) {
            throw ValidationException::withMessages([
                'revision' => sprintf(
                    'The revision contains an invalid %s.',
                    $field,
                ),
            ]);
        }

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            throw ValidationException::withMessages([
                'revision' => sprintf(
                    'The revision does not contain a valid %s.',
                    $field,
                ),
            ]);
        }

        if (
            mb_strlen(
                $value,
            ) > $maximumLength
        ) {
            throw ValidationException::withMessages([
                'revision' => sprintf(
                    'The revision %s exceeds the allowed length.',
                    $field,
                ),
            ]);
        }

        return $value;
    }

    private function optionalRevisionText(
        mixed $value,
        int $maximumLength,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            return null;
        }

        return mb_substr(
            $value,
            0,
            $maximumLength,
        );
    }

    private function uniqueSlug(
        string $candidate,
        int $ignoreId,
        NewsLocale $locale = NewsLocale::English,
    ): string {
        $baseSlug =
            Str::slug(
                $candidate,
            );

        if ($baseSlug === '') {
            $baseSlug =
                'news';
        }

        $baseSlug =
            mb_substr(
                $baseSlug,
                0,
                240,
            );

        $slug =
            $baseSlug;

        $counter =
            2;

        while (
            News::query()
                ->withTrashed()
                ->where(
                    'locale',
                    $locale->value,
                )
                ->where(
                    'slug',
                    $slug,
                )
                ->where(
                    'id',
                    '!=',
                    $ignoreId,
                )
                ->exists()
        ) {
            $suffix =
                '-'.$counter;

            $slug =
                mb_substr(
                    $baseSlug,
                    0,
                    255 - mb_strlen(
                        $suffix,
                    ),
                )
                .$suffix;

            $counter++;
        }

        return $slug;
    }

    private function newsLocale(News $news): NewsLocale
    {
        $rawLocale = $news->getRawOriginal('locale');

        if (! is_string($rawLocale)) {
            return NewsLocale::English;
        }

        return NewsLocale::tryFrom($rawLocale)
            ?? NewsLocale::English;
    }

    private function newsEditorMode(News $news): NewsEditorMode
    {
        $rawEditorMode = $news->getRawOriginal('editor_mode');

        if (! is_string($rawEditorMode)) {
            return NewsEditorMode::Visual;
        }

        return NewsEditorMode::tryFrom($rawEditorMode)
            ?? NewsEditorMode::Visual;
    }

    private function nextRevisionNumber(
        News $news,
    ): int {
        $maximum =
            NewsRevision::query()
                ->where(
                    'news_id',
                    $news->getKey(),
                )
                ->max(
                    'revision_number',
                );

        if (! is_numeric($maximum)) {
            return 1;
        }

        return ((int) $maximum) + 1;
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function normaliseReason(
        ?string $reason,
    ): ?string {
        if ($reason === null) {
            return null;
        }

        $reason =
            trim(
                $reason,
            );

        if ($reason === '') {
            return null;
        }

        return mb_substr(
            $reason,
            0,
            255,
        );
    }
}
