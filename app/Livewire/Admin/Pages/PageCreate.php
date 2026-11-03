<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageEditorMode;
use App\Enums\PageLocale;
use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use App\Rules\ValidPageCss;
use App\Services\AuditLogger;
use App\Services\ContentSanitizer;
use App\Services\PageBlockSanitizer;
use App\Services\PageHtmlSanitizer;
use App\Services\PageRevisionService;
use App\Support\PageBlockFactory;
use App\Support\PageSlugger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class PageCreate extends Component
{
    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $content = '';

    public string $customCss = '';

    public string $locale = PageLocale::English->value;

    #[Locked]
    public string $translationGroup = '';

    #[Locked]
    public ?int $translationSourcePageId = null;

    #[Locked]
    public string $translationSourceTitle = '';

    public string $editorMode = PageEditorMode::Visual->value;

    public bool $showTitle = true;

    /**
     * @var list<array<string, mixed>>
     */
    public array $blocks = [];

    public string $seoTitle = '';

    public string $metaDescription = '';

    public string $canonicalUrl = '';

    public bool $robotsIndex = true;

    public string $ogTitle = '';

    public string $ogDescription = '';

    public string $ogImage = '';

    public bool $slugManuallyEdited = false;

    public function mount(
        ?int $pageId = null,
        ?string $locale = null,
    ): void {
        Gate::authorize(
            'pages.create',
        );

        if ($pageId === null) {
            $this->translationGroup = (string) Str::uuid();

            return;
        }

        $page = Page::query()->findOrFail(
            $pageId,
        );

        $targetLocale = is_string($locale)
            ? PageLocale::tryFrom($locale)
            : null;

        abort_unless(
            $targetLocale instanceof PageLocale && app(\App\Services\SiteLanguageService::class)->enabled($targetLocale->value),
            404,
        );

        $rawSourceLocale = $page->getRawOriginal('locale');

        $sourceLocale = is_string($rawSourceLocale)
            ? PageLocale::tryFrom($rawSourceLocale)
            : null;

        $sourceLocale ??= PageLocale::English;

        abort_if(
            $sourceLocale === $targetLocale,
            409,
            'That language version already exists.',
        );

        $translationGroup = $page->getAttribute(
            'translation_group',
        );

        abort_unless(
            is_string($translationGroup)
                && trim($translationGroup) !== '',
            409,
            'The source page does not have a translation group.',
        );

        $existing = Page::withTrashed()
            ->where(
                'translation_group',
                $translationGroup,
            )
            ->where(
                'locale',
                $targetLocale->value,
            )
            ->exists();

        abort_if(
            $existing,
            409,
            'That language version already exists.',
        );

        $this->locale = $targetLocale->value;
        $this->translationGroup = $translationGroup;
        $this->translationSourcePageId = (int) $page->id;
        $this->translationSourceTitle = (string) $page->title;
        $this->customCss = is_string($page->custom_css) ? $page->custom_css : '';

        /*
         * Translation content is intentionally not copied or translated.
         * The operator must enter the approved wording manually.
         */
        $this->title = '';
        $this->slug = '';
        $this->excerpt = '';
        $this->content = '';
        $this->editorMode = PageEditorMode::Visual->value;
        $this->showTitle = true;
    }

    public function updatedTitle(): void
    {
        if ($this->slugManuallyEdited) {
            return;
        }

        $this->slug = Str::slug(
            $this->title,
        );
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;

        $this->slug = Str::slug(
            $this->slug,
        );
    }

    public function updatedLocale(): void
    {
        if ($this->translationSourcePageId !== null) {
            return;
        }

        if (PageLocale::tryFrom($this->locale) === null) {
            $this->locale = PageLocale::English->value;
        }

        $this->resetValidation(
            'locale',
        );
    }

    public function regenerateSlug(): void
    {
        $this->slugManuallyEdited = false;

        $this->slug = Str::slug(
            $this->title,
        );

        $this->resetValidation(
            'slug',
        );
    }

    public function setEditorMode(string $mode): void
    {
        Gate::authorize(
            'pages.create',
        );

        $editorMode = PageEditorMode::tryFrom(
            $mode,
        );

        if (! $editorMode instanceof PageEditorMode) {
            $this->addError(
                'editorMode',
                'The selected editor mode is invalid.',
            );

            return;
        }

        if ($this->editorMode === $editorMode->value) {
            return;
        }

        /*
         * Both editors use the same canonical HTML content.
         * The browser UI warns before advanced Tailwind markup is opened
         * visually, but the server does not duplicate or rewrite content
         * simply because the preferred editor changes.
         */
        $this->editorMode = $editorMode->value;

        $this->resetValidation(
            'editorMode',
        );
    }

    public function addBlock(
        string $type,
    ): void {
        Gate::authorize(
            'pages.create',
        );

        if (
            count($this->blocks)
            >= PageBlockSanitizer::MAX_BLOCKS
        ) {
            throw ValidationException::withMessages([
                'blocks' => sprintf(
                    'A page may contain a maximum of %d blocks.',
                    PageBlockSanitizer::MAX_BLOCKS,
                ),
            ]);
        }

        $this->blocks[] = PageBlockFactory::make(
            $type,
        );

        $this->resetValidation(
            'blocks',
        );
    }

    public function removeBlock(
        int $index,
    ): void {
        Gate::authorize(
            'pages.create',
        );

        if (
            $index < 0
            || $index >= count($this->blocks)
        ) {
            return;
        }

        array_splice(
            $this->blocks,
            $index,
            1,
        );

        $this->resetValidation(
            'blocks',
        );
    }

    public function moveBlockUp(
        int $index,
    ): void {
        Gate::authorize(
            'pages.create',
        );

        if (
            $index <= 0
            || ! array_key_exists(
                $index,
                $this->blocks,
            )
        ) {
            return;
        }

        $this->swapBlocks(
            $index,
            $index - 1,
        );
    }

    public function moveBlockDown(
        int $index,
    ): void {
        Gate::authorize(
            'pages.create',
        );

        if (
            $index < 0
            || $index >= count($this->blocks) - 1
        ) {
            return;
        }

        $this->swapBlocks(
            $index,
            $index + 1,
        );
    }

    public function save(): void
    {
        Gate::authorize(
            'pages.create',
        );

        $actor = $this->actor();

        $this->normaliseInput();
        $this->validate();

        $slugSource = $this->slug !== ''
            ? $this->slug
            : $this->title;

        $uniqueSlug = PageSlugger::unique(
            $slugSource,
            locale: $this->locale,
        );

        $page = DB::transaction(
            function () use (
                $actor,
                $uniqueSlug,
            ): Page {
                $page = Page::query()->create([
                    'title' => $this->title,

                    'slug' => $uniqueSlug,

                    'locale' => $this->locale,

                    'translation_group' => $this->translationGroup,

                    'excerpt' => $this->excerpt !== ''
                        ? $this->excerpt
                        : null,

                    'content' => $this->content !== ''
                        ? $this->content
                        : null,

                    'editor_mode' => $this->editorMode,

                    'custom_css' => trim($this->customCss) !== '' ? trim($this->customCss) : null,

                    'show_title' => $this->showTitle,

                    'blocks' => $this->blocks !== []
                        ? $this->blocks
                        : null,

                    /*
                     * SEO
                     */
                    'seo_title' => $this->seoTitle !== ''
                        ? $this->seoTitle
                        : null,

                    'meta_description' => $this->metaDescription !== ''
                        ? $this->metaDescription
                        : null,

                    'canonical_url' => $this->canonicalUrl !== ''
                        ? $this->canonicalUrl
                        : null,

                    'robots_index' => $this->robotsIndex,

                    'og_title' => $this->ogTitle !== ''
                        ? $this->ogTitle
                        : null,

                    'og_description' => $this->ogDescription !== ''
                        ? $this->ogDescription
                        : null,

                    'og_image' => $this->ogImage !== ''
                        ? $this->ogImage
                        : null,

                    /*
                     * Workflow
                     */
                    'status' => PageStatus::Draft->value,

                    'created_by' => $actor->id,

                    'updated_by' => $actor->id,
                ]);

                app(AuditLogger::class)->log(
                    event: 'pages.created',
                    description: 'Draft website page created.',
                    actor: $actor,
                    subject: $page,
                    oldValues: [],
                    newValues: [
                        'title' => $page->title,

                        'slug' => $page->slug,

                        'locale' => $this->locale,

                        'translation_group' => $this->translationGroup,

                        'translation_source_page_id' => $this->translationSourcePageId,

                        'status' => PageStatus::Draft->value,

                        'excerpt_present' => $page->excerpt !== null,

                        'content_present' => $page->content !== null,

                        'custom_css_present' => $page->custom_css !== null,

                        'editor_mode' => $this->editorMode,

                        'show_title' => $this->showTitle,

                        'blocks_count' => count($this->blocks),

                        'seo_title_present' => $page->seo_title !== null,

                        'meta_description_present' => $page->meta_description
                            !== null,

                        'canonical_url_present' => $page->canonical_url !== null,

                        'robots_index' => (bool) $page->robots_index,

                        'og_title_present' => $page->og_title !== null,

                        'og_description_present' => $page->og_description !== null,

                        'og_image_present' => $page->og_image !== null,
                    ],
                );

                app(PageRevisionService::class)
                    ->capture(
                        page: $page,
                        actor: $actor,
                        summary: 'Initial draft created.',
                    );

                return $page;
            },
        );

        session()->flash(
            'status',
            "{$page->title} was created successfully.",
        );

        $this->redirectRoute(
            'admin.pages.index',
            navigate: true,
        );
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'locale' => [
                'required',
                'string',
                'in:'.implode(',', app(\App\Services\SiteLanguageService::class)->codes()),
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],

            'excerpt' => [
                'nullable',
                'string',
                'max:500',
            ],

            'content' => [
                'nullable',
                'string',
                'max:100000',
            ],

            'customCss' => ['nullable', 'string', 'max:50000', new ValidPageCss],

            'editorMode' => [
                'required',
                'string',
                'in:visual,html',
            ],

            'showTitle' => [
                'boolean',
            ],

            'blocks' => [
                'array',
                'max:100',
            ],

            'seoTitle' => [
                'nullable',
                'string',
                'max:70',
            ],

            'metaDescription' => [
                'nullable',
                'string',
                'max:160',
            ],

            'canonicalUrl' => [
                'nullable',
                'string',
                'max:2048',
                'url:http,https',
            ],

            'robotsIndex' => [
                'boolean',
            ],

            'ogTitle' => [
                'nullable',
                'string',
                'max:95',
            ],

            'ogDescription' => [
                'nullable',
                'string',
                'max:200',
            ],

            'ogImage' => [
                'nullable',
                'string',
                'max:2048',
                'url:http,https',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'slug.regex' => 'The slug may contain lowercase letters, numbers and hyphens only.',

            'canonicalUrl.url' => 'The canonical URL must be a valid HTTP or HTTPS URL.',

            'ogImage.url' => 'The Open Graph image must be a valid HTTP or HTTPS URL.',
        ];
    }

    public function render(): View
    {
        $locales = app(\App\Services\SiteLanguageService::class)->choices(PageLocale::cases());

        return view(
            'livewire.admin.pages.page-create',
            compact('locales'),
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Create Page',
            ],
        );
    }

    private function normaliseInput(): void
    {
        $sanitizer = app(
            ContentSanitizer::class,
        );

        $this->title = trim(
            $this->title,
        );

        $this->locale = match ($this->locale) {
            PageLocale::Sinhala->value => PageLocale::Sinhala->value,
            PageLocale::Tamil->value => PageLocale::Tamil->value,
            default => PageLocale::English->value,
        };

        if ($this->translationGroup === '') {
            $this->translationGroup = (string) Str::uuid();
        }

        $this->slug = Str::slug(
            trim($this->slug),
        );

        $this->excerpt = $sanitizer->plainText(
            $this->excerpt,
            500,
        );

        $pageHtmlSanitizer = app(
            PageHtmlSanitizer::class,
        );

        $this->content = $this->editorMode === PageEditorMode::Visual->value
            ? $pageHtmlSanitizer->sanitizeVisual(
                $this->content,
            )
            : $pageHtmlSanitizer->sanitize(
                $this->content,
            );

        $this->blocks = app(
            PageBlockSanitizer::class,
        )->normalize(
            $this->blocks,
        );

        $this->seoTitle = $sanitizer->plainText(
            $this->seoTitle,
            70,
        );

        $this->metaDescription =
            $sanitizer->plainText(
                $this->metaDescription,
                160,
            );

        $this->canonicalUrl = trim(
            $this->canonicalUrl,
        );

        $this->ogTitle = $sanitizer->plainText(
            $this->ogTitle,
            95,
        );

        $this->ogDescription =
            $sanitizer->plainText(
                $this->ogDescription,
                200,
            );

        $this->ogImage = trim(
            $this->ogImage,
        );
    }

    private function swapBlocks(
        int $firstIndex,
        int $secondIndex,
    ): void {
        $temporary =
            $this->blocks[$firstIndex];

        $this->blocks[$firstIndex] =
            $this->blocks[$secondIndex];

        $this->blocks[$secondIndex] =
            $temporary;
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
