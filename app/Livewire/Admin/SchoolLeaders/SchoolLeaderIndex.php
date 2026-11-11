<?php

namespace App\Livewire\Admin\SchoolLeaders;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Enums\SchoolLeadershipRank;
use App\Models\MediaAsset;
use App\Models\SchoolLeader;
use App\Models\SchoolLeadershipPosition;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MediaUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class SchoolLeaderIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public bool $showEditor = false;

    public string $roleKey = '';

    public string $rank = '';

    public string $imageMediaId = '';

    /**
     * Livewire temporarily hydrates uploaded files internally,

     * therefore this property must remain mixed.
     */
    public mixed $newImage = null;

    public string $newImageTitle = '';

    public string $newImageAltText = '';

    public string $nameEn = '';

    public string $nameSi = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $secondaryTenureEn = '';

    public string $secondaryTenureSi = '';

    public bool $isCurrent = false;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $positionFilter = 'all';

    public int $perPage = 10;

    public string $sortField = 'appointment';

    public string $sortDirection = 'asc';

    public function mount(): void
    {

        Gate::authorize('school-leaders.view');

        $this->resetForm();

    }

    public function startCreate(): void
    {

        Gate::authorize('school-leaders.update');

        $this->resetForm();

        $this->showEditor = true;

    }

    public function edit(int $id): void
    {

        Gate::authorize('school-leaders.view');

        Gate::authorize('school-leaders.update');

        $leader = SchoolLeader::query()

            ->with('position')

            ->findOrFail($id);

        $position = $leader->position;

        abort_unless(

            $position instanceof SchoolLeadershipPosition,

            404,

        );

        $this->editingId = $leader->id;

        $this->roleKey = $position->key;

        $this->rank = is_string($leader->rank)

            ? $leader->rank

            : '';

        $this->imageMediaId = $leader->image_media_id === null

            ? ''

            : (string) $leader->image_media_id;

        $this->nameEn = is_string($leader->name_en)

            ? $leader->name_en

            : '';

        $this->nameSi = is_string($leader->name_si)

            ? $leader->name_si

            : '';

        $this->startDate = $leader->start_date?->format('Y-m-d') ?? '';

        $this->endDate = $leader->end_date?->format('Y-m-d') ?? '';

        $this->secondaryTenureEn = is_string($leader->secondary_tenure_en) ? $leader->secondary_tenure_en : '';

        $this->secondaryTenureSi = is_string($leader->secondary_tenure_si) ? $leader->secondary_tenure_si : '';

        $this->isCurrent = $leader->end_date === null;

        $this->newImage = null;

        $this->newImageTitle = '';

        $this->newImageAltText = '';

        $this->resetValidation();

        $this->showEditor = true;

    }

    public function closeEditor(): void
    {
        Gate::authorize('school-leaders.view');
        Gate::authorize('school-leaders.update');

        $this->showEditor = false;
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        if (! in_array(
            $this->statusFilter,
            [
                'all',
                'present',
                'past',
            ],
            true,
        )) {
            $this->statusFilter = 'all';
        }

        $this->resetPage();
    }

    public function updatedPositionFilter(): void
    {
        if (
            $this->positionFilter !== 'all'
            && ! ctype_digit($this->positionFilter)
        ) {
            $this->positionFilter = 'all';
        }

        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array(
            $this->perPage,
            [
                10,
                25,
                50,
            ],
            true,
        )) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array(
            $field,
            [
                'appointment',
                'name',
                'period',
            ],
            true,
        )) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc'
                ? 'desc'
                : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function resetTableFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->positionFilter = 'all';

        $this->resetPage();
    }

    public function updatedRoleKey(): void
    {

        $position = SchoolLeadershipPosition::query()

            ->where('key', $this->roleKey)

            ->first();

        $selectedRank = SchoolLeadershipRank::tryFrom(

            $this->rank,

        );

        if (! $position instanceof SchoolLeadershipPosition) {

            $this->rank = '';

            $this->resetValidation([

                'roleKey',

                'rank',

            ]);

            return;

        }

        if (

            $selectedRank instanceof SchoolLeadershipRank

            && $selectedRank->isCommissioned()

                !== $position->requiresCommissionedRank()

        ) {

            $this->rank = '';

        }

        $this->resetValidation([

            'roleKey',

            'rank',

        ]);

    }

    public function updatedIsCurrent(): void
    {

        if ($this->isCurrent) {

            $this->endDate = '';

        }

        $this->resetValidation([

            'isCurrent',

            'endDate',

        ]);

    }

    public function save(): void
    {

        Gate::authorize('school-leaders.view');

        Gate::authorize('school-leaders.update');

        $validated = $this->validate([

            'roleKey' => [

                'required',

                'string',

                'max:100',

                Rule::exists(

                    'school_leadership_positions',

                    'key',

                ),

            ],

            'rank' => [

                'required',

                'string',

                Rule::enum(SchoolLeadershipRank::class),

            ],

            'imageMediaId' => [

                'nullable',

                'integer',

                'exists:media_assets,id',

            ],

            'newImage' => [

                'nullable',

                'file',

                'image',

                'max:20480',

            ],

            'newImageTitle' => [

                'nullable',

                'string',

                'max:255',

            ],

            'newImageAltText' => [

                'nullable',

                'string',

                'max:255',

            ],

            'nameEn' => [

                'required',

                'string',

                'max:180',

            ],

            'nameSi' => [

                'required',

                'string',

                'max:220',

            ],

            'startDate' => [

                'required',

                'date',

            ],

            'endDate' => [

                $this->isCurrent ? 'nullable' : 'required',

                'date',

                'after_or_equal:startDate',

            ],

            'isCurrent' => [

                'boolean',

            ],

            'secondaryTenureEn' => [

                'nullable',

                'string',

                'max:1000',

            ],

            'secondaryTenureSi' => [

                'nullable',

                'string',

                'max:1000',

            ],

        ]);

        $position = SchoolLeadershipPosition::query()

            ->where('key', $this->roleKey)

            ->firstOrFail();

        $this->assertPositionCanBeSelected(

            $position,

        );

        $rank = SchoolLeadershipRank::from(

            $this->rank,

        );

        $this->assertRankMatchesAppointment(

            position: $position,

            rank: $rank,

        );

        $this->assertCurrentAppointmentIsUnique(

            $position,

        );

        $imageMediaId = $this->resolveImageMediaId(

            $validated,

        );

        $actor = $this->actor();

        $creating = $this->editingId === null;

        $leader = DB::transaction(function () use (

            $actor,

            $position,

            $rank,

            $imageMediaId,

        ): SchoolLeader {

            $leader = $this->editingId === null

                ? new SchoolLeader

                : SchoolLeader::query()->findOrFail(

                    $this->editingId,

                );

            $leader->fill([

                'position_id' => $position->id,

                'role_key' => $position->key,

                'rank' => $rank->value,

                'start_date' => $this->cleanRequired(

                    $this->startDate,

                ),

                'end_date' => $this->isCurrent

                    ? null

                    : $this->cleanRequired(

                        $this->endDate,

                    ),

                'title_en' => $position->name_en,

                'title_si' => $position->name_si,

                'secondary_tenure_en' => trim($this->secondaryTenureEn) !== '' ? trim($this->secondaryTenureEn) : null,

                'secondary_tenure_si' => trim($this->secondaryTenureSi) !== '' ? trim($this->secondaryTenureSi) : null,

                'name_en' => $this->cleanRequired(

                    $this->nameEn,

                ),

                'name_si' => $this->cleanRequired(

                    $this->nameSi,

                ),

                'image_media_id' => $imageMediaId,

                'sort_order' => $position->sort_order,

                'is_active' => true,

                'updated_by' => $actor->id,

            ]);

            if (! $leader->exists) {

                $leader->created_by = $actor->id;

            }

            $leader->save();

            return $leader;

        });

        app(AuditLogger::class)->log(

            event: $creating

                ? 'settings.school-leader-created'

                : 'settings.school-leader-updated',

            description: $creating

                ? 'A School of Signals leadership appointment was created.'

                : 'A School of Signals leadership appointment was updated.',

            actor: $actor,

            subject: $leader,

        );

        session()->flash(

            'status',

            $creating

                ? 'Leadership appointment created successfully.'

                : 'Leadership appointment updated successfully.',

        );

        $this->closeEditor();

    }

    public function delete(int $id): void
    {
        Gate::authorize('school-leaders.view');
        Gate::authorize('school-leaders.update');

        $actor = $this->actor();

        DB::transaction(function () use ($id, $actor): void {
            $leader = SchoolLeader::query()->findOrFail($id);
            $oldValues = $leader->getAttributes();

            $leader->delete();

            app(AuditLogger::class)->log(
                event: 'settings.school-leader-deleted',
                description: 'A School of Signals leadership appointment was deleted.',
                actor: $actor,
                subject: $leader,
                oldValues: $oldValues,
            );
        });

        if ($this->editingId === $id) {
            $this->closeEditor();
        }

        $this->resetPage();

        session()->flash(
            'status',
            'Leadership appointment deleted successfully.',
        );
    }

    public function updatedNewImage(): void
    {

        Gate::authorize('school-leaders.view');

        $this->resetValidation('newImage');

    }

    public function clearNewImage(): void
    {

        Gate::authorize('school-leaders.view');

        $this->newImage = null;

        $this->resetValidation('newImage');

    }

    public function render(): View
    {
        Gate::authorize('school-leaders.view');

        $positions = SchoolLeadershipPosition::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $appointmentOptions = SchoolLeadershipPosition::query()
            ->where(function (Builder $query): void {
                $query->where('is_active', true);

                if ($this->roleKey !== '') {
                    $query->orWhere(
                        'key',
                        $this->roleKey,
                    );
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $leadersQuery = SchoolLeader::query()
            ->join(
                'school_leadership_positions as positions',
                'positions.id',
                '=',
                'school_leaders.position_id',
            )
            ->whereNotNull(
                'school_leaders.position_id',
            )
            ->with([
                'image',
                'position',
            ])
            ->select(
                'school_leaders.*',
            );

        $search = trim(
            $this->search,
        );

        if ($search !== '') {
            $like = '%'.$search.'%';

            $leadersQuery->where(
                function (Builder $query) use ($like): void {
                    $query
                        ->where(
                            'school_leaders.name_en',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'school_leaders.name_si',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'school_leaders.rank',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'school_leaders.title_en',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'school_leaders.title_si',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'positions.name_en',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'positions.name_si',
                            'like',
                            $like,
                        );
                },
            );
        }

        if (
            $this->positionFilter !== 'all'
            && ctype_digit($this->positionFilter)
        ) {
            $leadersQuery->where(
                'school_leaders.position_id',
                (int) $this->positionFilter,
            );
        }

        if ($this->statusFilter === 'present') {
            $leadersQuery->whereNull(
                'school_leaders.end_date',
            );
        } elseif ($this->statusFilter === 'past') {
            $leadersQuery->whereNotNull(
                'school_leaders.end_date',
            );
        }

        $sortDirection = $this->sortDirection === 'desc'
            ? 'desc'
            : 'asc';

        match ($this->sortField) {
            'name' => $leadersQuery->orderBy(
                'school_leaders.name_en',
                $sortDirection,
            ),
            'period' => $leadersQuery->orderBy(
                'school_leaders.start_date',
                $sortDirection,
            ),
            default => $leadersQuery->orderBy(
                'positions.sort_order',
                $sortDirection,
            ),
        };

        $leadersQuery
            ->orderByRaw(
                'CASE WHEN school_leaders.end_date IS NULL THEN 0 ELSE 1 END',
            )
            ->orderByDesc(
                'school_leaders.start_date',
            )
            ->orderByDesc(
                'school_leaders.id',
            );

        if (! in_array(
            $this->perPage,
            [
                10,
                25,
                50,
            ],
            true,
        )) {
            $this->perPage = 10;
        }

        /** @var view-string $viewName */
        $viewName = 'livewire.admin.school-leaders.school-leader-index';

        return view(
            $viewName,
            [
                'schoolLeaders' => $leadersQuery
                    ->paginate(
                        $this->perPage,
                    ),

                'positions' => $positions,

                'appointmentOptions' => $appointmentOptions,

                'mediaAssets' => MediaAsset::query()
                    ->where(
                        'type',
                        MediaType::Image->value,
                    )
                    ->where(
                        'visibility',
                        MediaVisibility::Public->value,
                    )
                    ->latest()
                    ->limit(200)
                    ->get(),

                'rankOptions' => SchoolLeadershipRank::cases(),
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'School Leadership',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveImageMediaId(

        array $validated,

    ): ?int {

        if (

            $this->newImage

            instanceof TemporaryUploadedFile

        ) {

            Gate::authorize('media.upload');

            $title = $this->cleanNullable(

                $this->newImageTitle,

            )

                ?? $this->cleanNullable(

                    $this->nameEn,

                )

                ?? 'School leadership portrait';

            $altText = $this->cleanNullable(

                $this->newImageAltText,

            )

                ?? $title;

            $media = app(

                MediaUploadService::class,

            )->upload(

                file: $this->newImage,

                type: MediaType::Image,

                visibility: MediaVisibility::Public,

                actor: $this->actor(),

                title: $title,

                altText: $altText,

                caption: null,

            );

            return $media->id;

        }

        $selectedMediaId = $validated[

            'imageMediaId'

        ] ?? null;

        $imageMediaId = is_numeric(

            $selectedMediaId,

        )

            ? (int) $selectedMediaId

            : null;

        $this->assertPublicImage(

            $imageMediaId,

        );

        return $imageMediaId;

    }

    private function assertPublicImage(

        ?int $mediaId,

    ): void {

        if ($mediaId === null) {

            return;

        }

        $exists = MediaAsset::query()

            ->whereKey($mediaId)

            ->where(

                'type',

                MediaType::Image->value,

            )

            ->where(

                'visibility',

                MediaVisibility::Public->value,

            )

            ->exists();

        if (! $exists) {

            throw ValidationException::withMessages([
                'imageMediaId' => 'Select a valid public image.',

            ]);

        }

    }

    private function assertPositionCanBeSelected(

        SchoolLeadershipPosition $position,

    ): void {

        if ($position->is_active) {

            return;

        }

        if ($this->editingId !== null) {

            $existingPositionId = SchoolLeader::query()

                ->whereKey($this->editingId)

                ->value('position_id');

            if (

                is_numeric($existingPositionId)

                && (int) $existingPositionId

                    === $position->id

            ) {

                return;

            }

        }

        throw ValidationException::withMessages([
            'roleKey' => 'Select an active appointment type.',

        ]);

    }

    private function assertRankMatchesAppointment(

        SchoolLeadershipPosition $position,

        SchoolLeadershipRank $rank,

    ): void {

        if (

            $rank->isCommissioned()

            === $position->requiresCommissionedRank()

        ) {

            return;

        }

        throw ValidationException::withMessages([
            'rank' => $position->requiresCommissionedRank()

                ? 'Select a commissioned officer rank for this appointment.'

                : 'Select an Other Ranks rank for this appointment.',

        ]);

    }

    private function assertCurrentAppointmentIsUnique(

        SchoolLeadershipPosition $position,

    ): void {

        if (! $this->isCurrent) {

            return;

        }

        $query = SchoolLeader::query()

            ->where(

                'position_id',

                $position->id,

            )

            ->whereNull('end_date');

        if ($this->editingId !== null) {

            $query->whereKeyNot(

                $this->editingId,

            );

        }

        if (! $query->exists()) {

            return;

        }

        throw ValidationException::withMessages([
            'isCurrent' => 'This appointment already has a present holder. Add an end date to the existing present record before assigning a new present holder.',

        ]);

    }

    private function resetForm(): void
    {

        $this->editingId = null;

        $this->roleKey = '';

        $this->rank = '';

        $this->imageMediaId = '';

        $this->newImage = null;

        $this->newImageTitle = '';

        $this->newImageAltText = '';

        $this->nameEn = '';

        $this->nameSi = '';

        $this->startDate = '';

        $this->endDate = '';

        $this->secondaryTenureEn = '';

        $this->secondaryTenureSi = '';

        $this->isCurrent = false;

        $this->resetValidation();

    }

    private function actor(): User
    {

        $user = Auth::user();

        abort_unless(

            $user instanceof User,

            403,

        );

        return $user;

    }

    private function cleanRequired(

        string $value,

    ): string {

        return trim($value);

    }

    private function cleanNullable(

        string $value,

    ): ?string {

        $value = trim($value);

        return $value === ''

            ? null

            : $value;

    }
}
