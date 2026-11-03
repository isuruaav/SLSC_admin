@extends(($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout") ? "theme-{$activeTheme}::layout" : 'layouts.public')

@php
    $isSinhala = $currentLocale === 'si';
    $heading = $isSinhala ? 'ප්‍රකාශන' : 'Publications';
@endphp

@section('title', $heading.' | '.config('app.name'))
@section('meta_description', $isSinhala ? 'සංඥා පාසලේ නවතම ප්‍රකාශන හා සඟරා කියවන්න.' : 'Read the latest journals, reports and publications from the School of Signals.')
@section('canonical', \App\Models\Publication::indexUrl($currentLocale))

@push('styles')
    <style>
        /* Publications listing only: heading uses public.galleries.styles. */
        .publication-page {
            min-height: 65vh;
            padding-bottom: 72px;
            background: #f6f8f6;
        }
        .publication-shell {
            width: min(1240px, calc(100% - 40px));
            margin-inline: auto;
        }
        .publication-listing { padding-top: 36px; }
        .publication-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 24px;
            align-items: start;
        }
        .publication-card { min-width: 0; }
        .publication-cover {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            aspect-ratio: 3 / 4;
            background: #fff;
            border: 1px solid #dbe3dd;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 20px #143a2510;
            transition: transform .2s, box-shadow .2s;
        }
        .publication-cover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 26px #143a2520;
        }
        .publication-cover:focus-visible,
        .publication-card h2 a:focus-visible,
        .publication-read:focus-visible {
            outline: 3px solid #b29148;
            outline-offset: 4px;
        }
        .publication-cover img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #fff;
        }
        .publication-pdf-badge {
            position: absolute;
            right: 10px;
            bottom: 10px;
            background: #173d2e;
            color: #fff;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
        }
        .publication-card h2 {
            font-size: 16px;
            line-height: 1.5;
            margin: 16px 0 8px;
            overflow-wrap: anywhere;
        }
        .publication-card h2 a {
            color: #173d2e;
            text-decoration: none;
        }
        .publication-date {
            display: block;
            font-size: 12px;
            color: #718176;
            margin-bottom: 12px;
        }
        .publication-read {
            font-size: 13px;
            font-weight: 700;
            color: #145c45;
            text-decoration: none;
        }
        .publication-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 22px;
            margin-top: 42px;
            font-size: 14px;
        }
        .publication-pagination a {
            color: #145c45;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #cfdbd1;
            border-radius: 7px;
            padding: 9px 14px;
            background: #fff;
        }
        .publication-pagination span { color: #6b7a70; }
        .publication-empty {
            padding: 64px 24px;
            text-align: center;
            background: #fff;
            border: 1px solid #dce5de;
            border-radius: 14px;
        }
        .publication-empty h2 {
            font-size: 22px;
            color: #234432;
            margin-bottom: 12px;
        }
        .publication-empty p { color: #718176; line-height: 1.7; }

        @media (max-width: 1000px) {
            .publication-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .publication-shell { width: calc(100% - 32px); }
            .publication-listing { padding-top: 28px; }
            .publication-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 24px 16px;
            }
            .publication-card h2 { font-size: 15px; }
        }
        @media (max-width: 360px) {
            .publication-grid { grid-template-columns: minmax(0, 1fr); }
            .publication-card { max-width: 250px; margin-inline: auto; width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            .publication-cover { transition: none; }
        }
    </style>
@endpush

@section('content')
    {{-- Reuse the same hero design as the Gallery index. --}}
    @include('public.galleries.styles')

    <section class="publication-page" lang="{{ $currentLocale }}" aria-labelledby="publications-title">
        <div class="aw-gallery aw-gallery-index">
            <header class="aw-gallery-hero">
                <div class="aw-gallery-container">
                    <p class="aw-gallery-kicker">
                        {{ $isSinhala ? 'අපගේ ප්‍රකාශන' : 'Explore our library' }}
                    </p>
                    <h1 id="publications-title">{{ $heading }}</h1>
                    <p class="aw-gallery-description">
                        {{ $isSinhala ? 'පාසලේ නවතම ප්‍රකාශන හා සඟරා. PDF ගොනුව කියවීමට කවරය මත ක්ලික් කරන්න.' : 'Explore our latest journals and publications. Select a cover to read the PDF.' }}
                    </p>
                </div>
            </header>
        </div>

        <div class="publication-shell">
            <div class="publication-listing">
                @if ($publications->isNotEmpty())
                    <div class="publication-grid">
                        @foreach ($publications as $publication)
                            @php($pdfUrl = route('publications.pdf', ['publication' => $publication->uuid]))
                            <article class="publication-card">
                                <a class="publication-cover" href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $publication->titleForLocale($currentLocale) }} — PDF">
                                    <img src="{{ route('publications.cover', ['publication' => $publication->uuid]) }}"
                                         alt="{{ $publication->titleForLocale($currentLocale) }}"
                                         width="{{ $publication->cover_width }}"
                                         height="{{ $publication->cover_height }}"
                                         loading="lazy" decoding="async">
                                    <span class="publication-pdf-badge" aria-hidden="true">PDF ↗</span>
                                </a>
                                <h2>
                                    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer">
                                        {{ $publication->titleForLocale($currentLocale) }}
                                    </a>
                                </h2>
                                <time class="publication-date" datetime="{{ $publication->published_at->toDateString() }}">
                                    {{ $publication->published_at->format('d M Y') }}
                                </time>
                                <a class="publication-read" href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer">
                                    {{ $isSinhala ? 'PDF කියවන්න' : 'Read PDF' }}
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="publication-empty">
                        <h2>{{ $isSinhala ? 'දැනට ප්‍රකාශන නොමැත' : 'No publications to display' }}</h2>
                        <p>{{ $isSinhala ? 'නවතම ප්‍රකාශන සඳහා නැවත පිවිසෙන්න.' : 'Check back soon for our latest publications.' }}</p>
                        @if ($publications->currentPage() > 1)
                            <a class="publication-read" href="{{ \App\Models\Publication::indexUrl($currentLocale) }}">
                                {{ $isSinhala ? 'පළමු පිටුවට යන්න' : 'Go to the first page' }}
                            </a>
                        @endif
                    </div>
                @endif

                @if ($publications->hasPages())
                    <nav class="publication-pagination" aria-label="{{ $isSinhala ? 'ප්‍රකාශන පිටු' : 'Publication pages' }}">
                        @if ($publications->previousPageUrl())
                            <a href="{{ $publications->previousPageUrl() }}" rel="prev">← {{ $isSinhala ? 'පෙර' : 'Previous' }}</a>
                        @endif
                        <span>{{ $publications->currentPage() }} / {{ $publications->lastPage() }}</span>
                        @if ($publications->nextPageUrl())
                            <a href="{{ $publications->nextPageUrl() }}" rel="next">{{ $isSinhala ? 'ඊළඟ' : 'Next' }} →</a>
                        @endif
                    </nav>
                @endif
            </div>
        </div>
    </section>
@endsection
