@php
    $commandantMedia = app(\App\Services\MediaUrlService::class);
    $commandantImage = $commandant->image ? $commandantMedia->mediumOrOriginal($commandant->image) : null;
    $commandantName = $commandant->nameForLocale($commandantLocale);
    $commandantRole = $commandant->appointmentLabelForLocale($commandantLocale);
    $commandantRank = $commandant->rankLabelForLocale($commandantLocale);
    $commandantProfileUrl = $commandant->profileUrlForLocale($commandantLocale);
@endphp

<article class="sos-commandant-card">
    @if ($commandantBackdrop ?? null)
        <img class="sos-commandant-backdrop" src="{{ $commandantMedia->mediumOrOriginal($commandantBackdrop) }}"
            alt="" loading="lazy" aria-hidden="true">
    @endif

    <div class="sos-commandant-copy">
        <div class="sos-commandant-identity">
            <img class="sos-commandant-mark" src="{{ asset('themes/slsc/assets/images/logo.png') }}"
                alt="" width="72" height="88" loading="lazy">
            <div>
                <p class="sos-commandant-kicker">{{ __('theme-slsc::home.leadership.school') }}</p>
                <p class="sos-commandant-role">{{ $commandantRole }}</p>
            </div>
        </div>

        @if ($commandantRank !== '')
            <p class="sos-commandant-rank">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span>{{ $commandantRank }}</span>
            </p>
        @endif

        @if ($isCommandantProfile)
            <h1>{{ $commandantName !== '' ? $commandantName : $commandantRole }}</h1>
        @else
            <h2>{{ $commandantName !== '' ? $commandantName : $commandantRole }}</h2>
        @endif

        @if ($commandant->start_date)
            <div class="sos-commandant-service">
                <span class="sos-commandant-service-icon" aria-hidden="true">
                    <i class="fa-regular fa-calendar-check"></i>
                </span>
                <div>
                    <p>{{ __('theme-slsc::home.leadership.assumed_office') }}</p>
                    <time datetime="{{ $commandant->start_date->toDateString() }}">{{ $commandant->start_date->format('d M Y') }}</time>
                </div>
            </div>
        @endif

        @if (! $isCommandantProfile && $commandantProfileUrl)
            <a class="sos-commandant-button" href="{{ $commandantProfileUrl }}"
                aria-label="{{ __('theme-slsc::home.leadership.read_profile') }}: {{ $commandantName !== '' ? $commandantName : $commandantRole }}">
                <span>{{ __('theme-slsc::home.leadership.read_more') }}</span>
                <span class="sos-commandant-button-icon" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
            </a>
        @endif
    </div>

    <div class="sos-commandant-portrait">
        <div class="sos-commandant-photo">
            @if ($commandantImage)
                <img src="{{ $commandantImage }}" alt="{{ $commandantName !== '' ? $commandantName : $commandantRole }}"
                    width="300" height="360" loading="lazy">
            @else
                <div class="sos-commandant-placeholder" aria-hidden="true">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            @endif
        </div>
        <p class="sos-commandant-status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ __('theme-slsc::home.leadership.'.($commandant->isCurrentAppointment() ? 'current_commandant' : 'past_commandant')) }}</span>
        </p>
    </div>
</article>
