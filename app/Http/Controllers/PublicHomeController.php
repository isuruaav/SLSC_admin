<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Gallery;
use App\Models\HeroSlide;
use App\Models\News;
use App\Models\SchoolLeader;
use App\Models\SiteSetting;
use App\Models\SpecialProgram;
use App\Services\ThemeManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View as ViewFacade;

final class PublicHomeController
{
    public function __construct(
        private readonly ThemeManager $themeManager,
    ) {}

    public function __invoke(Request $request): View
    {
        $locale = $this->requestLocale($request);

        app()->setLocale($locale);

        $settings = Schema::hasTable('site_settings')
            ? SiteSetting::query()->first()
            : null;

        $slides = Schema::hasTable('hero_slides')
            ? HeroSlide::query()
                ->active()
                ->with([
                    'image.variants',
                    'translations',
                ])
                ->get()
            : collect();

        /*
         |--------------------------------------------------------------------------
         | School Leadership
         |--------------------------------------------------------------------------
         |
         | Homepage rules:
         |
         | 1. Leader must belong to a dynamic appointment position.
         | 2. Leader must be the present holder (end_date = NULL).
         | 3. Leader record must be active.
         | 4. Appointment type must be active.
         | 5. Appointment type must have "Show on homepage" enabled.
         | 6. Appointment position sort_order controls homepage order.
         |
         */
        $schoolLeaders = Schema::hasTable('school_leaders')
            && Schema::hasTable('school_leadership_positions')
                ? SchoolLeader::query()
                    ->join(
                        'school_leadership_positions as positions',
                        'positions.id',
                        '=',
                        'school_leaders.position_id',
                    )
                    ->whereNotNull(
                        'school_leaders.position_id',
                    )
                    ->whereNull(
                        'school_leaders.end_date',
                    )
                    ->where(
                        'school_leaders.is_active',
                        true,
                    )
                    ->where(
                        'positions.is_active',
                        true,
                    )
                    ->where(
                        'positions.show_on_home',
                        true,
                    )
                    ->where(
                        'positions.key',
                        SchoolLeader::ROLE_COMMANDANT,
                    )
                    ->with([
                        'position',
                        'image.variants',
                    ])
                    ->select(
                        'school_leaders.*',
                    )
                    ->orderBy(
                        'positions.sort_order',
                    )
                    ->orderBy(
                        'school_leaders.start_date',
                        'desc',
                    )
                    ->orderByDesc('school_leaders.id')
                    ->limit(1)
                    ->get()
                : collect();

        /*
         |--------------------------------------------------------------------------
         | Homepage News Split: Photo Story (Left) + News Features (Right x 3)
         |--------------------------------------------------------------------------
         */
        $photoStory = News::query()
            ->published()
            ->where('locale', $locale)
            ->photoStories()
            ->with([
                'category',
                'featuredImage.variants',
            ])
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->first();

        $newsFeatures = News::query()
            ->published()
            ->where('locale', $locale)
            ->newsFeatures()
            ->when($photoStory, fn ($q) => $q->where('id', '!=', $photoStory->id))
            ->with([
                'category',
                'featuredImage.variants',
            ])
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit(3)
            ->get();

        $data = [
            'settings' => $settings,

            'slides' => $slides,

            'schoolLeaders' => $schoolLeaders,

            'photoStory' => $photoStory,

            'newsFeatures' => $newsFeatures,

            /*
             |--------------------------------------------------------------------------
             | Latest News (Kept for fallback and backward compatibility)
             |--------------------------------------------------------------------------
             */
            'latestNews' => News::query()
                ->published()
                ->where(
                    'locale',
                    $locale,
                )
                ->with([
                    'category',
                    'featuredImage.variants',
                ])
                ->latest(
                    'published_at',
                )
                ->limit(4)
                ->get(),

            /*
             |--------------------------------------------------------------------------
             | Latest Galleries
             |--------------------------------------------------------------------------
             */
            'latestGalleries' => Gallery::query()
                ->published()
                ->with([
                    'coverMedia.variants',
                ])
                ->latest(
                    'published_at',
                )
                ->limit(3)
                ->get(),

            /*
             |--------------------------------------------------------------------------
             | Latest Documents
             |--------------------------------------------------------------------------
             */
            'latestDocuments' => Document::query()
                ->published()
                ->latest(
                    'published_at',
                )
                ->limit(5)
                ->get(),

            /*
             |--------------------------------------------------------------------------
             | Special Programs Directory
             |--------------------------------------------------------------------------
             */
            'specialPrograms' => Schema::hasTable('special_programs')
                ? SpecialProgram::query()
                    ->active()
                    ->ordered()
                    ->with(['image.variants'])
                    ->get()
                : collect(),

            'languageVersions' => $this->languageVersions(),
        ];

        $themeHomePath = $this->themeHomePath();

        if ($themeHomePath === null) {
            return view(
                'public.home',
                $data,
            );
        }

        return ViewFacade::file(
            $themeHomePath,
            $data,
        );
    }

    private function requestLocale(
        Request $request,
    ): string {
        $routeLocale = $request->route(
            'locale',
        );

        $locale = is_string($routeLocale)
            && trim($routeLocale) !== ''
                ? strtolower(
                    trim($routeLocale),
                )
                : 'en';

        abort_unless(
            in_array(
                $locale,
                $this->supportedLocales(),
                true,
            ),
            404,
        );

        return $locale;
    }

    /**
     * @return list<array{
     *     code: string,
     *     available: true,
     *     url: string
     * }>
     */
    private function languageVersions(): array
    {
        $versions = [];

        foreach (
            $this->supportedLocales() as $locale
        ) {
            $versions[] = [
                'code' => $locale,
                'available' => true,

                'url' => $locale === 'en'
                    ? route('home')
                    : route(
                        'home.localized',
                        [
                            'locale' => $locale,
                        ],
                    ),
            ];
        }

        return $versions;
    }

    /**
     * @return list<string>
     */
    private function supportedLocales(): array
    {
        return app(\App\Services\SiteLanguageService::class)->codes();
    }

    private function themeHomePath(): ?string
    {
        $activeTheme = config(
            'awcms.active_theme',
        );

        if (
            ! is_string($activeTheme)
            || trim($activeTheme) === ''
        ) {
            return null;
        }

        $activeTheme = trim(
            $activeTheme,
        );

        $viewsPath = $this->themeManager
            ->viewsPath(
                $activeTheme,
            );

        if ($viewsPath === null) {
            return null;
        }

        $homePath = $viewsPath
            .DIRECTORY_SEPARATOR
            .'home.blade.php';

        return is_file($homePath)
            ? $homePath
            : null;
    }
}
