<?php

namespace App\Livewire\Admin\News;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Enums\NewsEditorMode;
use App\Enums\NewsLocale;
use App\Models\MediaAsset;
use App\Models\News;
use App\Models\NewsCategory;
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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class NewsCreate extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $slug = '';

    public string $summary = '';

    public string $content = '';

    public string $sinhalaTitle = '';

    public string $sinhalaSummary = '';

    public string $sinhalaContent = '';

    public string $categoryId = '';

    public string $newsType = 'news_feature';

    public string $featuredImageId = '';

    public bool $isFeatured = false;

    public bool $showInGallery = false;

    public string $publishedAt = '';

    public string $locale = NewsLocale::English->value;

    public string $editorMode = NewsEditorMode::Visual->value;

    /** @var array<int, TemporaryUploadedFile> */
    public array $galleryUploads = [];

    #[Locked]
    public string $translationGroup = '';

    #[Locked]
    public ?int $translationSourceNewsId = null;

    #[Locked]
    public string $translationSourceTitle = '';

    /* Kept for backward compatibility; intentionally hidden from the new UI. */

    public string $seoTitle = '';

    public string $seoDescription = '';

    public bool $slugManuallyEdited = false;

    public function mount(

        ?int $newsId = null,

        ?string $locale = null,

    ): void {

        Gate::authorize('news.create');

        if ($newsId === null) {

            $this->translationGroup = Str::uuid()->toString();

            $defaultCat = NewsCategory::query()->active()->where('slug', 'news-feature')->first()
                ?? NewsCategory::query()->active()->ordered()->first();
            if ($defaultCat) {
                $this->categoryId = (string) $defaultCat->id;
                $this->newsType = $defaultCat->newsType();
            }

            return;

        }

        $news = News::query()->findOrFail($newsId);

        $targetLocale = is_string($locale)

            ? NewsLocale::tryFrom($locale)

            : null;

        abort_unless(

            $targetLocale instanceof NewsLocale && app(\App\Services\SiteLanguageService::class)->enabled($targetLocale->value),

            404,

        );

        $rawSourceLocale = $news->getRawOriginal('locale');

        $sourceLocale = is_string($rawSourceLocale)

            ? NewsLocale::tryFrom($rawSourceLocale)

            : null;

        $sourceLocale ??= NewsLocale::English;

        abort_if(

            $sourceLocale === $targetLocale,

            409,

            'That language version already exists.',

        );

        $translationGroup = $news->getAttribute('translation_group');

        abort_unless(

            is_string($translationGroup) && trim($translationGroup) !== '',

            409,

            'The source article does not have a translation group.',

        );

        $existing = News::withTrashed()

            ->where('translation_group', $translationGroup)

            ->where('locale', $targetLocale->value)

            ->exists();

        abort_if(

            $existing,

            409,

            'That language version already exists.',

        );

        $this->locale = $targetLocale->value;

        $this->translationGroup = $translationGroup;

        $this->translationSourceNewsId = (int) $news->id;

        $this->translationSourceTitle = (string) $news->title;

        /*

         * No automatic translation and no source body copy.

         * The operator enters the approved language content manually.

         */

        $this->title = '';

        $this->slug = '';

        $this->summary = '';

        $this->content = '';

        $this->editorMode = NewsEditorMode::Visual->value;

        if ($news->category_id !== null) {

            $this->categoryId = (string) $news->category_id;
            $this->updatedCategoryId($this->categoryId);

        }

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

    public function updatedLocale(): void
    {

        if ($this->translationSourceNewsId !== null) {

            return;

        }

        if (NewsLocale::tryFrom($this->locale) === null) {

            $this->locale = NewsLocale::English->value;

        }

    }

    public function regenerateSlug(): void
    {

        $this->slugManuallyEdited = false;

        $this->slug = Str::slug($this->title);

    }

    public function save(): void
    {
        Gate::authorize('news.create');

        $this->normaliseInput();
        $this->validate();

        $editorMode = NewsEditorMode::from($this->editorMode);
        $actor = $this->actor();

        $category = NewsCategory::query()->findOrFail(
            (int) $this->categoryId,
        );

        /*
         * Keep the existing manual-translation route working.
         * This remains available for Tamil and older translation groups.
         */
        if ($this->translationSourceNewsId !== null) {
            $uploadedGalleryMedia = $this->uploadGalleryMedia($actor);

            $news = app(NewsArticleService::class)->create(
                actor: $actor,
                category: $category,
                title: $this->title,
                content: $this->content,
                summary: $this->nullable($this->summary),
                slug: $this->nullable($this->slug),
                featuredImage: $this->featuredImage(),
                isFeatured: $this->isFeatured,
                showInGallery: $this->showInGallery,
                publishedAt: $this->publicationDate(),
                seoTitle: $this->nullable($this->seoTitle),
                seoDescription: $this->nullable($this->seoDescription),
                locale: NewsLocale::from($this->locale),
                translationGroup: $this->translationGroup,
                editorMode: $editorMode,
                newsType: $this->newsType,
            );

            foreach ($uploadedGalleryMedia as $media) {
                app(NewsImageService::class)->addImage(
                    news: $news,
                    media: $media,
                    actor: $actor,
                );
            }

            session()->flash(
                'status',
                'News translation was created as a draft.',
            );

            $this->redirectRoute(
                'admin.news.edit',
                ['news' => $news->id],
                navigate: true,
            );

            return;
        }

        $newsPair = $this->createBilingualPair(
            actor: $actor,
            category: $category,
            editorMode: $editorMode,
            publishImmediately: false,
        );

        $englishNews = $newsPair[0];

        session()->flash(
            'status',
            $newsPair[1] instanceof News
                ? 'English and Sinhala news articles were created as drafts.'
                : 'English news article was created as a draft.',
        );

        $this->redirectRoute(
            'admin.news.edit',
            ['news' => $englishNews->id],
            navigate: true,
        );
    }

    public function saveAndPublish(): void
    {
        Gate::authorize('news.create');
        Gate::authorize('news.publish');

        abort_if(
            $this->translationSourceNewsId !== null,
            409,
            'Save & Publish is available only for the normal news create flow.',
        );

        $this->normaliseInput();
        $this->validate();

        $actor = $this->actor();

        $category = NewsCategory::query()->findOrFail(
            (int) $this->categoryId,
        );

        $editorMode = NewsEditorMode::from($this->editorMode);

        $newsPair = $this->createBilingualPair(
            actor: $actor,
            category: $category,
            editorMode: $editorMode,
            publishImmediately: true,
        );

        $englishNews = $newsPair[0];

        session()->flash(
            'status',
            $englishNews->published_at?->isFuture()
                ? 'Publication is scheduled for '.$englishNews->published_at->format('d M Y H:i').' ('.config('app.timezone').').'
                : ($newsPair[1] instanceof News
                ? 'English and Sinhala news articles were published successfully.'
                : 'English news article was published successfully.'),
        );

        $this->redirectRoute(
            'admin.news.edit',
            ['news' => $englishNews->id],
            navigate: true,
        );
    }

    /**
     * @param  array<int, int|string>  $orderedIndexes
     */
    public function reorderPendingGalleryImages(

        array $orderedIndexes,

    ): void {

        Gate::authorize('news.create');

        if ($this->galleryUploads === []) {

            return;

        }

        $orderedIndexes = collect($orderedIndexes)

            ->map(

                static fn (int|string $index): int => (int) $index,

            )

            ->filter(

                static fn (int $index): bool => $index >= 0,

            )

            ->unique()

            ->values();

        $expectedIndexes = collect(

            array_keys($this->galleryUploads),

        )

            ->map(

                static fn (int|string $index): int => (int) $index,

            )

            ->sort()

            ->values();

        $submittedIndexes = $orderedIndexes

            ->sort()

            ->values();

        abort_unless(

            $expectedIndexes->all()

                === $submittedIndexes->all(),

            422,

            'Invalid image order.',

        );

        $reordered = [];

        foreach ($orderedIndexes as $index) {

            $reordered[] =

                $this->galleryUploads[$index];

        }

        $this->galleryUploads = $reordered;

    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {

        $rules = [

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

            'summary' => [

                'nullable',

                'string',

                'max:2000',

            ],

            'content' => [

                'required',

                'string',

                'max:250000',

            ],

            'newsType' => [

                'required',

                'string',

                'in:photo_story,news_feature',

            ],

            'categoryId' => [

                'required',

                'integer',

                'exists:news_categories,id',

            ],

            'featuredImageId' => [

                'nullable',

                'integer',

                'exists:media_assets,id',

            ],

            'isFeatured' => [

                'boolean',

            ],

            'showInGallery' => [

                'boolean',

            ],

            'publishedAt' => [

                'nullable',

                'date',

            ],

            'locale' => [

                'required',

                Rule::in(app(\App\Services\SiteLanguageService::class)->codes()),

            ],

            'editorMode' => [

                'required',

                Rule::enum(

                    NewsEditorMode::class,

                ),

            ],

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

        /*
         * English is the primary version.
         *
         * Sinhala is optional. If the user starts entering any Sinhala
         * content, require both Sinhala title and Sinhala body so that a
         * partial/invalid translation is never created.
         *
         * Manual translation mode keeps the existing single-language flow.
         */
        if (
            $this->translationSourceNewsId
            === null
        ) {
            $rules['sinhalaTitle'] = [
                'nullable',
                'required_with:sinhalaSummary,sinhalaContent',
                'string',
                'max:255',
            ];

            $rules['sinhalaSummary'] = [
                'nullable',
                'string',
                'max:2000',
            ];

            $rules['sinhalaContent'] = [
                'nullable',
                'required_with:sinhalaTitle,sinhalaSummary',
                'string',
                'max:250000',
            ];
        }

        return $rules;

    }

    public function render(): View
    {

        Gate::authorize('news.create');

        return view(

            'livewire.admin.news.news-create',

            [

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

                'locales' => app(\App\Services\SiteLanguageService::class)->choices(NewsLocale::cases()),

            ],

        )->layout(

            'components.layouts.admin',

            [

                'title' => 'Create News',

            ],

        );

    }

    private function normaliseInput(): void
    {

        $this->title = trim($this->title);

        $this->slug = Str::slug($this->slug);

        $this->summary = trim($this->summary);

        $this->sinhalaTitle =

    trim(

        $this->sinhalaTitle,

    );

        $this->sinhalaSummary =

            trim(

                $this->sinhalaSummary,

            );

        $this->sinhalaContent =

            trim(

                $this->sinhalaContent,

            );

        $this->categoryId = trim($this->categoryId);

        $this->updatedCategoryId($this->categoryId);

        $this->featuredImageId = trim($this->featuredImageId);

        $this->publishedAt = trim($this->publishedAt);

        $this->seoTitle = trim($this->seoTitle);

        $this->seoDescription = trim($this->seoDescription);

        if (NewsLocale::tryFrom($this->locale) === null) {

            $this->locale = NewsLocale::English->value;

        }

        if (NewsEditorMode::tryFrom($this->editorMode) === null) {

            $this->editorMode = NewsEditorMode::Visual->value;

        }

    }

    /**
     * @return list<MediaAsset>
     */
    private function uploadGalleryMedia(User $actor): array
    {

        if ($this->galleryUploads === []) {

            return [];

        }

        Gate::forUser($actor)->authorize(

            'media.upload',

        );

        Gate::forUser($actor)->authorize(

            'news.update',

        );

        /*

         * Validate original filenames and total upload size

         * before creating any MediaAsset records.

         */

        $totalBytes = 0;

        foreach ($this->galleryUploads as $file) {

            $originalName =

                $file->getClientOriginalName();

            /*

             * Reject uppercase English letters anywhere

             * in the original filename.

             *

             * Rejected examples:

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

                throw ValidationException::withMessages([
                    'galleryUploads' => sprintf(

                        'The filename "%s" contains capital letters. Rename it using lowercase letters only and try again.',

                        $originalName,

                    ),

                ]);

            }

            $totalBytes +=

                $file->getSize();

        }

        /*

         * Maximum combined upload size = 30 MB.

         */

        if (

            $totalBytes

            > (30 * 1024 * 1024)

        ) {

            throw ValidationException::withMessages([
                'galleryUploads' => 'The total size of the selected images must not exceed 30 MB.',

            ]);

        }

        /** @var list<MediaAsset> $uploaded */
        $uploaded = [];

        foreach (

            $this->galleryUploads as $index => $file

        ) {

            $extension =

                strtolower(

                    $file->getClientOriginalExtension(),

                );

            /*

             * Image position follows the drag-and-drop

             * order in $galleryUploads.

             *

             * Examples:

             * 2026_10-01image-01.jpg

             * 2026_10-01image-02.png

             * 2026_10-01image-03.webp

             */

            $sequence =

                $index + 1;

            $renamedName =

                sprintf(

                    '%simage-%02d.%s',

                    now()->format('Y_m-d'),

                    $sequence,

                    $extension,

                );

            /*

             * Re-wrap the Livewire temporary upload using

             * the generated client-visible filename.

             *

             * MediaUploadService still stores the physical

             * file using its secure UUID filename.

             */

            $renamedFile =

                new UploadedFile(

                    path: $file->getRealPath(),

                    originalName: $renamedName,

                    mimeType: $file->getMimeType(),

                    error: UPLOAD_ERR_OK,

                    test: true,

                );

            $uploaded[] =

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

        }

        return $uploaded;

    }

    /**
     * @return array{0: News, 1: News|null}
     */
    private function createBilingualPair(
        User $actor,
        NewsCategory $category,
        NewsEditorMode $editorMode,
        bool $publishImmediately,
    ): array {
        if ($publishImmediately) {
            Gate::forUser($actor)->authorize('news.publish');
        }

        /*
         * Upload the physical media only once. The same MediaAsset
         * rows are then attached to every language version that exists.
         */
        $uploadedGalleryMedia = $this->uploadGalleryMedia($actor);
        $featuredImage = $this->featuredImage();
        $publicationDate = $this->publicationDate();

        return DB::transaction(
            function () use (
                $actor,
                $category,
                $editorMode,
                $publishImmediately,
                $uploadedGalleryMedia,
                $featuredImage,
                $publicationDate,
            ): array {
                $articleService = app(NewsArticleService::class);

                $englishNews = $articleService->create(
                    actor: $actor,
                    category: $category,
                    title: $this->title,
                    content: $this->content,
                    summary: $this->nullable($this->summary),
                    slug: $this->nullable($this->slug),
                    featuredImage: $featuredImage,
                    isFeatured: $this->isFeatured,
                    showInGallery: $this->showInGallery,
                    publishedAt: $publicationDate,
                    seoTitle: $this->nullable($this->seoTitle),
                    seoDescription: $this->nullable($this->seoDescription),
                    locale: NewsLocale::English,
                    translationGroup: $this->translationGroup,
                    editorMode: $editorMode,
                );

                $sinhalaNews = null;

                if ($this->hasSinhalaInput()) {
                    $sinhalaNews = $articleService->create(
                        actor: $actor,
                        category: $category,
                        title: $this->sinhalaTitle,
                        content: $this->sinhalaContent,
                        summary: $this->nullable($this->sinhalaSummary),
                        slug: $this->nullable($this->slug),
                        featuredImage: $featuredImage,
                        isFeatured: $this->isFeatured,
                        showInGallery: $this->showInGallery,
                        publishedAt: $publicationDate,
                        seoTitle: null,
                        seoDescription: null,
                        locale: NewsLocale::Sinhala,
                        translationGroup: $this->translationGroup,
                        editorMode: $editorMode,
                        newsType: $this->newsType,
                    );
                }

                $imageService = app(NewsImageService::class);

                foreach ($uploadedGalleryMedia as $media) {
                    $imageService->addImage(
                        news: $englishNews,
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
                }

                if ($publishImmediately) {
                    $workflowService = app(NewsWorkflowService::class);

                    $englishNews = $workflowService->publishImmediately(
                        news: $englishNews,
                        actor: $actor,
                    );

                    if ($sinhalaNews instanceof News) {
                        $sinhalaNews = $workflowService->publishImmediately(
                            news: $sinhalaNews,
                            actor: $actor,
                        );
                    }
                }

                return [
                    $englishNews,
                    $sinhalaNews,
                ];
            },
        );
    }

    private function hasSinhalaInput(): bool
    {
        return trim($this->sinhalaTitle) !== ''
            || trim($this->sinhalaSummary) !== ''
            || trim($this->sinhalaContent) !== '';
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

    private function actor(): User
    {

        $actor = Auth::user();

        abort_unless(

            $actor instanceof User,

            403,

        );

        return $actor;

    }
}
