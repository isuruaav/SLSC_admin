@php
    $activeTheme = config('awcms.active_theme');
    $themeLayout = $activeTheme ? "theme-{$activeTheme}::layout" : null;
    $previewLayout = ($themeLayout && view()->exists($themeLayout))
        ? $themeLayout
        : (view()->exists('theme-slsc::layout') ? 'theme-slsc::layout' : 'layouts.public');
@endphp
@extends($previewLayout)

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')

    {{-- Administrator preview notice --}}
    <div
        style="
            background:#fff7d6;
            border-bottom:1px solid #f1d77a;
            padding:12px 20px;
        "
    >
        <div
            style="
                width:min(1120px, calc(100% - 32px));
                margin:0 auto;
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:16px;
                flex-wrap:wrap;
            "
        >
            <div>
                <strong style="color:#7a5600;">
                    Administrator Preview
                </strong>

                <div style="margin-top:3px;font-size:13px;color:#8a6715;">
                    @if ($news->trashed())
                        This news article is in Trash. Restore it before making changes.
                    @else
                        This is a preview of the current news article.
                    @endif
                </div>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span
                    style="
                        padding:5px 10px;
                        border-radius:999px;
                        background:#fff;
                        font-size:12px;
                        font-weight:700;
                    "
                >
                    {{ $news->locale->nativeLabel() }}
                </span>

                <span
                    style="
                        padding:5px 10px;
                        border-radius:999px;
                        background:#fff;
                        font-size:12px;
                        font-weight:700;
                    "
                >
                    {{ $news->trashed() ? 'Trash' : $news->status->label() }}
                </span>
            </div>
        </div>
    </div>

    <main class="sos-article-page">

        <article>

            {{-- Article header --}}
            <header class="sos-article-header">
                <div class="sos-article-container">

                    <div class="sos-article-toolbar">
                        <a
                            href="{{ $news->trashed() ? route('admin.news.index', ['records' => 'trashed']) : route('admin.news.edit', $news) }}"
                            class="sos-article-back"
                        >
                            <i
                                class="fa-solid fa-arrow-left"
                                aria-hidden="true"
                            ></i>

                            {{ $news->trashed() ? 'Back to Trash' : 'Back to Edit' }}
                        </a>
                    </div>

                    <div class="sos-article-meta">

                        @if ($news->category)
                            <span class="sos-article-category">
                                {{ $news->category->name }}
                            </span>
                        @endif

                        @if ($news->is_featured)
                            <span class="sos-article-featured">
                                Featured
                            </span>
                        @endif

                        @if ($news->published_at)
                            <time
                                datetime="{{ $news->published_at->toIso8601String() }}"
                            >
                                <i
                                    class="fa-regular fa-calendar"
                                    aria-hidden="true"
                                ></i>

                                {{ $news->published_at->format('d M Y') }}
                            </time>
                        @endif

                    </div>

                    <h1 class="sos-article-title">
                        {{ $news->title }}
                    </h1>

                    {{-- @if ($news->summary)
                        <p class="sos-article-summary">
                            {{ $news->summary }}
                        </p>
                    @endif --}}

                </div>
            </header>


            {{-- Featured image --}}
            {{-- @if (
                is_string($featuredImageUrl)
                && $featuredImageUrl !== ''
            )
                <section class="sos-article-body">
                    <div class="sos-article-container">

                        <figure
                            style="
                                margin:0 0 36px;
                                overflow:hidden;
                                border-radius:18px;
                            "
                        >
                            <img
                                src="{{ $featuredImageUrl }}"
                                alt="{{ $news->featuredImage?->alt_text ?: $news->title }}"
                                style="
                                    display:block;
                                    width:100%;
                                    max-height:620px;
                                    object-fit:cover;
                                "
                            >
                        </figure>

                    </div>
                </section>
            @endif --}}


            {{-- Article content --}}
            @if ($safeContent !== '')

                <section class="sos-article-body">

                    <div class="sos-article-container">

                        @if (
                            $news->editor_mode
                            === \App\Enums\NewsEditorMode::Visual
                        )
                            <div class="sos-article-content awcms-content">
                                {!! $safeContent !!}
                            </div>
                        @else
                            <div class="sos-article-content page-html-content">
                                {!! $safeContent !!}
                            </div>
                        @endif

                    </div>

                </section>

            @endif


            {{-- Article image gallery --}}
            @if ($news->images->isNotEmpty())

                <section class="sos-article-gallery-section">

                    <div class="sos-article-container">

                        <h2>
                            Event Photographs
                        </h2>

                        <div class="sos-article-gallery">

                            @foreach ($news->images as $newsImage)

                                @php
                                    $galleryImageUrl =
                                        \App\Http\Controllers\PublicNewsController::imageUrl(
                                            $newsImage->media,
                                        );

                                    $galleryAlt =
                                        $newsImage->media?->alt_text
                                        ?: $news->title;
                                @endphp

                                @if (
                                    is_string($galleryImageUrl)
                                    && $galleryImageUrl !== ''
                                )
                                    <figure class="sos-article-photo">

                                        <a
                                            href="{{ $galleryImageUrl }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <img
                                                src="{{ $galleryImageUrl }}"
                                                alt="{{ $galleryAlt }}"
                                                loading="lazy"
                                            >
                                        </a>

                                        @if ($newsImage->media?->caption)
                                            <figcaption>
                                                {{ $newsImage->media->caption }}
                                            </figcaption>
                                        @endif

                                    </figure>
                                @endif

                            @endforeach

                        </div>

                    </div>

                </section>

            @endif

        </article>

    </main>

@endsection
