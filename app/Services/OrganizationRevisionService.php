<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationRevision;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class OrganizationRevisionService
{
    public function capture(Organization $organization, ?int $actorId, string $summary): OrganizationRevision
    {
        $organization->refresh();

        return OrganizationRevision::query()->create([
            'organization_id' => $organization->id, 'created_by' => $actorId, 'summary' => $summary,
            'snapshot' => [
                'attributes' => $organization->only($organization->getFillable()),
                'translations' => $organization->translations()->get()->map(fn ($row) => Arr::except($row->toArray(), ['id', 'organization_id', 'created_at', 'updated_at']))->all(),
                'blocks' => $organization->contentBlocks()->get()->map(fn ($row) => Arr::except($row->toArray(), ['id', 'organization_id', 'created_at', 'updated_at']))->all(),
            ],
        ]);
    }

    public function restore(int $revisionId, User $actor): Organization
    {
        Gate::forUser($actor)->authorize('organizations.update');

        return DB::transaction(function () use ($revisionId, $actor): Organization {
            $revision = OrganizationRevision::query()->findOrFail($revisionId);
            $organization = Organization::query()->lockForUpdate()->whereKey($revision->organization_id)->firstOrFail();
            if ($organization->status === OrganizationStatus::Published) {
                Gate::forUser($actor)->authorize('organizations.publish');
            }
            $this->capture($organization, $actor->id, 'Before restoring a previous version');
            $snapshot = $revision->snapshot;
            $organization->fill($snapshot['attributes']);
            $organization->fill(['status' => OrganizationStatus::Draft, 'published_at' => null, 'updated_by' => $actor->id])->save();
            $organization->translations()->delete();
            $organization->contentBlocks()->delete();
            foreach ($snapshot['translations'] as $translation) {
                $organization->translations()->create($translation);
            }
            foreach ($snapshot['blocks'] as $block) {
                $organization->contentBlocks()->create($block);
            }
            app(AuditLogger::class)->log(event: 'organizations.restored', description: 'Organization content restored as a draft.', actor: $actor, subject: $organization);

            return $organization;
        });
    }
}
