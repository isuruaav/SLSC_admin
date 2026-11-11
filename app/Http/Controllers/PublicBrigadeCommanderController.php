<?php

namespace App\Http\Controllers;

use App\Models\BrigadeCommander;
use App\Services\HistoricalYearFilter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PublicBrigadeCommanderController extends Controller
{
    public function index(Request $request, ?string $locale = null): View
    {
        $currentLocale = $this->resolveLocale($locale);
        app()->setLocale($currentLocale);

        $commanders = BrigadeCommander::query()
            ->active()
            ->with('imageMedia')
            ->ordered()
            ->get();

        $filters = $request->validate(['year' => ['nullable', 'integer', 'between:1900,9999'], 'view' => ['nullable', 'in:grid,timeline']]);
        $selectedYear = (string) ($filters['year'] ?? '');
        $historyView = $filters['view'] ?? 'grid';
        $commanders = app(HistoricalYearFilter::class)->apply($commanders, $selectedYear, $historyView);

        $pageTitle = $currentLocale === 'si'
            ? 'බලසේනාධිපතිවරුන් | ශ්‍රී ලංකා සංඥා බලකාය'
            : 'Brigade Commanders | Sri Lanka Signal Corps';

        $pageDescription = $currentLocale === 'si'
            ? 'ශ්‍රී ලංකා යුද්ධ හමුදාවේ සියලුම සංඥා හා සන්නිවේදන මෙහෙයුම් මෙහෙයවූ 1 වන සංඥා බලසේනාවේ හිටපු බලසේනාධිපතිවරුන්.'
            : 'Historic Commanders of 1 Signal Brigade who commanded tactical signals and communication regiments across the Sri Lanka Army.';

        $languageVersions = [
            'en' => ['code' => 'en', 'available' => true, 'url' => route('brigade-commanders.index')],
            'si' => ['code' => 'si', 'available' => true, 'url' => route('brigade-commanders.index.localized', ['locale' => 'si'])],
        ];

        return view('theme-slsc::brigade-commanders', [
            'commanders' => $commanders,
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
