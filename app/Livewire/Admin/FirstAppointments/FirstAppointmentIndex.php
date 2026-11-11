<?php

namespace App\Livewire\Admin\FirstAppointments;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Models\FirstAppointmentHolder;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class FirstAppointmentIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public bool $showModal = false;

    public string $appointmentEn = '';

    public string $appointmentSi = '';

    public string $nameEn = '';

    public string $nameSi = '';

    public string $periodEn = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $periodSi = '';

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
        Gate::authorize('first-appointments.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        $this->reset(['startDate', 'endDate']);
        Gate::authorize('first-appointments.manage');

        $this->resetValidation();
        $this->editingId = null;
        $this->appointmentEn = '';
        $this->appointmentSi = '';
        $this->nameEn = '';
        $this->nameSi = '';
        $this->periodEn = '';
        $this->periodSi = '';
        $this->imagePath = null;
        $this->imageMediaId = null;
        $this->newImage = null;
        $this->currentImageUrl = null;
        $this->sortOrder = ((int) FirstAppointmentHolder::max('sort_order')) + 1;
        $this->isActive = true;

        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('first-appointments.manage');

        $this->resetValidation();
        $holder = FirstAppointmentHolder::with('imageMedia')->findOrFail($id);

        $this->editingId = $holder->id;
        $this->appointmentEn = $holder->appointment_en;
        $this->appointmentSi = $holder->appointment_si ?? '';
        $this->nameEn = $holder->name_en;
        $this->nameSi = $holder->name_si ?? '';
        $this->periodEn = $holder->period_en ?? '';
        $this->startDate = $holder->getAttribute('start_date')?->format('Y-m-d') ?? '';
        $this->endDate = $holder->getAttribute('end_date')?->format('Y-m-d') ?? '';
        $this->periodSi = $holder->period_si ?? '';
        $this->imagePath = $holder->image_path;
        $this->imageMediaId = $holder->image_media_id;
        $this->newImage = null;
        $this->currentImageUrl = $holder->imageUrl();
        $this->sortOrder = $holder->sort_order;
        $this->isActive = $holder->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize('first-appointments.manage');

        $this->validate([
            'appointmentEn' => ['required', 'string', 'max:255'],
            'appointmentSi' => ['nullable', 'string', 'max:255'],
            'nameEn' => ['required', 'string', 'max:255'],
            'nameSi' => ['nullable', 'string', 'max:255'],
            'startDate' => ['nullable', 'date_format:Y-m-d'],
            'endDate' => ['nullable', 'date_format:Y-m-d', $this->startDate !== '' ? 'after_or_equal:startDate' : 'date'],
            'periodEn' => ['nullable', 'string', 'max:100'],
            'periodSi' => ['nullable', 'string', 'max:100'],
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
                title: $this->nameEn ?: $this->appointmentEn,
                visibility: MediaVisibility::Public,
                type: MediaType::Image,
                actor: $actor,
            );
            $imageMediaId = $media->id;
            $imagePath = null;
        }

        $data = [
            'appointment_en' => $this->appointmentEn,
            'appointment_si' => $this->appointmentSi ?: null,
            'name_en' => $this->nameEn,
            'name_si' => $this->nameSi ?: null,
            'start_date' => $this->startDate ?: null, 'end_date' => $this->endDate ?: null,
            'period_en' => $this->periodEn ?: null,
            'period_si' => $this->periodSi ?: null,
            'image_media_id' => $imageMediaId,
            'image_path' => $imagePath,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
            'updated_by' => Auth::id(),
        ];

        if ($this->editingId) {
            $holder = FirstAppointmentHolder::findOrFail($this->editingId);
            $holder->update($data);
            session()->flash('status', "Appointment Holder [{$holder->appointment_en}] updated successfully.");
        } else {
            $data['created_by'] = Auth::id();
            $holder = FirstAppointmentHolder::create($data);
            session()->flash('status', "Appointment Holder [{$holder->appointment_en}] added successfully.");
        }

        $this->cancel();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('first-appointments.manage');

        $holder = FirstAppointmentHolder::findOrFail($id);
        $holder->update(['is_active' => ! $holder->is_active]);

        session()->flash(
            'status',
            "{$holder->appointment_en} ".($holder->is_active ? 'activated.' : 'deactivated.')
        );
    }

    /** @param list<int> $orderedIds */
    public function reorderHolders(array $orderedIds): void
    {
        Gate::authorize('first-appointments.manage');

        foreach ($orderedIds as $index => $id) {
            FirstAppointmentHolder::where('id', (int) $id)->update([
                'sort_order' => $index + 1,
            ]);
        }

        session()->flash('status', 'Order updated successfully.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('first-appointments.manage');

        $holder = FirstAppointmentHolder::findOrFail($id);
        $title = $holder->appointment_en;
        $holder->delete();

        session()->flash('status', "Appointment Holder [{$title}] deleted successfully.");
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
        Gate::authorize('first-appointments.view');

        $query = FirstAppointmentHolder::query()->with('imageMedia');

        if ($this->search !== '') {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('appointment_en', 'like', "%{$s}%")
                    ->orWhere('appointment_si', 'like', "%{$s}%")
                    ->orWhere('name_en', 'like', "%{$s}%")
                    ->orWhere('name_si', 'like', "%{$s}%")
                    ->orWhere('period_en', 'like', "%{$s}%");
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $holders = $query->ordered()->paginate(20);

        return view('livewire.admin.first-appointments.first-appointment-index', [
            'holders' => $holders,
        ])->layout('components.layouts.admin', ['title' => '1st Appointments Holders']);
    }
}
