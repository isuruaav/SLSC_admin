@extends(
    ($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout")
        ? "theme-{$activeTheme}::layout"
        : 'layouts.public'
)

@php
    $isSinhala = $currentLocale === 'si';
    $documentTitle = $document->titleForLocale($currentLocale);
    $description = $document->descriptionForLocale($currentLocale);
    $media = $currentVersion->media;
    $isPdf = $media?->extension === 'pdf';

    $indexUrl = $isSinhala
        ? route('documents.index.localized', ['locale' => 'si'])
        : route('documents.index');

    $viewUrl = $currentLocale === 'en'
        ? route('documents.view', ['slug' => $document->slug])
        : route('document-files.view', [
            'locale' => $currentLocale,
            'slug' => $document->slug,
        ]);

    $downloadUrl = $currentLocale === 'en'
        ? route('documents.download', ['slug' => $document->slug])
        : route('document-files.download', [
            'locale' => $currentLocale,
            'slug' => $document->slug,
        ]);
@endphp

@section('title', $documentTitle.' | '.config('app.name'))

@push('styles')
    @include('public.documents.upload-styles')
@endpush

@section('content')
    <div class="document-public">
        <div class="document-shell">
            <a class="document-back" href="{{ $indexUrl }}">
                {{ $isSinhala ? '← සියලු ලේඛන' : '← All Documents' }}
            </a>

            <article class="document-card document-detail">
                <span class="document-type">
                    {{ strtoupper($media?->extension ?? '') }}
                </span>

                <h1>{{ $documentTitle }}</h1>

                @if ($description)
                    <p class="document-description">{{ $description }}</p>
                @endif

                <dl class="document-info">
                    <div>
                        <dt>{{ $isSinhala ? 'ගොනුව' : 'File' }}</dt>
                        <dd>{{ $media?->original_name }}</dd>
                    </div>

                    <div>
                        <dt>{{ $isSinhala ? 'ප්‍රමාණය' : 'Size' }}</dt>
                        <dd>{{ number_format(($media?->size_bytes ?? 0) / 1024, 1) }} KB</dd>
                    </div>

                    <div>
                        <dt>{{ $isSinhala ? 'අනුවාදය' : 'Version' }}</dt>
                        <dd>
                            {{ ($isSinhala ? 'අනුවාදය' : 'Version').' '.$currentVersion->version }}
                        </dd>
                    </div>
                </dl>

                <div class="document-actions">
                    @if ($isPdf)
                        <a
                            class="document-button"
                            href="{{ $viewUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ $isSinhala ? 'PDF බලන්න' : 'View PDF' }}
                        </a>
                    @endif

                    <a class="document-button document-button-light" href="{{ $downloadUrl }}">
                        {{ $isSinhala
                            ? ($isPdf ? 'PDF බාගත කරන්න' : 'ගොනුව බාගත කරන්න')
                            : 'Download File' }}
                    </a>
                </div>

                @if (! $isPdf)
                    <p class="document-note">
                        {{ $isSinhala
                            ? 'මෙම ගොනුව බාගත කර Word, Excel හෝ ගැළපෙන යෙදුමකින් විවෘත කරන්න.'
                            : 'Download this file and open it in Word, Excel or a compatible application.' }}
                    </p>
                @endif
            </article>
        </div>
    </div>
@endsection
