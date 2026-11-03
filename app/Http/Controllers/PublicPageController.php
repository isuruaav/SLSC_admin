<?php

namespace App\Http\Controllers;

use App\Enums\PageEditorMode;
use App\Enums\PageLocale;
use App\Enums\PageStatus;
use App\Models\Page;
use App\Services\PageBlockRenderer;
use App\Services\PageHtmlSanitizer;
use App\Services\ThemeViewResolver;
use App\Support\PageSeo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

final class PublicPageController extends Controller
{
    public function __construct(
        private readonly ThemeViewResolver $themeViewResolver,
    ) {}

    public function __invoke(Request $request): View
    {
        $routeSlug = $request->route('slug');

        abort_unless(
            is_string($routeSlug) && trim($routeSlug) !== '',
            404,
        );

        $pageLocale = $this->routeLocale($request);

        $page = Page::query()
            ->where(
                'slug',
                $routeSlug,
            )
            ->where(
                'locale',
                $pageLocale->value,
            )
            ->where(
                'status',
                PageStatus::Published->value,
            )
            ->whereNotNull(
                'published_at',
            )
            ->where(
                'published_at',
                '<=',
                now(),
            )
            ->firstOrFail();

        app()->setLocale(
            $pageLocale->value,
        );

        $content = $page->getAttribute('content');

        $rawContent = is_string($content)
            ? $content
            : null;

        $rawEditorMode = $page->getRawOriginal('editor_mode');

        $editorMode = is_string($rawEditorMode)
            ? PageEditorMode::tryFrom($rawEditorMode)
            : null;

        $editorMode ??= PageEditorMode::Html;

        $htmlSanitizer = app(
            PageHtmlSanitizer::class,
        );

        $safeContent = $editorMode === PageEditorMode::Visual
            ? $htmlSanitizer->sanitizeVisual(
                $rawContent,
            )
            : $htmlSanitizer->sanitize(
                $rawContent,
            );

        $pageBlocks = app(
            PageBlockRenderer::class,
        )->forPage(
            $page,
        );

        $seoTitle = PageSeo::title(
            $page,
        );

        $metaDescription =
            PageSeo::description(
                $page,
            );

        $canonicalUrl =
            PageSeo::canonicalUrl(
                $page,
            );

        $robots = PageSeo::robots(
            $page,
        );

        $ogTitle =
            PageSeo::openGraphTitle(
                $page,
            );

        $ogDescription =
            PageSeo::openGraphDescription(
                $page,
            );

        $ogImage =
            PageSeo::openGraphImage(
                $page,
            );

        $translationGroup = $page->getAttribute(
            'translation_group',
        );

        $publishedTranslations = Page::query()
            ->whereRaw('1 = 0')
            ->get();

        if (
            is_string($translationGroup)
            && trim($translationGroup) !== ''
        ) {
            $publishedTranslations = Page::query()
                ->published()
                ->where(
                    'translation_group',
                    $translationGroup,
                )
                ->get();
        }

        $languageVersions = array_map(
            static function (PageLocale $localeOption) use (
                $page,
                $publishedTranslations,
            ): array {
                $translation = $publishedTranslations->first(
                    static fn (Page $candidate): bool => $candidate->getRawOriginal('locale') === $localeOption->value,
                );

                $activeLocale = $page->getRawOriginal('locale');

                return [
                    'code' => $localeOption->value,
                    'label' => $localeOption->label(),
                    'native_label' => $localeOption->nativeLabel(),
                    'active' => $activeLocale === $localeOption->value,
                    'available' => $translation instanceof Page,
                    'url' => $translation instanceof Page
    ? route(
        'pages.show.localized',
        [
            'locale' => $localeOption->value,
            'slug' => $translation->slug,
        ],
    )
    : null,
                ];
            },
            app(\App\Services\SiteLanguageService::class)->choices(PageLocale::cases()),
        );

        return $this->pageView(
            [
                'page' => $page,

                'safeContent' => $safeContent,

                'pageBlocks' => $pageBlocks,

                'languageVersions' => $languageVersions,

                /*
                 * Browser / Search Engine
                 */
                'pageTitle' => $seoTitle,

                'metaDescription' => $metaDescription,

                'canonicalUrl' => $canonicalUrl,

                'robots' => $robots,

                /*
                 * Open Graph
                 */
                'ogType' => 'website',

                'ogTitle' => $ogTitle,

                'ogDescription' => $ogDescription,

                'ogUrl' => $canonicalUrl,

                'ogImage' => $ogImage,

                /*
                 * Twitter / X
                 */
                'twitterCard' => $ogImage !== null
                        ? 'summary_large_image'
                        : 'summary',

                'twitterTitle' => $ogTitle,

                'twitterDescription' => $ogDescription,

                'twitterImage' => $ogImage,

                'socialMetadata' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pageView(array $data): View
    {
        $themeViewPath = $this->themeViewResolver->resolve(
            'pages.show',
        );

        if ($themeViewPath === null) {
            return view('pages.show', $data);
        }

        return ViewFacade::file(
            $themeViewPath,
            $data,
        );
    }

    private function routeLocale(Request $request): PageLocale
    {
        $routeLocale = $request->route('locale');

        if (! is_string($routeLocale) || trim($routeLocale) === '') {
            return PageLocale::English;
        }

        $locale = PageLocale::tryFrom($routeLocale);

        abort_unless(
            $locale instanceof PageLocale,
            404,
        );

        return $locale;
    }
}
