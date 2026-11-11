@extends('theme-slsc::layout')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section('content')
    @php
        $isHistory = $isHistory ?? false;
    @endphp
    <div class="section section-soft school-leadership-page">
        <div class="container">
            @include('theme-slsc::partials.page-heading', [
                'headingClass' => 'section-head school-leadership-head',
                'heading' => $pageTitle,
                'headingIntro' => __('theme-slsc::home.leadership.'.($isHistory ? 'past_description' : 'description')),
                'headingActionUrl' => $isHistory
                    ? ($currentLocale === 'en' ? route('school-leadership.index') : route('school-leadership.localized', ['locale' => $currentLocale]))
                    : ($currentLocale === 'en' ? route('school-leadership.history') : route('school-leadership.history.localized', ['locale' => $currentLocale])),
                'headingActionLabel' => __('theme-slsc::home.leadership.'.($isHistory ? 'current_leadership' : 'past_leadership')),
            ])

            @if ($leaders->isNotEmpty())
                <div class="leadership-grid school-leadership-grid">
                    @foreach ($leaders as $leader)
                        @php
                            $leaderImage = $leader->image
                                ? app(\App\Services\MediaUrlService::class)->mediumOrOriginal($leader->image)
                                : null;
                            $leaderName = $leader->nameForLocale($currentLocale);
                            $leaderTitle = $leader->appointmentLabelForLocale($currentLocale);
                            $leaderRank = $leader->rankLabelForLocale($currentLocale);
                        @endphp

                        <article class="card leader-card">
                            <div class="leader-card-image-wrap">
                                @if ($leaderImage)
                                    <img
                                        src="{{ $leaderImage }}"
                                        alt="{{ $leaderName !== '' ? $leaderName : $leaderTitle }}"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="leader-placeholder" aria-hidden="true">
                                        <i class="fa-solid fa-user-tie"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="leader-body">
                                <p class="role">{{ $leaderTitle }}</p>

                                @if ($leaderRank !== '')
                                    <p class="leader-rank">{{ $leaderRank }}</p>
                                @endif

                                @if ($leaderName !== '')
                                    <h2>{{ $leaderName }}</h2>
                                @endif

                                @if ($leader->start_date)
                                    <p class="leader-period">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                        <span>
                                            {{ $leader->start_date->format('d M Y') }}
                                            &ndash;
                                            {{ $leader->end_date?->format('d M Y') ?? __('theme-slsc::home.leadership.present') }}
                                        </span>
                                    </p>
                                @endif

                                @if ($leaderProfileUrl = $leader->profileUrlForLocale($currentLocale))
                                    <a class="read-more-btn school-leader-profile-link" href="{{ $leaderProfileUrl }}"
                                        aria-label="{{ __('theme-slsc::home.leadership.read_more') }}: {{ $leaderName !== '' ? $leaderName : $leaderTitle }}">
                                        <span>{{ __('theme-slsc::home.leadership.read_more') }}</span>
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="card empty-leadership-card">
                    <p>
                        {{ $currentLocale === 'si'
                            ? 'දැනට පෙන්වීමට නායකත්ව තොරතුරු නොමැත.'
                            : 'There are no leadership details to display.' }}
                    </p>
                </div>
            @endif

            <div class="leadership-bottom-actions">
                <a
                    class="btn btn-green"
                    href="{{ $currentLocale === 'en' ? route('home') : route('home.localized', ['locale' => $currentLocale]) }}"
                >
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    <span>{{ $currentLocale === 'si' ? 'මුල් පිටුවට' : 'Back to Home' }}</span>
                </a>
            </div>
        </div>
    </div>
@endsection
