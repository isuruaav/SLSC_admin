<?php

namespace App\Services;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use App\Support\PageSlugger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class PageDeletionService
{
    public function delete(
        Page $page,
        User $actor,
    ): void {
        Gate::forUser($actor)->authorize(
            'pages.delete',
        );

        $pageId = (int) $page->getKey();

        DB::transaction(function () use (
            $pageId,
            $actor,
        ): void {
            $lockedPage = Page::query()
                ->lockForUpdate()
                ->findOrFail($pageId);

            $status = $this->statusOf(
                $lockedPage,
            );

            $this->ensureDeletableStatus(
                $status,
            );

            $lockedPage->forceFill([
                'updated_by' => $actor->id,
            ])->save();

            $lockedPage->delete();

            app(AuditLogger::class)->log(
                event: 'pages.deleted',
                description: 'Website page moved to Trash.',
                actor: $actor,
                subject: $lockedPage,
                oldValues: [
                    'status' => $status->value,
                    'deleted' => false,
                ],
                newValues: [
                    'status' => $status->value,
                    'deleted' => true,
                ],
            );
        });
    }

    public function restore(
        int $pageId,
        User $actor,
    ): void {
        Gate::forUser($actor)->authorize(
            'pages.delete',
        );

        DB::transaction(function () use (
            $pageId,
            $actor,
        ): void {
            $lockedPage = Page::withTrashed()
                ->lockForUpdate()
                ->findOrFail($pageId);

            if (! $lockedPage->trashed()) {
                throw ValidationException::withMessages([
                    'workflow' => 'Only deleted pages may be restored.',
                ]);
            }

            $status = $this->statusOf(
                $lockedPage,
            );

            $this->ensureDeletableStatus(
                $status,
            );

            $oldSlug = (string) $lockedPage->slug;

            $lockedPage->forceFill([
                'slug' => PageSlugger::unique(
                    $oldSlug,
                    $pageId,
                    (string) $lockedPage->getRawOriginal('locale'),
                ),
                'updated_by' => $actor->id,
            ]);

            $lockedPage->restore();

            app(AuditLogger::class)->log(
                event: 'pages.restored',
                description: 'Website page restored from Trash.',
                actor: $actor,
                subject: $lockedPage,
                oldValues: [
                    'status' => $status->value,
                    'deleted' => true,
                    'slug' => $oldSlug,
                ],
                newValues: [
                    'status' => $status->value,
                    'deleted' => false,
                    'slug' => $lockedPage->slug,
                ],
            );
        });
    }

    private function statusOf(Page $page): PageStatus
    {
        $status = $page->getRawOriginal(
            'status',
        );

        if (! is_string($status)) {
            throw ValidationException::withMessages([
                'workflow' => 'The page workflow status is invalid.',
            ]);
        }

        $pageStatus = PageStatus::tryFrom(
            $status,
        );

        if (! $pageStatus instanceof PageStatus) {
            throw ValidationException::withMessages([
                'workflow' => 'The page workflow status is invalid.',
            ]);
        }

        return $pageStatus;
    }

    private function ensureDeletableStatus(
        PageStatus $status,
    ): void {
        if (
            in_array(
                $status,
                [
                    PageStatus::Draft,
                    PageStatus::Archived,
                ],
                true,
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'workflow' => 'Only Draft or Archived pages may be deleted.',
        ]);
    }
}
