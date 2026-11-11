<?php

namespace App\Livewire\Admin\HeroSlides;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Enums\PageLocale;
use App\Livewire\Admin\Concerns\EditsVisibilitySchedule;
use App\Models\HeroSlide;
use App\Models\HeroSlideTranslation;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MediaUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class HeroSlideIndex extends Component
{
    use EditsVisibilitySchedule;
    use WithFileUploads;

    #[Locked]
    public ?int $editingId = null;

    public bool $showEditor = false;

    public string $imageMediaId = '';

    /*
     * Livewire temporarily hydrates uploaded files internally,
     * therefore this property must remain mixed.
     */
    public mixed $newImage = null;

    public string $newImageTitle = '';

    public string $newImageAltText = '';

    public string $englishTitle = '';

    public string $englishSubtitle = '';

    public string $englishButtonLabel = '';

    public string $englishButtonUrl = '';

    public string $sinhalaTitle = '';

    public string $sinhalaSubtitle = '';

    public string $sinhalaButtonLabel = '';

    public string $sinhalaButtonUrl = '';

    public bool $isActive = true;

    public function mount(): void
    {
        Gate::authorize('hero-slides.view');
    }

    public function startCreate(): void
    {
        $this->reset(['startsAt', 'endsAt']);
        Gate::authorize('hero-slides.view');
        Gate::authorize('hero-slides.create');

        $this->resetForm();
        $this->showEditor = true;
    }

    public function save(): void
    {
        Gate::authorize('hero-slides.view');
        Gate::authorize(
            $this->editingId === null
                ? 'hero-slides.create'
                : 'hero-slides.update',
        );

        $this->scheduleAttributes();
        $validated = $this->validate([
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
            'englishTitle' => [
                'required',
                'string',
                'max:180',
            ],
            'englishSubtitle' => [
                'nullable',
                'string',
                'max:255',
            ],
            'englishButtonLabel' => [
                'nullable',
                'string',
                'max:100',
            ],
            'englishButtonUrl' => [
                'nullable',
                'string',
                'max:2048',
            ],
            'sinhalaTitle' => [
                'required',
                'string',
                'max:180',
            ],
            'sinhalaSubtitle' => [
                'nullable',
                'string',
                'max:255',
            ],
            'sinhalaButtonLabel' => [
                'nullable',
                'string',
                'max:100',
            ],
            'sinhalaButtonUrl' => [
                'nullable',
                'string',
                'max:2048',
            ],
            'isActive' => [
                'boolean',
            ],
        ]);
        $imageMediaId = $this->resolveImageMediaId(
            $validated,
        );

        $englishButtonUrl = $this->safeButtonUrl(
            $this->englishButtonUrl,
            'englishButtonUrl',
        );

        $sinhalaButtonUrl = $this->safeButtonUrl(
            $this->sinhalaButtonUrl,
            'sinhalaButtonUrl',
        );

        $this->assertButtonPair(
            $this->englishButtonLabel,
            $englishButtonUrl,
            'englishButtonUrl',
        );

        $this->assertButtonPair(
            $this->sinhalaButtonLabel,
            $sinhalaButtonUrl,
            'sinhalaButtonUrl',
        );

        $actor = $this->actor();

        $savedSlide = DB::transaction(function () use (
            $actor,
            $imageMediaId,
            $englishButtonUrl,
            $sinhalaButtonUrl,
        ): HeroSlide {
            $slide = $this->editingId === null
                ? new HeroSlide
                : HeroSlide::query()->findOrFail($this->editingId);

            if (! $slide->exists) {
                $slide->sort_order =
                    ((int) HeroSlide::query()->max('sort_order')) + 10;

                $slide->created_by = $actor->id;
            }

            /*
             * Keep the original English columns synchronized for backward
             * compatibility with existing public views.
             */
            $slide->fill([
                ...$this->scheduleAttributes(),
                'title' => $this->cleanRequired($this->englishTitle),
                'subtitle' => $this->cleanNullable($this->englishSubtitle),
                'image_media_id' => $imageMediaId,
                'button_label' => $this->cleanNullable(
                    $this->englishButtonLabel,
                ),
                'button_url' => $englishButtonUrl,
                'is_active' => $this->isActive,
                'updated_by' => $actor->id,
            ]);

            $slide->save();

            $slide->translations()->updateOrCreate(
                [
                    'locale' => PageLocale::English->value,
                ],
                [
                    'title' => $this->cleanRequired(
                        $this->englishTitle,
                    ),
                    'subtitle' => $this->cleanNullable(
                        $this->englishSubtitle,
                    ),
                    'button_label' => $this->cleanNullable(
                        $this->englishButtonLabel,
                    ),
                    'button_url' => $englishButtonUrl,
                ],
            );

            $slide->translations()->updateOrCreate(
                [
                    'locale' => PageLocale::Sinhala->value,
                ],
                [
                    'title' => $this->cleanRequired(
                        $this->sinhalaTitle,
                    ),
                    'subtitle' => $this->cleanNullable(
                        $this->sinhalaSubtitle,
                    ),
                    'button_label' => $this->cleanNullable(
                        $this->sinhalaButtonLabel,
                    ),
                    'button_url' => $sinhalaButtonUrl,
                ],
            );

            return $slide;
        });

        $wasCreated = $this->editingId === null;

        app(AuditLogger::class)->log(
            event: $wasCreated
                ? 'settings.hero-created'
                : 'settings.hero-updated',
            description: $wasCreated
                ? 'A multilingual hero slide was created.'
                : 'A multilingual hero slide was updated.',
            actor: $actor,
            subject: $savedSlide,
        );

        session()->flash(
            'status',
            $this->editingId === null
                ? 'Hero slide created successfully.'
                : 'Hero slide updated successfully.',
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        Gate::authorize('hero-slides.view');
        Gate::authorize('hero-slides.update');

        $slide = HeroSlide::query()
            ->with('translations')
            ->findOrFail($id);

        $english = $slide->translation(
            PageLocale::English,
            fallbackToEnglish: false,
        );

        $sinhala = $slide->translation(
            PageLocale::Sinhala,
            fallbackToEnglish: false,
        );

        $this->editingId = $slide->id;

        $this->imageMediaId = $slide->image_media_id === null
            ? ''
            : (string) $slide->image_media_id;

        if ($english instanceof HeroSlideTranslation) {
            $this->englishTitle = $english->title;
            $this->englishSubtitle = $english->subtitle ?? '';
            $this->englishButtonLabel = $english->button_label ?? '';
            $this->englishButtonUrl = $english->button_url ?? '';
        } else {
            $this->englishTitle = $slide->title;
            $this->englishSubtitle = $slide->subtitle ?? '';
            $this->englishButtonLabel = $slide->button_label ?? '';
            $this->englishButtonUrl = $slide->button_url ?? '';
        }

        if ($sinhala instanceof HeroSlideTranslation) {
            $this->sinhalaTitle = $sinhala->title;
            $this->sinhalaSubtitle = $sinhala->subtitle ?? '';
            $this->sinhalaButtonLabel = $sinhala->button_label ?? '';
            $this->sinhalaButtonUrl = $sinhala->button_url ?? '';
        } else {
            $this->sinhalaTitle = '';
            $this->sinhalaSubtitle = '';
            $this->sinhalaButtonLabel = '';
            $this->sinhalaButtonUrl = '';
        }

        $this->newImage = null;
        $this->newImageTitle = '';
        $this->newImageAltText = '';
        $this->isActive = $slide->is_active;
        $this->loadSchedule($slide);

        $this->resetValidation();
        $this->showEditor = true;
    }

    public function cancelEdit(): void
    {
        $this->closeEditor();
    }

    public function closeEditor(): void
    {
        Gate::authorize('hero-slides.view');

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('hero-slides.view');
        Gate::authorize('hero-slides.update');

        $slide = HeroSlide::query()->findOrFail($id);

        $slide->forceFill([
            'is_active' => ! $slide->is_active,
            'updated_by' => $this->actor()->id,
        ])->save();

        session()->flash(
            'status',
            $slide->is_active
                ? 'Hero slide activated.'
                : 'Hero slide deactivated.',
        );
    }

    public function moveUp(int $id): void
    {
        Gate::authorize('hero-slides.view');

        $this->move($id, 'up');
    }

    public function moveDown(int $id): void
    {
        Gate::authorize('hero-slides.view');

        $this->move($id, 'down');
    }

    public function delete(int $id): void
    {
        Gate::authorize('hero-slides.view');
        Gate::authorize('hero-slides.delete');

        HeroSlide::query()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash(
            'status',
            'Hero slide deleted successfully.',
        );
    }

    public function render(): View
    {
        Gate::authorize('hero-slides.view');

        return view(
            'livewire.admin.hero-slides.hero-slide-index',
            [
                'heroSlides' => HeroSlide::query()
                    ->with([
                        'image',
                        'translations',
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(),
                'mediaAssets' => MediaAsset::query()
                    ->where('type', 'image')
                    ->where('visibility', 'public')
                    ->latest()
                    ->limit(200)
                    ->get(),
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Hero Slider',
            ],
        );
    }

    private function move(int $id, string $direction): void
    {
        Gate::authorize('hero-slides.view');
        Gate::authorize('hero-slides.update');

        $slides = HeroSlide::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $currentIndex = $slides->search(
            static fn (HeroSlide $slide): bool => $slide->id === $id,
        );

        if (! is_int($currentIndex)) {
            return;
        }

        $targetIndex = $direction === 'up'
            ? $currentIndex - 1
            : $currentIndex + 1;

        $current = $slides->get($currentIndex);
        $target = $slides->get($targetIndex);

        if (
            ! $current instanceof HeroSlide
            || ! $target instanceof HeroSlide
        ) {
            return;
        }

        DB::transaction(function () use ($current, $target): void {
            $currentOrder = $current->sort_order;
            $targetOrder = $target->sort_order;

            $current->forceFill([
                'sort_order' => $targetOrder,
                'updated_by' => $this->actor()->id,
            ])->save();

            $target->forceFill([
                'sort_order' => $currentOrder,
                'updated_by' => $this->actor()->id,
            ])->save();
        });
    }

    public function updatedNewImage(): void
    {
        Gate::authorize('hero-slides.view');

        $this->resetValidation('newImage');
    }

    public function clearNewImage(): void
    {
        Gate::authorize('hero-slides.view');

        $this->newImage = null;
        $this->resetValidation('newImage');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveImageMediaId(array $validated): ?int
    {
        if ($this->newImage instanceof TemporaryUploadedFile) {
            Gate::authorize('media.upload');

            $title = $this->cleanNullable($this->newImageTitle)
                ?? $this->cleanRequired($this->englishTitle);

            $altText = $this->cleanNullable(
                $this->newImageAltText,
            ) ?? $title;

            $media = app(MediaUploadService::class)->upload(
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

        $selectedMediaId = $validated['imageMediaId'] ?? null;

        $imageMediaId = is_numeric($selectedMediaId)
            ? (int) $selectedMediaId
            : null;

        $this->assertPublicImage($imageMediaId);

        return $imageMediaId;
    }

    private function assertPublicImage(?int $mediaId): void
    {
        if ($mediaId === null) {
            return;
        }

        $exists = MediaAsset::query()
            ->whereKey($mediaId)
            ->where('type', 'image')
            ->where('visibility', 'public')
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'imageMediaId' => 'Select a valid public image.',
            ]);
        }
    }

    private function assertButtonPair(
        string $label,
        ?string $url,
        string $errorKey,
    ): void {
        if ($this->cleanNullable($label) !== null && $url === null) {
            throw ValidationException::withMessages([
                $errorKey => 'A button URL is required when a button label is provided.',
            ]);
        }
    }

    private function safeButtonUrl(
        string $value,
        string $errorKey,
    ): ?string {
        $url = trim($value);

        if ($url === '') {
            return null;
        }

        $isLocal = str_starts_with($url, '/')
            && ! str_starts_with($url, '//');

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $isExternal = is_string($scheme)
            && in_array(strtolower($scheme), ['http', 'https'], true)
            && filter_var($url, FILTER_VALIDATE_URL) !== false;

        if (! $isLocal && ! $isExternal) {
            throw ValidationException::withMessages([
                $errorKey => 'Use a local path beginning with / or a valid http/https URL.',
            ]);
        }

        return $url;
    }

    private function cleanRequired(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function cleanNullable(string $value): ?string
    {
        $clean = trim(strip_tags($value));

        return $clean === ''
            ? null
            : $clean;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'showEditor',
            'imageMediaId',
            'englishTitle',
            'englishSubtitle',
            'englishButtonLabel',
            'englishButtonUrl',
            'sinhalaTitle',
            'sinhalaSubtitle',
            'sinhalaButtonLabel',
            'sinhalaButtonUrl',
            'isActive',
            'newImage',
            'newImageTitle',
            'newImageAltText',
        ]);

        $this->isActive = true;
        $this->resetValidation();
    }

    private function actor(): User
    {
        $actor = Auth::user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
