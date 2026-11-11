<?php

namespace App\Http\Controllers;

use App\Models\SchoolLeader;
use App\Models\SchoolLeadershipPosition;
use App\Services\HistoricalYearFilter;
use App\Services\SiteLanguageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class PublicPastAppointmentsController
{
    /**
     * The 8 primary appointments for past appointment history in display order.
     *
     * @var array<string, array{slug: string, title_en: string, title_si: string, short_en: string, short_si: string, badge: string}>
     */
    public const APPOINTMENTS = [
        'colonel_commandant' => [
            'slug' => 'regt-comdt-col-comdt',
            'title_en' => 'Regtl Comds & Col Comdts',
            'title_si' => 'රෙජිමේන්තු අණදෙන නිලධාරීන් හා බලකාධිපතිවරුන්',
            'short_en' => 'Regtl Comds & Col Comdts',
            'short_si' => 'බලකාධිපතිවරුන්',
            'badge' => '01',
        ],
        'chief_signal_officer' => [
            'slug' => 'chief-signal-officers',
            'title_en' => 'Chief Signal Officers',
            'title_si' => 'ප්‍රධාන සංඥා නිලධාරීන්',
            'short_en' => 'Chief Signal Officers',
            'short_si' => 'ප්‍රධාන සංඥා නිලධාරීන්',
            'badge' => '02',
        ],
        'director_it' => [
            'slug' => 'directors-of-it',
            'title_en' => 'Directors of IT',
            'title_si' => 'තොරතුරු තාක්ෂණ අධ්‍යක්ෂකවරුන්',
            'short_en' => 'Directors of IT',
            'short_si' => 'තොරතුරු තාක්ෂණ අධ්‍යක්ෂක',
            'badge' => '03',
        ],
        'commander_1_signal_brigade' => [
            'slug' => '1-bde-comds',
            'title_en' => '1 Bde Comds',
            'title_si' => '1 වන සංඥා බලසේනාධිපතිවරුන්',
            'short_en' => '1 Bde Comds',
            'short_si' => '1 බලසේනාධිපති',
            'badge' => '04',
        ],
        'commander_2_signal_brigade' => [
            'slug' => '2-bde-comds',
            'title_en' => '2 Bde Comds',
            'title_si' => '2 වන සංඥා බලසේනාධිපතිවරුන්',
            'short_en' => '2 Bde Comds',
            'short_si' => '2 බලසේනාධිපති',
            'badge' => '05',
        ],
        'regimental_centre_commandant' => [
            'slug' => 'centre-commandants',
            'title_en' => 'Cen Comdts',
            'title_si' => 'රෙජිමේන්තු මධ්‍යස්ථාන සේනා විධායකවරුන්',
            'short_en' => 'Cen Comdts',
            'short_si' => 'මධ්‍යස්ථාන සේනා විධායක',
            'badge' => '06',
        ],
        'regimental_adjutant' => [
            'slug' => 'regimental-adjutants',
            'title_en' => 'Regimantal Adjutants',
            'title_si' => 'රෙජිමේන්තු විධායක අධිකාරිවරුන්',
            'short_en' => 'Regimantal Adjutants',
            'short_si' => 'රෙජිමේන්තු විධායක අධිකාරි',
            'badge' => '07',
        ],
        'regimental_sergeant_major' => [
            'slug' => 'regimental-sergeant-major',
            'title_en' => 'Regimental Sergent Major',
            'title_si' => 'රෙජිමේන්තු සැරයන් මේජර්වරුන්',
            'short_en' => 'Regimental Sergent Major',
            'short_si' => 'රෙජිමේන්තු සැරයන් මේජර්',
            'badge' => '08',
        ],
    ];

    public function index(Request $request): View
    {
        $requestedTab = $request->query('tab');
        $activeTab = is_string($requestedTab) && array_key_exists($requestedTab, self::APPOINTMENTS)
            ? $requestedTab
            : 'colonel_commandant';

        return $this->renderAppointmentView($request, $activeTab);
    }

    public function colonelCommandant(Request $request): View
    {
        return $this->renderAppointmentView($request, 'colonel_commandant');
    }

    public function chiefSignalOfficer(Request $request): View
    {
        return $this->renderAppointmentView($request, 'chief_signal_officer');
    }

    public function directorIt(Request $request): View
    {
        return $this->renderAppointmentView($request, 'director_it');
    }

    public function commander1SignalBrigade(Request $request): View
    {
        return $this->renderAppointmentView($request, 'commander_1_signal_brigade');
    }

    public function commander2SignalBrigade(Request $request): View
    {
        return $this->renderAppointmentView($request, 'commander_2_signal_brigade');
    }

    public function regimentalCentreCommandant(Request $request): View
    {
        return $this->renderAppointmentView($request, 'regimental_centre_commandant');
    }

    public function regimentalAdjutant(Request $request): View
    {
        return $this->renderAppointmentView($request, 'regimental_adjutant');
    }

    public function regimentalSergeantMajor(Request $request): View
    {
        return $this->renderAppointmentView($request, 'regimental_sergeant_major');
    }

    private function renderAppointmentView(Request $request, string $activeKey): View
    {
        $locale = $this->locale($request);
        app()->setLocale($locale);

        $appointmentMeta = self::APPOINTMENTS[$activeKey] ?? self::APPOINTMENTS['colonel_commandant'];

        // Get position record if exists
        $position = Schema::hasTable('school_leadership_positions')
            ? SchoolLeadershipPosition::query()->where('key', $activeKey)->first()
            : null;

        // Fetch ONLY leaders for this specific appointment
        $leaders = Schema::hasTable('school_leaders')
            ? SchoolLeader::query()
                ->where('is_active', true)
                ->where(function ($query) use ($position, $activeKey) {
                    if ($position) {
                        $query->where('position_id', $position->id);
                    }
                    $query->orWhere('role_key', $activeKey);
                })
                ->with(['position', 'image.variants'])
                ->orderBy('sort_order')
                ->orderBy('start_date')
                ->orderBy('id')
                ->get()
            : (new SchoolLeader)->newCollection();

        $filters = $request->validate(['year' => ['nullable', 'integer', 'between:1900,9999'], 'view' => ['nullable', 'in:grid,timeline']]);
        $selectedYear = (string) ($filters['year'] ?? '');
        $historyView = $filters['view'] ?? 'grid';
        $leaders = app(HistoricalYearFilter::class)->apply($leaders, $selectedYear, $historyView);

        $displayTitle = $locale === 'si'
            ? $appointmentMeta['title_si']
            : $appointmentMeta['title_en'];

        $pageTitle = $displayTitle.' | '.($locale === 'si' ? 'ශ්‍රී ලංකා සංඥා බලකාය' : 'Sri Lanka Signal Corps');

        $pageDescription = $locale === 'si'
            ? 'ශ්‍රී ලංකා සංඥා බලකායේ '.$appointmentMeta['title_si'].' ධූරය හොබවන ලද සහ දැනට හොබවන නිලධාරීන්ගේ තොරතුරු.'
            : 'The distinguished officers who have served and currently serve as '.$appointmentMeta['title_en'].' of the Sri Lanka Signal Corps.';

        $languageVersions = [
            [
                'code' => 'en',
                'available' => true,
                'url' => url('/'.$appointmentMeta['slug']),
            ],
            [
                'code' => 'si',
                'available' => true,
                'url' => url('/si/'.$appointmentMeta['slug']),
            ],
        ];

        return view('theme-slsc::history.past-appointments', [
            'activeKey' => $activeKey,
            'appointmentMeta' => $appointmentMeta,
            'displayTitle' => $displayTitle,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'metaDescription' => $pageDescription,
            'position' => $position,
            'leaders' => $leaders,
            'selectedYear' => $selectedYear,
            'historyView' => $historyView,
            'currentLocale' => $locale,
            'languageVersions' => $languageVersions,
        ]);
    }

    private function locale(Request $request): string
    {
        $routeLocale = $request->route('locale');
        $locale = is_string($routeLocale) && trim($routeLocale) !== ''
            ? strtolower(trim($routeLocale))
            : 'en';

        $allowedLocales = app(SiteLanguageService::class)->codes();

        abort_unless(in_array($locale, $allowedLocales, true), 404);

        return $locale;
    }
}
