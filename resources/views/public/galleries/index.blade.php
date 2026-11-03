@php
    $activeTheme = config('awcms.active_theme');
    $galleryLayout = $activeTheme && view()->exists("theme-{$activeTheme}::layout")
        ? "theme-{$activeTheme}::layout" : 'layouts.public';
    $feed = app(\App\Services\PublicGalleryFeed::class);
    $isSinhala = app()->getLocale() === 'si';
    $galleryTitle = $isSinhala ? 'ඡායාරූප ගැලරිය' : 'Photo Gallery';
@endphp

@extends($galleryLayout)

@section('title', $galleryTitle.' | '.config('app.name'))
@section('description', $isSinhala ? 'පුවත්, උත්සව සහ ක්‍රියාකාරකම්වල ඡායාරූප එකතුව.' : 'Photographs from our news, events and activities.')
@section('meta_description', $isSinhala ? 'පුවත්, උත්සව සහ ක්‍රියාකාරකම්වල ඡායාරූප එකතුව.' : 'Photographs from our news, events and activities.')
@section('canonical', \App\Services\GalleryLocale::indexUrl(app()->getLocale()))

@section('content')
    @include('public.galleries.styles')

    <div class="aw-gallery aw-gallery-index" lang="{{ app()->getLocale() }}">
        <header class="aw-gallery-hero">
            <div class="aw-gallery-container">
                <p class="aw-gallery-kicker">
                    {{ $isSinhala ? 'අපගේ මතක සටහන්' : 'Our moments' }}
                </p>
                <h1>{{ $galleryTitle }}</h1>
                <p class="aw-gallery-description">
                    {{ $isSinhala ? 'පුවත්, උත්සව සහ ක්‍රියාකාරකම්වල ඡායාරූප එකතුව.' : 'Explore photographs from our news, events and activities.' }}
                </p>
            </div>
        </header>

        <section class="aw-gallery-main" aria-label="{{ $galleryTitle }}">
            <div class="aw-gallery-container">
                @if ($albums->isNotEmpty())
                    <div class="aw-gallery-grid aw-gallery-albums">
                        @foreach ($albums as $album)
                            @php
                                $isNews = $album instanceof \App\Models\News;
                                $albumUrl = $isNews
                                    ? \App\Services\GalleryLocale::newsUrl(app()->getLocale(), $album->id)
                                    : \App\Services\GalleryLocale::showUrl(app()->getLocale(), $album->slug);
                                $albumTitle = $isNews ? $album->title : $album->titleForLocale(app()->getLocale());
                                $cover = $isNews ? $album->images->first()?->media : $album->coverMedia;
                                $coverUrl = $feed->imageUrl($cover);
                            @endphp

                            <article class="aw-gallery-card">
                                <a href="{{ $albumUrl }}" aria-label="{{ $albumTitle }}" class="aw-gallery-cover">
                                    @if ($coverUrl)
                                        <img src="{{ $coverUrl }}" alt="{{ $cover?->alt_text ?: $albumTitle }}"
                                            loading="lazy" decoding="async" width="600" height="450">
                                    @else
                                        <span class="aw-gallery-placeholder">{{ $isSinhala ? 'ඡායාරූප එකතුව' : 'View photographs' }}</span>
                                    @endif
                                </a>

                                <div class="aw-gallery-card-body">
                                    <div class="aw-gallery-card-meta">
                                        <span class="aw-gallery-album-type">
                                            {{ $isNews ? ($isSinhala ? 'පුවත්' : 'News') : ($isSinhala ? 'ගැලරිය' : 'Gallery') }}
                                        </span>
                                        @if ($album->published_at)
                                            <time datetime="{{ $album->published_at->toIso8601String() }}">
                                                {{ $album->published_at->format('d M Y') }}
                                            </time>
                                        @endif
                                    </div>
                                    <h2><a href="{{ $albumUrl }}">{{ $albumTitle }}</a></h2>
                                    <a href="{{ $albumUrl }}" class="aw-gallery-link">
                                        <span>{{ $isSinhala ? 'ඡායාරූප බලන්න' : 'View album' }}</span>
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="aw-gallery-empty">
                        <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="5" y="8" width="38" height="32" rx="5"/>
                            <circle cx="16" cy="18" r="4"/>
                            <path d="m7 35 11-10 7 6 7-10 10 14"/>
                        </svg>
                        <h2>
                            {{ $isSinhala ? 'දැනට ඡායාරූප එකතු නොමැත' : 'No albums to display' }}
                        </h2>
                        <p>
                            {{ $isSinhala ? 'මෙම පිටුවේ පෙන්වීමට ඡායාරූප එකතු නොමැත. නව ඡායාරූප සඳහා නැවත පිවිසෙන්න.' : 'There are no albums on this page. Check back soon for new photographs.' }}
                        </p>
                        @if ($albums->currentPage() > 1)
                            <a href="{{ \App\Services\GalleryLocale::indexUrl(app()->getLocale()) }}" class="aw-gallery-link">
                                ← {{ $isSinhala ? 'පළමු පිටුවට යන්න' : 'Go to the first page' }}
                            </a>
                        @else
                            <a href="{{ app()->getLocale() === 'en' ? route('news.index') : route('news.index.localized', ['locale' => app()->getLocale()]) }}" class="aw-gallery-link">
                                {{ $isSinhala ? 'පුවත් බලන්න' : 'Browse news' }} →
                            </a>
                        @endif
                    </div>
                @endif

                @if ($albums->hasPages() && $albums->currentPage() <= $albums->lastPage())
                    <div class="aw-gallery-pagination">
                        {{ $albums->onEachSide(1)->links('public.galleries.pagination') }}
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
