@php
    $activeTheme = config('awcms.active_theme');
    $galleryLayout = $activeTheme && view()->exists("theme-{$activeTheme}::layout")
        ? "theme-{$activeTheme}::layout" : 'layouts.public';
    $feed = app(\App\Services\PublicGalleryFeed::class);
    $isSinhala = $news->getRawOriginal('locale') === 'si';
    $newsLocale = $news->locale instanceof \BackedEnum ? $news->locale->value : (string) $news->locale;
    $newsUrl = $newsLocale === 'en' ? route('news.show', ['slug' => $news->slug])
        : route('news.show.localized', ['locale' => $newsLocale, 'slug' => $news->slug]);
@endphp

@extends($galleryLayout)

@section('title', $news->title.' | '.config('app.name'))
@section('description', $news->summary ?: $news->title)
@section('meta_description', $news->summary ?: $news->title)
@section('canonical', \App\Services\GalleryLocale::newsUrl(app()->getLocale(), $news->id))

@section('content')
    @include('public.galleries.styles')
    <div class="aw-gallery" lang="{{ app()->getLocale() }}">
        <header class="aw-gallery-hero">
            <div class="aw-gallery-container">
                <a class="aw-gallery-link" href="{{ \App\Services\GalleryLocale::indexUrl(app()->getLocale()) }}">← {{ $isSinhala ? 'ඡායාරූප ගැලරිය' : 'All albums' }}</a>
                <h1>{{ $news->title }}</h1>
                <p class="aw-gallery-card-meta">
                    <time datetime="{{ $news->published_at?->toIso8601String() }}">{{ $news->published_at?->format('d M Y') }}</time>
                    <span>{{ $news->images->count() }} {{ $isSinhala ? 'ඡායාරූප' : 'photographs' }}</span>
                    <a class="aw-gallery-link" href="{{ $newsUrl }}">{{ $isSinhala ? 'පුවත කියවන්න' : 'Read news article' }} →</a>
                </p>
            </div>
        </header>
        <section class="aw-gallery-main" aria-label="{{ $isSinhala ? 'ඡායාරූප' : 'Photographs' }}">
            <div class="aw-gallery-container">
                <p class="aw-gallery-hint">{{ $isSinhala ? 'විශාල ඡායාරූපය නව ටැබ් එකක විවෘත කිරීමට ඡායාරූපයක් තෝරන්න.' : 'Select a photograph to open the larger image in a new tab.' }}</p>
                <div class="aw-gallery-grid">
                @foreach ($news->images as $newsImage)
                    @php
                        $imageUrl = $feed->imageUrl($newsImage->media);
                        $originalUrl = $feed->imageUrl($newsImage->media, true) ?: $imageUrl;
                    @endphp
                    @if ($imageUrl)
                        <figure class="aw-gallery-photo">
                            <a class="aw-gallery-cover" href="{{ $originalUrl }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $imageUrl }}" alt="{{ $newsImage->media?->alt_text ?: $news->title }}" width="600" height="450" loading="lazy" decoding="async">
                            </a>
                            @if ($newsImage->media?->caption)
                                <figcaption>{{ $newsImage->media->caption }}</figcaption>
                            @endif
                        </figure>
                    @endif
                @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
