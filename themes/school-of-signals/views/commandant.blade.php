@extends('theme-school-of-signals::layout')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@php
    $commandantOgMedia = app(\App\Services\MediaUrlService::class);
    $commandantOgImage = $commandant->image ? $commandantOgMedia->mediumOrOriginal($commandant->image) : null;
@endphp

@section('og_tags')
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($commandantOgImage)
        <meta property="og:image" content="{{ $commandantOgImage }}">
        <meta name="twitter:image" content="{{ $commandantOgImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
@endsection

@section('content')
    <section class="sos-commandant-section sos-commandant-profile">
        <div class="container">
            @include('theme-school-of-signals::partials.commandant-feature', [
                'commandant' => $commandant,
                'commandantLocale' => $currentLocale,
                'isCommandantProfile' => true,
            ])

            <div class="sos-commandant-details">
                <h2>{{ __('theme-school-of-signals::home.leadership.profile') }}</h2>
                <dl>
                    <div>
                        <dt>{{ __('theme-school-of-signals::home.leadership.appointment') }}</dt>
                        <dd>{{ $commandant->appointmentLabelForLocale($currentLocale) }}</dd>
                    </div>
                    @if ($commandant->rankLabelForLocale($currentLocale) !== '')
                        <div>
                            <dt>{{ __('theme-school-of-signals::home.leadership.rank') }}</dt>
                            <dd>{{ $commandant->rankLabelForLocale($currentLocale) }}</dd>
                        </div>
                    @endif
                    @if ($commandant->start_date)
                        <div>
                            <dt>{{ __('theme-school-of-signals::home.leadership.assumed_office') }}</dt>
                            <dd><time datetime="{{ $commandant->start_date->toDateString() }}">{{ $commandant->start_date->format('d M Y') }}</time></dd>
                        </div>
                    @endif
                    @if ($commandant->end_date)
                        <div>
                            <dt>{{ __('theme-school-of-signals::home.leadership.completed_service') }}</dt>
                            <dd><time datetime="{{ $commandant->end_date->toDateString() }}">{{ $commandant->end_date->format('d M Y') }}</time></dd>
                        </div>
                    @endif
                    <div>
                        <dt>{{ __('theme-school-of-signals::home.leadership.status') }}</dt>
                        <dd>{{ __('theme-school-of-signals::home.leadership.'.($commandant->isCurrentAppointment() ? 'present' : 'past')) }}</dd>
                    </div>
                </dl>
            </div>

            @include('theme-school-of-signals::partials.leadership-profile-content')

            <div class="sos-leadership-link">
                <a class="btn btn-white" href="{{ $currentLocale === 'en' ? route('home') : route('home.localized', ['locale' => $currentLocale]) }}#leadership">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    <span>{{ __('theme-school-of-signals::home.leadership.back_home') }}</span>
                </a>
                <a class="btn btn-green" href="{{ $commandant->isCurrentAppointment()
                    ? ($currentLocale === 'en' ? route('school-leadership.index') : route('school-leadership.localized', ['locale' => $currentLocale]))
                    : ($currentLocale === 'en' ? route('school-leadership.history') : route('school-leadership.history.localized', ['locale' => $currentLocale])) }}">
                    <span>{{ __('theme-school-of-signals::home.leadership.'.($commandant->isCurrentAppointment() ? 'view_all' : 'past_leadership')) }}</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>
@endsection
