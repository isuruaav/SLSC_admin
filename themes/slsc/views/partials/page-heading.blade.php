<header class="{{ $headingClass ?? 'slsc-page-heading' }}" data-page-heading>
    <p class="kicker">
        <span class="kicker-pulse" aria-hidden="true"></span>
        <span>{{ $headingEyebrow ?? (app()->getLocale() === 'si' ? 'ශ්‍රී ලංකා සංඥා බලකාය' : 'Sri Lanka Signal Corps') }}</span>
    </p>
    @if ($headingVisible ?? true)
        <h1 class="title-lg">{{ $heading }}</h1>
    @endif
    @if (! empty($headingIntro))
        <p class="lead">{{ $headingIntro }}</p>
    @endif
    @if (! empty($headingActionUrl) && ! empty($headingActionLabel))
        <a class="btn btn-white" href="{{ $headingActionUrl }}">
            <span>{{ $headingActionLabel }}</span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    @endif
</header>
