@extends(($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout") ? "theme-{$activeTheme}::layout" : 'layouts.public')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section(
    'canonical',
    $currentLocale === 'en'
        ? route('history.past-chief-instructors')
        : route('history.past-chief-instructors.localized', ['locale' => 'si'])
)

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
                    {{ $currentLocale === 'si'
                        ? 'ප්‍රධාන උපදේශකවරු'
                        : 'Chief Instructors' }}
                </h1>

                <p class="section-subtitle mx-auto">
                    {{ $currentLocale === 'si'
                        ? 'ශ්‍රී ලංකා සංඥා පාසලේ සේවය කළ සහ වර්තමාන ප්‍රධාන උපදේශකවරු.'
                        : 'The Chief Instructors who served and currently serve the School of Signals.' }}
                </p>

            </div>
        </header>

        <section
            class="section past-command-section"
            aria-labelledby="chief-instructors-title"
        >
            <div class="container">

                <div class="section-head">
                    <div>

                        <p class="kicker">
                            {{ $currentLocale === 'si'
                                ? 'නායකත්ව ඉතිහාසය'
                                : 'Leadership History' }}
                        </p>

                        <h2
                            class="title-lg"
                            id="chief-instructors-title"
                        >
                            {{ $currentLocale === 'si'
                                ? 'ප්‍රධාන උපදේශකවරු'
                                : 'Chief Instructors' }}
                        </h2>

                    </div>
                </div>

                <?php if ($chiefInstructors->isNotEmpty()): ?>

                    <div class="past-command-grid">

                        <?php foreach ($chiefInstructors as $index => $instructor): ?>

                            <?php
                                $imageUrl = $instructor->image
                                    ? $mediaUrls->mediumOrOriginal(
                                        $instructor->image
                                    )
                                    : null;

                                $name = $instructor->nameForLocale(
                                    $currentLocale
                                );

                                $rank = $instructor->rankLabelForLocale(
                                    $currentLocale
                                );

                                $isCurrent = $instructor
                                    ->isCurrentAppointment();
                            ?>

                            <article class="card past-command-card reveal">

                                <div class="command-img">

                                    <span class="command-no">
                                        {{ str_pad(
                                            (string) ($index + 1),
                                            2,
                                            '0',
                                            STR_PAD_LEFT
                                        ) }}
                                    </span>

                                    <?php if ($imageUrl): ?>

                                        <img
                                            src="{{ $imageUrl }}"
                                            alt="{{ $name }}"
                                            loading="lazy"
                                        >

                                    <?php else: ?>

                                        <div class="command-placeholder">

                                            <i
                                                class="fa-solid fa-user-tie"
                                                aria-hidden="true"
                                            ></i>

                                            <span>
                                                {{ $currentLocale === 'si'
                                                    ? 'රූපය නොමැත'
                                                    : 'Image unavailable' }}
                                            </span>

                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="card-body">

                                    <p class="role">
                                        {{ $instructor->appointmentLabelForLocale(
                                            $currentLocale
                                        ) }}
                                    </p>

                                    <?php if ($rank !== ''): ?>

                                        <p class="leader-rank">
                                            {{ $rank }}
                                        </p>

                                    <?php endif; ?>

                                    <h3>
                                        {{ $name !== ''
                                            ? $name
                                            : ($currentLocale === 'si'
                                                ? 'නම ඇතුළත් කර නැත'
                                                : 'Name not added') }}
                                    </h3>

                                    <div class="command-meta">

                                        <span>

                                            <i
                                                class="fa-regular fa-calendar"
                                                aria-hidden="true"
                                            ></i>

                                            {{ $instructor->start_date
                                                ? $instructor->start_date
                                                    ->format('d.m.Y')
                                                : '—' }}

                                        </span>

                                        <span>

                                            <i
                                                class="fa-solid fa-arrow-right"
                                                aria-hidden="true"
                                            ></i>

                                            <?php if ($instructor->end_date): ?>

                                                {{ $instructor->end_date
                                                    ->format('d.m.Y') }}

                                            <?php else: ?>

                                                {{ $currentLocale === 'si'
                                                    ? 'අද දක්වා'
                                                    : 'Up to Date' }}

                                            <?php endif; ?>

                                        </span>

                                    </div>

                                    @if ($profileUrl = $instructor->profileUrlForLocale($currentLocale))
                                        <a class="read-more-btn school-leader-profile-link" href="{{ $profileUrl }}">
                                            <span>{{ __('theme-slsc::home.leadership.read_more') }}</span>
                                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    @endif

                                    <?php if ($isCurrent): ?>

                                        <span class="current-commandant-label">

                                            {{ $currentLocale === 'si'
                                                ? 'වර්තමාන ප්‍රධාන උපදේශක'
                                                : 'Present Chief Instructor' }}

                                        </span>

                                    <?php endif; ?>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="news-empty">

                        <i
                            class="fa-solid fa-landmark"
                            aria-hidden="true"
                        ></i>

                        <p>
                            {{ $currentLocale === 'si'
                                ? 'ප්‍රධාන උපදේශකවරුන්ගේ තොරතුරු තවම ඇතුළත් කර නැත.'
                                : 'Chief Instructor records have not been added yet.' }}
                        </p>

                    </div>

                <?php endif; ?>

            </div>
        </section>

    </article>

@endsection
