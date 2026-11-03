<?php

use App\Livewire\Admin\Settings\SiteSettingsIndex;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Models\ThemeLayout;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::findOrCreate('settings.manage', 'web');

    $administrator = User::factory()->create();
    $administrator->givePermissionTo('settings.manage');
    $this->actingAs($administrator);

    SiteSetting::query()->create([
        'site_name' => 'School of Signals',
        'email' => 'old@example.com',
        'theme_family' => 'training-school',
        'primary_color' => '#0f172a',
        'accent_color' => '#16a34a',
    ]);

    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => 'footer',
        'locale' => 'en',
        'published_html' => '<footer class="managed-school-footer">[[contact_details]][[social_links]]</footer>',
        'status' => 'published',
        'published_at' => now(),
    ]);
});

it('uses the email saved in site settings for footer contact links in both languages', function (): void {
    Livewire::test(SiteSettingsIndex::class)
        ->set('email', 'office+inquiries@example.com')
        ->call('save')
        ->assertHasNoErrors();

    foreach ([route('home'), route('home.localized', ['locale' => 'si'])] as $url) {
        $this->get($url)->assertOk()
            ->assertSee('class="cms-contact-email" href="mailto:office+inquiries@example.com"', false)
            ->assertDontSee('mailto:old@example.com', false);
    }
});

it('adds a social link through site settings and renders its icon in both footer languages', function (string $platform, string $icon): void {
    Livewire::test(SiteSettingsIndex::class)
        ->assertSee('social-platforms')
        ->set('socialPlatform', $platform)
        ->set('socialLabel', 'School & Training')
        ->set('socialUrl', 'https://example.com/school')
        ->call('addSocialLink')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('social_links', [
        'platform' => trim($platform),
        'url' => 'https://example.com/school',
        'is_active' => true,
    ]);

    foreach ([route('home'), route('home.localized', ['locale' => 'si'])] as $url) {
        $this->get($url)->assertOk()
            ->assertSee('class="cms-social-link" href="https://example.com/school"', false)
            ->assertSee('<i class="'.$icon.'" aria-hidden="true"></i>', false)
            ->assertSee('aria-label="School &amp; Training" title="School &amp; Training"', false)
            ->assertDontSeeText('School & Training')
            ->assertDontSee('[[social_links]]', false);
    }
})->with([
    'Facebook' => ['Facebook', 'fa-brands fa-facebook-f'],
    'YouTube' => ['YouTube', 'fa-brands fa-youtube'],
    'case and spaces' => [' instagram ', 'fa-brands fa-instagram'],
    'Twitter alias' => ['Twitter', 'fa-brands fa-x-twitter'],
    'X' => ['X', 'fa-brands fa-x-twitter'],
    'custom platform' => ['School Portal', 'fa-solid fa-link'],
]);

it('renders platform icons in the built-in theme footer too', function (): void {
    ThemeLayout::query()->update(['status' => 'draft']);
    SocialLink::query()->create([
        'platform' => 'LinkedIn',
        'url' => 'https://example.com/linkedin',
        'is_active' => true,
    ]);

    $this->get(route('home'))->assertOk()
        ->assertSee('class="footer"', false)
        ->assertSee('class="fa-brands fa-linkedin-in"', false);
});

it('keeps inactive and unsafe social links out of the managed footer', function (): void {
    SocialLink::query()->create([
        'platform' => 'Facebook',
        'url' => 'https://example.com/inactive-social-link',
        'is_active' => false,
    ]);
    SocialLink::query()->create([
        'platform' => 'YouTube',
        'url' => 'javascript:alert(1)',
        'is_active' => true,
    ]);

    $this->get(route('home'))->assertOk()
        ->assertDontSee('https://example.com/inactive-social-link', false)
        ->assertDontSee('javascript:alert(1)', false)
        ->assertDontSee('class="cms-social-links"', false);
});
