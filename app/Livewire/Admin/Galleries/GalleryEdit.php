<?php

namespace App\Livewire\Admin\Galleries;

use App\Enums\MediaType;
use App\Enums\MediaVariantPreset;
use App\Enums\MediaVisibility;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\MediaVariant;
use App\Models\User;
use App\Services\GalleryService;
use App\Services\MediaDuplicateFinder;
use App\Services\MediaUploadService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class GalleryEdit extends Component
{
    use WithFileUploads;

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    /** @var array<int, array{name: string, success: bool, message: string}> */
    #[Locked]
    public array $imageUploadResults = [];

    public function updatedUploads(): void
    {
        $this->imageUploadResults = [];
    }

    #[Locked]
    public int $galleryId;

    public string $title = '';

    public string $titleSi = '';

    public string $slug = '';

    public string $eventDate = '';

    public string $description = '';

    public string $coverMediaId = '';

    public string $publishedAt = '';

    public string $seoTitle = '';

    public string $seoDescription = '';

    /*
    |--------------------------------------------------------------------------
    | New Gallery Image
    |--------------------------------------------------------------------------
    */

    public string $selectedMediaId = '';

    public string $newCaption = '';

    public string $newAltText = '';

    /*
    |--------------------------------------------------------------------------
    | Existing Gallery Image Metadata
    |--------------------------------------------------------------------------
    */

    /**
     * @var array<int, string>
     */
    public array $imageCaptions = [];

    /**
     * @var array<int, string>
     */
    public array $imageAltTexts = [];

    public function mount(
        Gallery $gallery,
    ): void {
        Gate::authorize(
            'galleries.view',
        );

        Gate::authorize(
            'galleries.update',
        );

        abort_if(
            $gallery->trashed(),
            404,
        );

        $this->loadGallery(
            $gallery,
        );

        $this->loadImageMetadata();
    }

    /*
    |--------------------------------------------------------------------------
    | Save Gallery
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        Gate::authorize(
            'galleries.update',
        );

        $this->normaliseInput();

        $this->validate();

        $gallery =
            app(
                GalleryService::class,
            )->update(
                gallery: $this->gallery(),

                actor: $this->actor(),

                title: $this->title,
                titleSi: $this->titleSi,

                eventDate: $this->eventDateValue(),

                description: $this->nullableString(
                    $this->description,
                ),

                slug: $this->nullableString(
                    $this->slug,
                ),

                coverMedia: $this->coverMedia(),

                publishedAt: $this->publicationDateValue(),

                seoTitle: $this->nullableString(
                    $this->seoTitle,
                ),

                seoDescription: $this->nullableString(
                    $this->seoDescription,
                ),
            );

        $this->loadGallery(
            $gallery,
        );

        session()->flash(
            'status',
            'Gallery updated successfully.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Add Image
    |--------------------------------------------------------------------------
    */

    public function addImage(): void
    {
        Gate::authorize(
            'galleries.update',
        );

        $this->selectedMediaId =
            trim(
                $this->selectedMediaId,
            );

        $this->newCaption =
            trim(
                $this->newCaption,
            );

        $this->newAltText =
            trim(
                $this->newAltText,
            );

        $this->validate([
            'selectedMediaId' => [
                'required',
                'integer',
                'exists:media_assets,id',
            ],

            'newCaption' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'newAltText' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $media =
            MediaAsset::withTrashed()
                ->findOrFail(
                    (int) $this->selectedMediaId,
                );

        app(
            GalleryService::class,
        )->addImage(
            gallery: $this->gallery(),

            media: $media,

            actor: $this->actor(),

            caption: $this->nullableString(
                $this->newCaption,
            ),

            altText: $this->nullableString(
                $this->newAltText,
            ),
        );

        $this->selectedMediaId = '';

        $this->newCaption = '';

        $this->newAltText = '';

        $this->refreshImageMetadata();

        session()->flash(
            'status',
            'Image added to gallery.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Image Metadata
    |--------------------------------------------------------------------------
    */

    public function updateImage(
        int $galleryImageId,
    ): void {
        Gate::authorize(
            'galleries.update',
        );

        $galleryImage =
            $this->galleryImage(
                $galleryImageId,
            );

        $this->validate([
            'imageCaptions.'.$galleryImageId => [
                'nullable',
                'string',
                'max:1000',
            ],

            'imageAltTexts.'.$galleryImageId => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $caption =
            $this->imageCaptions[$galleryImageId] ?? '';

        $altText =
            $this->imageAltTexts[$galleryImageId] ?? '';

        app(
            GalleryService::class,
        )->updateImage(
            galleryImage: $galleryImage,

            actor: $this->actor(),

            caption: $this->nullableString(
                $caption,
            ),

            altText: $this->nullableString(
                $altText,
            ),
        );

        $this->imageCaptions[$galleryImageId] = $caption;
        $this->imageAltTexts[$galleryImageId] = $altText;

        session()->flash(
            'status',
            'Gallery image details updated.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Image
    |--------------------------------------------------------------------------
    */

    public function removeImage(
        int $galleryImageId,
    ): void {
        Gate::authorize(
            'galleries.update',
        );

        app(
            GalleryService::class,
        )->removeImage(
            galleryImage: $this->galleryImage(
                $galleryImageId,
            ),

            actor: $this->actor(),
        );

        $this->coverMediaId = (string) ($this->gallery()->cover_media_id ?? '');
        $this->refreshImageMetadata();

        session()->flash(
            'status',
            'Image removed from gallery.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Image Ordering
    |--------------------------------------------------------------------------
    */

    public function moveImageUp(
        int $galleryImageId,
    ): void {
        $this->moveImage(
            galleryImageId: $galleryImageId,

            offset: -1,
        );
    }

    public function moveImageDown(
        int $galleryImageId,
    ): void {
        $this->moveImage(
            galleryImageId: $galleryImageId,

            offset: 1,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Publish
    |--------------------------------------------------------------------------
    */

    public function publish(): void
    {
        Gate::authorize('galleries.publish');
        if ($this->uploads !== []) {
            throw ValidationException::withMessages([
                'uploads' => 'Upload the selected images before publishing.',
            ]);
        }
        $this->save();
        $this->saveAllImageDetails();
        Gate::authorize(
            'galleries.publish',
        );

        $gallery =
            app(
                GalleryService::class,
            )->publish(
                gallery: $this->gallery(),

                actor: $this->actor(),
            );

        $this->loadGallery(
            $gallery,
        );

        session()->flash(
            'status',
            'Gallery published successfully.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Archive
    |--------------------------------------------------------------------------
    */

    public function unpublish(): void
    {
        Gate::authorize(
            'galleries.publish',
        );

        $gallery =
            app(
                GalleryService::class,
            )->unpublish(
                gallery: $this->gallery(),

                actor: $this->actor(),
            );

        $this->loadGallery(
            $gallery,
        );

        session()->flash(
            'status',
            'Gallery unpublished. You can now edit it.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function uploadImages(): void
    {
        Gate::authorize('galleries.update');
        Gate::authorize('media.upload');
        abort_unless($this->gallery()->isEditable(), 409, 'Unpublish the gallery before editing.');
        $this->validate(['uploads' => ['required', 'array', 'min:1', 'max:20']]);
        foreach (array_keys($this->uploads) as $index) {
            $this->storeGalleryUpload((int) $index);
        }
        $this->refreshImageMetadata();
        session()->flash('status', $this->uploads === [] ? 'Images added. Publish when ready.' : 'Some files need attention. Successful images were kept; retry only the failed files.');
    }

    public function retryImageUpload(int $index): void
    {
        Gate::authorize('galleries.update');
        Gate::authorize('media.upload');
        abort_unless($this->gallery()->isEditable(), 409, 'Unpublish the gallery before editing.');
        $this->storeGalleryUpload($index);
        $this->refreshImageMetadata();
    }

    private function storeGalleryUpload(int $index): void
    {
        $file = $this->uploads[$index] ?? null;
        abort_unless($file instanceof TemporaryUploadedFile, 404);
        $name = $file->getClientOriginalName();
        try {
            Validator::make(['image' => $file], ['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']])->validate();
            $gallery = $this->gallery();
            $media = app(MediaDuplicateFinder::class)->find($file, 'public', $this->actor())
                ?? app(MediaUploadService::class)->upload(file: $file, type: MediaType::Image, visibility: MediaVisibility::Public, actor: $this->actor(), title: mb_substr($gallery->title, 0, 240).' - '.($index + 1), altText: $gallery->title);
            if (! $gallery->images()->where('media_asset_id', $media->id)->exists()) {
                app(GalleryService::class)->addImage(gallery: $gallery, media: $media, actor: $this->actor());
            }
            $this->imageUploadResults[$index] = ['name' => $name, 'success' => true, 'message' => 'Added to the gallery.'];
            unset($this->uploads[$index]);
        } catch (\Throwable $exception) {
            if (! $exception instanceof ValidationException) {
                report($exception);
            }
            $this->imageUploadResults[$index] = ['name' => $name, 'success' => false, 'message' => $exception instanceof ValidationException ? collect($exception->errors())->flatten()->implode(' ') : 'Could not add this file. Retry it after checking gallery status.'];
        }
    }

    private function refreshImageMetadata(): void
    {
        $captions = $this->imageCaptions;
        $alts = $this->imageAltTexts;
        $this->loadImageMetadata();
        $this->imageCaptions = array_replace($this->imageCaptions, array_intersect_key($captions, $this->imageCaptions));
        $this->imageAltTexts = array_replace($this->imageAltTexts, array_intersect_key($alts, $this->imageAltTexts));
        $this->coverMediaId = (string) ($this->gallery()->cover_media_id ?? '');
    }

    public function selectCover(int $galleryImageId): void
    {
        Gate::authorize('galleries.update');
        abort_unless($this->gallery()->isEditable(), 409, 'Unpublish the gallery before editing.');
        $image = $this->galleryImage($galleryImageId);
        $this->coverMediaId = (string) $image->media_asset_id;
        $this->save();
    }

    public function delete(): void
    {
        Gate::authorize('galleries.delete');
        app(GalleryService::class)->delete(gallery: $this->gallery(), actor: $this->actor());
        session()->flash('status', 'Gallery deleted successfully.');
        $this->redirectRoute('admin.galleries.index', navigate: true);
    }

    public function render(): View
    {
        Gate::authorize(
            'galleries.view',
        );

        Gate::authorize(
            'galleries.update',
        );

        $gallery =
            $this->gallery()
                ->load([
                    'coverMedia',
                    'images.media',
                ]);

        $editable =
            $gallery->isEditable();

        $coverMediaAssets =
            MediaAsset::query()
                ->where(
                    'type',
                    MediaType::Image->value,
                )
                ->where(
                    'visibility',
                    MediaVisibility::Public->value,
                )
                ->whereIn(
                    'id',
                    GalleryImage::query()
                        ->select(
                            'media_asset_id',
                        )
                        ->where(
                            'gallery_id',
                            $this->galleryId,
                        ),
                )
                ->orderByDesc(
                    'id',
                )
                ->get();

        /*
         * Images already attached to this gallery do not need
         * to appear in the Add Image selector.
         */
        $mediaAssets =
            MediaAsset::query()
                ->where(
                    'type',
                    MediaType::Image->value,
                )
                ->where(
                    'visibility',
                    MediaVisibility::Public->value,
                )
                ->whereNotIn(
                    'id',
                    GalleryImage::query()
                        ->select(
                            'media_asset_id',
                        )
                        ->where(
                            'gallery_id',
                            $this->galleryId,
                        ),
                )
                ->orderByDesc(
                    'id',
                )
                ->limit(
                    150,
                )
                ->get();

        /**
         * @var array<int, string|null> $previewUrls
         */
        $previewUrls = [];

        foreach (
            $gallery->images as $galleryImage
        ) {
            $media =
                $galleryImage->media;

            $previewUrls[(int) $galleryImage->getKey()] =
                $media instanceof MediaAsset
                ? $this->mediaPreviewUrl(
                    $media,
                )
                : null;
        }

        return view(
            'livewire.admin.galleries.gallery-edit',
            [
                'gallery' => $gallery,

                'editable' => $editable,

                'coverMediaAssets' => $coverMediaAssets,

                'mediaAssets' => $mediaAssets,

                'previewUrls' => $previewUrls,

                'coverPreviewUrl' => $gallery->coverMedia instanceof MediaAsset
                    ? $this->mediaPreviewUrl(
                        $gallery->coverMedia,
                    )
                    : null,
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Edit Gallery',
            ],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'titleSi' => ['nullable', 'string', 'max:255'],
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
            ],

            'eventDate' => [
                'nullable',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'coverMediaId' => [
                'nullable',
                'integer',
                'exists:media_assets,id',
            ],

            'publishedAt' => [
                'nullable',
                'date',
            ],

            'seoTitle' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seoDescription' => [
                'nullable',
                'string',
                'max:320',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Gallery Loading
    |--------------------------------------------------------------------------
    */

    private function loadGallery(
        Gallery $gallery,
    ): void {
        $this->galleryId =
            (int) $gallery->getKey();

        $this->titleSi = $this->stringValue($gallery->getAttribute('title_si'));

        $this->title =
            $this->stringValue(
                $gallery->getAttribute(
                    'title',
                ),
            );

        $this->slug =
            $this->stringValue(
                $gallery->getAttribute(
                    'slug',
                ),
            );

        $eventDate =
            $gallery->getAttribute(
                'event_date',
            );

        $this->eventDate =
            $eventDate instanceof DateTimeInterface
            ? $eventDate->format(
                'Y-m-d',
            )
            : '';

        $this->description =
            $this->stringValue(
                $gallery->getAttribute(
                    'description',
                ),
            );

        $coverMediaId =
            $gallery->getAttribute(
                'cover_media_id',
            );

        $this->coverMediaId =
            is_numeric(
                $coverMediaId,
            )
            ? (string) ((int) $coverMediaId)
            : '';

        $publishedAt =
            $gallery->getAttribute(
                'published_at',
            );

        $this->publishedAt =
            $publishedAt instanceof DateTimeInterface
            ? $publishedAt->format(
                'Y-m-d\TH:i',
            )
            : '';

        $this->seoTitle =
            $this->stringValue(
                $gallery->getAttribute(
                    'seo_title',
                ),
            );

        $this->seoDescription =
            $this->stringValue(
                $gallery->getAttribute(
                    'seo_description',
                ),
            );
    }

    private function loadImageMetadata(): void
    {
        $this->imageCaptions = [];

        $this->imageAltTexts = [];

        $galleryImages =
            GalleryImage::query()
                ->where(
                    'gallery_id',
                    $this->galleryId,
                )
                ->orderBy(
                    'sort_order',
                )
                ->orderBy(
                    'id',
                )
                ->get();

        foreach ($galleryImages as $galleryImage) {
            $galleryImageId =
                (int) $galleryImage->getKey();

            $this->imageCaptions[$galleryImageId] =
                $this->stringValue(
                    $galleryImage->getAttribute(
                        'caption',
                    ),
                );

            $this->imageAltTexts[$galleryImageId] =
                $this->stringValue(
                    $galleryImage->getAttribute(
                        'alt_text',
                    ),
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reorder Helper
    |--------------------------------------------------------------------------
    */
    private function moveImage(
        int $galleryImageId,
        int $offset,
    ): void {
        Gate::authorize(
            'galleries.update',
        );

        $images =
            GalleryImage::query()
                ->where(
                    'gallery_id',
                    $this->galleryId,
                )
                ->orderBy(
                    'sort_order',
                )
                ->orderBy(
                    'id',
                )
                ->get();

        /**
         * @var list<int> $ids
         */
        $ids = [];

        foreach ($images as $image) {
            $ids[] =
                (int) $image->getKey();
        }

        $currentIndex =
            array_search(
                $galleryImageId,
                $ids,
                true,
            );

        if ($currentIndex === false) {
            return;
        }

        $targetIndex =
            $currentIndex + $offset;

        if (
            $targetIndex < 0
            || $targetIndex >= count(
                $ids,
            )
        ) {
            return;
        }

        $temporary =
            $ids[$currentIndex];

        $ids[$currentIndex] =
            $ids[$targetIndex];

        $ids[$targetIndex] =
            $temporary;

        /**
         * Re-index the array so PHPStan can guarantee
         * that this value is a list<int>.
         *
         * @var list<int> $orderedImageIds
         */
        $orderedImageIds =
            array_values(
                $ids,
            );

        app(
            GalleryService::class,
        )->reorderImages(
            gallery: $this->gallery(),

            actor: $this->actor(),

            orderedImageIds: $orderedImageIds,
        );

        session()->flash(
            'status',
            'Gallery image order updated.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Model Helpers
    |--------------------------------------------------------------------------
    */

    private function gallery(): Gallery
    {
        return Gallery::query()
            ->findOrFail(
                $this->galleryId,
            );
    }

    private function galleryImage(
        int $galleryImageId,
    ): GalleryImage {
        return GalleryImage::query()
            ->where(
                'gallery_id',
                $this->galleryId,
            )
            ->findOrFail(
                $galleryImageId,
            );
    }

    private function coverMedia(): ?MediaAsset
    {
        if ($this->coverMediaId === '') {
            return null;
        }

        return MediaAsset::withTrashed()
            ->findOrFail(
                (int) $this->coverMediaId,
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Date Helpers
    |--------------------------------------------------------------------------
    */

    private function eventDateValue(): ?CarbonImmutable
    {
        if ($this->eventDate === '') {
            return null;
        }

        return CarbonImmutable::parse(
            $this->eventDate,
        )->startOfDay();
    }

    private function publicationDateValue(): ?CarbonImmutable
    {
        if ($this->publishedAt === '') {
            return null;
        }

        return CarbonImmutable::parse(
            $this->publishedAt,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Media Preview
    |--------------------------------------------------------------------------
    */

    private function mediaPreviewUrl(
        MediaAsset $media,
    ): ?string {
        if (
            $media->trashed()
            || ! $media->isImage()
            || ! $media->isPublic()
        ) {
            return null;
        }

        $variant =
            $media->variants()
                ->where(
                    'name',
                    MediaVariantPreset::Thumbnail->value,
                )
                ->first();

        if ($variant instanceof MediaVariant) {
            $url =
                $this->storageUrl(
                    disk: $variant->getAttribute(
                        'disk',
                    ),

                    path: $variant->getAttribute(
                        'path',
                    ),
                );

            if ($url !== null) {
                return $url;
            }
        }

        return $this->storageUrl(
            disk: $media->getAttribute(
                'disk',
            ),

            path: $media->getAttribute(
                'path',
            ),
        );
    }

    private function storageUrl(
        mixed $disk,
        mixed $path,
    ): ?string {
        if (
            ! is_string($disk)
            || trim($disk) === ''
            || ! is_string($path)
            || trim($path) === ''
        ) {
            return null;
        }

        return Storage::disk(
            $disk,
        )->url(
            $path,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Input Helpers
    |--------------------------------------------------------------------------
    */

    private function normaliseInput(): void
    {
        $this->title =
            trim(
                $this->title,
            );

        $this->slug =
            trim(
                $this->slug,
            );

        $this->eventDate =
            trim(
                $this->eventDate,
            );

        $this->description =
            trim(
                $this->description,
            );

        $this->coverMediaId =
            trim(
                $this->coverMediaId,
            );

        $this->publishedAt =
            trim(
                $this->publishedAt,
            );

        $this->seoTitle =
            trim(
                $this->seoTitle,
            );

        $this->seoDescription =
            trim(
                $this->seoDescription,
            );
    }

    private function nullableString(
        string $value,
    ): ?string {
        $value =
            trim(
                $value,
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function stringValue(
        mixed $value,
    ): string {
        return is_string(
            $value,
        )
            ? $value
            : '';
    }

    private function actor(): User
    {
        $actor =
            Auth::user();

        abort_unless(
            $actor instanceof User,
            403,
        );

        return $actor;
    }

    /** @param array<array-key, int|string> $ids */
    public function reorderImages(array $ids): void
    {
        Gate::authorize('galleries.update');
        Validator::make(['ids' => $ids], ['ids' => ['array'], 'ids.*' => ['required', 'integer', 'distinct']])->validate();
        $order = array_values(array_map('intval', $ids));
        app(GalleryService::class)->reorderImages(gallery: $this->gallery(), actor: $this->actor(), orderedImageIds: $order);
        session()->flash('status', 'Image order saved.');
    }

    public function saveAllImageDetails(): void
    {
        Gate::authorize('galleries.update');
        $this->validate(['imageCaptions' => ['array'], 'imageCaptions.*' => ['nullable', 'string', 'max:1000'], 'imageAltTexts' => ['array'], 'imageAltTexts.*' => ['nullable', 'string', 'max:255']]);
        DB::transaction(function (): void {
            foreach (array_keys($this->imageCaptions + $this->imageAltTexts) as $id) {
                app(GalleryService::class)->updateImage(galleryImage: $this->galleryImage((int) $id), actor: $this->actor(), caption: $this->nullableString($this->imageCaptions[$id] ?? ''), altText: $this->nullableString($this->imageAltTexts[$id] ?? ''));
            }
        });
        $this->loadImageMetadata();
        session()->flash('status', 'All image captions and descriptions saved.');
    }
}
