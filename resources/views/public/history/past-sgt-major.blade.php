@extends(($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout") ? "theme-{$activeTheme}::layout" : 'layouts.public')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)
@section('canonical', $currentLocale === 'si' ? route('history.past-sgt-major.localized') : route('history.past-sgt-major'))

@section('content')
    @php
        $mediaUrls = app(\App\Services\MediaUrlService::class);
        $isSinhala = $currentLocale === 'si';
        $heading = $isSinhala ? 'රෙජිමේන්තු සැරයන් මේජර්වරු' : 'Regimental Sergeant Majors';
    @endphp

    <article class="past-command-page" lang="{{ $currentLocale }}">
        <header class="section section-soft past-command-section">
            <div class="container text-center">
                <p class="kicker">{{ $isSinhala ? 'ඉතිහාසය' : 'History' }}</p>
                <h1 class="title-lg">{{ $heading }}</h1>
                <p class="section-subtitle mx-auto">
                    {{ $metaDescription }}
                </p>
            </div>
        </header>

        <section class="section past-command-section" aria-labelledby="sergeant-majors-title">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="kicker">{{ $isSinhala ? 'නායකත්ව ඉතිහාසය' : 'Leadership History' }}</p>
                        <h2 class="title-lg" id="sergeant-majors-title">{{ $heading }}</h2>
                    </div>
                </div>

                @if ($sergeantMajors->isNotEmpty())
                    <div class="past-command-grid">
                        @foreach ($sergeantMajors as $sergeantMajor)
                            @php
                                $imageUrl = $sergeantMajor->image
                                    ? $mediaUrls->mediumOrOriginal($sergeantMajor->image)
                                    : null;
                                $name = $sergeantMajor->nameForLocale($currentLocale);
                                $rank = $sergeantMajor->rankLabelForLocale($currentLocale);
                            @endphp

                            <article class="card past-command-card reveal">
                                <div class="command-img">
                                    <span class="command-no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $name ?: $heading }}" loading="lazy">
                                    @else
                                        <div class="command-placeholder">
                                            <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
                                            <span>{{ $isSinhala ? 'රූපය නොමැත' : 'Image unavailable' }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="card-body">
                                    <p class="role">{{ $sergeantMajor->appointmentLabelForLocale($currentLocale) }}</p>
                                    @if ($rank !== '')
                                        <p class="leader-rank">{{ $rank }}</p>
                                    @endif
                                    <h3>{{ $name !== '' ? $name : ($isSinhala ? 'නම ඇතුළත් කර නැත' : 'Name not added') }}</h3>
                                    <div class="command-meta">
                                        <span>
                                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                            {{ $sergeantMajor->start_date?->format('d.m.Y') ?: '—' }}
                                        </span>
                                        <span>
                                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                            {{ $sergeantMajor->end_date?->format('d.m.Y') ?: ($isSinhala ? 'අද දක්වා' : 'Up to Date') }}
                                        </span>
                                    </div>

                                    @if ($profileUrl = $sergeantMajor->profileUrlForLocale($currentLocale))
                                        <a class="read-more-btn school-leader-profile-link" href="{{ $profileUrl }}">
                                            <span>{{ __('theme-slsc::home.leadership.read_more') ?: __('theme-school-of-signals::home.leadership.read_more') }}</span>
                                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    @endif

                                    @if ($sergeantMajor->isCurrentAppointment())
                                        <span class="current-commandant-label">
                                            {{ $isSinhala ? 'වර්තමාන රෙජිමේන්තු සැරයන් මේජර්' : 'Present Regimental Sergeant Major' }}
                                        </span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="news-empty">
                        <i class="fa-solid fa-landmark" aria-hidden="true"></i>
                        <p>{{ $isSinhala ? 'රෙජිමේන්තු සැරයන් මේජර්වරුන්ගේ තොරතුරු තවම ඇතුළත් කර නැත.' : 'Regimental Sergeant Major records have not been added yet.' }}</p>
                    </div>
                @endif
            </div>
        </section>
    </article>
@endsection
