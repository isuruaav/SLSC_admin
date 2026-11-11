<div class="space-y-6">
    <div class="flex flex-col gap-4
               lg:flex-row lg:items-start
               lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold
                           text-zinc-950">
                    Revision History
                </h1>

                <span
                    class="inline-flex rounded-full
                           bg-zinc-100 px-3 py-1
                           text-xs font-semibold
                           text-zinc-700">
                    {{ $page->status->label() }}
                </span>
            </div>

            <p class="mt-1 text-sm
                       text-zinc-600">
                {{ $page->title }}
            </p>

            <p class="mt-1 font-mono
                       text-xs text-zinc-500">
                /pages/{{ $page->slug }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-admin.icon-action label="Preview Current" icon="eye" tone="neutral"
                href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener noreferrer"
            />

            <a href="{{ route('admin.pages.index') }}" wire:navigate
                class="inline-flex items-center
                       justify-center rounded-xl
                       border border-zinc-300
                       bg-white px-4 py-2.5
                       text-sm font-semibold
                       text-zinc-700
                       hover:bg-zinc-50">
                Back to Pages
            </a>
        </div>
    </div>

    @if (session('status'))
        <div
            class="rounded-xl border
                   border-emerald-200
                   bg-emerald-50 px-4 py-3
                   text-sm font-medium
                   text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @error('revision')
        <div
            class="rounded-xl border
                   border-red-200
                   bg-red-50 px-4 py-3
                   text-sm font-medium
                   text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="grid gap-6
               xl:grid-cols-[320px_minmax(0,1fr)]">
        {{-- Revision list --}}
        <aside
            class="overflow-hidden rounded-2xl
                   border border-zinc-200
                   bg-white shadow-sm">
            <div class="border-b border-zinc-200
                       bg-zinc-50 px-5 py-4">
                <h2 class="font-semibold
                           text-zinc-900">
                    Saved Versions
                </h2>

                <p class="mt-1 text-xs
                           text-zinc-500">
                    {{ $revisions->count() }}
                    revision(s)
                </p>
            </div>

            @forelse ($revisions as $revision)
                <button type="button" wire:key="revision-{{ $revision->id }}"
                    wire:click="selectRevision({{ $revision->id }})"
                    class="block w-full border-b
                           border-zinc-100 px-5 py-4
                           text-left transition
                           hover:bg-zinc-50
                           {{ $selectedRevisionId === $revision->id ? 'bg-emerald-50' : 'bg-white' }}">
                    <div class="flex items-start
                               justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold
           text-zinc-900">
                                Revision #{{ $revision->revision_number }}
                            </p>

                            <p class="mt-1 text-xs
                                       text-zinc-500">
                                {{ $revision->created_at?->format('Y-m-d H:i') }}
                            </p>
                        </div>

                        <span
                            class="rounded-full
                                   bg-zinc-100
                                   px-2 py-1
                                   text-[11px]
                                   font-semibold
                                   text-zinc-600">
                            {{ $revision->status->label() }}
                        </span>
                    </div>

                    @if ($revision->change_summary)
                        <p class="mt-3 text-xs
                                   leading-5 text-zinc-600">
                            {{ $revision->change_summary }}
                        </p>
                    @endif

                    <p class="mt-2 text-xs
                               text-zinc-400">
                        By
                        {{ $revision->creator?->name ?? 'System' }}
                    </p>

                    @if ($revision->restored_from_revision_number)
                        <p
                            class="mt-2 text-xs
                                   font-semibold
                                   text-violet-700">
                            Restored from
                            revision
                            #{{ $revision->restored_from_revision_number }}
                        </p>
                    @endif
                </button>
            @empty
                <div class="px-5 py-12
                           text-center">
                    <p class="font-semibold
                               text-zinc-700">
                        No revisions found
                    </p>

                    <p class="mt-1 text-sm
                               text-zinc-500">
                        A revision will appear
                        after this page is saved.
                    </p>
                </div>
            @endforelse
        </aside>

        {{-- Revision detail --}}
        <section class="space-y-6">
            @if ($selectedRevision)
                <div
                    class="rounded-2xl border
                           border-zinc-200
                           bg-white p-6 shadow-sm">
                    <div
                        class="flex flex-col gap-4
                               lg:flex-row
                               lg:items-start
                               lg:justify-between">
                        <div>
                            <h2 class="text-xl font-bold
           text-zinc-950">
                                Revision #{{ $selectedRevision->revision_number }}
                            </h2>

                            <p class="mt-1 text-sm
                                       text-zinc-500">
                                Saved
                                {{ $selectedRevision->created_at?->format('F j, Y \a\t H:i') }}
                            </p>

                            <p class="mt-1 text-sm
                                       text-zinc-500">
                                Created by
                                {{ $selectedRevision->creator?->name ?? 'System' }}
                            </p>
                        </div>

                        @can('pages.revisions.restore')
                            @if ($page->status === \App\Enums\PageStatus::Draft)
                                <x-admin.icon-action label="Restore Revision" icon="arrow-uturn-left" tone="success"
                                    type="button"
                                    wire:click="restoreRevision(
                                        {{ $selectedRevision->id }}
                                    )"
                                    wire:confirm="
                                        Restore revision
                                       Revision #{{ $selectedRevision->revision_number }}?
                                        The current Draft
                                        will be preserved as
                                        revision history.
                                    "
                                    wire:loading.attr="disabled"
                                    wire:target="restoreRevision(
                                        {{ $selectedRevision->id }}
                                    )"
                                />
                            @else
                                <div
                                    class="rounded-xl border
                                           border-amber-200
                                           bg-amber-50
                                           px-4 py-3
                                           text-xs font-medium
                                           text-amber-800">
                                    Return this page to
                                    Draft before restoring
                                    an older revision.
                                </div>
                            @endif
                        @endcan
                    </div>

                    @if ($selectedRevision->change_summary)
                        <div
                            class="mt-5 rounded-xl
                                   bg-zinc-50
                                   px-4 py-3">
                            <p
                                class="text-xs font-semibold
                                       uppercase tracking-wide
                                       text-zinc-500">
                                Change Summary
                            </p>

                            <p class="mt-1 text-sm
                                       text-zinc-700">
                                {{ $selectedRevision->change_summary }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Field comparison --}}
                <div
                    class="overflow-hidden rounded-2xl
                           border border-zinc-200
                           bg-white shadow-sm">
                    <div class="border-b border-zinc-200
                               px-5 py-4">
                        <h2 class="font-semibold
                                   text-zinc-900">
                            Compare with Current Page
                        </h2>

                        <p class="mt-1 text-sm
                                   text-zinc-500">
                            Left side is the selected
                            revision. Right side is the
                            current page.
                        </p>
                    </div>

                    <div class="grid
                               lg:grid-cols-2">
                        <div
                            class="border-b
                                   border-zinc-200
                                   p-5
                                   lg:border-b-0
                                   lg:border-r">
                            <p
                                class="mb-4 text-xs
                                       font-bold uppercase
                                       tracking-wide
                                       text-violet-700">
                                Revision
                                #{{ $selectedRevision->revision_number }}
                            </p>

                            <dl class="space-y-5">
                                <div>
                                    <dt
                                        class="text-xs
                                               font-semibold
                                               text-zinc-500">
                                        Title
                                    </dt>

                                    <dd
                                        class="mt-1 text-sm
                                               text-zinc-900">
                                        {{ $selectedRevision->title }}
                                    </dd>
                                </div>

                                <div>
                                    <dt
                                        class="text-xs
                                               font-semibold
                                               text-zinc-500">
                                        Slug
                                    </dt>

                                    <dd
                                        class="mt-1 font-mono
                                               text-sm
                                               text-zinc-700">
                                        /{{ $selectedRevision->slug }}
                                    </dd>
                                </div>

                                <div>
                                    <dt
                                        class="text-xs
                                               font-semibold
                                               text-zinc-500">
                                        Status
                                    </dt>

                                    <dd
                                        class="mt-1 text-sm
                                               text-zinc-700">
                                        {{ $selectedRevision->status->label() }}
                                    </dd>
                                </div>

                                <div>
                                    <dt
                                        class="text-xs
                                               font-semibold
                                               text-zinc-500">
                                        Short Description
                                    </dt>

                                    <dd
                                        class="mt-1 whitespace-pre-line
                                               text-sm
                                               text-zinc-700">
                                        {{ $selectedRevision->excerpt ?: '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="p-5">
                            <p
                                class="mb-4 text-xs
                                       font-bold uppercase
                                       tracking-wide
                                       text-emerald-700">
                                Current Page
                            </p>

                            <dl class="space-y-5">
                                <div>
                                    <dt
                                        class="text-xs
                                               font-semibold
                                               text-zinc-500">
                                        Title
                                    </dt>

                                    <dd
                                        class="mt-1 text-sm
                                               text-zinc-900">
                                        {{ $page->title }}
                                    </dd>
                                </div>

                                <div>
                                    <dt
                                        class="text-xs
                                               font-semibold
                                               text-zinc-500">
                                        Slug
                                    </dt>

                                    <dd
                                        class="mt-1 font-mono
                                               text-sm
                                               text-zinc-700">
                                        /{{ $page->slug }}
                                    </dd>
                                </div>

                                <div>
                                    <dt
                                        class="text-xs
                                               font-semibold
                                               text-zinc-500">
                                        Status
                                    </dt>

                                    <dd
                                        class="mt-1 text-sm
                                               text-zinc-700">
                                        {{ $page->status->label() }}
                                    </dd>
                                </div>

                                <div>
                                    <dt
                                        class="text-xs
                                               font-semibold
                                               text-zinc-500">
                                        Short Description
                                    </dt>

                                    <dd
                                        class="mt-1 whitespace-pre-line
                                               text-sm
                                               text-zinc-700">
                                        {{ $page->excerpt ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                    <h2 class="border-b border-zinc-200 px-5 py-4 font-semibold text-zinc-900">Custom CSS Comparison</h2>
                    <div class="grid lg:grid-cols-2">
                        <div class="min-w-0 border-b border-zinc-200 p-5 lg:border-b-0 lg:border-r">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-violet-700">Revision CSS</p>
                            <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-zinc-50 p-4 text-xs">{{ $selectedRevision->custom_css ?: 'No custom CSS.' }}</pre>
                        </div>
                        <div class="min-w-0 p-5">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-emerald-700">Current CSS</p>
                            <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-zinc-50 p-4 text-xs">{{ $page->custom_css ?: 'No custom CSS.' }}</pre>
                        </div>
                    </div>
                </section>

                {{-- Content comparison --}}
                <div
                    class="overflow-hidden rounded-2xl
                           border border-zinc-200
                           bg-white shadow-sm">
                    <div class="border-b border-zinc-200
                               px-5 py-4">
                        <h2 class="font-semibold
                                   text-zinc-900">
                            Content Comparison
                        </h2>
                    </div>

                    <div class="grid
                               lg:grid-cols-2">
                        <div
                            class="border-b
                                   border-zinc-200
                                   p-5
                                   lg:border-b-0
                                   lg:border-r">
                            <p
                                class="mb-5 text-xs
                                       font-bold uppercase
                                       tracking-wide
                                       text-violet-700">
                                Revision Content
                            </p>

                            @if ($revisionContent !== '')
                                <div class="trix-content
                                           awcms-content">
                                    {!! $revisionContent !!}
                                </div>
                            @else
                                <p class="text-sm
                                           text-zinc-400">
                                    No content.
                                </p>
                            @endif
                        </div>

                        <div class="p-5">
                            <p
                                class="mb-5 text-xs
                                       font-bold uppercase
                                       tracking-wide
                                       text-emerald-700">
                                Current Content
                            </p>

                            @if ($currentContent !== '')
                                <div class="trix-content
                                           awcms-content">
                                    {!! $currentContent !!}
                                </div>
                            @else
                                <p class="text-sm
                                           text-zinc-400">
                                    No content.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div
                    class="rounded-2xl border
                           border-zinc-200
                           bg-white px-6 py-16
                           text-center shadow-sm">
                    <p class="font-semibold
                               text-zinc-700">
                        No revision selected
                    </p>

                    <p class="mt-1 text-sm
                               text-zinc-500">
                        Select a revision from
                        the history list.
                    </p>
                </div>
            @endif
        </section>
    </div>
    @if ($selectedRevision)
        <section class="rounded-2xl border border-zinc-200 bg-white p-5"><h2 class="mb-4 font-bold">Revision compared with current content</h2><x-admin.field-diff :before="$selectedRevision->only(['title','slug','summary','excerpt','content','seo_title','seo_description'])" :after="$page->only(['title','slug','summary','excerpt','content','seo_title','seo_description'])" /></section>
    @endif
</div>
