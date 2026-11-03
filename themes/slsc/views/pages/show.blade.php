@extends('theme-slsc::layout')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@if (is_string($canonicalUrl) && $canonicalUrl !== '')
    @section('canonical', $canonicalUrl)
@endif

@php
    $supportedThemeLocales = app(\App\Services\SiteLanguageService::class)->codes();
    $themeLanguageVersions = array_values(
        array_filter(
            $languageVersions,
            static fn(mixed $languageVersion): bool => is_array($languageVersion) &&
                isset($languageVersion['code']) &&
                is_string($languageVersion['code']) &&
                in_array($languageVersion['code'], $supportedThemeLocales, true),
        ),
    );
@endphp

@section('meta')
    <meta name="robots" content="{{ $robots }}">
    @foreach ($themeLanguageVersions as $languageVersion)
        @if (($languageVersion['available'] ?? false) && isset($languageVersion['url']) && is_string($languageVersion['url']))
            <link rel="alternate" hreflang="{{ $languageVersion['code'] }}" href="{{ $languageVersion['url'] }}">
        @endif
    @endforeach

    @if ($socialMetadata)
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ $ogUrl }}">
        @if (is_string($ogImage) && $ogImage !== '')
            <meta property="og:image" content="{{ $ogImage }}">
        @endif
        <meta name="twitter:card" content="{{ $twitterCard }}">
        <meta name="twitter:title" content="{{ $twitterTitle }}">
        <meta name="twitter:description" content="{{ $twitterDescription }}">
        @if (is_string($twitterImage) && $twitterImage !== '')
            <meta name="twitter:image" content="{{ $twitterImage }}">
        @endif
    @endif
@endsection

@push('styles')
    <x-page.custom-styles :page="$page" />
@endpush

@section('content')
    @php
        $contactPattern = '~<(div|p)\b[^>]*>\s*\[\[contact_page\]\]\s*</\1>~i';

        if (preg_match($contactPattern, $safeContent) === 1) {
            $contactWidget = view('theme-slsc::partials.cms-contact')->render();

            $contactRendered = false;

            $safeContent =
                preg_replace_callback(
                    $contactPattern,
                    static function (array $matches) use ($contactWidget, &$contactRendered): string {
                        if ($contactRendered) {
                            return '';
                        }

                        $contactRendered = true;

                        return $contactWidget;
                    },
                    $safeContent,
                ) ?? $safeContent;
        }
    @endphp
    <article class="theme-page">
        @if ($page->show_title || $page->excerpt)
            <header class="section-space section-muted" style="padding-top: 3rem; padding-bottom: 3rem;">
                <div class="container">
                    <p class="eyebrow"><span class="label-line" aria-hidden="true"></span>{{ __('public.school_information') }}</p>
                    @if ($page->show_title)
                        <h1 class="section-title" style="margin-top: 0.5rem;">{{ $page->title }}</h1>
                    @endif
                    @if ($page->excerpt)
                        <p class="section-subtitle">{{ $page->excerpt }}</p>
                    @endif
                </div>
            </header>
        @endif

        @if ($safeContent !== '')
            @if ($page->getRawOriginal('editor_mode') === \App\Enums\PageEditorMode::Visual->value)
                <section id="cms-page-{{ $page->getKey() }}" class="section-space">
                    <div class="container">
                        <div class="awcms-content" style="line-height: 1.8; color: var(--color-text, #1e293b);">
                            {!! $safeContent !!}
                        </div>
                    </div>
                </section>
            @else
                <div id="cms-page-{{ $page->getKey() }}" class="page-html-content">
                    {!! $safeContent !!}
                </div>
            @endif
        @endif

        @if ($pageBlocks !== [])
            <section class="section-space">
                <div class="container">
                    <x-page.blocks :blocks="$pageBlocks" />
                </div>
            </section>
        @endif
    </article>
@endsection

