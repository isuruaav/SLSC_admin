@extends('theme-slsc::layout')

@php
    $currentNewsLocale = $locale->value;

    $publicNewsIndexUrl =
        $currentNewsLocale === 'en'
            ? route('news.index')
            : route('news.index.localized', ['locale' => $currentNewsLocale]);

    $currentType ??= null;
    $listingTitle = match ($currentType) {
        'photo-story' => __('public.news_page.photo_stories'),
        'news-feature' => __('public.news_page.news_features'),
        default => __('public.news'),
    };
    $canonicalUrl = $currentType === null
        ? $publicNewsIndexUrl
        : $publicNewsIndexUrl.'?'.http_build_query(['type' => $currentType]);

    $publicNewsUrl = static function (\App\Models\News $article) use ($currentNewsLocale): string {
        return $currentNewsLocale === 'en'
            ? route('news.show', ['slug' => $article->slug])
            : route('news.show.localized', [
                'locale' => $currentNewsLocale,
                'slug' => $article->slug,
            ]);
    };
@endphp

@section('title', $listingTitle . ' | ' . config('app.name'))
@section('description', __('public.news_page.description'))
@section('meta_description', __('public.news_page.description'))
@section('canonical', $canonicalUrl)

@section('content')
    <div class="school-news-index-page">
        <header class="school-news-index-header">
            <div class="school-news-container school-news-index-header-inner">
                <div>
                    <span class="school-news-section-kicker">{{ __('theme-slsc::home.news.kicker') }}</span>
                    <h1>{{ $listingTitle }}</h1>
                    <p>{{ __('public.news_page.intro') }}</p>
                </div>
            </div>
        </header>

        <section class="school-news-listing">
            <div class="school-news-container">
                {{-- Type Filter Tabs --}}
                <nav class="news-type-filter-tabs" aria-label="{{ __('public.news_page.filter_label') }}">
                    <a href="{{ $publicNewsIndexUrl }}"
                        @class([
                            'news-filter-tab',
                            'is-active' => empty($currentType) || $currentType === 'all',
                        ]) @if ($currentType === null) aria-current="page" @endif>
                        <i class="fa-solid fa-list-ul" aria-hidden="true"></i>
                        <span>{{ __('public.news_page.all') }}</span>
                    </a>

                    <a href="{{ $publicNewsIndexUrl . (str_contains($publicNewsIndexUrl, '?') ? '&' : '?') . 'type=photo-story' }}"
                        @class([
                            'news-filter-tab',
                            'is-active' => $currentType === 'photo-story' || $currentType === 'photo_story',
                        ]) @if ($currentType === 'photo-story') aria-current="page" @endif>
                        <i class="fa-solid fa-camera-retro" aria-hidden="true"></i>
                        <span>{{ __('public.news_page.photo_stories') }}</span>
                    </a>

                    <a href="{{ $publicNewsIndexUrl . (str_contains($publicNewsIndexUrl, '?') ? '&' : '?') . 'type=news-feature' }}"
                        @class([
                            'news-filter-tab',
                            'is-active' => $currentType === 'news-feature' || $currentType === 'news_feature',
                        ]) @if ($currentType === 'news-feature') aria-current="page" @endif>
                        <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                        <span>{{ __('public.news_page.news_features') }}</span>
                    </a>
                </nav>

                @if ($news->isEmpty())
                    <div class="school-news-empty">
                        <i class="fa-regular fa-newspaper" aria-hidden="true"></i>
                        <h2>{{ __('public.news_page.empty_title') }}</h2>
                        <p>{{ __('public.news_page.empty', ['language' => $locale->nativeLabel()]) }}</p>
                    </div>
                @else
                    <div class="school-news-card-grid">
                        @foreach ($news as $article)
                            @php
                                $imageUrl = \App\Http\Controllers\PublicNewsController::imageUrl(
                                    $article->featuredImage,
                                );
                            @endphp

                            <article class="school-news-card">
                                @if (is_string($imageUrl) && $imageUrl !== '')
                                    <a href="{{ $publicNewsUrl($article) }}" class="school-news-card-image"
                                        aria-label="{{ __('public.news_page.read', ['title' => $article->title]) }}">
                                        <img src="{{ $imageUrl }}"
                                            alt="{{ $article->featuredImage?->alt_text ?: $article->title }}"
                                            loading="lazy">
                                    </a>
                                @else
                                    <a href="{{ $publicNewsUrl($article) }}"
                                        class="school-news-card-image school-news-card-placeholder"
                                        aria-label="{{ __('public.news_page.read', ['title' => $article->title]) }}">
                                        <i class="fa-regular fa-image" aria-hidden="true"></i>
                                    </a>
                                @endif

                                <div class="school-news-card-content">
                                    <div class="school-news-card-badges">
                                        @if ($article->isPhotoStory())
                                            <span class="school-news-category school-news-category--photo">
                                                <i class="fa-solid fa-camera-retro" aria-hidden="true"></i> {{ __('public.news_page.photo_stories') }}
                                            </span>
                                        @else
                                            <span class="school-news-category">
                                                <i class="fa-solid fa-newspaper" aria-hidden="true"></i> {{ __('public.news_page.news_features') }}
                                            </span>
                                        @endif

                                        @if ($article->category && ! in_array($article->category->slug, ['photo-story', 'news-feature'], true))
                                            <span class="school-news-category">
                                                {{ $article->category->name }}
                                            </span>
                                        @endif

                                        @if ($article->is_featured)
                                            <span class="school-news-featured">
                                                <i class="fa-solid fa-star" aria-hidden="true"></i>
                                                {{ __('public.news_page.featured') }}
                                            </span>
                                        @endif
                                    </div>

                                    <h2>
                                        <a href="{{ $publicNewsUrl($article) }}">
                                            {{ $article->title }}
                                        </a>
                                    </h2>

                                    @if ($article->summary)
                                        <p>{{ $article->summary }}</p>
                                    @endif

                                    <div class="school-news-card-footer">
                                        @if ($article->published_at)
                                            <time datetime="{{ $article->published_at->toIso8601String() }}">
                                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                                {{ $article->published_at->format('d M Y') }}
                                            </time>
                                        @endif

                                        <a href="{{ $publicNewsUrl($article) }}">
                                            {{ __('theme-slsc::home.news.read_more') }}
                                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($news->hasPages())
                        <div class="school-news-pagination">
                            {{ $news->onEachSide(1)->links('public.news.pagination') }}
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
@endsection
