@extends('theme-slsc::layout')

@section('title', $pageTitle)
@section('description', $pageDescription)
@section('meta_description', $pageDescription)

@section('content')
<div class="brigade-commanders-page">
    <div class="container">
        <!-- Page Header -->
        @include('theme-slsc::partials.page-heading', [
                'headingClass' => 'brigade-commanders-head',
                'heading' => $currentLocale === 'si' ? 'බලසේනාධිපතිවරුන්' : 'Brigade Commanders',
                'headingIntro' => $pageDescription,
            ])
        @include('public.history.year-filter')

        <!-- 4-Column Grid -->
        @if ($commanders->isNotEmpty())
            <div data-history-view="{{ $historyView }}" class="brigade-commanders-grid">
                @foreach ($commanders as $commander)
                    @php
                        $name = $commander->nameForLocale($currentLocale);
                        $period = $commander->periodForLocale($currentLocale);
                        $secondaryTenure = $commander->secondaryTenureForLocale($currentLocale);
                        $orderNumber = $commander->displayOrderNumber();
                        $imageUrl = $commander->imageUrl();
                    @endphp

                    <article id="commander-{{ $commander->id }}" class="brigade-commander-card">
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

                                @if (! empty($secondaryTenure))
                                    <div class="card-secondary-tenure">
                                        {!! nl2br(e($secondaryTenure)) !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="brigade-commanders-empty">
                <i class="fa-solid fa-medal"></i>
                <h3>{{ $currentLocale === 'si' ? 'තොරතුරු හමු නොවීය' : 'No records found' }}</h3>
                <p>{{ $currentLocale === 'si' ? 'දැනට ප්‍රදර්ශනය කිරීමට බලසේනාධිපතිවරුන්ගේ තොරතුරු නොමැත.' : 'There are no brigade commanders currently published.' }}</p>
            </div>
        @endif
    </div>
</div>
@endsection

