<?php

namespace App\Livewire\Admin\SpecialPrograms;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Models\SpecialProgram;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class SpecialProgramIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public bool $showEditor = false;

    public string $titleEn = '';

    public string $titleSi = '';

    public string $url = '';

    public string $category = SpecialProgram::CATEGORY_HERITAGE;

    public ?int $imageMediaId = null;

    public mixed $newImage = null;

    public ?string $currentImageUrl = null;

    public int $sortOrder = 0;

    public bool $isActive = true;

    public bool $openInNewTab = true;

    public string $search = '';

    public string $categoryFilter = 'all';

    public string $statusFilter = 'all';

    public int $perPage = 15;

    public string $sortField = 'sort_order';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        $this->authorizeAccess();
        $this->resetForm();

        // Auto-assign next sort order
        $maxOrder = SpecialProgram::query()->max('sort_order') ?? 0;
        $this->sortOrder = $maxOrder + 1;

        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeAccess();

        $program = SpecialProgram::query()->findOrFail($id);

        $this->editingId = $program->id;
        $this->titleEn = $program->title_en;
        $this->titleSi = $program->title_si ?? '';
        $this->url = $program->url;
        $this->category = $program->category;
        $this->imageMediaId = $program->image_media_id;
        $this->currentImageUrl = $program->imageUrl();
        $this->newImage = null;
        $this->sortOrder = $program->sort_order;
        $this->isActive = $program->is_active;
        $this->openInNewTab = $program->open_in_new_tab;

        $this->resetValidation();
        $this->showEditor = true;
    }

    public function removeImage(): void
    {
        $this->imageMediaId = null;
        $this->currentImageUrl = null;
        $this->newImage = null;
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $validated = $this->validate([
            'titleEn' => ['required', 'string', 'max:255'],
            'titleSi' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'in:heritage,community,resources'],
            'newImage' => ['nullable', 'image', 'max:10240'],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
            'openInNewTab' => ['boolean'],
        ]);

        $imageMediaId = $this->imageMediaId;

        if ($this->newImage instanceof TemporaryUploadedFile) {
            /** @var User $actor */
            $actor = Auth::user();

            $media = app(MediaUploadService::class)->upload(
                file: $this->newImage,
                type: MediaType::Image,
                visibility: MediaVisibility::Public,
                actor: $actor,
                title: trim($validated['titleEn']),
                altText: trim($validated['titleEn']),
            );

            $imageMediaId = $media->id;
        }

        $attributes = [
            'title_en' => trim($validated['titleEn']),
            'title_si' => trim($validated['titleSi']) !== '' ? trim($validated['titleSi']) : null,
            'url' => trim($validated['url']),
            'category' => $validated['category'],
            'image_media_id' => $imageMediaId,
            'sort_order' => (int) $validated['sortOrder'],
            'is_active' => (bool) $validated['isActive'],
            'open_in_new_tab' => (bool) $validated['openInNewTab'],
        ];

        if ($this->editingId === null) {
            SpecialProgram::query()->create($attributes);
            session()->flash('status', 'Special Program created successfully.');
        } else {
            $program = SpecialProgram::query()->findOrFail($this->editingId);
            $program->update($attributes);
            session()->flash('status', 'Special Program updated successfully.');
        }

        $this->closeEditor();
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAccess();

        $program = SpecialProgram::query()->findOrFail($id);
        $program->update(['is_active' => ! $program->is_active]);

        session()->flash(
            'status',
            $program->title_en . ($program->is_active ? ' activated.' : ' deactivated.'),
        );
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();

        $program = SpecialProgram::query()->findOrFail($id);
        $title = $program->title_en;
        $program->delete();

        session()->flash('status', "Special Program '{$title}' removed successfully.");
    }

    public function closeEditor(): void
    {
        $this->resetForm();
        $this->showEditor = false;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render(): View
    {
        $this->authorizeAccess();

        $query = SpecialProgram::query()->with('image.variants');

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('title_en', 'like', "%{$search}%")
                  ->orWhere('title_si', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $programs = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.special-programs.special-program-index', [
            'programs' => $programs,
            'categories' => SpecialProgram::categories(),
        ])->layout('components.layouts.admin', [
            'title' => 'Special Programs',
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->titleEn = '';
        $this->titleSi = '';
        $this->url = '';
        $this->category = SpecialProgram::CATEGORY_HERITAGE;
        $this->imageMediaId = null;
        $this->newImage = null;
        $this->currentImageUrl = null;
        $this->sortOrder = 0;
        $this->isActive = true;
        $this->openInNewTab = true;
        $this->resetValidation();
    }

    private function authorizeAccess(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('Super Administrator') || $user->can('admin.access') || $user->can('settings.manage')) {
            return;
        }

        abort(403, 'Unauthorized access to Special Programs.');
    }
}

