<?php

use App\Enums\PageLocale;
use App\Models\HeroSlide;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set(
        'awcms.active_theme',
        'slsc',
    );

    config()->set(
        'awcms.theme_locales.slsc',
        [
            PageLocale::English->value,
            PageLocale::Sinhala->value,
        ],
    );
});

it('renders working hero page links when the website is installed in a subdirectory', function (string $locale, string $storedUrl, string $expectedPath, ?string $theme): void {
    config(['awcms.active_theme' => $theme]);

    Page::factory()->published()->create([
        'locale' => $locale,
        'slug' => $locale === 'en' ? 'aboutus' : 'siaboutus',
        'content' => '<p>Hero button destination</p>',
    ]);
    $slide = HeroSlide::query()->create([
        'title' => 'School slide',
        'button_label' => 'About Our School',
        'button_url' => '/page/aboutus',
        'is_active' => true,
    ]);
    $slide->translations()->create([
        'locale' => $locale,
        'title' => 'School slide',
        'button_label' => 'Visit school page',
        'button_url' => $storedUrl,
    ]);

    $this->withServerVariables([
        'SCRIPT_NAME' => '/sch_of_slsc/public/index.php',
        'SCRIPT_FILENAME' => public_path('index.php'),
        'PHP_SELF' => '/sch_of_slsc/public/index.php',
    ]);

    $homepage = 'http://localhost/sch_of_slsc/public/'.$locale;
    $destination = 'http://localhost/sch_of_slsc/public'.$expectedPath;

    $this->get($homepage)->assertOk()->assertSee('href="'.$destination.'"', false);
    $this->get($destination)->assertOk()->assertSee('Hero button destination');
})->with([
    'saved English URL' => ['en', '/page/aboutus', '/pages/aboutus'],
    'saved Sinhala URL' => ['si', '/si/page/siaboutus', '/si/pages/siaboutus'],
    'canonical English URL' => ['en', '/en/pages/aboutus', '/en/pages/aboutus'],
    'prefixed local URL' => ['en', '/sch_of_slsc/public/en/pages/aboutus', '/en/pages/aboutus'],
])->with(['slsc', null]);

it('keeps external hero button destinations unchanged', function (): void {
    HeroSlide::query()->create([
        'title' => 'External training',
        'button_label' => 'Training portal',
        'button_url' => 'https://training.example/page/aboutus?course=signals',
        'is_active' => true,
    ]);

    $this->get(route('home'))->assertOk()
        ->assertSee('href="https://training.example/page/aboutus?course=signals"', false);
});

it('renders hero slide content in the requested homepage language', function (): void {
    $slide = HeroSlide::query()->create([
        'title' => 'Legacy English Slider Title',
        'subtitle' => 'Legacy English Slider Subtitle',
        'button_label' => 'Legacy English Button',
        'button_url' => '/legacy-page',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $slide->translations()->createMany([
        [
            'locale' => PageLocale::English->value,
            'title' => 'English Signals Slider',
            'subtitle' => 'English Signals Description',
            'button_label' => 'Explore English',
            'button_url' => '/en/pages/english-slider-page',
        ],
        [
            'locale' => PageLocale::Sinhala->value,
            'title' => 'සිංහල සංඥා ස්ලයිඩරය',
            'subtitle' => 'සිංහල සංඥා විස්තරය',
            'button_label' => 'සිංහලෙන් බලන්න',
            'button_url' => '/si/pages/sinhala-slider-page',
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('English Signals Slider')
        ->assertSeeText('English Signals Description')
        ->assertSeeText('Explore English')
        ->assertSee('/en/pages/english-slider-page')
        ->assertDontSeeText('සිංහල සංඥා ස්ලයිඩරය')
        ->assertDontSee('/si/pages/sinhala-slider-page');

    $this->get(
        route(
            'home.localized',
            [
                'locale' => PageLocale::Sinhala->value,
            ],
        ),
    )
        ->assertOk()
        ->assertSeeText('සිංහල සංඥා ස්ලයිඩරය')
        ->assertSeeText('සිංහල සංඥා විස්තරය')
        ->assertSeeText('සිංහලෙන් බලන්න')
        ->assertSee('/si/pages/sinhala-slider-page')
        ->assertDontSeeText('English Signals Slider')
        ->assertDontSee('/en/pages/english-slider-page');
});

it('uses english fallback and excludes inactive hero slides', function (): void {
    $activeSlide = HeroSlide::query()->create([
        'title' => 'Legacy Active Title',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $activeSlide->translations()->create([
        'locale' => PageLocale::English->value,
        'title' => 'Published English Fallback Slide',
        'subtitle' => 'Fallback content for Sinhala',
    ]);

    $inactiveSlide = HeroSlide::query()->create([
        'title' => 'Hidden Legacy Slide',
        'is_active' => false,
        'sort_order' => 20,
    ]);

    $inactiveSlide->translations()->createMany([
        [
            'locale' => PageLocale::English->value,
            'title' => 'Hidden English Slide',
        ],
        [
            'locale' => PageLocale::Sinhala->value,
            'title' => 'සඟවා ඇති සිංහල ස්ලයිඩරය',
        ],
    ]);

    $this->get(
        route(
            'home.localized',
            [
                'locale' => PageLocale::Sinhala->value,
            ],
        ),
    )
        ->assertOk()
        ->assertSeeText('Published English Fallback Slide')
        ->assertSeeText('Fallback content for Sinhala')
        ->assertDontSeeText('Hidden English Slide')
        ->assertDontSeeText('සඟවා ඇති සිංහල ස්ලයිඩරය')
        ->assertDontSeeText('Hidden Legacy Slide');
});

it('renders slider buttons only when configured and does not render extra links or hardcoded copy', function (): void {
    $slideWithoutButton = HeroSlide::query()->create([
        'title' => 'Slide Without Button',
        'subtitle' => 'Only subtitle added from admin',
        'button_label' => null,
        'button_url' => null,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->get(route('home'))->assertOk();

    $response->assertSeeText('Slide Without Button');
    $response->assertSeeText('Only subtitle added from admin');
    $response->assertDontSeeText('A proud heritage of service, discipline, innovation and professional excellence.');
    $response->assertDontSeeText('Discover Our Corps');
    $response->assertDontSee('hero-secondary');

    $slideWithoutButton->update([
        'button_label' => 'Learn More',
        'button_url' => '/page/aboutus',
    ]);

    $updatedResponse = $this->get(route('home'))->assertOk();
    $updatedResponse->assertSeeText('Learn More');
    $updatedResponse->assertSee('/pages/aboutus');
    $updatedResponse->assertDontSee('hero-secondary');
});
