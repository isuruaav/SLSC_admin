@extends(($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout") ? "theme-{$activeTheme}::layout" : 'layouts.public')

@php
    $isSinhala = $currentLocale === 'si';
    $title = $isSinhala ? 'පාඨමාලා ලැයිස්තුව' : 'Course List';
@endphp

@section('title', $title.' | '.config('app.name'))
@section('meta_description', $isSinhala ? 'සංඥා පාසලේ සක්‍රීය පුහුණු පාඨමාලා සහ සුදුසුකම්.' : 'Active training courses and qualifications at the School of Signals.')
@section('canonical', $currentLocale === 'si' ? route('courses.index.localized', ['locale' => 'si']) : route('courses.index'))

@push('styles')
    <style>
        .aw-courses{background:#fff;color:#111936;min-height:65vh}
        .aw-courses *,.aw-courses *::before,.aw-courses *::after{box-sizing:border-box}
        .aw-courses-container{width:min(1120px,calc(100% - 40px));margin-inline:auto}
        .aw-courses-hero{padding:28px 0 18px;background:#fff}
        .aw-courses-kicker{margin:0;color:#14804c;font-size:12px;font-weight:700;letter-spacing:.04em}
        .aw-courses h1{margin:17px 0 0;color:#071b43;font-size:clamp(27px,3vw,36px);font-weight:700;line-height:1.45}
        .aw-courses-main{padding-block:14px 56px}
        .aw-courses-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 20px}
        .aw-course-card{position:relative;display:flex;min-height:120px;flex-direction:column;justify-content:center;overflow:hidden;padding:24px 28px 24px 30px;border:0;border-radius:14px;background:linear-gradient(115deg,#102b43,#1b5273);box-shadow:0 8px 18px rgba(9,35,55,.15)}
        .aw-course-card::before{position:absolute;inset:0 auto 0 0;width:6px;background:#82c5a2;content:""}
        .aw-course-card:nth-child(even){background:linear-gradient(115deg,#165238,#2e805a);box-shadow:0 8px 18px rgba(18,67,45,.14)}
        .aw-course-card h2{margin:0;color:#fff;font-size:17px;font-weight:700;line-height:1.7;overflow-wrap:anywhere}
        .aw-course-dates{display:flex;flex-wrap:wrap;gap:8px 18px;margin-top:12px;color:#e1f3e9;font-size:13px}
        .aw-course-dates time{font-weight:700}
        .aw-course-qualifications{margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.24)}
        .aw-course-qualifications h3{margin:0 0 7px;color:#fff;font-size:13px;font-weight:700}
        .aw-course-qualifications ul{margin:0;padding-left:20px;color:#e1f3e9;font-size:13px;line-height:1.7}
        .aw-course-empty{padding:40px 24px;border:1px solid #dce5f2;border-radius:14px;background:#fff;text-align:center;color:#465570}
        .aw-course-empty h2{margin:0 0 10px;color:#111936;font-size:22px}
        @media(max-width:700px){.aw-courses-container{width:calc(100% - 32px)}.aw-courses-hero{padding:23px 0 16px}.aw-courses h1{margin-top:12px;font-size:28px}.aw-courses-main{padding-block:10px 40px}.aw-courses-grid{grid-template-columns:1fr;gap:14px}.aw-course-card{min-height:100px;padding:20px 22px 20px 26px}.aw-course-card h2{font-size:16px}}
    </style>
@endpush

@section('content')
    <div class="aw-courses" lang="{{ $currentLocale }}">
        <header class="aw-courses-hero">
            <div class="aw-courses-container">
                <p class="aw-courses-kicker">{{ $isSinhala ? 'ශ්‍රී ලංකා යුද්ධ හමුදාව · සංඥා පාසල' : 'Sri Lanka Army · School of Signals' }}</p>
                <h1>{{ $title }}</h1>
            </div>
        </header>
        <div class="aw-courses-main">
            <div class="aw-courses-container">
                @include('public.courses.filters')
                @if ($courses->isNotEmpty())
                    <div class="aw-courses-grid">
                        @foreach ($courses as $course)
                            <article class="aw-course-card">
                                @php($courseState = $course->end_date?->isPast() && !$course->end_date?->isToday() ? 'completed' : ($course->start_date?->isFuture() ? 'upcoming' : ($course->start_date ? 'ongoing' : 'available')))
                                <span class="course-status">{{ ['completed' => ['Completed','අවසන් වූ'], 'upcoming' => ['Upcoming','ඉදිරියේදී'], 'ongoing' => ['Ongoing','දැනට පැවැත්වෙන'], 'available' => ['Available','තොරතුරු ලබාගත හැක']][$courseState][$isSinhala ? 1 : 0] }}</span>
                                <h2 lang="{{ $isSinhala ? 'si' : 'en' }}">{{ $isSinhala ? ($course->name_si ?: $course->name) : $course->name }}</h2>
                                @if ($course->start_date || $course->end_date)
                                    <div class="aw-course-dates">
                                        @if ($course->start_date)
                                            <span>{{ $isSinhala ? 'ආරම්භක දිනය' : 'Starts' }}:
                                                <time datetime="{{ $course->start_date->toDateString() }}">{{ $course->start_date->format('d M Y') }}</time>
                                            </span>
                                        @endif
                                        @if ($course->end_date)
                                            <span>{{ $isSinhala ? 'අවසන් දිනය' : 'Ends' }}:
                                                <time datetime="{{ $course->end_date->toDateString() }}">{{ $course->end_date->format('d M Y') }}</time>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                @if ($course->qualifications->isNotEmpty())
                                    <section class="aw-course-qualifications">
                                        <h3>{{ $isSinhala ? 'සුදුසුකම්' : 'Qualifications' }}</h3>
                                        <ul>
                                            @foreach ($course->qualifications as $qualification)
                                                <li>{{ $isSinhala ? ($qualification->qualification_si ?: $qualification->qualification) : $qualification->qualification }}</li>
                                            @endforeach
                                        </ul>
                                    </section>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="aw-course-empty">
                        <h2>{{ $isSinhala ? 'දැනට සක්‍රීය පාඨමාලා නොමැත' : 'No active courses at the moment' }}</h2>
                        <p>{{ $isSinhala ? 'නව පාඨමාලා තොරතුරු සඳහා පසුව නැවත බලන්න.' : 'Please check back later for course updates.' }}</p>
                    </div>
                @endif
                @if ($courses->hasPages())<nav class="course-public-filters" aria-label="Course pages">@if ($courses->previousPageUrl())<a href="{{ $courses->previousPageUrl() }}">← {{ $isSinhala ? 'පෙර' : 'Previous' }}</a>@endif<span>{{ $courses->currentPage() }} / {{ $courses->lastPage() }}</span>@if ($courses->nextPageUrl())<a href="{{ $courses->nextPageUrl() }}">{{ $isSinhala ? 'ඊළඟ' : 'Next' }} →</a>@endif</nav>@endif
            </div>
        </div>
    </div>
@endsection
