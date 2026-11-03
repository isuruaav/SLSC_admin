@extends(($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout") ? "theme-{$activeTheme}::layout" : 'layouts.public')

@php
    $currentNewsLocale = $locale->value;

    $publicNewsIndexUrl = $currentNewsLocale === 'en'
        ? route('news.index')
        : route('news.index.localized', ['locale' => $currentNewsLocale]);

    $publicNewsUrl = static function (\App\Models\News $article) use ($currentNewsLocale): string {
        return $currentNewsLocale === 'en'
            ? route('news.show', ['slug' => $article->slug])
            : route('news.show.localized', [
                'locale' => $currentNewsLocale,
                'slug' => $article->slug,
            ]);
    };
@endphp

@section('title', 'News | '.config('app.name'))
@section('description', 'Latest published news and official updates.')
@section('meta_description', 'Latest published news and official updates.')
@section('canonical', $publicNewsIndexUrl)

@section('content')
    <main class="school-news-index-page">
        <header class="school-news-index-header">
            <div class="school-news-container school-news-index-header-inner">
                <div>
                    <span class="school-news-section-kicker">Latest Updates</span>
                    <h1>News</h1>
                    <p>Latest published news, announcements and official updates.</p>
                </div>

                <nav class="school-news-languages" aria-label="News language">
                    @foreach ($locales as $localeOption)
                        <a
                            href="{{ $localeOption === \App\Enums\NewsLocale::English
                                ? route('news.index')
                                : route('news.index.localized', ['locale' => $localeOption->value]) }}"
                            hreflang="{{ $localeOption->value }}"
                            @class([
                                'school-news-language',
                                'active' => $localeOption === $locale,
                            ])
                        >
                            {{ $localeOption->nativeLabel() }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        <section class="school-news-listing">
            <div class="school-news-container">
                @if ($news->isEmpty())
                    <div class="school-news-empty">
                        <i class="fa-regular fa-newspaper" aria-hidden="true"></i>
                        <h2>No News Available</h2>
                        <p>No published news is available in {{ $locale->nativeLabel() }}.</p>
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
                                    <a
                                        href="{{ $publicNewsUrl($article) }}"
                                        class="school-news-card-image"
                                        aria-label="Read {{ $article->title }}"
                                    >
                                        <img
                                            src="{{ $imageUrl }}"
                                            alt="{{ $article->featuredImage?->alt_text ?: $article->title }}"
                                            loading="lazy"
                                        >
                                    </a>
                                @else
                                    <a
                                        href="{{ $publicNewsUrl($article) }}"
                                        class="school-news-card-image school-news-card-placeholder"
                                        aria-label="Read {{ $article->title }}"
                                    >
                                        <i class="fa-regular fa-image" aria-hidden="true"></i>
                                    </a>
                                @endif

                                <div class="school-news-card-content">
                                    <div class="school-news-card-badges">
                                        @if ($article->category)
                                            <span class="school-news-category">
                                                {{ $article->category->name }}
                                            </span>
                                        @endif

                                        @if ($article->is_featured)
                                            <span class="school-news-featured">
                                                <i class="fa-solid fa-star" aria-hidden="true"></i>
                                                Featured
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
                                            Read More
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
    </main>
@endsection
