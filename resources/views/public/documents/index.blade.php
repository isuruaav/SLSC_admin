@extends(
    ($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout")
        ? "theme-{$activeTheme}::layout"
        : 'layouts.public'
)

@php
    $isSinhala = $currentLocale === 'si';
    $heading = $isSinhala ? 'ලේඛන' : 'Documents';
@endphp

@section('title', $heading . ' | ' . config('app.name'))

@push('styles')
    @include('public.documents.styles')
@endpush

@section('content')
    <div class="sos-documents" lang="{{ $currentLocale }}">
        <header class="doc-hero">
            <div class="doc-shell">
                <p class="doc-kicker">
                    {{ $isSinhala ? 'ප්‍රකාශන සහ බාගත කිරීම්' : 'Publications & Downloads' }}
                </p>

                <h1>{{ $heading }}</h1>

                <p class="doc-intro">
                    {{ $isSinhala
                        ? 'නිල ලේඛන, වාර්තා සහ ප්‍රකාශන මෙතැනින් කියවන්න හෝ PDF ලෙස බාගත කරන්න.'
                        : 'Browse official documents, reports and publications. View or download the latest PDF version.' }}
                </p>
            </div>
        </header>

        <section class="doc-content" aria-label="{{ $heading }}">
            <div class="doc-shell">
                @if ($documents->isNotEmpty())
                    <div class="doc-list">
                        @foreach ($documents as $document)
                            @php
                                $documentTitle = $document->titleForLocale($currentLocale);
                                $description = $document->descriptionForLocale($currentLocale);

                                $detailsUrl = $isSinhala
                                    ? route('documents.show.localized', [
                                        'locale' => 'si',
                                        'slug' => $document->slug,
                                    ])
                                    : route('documents.show', ['slug' => $document->slug]);
                            @endphp

                            <article class="doc-card">
                                <div class="doc-copy">
                                    <div class="doc-meta">
                                        <span class="doc-badge">PDF</span>

                                        @if ($document->category)
                                            <span>{{ $document->category->name }}</span>
                                        @endif

                                        @if ($document->document_date)
                                            <time datetime="{{ $document->document_date->format('Y-m-d') }}">
                                                {{ $document->document_date->format('Y.m.d') }}
                                            </time>
                                        @endif
                                    </div>

                                    <h2>
                                        <a href="{{ $detailsUrl }}">
                                            {{ $documentTitle }}
                                        </a>
                                    </h2>

                                    @if ($description !== null)
                                        <p class="doc-description">
                                            {{ \Illuminate\Support\Str::limit($description, 200) }}
                                        </p>
                                    @endif

                                    <p class="doc-version">
                                        {{ $isSinhala ? 'වත්මන් අනුවාදය' : 'Current version' }}:
                                        {{ $document->current_version }}
                                    </p>
                                </div>

                                <div class="doc-actions">
                                    <a class="doc-button" href="{{ $detailsUrl }}">
                                        {{ $isSinhala ? 'විස්තර' : 'Details' }}
                                    </a>

                                    <a
                                        class="doc-button"
                                        href="{{ route('documents.view', ['slug' => $document->slug]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ $isSinhala ? 'PDF බලන්න' : 'View PDF' }}
                                    </a>

                                    <a
                                        class="doc-button doc-button-primary"
                                        href="{{ route('documents.download', ['slug' => $document->slug]) }}"
                                    >
                                        {{ $isSinhala ? 'බාගත කරන්න' : 'Download' }}
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($documents->hasPages())
                        <nav
                            class="doc-pagination"
                            aria-label="{{ $isSinhala ? 'ලේඛන පිටු' : 'Document pagination' }}"
                        >
                            <p>
                                {{ $isSinhala ? 'පිටුව' : 'Page' }}
                                {{ $documents->currentPage() }}
                                {{ $isSinhala ? '/ මුළු පිටු' : 'of' }}
                                {{ $documents->lastPage() }}
                            </p>

                            <div class="doc-pagination-actions">
                                @if ($documents->onFirstPage())
                                    <span class="doc-button doc-disabled" aria-disabled="true">
                                        {{ $isSinhala ? 'පෙර පිටුව' : 'Previous' }}
                                    </span>
                                @else
                                    <a class="doc-button" href="{{ $documents->previousPageUrl() }}" rel="prev">
                                        {{ $isSinhala ? 'පෙර පිටුව' : 'Previous' }}
                                    </a>
                                @endif

                                @if ($documents->hasMorePages())
                                    <a class="doc-button" href="{{ $documents->nextPageUrl() }}" rel="next">
                                        {{ $isSinhala ? 'ඊළඟ පිටුව' : 'Next' }}
                                    </a>
                                @else
                                    <span class="doc-button doc-disabled" aria-disabled="true">
                                        {{ $isSinhala ? 'ඊළඟ පිටුව' : 'Next' }}
                                    </span>
                                @endif
                            </div>
                        </nav>
                    @endif
                @else
                    <div class="doc-empty">
                        <span class="doc-badge" aria-hidden="true">PDF</span>

                        <h2>
                            {{ $isSinhala ? 'දැනට ලේඛන නොමැත' : 'No documents available' }}
                        </h2>

                        <p>
                            {{ $isSinhala
                                ? 'ප්‍රකාශයට පත් කළ ලේඛන මෙහි දිස්වනු ඇත.'
                                : 'Published documents will appear here.' }}
                        </p>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection