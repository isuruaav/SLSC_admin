@extends('theme-school-of-signals::layout')

@php
    $currentNewsLocale = $news->locale instanceof \BackedEnum ? $news->locale->value : (string) $news->locale;

    $articleUrl = static function (\App\Models\News $article) use ($currentNewsLocale): string {
        return $currentNewsLocale === 'en'
            ? route('news.show', ['slug' => $article->slug])
            : route('news.show.localized', [
                'locale' => $currentNewsLocale,
                'slug' => $article->slug,
            ]);
    };

    $indexUrl =
        $currentNewsLocale === 'en'
            ? route('news.index')
            : route('news.index.localized', [
                'locale' => $currentNewsLocale,
            ]);

    $allowedLocales = app(\App\Services\SiteLanguageService::class)->codes();

    $description = $news->seo_description ?: ($news->summary ?: $news->title);

    $galleryItems = $news->images
        ->map(function ($newsImage) use ($news): ?array {
            $url = \App\Http\Controllers\PublicNewsController::imageUrl($newsImage->media);

            if (!is_string($url) || $url === '') {
                return null;
            }

            return [
                'url' => $url,
                'alt' => $newsImage->media?->alt_text ?: $news->title,
                'caption' => $newsImage->media?->caption,
            ];
        })
        ->filter()
        ->values();
@endphp

@section('title', ($news->seo_title ?: $news->title) . ' | ' . config('app.name'))
@section('description', $description)
@section('meta_description', $description)
@section('canonical', $articleUrl($news))

@section('meta')
    @foreach ($languageVersions as $languageVersion)
        @if (($languageVersion['available'] ?? false) && in_array($languageVersion['code'] ?? '', $allowedLocales, true) && is_string($languageVersion['url'] ?? null))
            <link rel="alternate" hreflang="{{ $languageVersion['code'] }}" href="{{ $languageVersion['url'] }}">
        @endif
    @endforeach

@endsection

@section('og_tags')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $news->seo_title ?: $news->title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $articleUrl($news) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $news->seo_title ?: $news->title }}">
    <meta name="twitter:description" content="{{ $description }}">

    @if ($featuredImageUrl)
        <meta property="og:image" content="{{ $featuredImageUrl }}">
        <meta name="twitter:image" content="{{ $featuredImageUrl }}">
    @endif
@endsection

@section('content')
    <div class="sos-article-page">
        <article>
            <header class="sos-article-header">
                <div class="sos-article-container">
                    <div class="sos-article-toolbar">
                        <a class="sos-article-back" href="{{ $indexUrl }}">
                            ← {{ __('theme-school-of-signals::home.news.all') }}
                        </a>
                    </div>

                    <div class="sos-article-meta">
                        @if ($news->category)
                            <span class="sos-article-category">
                                {{ $news->category->name }}
                            </span>
                        @endif

                        @if ($news->is_featured)
                            <span class="sos-article-featured">{{ __('public.news_page.featured') }}</span>
                        @endif

                        @if ($news->published_at)
                            <time datetime="{{ $news->published_at->toIso8601String() }}">
                                {{ $news->published_at->format('d M Y H:i') }}
                            </time>
                        @endif
                    </div>

                    <h1 class="sos-article-title">{{ $news->title }}</h1>

                    {{--
                    @if ($news->summary)
                        <p class="sos-article-summary">{{ $news->summary }}</p>
                    @endif
                    --}}
                </div>
            </header>

            @if ($safeContent !== '')
                <div class="sos-article-body">
                    <div class="sos-article-container">
                        <div @class([
                            'sos-article-content',
                            'awcms-content' => $news->editor_mode === \App\Enums\NewsEditorMode::Visual,
                            'page-html-content' =>
                                $news->editor_mode !== \App\Enums\NewsEditorMode::Visual,
                        ])>
                            {!! $safeContent !!}
                        </div>
                    </div>
                </div>
            @endif

            @if ($galleryItems->isNotEmpty())
                <section class="sos-article-gallery-section" aria-labelledby="article-gallery-title" data-news-gallery>
                    <div class="sos-article-container">
                        <h2 id="article-gallery-title">
                            {{ __('public.news_page.event_photos') }}
                        </h2>

                        <div class="sos-article-gallery">
                            @foreach ($galleryItems as $photo)
                                <figure class="sos-article-photo">
                                    <button type="button" class="sos-article-photo-button" data-gallery-open
                                        data-gallery-index="{{ $loop->index }}" data-full-url="{{ $photo['url'] }}"
                                        data-alt="{{ $photo['alt'] }}" data-caption="{{ $photo['caption'] ?? '' }}"
                                        aria-label="{{ __('public.news_page.open_photo', ['number' => $loop->iteration]) }}">
                                        <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy">

                                        <span class="sos-photo-zoom-icon">
                                            <i class="fa-solid fa-expand" aria-hidden="true"></i>
                                        </span>
                                    </button>

                                    @if (is_string($photo['caption']) && $photo['caption'] !== '')
                                        <figcaption>
                                            {{ $photo['caption'] }}
                                        </figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>

                    <div class="sos-lightbox" data-gallery-lightbox role="dialog" aria-modal="true"
                        aria-label="{{ __('public.news_page.photo_viewer') }}" hidden>
                        <button type="button" class="sos-lightbox-close" data-gallery-close aria-label="{{ __('public.news_page.close_photos') }}">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>

                        @if ($galleryItems->count() > 1)
                            <button type="button" class="sos-lightbox-arrow sos-lightbox-prev" data-gallery-prev
                                aria-label="{{ __('public.news_page.previous_photo') }}">
                                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                            </button>
                        @endif

                        <div class="sos-lightbox-stage">
                            <figure class="sos-lightbox-figure">
                                <img src="" alt="" data-gallery-image>

                                <figcaption data-gallery-caption hidden></figcaption>
                            </figure>
                        </div>

                        @if ($galleryItems->count() > 1)
                            <button type="button" class="sos-lightbox-arrow sos-lightbox-next" data-gallery-next
                                aria-label="{{ __('public.news_page.next_photo') }}">
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        @endif

                        <div class="sos-lightbox-counter">
                            <span data-gallery-current>1</span>
                            /
                            <span data-gallery-total>{{ $galleryItems->count() }}</span>
                        </div>
                    </div>
                </section>
            @endif
        </article>

        @if ($relatedNews->isNotEmpty())
            <section class="sos-article-related" aria-labelledby="related-articles-title" data-related-carousel>
                <div class="sos-article-container">

                    <div class="sos-related-heading">
                        <div>
                            <p class="sos-related-eyebrow">
                                {{ __('public.news_page.continue_reading') }}
                            </p>

                            <h2 id="related-articles-title">
                                {{ __('public.news_page.related') }}
                            </h2>
                        </div>

                        <a href="{{ $indexUrl }}" class="sos-related-view-all">
                            {{ __('public.news_page.view_all') }}

                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="sos-related-carousel">

                        <button type="button" class="sos-related-arrow sos-related-arrow-left" data-related-prev
                            aria-label="{{ __('public.news_page.previous_related') }}">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </button>

                        <div class="sos-related-carousel-track" data-related-track>
                            @foreach ($relatedNews as $related)
                                <article class="sos-article-related-card sos-related-slide">

                                    @if ($related->published_at)
                                        <time datetime="{{ $related->published_at->toIso8601String() }}">
                                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>

                                            {{ $related->published_at->format('d M Y') }}
                                        </time>
                                    @endif

                                    <h3>
                                        <a href="{{ $articleUrl($related) }}">
                                            {{ $related->title }}
                                        </a>
                                    </h3>

                                    @if ($related->summary)
                                        <p class="sos-related-summary">
                                            {{ \Illuminate\Support\Str::limit($related->summary, 120) }}
                                        </p>
                                    @endif

                                    <a class="sos-article-back sos-related-read-more" href="{{ $articleUrl($related) }}">
                                        {{ __('public.news_page.read_article') }}

                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>

                                </article>
                            @endforeach
                        </div>

                        <button type="button" class="sos-related-arrow sos-related-arrow-right" data-related-next
                            aria-label="{{ __('public.news_page.next_related') }}">
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>

                    </div>
                </div>
            </section>
        @endif
    </div>


    @push('scripts')
        <script>
            (() => {
                const initialiseNewsPage = () => {

                    /*
                     * ==========================================================
                     * Event Photograph Lightbox
                     * ==========================================================
                     */
                    document.querySelectorAll('[data-news-gallery]').forEach((gallery) => {
                        const buttons = Array.from(
                            gallery.querySelectorAll('[data-gallery-open]')
                        );

                        const lightbox = gallery.querySelector('[data-gallery-lightbox]');
                        const image = gallery.querySelector('[data-gallery-image]');
                        const caption = gallery.querySelector('[data-gallery-caption]');
                        const currentLabel = gallery.querySelector('[data-gallery-current]');

                        const closeButton = gallery.querySelector('[data-gallery-close]');
                        const previousButton = gallery.querySelector('[data-gallery-prev]');
                        const nextButton = gallery.querySelector('[data-gallery-next]');

                        if (
                            buttons.length === 0 ||
                            !lightbox ||
                            !image
                        ) {
                            return;
                        }

                        let currentIndex = 0;

                        const render = () => {
                            const button = buttons[currentIndex];

                            if (!button) {
                                return;
                            }

                            image.src = button.dataset.fullUrl || '';
                            image.alt = button.dataset.alt || '';

                            if (currentLabel) {
                                currentLabel.textContent = String(currentIndex + 1);
                            }

                            const captionText =
                                (button.dataset.caption || '').trim();

                            if (caption) {
                                caption.textContent = captionText;
                                caption.hidden = captionText === '';
                            }
                        };

                        const open = (index) => {
                            currentIndex = index;

                            render();

                            lightbox.hidden = false;
                            document.body.style.overflow = 'hidden';

                            closeButton?.focus();
                        };

                        const close = () => {
                            lightbox.hidden = true;
                            document.body.style.overflow = '';

                            buttons[currentIndex]?.focus();
                        };

                        const next = () => {
                            currentIndex =
                                (currentIndex + 1) % buttons.length;

                            render();
                        };

                        const previous = () => {
                            currentIndex =
                                (currentIndex - 1 + buttons.length) % buttons.length;

                            render();
                        };

                        buttons.forEach((button, index) => {
                            button.addEventListener('click', () => {
                                open(index);
                            });
                        });

                        closeButton?.addEventListener('click', close);
                        nextButton?.addEventListener('click', next);
                        previousButton?.addEventListener('click', previous);

                        lightbox.addEventListener('click', (event) => {
                            if (event.target === lightbox) {
                                close();
                            }
                        });

                        document.addEventListener('keydown', (event) => {
                            if (lightbox.hidden) {
                                return;
                            }

                            if (event.key === 'Escape') {
                                close();
                            }

                            if (event.key === 'ArrowRight') {
                                next();
                            }

                            if (event.key === 'ArrowLeft') {
                                previous();
                            }
                        });
                    });


                    /*
                     * ==========================================================
                     * Related News Carousel
                     * ==========================================================
                     */
                    document.querySelectorAll('[data-related-carousel]').forEach((carousel) => {
                        const track =
                            carousel.querySelector('[data-related-track]');

                        const previousButton =
                            carousel.querySelector('[data-related-prev]');

                        const nextButton =
                            carousel.querySelector('[data-related-next]');

                        if (!track) {
                            return;
                        }

                        const scroll = (direction) => {
                            const firstCard =
                                track.querySelector('.sos-related-slide');

                            if (!firstCard) {
                                return;
                            }

                            const trackStyle =
                                window.getComputedStyle(track);

                            const gap =
                                Number.parseFloat(trackStyle.columnGap || trackStyle.gap) || 20;

                            const cardWidth =
                                firstCard.getBoundingClientRect().width;

                            track.scrollBy({
                                left: direction * (cardWidth + gap),
                                behavior: 'smooth',
                            });
                        };

                        previousButton?.addEventListener('click', () => {
                            scroll(-1);
                        });

                        nextButton?.addEventListener('click', () => {
                            scroll(1);
                        });
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener(
                        'DOMContentLoaded',
                        initialiseNewsPage, {
                            once: true
                        },
                    );
                } else {
                    initialiseNewsPage();
                }
            })();
        </script>
    @endpush
@endsection
