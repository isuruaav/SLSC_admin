@extends(
    ($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout")
        ? "theme-{$activeTheme}::layout"
        : 'layouts.public'
)

@php
    $isSinhala = $currentLocale === 'si';

    $documentTitle = $document->titleForLocale($currentLocale);
    $description = $document->descriptionForLocale($currentLocale);

    $indexUrl = $isSinhala
        ? route('documents.index.localized', ['locale' => 'si'])
        : route('documents.index');

    $documentUrl = $isSinhala
        ? route('documents.show.localized', [
            'locale' => 'si',
            'slug' => $document->slug,
        ])
        : route('documents.show', ['slug' => $document->slug]);

    $media = $currentVersion->media;
    $originalName = $media?->original_name;
    $sizeBytes = $media?->size_bytes;

    $formattedSize = null;

    if (is_numeric($sizeBytes) && (int) $sizeBytes > 0) {
        $bytes = (int) $sizeBytes;

        $formattedSize = $bytes >= 1048576
            ? number_format($bytes / 1048576, 2) . ' MB'
            : number_format($bytes / 1024, 1) . ' KB';
    }
@endphp

@section('title', $documentTitle . ' | ' . config('app.name'))

@push('styles')
    @include('public.documents.styles')
@endpush

@section('content')
    <article class="sos-documents" lang="{{ $currentLocale }}">
        <header class="doc-hero">
            <div class="doc-shell">
                <a class="doc-back" href="{{ $indexUrl }}">
                    &larr; {{ $isSinhala ? 'සියලු ලේඛන' : 'All Documents' }}
                </a>

                <p class="doc-kicker">
                    {{ $isSinhala ? 'නිල ප්‍රකාශනය' : 'Official Publication' }}
                </p>

                <h1>{{ $documentTitle }}</h1>

                <p class="doc-intro">
                    PDF · {{ $isSinhala ? 'අනුවාදය' : 'Version' }}
                    {{ $currentVersion->version }}
                </p>
            </div>
        </header>

        <div class="doc-content">
            <div class="doc-shell doc-detail-grid">
                <div>
                    @if ($description !== null)
                        <section class="doc-panel">
                            <h2>
                                {{ $isSinhala ? 'ලේඛනය පිළිබඳව' : 'About this document' }}
                            </h2>

                            <p class="doc-text">{{ $description }}</p>
                        </section>
                    @endif

                    <section class="doc-panel">
                        <h2>
                            {{ $isSinhala ? 'ලේඛන විස්තර' : 'Document Details' }}
                        </h2>

                        <dl>
                            @if ($document->category)
                                <div class="doc-data-row">
                                    <dt>{{ $isSinhala ? 'කාණ්ඩය' : 'Category' }}</dt>
                                    <dd>{{ $document->category->name }}</dd>
                                </div>
                            @endif

                            <div class="doc-data-row">
                                <dt>{{ $isSinhala ? 'ලේඛනයේ දිනය' : 'Document Date' }}</dt>
                                <dd>{{ $document->document_date?->format('Y.m.d') ?? '—' }}</dd>
                            </div>

                            <div class="doc-data-row">
                                <dt>{{ $isSinhala ? 'ප්‍රකාශිත දිනය' : 'Published' }}</dt>
                                <dd>{{ $document->published_at?->format('Y.m.d') ?? '—' }}</dd>
                            </div>

                            <div class="doc-data-row">
                                <dt>{{ $isSinhala ? 'වත්මන් අනුවාදය' : 'Current Version' }}</dt>
                                <dd>
                                    {{ $currentVersion->version }}

                                    @if ($currentVersion->version_label)
                                        — {{ $currentVersion->version_label }}
                                    @endif
                                </dd>
                            </div>

                            @if (is_string($originalName) && trim($originalName) !== '')
                                <div class="doc-data-row">
                                    <dt>{{ $isSinhala ? 'ගොනුව' : 'File' }}</dt>
                                    <dd>{{ $originalName }}</dd>
                                </div>
                            @endif

                            @if ($formattedSize !== null)
                                <div class="doc-data-row">
                                    <dt>{{ $isSinhala ? 'ගොනුවේ ප්‍රමාණය' : 'File Size' }}</dt>
                                    <dd>{{ $formattedSize }}</dd>
                                </div>
                            @endif
                        </dl>
                    </section>

                    @if ($currentVersion->change_note)
                        <section class="doc-panel">
                            <h2>
                                {{ $isSinhala ? 'අනුවාදයේ තොරතුරු' : 'Version Information' }}
                            </h2>

                            <p class="doc-text">{{ $currentVersion->change_note }}</p>
                        </section>
                    @endif
                </div>

                <aside>
                    <section class="doc-panel">
                        <span class="doc-badge">PDF</span>

                        <h2 style="margin-top:16px">
                            {{ $isSinhala ? 'කියවන්න හෝ බාගත කරන්න' : 'Read or Download' }}
                        </h2>

                        <p class="doc-description">
                            {{ $isSinhala
                                ? 'මෙම ලේඛනයේ වත්මන් PDF අනුවාදය මෙතැනින් ලබාගන්න.'
                                : 'Access the current PDF version of this document.' }}
                        </p>

                        <div class="doc-sidebar-actions">
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
                                {{ $isSinhala ? 'PDF බාගත කරන්න' : 'Download PDF' }}
                            </a>
                        </div>
                    </section>

                    <section class="doc-panel">
                        <h2>{{ $isSinhala ? 'ලේඛනයේ සබැඳිය' : 'Document Link' }}</h2>

                        <p class="doc-description">
                            {{ $isSinhala
                                ? 'PDF අනුවාදය යාවත්කාලීන කළද මෙම සබැඳිය වෙනස් නොවේ.'
                                : 'This link remains the same when the PDF version is updated.' }}
                        </p>

                        <a class="doc-url" href="{{ $documentUrl }}">
                            {{ $documentUrl }}
                        </a>
                    </section>
                </aside>
            </div>
        </div>
    </article>
@endsection