<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-950">
                Media Library
            </h1>

            <p class="mt-1 text-sm leading-6 text-zinc-600">
                Manage uploaded media as a searchable directory or group files automatically
                by the News and Gallery records that use them.
            </p>
        </div>

        @can('media.upload')
            <a
                href="{{ route('admin.media.upload') }}"
                class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800"
            >
                + Upload Media
            </a>
        @endcan
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <button
            type="button"
            wire:click="$set('view', 'active')"
            @class([
                'rounded-2xl border p-5 text-left transition',
                'border-emerald-300 bg-emerald-50' => $view === 'active',
                'border-zinc-200 bg-white hover:bg-zinc-50' => $view !== 'active',
            ])
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Active Media
            </p>

            <p class="mt-2 text-2xl font-black text-zinc-950">
                {{ $activeCount }}
            </p>
        </button>

        <button
            type="button"
            wire:click="$set('view', 'trash')"
            @class([
                'rounded-2xl border p-5 text-left transition',
                'border-red-300 bg-red-50' => $view === 'trash',
                'border-zinc-200 bg-white hover:bg-zinc-50' => $view !== 'trash',
            ])
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Trash
            </p>

            <p class="mt-2 text-2xl font-black text-zinc-950">
                {{ $trashCount }}
            </p>
        </button>
    </div>

    {{-- Library Mode --}}
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-wrap gap-2 p-2">
            <button
                type="button"
                wire:click="setLibraryMode('all')"
                @class([
                    'rounded-xl px-4 py-2.5 text-sm font-bold transition',
                    'bg-zinc-900 text-white' => $libraryMode === 'all',
                    'text-zinc-600 hover:bg-zinc-100' => $libraryMode !== 'all',
                ])
            >
                All Media
            </button>

            <button
                type="button"
                wire:click="setLibraryMode('usage')"
                @class([
                    'rounded-xl px-4 py-2.5 text-sm font-bold transition',
                    'bg-zinc-900 text-white' => $libraryMode === 'usage',
                    'text-zinc-600 hover:bg-zinc-100' => $libraryMode !== 'usage',
                ])
            >
                By News / Gallery
            </button>

            <button
                type="button"
                wire:click="setLibraryMode('other')"
                @class([
                    'rounded-xl px-4 py-2.5 text-sm font-bold transition',
                    'bg-zinc-900 text-white' => $libraryMode === 'other',
                    'text-zinc-600 hover:bg-zinc-100' => $libraryMode !== 'other',
                ])
            >
                Other Media
            </button>
        </div>
    </div>

    @if ($libraryMode === 'other')
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800">
            <strong>Other Media</strong> means files that are not linked directly to a News or Gallery record.
            They may still be used by Pages, Hero Slider, School Leadership, Site Settings, Documents or another module.
        </div>
    @endif

    {{-- Search / Filters --}}
    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div
            @class([
                'grid gap-4',
                'lg:grid-cols-[minmax(0,1fr)_200px_220px_auto]' =>
                    ! ($libraryMode === 'usage' && $selectedUsageGroup === null),
                'lg:grid-cols-[minmax(0,1fr)_180px_200px_220px_auto]' =>
                    $libraryMode === 'usage' && $selectedUsageGroup === null,
            ])
        >
            <div>
                <label
                    for="media-search"
                    class="mb-2 block text-xs font-bold uppercase tracking-wide text-zinc-500"
                >
                    Search
                </label>

                <input
                    id="media-search"
                    type="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="{{ $libraryMode === 'usage' && $selectedUsageGroup === null
                        ? 'News or gallery title...'
                        : 'Media title, filename, alt text...' }}"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm"
                >
            </div>

            @if ($libraryMode === 'usage' && $selectedUsageGroup === null)
                <div>
                    <label
                        for="media-usage-filter"
                        class="mb-2 block text-xs font-bold uppercase tracking-wide text-zinc-500"
                    >
                        Group
                    </label>

                    <select
                        id="media-usage-filter"
                        wire:model.live="usageType"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="all">
                            News + Galleries
                        </option>
                        <option value="news">
                            News
                        </option>
                        <option value="gallery">
                            Galleries
                        </option>
                    </select>
                </div>
            @endif

            <div>
                <label
                    for="media-type-filter"
                    class="mb-2 block text-xs font-bold uppercase tracking-wide text-zinc-500"
                >
                    Type
                </label>

                <select
                    id="media-type-filter"
                    wire:model.live="typeFilter"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm"
                >
                    <option value="">
                        All Types
                    </option>

                    @foreach ($mediaTypes as $mediaType)
                        <option value="{{ $mediaType->value }}">
                            {{ $mediaType->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="media-visibility-filter"
                    class="mb-2 block text-xs font-bold uppercase tracking-wide text-zinc-500"
                >
                    Visibility
                </label>

                <select
                    id="media-visibility-filter"
                    wire:model.live="visibilityFilter"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm"
                >
                    <option value="">
                        All Visibility
                    </option>

                    @foreach ($visibilities as $visibility)
                        <option value="{{ $visibility->value }}">
                            {{ $visibility->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-50"
                >
                    Clear Filters
                </button>
            </div>
        </div>
    </section>

    @if ($libraryMode === 'usage' && $selectedUsageGroup === null)
        {{-- Usage Group Directory --}}
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-zinc-200 bg-zinc-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-zinc-900">
                        Media grouped by content
                    </h2>

                    <p class="mt-1 text-xs text-zinc-500">
                        News language versions are combined into one group.
                        Click a content title to view all media linked to it.
                    </p>
                </div>

                <span class="rounded-full bg-zinc-200 px-3 py-1 text-xs font-bold text-zinc-700">
                    {{ count($usageGroups) }} groups
                </span>
            </div>

            @if ($usageGroups === [])
                <div class="px-6 py-16 text-center">
                    <p class="font-bold text-zinc-700">
                        No grouped media found.
                    </p>

                    <p class="mt-1 text-sm text-zinc-500">
                        Change the current search or filters.
                    </p>
                </div>
            @else
                <div class="divide-y divide-zinc-100">
                    @foreach ($usageGroups as $group)
                        <div
                            wire:key="usage-group-{{ $group['type'] }}-{{ $group['id'] }}"
                            class="flex flex-col gap-4 px-5 py-4 transition hover:bg-zinc-50 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <button
                                type="button"
                                wire:click="openUsageGroup('{{ $group['type'] }}', {{ $group['id'] }})"
                                class="min-w-0 text-left"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide',
                                            'bg-blue-100 text-blue-700' => $group['type'] === 'news',
                                            'bg-violet-100 text-violet-700' => $group['type'] === 'gallery',
                                        ])
                                    >
                                        {{ $group['type'] === 'news' ? 'News' : 'Gallery' }}
                                    </span>

                                    <span class="font-black text-zinc-950 hover:text-emerald-700">
                                        {{ $group['title'] }}
                                    </span>
                                </div>

                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ $group['subtitle'] }}
                                </p>
                            </button>

                            <div class="flex shrink-0 items-center gap-3">
                                <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-black text-zinc-700">
                                    {{ $group['media_count'] }}
                                    {{ $group['media_count'] === 1 ? 'file' : 'files' }}
                                </span>

                                <button
                                    type="button"
                                    wire:click="openUsageGroup('{{ $group['type'] }}', {{ $group['id'] }})"
                                    class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-bold text-zinc-700 hover:bg-zinc-100"
                                >
                                    View Files →
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        {{-- Media Table --}}
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    @if ($libraryMode === 'usage' && $selectedUsageGroup !== null)
                        <button
                            type="button"
                            wire:click="closeUsageGroup"
                            class="mb-2 inline-flex items-center text-xs font-bold text-emerald-700 hover:text-emerald-900"
                        >
                            ← Back to News / Gallery groups
                        </button>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide',
                                    'bg-blue-100 text-blue-700' => $selectedUsageGroup['type'] === 'news',
                                    'bg-violet-100 text-violet-700' => $selectedUsageGroup['type'] === 'gallery',
                                ])
                            >
                                {{ $selectedUsageGroup['type'] === 'news' ? 'News' : 'Gallery' }}
                            </span>

                            <h2 class="font-black text-zinc-950">
                                {{ $selectedUsageGroup['title'] }}
                            </h2>
                        </div>

                        <p class="mt-1 text-xs text-zinc-500">
                            {{ $selectedUsageGroup['subtitle'] }}
                        </p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @if ($selectedUsageGroup['type'] === 'news')
                                @can('news.update')
                                    <a
                                        href="{{ route('admin.news.edit', $selectedUsageGroup['id']) }}"
                                        class="text-xs font-bold text-blue-700 hover:underline"
                                    >
                                        Open News Record
                                    </a>
                                @endcan
                            @else
                                @can('galleries.update')
                                    <a
                                        href="{{ route('admin.galleries.edit', $selectedUsageGroup['id']) }}"
                                        class="text-xs font-bold text-violet-700 hover:underline"
                                    >
                                        Open Gallery Record
                                    </a>
                                @endcan
                            @endif
                        </div>
                    @else
                        <h2 class="font-bold text-zinc-900">
                            @if ($libraryMode === 'other')
                                Other Media
                            @elseif ($view === 'trash')
                                Deleted Media
                            @else
                                Media Directory
                            @endif
                        </h2>

                        <p class="mt-1 text-xs text-zinc-500">
                            Click an image preview or file title to open its details.
                        </p>
                    @endif
                </div>

                @if ($media !== null)
                    <p class="text-sm text-zinc-600">
                        Showing
                        <strong>{{ $media->firstItem() ?? 0 }}</strong>
                        –
                        <strong>{{ $media->lastItem() ?? 0 }}</strong>
                        of
                        <strong>{{ $media->total() }}</strong>
                        items
                    </p>
                @endif
            </div>

            @if ($media === null || $media->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-lg font-bold text-zinc-800">
                        No media found
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        @if ($view === 'trash')
                            No deleted media matches the current selection.
                        @else
                            Upload a file or change the current search filters.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-[1080px] w-full divide-y divide-zinc-200">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    Preview
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    Type
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    Visibility
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    Original File
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    Size
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    Uploaded By
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    Uploaded
                                </th>
                                <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($media as $asset)
                                @php
                                    $type = $asset->getAttribute('type');
                                    $visibility = $asset->getAttribute('visibility');

                                    $displayTitle = is_string($asset->title)
                                        && trim($asset->title) !== ''
                                            ? $asset->title
                                            : $asset->original_name;

                                    $assetUrl = $view === 'active'
                                        ? app(\App\Services\MediaUrlService::class)->namedOriginal($asset)
                                        : null;

                                    $previewUrl = $view === 'active' && $asset->isImage()
                                        ? app(\App\Services\MediaUrlService::class)->thumbnailOrOriginal($asset)
                                        : null;
                                @endphp

                                <tr
                                    wire:key="media-row-{{ $asset->id }}"
                                    class="hover:bg-zinc-50"
                                >
                                    <td class="px-5 py-4 align-top">
                                        <div class="max-w-sm">
                                            @if ($previewUrl)
                                                <a
                                                    href="{{ route('admin.media.edit', $asset) }}"
                                                    aria-label="Open details for {{ $displayTitle }}"
                                                    title="{{ $displayTitle }}"
                                                    class="inline-block overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
                                                >
                                                    <img
                                                        src="{{ $previewUrl }}"
                                                        alt="{{ $asset->alt_text ?: $displayTitle }}"
                                                        width="80"
                                                        height="64"
                                                        loading="lazy"
                                                        decoding="async"
                                                        class="h-16 w-20 object-cover"
                                                    >
                                                </a>
                                            @elseif ($view === 'active')
                                                <a
                                                    href="{{ route('admin.media.edit', $asset) }}"
                                                    class="font-bold text-blue-700 hover:text-blue-900 hover:underline"
                                                >
                                                    {{ $displayTitle }}
                                                </a>
                                            @else
                                                <p class="font-bold text-zinc-900">
                                                    {{ $displayTitle }}
                                                </p>
                                            @endif

                                            <p class="mt-1 text-[11px] text-zinc-400">
                                                #{{ $asset->id }}
                                            </p>

                                            @if (
                                                $view === 'trash'
                                                && $asset->deleted_at
                                            )
                                                <p class="mt-1 text-xs font-semibold text-red-600">
                                                    Deleted {{ $asset->deleted_at->diffForHumans() }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        @if ($type instanceof \App\Enums\MediaType)
                                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold text-zinc-700">
                                                {{ $type->label() }}
                                            </span>
                                        @else
                                            <span class="text-sm text-zinc-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        @if ($visibility instanceof \App\Enums\MediaVisibility)
                                            <span
                                                @class([
                                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                                    'bg-emerald-100 text-emerald-700' =>
                                                        $visibility === \App\Enums\MediaVisibility::Public,
                                                    'bg-amber-100 text-amber-700' =>
                                                        $visibility === \App\Enums\MediaVisibility::Internal,
                                                    'bg-red-100 text-red-700' =>
                                                        $visibility === \App\Enums\MediaVisibility::Restricted,
                                                ])
                                            >
                                                {{ $visibility->label() }}
                                            </span>
                                        @else
                                            <span class="text-sm text-zinc-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        <p
                                            class="max-w-52 truncate text-sm text-zinc-600"
                                            title="{{ $asset->original_name }}"
                                        >
                                            {{ $asset->original_name }}
                                        </p>
                                        @if ($assetUrl)
                                            <x-admin.copy-path :path="$assetUrl"
                                                :label="$asset->isImage() ? 'Public image path' : 'Public document path'"
                                                class="mt-3 min-w-64 max-w-sm" />
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 align-top text-sm text-zinc-600">
                                        @if (
                                            is_int($asset->size_bytes)
                                            && $asset->size_bytes > 0
                                        )
                                            @if ($asset->size_bytes >= 1048576)
                                                {{ number_format(
                                                    $asset->size_bytes / 1048576,
                                                    2,
                                                ) }}
                                                MB
                                            @else
                                                {{ number_format(
                                                    $asset->size_bytes / 1024,
                                                    1,
                                                ) }}
                                                KB
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 align-top text-sm text-zinc-600">
                                        {{ $asset->uploader?->name ?? 'Unknown' }}
                                    </td>

                                    <td class="px-5 py-4 align-top text-sm text-zinc-600">
                                        @if ($asset->created_at)
                                            {{ $asset->created_at->format('d M Y') }}

                                            <span class="mt-0.5 block text-[11px] text-zinc-400">
                                                {{ $asset->created_at->format('h:i A') }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-right align-top">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            @if ($view === 'active')
                                                @can('media.delete')
                                                    <x-admin.icon-action label="Move to Trash" icon="trash" tone="danger"
                                                        type="button"
                                                        wire:click="deleteMedia({{ $asset->id }})"
                                                        wire:confirm="Move this media asset to trash?"
                                                        wire:loading.attr="disabled"
                                                        wire:target="deleteMedia({{ $asset->id }})"
                                                    />
                                                @endcan
                                            @else
                                                @can('media.delete')
                                                    <x-admin.icon-action label="Restore" icon="arrow-uturn-left" tone="success"
                                                        type="button"
                                                        wire:click="restoreMedia({{ $asset->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="restoreMedia({{ $asset->id }})"
                                                    />

                                                    <x-admin.icon-action label="Delete Permanently" icon="trash" tone="danger"
                                                        type="button"
                                                        wire:click="forceDeleteMedia({{ $asset->id }})"
                                                        wire:confirm="Permanently delete this media asset? This action cannot be undone."
                                                        wire:loading.attr="disabled"
                                                        wire:target="forceDeleteMedia({{ $asset->id }})"
                                                    />
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($media !== null && $media->hasPages())
                <div class="border-t border-zinc-200 bg-zinc-50 px-5 py-4">
                    {{ $media->links() }}
                </div>
            @endif
        </section>
    @endif
</div>
