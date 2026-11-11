<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationCategory;
use App\Services\SiteLanguageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class PublicOrganizationController extends Controller
{
    public function index(Request $request, ?string $locale = null): View
    {
        $currentLocale = $this->resolveLocale($locale);
        app()->setLocale($currentLocale);

        $categorySlug = $request->query('category');
        $search = trim((string) $request->query('q', ''));

        $query = Organization::query()
            ->published()
            ->with(['category', 'translations', 'contentBlocks'])
            ->ordered();

        if (! empty($categorySlug) && $categorySlug !== 'all') {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhereHas('translations', function ($t) use ($search) {
                        $t->where('title', 'like', "%{$search}%")
                            ->orWhere('summary', 'like', "%{$search}%");
                    });
            });
        }

        $organizations = $query->get();
        $categories = OrganizationCategory::active()->ordered()->withCount(['organizations' => fn ($q) => $q->published()])->get();

        $languageVersions = [
            'en' => ['code' => 'en', 'available' => true, 'url' => route('organization.index')],
            'si' => ['code' => 'si', 'available' => true, 'url' => route('organization.index.localized', ['locale' => 'si'])],
        ];

        $pageTitle = match ($currentLocale) {
            'si' => 'සංවිධාන ව්‍යුහය | ශ්‍රී ලංකා සංඥා බලකාය',
            default => 'Organization Structure | Sri Lanka Signal Corps',
        };

        $pageDescription = match ($currentLocale) {
            'si' => 'ශ්‍රී ලංකා සංඥා බලකායේ ප්‍රධාන මූලස්ථානය, අධ්‍යක්ෂ මණ්ඩල, බලසේනා, රෙජිමේන්තු හා පුහුණු පාසල් තොරතුරු.',
            default => 'Explore the command structure, brigades, regiments, and specialized units of the Sri Lanka Signal Corps.',
        };

        return view('theme-slsc::organization.index', [
            'organizations' => $organizations,
            'categories' => $categories,
            'currentCategory' => $categorySlug,
            'searchQuery' => $search,
            'currentLocale' => $currentLocale,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'languageVersions' => $languageVersions,
        ]);
    }

    public function show(Request $request, string $slug, ?string $locale = null): View
    {
        $currentLocale = $this->resolveLocale($locale);
        app()->setLocale($currentLocale);

        /** @var Organization|null $organization */
        $organization = Organization::query()
            ->where('slug', $slug)
            ->with(['category', 'translations', 'contentBlocks' => fn ($q) => $q->ordered()])
            ->first();

        if (! $organization) {
            abort(404);
        }

        // If not published, only allow preview for authenticated users with permission
        if ($organization->status !== OrganizationStatus::Published) {
            $user = Auth::user();
            if (! $user || ! $user->can('organizations.view')) {
                abort(404);
            }
        }

        $translation = $organization->translationForLocale($currentLocale);
        $title = $translation?->title ?: ($organization->titleForLocale('en') ?: $organization->slug);
        $subtitle = $translation?->subtitle ?: '';
        $summary = $translation?->summary ?: '';
        $seoTitle = $translation?->seo_title ?: "{$title} | Sri Lanka Signal Corps";
        $seoDescription = $translation?->seo_description ?: $summary;

        // Structured language switch URLs
        $languageVersions = [
            'en' => ['code' => 'en', 'available' => true, 'url' => route('organization.show', ['slug' => $organization->slug])],
            'si' => ['code' => 'si', 'available' => true, 'url' => route('organization.show.localized', ['locale' => 'si', 'slug' => $organization->slug])],
        ];

        // Siblings in same category
        $relatedUnits = Organization::query()
            ->published()
            ->where('category_id', $organization->category_id)
            ->where('id', '!=', $organization->id)
            ->ordered()
            ->take(6)
            ->get();

        return view('theme-slsc::organization.show', [
            'organization' => $organization,
            'translation' => $translation,
            'title' => $title,
            'subtitle' => $subtitle,
            'summary' => $summary,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'currentLocale' => $currentLocale,
            'languageVersions' => $languageVersions,
            'relatedUnits' => $relatedUnits,
            'contentBlocks' => $organization->contentBlocks,
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

