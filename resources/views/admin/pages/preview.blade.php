@php
    $activeTheme = config('awcms.active_theme');
    $themeLayout = $activeTheme ? "theme-{$activeTheme}::layout" : null;
    $previewLayout = ($themeLayout && view()->exists($themeLayout))
        ? $themeLayout
        : (view()->exists('theme-slsc::layout') ? 'theme-slsc::layout' : 'layouts.public');
    $currentLocale = app()->getLocale();
@endphp

@extends($previewLayout)

@section('title', $pageTitle)
@section('description', $metaDescription)
@section('meta_description', $metaDescription)

@section('meta')
    <meta name="robots" content="{{ $robots }}">
@endsection

@push('styles')
    <x-page.custom-styles :page="$page" />
@endpush

@section('content')
    {{-- Modern SLSC Administrator Preview Top Bar --}}
    <div class="admin-preview-bar">
        <div class="admin-preview-container">
            <div class="admin-preview-info">
                <span class="admin-preview-icon" aria-hidden="true">
                    <i class="fa-solid fa-shield-halved"></i>
                </span>
                <div>
                    <p class="admin-preview-title">Administrator Preview Mode</p>
                    <p class="admin-preview-desc">Viewing live page layout and content preview</p>
                </div>
            </div>

            <div class="admin-preview-actions">
                <span class="admin-preview-status {{ $page->status === \App\Enums\PageStatus::Published ? 'status-published' : 'status-draft' }}">
                    <i class="fa-solid {{ $page->status === \App\Enums\PageStatus::Published ? 'fa-circle-check' : 'fa-clock' }}"></i>
                    {{ $page->status->label() }}
                </span>

                <a href="{{ route('admin.pages.edit', $page) }}" class="admin-preview-btn admin-preview-btn-edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit Page</span>
                </a>

                @if ($page->status === \App\Enums\PageStatus::Published)
                    <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="admin-preview-btn admin-preview-btn-public">
                        <span>Open Live Page</span>
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Main CMS Page Article --}}
    <main class="theme-page">
        <div class="container">
            @if ($page->show_title || $page->excerpt)
                <header class="cms-page-head">
                    <div class="kicker">
                        <span class="kicker-pulse"></span>
                        <span>{{ $currentLocale === 'si' ? 'ශ්‍රී ලංකා සංඥා බලකාය' : 'Sri Lanka Signal Corps' }}</span>
                    </div>

                    @if ($page->show_title)
                        <h1 class="title-lg">
                            {{ $page->title }}
                        </h1>
                    @endif

                    @if ($page->excerpt)
                        <p class="lead">
                            {{ $page->excerpt }}
                        </p>
                    @endif
                </header>
            @endif

            @if ($safeContent !== '')
                <article class="page-article-card" id="cms-page-{{ $page->getKey() }}">
                    <div class="awcms-content">
                        {!! $safeContent !!}
                    </div>
                </article>
            @endif

            {{-- Legacy page blocks remain visible in previews for existing pages. --}}
            @if ($pageBlocks !== [])
                <div class="page-blocks-wrap" style="margin-top: 2rem;">
                    <x-page.blocks :blocks="$pageBlocks" />
                </div>
            @endif
        </div>
    </main>
@endsection
