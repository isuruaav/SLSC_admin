@extends('theme-slsc::layout')

@php
    $currentSettings = $settings ?? $siteSettings ?? null;
    $homeLocale = in_array(app()->getLocale(), ['en', 'si', 'ta'], true) ? app()->getLocale() : 'en';
@endphp

@section('title', ($currentSettings?->localized('site_name') ?: __('theme-slsc::interface.brandName')) . ' | ' . __('theme-slsc::interface.motto'))
@section('meta_description', $currentSettings?->localized('site_tagline') ?: __('theme-slsc::interface.metaDescription'))

@section('content')
@php
    $themeAssetBase = asset('themes/slsc/assets');
    $mediaUrls = app(\App\Services\MediaUrlService::class);

    // Default slides if CMS hero_slides is empty
    $defaultSlides = [
        [
            'image' => $themeAssetBase . '/images/programs-sample.jpg',
            'alt' => 'Sri Lanka Signal Corps ceremonial parade',
            'label' => 'heroLabel1',
            'overline' => 'THE CORPS OF SIGNALS',
            'title' => 'heroTitle1',
            'heading' => 'Excellence in Military Communications.',
            'description' => 'heroDesc1',
            'copy' => 'A proud heritage of service, discipline, innovation and professional excellence.',
            'cta' => 'discover',
            'button' => 'Discover Our Corps',
            'url' => '#about',
        ],
        [
            'image' => $themeAssetBase . '/images/hero-innovation.jpg',
            'alt' => 'Sri Lanka Signal Corps leadership and activities',
            'label' => 'heroLabel2',
            'overline' => 'LEADERSHIP & INNOVATION',
            'title' => 'heroTitle2',
            'heading' => 'Connecting People. Advancing Technology.',
            'description' => 'heroDesc2',
            'copy' => 'Supporting professional development and modern information and communication technology.',
            'cta' => 'exploreOrganization',
            'button' => 'Explore Organization',
            'url' => 'https://alt.army.lk/signaller/organization',
        ],
        [
            'image' => $themeAssetBase . '/images/hero-memorial.jpg',
            'alt' => 'Sri Lanka Signal Corps memorial',
            'label' => 'heroLabel3',
            'overline' => 'HONOUR & REMEMBRANCE',
            'title' => 'heroTitle3',
            'heading' => 'Honouring Our Proud Legacy.',
            'description' => 'heroDesc3',
            'copy' => 'Remembering the dedication, service and sacrifices of our war heroes.',
            'cta' => 'viewStories',
            'button' => 'View Our Stories',
            'url' => 'https://alt.army.lk/signaller/photo-story',
        ],
    ];

    $hasCmsSlides = isset($slides) && $slides->isNotEmpty();

    // Default special programs fallback
    $defaultSpecialPrograms = [
        ['title' => 'Code of Conduct', 'title_si' => 'ආචාර ධර්ම පද්ධතිය', 'url' => 'https://alt.army.lk/public/code_of_conduct.pdf', 'category' => 'heritage'],
        ['title' => 'Signal Rugby Theme Song', 'title_si' => 'සංඥා රග්බි තේමා ගීතය', 'url' => 'https://alt.army.lk/signaller/rugby_song', 'category' => 'heritage'],
        ['title' => 'Signal Flag', 'title_si' => 'සංඥා ධජය', 'url' => 'https://alt.army.lk/signaller/flag', 'category' => 'heritage'],
        ['title' => 'Signal Song', 'title_si' => 'සංඥා බලකා ගීතය', 'url' => 'https://alt.army.lk/signaller/song', 'category' => 'heritage'],
        ['title' => 'Signal March', 'title_si' => 'සංඥා බලකා පෙළපාළි ගීතය', 'url' => 'https://alt.army.lk/signaller/signal-march', 'category' => 'heritage'],
        ['title' => 'Supreme Sacrifices', 'title_si' => 'උත්තරීතර පරිත්‍යාගයන්', 'url' => 'https://alt.army.lk/signaller/supreme-sacrifices', 'category' => 'heritage'],
        ['title' => '75th Anniversary Sri Lanka Signal Corps', 'title_si' => 'ශ්‍රී ලංකා සංඥා බලකායේ 75 වන සංවත්සරය', 'url' => 'https://alt.army.lk/signaller/content/commander-army-visited-corps-signals-respect-signallers-their-75th-anniversary', 'category' => 'heritage'],
        ['title' => 'EX - Servicemen', 'title_si' => 'විශ්‍රාමික හමුදා සාමාජිකයන්', 'url' => 'https://alt.army.lk/signaller/ex-servicemen', 'category' => 'community'],
        ['title' => 'Junior Club', 'title_si' => 'කනිෂ්ඨ සමාජය', 'url' => 'https://alt.army.lk/signaller/junior-club', 'category' => 'community'],
        ['title' => 'Signal Seva Vanitha Branch', 'title_si' => 'සංඥා සේවා වනිතා ශාඛාව', 'url' => 'https://alt.army.lk/slsc_svb/', 'category' => 'community'],
        ['title' => 'Professional Achievements', 'title_si' => 'වෘත්තීය ජයග්‍රහණ', 'url' => 'https://alt.army.lk/signaller/professional-achievements', 'category' => 'resources'],
        ['title' => 'Holiday Bungalows', 'title_si' => 'නිවාඩු බංගලා', 'url' => 'https://alt.army.lk/signaller/holiday-bungalows', 'category' => 'resources'],
        ['title' => 'Research and Development Project', 'title_si' => 'පර්යේෂණ හා සංවර්ධන ව්‍යාපෘතිය', 'url' => 'https://alt.army.lk/signaller/research-and-development-project', 'category' => 'resources'],
        ['title' => 'Veteran Affairs Section', 'title_si' => 'විශ්‍රාමික රණවිරු කටයුතු අංශය', 'url' => 'https://alt.army.lk/slsc_va/', 'category' => 'community'],
        ['title' => 'History Book Launching Ceremony', 'title_si' => 'ඉතිහාස ග්‍රන්ථය එළිදැක්වීමේ උත්සවය', 'url' => 'https://alt.army.lk/signaller/history-book-launching-ceremony', 'category' => 'heritage'],
    ];

    // Special programs from database or fallback
    $dbSpecialPrograms = isset($specialPrograms) && $specialPrograms instanceof \Illuminate\Support\Collection && $specialPrograms->isNotEmpty()
        ? $specialPrograms
        : collect();

    $specialProgramsList = [];
    if ($dbSpecialPrograms->isNotEmpty()) {
        foreach ($dbSpecialPrograms as $sp) {
            $specialProgramsList[] = [
                'title' => $sp->titleForLocale($homeLocale),
                'url' => $sp->url,
                'category' => $sp->category,
                'open_in_new_tab' => $sp->open_in_new_tab,
                'image_url' => $sp->imageUrl(),
            ];
        }
    } else {
        foreach ($defaultSpecialPrograms as $dsp) {
            $specialProgramsList[] = [
                'title' => ($homeLocale === 'si' && !empty($dsp['title_si'])) ? $dsp['title_si'] : $dsp['title'],
                'url' => $dsp['url'],
                'category' => $dsp['category'],
                'open_in_new_tab' => true,
                'image_url' => null,
            ];
        }
    }

    // Default news
    $defaultNews = [
        [
            'key' => 'news1Title',
            'title' => 'Corps of Signals Celebrates 83rd Anniversary with Ceremonial Parade',
            'date' => '2026-10-19',
            'category' => 'events',
            'url' => 'https://alt.army.lk/signaller/content/corps-signals-celebrates-its-83rd-anniversary-ceremonial-parade',
            'image' => $themeAssetBase . '/images/news-parade.jpg',
            'alt' => 'Ceremonial parade marking the 83rd anniversary of the Sri Lanka Signal Corps',
            'featured' => true,
        ],
        [
            'key' => 'news2Title',
            'title' => 'Signal Corps Remembers Fallen War Heroes',
            'date' => '2026-10-18',
            'category' => 'events',
            'url' => 'https://alt.army.lk/signaller/content/slsc-commemorates-its-fallen-war-heroes-regimental-centre',
            'image' => $themeAssetBase . '/images/news-remembrance.jpg',
            'alt' => 'Remembrance ceremony at the Regimental Centre memorial',
            'featured' => false,
        ],
        [
            'key' => 'news3Title',
            'title' => '1st Regiment Commemorates Its 83rd Anniversary',
            'date' => '2026-10-18',
            'category' => 'events',
            'url' => 'https://alt.army.lk/signaller/content/1-slsc-commemorates-its-83rd-anniversary-mullaitivu',
            'image' => $themeAssetBase . '/images/news-regiment.jpg',
            'alt' => '1st Regiment commemoration activities in Mullaitivu',
            'featured' => false,
        ],
        [
            'key' => 'news4Title',
            'title' => 'Seva Vanitha Welfare Assistance Programme',
            'date' => '2026-10-10',
            'category' => 'welfare',
            'url' => 'https://alt.army.lk/signaller/content/slsc-seva-vanitha-branch-launched-welfare-assistance-programme',
            'image' => $themeAssetBase . '/images/news-welfare.jpg',
            'alt' => 'Seva Vanitha Branch welfare assistance distribution',
            'featured' => false,
        ],
    ];

    $newsItems = isset($latestNews) && $latestNews->isNotEmpty() ? $latestNews : (isset($news) && $news->isNotEmpty() ? $news : collect());
    $hasCmsNews = $newsItems->isNotEmpty();

    // Commandant / Leader
    $commandantLeader = isset($schoolLeaders) ? $schoolLeaders->first() : null;
    $commandantName = $commandantLeader ? $commandantLeader->nameForLocale($homeLocale) : 'Major General K M G Bandaranayake';
    $commandantRank = $commandantLeader ? $commandantLeader->rankLabelForLocale($homeLocale) : 'Major General';
    $commandantPhoto = ($commandantLeader && $commandantLeader->image)
        ? $mediaUrls->mediumOrOriginal($commandantLeader->image)
        : $themeAssetBase . '/images/maj_gen_bandaranayake_new.jpg';
@endphp

{{-- 1. Hero Slider Carousel --}}
<section class="hero-section" aria-label="{{ __('theme-slsc::interface.featuredStories') }}" data-i18n-aria="featuredStories">
    <div class="hero-slider" id="heroSlider" role="region" aria-roledescription="carousel" aria-label="{{ __('theme-slsc::interface.featuredStories') }}" tabindex="0" data-i18n-aria="featuredStories">
        @if ($hasCmsSlides)
            @foreach ($slides as $index => $slide)
                @php
                    $slideTranslation = $slide->translation($homeLocale);
                    $slideTitle = $slideTranslation?->title ?? $slide->title;
                    $slideHeading = $slideTranslation?->subtitle ?? $slide->subtitle ?? 'THE CORPS OF SIGNALS';
                    $slideDesc = $slideTranslation?->description ?? $slide->description ?? 'A proud heritage of service, discipline, innovation and professional excellence.';
                    $slideUrl = $slide->resolvedButtonUrl($homeLocale) ?: '#about';
                    $slideBtn = $slideTranslation?->button_label ?? $slide->button_label ?? 'Discover Our Corps';
                    $slideImage = $slide->image ? ($mediaUrls->original($slide->image) ?? $mediaUrls->mediumOrOriginal($slide->image)) : $themeAssetBase . '/images/programs-sample.jpg';
                @endphp
                <article class="hero-slide {{ $index === 0 ? 'is-active' : '' }}" aria-hidden="{{ $index === 0 ? 'false' : 'true' }}" {{ $index !== 0 ? 'inert' : '' }}>
                    <img class="hero-image" src="{{ $slideImage }}" alt="{{ $slideTitle }}" width="1090" height="326" {{ $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' }}>
                    <div class="container hero-inner">
                        <div class="hero-content">
                            <p class="hero-overline"><span class="hero-pulse" aria-hidden="true"></span><span>{{ $slideHeading }}</span></p>
                            <h1>{{ $slideTitle }}</h1>
                            <p class="hero-description">{{ $slideDesc }}</p>
                            <div class="hero-links">
                                <a class="button button-green" href="{{ $slideUrl }}">
                                    <span>{{ $slideBtn }}</span>
                                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg>
                                </a>
                                <a class="hero-secondary" href="#special-programs">
                                    <span data-i18n="navPrograms">{{ __('theme-slsc::interface.navPrograms') }}</span>
                                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        @else
            @foreach ($defaultSlides as $index => $slide)
                <article class="hero-slide {{ $index === 0 ? 'is-active' : '' }}" aria-hidden="{{ $index === 0 ? 'false' : 'true' }}" {{ $index !== 0 ? 'inert' : '' }}>
                    <img class="hero-image" src="{{ $slide['image'] }}" alt="{{ __('theme-slsc::interface.'.$slide['title']) }}" width="1090" height="326" {{ $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' }}>
                    <div class="container hero-inner">
                        <div class="hero-content">
                            <p class="hero-overline"><span class="hero-pulse" aria-hidden="true"></span><span data-i18n="{{ $slide['label'] }}">{{ __('theme-slsc::interface.'.$slide['label']) }}</span></p>
                            <h1 data-i18n="{{ $slide['title'] }}">{{ __('theme-slsc::interface.'.$slide['title']) }}</h1>
                            <p class="hero-description" data-i18n="{{ $slide['description'] }}">{{ __('theme-slsc::interface.'.$slide['description']) }}</p>
                            <div class="hero-links">
                                <a class="button button-green" href="{{ $slide['url'] }}" {{ str_starts_with($slide['url'], 'https:') ? 'target="_blank" rel="noopener noreferrer"' : '' }}>
                                    <span data-i18n="{{ $slide['cta'] }}">{{ __('theme-slsc::interface.'.$slide['cta']) }}</span>
                                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg>
                                </a>
                                <a class="hero-secondary" href="#special-programs">
                                    <span data-i18n="navPrograms">{{ __('theme-slsc::interface.navPrograms') }}</span>
                                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        @endif

        @php $slideCount = $hasCmsSlides ? $slides->count() : count($defaultSlides); @endphp
        <div class="container hero-bottom enhanced-only">
            <div class="hero-pagination">
                <span class="hero-count"><strong id="activeSlideNumber">01</strong><span>/ {{ str_pad((string)$slideCount, 2, '0', STR_PAD_LEFT) }}</span></span>
                <div class="slider-dots" role="group" aria-label="{{ __('theme-slsc::interface.chooseSlide') }}" data-i18n-aria="chooseSlide">
                    @for ($i = 0; $i < $slideCount; $i++)
                        <button type="button" class="slider-dot {{ $i === 0 ? 'active' : '' }}" data-slide="{{ $i }}" aria-label="Slide {{ $i + 1 }}" {{ $i === 0 ? 'aria-current="true"' : '' }}><span></span></button>
                    @endfor
                </div>
                <span class="hero-progress" aria-hidden="true"><span id="heroProgress"></span></span>
            </div>
            <div class="slider-controls">
                <button id="toggleSlider" class="slider-arrow" type="button" aria-label="{{ __('theme-slsc::interface.pauseSlider') }}" data-i18n-aria="pauseSlider" aria-pressed="false">
                    <svg class="icon pause-icon" aria-hidden="true" focusable="false"><use href="#i-pause"></use></svg>
                    <svg class="icon play-icon" aria-hidden="true" focusable="false"><use href="#i-play"></use></svg>
                </button>
                <button id="prevSlide" class="slider-arrow" type="button" aria-label="{{ __('theme-slsc::interface.previousSlide') }}" data-i18n-aria="previousSlide">
                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-left"></use></svg>
                </button>
                <button id="nextSlide" class="slider-arrow" type="button" aria-label="{{ __('theme-slsc::interface.nextSlide') }}" data-i18n-aria="nextSlide">
                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-right"></use></svg>
                </button>
            </div>
        </div>
        <p id="slideStatus" class="sr-only" aria-live="polite" aria-atomic="true"></p>
    </div>
</section>

{{-- 2. Live News Ticker --}}
<div class="news-ticker" aria-label="{{ __('theme-slsc::interface.latestHeadlines') }}" data-i18n-aria="latestHeadlines">
    <div class="container ticker-inner">
        <span class="ticker-label">
            <span class="status-dot" aria-hidden="true"></span>
            <span data-i18n="latest">{{ __('theme-slsc::interface.latest') }}</span>
        </span>
        <div class="ticker-window">
            <div class="ticker-track">
                <span data-i18n="ticker1">{{ __('theme-slsc::interface.ticker1') }}</span>
                <span class="ticker-separator" aria-hidden="true">•</span>
                <span data-i18n="ticker2">{{ __('theme-slsc::interface.ticker2') }}</span>
                <span class="ticker-separator" aria-hidden="true">•</span>
                <span data-i18n="ticker3">{{ __('theme-slsc::interface.ticker3') }}</span>
            </div>
        </div>
        <a class="ticker-all" href="#news">
            <span data-i18n="viewAll">{{ __('theme-slsc::interface.viewAll') }}</span>
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg>
        </a>
    </div>
</div>

{{-- 3. Anniversary Spotlight Banner --}}
<aside class="anniversary-section" aria-label="{{ __('theme-slsc::interface.featuredUpdate') }}" data-i18n-aria="featuredUpdate">
    <div class="container anniversary-grid">
        <div class="anniversary-number" aria-label="{{ __('theme-slsc::interface.anniversaryAria') }}">83<sup>rd</sup><span>1943 — 2026</span></div>
        <div class="anniversary-story">
            <p class="eyebrow" data-i18n="featuredUpdate">{{ __('theme-slsc::interface.featuredUpdate') }}</p>
            <h2 data-i18n="anniversaryTitle">{{ __('theme-slsc::interface.anniversaryTitle') }}</h2>
            <p data-i18n="anniversaryDesc">{{ __('theme-slsc::interface.anniversaryDesc') }}</p>
        </div>
        <a class="text-link" href="https://alt.army.lk/signaller/features-news" target="_blank" rel="noopener noreferrer">
            <span data-i18n="readStory">{{ __('theme-slsc::interface.readStory') }}</span>
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg>
        </a>
    </div>
</aside>

{{-- 4. Leadership / Colonel Commandant --}}
<section id="about" class="section-space commandant-section" aria-labelledby="commandantTitle">
    <div class="container commandant-layout">
        <div class="commandant-visual reveal">
            <div class="portrait-frame">
                <img src="{{ $commandantPhoto }}" alt="{{ $commandantName }}" width="300" height="372" loading="lazy">
            </div>
            <div class="portrait-caption">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-shield"></use></svg>
                <span data-i18n="brandName">{{ __('theme-slsc::interface.brandName') }}</span>
            </div>
        </div>
        <div class="commandant-content reveal">
            <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="commandantEyebrow">{{ __('theme-slsc::interface.commandantEyebrow') }}</span></p>
            <h2 id="commandantTitle" class="section-title">
                <span data-i18n="commandantTitle">{{ __('theme-slsc::interface.commandantTitle') }}</span>
                <span class="commandant-corps" data-i18n="brandName">{{ __('theme-slsc::interface.brandName') }}</span>
            </h2>
            <div class="commandant-identity">
                <h3>{{ $commandantName }}</h3>
                <p lang="en">USP <span>ndc</span> psc</p>
            </div>
            <div class="commandant-motto">
                <span class="tricolour" aria-hidden="true"></span>
                <span data-i18n="motto">{{ __('theme-slsc::interface.motto') }}</span>
            </div>
            <p class="muted commandant-note" data-i18n="mottoSubtitle">{{ __('theme-slsc::interface.mottoSubtitle') }}</p>

            @if ($commandantLeader && ($commandantProfileUrl = $commandantLeader->profileUrlForLocale($homeLocale)))
                <div class="commandant-actions" style="margin-top: 1rem;">
                    <a href="{{ $commandantProfileUrl }}" class="button button-outline">
                        <span>{{ __('theme-slsc::home.leadership.read_more') ?: 'Read More' }}</span>
                    </a>
                </div>
            @endif

            @php
                $schoolLeadershipUrl = $homeLocale === 'en'
                    ? route('school-leadership.index')
                    : route('school-leadership.localized', ['locale' => $homeLocale]);
            @endphp
            <div class="sos-leadership-link" style="margin-top: 0.75rem;">
                <a href="{{ $schoolLeadershipUrl }}" class="button button-outline">
                    <span>{{ __('theme-slsc::home.leadership.view_all') ?: 'View School Leadership' }}</span>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- 5. Newsroom / News & Updates --}}
@php
    // Prepare Photo Story & News Features
    $cmsPhotoStory = $photoStory ?? null;
    $cmsNewsFeatures = isset($newsFeatures) && $newsFeatures->isNotEmpty() ? $newsFeatures : collect();

    // 1. Photo Story data (Left Box)
    if ($cmsPhotoStory) {
        $psImage = $cmsPhotoStory->featuredImage ? $mediaUrls->mediumOrOriginal($cmsPhotoStory->featuredImage) : $themeAssetBase . '/images/news-parade.jpg';
        $psTitle = $cmsPhotoStory->title;
        $psExcerpt = $cmsPhotoStory->summary ?: \Illuminate\Support\Str::limit(strip_tags($cmsPhotoStory->content), 180);
        $psUrl = $homeLocale === 'en' ? route('news.show', ['slug' => $cmsPhotoStory->slug]) : route('news.show.localized', ['locale' => $homeLocale, 'slug' => $cmsPhotoStory->slug]);
        $psRawDate = $cmsPhotoStory->published_at ?? $cmsPhotoStory->created_at;
        $psDateText = $psRawDate instanceof \Carbon\CarbonInterface
            ? $psRawDate->format('d M Y')
            : ($psRawDate ? \Carbon\Carbon::parse($psRawDate)->format('d M Y') : date('d M Y'));
        $psDateIso = $psRawDate instanceof \Carbon\CarbonInterface
            ? $psRawDate->toDateString()
            : ($psRawDate ? \Carbon\Carbon::parse($psRawDate)->toDateString() : date('Y-m-d'));
        $psCategory = $cmsPhotoStory->category?->name ?? 'Photo Story';
    }

    // 2. News Features list (Right Box Stack - 3 cards)
    $featuresList = [];
    if ($cmsNewsFeatures->isNotEmpty()) {
        foreach ($cmsNewsFeatures->take(3) as $fItem) {
            $fRawDate = $fItem->published_at ?? $fItem->created_at;
            $featuresList[] = [
                'title' => $fItem->title,
                'image' => $fItem->featuredImage ? $mediaUrls->mediumOrOriginal($fItem->featuredImage) : $themeAssetBase . '/images/news-remembrance.jpg',
                'excerpt' => $fItem->summary ?: \Illuminate\Support\Str::limit(strip_tags($fItem->content), 100),
                'url' => $homeLocale === 'en' ? route('news.show', ['slug' => $fItem->slug]) : route('news.show.localized', ['locale' => $homeLocale, 'slug' => $fItem->slug]),
                'dateText' => $fRawDate instanceof \Carbon\CarbonInterface ? $fRawDate->format('d M Y') : ($fRawDate ? \Carbon\Carbon::parse($fRawDate)->format('d M Y') : date('d M Y')),
                'dateIso' => $fRawDate instanceof \Carbon\CarbonInterface ? $fRawDate->toDateString() : ($fRawDate ? \Carbon\Carbon::parse($fRawDate)->toDateString() : date('Y-m-d')),
                'category' => $fItem->category?->name ?? 'News Feature',
            ];
        }
    }

    $allPhotoStoriesUrl = $homeLocale === 'en'
        ? route('news.index', ['type' => 'photo-story'])
        : route('news.index.localized', ['locale' => $homeLocale, 'type' => 'photo-story']);

    $allNewsFeaturesUrl = $homeLocale === 'en'
        ? route('news.index', ['type' => 'news-feature'])
        : route('news.index.localized', ['locale' => $homeLocale, 'type' => 'news-feature']);
@endphp

<section id="news" class="section-space section-muted" aria-labelledby="newsTitle">
    <div class="container">
        {{-- Section Heading with Separate View All Buttons --}}
        <div class="section-heading reveal news-section-heading">
            <div>
                <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="newsEyebrow">{{ __('theme-slsc::interface.newsEyebrow') }}</span></p>
                <h2 id="newsTitle" class="section-title" data-i18n="newsTitle">{{ __('theme-slsc::interface.newsTitle') }}</h2>
                <p class="section-subtitle" data-i18n="newsSubtitle">{{ __('theme-slsc::interface.newsSubtitle') }}</p>
            </div>
            <div class="news-header-actions">
                <a class="button button-outline news-action-btn photo-story-action-btn" href="{{ $allPhotoStoriesUrl }}">
                    <i class="fa-solid fa-camera-retro" aria-hidden="true"></i>
                    <span>{{ __('theme-slsc::interface.allPhotoStories') }}</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a class="button button-green news-action-btn news-feature-action-btn" href="{{ $allNewsFeaturesUrl }}">
                    <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                    <span>{{ __('theme-slsc::interface.allNewsFeatures') }}</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        {{-- Wireframe 1 + 3 News Portal Grid --}}
        <div class="news-portal-grid reveal" id="newsGrid">
            {{-- LEFT: PHOTO STORY (BIG BOX) --}}
            @if ($cmsPhotoStory)
            <article class="photo-story-box">
                <a class="photo-story-image-wrap" href="{{ $psUrl }}" aria-label="{{ $psTitle }}">
                    <img src="{{ $psImage }}" alt="{{ $psTitle }}" width="720" height="420" loading="lazy" decoding="async">
                    <span class="photo-story-badge">
                        <i class="fa-solid fa-camera-retro" aria-hidden="true"></i>
                        <span>{{ __('theme-slsc::interface.photoStory') }}</span>
                    </span>
                </a>
                <div class="photo-story-body">
                    <div class="news-meta">
                        <span class="news-category">{{ $psCategory }}</span>
                        <time datetime="{{ $psDateIso }}">
                            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
                            {{ $psDateText }}
                        </time>
                    </div>
                    <h3 class="photo-story-title">
                        <a href="{{ $psUrl }}">{{ $psTitle }}</a>
                    </h3>
                    <p class="photo-story-excerpt">{{ $psExcerpt }}</p>
                    <div class="photo-story-footer">
                        <a class="button button-green photo-story-btn" href="{{ $psUrl }}">
                            <span data-i18n="readMore">{{ __('theme-slsc::interface.readMore') }}</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </article>
            @else
                <div class="photo-story-box">
                    <div class="photo-story-body">
                        <h3 class="photo-story-title">{{ __('public.news_page.photo_stories') }}</h3>
                        <p class="photo-story-excerpt">{{ __('public.news_page.no_photo_stories') }}</p>
                    </div>
                </div>
            @endif

            {{-- RIGHT: 3 STACKED NEWS FEATURES (SMALL BOXES) --}}
            <div class="news-features-stack">
                @forelse ($featuresList as $feature)
                    <article class="news-feature-card">
                        <a class="news-feature-thumb" href="{{ $feature['url'] }}" aria-label="{{ $feature['title'] }}">
                            <img src="{{ $feature['image'] }}" alt="{{ $feature['title'] }}" width="240" height="180" loading="lazy" decoding="async">
                        </a>
                        <div class="news-feature-body">
                            <div class="news-meta">
                                <span class="feature-tag">
                                    <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                                    <span>{{ __('theme-slsc::interface.newsFeature') }}</span>
                                </span>
                                <time datetime="{{ $feature['dateIso'] }}">
                                    <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
                                    {{ $feature['dateText'] }}
                                </time>
                            </div>
                            <h4 class="news-feature-title">
                                <a href="{{ $feature['url'] }}">{{ $feature['title'] }}</a>
                            </h4>
                            <p class="news-feature-excerpt">{{ $feature['excerpt'] }}</p>
                            <a class="feature-read-more" href="{{ $feature['url'] }}">
                                <span data-i18n="readMore">{{ __('theme-slsc::interface.readMore') }}</span>
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="news-feature-card">
                        <div class="news-feature-body">
                            <h4 class="news-feature-title">{{ __('public.news_page.news_features') }}</h4>
                            <p class="news-feature-excerpt">{{ __('public.news_page.no_news_features') }}</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>

{{-- 6. Special Programs Directory --}}
<section id="special-programs" class="section-space" aria-labelledby="programsTitle">
    <div class="container">
        <div class="section-heading reveal">
            <div>
                <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="programsEyebrow">{{ __('theme-slsc::interface.programsEyebrow') }}</span></p>
                <h2 id="programsTitle" class="section-title" data-i18n="programsTitle">{{ __('theme-slsc::interface.programsTitle') }}</h2>
                <p class="section-subtitle" data-i18n="programsSubtitle">{{ __('theme-slsc::interface.programsSubtitle') }}</p>
            </div>
            <span class="section-index" aria-hidden="true">{{ count($specialProgramsList) }}<span data-i18n="programsLabel">{{ __('theme-slsc::interface.programsLabel') }}</span></span>
        </div>
        <div class="filter-bar program-filters enhanced-only">
            <label class="input-wrap">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
                <span class="sr-only" data-i18n="searchPrograms">{{ __('theme-slsc::interface.searchPrograms') }}</span>
                <input id="programSearch" type="search" placeholder="{{ __('theme-slsc::interface.programSearchPlaceholder') }}" data-i18n-placeholder="programSearchPlaceholder" autocomplete="off">
            </label>
            <label class="select-wrap">
                <span class="sr-only" data-i18n="programCategories">{{ __('theme-slsc::interface.programCategories') }}</span>
                <select id="programCategory">
                    <option value="all" data-i18n="allPrograms">{{ __('theme-slsc::interface.allPrograms') }}</option>
                    <option value="heritage" data-i18n="heritageCategory">{{ __('theme-slsc::interface.heritageCategory') }}</option>
                    <option value="community" data-i18n="communityCategory">{{ __('theme-slsc::interface.communityCategory') }}</option>
                    <option value="resources" data-i18n="resourcesCategory">{{ __('theme-slsc::interface.resourcesCategory') }}</option>
                </select>
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg>
            </label>
            <span class="result-count" id="programCount" aria-live="polite" aria-atomic="true">{{ count($specialProgramsList) }} programs</span>
        </div>
        <ul class="programs-grid" id="programGrid">
            @foreach ($specialProgramsList as $index => $program)
                <li class="program-item reveal" data-program-category="{{ $program['category'] }}">
                    <a class="program-card" href="{{ $program['url'] }}" @if($program['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif data-search-item="programs" data-entry-link @if(!empty($program['image_url'])) style="--program-bg: url('{{ $program['image_url'] }}');" @endif>
                        <span class="program-number" aria-hidden="true">{{ str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 data-entry-title>{{ $program['title'] }}</h3>
                        <svg class="icon program-arrow" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="empty-state" id="noPrograms" hidden>
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
            <h3 data-i18n="noPrograms">{{ __('theme-slsc::interface.noPrograms') }}</h3>
            <p data-i18n="tryAnotherSearch">{{ __('theme-slsc::interface.tryAnotherSearch') }}</p>
            <button class="button button-outline" type="button" data-reset="programs" data-i18n="resetFilters">{{ __('theme-slsc::interface.resetFilters') }}</button>
        </div>
    </div>
</section>

{{-- 7. Digital Publications ("The Signaller") --}}
<section id="publications" class="publication-section" aria-labelledby="publicationTitle">
    <div class="container">
        <div class="publication-panel reveal">
            <div class="publication-copy">
                <p class="eyebrow" data-i18n="pubEyebrow">{{ __('theme-slsc::interface.pubEyebrow') }}</p>
                <h2 id="publicationTitle" data-i18n="pubTitle">{{ __('theme-slsc::interface.pubTitle') }}</h2>
                <p data-i18n="pubDesc">{{ __('theme-slsc::interface.pubDesc') }}</p>
                <a class="button button-white" href="https://alt.army.lk/signaller/content/%E2%80%98-signaller%E2%80%99-magazine-2024-launched-web" target="_blank" rel="noopener noreferrer">
                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-book"></use></svg>
                    <span data-i18n="pubButton">{{ __('theme-slsc::interface.pubButton') }}</span>
                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
                </a>
            </div>
            <div class="publication-art" aria-hidden="true">
                <div class="book-shadow"></div>
                <div class="book-cover">
                    <span class="book-edition">2024</span>
                    <img src="{{ $themeAssetBase }}/images/logo/signals-logo.png" alt="" width="3239" height="4412" loading="lazy">
                    <span class="book-name">THE<br>SIGNALLER</span>
                    <span class="book-corps">SRI LANKA SIGNAL CORPS</span>
                    <span class="tricolour"></span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 8. Recruitment / Join Us --}}
<section id="join-us" class="section-space join-section" aria-labelledby="joinTitle">
    <div class="container join-layout">
        <div class="join-copy reveal">
            <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="joinEyebrow">{{ __('theme-slsc::interface.joinEyebrow') }}</span></p>
            <h2 id="joinTitle" class="section-title" data-i18n="joinTitle">{{ __('theme-slsc::interface.joinTitle') }}</h2>
            <p class="section-subtitle" data-i18n="joinDescription">{{ __('theme-slsc::interface.joinDescription') }}</p>
            <a class="button button-green join-cta" href="#registrationDialog" data-open-registration aria-haspopup="dialog">
                <span data-i18n="joinRegister">{{ __('theme-slsc::interface.joinRegister') }}</span>
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg>
            </a>
            <p class="join-note" data-i18n="joinNote">{{ __('theme-slsc::interface.joinNote') }}</p>
        </div>
        <ol class="join-steps reveal" aria-label="{{ __('theme-slsc::interface.joinStepsLabel') }}" data-i18n-aria="joinStepsLabel">
            <li>
                <span class="join-step-number" aria-hidden="true">01</span>
                <div>
                    <h3 data-i18n="joinStepOne">{{ __('theme-slsc::interface.joinStepOne') }}</h3>
                    <p data-i18n="joinStepOneCopy">{{ __('theme-slsc::interface.joinStepOneCopy') }}</p>
                </div>
            </li>
            <li>
                <span class="join-step-number" aria-hidden="true">02</span>
                <div>
                    <h3 data-i18n="joinStepTwo">{{ __('theme-slsc::interface.joinStepTwo') }}</h3>
                    <p data-i18n="joinStepTwoCopy">{{ __('theme-slsc::interface.joinStepTwoCopy') }}</p>
                </div>
            </li>
            <li>
                <span class="join-step-number" aria-hidden="true">03</span>
                <div>
                    <h3 data-i18n="joinStepThree">{{ __('theme-slsc::interface.joinStepThree') }}</h3>
                    <p data-i18n="joinStepThreeCopy">{{ __('theme-slsc::interface.joinStepThreeCopy') }}</p>
                </div>
            </li>
        </ol>
    </div>
</section>

{{-- 9. Contact & Headquarters Directory --}}
<section id="contact" class="section-space contact-section" aria-labelledby="contactTitle">
    <div class="container contact-grid">
        <div class="contact-copy reveal">
            <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="footerContact">{{ __('theme-slsc::interface.footerContact') }}</span></p>
            <h2 id="contactTitle" class="section-title" data-i18n="contactTitle">{{ __('theme-slsc::interface.contactTitle') }}</h2>
            <div class="address-block">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-location"></use></svg>
                <div>
                    <h3 data-i18n="regimentalCentre">{{ __('theme-slsc::interface.regimentalCentre') }}</h3>
                    <p data-i18n="footerAddress">{{ __('theme-slsc::interface.footerAddress') }}</p>
                </div>
            </div>
            <a class="text-link" href="https://www.google.com/maps/search/?api=1&amp;query=Sri+Lanka+Signal+Corps+Regimental+Centre+Panagoda" target="_blank" rel="noopener noreferrer">
                <span data-i18n="openMap">{{ __('theme-slsc::interface.openMap') }}</span>
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
            </a>
        </div>
        <div class="portal-panel reveal">
            <p class="eyebrow" data-i18n="officialResources">{{ __('theme-slsc::interface.officialResources') }}</p>
            <h3 data-i18n="continueExploring">{{ __('theme-slsc::interface.continueExploring') }}</h3>
            <a href="https://alt.army.lk/signaller/" target="_blank" rel="noopener noreferrer">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-globe"></use></svg>
                <span data-i18n="signalPortal">{{ __('theme-slsc::interface.signalPortal') }}</span>
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
            </a>
            <a href="https://www.army.lk/" target="_blank" rel="noopener noreferrer">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-shield"></use></svg>
                <span data-i18n="armyPortal">{{ __('theme-slsc::interface.armyPortal') }}</span>
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
            </a>
            <a href="https://alt.army.lk/signaller/schoolofsignals/" target="_blank" rel="noopener noreferrer">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-book"></use></svg>
                <span data-i18n="schoolSignals">{{ __('theme-slsc::interface.schoolSignals') }}</span>
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-external"></use></svg>
            </a>
        </div>
    </div>
</section>
@endsection
