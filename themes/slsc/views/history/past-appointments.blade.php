@extends('theme-slsc::layout')

@section('title', $pageTitle)
@section('description', $pageDescription)
@section('meta_description', $metaDescription)

@section('canonical', $currentLocale === 'en' ? url('/' . $appointmentMeta['slug']) : url('/si/' . $appointmentMeta['slug']))

@section('content')
<div class="brigade-commanders-page">
    <div class="container">
        <!-- Page Header (Identical structure and style to Brigade Commanders page) -->
        @include('theme-slsc::partials.page-heading', [
                'headingClass' => 'brigade-commanders-head',
                'heading' => $displayTitle,
                'headingIntro' => $pageDescription,
            ])
        @include('public.history.year-filter')

        <!-- 4-Column Grid (Only the requested appointment holders) -->
        @if ($leaders->isNotEmpty())
            <div data-history-view="{{ $historyView }}" class="brigade-commanders-grid">
                @foreach ($leaders as $index => $leader)
                    @php
                        $name = $leader->displayNameWithRankForLocale($currentLocale);
                        $period = $leader->periodForLocale($currentLocale);
                        $orderNumber = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                        $imageUrl = $leader->resolvedImageUrl();
                        $isCurrent = $leader->isCurrentAppointment();
                    @endphp

                    <article class="brigade-commander-card">
                        <div class="card-photo-wrap">
                            @if ($imageUrl)
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="{{ $orderNumber }}. {{ $name }}"
                                    loading="lazy"
                                >
                            @else
                                <div class="card-photo-placeholder" aria-hidden="true">
                                    <i class="fa-solid fa-user-tie"></i>
                                </div>
                            @endif

                            @if ($isCurrent)
                                <span class="officer-current-badge">
                                    <i class="fa-solid fa-circle-check"></i>
                                    {{ $currentLocale === 'si' ? 'වත්මන්' : 'Present' }}
                                </span>
                            @endif
                        </div>

                        <div class="card-body">
                            <h2 class="card-name-title">
                                {{ $orderNumber }}. {{ $name }}
                            </h2>

                            <div class="card-period-wrap">
                                @if (! empty($period))
                                    <div class="card-period">
                                        <i class="fa-regular fa-calendar-check text-[11px] text-emerald-600" aria-hidden="true"></i>
                                        <span>{{ $period }}</span>
                                    </div>
                                @endif

                                @if ($secondaryTenure = $leader->secondaryTenureForLocale($currentLocale))
                                    <div class="card-secondary-tenure">
                                        {!! nl2br(e($secondaryTenure)) !!}
                                    </div>
                                @endif
                            </div>

                            @if ($leader->hasPublicProfile() && ($profileUrl = $leader->profileUrlForLocale($currentLocale)))
                                <div class="card-profile-action">
                                    <a href="{{ $profileUrl }}" class="card-profile-link">
                                        <span>{{ $currentLocale === 'si' ? 'පැතිකඩ බලන්න' : 'View Profile' }}</span>
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="brigade-commanders-empty">
                <i class="fa-solid fa-medal"></i>
                <h3>{{ $currentLocale === 'si' ? 'තොරතුරු හමු නොවීය' : 'No records found' }}</h3>
                <p>{{ $currentLocale === 'si' ? 'දැනට ප්‍රදර්ශනය කිරීමට මෙම පත්වීම සඳහා තොරතුරු නොමැත.' : 'There are no records currently published for this appointment.' }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
