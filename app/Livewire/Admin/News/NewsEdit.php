<?php

namespace App\Livewire\Admin\News;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Enums\NewsEditorMode;
use App\Enums\NewsLocale;
use App\Enums\NewsStatus;
use App\Models\MediaAsset;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsImage;
use App\Models\User;
use App\Services\MediaUploadService;
use App\Services\NewsArticleService;
use App\Services\NewsImageService;
use App\Services\NewsWorkflowService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class NewsEdit extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $newsId;

    public string $title = '';

    public string $slug = '';

    public string $summary = '';

    public string $content = '';

    public string $sinhalaTitle = '';

    public string $sinhalaSummary = '';

    public string $sinhalaContent = '';

    public string $sinhalaSeoTitle = '';

    public string $sinhalaSeoDescription = '';

    #[Locked]
    public ?int $sinhalaNewsId = null;

    #[Locked]
    public bool $bilingual = false;

    public string $categoryId = '';

    public string $newsType = 'news_feature';

    public string $featuredImageId = '';

    public bool $isFeatured = false;

    public bool $showInGallery = false;

    public string $publishedAt = '';

    #[Locked]
    public string $editorMode = NewsEditorMode::Visual->value;

    /** @var array<int, TemporaryUploadedFile> */
    public array $galleryUploads = [];

    /* Kept for backward compatibility; intentionally hidden from the new UI. */

    public string $seoTitle = '';

    public string $seoDescription = '';

    public bool $slugManuallyEdited = true;

    public function mount(News $news): void
    {

        Gate::authorize('news.update');

        abort_if(

            $news->trashed(),

            404,

        );

        $rawLocale = $news->getRawOriginal('locale');

        $locale = is_string($rawLocale)

            ? NewsLocale::tryFrom($rawLocale)

            : null;

        $translationGroup = $news->getAttribute('translation_group');

        if (

            ($locale === NewsLocale::English || $locale === NewsLocale::Sinhala)

            && is_string($translationGroup)

            && trim($translationGroup) !== ''

        ) {

            $englishNews = News::query()

                ->where('translation_group', $translationGroup)

                ->where('locale', NewsLocale::English->value)

                ->first();

            $sinhalaNews = News::query()

                ->where('translation_group', $translationGroup)

                ->where('locale', NewsLocale::Sinhala->value)

                ->first();

            if (

                $englishNews instanceof News

                && $sinhalaNews instanceof News

            ) {

                abort_if(

                    $this->editorModeOf($englishNews) !== $this->editorModeOf($sinhalaNews),

                    409,

                    'English and Sinhala versions use different editor modes. Resolve the mismatch before editing them together.',

                );

                $this->bilingual = true;

                $this->newsId = (int) $englishNews->id;

                $this->sinhalaNewsId = (int) $sinhalaNews->id;

                $this->loadNews($englishNews);

                $this->loadSinhalaNews($sinhalaNews);

                return;

            }

        }

        $this->newsId = (int) $news->id;

        $this->loadNews($news);

    }

    public function updatedNewsType(string $value): void
    {
        if ($value === 'photo_story') {
            $cat = NewsCategory::query()->where('slug', 'photo-story')->first();
            if ($cat) {
                $this->categoryId = (string) $cat->id;
            }
        } elseif ($value === 'news_feature') {
            $cat = NewsCategory::query()->where('slug', 'news-feature')->first()
                ?? NewsCategory::query()->where('id', 1)->first();
            if ($cat) {
                $this->categoryId = (string) $cat->id;
            }
        }
    }

    public function updatedCategoryId(string $value): void
    {
        $this->newsType = NewsCategory::query()->find((int) $value)?->newsType() ?? 'news_feature';
    }

    public function updatedTitle(): void
    {

        if ($this->slugManuallyEdited) {

            return;

        }

        $this->slug = Str::slug($this->title);

    }

    public function updatedSlug(): void
    {

        $this->slugManuallyEdited = true;

        $this->slug = Str::slug($this->slug);

    }

    public function regenerateSlug(): void
    {

        $this->slugManuallyEdited = false;

        $this->slug = Str::slug($this->title);

    }

    public function unpublishForEditing(): void
    {
        Gate::authorize('news.update');

        app(NewsWorkflowService::class)->unpublishForEditing($this->news(), $this->actor());

        $this->loadNews($this->news());
        if ($this->bilingual) {
            $this->loadSinhalaNews($this->sinhalaNews());
        }

        $this->resetValidation();
        session()->flash('status', 'Published versions were unpublished and returned to Draft.');
    }

    public function saveAndPublish(): void
    {
        Gate::authorize('news.update');
        Gate::authorize('news.publish');

        DB::transaction(function (): void {
            $this->save();
            $workflow = app(NewsWorkflowService::class);
            $workflow->publishImmediately($this->news(), $this->actor());
            if ($this->bilingual) {
                $workflow->publishImmediately($this->sinhalaNews(), $this->actor());
            }
        }, 3);

        $news = $this->news();
        $this->loadNews($news);
        if ($this->bilingual) {
            $this->loadSinhalaNews($this->sinhalaNews());
        }

        session()->flash('status', $news->published_at?->isFuture()
            ? 'Changes saved. Publication is scheduled for '.$news->published_at->format('d M Y H:i').' ('.config('app.timezone').').'
            : 'Changes saved and published successfully.');
    }

    public function save(): void
    {

        Gate::authorize('news.update');

        $news = $this->news();

        $this->assertDraft($news);

        $sinhalaNews = $this->bilingual

            ? $this->sinhalaNews()

            : null;

        if ($sinhalaNews instanceof News) {

            $this->assertDraft($sinhalaNews);

            abort_if(

                $this->editorModeOf($news) !== $this->editorModeOf($sinhalaNews),

                409,

                'English and Sinhala versions use different editor modes.',

            );

        }

        $this->normaliseInput();

        $this->validate();

        $category = NewsCategory::query()->findOrFail(

            (int) $this->categoryId,

        );

        $actor = $this->actor();

        $featuredImage = $this->featuredImage();

        $publishedAt = $this->publicationDate();

        $service = app(NewsArticleService::class);

        if ($sinhalaNews instanceof News) {

            /** @var array{0: News, 1: News} $updatedPair */
            $updatedPair = DB::transaction(

                function () use (

                    $service,

                    $news,

                    $sinhalaNews,

                    $actor,

                    $category,

                    $featuredImage,

                    $publishedAt,

                ): array {

                    $english = $service->update(

                        news: $news,

                        actor: $actor,

                        category: $category,

                        title: $this->title,

                        content: $this->content,

                        summary: $this->nullable($this->summary),

                        slug: $this->nullable($this->slug),

                        featuredImage: $featuredImage,

                        isFeatured: $this->isFeatured,

                        publishedAt: $publishedAt,

                        seoTitle: $this->nullable($this->seoTitle),

                        seoDescription: $this->nullable($this->seoDescription),

                        showInGallery: $this->showInGallery,

                        newsType: $this->newsType,

                    );

                    $sinhala = $service->update(

                        news: $sinhalaNews,

                        actor: $actor,

                        category: $category,

                        title: $this->sinhalaTitle,

                        content: $this->sinhalaContent,

                        summary: $this->nullable($this->sinhalaSummary),

                        slug: $this->nullable($this->slug),

                        featuredImage: $featuredImage,

                        isFeatured: $this->isFeatured,

                        publishedAt: $publishedAt,

                        seoTitle: $this->nullable($this->sinhalaSeoTitle),

                        seoDescription: $this->nullable($this->sinhalaSeoDescription),

                        showInGallery: $this->showInGallery,

                        newsType: $this->newsType,

                    );

                    return [$english, $sinhala];

                },

                3,

            );

            $this->loadNews($updatedPair[0]);

            $this->loadSinhalaNews($updatedPair[1]);

            session()->flash(

                'status',

                'English and Sinhala news articles were saved together.',

            );

            return;

        }

        $news = $service->update(

            news: $news,

            actor: $actor,

            category: $category,

            title: $this->title,

            content: $this->content,

            summary: $this->nullable($this->summary),

            slug: $this->nullable($this->slug),

            featuredImage: $featuredImage,

            isFeatured: $this->isFeatured,

            publishedAt: $publishedAt,

            seoTitle: $this->nullable($this->seoTitle),

            seoDescription: $this->nullable($this->seoDescription),

            showInGallery: $this->showInGallery,

            newsType: $this->newsType,

        );

        $this->loadNews($news);

        session()->flash(

            'status',

            'News article saved.',

        );

    }

    public function uploadGalleryImages(): void
    {

        Gate::authorize('news.update');

        Gate::authorize('media.upload');

        $news = $this->news();

        $this->assertDraft($news);

        $sinhalaNews = $this->bilingual

            ? $this->sinhalaNews()

            : null;

        if ($sinhalaNews instanceof News) {

            $this->assertDraft($sinhalaNews);

        }

        /*

         * Validate the selected upload batch.

         *

         * - Maximum 30 selected files

         * - Maximum 1 MB per image

         * - Images only

         * - JPG / JPEG / PNG / WEBP only

         */

        $this->validate(

            [

                'galleryUploads' => [

                    'required',

                    'array',

                    'min:1',

                    'max:30',

                ],

                'galleryUploads.*' => [

                    'required',

                    'file',

                    'image',

                    'mimes:jpg,jpeg,png,webp',

                    'max:1024',

                ],

            ],

            [

                'galleryUploads.required' => 'Select at least one image.',

                'galleryUploads.max' => 'You may select a maximum of 30 images.',

                'galleryUploads.*.image' => 'Every selected file must be a valid image.',

                'galleryUploads.*.mimes' => 'Only JPG, JPEG, PNG and WEBP images are allowed.',

                'galleryUploads.*.max' => 'Each image must be 1 MB or smaller.',

            ],

        );

        /*

         * Maximum 30 images for the whole news article.

         */

        $currentImageCount = NewsImage::query()

            ->where('news_id', $this->newsId)

            ->count();

        if ($sinhalaNews instanceof News) {

            $currentImageCount = max(

                $currentImageCount,

                NewsImage::query()

                    ->where('news_id', $sinhalaNews->id)

                    ->count(),

            );

        }

        $incomingImageCount = count(

            $this->galleryUploads,

        );

        if (

            ($currentImageCount + $incomingImageCount)

            > 30

        ) {

            $remaining =

                max(

                    0,

                    30 - $currentImageCount,

                );

            $this->addError(

                'galleryUploads',

                sprintf(

                    'This article can contain a maximum of 30 images. You can upload only %d more image(s).',

                    $remaining,

                ),

            );

            return;

        }

        /*

         * Additional filename + total-size checks.

         */

        $totalBytes = 0;

        foreach ($this->galleryUploads as $file) {

            $originalName =

                $file->getClientOriginalName();

            /*

             * Original filenames containing uppercase

             * English letters are not accepted.

             *

             * Examples rejected:

             * IMG_001.jpg

             * ArmyPhoto.png

             * image.JPG

             */

            if (

                preg_match(

                    '/[A-Z]/',

                    $originalName,

                ) === 1

            ) {

                $this->addError(

                    'galleryUploads',

                    sprintf(

                        'The filename "%s" contains capital letters. Rename it using lowercase letters only and try again.',

                        $originalName,

                    ),

                );

                return;

            }

            $size =

    $file->getSize();

            $totalBytes += $size;

        }

        /*

         * Maximum combined upload size = 30 MB.

         */

        if (

            $totalBytes

            > (30 * 1024 * 1024)

        ) {

            $this->addError(

                'galleryUploads',

                'The total size of the selected images must not exceed 30 MB.',

            );

            return;

        }

        $actor =

            $this->actor();

        foreach (

            $this->galleryUploads as $index => $file

        ) {

            $extension =

                strtolower(

                    $file->getClientOriginalExtension(),

                );

            /*

             * Continue numbering after images already

             * attached to this news article.

             *

             * Example:

             * 2026_10-01image-01.png

             * 2026_10-01image-02.jpg

             * 2026_10-01image-03.webp

             */

            $sequence =

                $currentImageCount

                + $index

                + 1;

            $renamedName =

                sprintf(

                    '%simage-%02d.%s',

                    now()->format('Y_m-d'),

                    $sequence,

                    $extension,

                );

            $realPath =

    $file->getRealPath();

            if (

                $realPath === ''

                || ! is_file($realPath)

            ) {

                $this->addError(

                    'galleryUploads',

                    'One of the selected images could not be prepared for upload.',

                );

                return;

            }

            /*

             * Create a safe upload wrapper with our

             * generated client-visible filename.

             *

             * MediaUploadService will STILL use its secure

             * UUID filename for physical storage.

             */

            $renamedFile =

                new UploadedFile(

                    path: $realPath,

                    originalName: $renamedName,

                    mimeType: $file->getMimeType(),

                    error: UPLOAD_ERR_OK,

                    test: true,

                );

            $media =

                app(

                    MediaUploadService::class,

                )->upload(

                    file: $renamedFile,

                    type: MediaType::Image,

                    visibility: MediaVisibility::Public,

                    actor: $actor,

                    title: $renamedName,

                    altText: null,

                    caption: null,

                );

            DB::transaction(

                function () use (

                    $news,

                    $sinhalaNews,

                    $media,

                    $actor,

                ): void {

                    $imageService = app(NewsImageService::class);

                    $imageService->addImage(

                        news: $news,

                        media: $media,

                        actor: $actor,

                    );

                    if ($sinhalaNews instanceof News) {

                        $imageService->addImage(

                            news: $sinhalaNews,

                            media: $media,

                            actor: $actor,

                        );

                    }

                },

                3,

            );

        }

        $this->reset(

            'galleryUploads',

        );

        session()->flash(

            'status',

            $this->bilingual

                ? 'Shared English and Sinhala news images uploaded successfully.'

                : 'News images uploaded successfully.',

        );

    }

    public function removeGalleryImage(int $newsImageId): void
    {

        Gate::authorize('news.update');

        $news = $this->news();

        $this->assertDraft($news);

        $newsImage = NewsImage::query()

            ->where('news_id', $this->newsId)

            ->findOrFail($newsImageId);

        $mediaAssetId = (int) $newsImage->media_asset_id;

        $actor = $this->actor();

        $sinhalaNews = $this->bilingual

            ? $this->sinhalaNews()

            : null;

        if ($sinhalaNews instanceof News) {

            $this->assertDraft($sinhalaNews);

        }

        DB::transaction(

            function () use (

                $newsImage,

                $sinhalaNews,

                $mediaAssetId,

                $actor,

            ): void {

                $service = app(NewsImageService::class);

                $service->removeImage(

                    newsImage: $newsImage,

                    actor: $actor,

                );

                if ($sinhalaNews instanceof News) {

                    $sinhalaImage = NewsImage::query()

                        ->where('news_id', $sinhalaNews->id)

                        ->where('media_asset_id', $mediaAssetId)

                        ->first();

                    if ($sinhalaImage instanceof NewsImage) {

                        $service->removeImage(

                            newsImage: $sinhalaImage,

                            actor: $actor,

                        );

                    }

                }

            },

            3,

        );

        session()->flash(

            'status',

            $this->bilingual

                ? 'Image removed from both English and Sinhala versions.'

                : 'News image removed from this article.',

        );

    }

    public function selectFeaturedImage(int $newsImageId): void
    {

        Gate::authorize('news.update');

        $news = $this->news();

        $this->assertDraft($news);

        $newsImage = NewsImage::query()

            ->where('news_id', $this->newsId)

            ->findOrFail($newsImageId);

        $actor = $this->actor();

        $mediaAssetId = (int) $newsImage->media_asset_id;

        $sinhalaNews = $this->bilingual

            ? $this->sinhalaNews()

            : null;

        if ($sinhalaNews instanceof News) {

            $this->assertDraft($sinhalaNews);

        }

        DB::transaction(

            function () use (

                $newsImage,

                $sinhalaNews,

                $mediaAssetId,

                $actor,

            ): void {

                $service = app(NewsImageService::class);

                $service->setFeaturedImage(

                    newsImage: $newsImage,

                    actor: $actor,

                );

                if ($sinhalaNews instanceof News) {

                    $sinhalaImage = NewsImage::query()

                        ->where('news_id', $sinhalaNews->id)

                        ->where('media_asset_id', $mediaAssetId)

                        ->first();

                    if (! $sinhalaImage instanceof NewsImage) {

                        $media = MediaAsset::query()->findOrFail($mediaAssetId);

                        $service->addImage(

                            news: $sinhalaNews,

                            media: $media,

                            actor: $actor,

                        );

                        $sinhalaImage = NewsImage::query()

                            ->where('news_id', $sinhalaNews->id)

                            ->where('media_asset_id', $mediaAssetId)

                            ->firstOrFail();

                    }

                    $service->setFeaturedImage(

                        newsImage: $sinhalaImage,

                        actor: $actor,

                    );

                }

            },

            3,

        );

        $this->featuredImageId = (string) $mediaAssetId;

        session()->flash(

            'status',

            $this->bilingual

                ? 'Featured image selected for both English and Sinhala versions.'

                : 'Featured image selected.',

        );

    }

    public function clearFeaturedImage(): void
    {

        Gate::authorize('news.update');

        $news = $this->news();

        $this->assertDraft($news);

        $actor = $this->actor();

        $sinhalaNews = $this->bilingual

            ? $this->sinhalaNews()

            : null;

        if ($sinhalaNews instanceof News) {

            $this->assertDraft($sinhalaNews);

        }

        DB::transaction(

            function () use (

                $news,

                $sinhalaNews,

                $actor,

            ): void {

                $service = app(NewsImageService::class);

                $service->clearFeaturedImage(

                    news: $news,

                    actor: $actor,

                );

                if ($sinhalaNews instanceof News) {

                    $service->clearFeaturedImage(

                        news: $sinhalaNews,

                        actor: $actor,

                    );

                }

            },

            3,

        );

        $this->featuredImageId = '';

        session()->flash(

            'status',

            $this->bilingual

                ? 'Featured image cleared from both English and Sinhala versions.'

                : 'Featured image cleared.',

        );

    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {

        $rules = [

            'title' => ['required', 'string', 'max:255'],

            'slug' => ['nullable', 'string', 'max:255'],

            'summary' => ['nullable', 'string', 'max:2000'],

            'content' => ['required', 'string', 'max:250000'],

            'newsType' => ['required', 'string', 'in:photo_story,news_feature'],

            'categoryId' => ['required', 'integer', 'exists:news_categories,id'],

            'featuredImageId' => ['nullable', 'integer', 'exists:media_assets,id'],

            'isFeatured' => ['boolean'],

            'showInGallery' => ['boolean'],

            'publishedAt' => ['nullable', 'date'],

            'galleryUploads' => [

                'array',

                'max:30',

            ],

            'galleryUploads.*' => [

                'file',

                'image',

                'mimes:jpg,jpeg,png,webp',

                'max:1024',

            ],

            'seoTitle' => ['nullable', 'string', 'max:255'],

            'seoDescription' => ['nullable', 'string', 'max:320'],

        ];

        if ($this->bilingual) {

            $rules['sinhalaTitle'] = ['required', 'string', 'max:255'];

            $rules['sinhalaSummary'] = ['nullable', 'string', 'max:2000'];

            $rules['sinhalaContent'] = ['required', 'string', 'max:250000'];

            $rules['sinhalaSeoTitle'] = ['nullable', 'string', 'max:255'];

            $rules['sinhalaSeoDescription'] = ['nullable', 'string', 'max:320'];

        }

        return $rules;

    }

    /**
     * @param  array<int, int|string>  $orderedIds
     */
    public function reorderGalleryImages(array $orderedIds): void
    {

        Gate::authorize('news.update');

        $news = $this->news();

        $this->assertDraft($news);

        $sinhalaNews = $this->bilingual

            ? $this->sinhalaNews()

            : null;

        if ($sinhalaNews instanceof News) {

            $this->assertDraft($sinhalaNews);

        }

        $orderedIds = collect($orderedIds)

            ->map(static fn (int|string $id): int => (int) $id)

            ->filter(static fn (int $id): bool => $id > 0)

            ->unique()

            ->values();

        $existingIds = NewsImage::query()

            ->where('news_id', $this->newsId)

            ->whereIn('id', $orderedIds->all())

            ->pluck('id')

            ->map(static fn (int|string $id): int => (int) $id)

            ->sort()

            ->values();

        abort_unless(

            $existingIds->all() === $orderedIds->sort()->values()->all(),

            422,

            'Invalid news image order.',

        );

        $orderedMediaIds = [];

        foreach ($orderedIds as $newsImageId) {

            $mediaAssetId = NewsImage::query()

                ->where('news_id', $this->newsId)

                ->whereKey($newsImageId)

                ->value('media_asset_id');

            abort_unless(

                is_numeric($mediaAssetId),

                422,

                'Invalid news image order.',

            );

            $orderedMediaIds[] = (int) $mediaAssetId;

        }

        if ($sinhalaNews instanceof News) {

            $this->synchroniseSinhalaImagesFromEnglish($this->actor());

        }

        DB::transaction(

            function () use (

                $orderedIds,

                $orderedMediaIds,

                $sinhalaNews,

            ): void {

                foreach ($orderedIds as $index => $newsImageId) {

                    NewsImage::query()

                        ->where('news_id', $this->newsId)

                        ->whereKey($newsImageId)

                        ->update([

                            'sort_order' => $index + 1,

                        ]);

                }

                if ($sinhalaNews instanceof News) {

                    foreach ($orderedMediaIds as $index => $mediaAssetId) {

                        NewsImage::query()

                            ->where('news_id', $sinhalaNews->id)

                            ->where('media_asset_id', $mediaAssetId)

                            ->update([

                                'sort_order' => $index + 1,

                            ]);

                    }

                }

            },

            3,

        );

        $this->news()->unsetRelation('images');

        if ($sinhalaNews instanceof News) {

            $sinhalaNews->unsetRelation('images');

        }

    }

    public function render(): View
    {

        Gate::authorize('news.update');

        $news = $this->news()->load([

            'translationVersions',

            'images.media.variants',

        ]);

        $status = $this->statusOf($news);

        $editable = $status === NewsStatus::Draft;

        $blockingStatus = $status;

        $lockedVersions = [];
        $canUnpublishForEditing = $status === NewsStatus::Published;
        if ($status !== NewsStatus::Draft) {
            $lockedVersions[] = ['language' => $news->locale->nativeLabel(), 'status' => $status?->label() ?? 'Unknown'];
        }

        $sinhalaNews = null;

        if ($this->bilingual) {

            $sinhalaNews = $this->sinhalaNews()->load([

                'images.media.variants',

            ]);

            $sinhalaStatus = $this->statusOf($sinhalaNews);

            $canUnpublishForEditing = $canUnpublishForEditing || $sinhalaStatus === NewsStatus::Published;

            if ($sinhalaStatus !== NewsStatus::Draft) {

                $editable = false;

                $blockingStatus = $sinhalaStatus;

                $lockedVersions[] = ['language' => $sinhalaNews->locale->nativeLabel(), 'status' => $sinhalaStatus?->label() ?? 'Unknown'];

            }

        }

        return view(

            'livewire.admin.news.news-edit',

            [

                'news' => $news,

                'sinhalaNews' => $sinhalaNews,

                'categories' => NewsCategory::query()

                    ->active()

                    ->ordered()

                    ->get(),

                'images' => MediaAsset::query()

                    ->where('type', MediaType::Image->value)

                    ->where('visibility', MediaVisibility::Public->value)

                    ->orderByDesc('created_at')

                    ->limit(100)

                    ->get(),

                'locales' => app(\App\Services\SiteLanguageService::class)->choices(NewsLocale::cases(), is_string($news->getRawOriginal('locale')) ? $news->getRawOriginal('locale') : null),

                'status' => $blockingStatus ?? NewsStatus::Draft,

                'editable' => $editable,

                'lockedVersions' => $lockedVersions,

                'canUnpublishForEditing' => $canUnpublishForEditing,

            ],

        )->layout(

            'components.layouts.admin',

            [

                'title' => $this->bilingual

                    ? 'Edit English + Sinhala News'

                    : 'Edit News',

            ],

        );

    }

    private function loadNews(News $news): void
    {

        $this->title = $this->stringAttribute(

            $news,

            'title',

        );

        $this->slug = $this->stringAttribute(

            $news,

            'slug',

        );

        $this->summary = $this->stringAttribute(

            $news,

            'summary',

        );

        $this->content = $this->stringAttribute(

            $news,

            'content',

        );

        $this->newsType = $news->category?->newsType() ?? 'news_feature';

        $categoryId = $news->getAttribute('category_id');

        $this->categoryId = is_numeric($categoryId)

            ? (string) $categoryId

            : '';

        $featuredImageId = $news->getAttribute('featured_image_id');

        $this->featuredImageId = is_numeric($featuredImageId)

            ? (string) $featuredImageId

            : '';

        $this->isFeatured = (bool) $news->getAttribute('is_featured');

        $this->showInGallery = (bool) $news->getAttribute('show_in_gallery');

        $publishedAt = $news->getAttribute('published_at');

        $this->publishedAt = $publishedAt instanceof DateTimeInterface

            ? $publishedAt->format('Y-m-d\TH:i')

            : '';

        $this->seoTitle = $this->stringAttribute(

            $news,

            'seo_title',

        );

        $this->seoDescription = $this->stringAttribute(

            $news,

            'seo_description',

        );

        $rawEditorMode = $news->getRawOriginal('editor_mode');

        $editorMode = is_string($rawEditorMode)

            ? NewsEditorMode::tryFrom($rawEditorMode)

            : null;

        $this->editorMode = ($editorMode ?? NewsEditorMode::Visual)->value;

    }

    private function loadSinhalaNews(News $news): void
    {

        $this->sinhalaTitle = $this->stringAttribute(

            $news,

            'title',

        );

        $this->sinhalaSummary = $this->stringAttribute(

            $news,

            'summary',

        );

        $this->sinhalaContent = $this->stringAttribute(

            $news,

            'content',

        );

        $this->sinhalaSeoTitle = $this->stringAttribute(

            $news,

            'seo_title',

        );

        $this->sinhalaSeoDescription = $this->stringAttribute(

            $news,

            'seo_description',

        );

    }

    private function normaliseInput(): void
    {

        $this->title = trim($this->title);

        $this->slug = Str::slug($this->slug);

        $this->summary = trim($this->summary);

        $this->sinhalaTitle = trim($this->sinhalaTitle);

        $this->sinhalaSummary = trim($this->sinhalaSummary);

        $this->sinhalaSeoTitle = trim($this->sinhalaSeoTitle);

        $this->sinhalaSeoDescription = trim($this->sinhalaSeoDescription);

        $this->categoryId = trim($this->categoryId);

        $this->updatedCategoryId($this->categoryId);

        $this->featuredImageId = trim($this->featuredImageId);

        $this->publishedAt = trim($this->publishedAt);

        $this->seoTitle = trim($this->seoTitle);

        $this->seoDescription = trim($this->seoDescription);

    }

    private function featuredImage(): ?MediaAsset
    {

        if ($this->featuredImageId === '') {

            return null;

        }

        return MediaAsset::query()->findOrFail(

            (int) $this->featuredImageId,

        );

    }

    private function publicationDate(): ?DateTimeInterface
    {

        if ($this->publishedAt === '') {

            return null;

        }

        return CarbonImmutable::parse($this->publishedAt);

    }

    private function nullable(string $value): ?string
    {

        $value = trim($value);

        return $value !== ''

            ? $value

            : null;

    }

    private function statusOf(News $news): ?NewsStatus
    {

        $rawStatus = $news->getRawOriginal('status');

        return is_string($rawStatus)

            ? NewsStatus::tryFrom($rawStatus)

            : null;

    }

    private function assertDraft(News $news): void
    {
        if ($this->statusOf($news) !== NewsStatus::Draft) {
            throw ValidationException::withMessages([
                'news' => 'Unpublish this article before editing it.',
            ]);
        }

    }

    private function editorModeOf(News $news): NewsEditorMode
    {

        $rawEditorMode = $news->getRawOriginal('editor_mode');

        $editorMode = is_string($rawEditorMode)

            ? NewsEditorMode::tryFrom($rawEditorMode)

            : null;

        return $editorMode ?? NewsEditorMode::Visual;

    }

    private function synchroniseSinhalaImagesFromEnglish(User $actor): void
    {

        if (! $this->bilingual) {

            return;

        }

        $englishNews = $this->news();

        $sinhalaNews = $this->sinhalaNews();

        $service = app(NewsImageService::class);

        DB::transaction(

            function () use (

                $englishNews,

                $sinhalaNews,

                $service,

                $actor,

            ): void {

                $englishImages = NewsImage::query()

                    ->where('news_id', $englishNews->id)

                    ->orderBy('sort_order')

                    ->orderBy('id')

                    ->get();

                $englishMediaIds = $englishImages

                    ->pluck('media_asset_id')

                    ->map(static fn (int|string $id): int => (int) $id)

                    ->all();

                $sinhalaImages = NewsImage::query()

                    ->where('news_id', $sinhalaNews->id)

                    ->get();

                foreach ($sinhalaImages as $sinhalaImage) {

                    if (! in_array((int) $sinhalaImage->media_asset_id, $englishMediaIds, true)) {

                        $service->removeImage(

                            newsImage: $sinhalaImage,

                            actor: $actor,

                        );

                    }

                }

                $existingSinhalaMediaIds = NewsImage::query()

                    ->where('news_id', $sinhalaNews->id)

                    ->pluck('media_asset_id')

                    ->map(static fn (int|string $id): int => (int) $id)

                    ->all();

                foreach ($englishMediaIds as $mediaAssetId) {

                    if (in_array($mediaAssetId, $existingSinhalaMediaIds, true)) {

                        continue;

                    }

                    $media = MediaAsset::query()->findOrFail($mediaAssetId);

                    $service->addImage(

                        news: $sinhalaNews,

                        media: $media,

                        actor: $actor,

                    );

                }

                foreach ($englishMediaIds as $index => $mediaAssetId) {

                    NewsImage::query()

                        ->where('news_id', $sinhalaNews->id)

                        ->where('media_asset_id', $mediaAssetId)

                        ->update([

                            'sort_order' => $index + 1,

                        ]);

                }

            },

            3,

        );

    }

    private function stringAttribute(News $news, string $attribute): string
    {

        $value = $news->getAttribute($attribute);

        return is_string($value)

            ? $value

            : '';

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

    private function sinhalaNews(): News
    {

        $newsId = $this->sinhalaNewsId;

        if ($newsId === null) {

            abort(404);

        }

        return News::query()->findOrFail(

            $newsId,

        );

    }

    private function news(): News
    {

        return News::query()->findOrFail(

            $this->newsId,

        );

    }
}
