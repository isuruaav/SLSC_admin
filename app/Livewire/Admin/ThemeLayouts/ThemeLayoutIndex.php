<?php

namespace App\Livewire\Admin\ThemeLayouts;

use App\Enums\ThemeLayoutLocale;
use App\Enums\ThemeLayoutRegion;
use App\Models\ThemeLayout;
use App\Models\User;
use App\Services\FooterEditorService;
use App\Services\NavigationEditorService;
use App\Services\SiteLanguageService;
use App\Services\ThemeLayoutSanitizer;
use App\Services\ThemeLayoutService;
use App\Services\ThemeManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ThemeLayoutIndex extends Component
{
    #[Locked]
    public string $themeSlug = '';

    #[Locked]
    public bool $themeAvailable = false;

    public string $region = 'header';

    public string $locale = 'en';

    public string $layoutHtml = '';

    public string $layoutCss = '';

    public function mount(): void
    {
        Gate::authorize('theme-layouts.manage');

        $activeTheme = config('awcms.active_theme');

        if (! is_string($activeTheme)) {
            return;
        }

        $activeTheme = trim($activeTheme);

        if ($activeTheme === '' || app(ThemeManager::class)->find($activeTheme) === null) {
            return;
        }

        $this->themeSlug = $activeTheme;
        $this->themeAvailable = true;
        $this->loadRegion();
    }

    public function selectLocale(string $locale): void
    {
        Gate::authorize('theme-layouts.manage');

        $selectedLocale = ThemeLayoutLocale::tryFrom($locale);

        if (! $selectedLocale instanceof ThemeLayoutLocale || ! app(SiteLanguageService::class)->enabled($locale)) {
            throw ValidationException::withMessages([
                'locale' => 'Select an enabled site language. Languages can be enabled in Site Settings.',
            ]);
        }

        $this->locale = $selectedLocale->value;
        $this->resetValidation();
        $this->loadRegion();
    }

    public function selectRegion(string $region): void
    {
        Gate::authorize('theme-layouts.manage');

        $selectedRegion = ThemeLayoutRegion::tryFrom($region);

        if (! $selectedRegion instanceof ThemeLayoutRegion) {
            throw ValidationException::withMessages([
                'region' => 'Select a valid theme layout region.',
            ]);
        }

        $this->region = $selectedRegion->value;
        $this->resetValidation();
        $this->loadRegion();
    }

    public function saveDraft(): void
    {
        Gate::authorize('theme-layouts.manage');

        $this->ensureThemeAvailable();

        /** @var array{layoutHtml: string, layoutCss: string|null} $validated */
        $validated = $this->validate([
            'layoutHtml' => ['required', 'string', 'max:100000'],
            'layoutCss' => ['nullable', 'string', 'max:50000'],
        ]);

        app(ThemeLayoutService::class)->saveDraft(
            themeSlug: $this->themeSlug,
            region: $this->currentRegion(),
            locale: $this->currentLocale(),
            actor: $this->actor(),
            html: $validated['layoutHtml'],
            css: $validated['layoutCss'],
        );

        $this->loadRegion();

        session()->flash(
            'status',
            $this->currentLocale()->label().' '.$this->currentRegion()->label().' draft saved securely.',
        );
    }

    public function publish(): void
    {
        Gate::authorize('theme-layouts.manage');

        $this->ensureThemeAvailable();

        $layout = $this->currentLayout();

        if (! $layout instanceof ThemeLayout) {
            throw ValidationException::withMessages([
                'layout_html' => 'Save a valid draft before publishing.',
            ]);
        }

        app(ThemeLayoutService::class)->publish(
            layout: $layout,
            actor: $this->actor(),
        );

        $this->loadRegion();

        session()->flash(
            'status',
            $this->currentLocale()->label().' '.$this->currentRegion()->label().' layout published.',
        );
    }

    public function saveAndPublish(): void
    {
        $this->saveDraft();
        $this->publish();
    }

    public function render(): View
    {
        $theme = $this->themeAvailable
            ? app(ThemeManager::class)->find($this->themeSlug)
            : null;

        $layout = $this->themeAvailable
            ? $this->currentLayout()
            : null;

        return view(
            'livewire.admin.theme-layouts.theme-layout-index',
            [
                'layout' => $layout,
                'themeName' => $theme !== null ? $theme['name'] : null,
                'locales' => app(SiteLanguageService::class)->choices(ThemeLayoutLocale::cases()),
                'placeholders' => ThemeLayoutSanitizer::ALLOWED_PLACEHOLDERS,
                'navigationConfig' => $this->themeAvailable && $this->region === 'header'
                    ? app(NavigationEditorService::class)->configuration($this->currentLocale()->value, $this->themeSlug)
                    : [],
                'footerConfig' => $this->themeAvailable && $this->region === 'footer'
                    ? app(FooterEditorService::class)->configuration($this->currentLocale()->value, $this->themeSlug)
                    : [],
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Header & Footer',
            ],
        );
    }

    public function previewNavigation(string $html, string $css): string
    {
        return $this->previewLayout($html, $css);
    }

    public function previewLayout(string $html, string $css): string
    {
        Gate::authorize('theme-layouts.manage');
        $this->ensureThemeAvailable();
        $this->resetValidation(['layout_html', 'layout_css']);

        $rendered = app(NavigationEditorService::class)->render($html, $css, $this->currentLocale()->value, $this->themeSlug);

        return view('admin.theme-layouts.navigation-preview', [
            'html' => $rendered['html'],
            'css' => $rendered['css'],
            'themeSlug' => $this->themeSlug,
            'previewLocale' => $this->currentLocale()->value,
        ])->render();
    }

    private function loadRegion(): void
    {
        if (! $this->themeAvailable) {
            $this->layoutHtml = '';
            $this->layoutCss = '';

            return;
        }

        $layout = $this->currentLayout();

        if (! $layout instanceof ThemeLayout) {
            $this->layoutHtml = '';
            $this->layoutCss = '';

            return;
        }

        $this->layoutHtml = $layout->draft_html ?? '';
        $this->layoutCss = $layout->draft_css ?? '';
    }

    private function currentLayout(): ?ThemeLayout
    {
        return ThemeLayout::query()
            ->where('theme_slug', $this->themeSlug)
            ->where('region', $this->currentRegion()->value)
            ->where('locale', $this->currentLocale()->value)
            ->first();
    }

    private function currentRegion(): ThemeLayoutRegion
    {
        $region = ThemeLayoutRegion::tryFrom($this->region);

        if (! $region instanceof ThemeLayoutRegion) {
            throw ValidationException::withMessages([
                'region' => 'Select a valid theme layout region.',
            ]);
        }

        return $region;
    }

    private function currentLocale(): ThemeLayoutLocale
    {
        $locale = ThemeLayoutLocale::tryFrom($this->locale);

        if (! $locale instanceof ThemeLayoutLocale) {
            throw ValidationException::withMessages([
                'locale' => 'Select a valid theme layout language.',
            ]);
        }

        return $locale;
    }

    private function ensureThemeAvailable(): void
    {
        if (! $this->themeAvailable || $this->themeSlug === '') {
            throw ValidationException::withMessages([
                'theme' => 'Configure an installed active theme before editing its layout.',
            ]);
        }
    }

    private function actor(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'authorization' => 'An authenticated administrator is required.',
            ]);
        }

        return $user;
    }
}
