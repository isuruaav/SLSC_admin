@php
    $activeTheme = config('awcms.active_theme');
    $publicLayout =
        $activeTheme && view()->exists("theme-{$activeTheme}::layout") ? "theme-{$activeTheme}::layout" : 'layouts.public';
@endphp

@extends($publicLayout)

@php
    $currentNewsLocale = $news->locale instanceof \BackedEnum ? $news->locale->value : (string) $news->locale;

    $publicNewsUrl =
        $currentNewsLocale === 'en'
            ? route('news.show', ['slug' => $news->slug])
            : route('news.show.localized', [
                'locale' => $currentNewsLocale,
                'slug' => $news->slug,
            ]);

    $publicNewsIndexUrl =
        $currentNewsLocale === 'en'
            ? route('news.index')
            : route('news.index.localized', [
                'locale' => $currentNewsLocale,
            ]);

    $relatedNewsUrl = static function (\App\Models\News $article) use ($currentNewsLocale): string {
        return $currentNewsLocale === 'en'
            ? route('news.show', ['slug' => $article->slug])
            : route('news.show.localized', [
                'locale' => $currentNewsLocale,
                'slug' => $article->slug,
            ]);
    };
@endphp

@section('title', ($news->seo_title ?: $news->title) . ' | ' . config('app.name'))
@section('description', $news->seo_description ?: ($news->summary ?: $news->title))
@section('meta_description', $news->seo_description ?: ($news->summary ?: $news->title))
@section('canonical', $publicNewsUrl)

@section('og_tags')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $news->seo_title ?: $news->title }}">
    <meta property="og:description" content="{{ $news->seo_description ?: ($news->summary ?: $news->title) }}">
    <meta property="og:url" content="{{ $publicNewsUrl }}">
    @if ($featuredImageUrl)
        <meta property="og:image" content="{{ $featuredImageUrl }}">
        <meta name="twitter:image" content="{{ $featuredImageUrl }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $news->seo_title ?: $news->title }}">
    <meta name="twitter:description" content="{{ $news->seo_description ?: ($news->summary ?: $news->title) }}">
@endsection

@section('meta')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $news->seo_title ?: $news->title }}">
    <meta property="og:description" content="{{ $news->seo_description ?: ($news->summary ?: $news->title) }}">
    <meta property="og:url" content="{{ $publicNewsUrl }}">
    @if ($featuredImageUrl)
        <meta property="og:image" content="{{ $featuredImageUrl }}">
    @endif
@endsection

@section('content')
    <article>
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ $publicNewsIndexUrl }}"
                        class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">← All News</a>

                    <nav class="flex flex-wrap gap-2" aria-label="Article language">
                        @foreach ($languageVersions as $version)
                            @if ($version['available'] && is_string($version['url']))
                                <a href="{{ $version['url'] }}"
                                    @class([
                                        'rounded-full px-3 py-1.5 text-xs font-bold',
                                        'bg-emerald-700 text-white' => $version['active'],
                                        'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' => !$version[
                                            'active'
                                        ],
                                    ])>{{ $version['native_label'] }}</a>
                            @else
                                <span
                                    class="cursor-not-allowed rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-bold text-zinc-300">{{ $version['native_label'] }}</span>
                            @endif
                        @endforeach
                    </nav>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3 text-sm text-zinc-500">
                    @if ($news->category)
                        <span
                            class="rounded-full bg-sky-50 px-3 py-1 font-semibold text-sky-700">{{ $news->category->name }}</span>
                    @endif
                    @if ($news->is_featured)
                        <span class="rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-700">Featured</span>
                    @endif
                    <span>{{ $news->published_at?->format('d M Y H:i') }}</span>
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight text-zinc-950 sm:text-4xl lg:text-5xl">
                    {{ $news->title }}</h1>

                @if ($news->summary)
                    <p class="mt-5 text-lg leading-8 text-zinc-600">{{ $news->summary }}</p>
                @endif
            </div>
        </header>

        @if ($safeContent !== '')
            @if ($news->editor_mode === \App\Enums\NewsEditorMode::Visual)
                <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                    <div class="awcms-content news-article-content">{!! $safeContent !!}</div>
                </div>
            @else
                <div class="page-html-content">{!! $safeContent !!}</div>
            @endif
        @endif

        @if ($news->images->isNotEmpty())
            <section class="mx-auto max-w-6xl px-4 pb-14 sm:px-6 lg:px-8 lg:pb-16">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($news->images as $newsImage)
                        @php
                            $galleryImageUrl = \App\Http\Controllers\PublicNewsController::imageUrl($newsImage->media);

                            $galleryAlt = $newsImage->media?->alt_text ?: $news->title;
                        @endphp

                        @if ($galleryImageUrl)
                            <figure class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                                <a href="{{ $galleryImageUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="block overflow-hidden bg-zinc-100" style="aspect-ratio: 4 / 3;">
                                    <img src="{{ $galleryImageUrl }}" alt="{{ $galleryAlt }}" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                </a>
                            </figure>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    </article>

    @if ($relatedNews->isNotEmpty())
        <section class="border-t border-zinc-200 bg-zinc-50">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-zinc-950">Related News</h2>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    @foreach ($relatedNews as $related)
                        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                            <p class="text-xs text-zinc-500">{{ $related->published_at?->format('d M Y') }}</p>
                            <h3 class="mt-2 font-bold leading-6 text-zinc-950">
                                <a href="{{ $relatedNewsUrl($related) }}"
                                    class="hover:text-emerald-700">{{ $related->title }}</a>
                            </h3>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
