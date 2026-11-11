@extends('theme-slsc::layout')

@section('title', $pageTitle)
@section('description', $pageDescription)
@section('meta_description', $pageDescription)

@section('content')
<div class="first-appointments-page">
    <div class="container">
        <!-- Page Header -->
        @include('theme-slsc::partials.page-heading', [
                'headingClass' => 'first-appointments-head',
                'heading' => $currentLocale === 'si' ? 'පළමු පත්වීම් හිමියන්' : '1st Appointments Holders',
                'headingIntro' => $pageDescription,
            ])
        @include('public.history.year-filter')

        <!-- 4-Column Grid -->
        @if ($holders->isNotEmpty())
            <div data-history-view="{{ $historyView }}" class="first-appointments-grid">
                @foreach ($holders as $holder)
                    @php
                        $appointment = $holder->appointmentForLocale($currentLocale);
                        $name = $holder->nameForLocale($currentLocale);
                        $period = $holder->periodForLocale($currentLocale);
                        $imageUrl = $holder->imageUrl();
                    @endphp

                    <article id="holder-{{ $holder->id }}" class="first-appointment-card">
                        <div class="card-photo-wrap">
                            @if ($imageUrl)
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="{{ $name }} - {{ $appointment }}"
                                    loading="lazy"
                                >
                            @else
                                <div class="card-photo-placeholder" aria-hidden="true">
                                    <i class="fa-solid fa-user-tie"></i>
                                </div>
                            @endif
                        </div>

                        <div class="card-body">
                            <h2 class="card-appointment">
                                {{ $appointment }}
                            </h2>

                            <div class="card-name">
                                {{ $name }}
                            </div>

                            @if (! empty($period))
                                <div class="card-period">
                                    <i class="fa-regular fa-calendar-check text-[11px]" aria-hidden="true"></i>
                                    <span>{{ $period }}</span>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="first-appointments-empty">
                <i class="fa-solid fa-award"></i>
                <h3>{{ $currentLocale === 'si' ? 'තොරතුරු හමු නොවීය' : 'No records found' }}</h3>
                <p>{{ $currentLocale === 'si' ? 'දැනට ප්‍රදර්ශනය කිරීමට පත්වීම් හිමියන්ගේ තොරතුරු නොමැත.' : 'There are no appointment holders currently published.' }}</p>
            </div>
        @endif
    </div>
</div>
@endsection

