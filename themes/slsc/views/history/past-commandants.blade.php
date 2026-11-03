@extends('theme-slsc::layout')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section('canonical', $currentLocale === 'en' ? route('history.past-commandants') :
    route('history.past-commandants.localized', ['locale' => 'si']))

@section('content')

    <?php
    $mediaUrls = app(\App\Services\MediaUrlService::class);
    ?>

    <article class="past-command-page">

        <header class="section section-soft past-command-section">
            <div class="container text-center">

                <p class="kicker">
                    {{ $currentLocale === 'si' ? 'ඉතිහාසය' : 'History' }}
                </p>

                <h1 class="title-lg">
                    {{ $currentLocale === 'si' ? 'සේනාවිධායකවරු' : 'Commandants' }}
                </h1>

                <p class="section-subtitle mx-auto">
                    {{ $currentLocale === 'si'
                        ? 'ශ්‍රී ලංකා සංඥා පාසලට නායකත්වය දුන් සේනාවිධායකවරු.'
                        : 'The Commandants who served and currently serve the School of Signals.' }}
                </p>

            </div>
        </header>

        <section class="section past-command-section" aria-labelledby="past-commandants-title">
            <div class="container">

                <div class="section-head">
                    <div>

                        <p class="kicker">
                            {{ $currentLocale === 'si' ? 'නායකත්ව ඉතිහාසය' : 'Leadership History' }}
                        </p>

                        <h2 class="title-lg" id="past-commandants-title">
                            {{ $currentLocale === 'si' ? 'සේනාවිධායකවරු' : 'Commandants' }}
                        </h2>

                    </div>
                </div>

                <?php if ($commandants->isNotEmpty()): ?>

                <div class="past-command-grid">

                    <?php foreach ($commandants as $index => $commandant): ?>

                    <?php
                    $imageUrl = $commandant->image ? $mediaUrls->mediumOrOriginal($commandant->image) : null;

                    $name = $commandant->nameForLocale($currentLocale);

                    $rank = $commandant->rankLabelForLocale($currentLocale);

                    $isCurrent = $commandant->isCurrentAppointment();
                    ?>

                    <article class="card past-command-card reveal">

                        <div class="command-img">

                            <span class="command-no">
                                {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                            </span>

                            <?php if ($imageUrl): ?>

                            <img src="{{ $imageUrl }}" alt="{{ $name }}" loading="lazy">

                            <?php else: ?>

                            <div class="command-placeholder">

                                <i class="fa-solid fa-user-tie" aria-hidden="true"></i>

                                <span>
                                    {{ $currentLocale === 'si' ? 'රූපය නොමැත' : 'Image unavailable' }}
                                </span>

                            </div>

                            <?php endif; ?>

                        </div>

                        <div class="card-body">

                            <p class="role">
                                {{ $commandant->appointmentLabelForLocale($currentLocale) }}
                            </p>

                            <?php if ($rank !== ''): ?>

                            <p class="leader-rank">
                                {{ $rank }}
                            </p>

                            <?php endif; ?>

                            <h3>
                                @if ($profileUrl = $commandant->profileUrlForLocale($currentLocale))
                                    <a href="{{ $profileUrl }}">
                                        {{ $name !== '' ? $name : ($currentLocale === 'si' ? 'නම ඇතුළත් කර නැත' : 'Name not added') }}
                                    </a>
                                @else
                                    {{ $name !== '' ? $name : ($currentLocale === 'si' ? 'නම ඇතුළත් කර නැත' : 'Name not added') }}
                                @endif
                            </h3>

                            <div class="command-meta">

                                <span>
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>

                                    {{ $commandant->start_date ? $commandant->start_date->format('d.m.Y') : '—' }}
                                </span>

                                <span>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>

                                    <?php if ($commandant->end_date): ?>

                                    {{ $commandant->end_date->format('d.m.Y') }}

                                    <?php else: ?>

                                    {{ $currentLocale === 'si' ? 'අද දක්වා' : 'Up to Date' }}

                                    <?php endif; ?>

                                </span>

                            </div>


                            <?php if ($isCurrent): ?>

                            <span class="current-commandant-label">
                                {{ $currentLocale === 'si' ? 'වර්තමාන සේනාවිධායක' : 'Present Commandant' }}
                            </span>

                            <?php endif; ?>

                        </div>

                    </article>

                    <?php endforeach; ?>

                </div>

                <?php else: ?>

                <div class="news-empty">

                    <i class="fa-solid fa-landmark" aria-hidden="true"></i>

                    <p>
                        {{ $currentLocale === 'si'
                            ? 'සේනාවිධායකවරුන්ගේ තොරතුරු තවම ඇතුළත් කර නැත.'
                            : 'Commandant records have not been added yet.' }}
                    </p>

                </div>

                <?php endif; ?>

            </div>
        </section>

    </article>

@endsection
