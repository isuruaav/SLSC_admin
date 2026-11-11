<?php

namespace App\Livewire\Admin\Media;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaDuplicateFinder;
use App\Services\MediaUploadService;
use App\Services\MediaUrlService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class MediaUpload extends Component
{
    use WithFileUploads;

    /*
     * Keep this property mixed because Livewire temporarily
     * hydrates/dehydrates uploaded files internally.
     */
    public mixed $file = null;

    public string $type = 'image';

    /** @var array<int, mixed> */
    public array $batchFiles = [];

    /** @var array<int, array{name: string, status: string, message: string, id: int|null, url: string|null}> */
    #[Locked]
    public array $batchResults = [];

    #[Locked]
    public ?int $duplicateMediaId = null;

    public bool $reuseDuplicates = true;

    public string $visibility = 'public';

    public string $title = '';

    public string $altText = '';

    public string $caption = '';

    #[Locked]
    public ?int $uploadedMediaId = null;

    public function mount(): void
    {
        Gate::authorize(
            'media.upload',
        );
    }

    public function updatedType(): void
    {
        $this->resetValidation();

        if ($this->type !== MediaType::Image->value) {
            $this->altText = '';
        }
    }

    public function updatedFile(): void
    {
        $this->duplicateMediaId = $this->file instanceof TemporaryUploadedFile ? app(MediaDuplicateFinder::class)->find($this->file, $this->visibility, $this->actor())?->id : null;
        $this->resetValidation(
            'file',
        );
    }

    public function clearFile(): void
    {
        $this->file = null;

        $this->resetValidation(
            'file',
        );
    }

    public function save(): void
    {
        Gate::authorize(
            'media.upload',
        );

        $this->normaliseInput();

        $this->validate();

        if (! $this->file instanceof TemporaryUploadedFile) {
            throw ValidationException::withMessages([
                'file' => 'Please select a valid file to upload.',
            ]);
        }

        $mediaType = MediaType::tryFrom(
            $this->type,
        );

        if (
            ! $mediaType instanceof MediaType
            || ! in_array(
                $mediaType,
                [
                    MediaType::Image,
                    MediaType::Document,
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'type' => 'Please select a supported media type.',
            ]);
        }

        $visibility = MediaVisibility::tryFrom(
            $this->visibility,
        );

        if (! $visibility instanceof MediaVisibility) {
            throw ValidationException::withMessages([
                'visibility' => 'Please select a valid visibility.',
            ]);
        }

        $media = app(
            MediaUploadService::class,
        )->upload(
            file: $this->file,
            type: $mediaType,
            visibility: $visibility,
            actor: $this->actor(),

            title: $this->title !== ''
                ? $this->title
                : null,

            altText: $mediaType === MediaType::Image
                && $this->altText !== ''
                ? $this->altText
                : null,

            caption: $this->caption !== ''
                ? $this->caption
                : null,
        );

        $uploadedTitle =
            (string) $media->title;

        $this->uploadedMediaId = (int) $media->getKey();

        $this->reset([
            'file',
            'title',
            'altText',
            'caption',
        ]);

        $this->type =
            MediaType::Image->value;

        $this->visibility =
            MediaVisibility::Public->value;

        session()->flash(
            'status',
            sprintf(
                '%s was uploaded successfully.',
                $uploadedTitle,
            ),
        );
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',

                /*
                 * Livewire temporary maximum.
                 *
                 * The secure service applies the
                 * smaller per-media-type limits.
                 */
                'max:20480',
            ],

            'type' => [
                'required',

                Rule::in([
                    MediaType::Image->value,
                    MediaType::Document->value,
                ]),
            ],

            'visibility' => [
                'required',

                Rule::in([
                    MediaVisibility::Public->value,
                    MediaVisibility::Internal->value,
                    MediaVisibility::Restricted->value,
                ]),
            ],

            'title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'altText' => [
                'nullable',
                'string',
                'max:255',
            ],

            'caption' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'file.required' => 'Please choose an image or PDF file.',

            'file.file' => 'The selected upload must be a valid file.',

            'file.max' => 'The selected file is too large.',

            'type.in' => 'Only Image and Document uploads are currently supported.',

            'visibility.in' => 'Please select a valid media visibility.',
        ];
    }

    public function render(): View
    {
        $uploadedMedia = $this->uploadedMediaId !== null
            ? MediaAsset::query()->find($this->uploadedMediaId)
            : null;

        return view(
            'livewire.admin.media.media-upload',
            [
                'mediaTypes' => [
                    MediaType::Image,
                    MediaType::Document,
                ],

                'visibilities' => MediaVisibility::cases(),

                'previewUrl' => $this->imagePreviewUrl(),

                'selectedFileName' => $this->selectedFileName(),

                'selectedFileSize' => $this->selectedFileSize(),

                'uploadedMedia' => $uploadedMedia,

                'uploadedMediaUrl' => $uploadedMedia instanceof MediaAsset
                    ? app(MediaUrlService::class)->namedOriginal($uploadedMedia)
                    : null,
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Upload Media',
            ],
        );
    }

    private function normaliseInput(): void
    {
        $this->type = strtolower(
            trim($this->type),
        );

        $this->visibility = strtolower(
            trim($this->visibility),
        );

        $this->title = trim(
            $this->title,
        );

        $this->altText = trim(
            $this->altText,
        );

        $this->caption = trim(
            $this->caption,
        );
    }

    private function imagePreviewUrl(): ?string
    {
        if (! $this->file instanceof TemporaryUploadedFile) {
            return null;
        }

        $mimeType = strtolower(
            $this->file->getMimeType(),
        );

        if (
            ! str_starts_with(
                $mimeType,
                'image/',
            )
        ) {
            return null;
        }

        try {
            return $this->file->temporaryUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    private function selectedFileName(): ?string
    {
        if (! $this->file instanceof TemporaryUploadedFile) {
            return null;
        }

        $name = trim(
            $this->file->getClientOriginalName(),
        );

        return $name !== ''
            ? $name
            : null;
    }

    private function selectedFileSize(): ?string
    {
        if (! $this->file instanceof TemporaryUploadedFile) {
            return null;
        }

        $bytes = $this->file->getSize();

        if ($bytes < 0) {
            return null;
        }

        if ($bytes >= 1024 * 1024) {
            return number_format(
                $bytes / (1024 * 1024),
                2,
            ).' MB';
        }

        return number_format(
            $bytes / 1024,
            1,
        ).' KB';
    }

    private function actor(): User
    {
        $actor = Auth::user();

        abort_unless(
            $actor instanceof User,
            403,
        );

        return $actor;
    }

    public function uploadBatch(): void
    {
        Gate::authorize('media.upload');
        $this->validate(['batchFiles' => ['required', 'array', 'min:1', 'max:20'], 'visibility' => ['required', 'in:public,internal,restricted']]);
        foreach (array_keys($this->batchFiles) as $index) {
            $this->retryBatch((int) $index);
        }
    }

    public function updatedBatchFiles(): void
    {
        $this->batchResults = [];
    }

    public function retryBatch(int $index): void
    {
        Gate::authorize('media.upload');
        $this->validate(['visibility' => ['required', 'in:public,internal,restricted']]);
        $file = $this->batchFiles[$index] ?? null;
        abort_unless($file instanceof TemporaryUploadedFile, 404);
        $name = $file->getClientOriginalName();
        try {
            $actor = $this->actor();
            $duplicate = $this->reuseDuplicates ? app(MediaDuplicateFinder::class)->find($file, $this->visibility, $actor) : null;
            $media = $duplicate ?? app(MediaUploadService::class)->upload($file, str_starts_with((string) $file->getMimeType(), 'image/') ? MediaType::Image : MediaType::Document, MediaVisibility::from($this->visibility), $actor);
            $this->batchResults[$index] = ['name' => $name, 'status' => $duplicate ? 'reused' : 'uploaded', 'message' => $duplicate ? 'Matching library file reused.' : 'Uploaded successfully.', 'id' => $media->id, 'url' => app(MediaUrlService::class)->namedOriginal($media)];
            unset($this->batchFiles[$index]);
        } catch (\Throwable $exception) {
            if (! $exception instanceof ValidationException) {
                report($exception);
            }
            $message = $exception instanceof ValidationException ? collect($exception->errors())->flatten()->implode(' ') : 'Upload failed. Retry this file.';
            $this->batchResults[$index] = ['name' => $name, 'status' => 'error', 'message' => $message, 'id' => null, 'url' => null];
        }
    }

    public function useExistingFile(): void
    {
        Gate::authorize('media.upload');
        abort_unless($this->file instanceof TemporaryUploadedFile, 404);
        $media = app(MediaDuplicateFinder::class)->find($this->file, $this->visibility, $this->actor());
        abort_unless($media !== null && $media->id === $this->duplicateMediaId, 404);
        $this->uploadedMediaId = $media->id;
        $this->file = null;
        $this->duplicateMediaId = null;
        session()->flash('status', 'Existing library file selected. No duplicate was uploaded.');
    }
}
