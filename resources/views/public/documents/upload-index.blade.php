@extends(
    ($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout")
        ? "theme-{$activeTheme}::layout"
        : 'layouts.public'
)

@php
    $isSinhala = $currentLocale === 'si';
    $heading = $isSinhala ? 'ලේඛන' : 'Documents';
@endphp

@section('title', $heading.' | '.config('app.name'))

@push('styles')
    @include('public.documents.upload-styles')
@endpush

@section('content')
    <div class="document-public">
        <div class="document-shell">
            <header class="document-heading">
                <span>{{ $isSinhala ? 'සංඥා පාසල' : 'School of Signals' }}</span>
                <h1>{{ $heading }}</h1>
                <p>
                    {{ $isSinhala
                        ? 'අවශ්‍ය ලේඛන මෙතැනින් බලන්න හෝ බාගත කරන්න.'
                        : 'View or download the documents you need.' }}
                </p>
            </header>

            <div class="document-list">
                @forelse ($documents as $document)
                    @php
                        $detailUrl = $isSinhala
                            ? route('documents.show.localized', [
                                'locale' => 'si',
                                'slug' => $document->slug,
                            ])
                            : route('documents.show', ['slug' => $document->slug]);

                        $description = $document->descriptionForLocale($currentLocale);
                    @endphp

                    <article class="document-card">
                        <h2>
                            <a href="{{ $detailUrl }}">
                                {{ $document->titleForLocale($currentLocale) }}
                            </a>
                        </h2>

                        @if ($description)
                            <p>{{ $description }}</p>
                        @endif

                        <div class="document-actions">
                            <a class="document-button" href="{{ $detailUrl }}">
                                {{ $isSinhala ? 'විස්තර බලන්න' : 'View Details' }}
                            </a>

                            <a
                                class="document-button document-button-light"
                                href="{{ route('document-files.download', [
                                    'locale' => $currentLocale,
                                    'slug' => $document->slug,
                                ]) }}"
                            >
                                {{ $isSinhala ? 'බාගත කරන්න' : 'Download' }}
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="document-card">
                        <p>
                            {{ $isSinhala
                                ? 'දැනට ප්‍රකාශිත ලේඛන නොමැත.'
                                : 'No published documents are available.' }}
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($documents->hasPages())
                <nav class="document-pagination" aria-label="Pagination">
                    @if ($documents->previousPageUrl())
                        <a href="{{ $documents->previousPageUrl() }}">
                            {{ $isSinhala ? '← පෙර' : '← Previous' }}
                        </a>
                    @endif

                    <span>{{ $documents->currentPage() }} / {{ $documents->lastPage() }}</span>

                    @if ($documents->nextPageUrl())
                        <a href="{{ $documents->nextPageUrl() }}">
                            {{ $isSinhala ? 'ඊළඟ →' : 'Next →' }}
                        </a>
                    @endif
                </nav>
            @endif
        </div>
    </div>
@endsection
