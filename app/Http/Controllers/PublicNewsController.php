<?php

namespace App\Http\Controllers;

use App\Enums\NewsEditorMode;
use App\Enums\NewsLocale;
use App\Models\MediaAsset;
use App\Models\News;
use App\Services\MediaUrlService;
use App\Services\PageHtmlSanitizer;
use App\Services\SiteLanguageService;
use App\Services\SiteUrlService;
use App\Services\ThemeViewResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

final class PublicNewsController extends Controller
{
    public function __construct(
        private readonly ThemeViewResolver $themeViewResolver,
    ) {}

    public function index(Request $request): View
    {
        $locale = $this->routeLocale($request);

        app()->setLocale($locale->value);

        $currentType = match ($request->query('type')) {
            'photo-story', 'photo_story' => 'photo-story',
            'news-feature', 'news_feature' => 'news-feature',
            default => null,
        };

        $query = News::query()
            ->with([
                'category',
                'featuredImage.variants',
            ])
            ->published()
            ->where('locale', $locale->value);

        if ($currentType === 'photo-story') {
            $query->photoStories();
        } elseif ($currentType === 'news-feature') {
            $query->newsFeatures();
        }

        $news = $query
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return $this->newsView(
            'index',
            [
                'news' => $news,
                'locale' => $locale,
                'locales' => app(SiteLanguageService::class)->choices(NewsLocale::cases()),
                'languageVersions' => $this->indexLanguageVersions($currentType),
                'currentType' => $currentType,
            ],
        );
    }

    public function show(Request $request): View
    {
        $locale = $this->routeLocale($request);
        $slug = $request->route('slug');

        abort_unless(
            is_string($slug) && trim($slug) !== '',
            404,
        );

        app()->setLocale($locale->value);

        $news = News::query()
            ->with([
                'category',
                'featuredImage.variants',
                'images.media.variants',
            ])
            ->published()
            ->where('locale', $locale->value)
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedNews = News::query()
            ->with([
                'category',
                'featuredImage.variants',
            ])
            ->published()
            ->where('locale', $locale->value)
            ->whereKeyNot($news->id)
            ->when(
                $news->category_id !== null,
                fn ($query) => $query->where(
                    'category_id',
                    $news->category_id,
                ),
            )
            ->orderByDesc('published_at')
            ->limit(12)
            ->get();

        $translationGroup = $news->getAttribute('translation_group');

        $publishedTranslations = News::query()
            ->whereRaw('1 = 0')
            ->get();

        if (is_string($translationGroup) && trim($translationGroup) !== '') {
            $publishedTranslations = News::query()
                ->published()
                ->where('translation_group', $translationGroup)
                ->get();
        }

        $languageVersions = array_map(
            static function (NewsLocale $localeOption) use (
                $news,
                $publishedTranslations,
            ): array {
                $translation = $publishedTranslations->first(
                    static fn (News $candidate): bool => $candidate->getRawOriginal('locale') === $localeOption->value,
                );

                $activeLocale = $news->getRawOriginal('locale');

                return [
                    'code' => $localeOption->value,
                    'label' => $localeOption->label(),
                    'native_label' => $localeOption->nativeLabel(),
                    'active' => $activeLocale === $localeOption->value,
                    'available' => $translation instanceof News,
                    'url' => $translation instanceof News
    ? route(
        'news.show.localized',
        [
            'locale' => $localeOption->value,
            'slug' => $translation->slug,
        ],
    )
    : null,
                ];
            },
            app(SiteLanguageService::class)->choices(NewsLocale::cases()),
        );

        $rawEditorMode = $news->getRawOriginal('editor_mode');

        $editorMode = is_string($rawEditorMode)
            ? NewsEditorMode::tryFrom($rawEditorMode)
            : null;

        $editorMode ??= NewsEditorMode::Visual;

        $content = $news->getAttribute('content');

        $rawContent = is_string($content)
            ? $content
            : null;

        $sanitizer = app(PageHtmlSanitizer::class);

        $safeContent = $editorMode === NewsEditorMode::Visual
            ? $sanitizer->sanitizeVisual($rawContent)
            : $sanitizer->sanitize($rawContent);
        $safeContent = app(SiteUrlService::class)->resolveHtml($safeContent);

        return $this->newsView(
            'show',
            [
                'news' => $news,
                'locale' => $locale,
                'languageVersions' => $languageVersions,
                'relatedNews' => $relatedNews,
                'safeContent' => $safeContent,
                'featuredImageUrl' => self::imageUrl(
                    $news->featuredImage,
                ),
            ],
        );
    }

    public static function imageUrl(?MediaAsset $media): ?string
    {
        return $media instanceof MediaAsset
            ? app(MediaUrlService::class)->mediumOrOriginal($media)
            : null;
    }

    private function routeLocale(Request $request): NewsLocale
    {
        $routeLocale = $request->route('locale');

        if (! is_string($routeLocale) || trim($routeLocale) === '') {
            return NewsLocale::English;
        }

        $locale = NewsLocale::tryFrom($routeLocale);

        abort_unless(
            $locale instanceof NewsLocale,
            404,
        );

        return $locale;
    }

    /**
     * @return list<array{code: string, available: true, url: string}>
     */
    private function indexLanguageVersions(?string $currentType): array
    {
        return array_map(
            static fn (NewsLocale $locale): array => [
                'code' => $locale->value,
                'available' => true,
                'url' => $locale === NewsLocale::English
                    ? route('news.index', $currentType === null ? [] : ['type' => $currentType])
                    : route('news.index.localized', array_filter(['locale' => $locale->value, 'type' => $currentType])),
            ],
            app(SiteLanguageService::class)->choices(NewsLocale::cases()),
        );
    }

    /**
     * @param  'index'|'show'  $view
     * @param  array<string, mixed>  $data
     */
    private function newsView(string $view, array $data): View
    {
        $themeViewPath = $this->themeViewResolver->resolve(
            'news.'.$view,
        );

        if ($themeViewPath !== null) {
            return ViewFacade::file(
                $themeViewPath,
                $data,
            );
        }

        $fallbackView = match ($view) {
            'index' => 'public.news.index',
            'show' => 'public.news.show',
        };

        return view(
            $fallbackView,
            $data,
        );
    }
}
