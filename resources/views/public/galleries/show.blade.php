@php
    $activeTheme = config('awcms.active_theme');
    $galleryLayout = $activeTheme && view()->exists("theme-{$activeTheme}::layout")
        ? "theme-{$activeTheme}::layout" : 'layouts.public';
    $feed = app(\App\Services\PublicGalleryFeed::class);
    $isSinhala = app()->getLocale() === 'si';
    $displayTitle = $gallery->titleForLocale(app()->getLocale());
    $description = $isSinhala ? $displayTitle : ($gallery->seo_description ?: ($gallery->description ?: $displayTitle));
    $coverUrl = $feed->imageUrl($gallery->coverMedia);
@endphp

@extends($galleryLayout)

@section('title', ($isSinhala ? $displayTitle : ($gallery->seo_title ?: $displayTitle)).' | '.config('app.name'))
@section('description', $description)
@section('meta_description', $description)
@section('canonical', \App\Services\GalleryLocale::showUrl(app()->getLocale(), $gallery->slug))

@section('meta')
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $isSinhala ? $displayTitle : ($gallery->seo_title ?: $displayTitle) }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ \App\Services\GalleryLocale::showUrl(app()->getLocale(), $gallery->slug) }}">
    <meta name="twitter:card" content="summary_large_image">
    @if ($coverUrl)
        <meta property="og:image" content="{{ $coverUrl }}">
        <meta name="twitter:image" content="{{ $coverUrl }}">
    @endif
@endsection

@section('content')
    @include('public.galleries.styles')
    <div class="aw-gallery" lang="{{ app()->getLocale() }}">
        <header class="aw-gallery-hero">
            <div class="aw-gallery-container">
                <a class="aw-gallery-link" href="{{ \App\Services\GalleryLocale::indexUrl(app()->getLocale()) }}">← {{ $isSinhala ? 'ඡායාරූප ගැලරිය' : 'All albums' }}</a>
                <h1>{{ $displayTitle }}</h1>
                <p class="aw-gallery-card-meta">
                    @if ($gallery->event_date)
                        <time datetime="{{ $gallery->event_date->format('Y-m-d') }}">{{ $gallery->event_date->format('d M Y') }}</time>
                    @endif
                    <span>{{ $gallery->images->count() }} {{ $isSinhala ? 'ඡායාරූප' : 'photographs' }}</span>
                </p>
                @if ($gallery->description)
                    <p class="aw-gallery-description">{{ $gallery->description }}</p>
                @endif
            </div>
        </header>
        <section class="aw-gallery-main" aria-label="{{ $isSinhala ? 'ඡායාරූප' : 'Photographs' }}">
            <div class="aw-gallery-container">
                <p class="aw-gallery-hint">{{ $isSinhala ? 'විශාල ඡායාරූපය නව ටැබ් එකක විවෘත කිරීමට ඡායාරූපයක් තෝරන්න.' : 'Select a photograph to open the larger image in a new tab.' }}</p>
                <div class="aw-gallery-grid">
                @foreach ($gallery->images as $galleryImage)
                    @php
                        $imageUrl = $feed->imageUrl($galleryImage->media);
                        $originalUrl = $feed->imageUrl($galleryImage->media, true) ?: $imageUrl;
                    @endphp
                    @if ($imageUrl)
                        <figure class="aw-gallery-photo">
                            <a class="aw-gallery-cover" href="{{ $originalUrl }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $imageUrl }}" alt="{{ $galleryImage->alt_text ?: ($galleryImage->media?->alt_text ?: $displayTitle) }}" width="600" height="450" loading="lazy" decoding="async">
                            </a>
                            @if ($galleryImage->caption)
                                <figcaption>{{ $galleryImage->caption }}</figcaption>
                            @endif
                        </figure>
                    @endif
                @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
