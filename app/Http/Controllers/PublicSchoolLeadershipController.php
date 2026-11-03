<?php

namespace App\Http\Controllers;

use App\Models\SchoolLeader;
use App\Services\LeadershipProfileContent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class PublicSchoolLeadershipController
{
    public function index(Request $request): View
    {
        $locale = $this->locale($request);
        app()->setLocale($locale);

        $leaders = Schema::hasTable('school_leaders')
            && Schema::hasTable('school_leadership_positions')
                ? SchoolLeader::query()
                    ->join(
                        'school_leadership_positions as positions',
                        'positions.id',
                        '=',
                        'school_leaders.position_id',
                    )
                    ->whereNotNull('school_leaders.position_id')
                    ->whereNull('school_leaders.end_date')
                    ->where('school_leaders.is_active', true)
                    ->where('positions.is_active', true)
                    ->where(
                        'positions.key',
                        '!=',
                        SchoolLeader::ROLE_COMMANDANT,
                    )
                    ->with([
                        'position',
                        'image.variants',
                    ])
                    ->select('school_leaders.*')
                    ->orderBy('positions.sort_order')
                    ->orderBy('school_leaders.sort_order')
                    ->orderBy('school_leaders.id')
                    ->get()
                : collect();

        return view(
            $this->themeNamespace().'::school-leadership',
            [
                'leaders' => $leaders,
                'currentLocale' => $locale,
                'languageVersions' => $this->languageVersions(),
                'pageTitle' => $locale === 'si'
                    ? 'පාසලේ නායකත්වය'
                    : 'School Leadership',
                'metaDescription' => $locale === 'si'
                    ? 'සංඥා පාසලේ වත්මන් නායකත්වය සහ පත්වීම්.'
                    : 'Meet the current leadership team of the School of Signals.',
            ],
        );
    }

    public function commandant(Request $request): View
    {
        return $this->profile($request);
    }

    public function history(Request $request): View
    {
        $locale = $this->locale($request);
        app()->setLocale($locale);

        $leaders = Schema::hasTable('school_leaders') && Schema::hasTable('school_leadership_positions')
            ? SchoolLeader::query()
                ->whereNotNull('end_date')
                ->where('is_active', true)
                ->whereHas('position', static fn (Builder $query) => $query->where('is_active', true))
                ->with(['position', 'image.variants'])
                ->orderByDesc('end_date')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view($this->themeNamespace().'::school-leadership', [
            'leaders' => $leaders,
            'isHistory' => true,
            'currentLocale' => $locale,
            'languageVersions' => $this->languageVersions('school-leadership.history'),
            'pageTitle' => __($this->themeNamespace().'::home.leadership.past_leadership'),
            'metaDescription' => __($this->themeNamespace().'::home.leadership.past_description'),
        ]);
    }

    public function show(Request $request): View
    {
        $locale = $this->locale($request);
        app()->setLocale($locale);
        abort_unless(in_array($locale, ['en', 'si'], true), 404);
        abort_unless(Schema::hasTable('school_leaders') && Schema::hasTable('school_leadership_positions'), 404);

        $leaderId = $request->route('leader');
        abort_unless(is_string($leaderId) && ctype_digit($leaderId), 404);
        $leader = SchoolLeader::query()
            ->with(['position', 'image.variants'])
            ->findOrFail($leaderId);

        return $this->profileView($leader, $locale);
    }

    public function profile(Request $request): View
    {
        $locale = $this->locale($request);
        app()->setLocale($locale);
        $appointment = $request->route('appointment', 'commandant');
        abort_unless(is_string($appointment), 404);
        $role = str_replace('-', '_', $appointment);
        abort_unless(in_array($locale, ['en', 'si'], true), 404);

        abort_unless(
            Schema::hasTable('school_leaders') && Schema::hasTable('school_leadership_positions'),
            404,
        );

        $leader = SchoolLeader::query()
            ->whereHas('position', static fn (Builder $query) => $query
                ->where('key', $role)
                ->where('is_active', true))
            ->where('is_active', true)
            ->current()
            ->with(['position', 'image.variants'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->firstOrFail();

        return $this->profileView($leader, $locale);
    }

    private function profileView(SchoolLeader $leader, string $locale): View
    {
        abort_unless($leader->hasPublicProfile(), 404);
        $isCommandant = $leader->position->key === SchoolLeader::ROLE_COMMANDANT;

        return view($isCommandant ? $this->themeNamespace().'::commandant' : $this->themeNamespace().'::leader-profile', [
            'commandant' => $leader,
            'leader' => $leader,
            'profileDocument' => app(LeadershipProfileContent::class)->forLeader($leader, $locale),
            'currentLocale' => $locale,
            'languageVersions' => $this->languageVersions('school-leadership.show', ['leader' => (string) $leader->id]),
            'pageTitle' => $leader->appointmentLabelForLocale($locale),
            'metaDescription' => $leader->appointmentLabelForLocale($locale)
                .' — '.$leader->nameForLocale($locale),
        ]);
    }

    private function locale(Request $request): string
    {
        $routeLocale = $request->route('locale');
        $locale = is_string($routeLocale) && trim($routeLocale) !== ''
            ? strtolower(trim($routeLocale))
            : 'en';

        $allowedLocales = app(\App\Services\SiteLanguageService::class)->codes();

        abort_unless(in_array($locale, $allowedLocales, true), 404);

        return $locale;
    }

    /**
     * @param  array<string, string>  $parameters
     * @return list<array{code: string, available: true, url: string}>
     */
    private function languageVersions(string $route = 'school-leadership', array $parameters = []): array
    {
        $locales = app(\App\Services\SiteLanguageService::class)->codes();

        $versions = [];

        foreach (array_unique($locales) as $locale) {
            if ($route !== 'school-leadership' && ! in_array($locale, ['en', 'si'], true)) {
                continue;
            }
            $versions[] = [
                'code' => $locale,
                'available' => true,
                'url' => $locale === 'en'
                    ? route($route === 'school-leadership' ? 'school-leadership.index' : $route, $parameters)
                    : route($route.'.localized', ['locale' => $locale] + $parameters),
            ];
        }

        return $versions;
    }

    private function themeNamespace(): string
    {
        $theme = config('awcms.active_theme');

        return ($theme && view()->exists("theme-{$theme}::school-leadership"))
            ? "theme-{$theme}"
            : 'theme-school-of-signals';
    }
}
