<?php

namespace App\Livewire\Admin\SpecialPrograms;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Enums\PageLocale;
use App\Livewire\Admin\Concerns\EditsVisibilitySchedule;
use App\Models\Page;
use App\Models\SpecialProgram;
use App\Models\User;
use App\Services\MediaUploadService;
use App\Services\SiteUrlService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class SpecialProgramIndex extends Component
{
    use EditsVisibilitySchedule;
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public bool $showEditor = false;

    public string $titleEn = '';

    public string $titleSi = '';

    public string $url = '';

    public string $linkedPageId = '';

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

    public function updatedLinkedPageId(): void
    {
        $this->authorizeAccess();
        $this->resetValidation('linkedPageId');
        if ($this->linkedPageId === '') {
            return;
        }
        $page = Page::query()->published()->find($this->linkedPageId);
        if ($page === null) {
            $this->addError('linkedPageId', 'Select a published page. Draft, scheduled and deleted pages cannot be linked yet.');

            return;
        }
        $this->url = $this->pagePath($page);
        $this->openInNewTab = false;
        $this->resetValidation('url');
    }

    public function updatedUrl(): void
    {
        $this->linkedPageId = '';
    }

    public function startCreate(): void
    {
        $this->reset(['startsAt', 'endsAt']);
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
        $this->url = app(SiteUrlService::class)->normalizeLink($program->url) ?? $program->url;
        $this->linkedPageId = (string) ($this->pageForPath($this->url)->id ?? '');
        $this->category = $program->category;
        $this->imageMediaId = $program->image_media_id;
        $this->currentImageUrl = $program->imageUrl();
        $this->newImage = null;
        $this->sortOrder = $program->sort_order;
        $this->isActive = $program->is_active;
        $this->loadSchedule($program);
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

        $this->scheduleAttributes();
        if ($this->linkedPageId !== '') {
            $page = Page::query()->published()->find($this->linkedPageId);
            if ($page === null) {
                throw ValidationException::withMessages(['linkedPageId' => 'This page is no longer published. Publish it or choose another page.']);
            }
            $this->url = $this->pagePath($page);
        }
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

        $path = app(SiteUrlService::class)->normalizeLink($validated['url']);
        if ($path === null) {
            throw ValidationException::withMessages(['url' => 'Enter a website path such as /pages/about or a complete https:// URL.']);
        }
        if (preg_match('~^/(?:en/|si/|ta/)?pages/~', $path) && $this->pageForPath($path) === null) {
            throw ValidationException::withMessages(['url' => 'This page is not publicly available. Create and publish it, then select it from the page list.']);
        }

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
            ...$this->scheduleAttributes(),
            'title_en' => trim($validated['titleEn']),
            'title_si' => trim($validated['titleSi']) !== '' ? trim($validated['titleSi']) : null,
            'url' => $path,
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
            $program->title_en.($program->is_active ? ' activated.' : ' deactivated.'),
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

        $programs = $query->orderBy(in_array($this->sortField, ['title_en', 'category', 'sort_order', 'is_active', 'created_at', 'updated_at'], true) ? $this->sortField : 'sort_order', $this->sortDirection === 'desc' ? 'desc' : 'asc')
            ->paginate($this->perPage);

        return view('livewire.admin.special-programs.special-program-index', [
            'programs' => $programs,
            'categories' => SpecialProgram::categories(),
            'linkablePages' => $this->showEditor ? Page::query()->published()->orderBy('locale')->orderBy('title')->get(['id', 'title', 'slug', 'locale']) : collect(),
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
        $this->linkedPageId = '';
        $this->category = SpecialProgram::CATEGORY_HERITAGE;
        $this->imageMediaId = null;
        $this->newImage = null;
        $this->currentImageUrl = null;
        $this->sortOrder = 0;
        $this->isActive = true;
        $this->openInNewTab = true;
        $this->resetValidation();
    }

    private function pagePath(Page $page): string
    {
        $locale = $page->getRawOriginal('locale');
        $url = $locale === PageLocale::English->value
            ? route('pages.show', ['slug' => $page->slug])
            : route('pages.show.localized', ['locale' => $locale, 'slug' => $page->slug]);

        return app(SiteUrlService::class)->portable($url);
    }

    private function pageForPath(string $url): ?Page
    {
        $path = app(SiteUrlService::class)->normalizeLink($url);
        if ($path === null || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }
        $path = parse_url($path, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('~^/(?:(en|si|ta)/)?pages/([a-z0-9]+(?:-[a-z0-9]+)*)/?$~', $path, $matches)) {
            return null;
        }

        return Page::query()->published()->where('locale', $matches[1] ?: 'en')->where('slug', $matches[2])->first();
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
