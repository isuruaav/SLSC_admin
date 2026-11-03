<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\ThemeLayoutLocale;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SiteLanguageService;
use App\Services\SiteSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class SiteSettingsIndex extends Component
{
    /** @var list<string> */
    public array $enabledLocales = ['en', 'si'];

    public string $siteName = '';

    public string $siteNameSi = '';

    public string $siteTagline = '';

    public string $siteTaglineSi = '';

    public string $themeFamily = 'army-unit';

    public ?int $logoMediaId = null;

    public ?int $faviconMediaId = null;

    public string $address = '';

    public string $addressSi = '';

    public string $phonePrimary = '';

    public string $phoneSecondary = '';

    public string $email = '';

    public string $mapUrl = '';

    public string $commanderName = '';

    public string $commanderNameSi = '';

    public string $commanderTitle = '';

    public string $commanderTitleSi = '';

    public string $commanderMessage = '';

    public string $commanderMessageSi = '';

    public ?int $commanderImageMediaId = null;

    public string $primaryColor = '#166534';

    public string $accentColor = '#ca8a04';

    public string $footerText = '';

    public string $footerTextSi = '';

    public bool $maintenanceMode = false;

    public string $maintenanceMessage = '';

    public string $maintenanceMessageSi = '';

    public string $socialPlatform = '';

    public string $socialLabel = '';

    public string $socialUrl = '';

    public function mount(): void
    {
        Gate::authorize('settings.manage');

        $this->loadSettings(
            SiteSetting::current(),
        );
    }

    public function save(): void
    {
        Gate::authorize('settings.manage');

        $this->validate([
            'enabledLocales' => ['required', 'array', 'min:1', 'max:3'],
            'enabledLocales.*' => ['required', 'string', 'distinct', 'in:en,si,ta'],
            'siteNameSi' => ['nullable', 'string', 'max:180'],
            'siteName' => ['required', 'string', 'max:180'],
            'siteTaglineSi' => ['nullable', 'string', 'max:255'],
            'siteTagline' => ['nullable', 'string', 'max:255'],
            'themeFamily' => ['required', 'in:army-unit,training-school,sfhq,establishment'],
            'logoMediaId' => ['nullable', 'integer', 'exists:media_assets,id'],
            'faviconMediaId' => ['nullable', 'integer', 'exists:media_assets,id'],
            'addressSi' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phonePrimary' => ['nullable', 'string', 'max:50'],
            'phoneSecondary' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'mapUrl' => ['nullable', 'string', 'max:2048'],
            'commanderNameSi' => ['nullable', 'string', 'max:180'],
            'commanderName' => ['nullable', 'string', 'max:180'],
            'commanderTitleSi' => ['nullable', 'string', 'max:180'],
            'commanderTitle' => ['nullable', 'string', 'max:180'],
            'commanderMessageSi' => ['nullable', 'string', 'max:5000'],
            'commanderMessage' => ['nullable', 'string', 'max:5000'],
            'commanderImageMediaId' => ['nullable', 'integer', 'exists:media_assets,id'],
            'primaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accentColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footerTextSi' => ['nullable', 'string', 'max:2000'],
            'footerText' => ['nullable', 'string', 'max:2000'],
            'maintenanceMode' => ['boolean'],
            'maintenanceMessageSi' => ['nullable', 'string', 'max:2000'],
            'maintenanceMessage' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! in_array('en', $this->enabledLocales, true)) {
            throw ValidationException::withMessages([
                'enabledLocales' => 'Keep English enabled as the fallback language.',
            ]);
        }

        $this->assertPublicImage($this->logoMediaId, 'logoMediaId');
        $this->assertPublicImage($this->faviconMediaId, 'faviconMediaId');
        $this->assertPublicImage($this->commanderImageMediaId, 'commanderImageMediaId');

        $mapUrl = $this->safeHttpUrl(
            $this->mapUrl,
            'mapUrl',
        );

        $updated = app(SiteSettingService::class)->update(
            settings: SiteSetting::current(),
            actor: $this->actor(),
            values: [
                'enabled_locales' => app(SiteLanguageService::class)->normalize($this->enabledLocales),
                'site_name' => trim($this->siteName),
                'site_name_si' => $this->nullable($this->siteNameSi),
                'site_tagline' => $this->nullable($this->siteTagline),
                'site_tagline_si' => $this->nullable($this->siteTaglineSi),
                'theme_family' => $this->themeFamily,
                'logo_media_id' => $this->logoMediaId,
                'favicon_media_id' => $this->faviconMediaId,
                'address' => $this->nullable($this->address),
                'address_si' => $this->nullable($this->addressSi),
                'phone_primary' => $this->nullable($this->phonePrimary),
                'phone_secondary' => $this->nullable($this->phoneSecondary),
                'email' => $this->nullable($this->email),
                'map_url' => $mapUrl,
                'commander_name' => $this->nullable(strip_tags($this->commanderName)),
                'commander_name_si' => $this->nullable(strip_tags($this->commanderNameSi)),
                'commander_title' => $this->nullable(strip_tags($this->commanderTitle)),
                'commander_title_si' => $this->nullable(strip_tags($this->commanderTitleSi)),
                'commander_message' => $this->nullable(strip_tags($this->commanderMessage)),
                'commander_message_si' => $this->nullable(strip_tags($this->commanderMessageSi)),
                'commander_image_media_id' => $this->commanderImageMediaId,
                'primary_color' => $this->primaryColor,
                'accent_color' => $this->accentColor,
                'footer_text' => $this->nullable($this->footerText),
                'footer_text_si' => $this->nullable($this->footerTextSi),
                'maintenance_mode' => $this->maintenanceMode,
                'maintenance_message' => $this->nullable($this->maintenanceMessage),
                'maintenance_message_si' => $this->nullable($this->maintenanceMessageSi),
            ],
        );

        $this->loadSettings($updated);

        session()->flash(
            'status',
            'Site settings saved successfully.',
        );
    }

    public function addSocialLink(): void
    {
        Gate::authorize('settings.manage');

        $this->validate([
            'socialPlatform' => ['required', 'string', 'max:80'],
            'socialLabel' => ['nullable', 'string', 'max:120'],
            'socialUrl' => ['required', 'string', 'max:2048'],
        ]);

        $url = $this->safeHttpUrl(
            $this->socialUrl,
            'socialUrl',
        );

        if ($url === null) {
            throw ValidationException::withMessages([
                'socialUrl' => 'The social URL is required.',
            ]);
        }

        $actor = $this->actor();

        $link = SocialLink::query()->create([
            'platform' => trim(strip_tags($this->socialPlatform)),
            'label' => $this->nullable(strip_tags($this->socialLabel)),
            'url' => $url,
            'is_active' => true,
            'sort_order' => ((int) SocialLink::query()->max('sort_order')) + 10,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        app(AuditLogger::class)->log(
            event: 'settings.social-created',
            description: 'A social link was created.',
            actor: $actor,
            subject: $link,
        );

        $this->socialPlatform = '';
        $this->socialLabel = '';
        $this->socialUrl = '';

        session()->flash(
            'status',
            'Social link added.',
        );
    }

    public function deleteSocialLink(int $id): void
    {
        Gate::authorize('settings.manage');

        $link = SocialLink::query()->findOrFail($id);

        app(AuditLogger::class)->log(
            event: 'settings.social-deleted',
            description: 'A social link was deleted.',
            actor: $this->actor(),
            subject: $link,
        );

        $link->delete();
    }

    public function render(): View
    {
        return view(
            'livewire.admin.settings.site-settings-index',
            [
                'mediaAssets' => MediaAsset::query()
                    ->where('type', 'image')
                    ->where('visibility', 'public')
                    ->latest()
                    ->limit(200)
                    ->get(),
                'socialLinks' => SocialLink::query()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(),
                'socialPlatforms' => array_keys(SocialLink::PLATFORM_ICONS),
                'availableLanguages' => ThemeLayoutLocale::cases(),
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Site Settings',
            ],
        );
    }

    private function loadSettings(SiteSetting $settings): void
    {
        $this->enabledLocales = app(SiteLanguageService::class)->codes($settings);
        $this->siteName = $settings->site_name;
        $this->siteNameSi = $settings->site_name_si ?? '';
        $this->siteTagline = $settings->site_tagline ?? '';
        $this->siteTaglineSi = $settings->site_tagline_si ?? '';
        $this->themeFamily = $settings->theme_family;
        $this->logoMediaId = $settings->logo_media_id;
        $this->faviconMediaId = $settings->favicon_media_id;
        $this->address = $settings->address ?? '';
        $this->addressSi = $settings->address_si ?? '';
        $this->phonePrimary = $settings->phone_primary ?? '';
        $this->phoneSecondary = $settings->phone_secondary ?? '';
        $this->email = $settings->email ?? '';
        $this->mapUrl = $settings->map_url ?? '';
        $this->commanderName = $settings->commander_name ?? '';
        $this->commanderNameSi = $settings->commander_name_si ?? '';
        $this->commanderTitle = $settings->commander_title ?? '';
        $this->commanderTitleSi = $settings->commander_title_si ?? '';
        $this->commanderMessage = $settings->commander_message ?? '';
        $this->commanderMessageSi = $settings->commander_message_si ?? '';
        $this->commanderImageMediaId = $settings->commander_image_media_id;
        $this->primaryColor = $settings->primary_color;
        $this->accentColor = $settings->accent_color;
        $this->footerText = $settings->footer_text ?? '';
        $this->footerTextSi = $settings->footer_text_si ?? '';
        $this->maintenanceMode = (bool) ($settings->maintenance_mode ?? false);
        $this->maintenanceMessage = $settings->maintenance_message ?? '';
        $this->maintenanceMessageSi = $settings->maintenance_message_si ?? '';
    }

    private function assertPublicImage(
        ?int $mediaId,
        string $field,
    ): void {
        if ($mediaId === null) {
            return;
        }

        $media = MediaAsset::query()->find($mediaId);

        if (
            ! $media instanceof MediaAsset
            || ! $media->isImage()
            || ! $media->isPublic()
            || $media->isExternal()
        ) {
            throw ValidationException::withMessages([
                $field => 'Select a valid uploaded Public image from the Media Library.',
            ]);
        }
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function safeHttpUrl(
        string $value,
        string $field,
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                $field => 'Enter a valid URL.',
            ]);
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        if (
            ! is_string($scheme)
            || ! in_array(mb_strtolower($scheme), ['http', 'https'], true)
        ) {
            throw ValidationException::withMessages([
                $field => 'Only http and https URLs are allowed.',
            ]);
        }

        return $value;
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
