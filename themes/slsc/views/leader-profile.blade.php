@extends('theme-slsc::layout')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@php
    $leaderOgMedia = app(\App\Services\MediaUrlService::class);
    $leaderOgImage = $leader->image ? $leaderOgMedia->mediumOrOriginal($leader->image) : null;
@endphp

@section('og_tags')
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($leaderOgImage)
        <meta property="og:image" content="{{ $leaderOgImage }}">
        <meta name="twitter:image" content="{{ $leaderOgImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
@endsection

@section('content')
    @php
        $leaderImage = $leader->image ? app(\App\Services\MediaUrlService::class)->mediumOrOriginal($leader->image) : null;
        $leaderName = $leader->nameForLocale($currentLocale);
    @endphp
    <section class="section section-soft sos-leader-profile-page">
        <div class="container">
            <header class="sos-leader-profile-header">
                @if ($leaderImage)
                    <img src="{{ $leaderImage }}" alt="{{ $leaderName }}" width="200" height="250">
                @endif
                <div>
                    <p class="kicker">{{ $leader->appointmentLabelForLocale($currentLocale) }}</p>
                    <h1 class="title-lg">{{ $leaderName !== '' ? $leaderName : $pageTitle }}</h1>
                    @if ($leader->rankLabelForLocale($currentLocale) !== '')
                        <p class="lead">{{ $leader->rankLabelForLocale($currentLocale) }}</p>
                    @endif
                    @if ($leader->start_date)
                        <p class="leader-period">{{ __('theme-slsc::home.leadership.assumed_office') }}:
                            <time datetime="{{ $leader->start_date->toDateString() }}">{{ $leader->start_date->format('d M Y') }}</time>
                        </p>
                    @endif
                    @if ($leader->end_date)
                        <p class="leader-period">{{ __('theme-slsc::home.leadership.completed_service') }}:
                            <time datetime="{{ $leader->end_date->toDateString() }}">{{ $leader->end_date->format('d M Y') }}</time>
                        </p>
                    @endif
                    <p class="leader-rank">{{ __('theme-slsc::home.leadership.'.($leader->isCurrentAppointment() ? 'present' : 'past')) }}</p>
                </div>
            </header>
            @include('theme-slsc::partials.leadership-profile-content')
            <div class="sos-leadership-link">
                <a class="btn btn-green" href="{{ $leader->isCurrentAppointment()
                    ? ($currentLocale === 'en' ? route('school-leadership.index') : route('school-leadership.localized', ['locale' => $currentLocale]))
                    : ($currentLocale === 'en' ? route('school-leadership.history') : route('school-leadership.history.localized', ['locale' => $currentLocale])) }}">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    <span>{{ __('theme-slsc::home.leadership.'.($leader->isCurrentAppointment() ? 'view_all' : 'past_leadership')) }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection
