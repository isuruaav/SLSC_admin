<?php

namespace App\Http\Controllers;

use App\Models\FirstAppointmentHolder;
use App\Services\HistoricalYearFilter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PublicFirstAppointmentController extends Controller
{
    public function index(Request $request, ?string $locale = null): View
    {
        $currentLocale = $this->resolveLocale($locale);
        app()->setLocale($currentLocale);

        $holders = FirstAppointmentHolder::query()
            ->active()
            ->with('imageMedia')
            ->ordered()
            ->get();

        $filters = $request->validate(['year' => ['nullable', 'integer', 'between:1900,9999'], 'view' => ['nullable', 'in:grid,timeline']]);
        $selectedYear = (string) ($filters['year'] ?? '');
        $historyView = $filters['view'] ?? 'grid';
        $holders = app(HistoricalYearFilter::class)->apply($holders, $selectedYear, $historyView);

        $pageTitle = $currentLocale === 'si'
            ? 'පළමු පත්වීම් හිමියන් | ශ්‍රී ලංකා සංඥා බලකාය'
            : '1st Appointments Holders | Sri Lanka Signal Corps';

        $pageDescription = $currentLocale === 'si'
            ? 'ශ්‍රී ලංකා සංඥා බලකායේ ප්‍රථම පත්වීම් දරමින් අණදීමේ සහ තාක්ෂණික විශිෂ්ටත්වයේ මූලාරම්භය සනිටුහන් කළ ගෞරවනීය නිලධාරීන්.'
            : 'Historic first appointment holders of the Sri Lanka Signal Corps who laid the foundational pillars of command and operational excellence.';

        $languageVersions = [
            'en' => ['code' => 'en', 'available' => true, 'url' => route('first-appointments.index')],
            'si' => ['code' => 'si', 'available' => true, 'url' => route('first-appointments.index.localized', ['locale' => 'si'])],
        ];

        return view('theme-slsc::first-appointments', [
            'holders' => $holders,
            'currentLocale' => $currentLocale,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'languageVersions' => $languageVersions,
            'selectedYear' => $selectedYear,
            'historyView' => $historyView,
        ]);
    }

    private function resolveLocale(?string $locale): string
    {
        if ($locale && in_array($locale, ['en', 'si'], true)) {
            return $locale;
        }

        $appLoc = app()->getLocale();

        return in_array($appLoc, ['en', 'si'], true) ? $appLoc : 'en';
    }
}
