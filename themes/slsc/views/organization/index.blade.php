@extends('theme-slsc::layout')

@section('title', $pageTitle)
@section('description', $pageDescription)
@section('meta_description', $pageDescription)

@section('content')
<div class="org-page">
    <!-- Hero Banner Section -->
    <section class="org-hero">
        <div class="container org-hero-inner">
            @include('theme-slsc::partials.page-heading', [
                'heading' => $currentLocale === 'si' ? 'සංවිධාන ව්‍යුහය' : 'Organization Structure',
                'headingIntro' => $pageDescription,
            ])

            <!-- Search and Filter Form -->
            <form method="GET" action="{{ $currentLocale === 'en' ? route('organization.index') : route('organization.index.localized', ['locale' => $currentLocale]) }}" class="org-search-form">
                <div class="org-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" value="{{ $searchQuery }}" placeholder="{{ $currentLocale === 'si' ? 'ඒකකයක් හෝ රෙජිමේන්තුවක් සොයන්න...' : 'Search units, regiments, or directorates…' }}" class="org-search-input">
                </div>
                @if ($currentCategory)
                    <input type="hidden" name="category" value="{{ $currentCategory }}">
                @endif
                <button type="submit" class="org-search-btn">
                    {{ $currentLocale === 'si' ? 'සොයන්න' : 'Search' }}
                </button>
            </form>

            <!-- Category Filtering Pills -->
            <div class="org-filters">
                <a href="{{ $currentLocale === 'en' ? route('organization.index') : route('organization.index.localized', ['locale' => $currentLocale]) }}"
                    class="org-pill {{ empty($currentCategory) || $currentCategory === 'all' ? 'active' : '' }}">
                    <span>{{ $currentLocale === 'si' ? 'සියල්ල' : 'All Units' }}</span>
                </a>
                @foreach ($categories as $cat)
                    <a href="{{ ($currentLocale === 'en' ? route('organization.index') : route('organization.index.localized', ['locale' => $currentLocale])) . '?category=' . $cat->slug }}"
                        class="org-pill {{ $currentCategory === $cat->slug ? 'active' : '' }}">
                        <span>{{ $cat->localizedName($currentLocale) }}</span>
                        <span class="org-pill-count">{{ $cat->organizations_count }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Units Directory Grid Section -->
    <section class="org-section">
        <div class="container">
            @if ($organizations->isEmpty())
                <div class="org-empty">
                    <div class="org-empty-icon">
                        <i class="fa-solid fa-sitemap"></i>
                    </div>
                    <h3>No organizations found</h3>
                    <p>No active units match your search query or selected category filter.</p>
                    <a href="{{ $currentLocale === 'en' ? route('organization.index') : route('organization.index.localized', ['locale' => $currentLocale]) }}"
                        class="org-pill active">
                        Clear All Filters
                    </a>
                </div>
            @else
                <div class="org-grid">
                    @foreach ($organizations as $org)
                        @php
                            $t = $org->translationForLocale($currentLocale);
                            $title = $t?->title ?: ($org->titleForLocale('en') ?: $org->slug);
                            $summary = $t?->summary ?: ($org->summaryForLocale('en') ?: '');
                            $detailUrl = $currentLocale === 'en'
                                ? route('organization.show', ['slug' => $org->slug])
                                : route('organization.show.localized', ['locale' => $currentLocale, 'slug' => $org->slug]);
                        @endphp

                        <article class="org-card">
                            <div>
                                <div class="org-card-top">
                                    @if ($org->category)
                                        <span class="org-cat-badge">
                                            {{ $org->category->localizedName($currentLocale) }}
                                        </span>
                                    @endif
                                    @if ($org->short_name)
                                        <span class="org-code-badge">
                                            {{ $org->short_name }}
                                        </span>
                                    @endif
                                </div>

                                <h2 class="org-card-title">
                                    <a href="{{ $detailUrl }}">{{ $title }}</a>
                                </h2>

                                @if ($summary)
                                    <p class="org-card-desc">
                                        {{ $summary }}
                                    </p>
                                @endif
                            </div>

                            <div class="org-card-foot">
                                @if ($org->location)
                                    <div class="org-card-location">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <span>{{ $org->location }}</span>
                                    </div>
                                @else
                                    <div class="org-card-sections">
                                        <i class="fa-solid fa-cubes"></i>
                                        <span>{{ $org->contentBlocks->count() }} Sections</span>
                                    </div>
                                @endif

                                <a href="{{ $detailUrl }}" class="org-card-link">
                                    <span>Explore</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
