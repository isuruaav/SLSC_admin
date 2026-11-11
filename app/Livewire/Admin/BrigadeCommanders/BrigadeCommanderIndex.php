<?php

namespace App\Livewire\Admin\BrigadeCommanders;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Models\BrigadeCommander;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class BrigadeCommanderIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public bool $showModal = false;

    public string $orderNumber = '';

    public string $nameEn = '';

    public string $nameSi = '';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $periodEn = '';

    public string $periodSi = '';

    public string $secondaryTenureEn = '';

    public string $secondaryTenureSi = '';

    public ?string $imagePath = null;

    public ?int $imageMediaId = null;

    public mixed $newImage = null;

    public ?string $currentImageUrl = null;

    public int $sortOrder = 0;

    public bool $isActive = true;

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        Gate::authorize('brigade-commanders.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->autoGeneratePeriod();
    }

    public function updatedEndDate(): void
    {
        $this->autoGeneratePeriod();
    }

    private function autoGeneratePeriod(): void
    {
        if ($this->startDate && $this->endDate) {
            $from = Carbon::parse($this->startDate)->format('Y - m - d');
            $to = Carbon::parse($this->endDate)->format('Y - m - d');
            if (empty($this->periodEn)) {
                $this->periodEn = "{$from} to {$to}";
            }
            if (empty($this->periodSi)) {
                $this->periodSi = "{$from} සිට {$to} දක්වා";
            }
        } elseif ($this->startDate && empty($this->periodEn)) {
            $from = Carbon::parse($this->startDate)->format('Y - m - d');
            $this->periodEn = "From {$from}";
            if (empty($this->periodSi)) {
                $this->periodSi = "{$from} සිට";
            }
        }
    }

    public function startCreate(): void
    {
        Gate::authorize('brigade-commanders.manage');

        $this->resetValidation();
        $this->editingId = null;
        $maxOrder = (int) BrigadeCommander::max('sort_order');
        $this->sortOrder = $maxOrder + 1;
        $this->orderNumber = sprintf('%02d', $this->sortOrder);
        $this->nameEn = '';
        $this->nameSi = '';
        $this->startDate = null;
        $this->endDate = null;
        $this->periodEn = '';
        $this->periodSi = '';
        $this->secondaryTenureEn = '';
        $this->secondaryTenureSi = '';
        $this->imagePath = null;
        $this->imageMediaId = null;
        $this->newImage = null;
        $this->currentImageUrl = null;
        $this->isActive = true;

        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('brigade-commanders.manage');

        $this->resetValidation();
        $com = BrigadeCommander::with('imageMedia')->findOrFail($id);

        $this->editingId = $com->id;
        $this->orderNumber = $com->order_number ?? sprintf('%02d', $com->sort_order);
        $this->nameEn = $com->name_en;
        $this->nameSi = $com->name_si ?? '';
        $this->startDate = $com->start_date?->format('Y-m-d');
        $this->endDate = $com->end_date?->format('Y-m-d');
        $this->periodEn = $com->period_en ?? '';
        $this->periodSi = $com->period_si ?? '';
        $this->secondaryTenureEn = $com->secondary_tenure_en ?? '';
        $this->secondaryTenureSi = $com->secondary_tenure_si ?? '';
        $this->imagePath = $com->image_path;
        $this->imageMediaId = $com->image_media_id;
        $this->newImage = null;
        $this->currentImageUrl = $com->imageUrl();
        $this->sortOrder = $com->sort_order;
        $this->isActive = $com->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize('brigade-commanders.manage');

        $this->validate([
            'orderNumber' => ['nullable', 'string', 'max:10'],
            'nameEn' => ['required', 'string', 'max:255'],
            'nameSi' => ['nullable', 'string', 'max:255'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date'],
            'periodEn' => ['nullable', 'string', 'max:255'],
            'periodSi' => ['nullable', 'string', 'max:255'],
            'secondaryTenureEn' => ['nullable', 'string'],
            'secondaryTenureSi' => ['nullable', 'string'],
            'newImage' => ['nullable', 'image', 'max:10240'],
            'sortOrder' => ['required', 'integer'],
            'isActive' => ['boolean'],
        ]);

        $imageMediaId = $this->imageMediaId;
        $imagePath = $this->imagePath;

        if ($this->newImage) {
            $actor = Auth::user();
            abort_unless($actor instanceof User, 403);
            $media = app(MediaUploadService::class)->upload(
                file: $this->newImage,
                title: $this->nameEn,
                visibility: MediaVisibility::Public,
                type: MediaType::Image,
                actor: $actor,
            );
            $imageMediaId = $media->id;
            $imagePath = null;
        }

        $data = [
            'order_number' => $this->orderNumber ?: sprintf('%02d', $this->sortOrder),
            'name_en' => $this->nameEn,
            'name_si' => $this->nameSi ?: null,
            'start_date' => $this->startDate ?: null,
            'end_date' => $this->endDate ?: null,
            'period_en' => $this->periodEn ?: null,
            'period_si' => $this->periodSi ?: null,
            'secondary_tenure_en' => $this->secondaryTenureEn ?: null,
            'secondary_tenure_si' => $this->secondaryTenureSi ?: null,
            'image_media_id' => $imageMediaId,
            'image_path' => $imagePath,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
            'updated_by' => Auth::id(),
        ];

        if ($this->editingId) {
            $com = BrigadeCommander::findOrFail($this->editingId);
            $com->update($data);
            session()->flash('status', "Brigade Commander [{$com->name_en}] updated successfully.");
        } else {
            $data['created_by'] = Auth::id();
            $com = BrigadeCommander::create($data);
            session()->flash('status', "Brigade Commander [{$com->name_en}] added successfully.");
        }

        $this->cancel();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('brigade-commanders.manage');

        $com = BrigadeCommander::findOrFail($id);
        $com->update(['is_active' => ! $com->is_active]);

        session()->flash(
            'status',
            "{$com->name_en} ".($com->is_active ? 'activated.' : 'deactivated.')
        );
    }

    /** @param list<int> $orderedIds */
    public function reorderCommanders(array $orderedIds): void
    {
        Gate::authorize('brigade-commanders.manage');

        foreach ($orderedIds as $index => $id) {
            $newSort = $index + 1;
            BrigadeCommander::where('id', (int) $id)->update([
                'sort_order' => $newSort,
                'order_number' => sprintf('%02d', $newSort),
            ]);
        }

        session()->flash('status', 'Commander order updated successfully.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('brigade-commanders.manage');

        $com = BrigadeCommander::findOrFail($id);
        $name = $com->name_en;
        $com->delete();

        session()->flash('status', "Brigade Commander [{$name}] deleted successfully.");
    }

    public function cancel(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->newImage = null;
        $this->currentImageUrl = null;
    }

    public function render(): View
    {
        Gate::authorize('brigade-commanders.view');

        $query = BrigadeCommander::query()->with('imageMedia');

        if ($this->search !== '') {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_en', 'like', "%{$s}%")
                    ->orWhere('name_si', 'like', "%{$s}%")
                    ->orWhere('order_number', 'like', "%{$s}%")
                    ->orWhere('period_en', 'like', "%{$s}%");
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $commanders = $query->ordered()->paginate(24);

        return view('livewire.admin.brigade-commanders.brigade-commander-index', [
            'commanders' => $commanders,
        ])->layout('components.layouts.admin', ['title' => 'Brigade Commanders']);
    }
}
