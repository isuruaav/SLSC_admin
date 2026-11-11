<div class="mx-auto max-w-7xl space-y-6">

    {{-- =====================================================
         HEADER
    ====================================================== --}}
    <div
        class="flex flex-col gap-4
               lg:flex-row
               lg:items-start
               lg:justify-between"
    >
        <div>
            @can('news.update')
                <a
                    href="{{ route(
                        'admin.news.edit',
                        [
                            'news' => $news->id,
                        ]
                    ) }}"
                    wire:navigate
                    class="text-sm font-semibold
                           text-emerald-700
                           transition
                           hover:text-emerald-800"
                >
                    ← Back to Article
                </a>
            @else
                <a
                    href="{{ route('admin.news.index') }}"
                    wire:navigate
                    class="text-sm font-semibold
                           text-emerald-700
                           transition
                           hover:text-emerald-800"
                >
                    ← Back to News
                </a>
            @endcan

            <h1
                class="mt-2 text-2xl
                       font-bold
                       text-zinc-950"
            >
                Revision History
            </h1>

            <p
                class="mt-1 max-w-3xl
                       text-sm leading-6
                       text-zinc-600"
            >
                Previous saved versions of

                <span class="font-semibold text-zinc-800">
                    {{ $news->title }}
                </span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('admin.news.index') }}"
                wire:navigate
                class="inline-flex items-center
                       justify-center
                       rounded-xl
                       border border-zinc-300
                       bg-white
                       px-4 py-2.5
                       text-sm font-semibold
                       text-zinc-700
                       shadow-sm
                       transition
                       hover:bg-zinc-50"
            >
                News List
            </a>

            @can('news.update')
                <a
                    href="{{ route(
                        'admin.news.edit',
                        [
                            'news' => $news->id,
                        ]
                    ) }}"
                    wire:navigate
                    class="inline-flex items-center
                           justify-center
                           rounded-xl
                           bg-emerald-700
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           shadow-sm
                           transition
                           hover:bg-emerald-800"
                >
                    Edit Article
                </a>
            @endcan
        </div>
    </div>


    {{-- =====================================================
         ARTICLE SUMMARY
    ====================================================== --}}
    <section
        class="rounded-2xl
               border border-zinc-200
               bg-white
               shadow-sm"
    >
        <div
            class="grid gap-4 p-5
                   sm:grid-cols-2
                   xl:grid-cols-4"
        >
            {{-- Current Article --}}
            <div>
                <p
                    class="text-xs font-bold
                           uppercase tracking-wide
                           text-zinc-500"
                >
                    Current Article
                </p>

                <p
                    class="mt-1 text-sm
                           font-semibold
                           text-zinc-900"
                >
                    {{ $news->title }}
                </p>
            </div>

            {{-- Current Status --}}
            <div>
                <p
                    class="text-xs font-bold
                           uppercase tracking-wide
                           text-zinc-500"
                >
                    Current Status
                </p>

                <div class="mt-2">
                    <span
                        @class([
                            'inline-flex rounded-full px-2.5 py-1 text-xs font-bold uppercase tracking-wide',

                            'bg-zinc-100 text-zinc-700' =>
                                $news->status ===
                                \App\Enums\NewsStatus::Draft,

                            'bg-blue-50 text-blue-700' =>
                                $news->status ===
                                \App\Enums\NewsStatus::Submitted,

                            'bg-violet-50 text-violet-700' =>
                                $news->status ===
                                \App\Enums\NewsStatus::Approved,

                            'bg-emerald-50 text-emerald-700' =>
                                $news->status ===
                                \App\Enums\NewsStatus::Published,

                            'bg-amber-50 text-amber-700' =>
                                $news->status ===
                                \App\Enums\NewsStatus::Archived,
                        ])
                    >
                        {{ $news->status->label() }}
                    </span>
                </div>
            </div>

            {{-- Category --}}
            <div>
                <p
                    class="text-xs font-bold
                           uppercase tracking-wide
                           text-zinc-500"
                >
                    Category
                </p>

                <p
                    class="mt-1 text-sm
                           font-semibold
                           text-zinc-900"
                >
                    {{ $news->category?->name ?? 'Uncategorised' }}
                </p>
            </div>

            {{-- Revision Count --}}
            <div>
                <p
                    class="text-xs font-bold
                           uppercase tracking-wide
                           text-zinc-500"
                >
                    Stored Revisions
                </p>

                <p
                    class="mt-1 text-sm
                           font-semibold
                           text-zinc-900"
                >
                    {{ $revisions->total() }}
                </p>
            </div>
        </div>
    </section>


    {{-- =====================================================
         MAIN CONTENT
    ====================================================== --}}
    <div
        class="grid gap-6
               xl:grid-cols-[minmax(0,420px)_minmax(0,1fr)]"
    >

        {{-- =================================================
             REVISION LIST
        ================================================== --}}
        <section
            class="overflow-hidden
                   rounded-2xl
                   border border-zinc-200
                   bg-white
                   shadow-sm"
        >
            <div
                class="border-b border-zinc-200
                       px-5 py-4"
            >
                <h2
                    class="font-bold
                           text-zinc-900"
                >
                    Saved Revisions
                </h2>

                <p
                    class="mt-1 text-xs
                           text-zinc-500"
                >
                    Newest revision appears first.
                </p>
            </div>

            @if ($revisions->isEmpty())
                <div
                    class="px-6 py-12
                           text-center"
                >
                    <div
                        class="mx-auto flex
                               h-12 w-12
                               items-center
                               justify-center
                               rounded-full
                               bg-zinc-100
                               text-xl
                               text-zinc-600"
                    >
                        ↻
                    </div>

                    <h3
                        class="mt-4
                               font-semibold
                               text-zinc-900"
                    >
                        No Revisions Yet
                    </h3>

                    <p
                        class="mx-auto mt-2
                               max-w-sm
                               text-sm leading-6
                               text-zinc-500"
                    >
                        A revision is created automatically
                        whenever an editable news article is
                        successfully updated.
                    </p>
                </div>
            @else
                <div class="divide-y divide-zinc-100">
                    @foreach ($revisions as $revision)
                        <button
                            type="button"
                            wire:key="news-revision-{{ $revision->id }}"
                            wire:click="selectRevision({{ $revision->id }})"
                            @class([
                                'block w-full px-5 py-4 text-left transition',

                                'bg-emerald-50' =>
                                    $selectedRevisionId ===
                                    $revision->id,

                                'hover:bg-zinc-50' =>
                                    $selectedRevisionId !==
                                    $revision->id,
                            ])
                        >
                            <div
                                class="flex items-start
                                       justify-between
                                       gap-4"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap
                                               items-center
                                               gap-2"
                                    >
                                        <span
                                            class="text-sm
                                                   font-bold
                                                   text-zinc-900"
                                        >
                                            Revision
                                            {{ $revision->revision_number }}
                                        </span>

                                        <span
                                            @class([
                                                'inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide',

                                                'bg-zinc-100 text-zinc-700' =>
                                                    $revision->status ===
                                                    \App\Enums\NewsStatus::Draft,

                                                'bg-blue-50 text-blue-700' =>
                                                    $revision->status ===
                                                    \App\Enums\NewsStatus::Submitted,

                                                'bg-violet-50 text-violet-700' =>
                                                    $revision->status ===
                                                    \App\Enums\NewsStatus::Approved,

                                                'bg-emerald-50 text-emerald-700' =>
                                                    $revision->status ===
                                                    \App\Enums\NewsStatus::Published,

                                                'bg-amber-50 text-amber-700' =>
                                                    $revision->status ===
                                                    \App\Enums\NewsStatus::Archived,
                                            ])
                                        >
                                            {{ $revision->status->label() }}
                                        </span>
                                    </div>

                                    <p
                                        class="mt-2 truncate
                                               text-sm
                                               font-medium
                                               text-zinc-700"
                                    >
                                        {{ $revision->title }}
                                    </p>

                                    <p
                                        class="mt-2 text-xs
                                               text-zinc-500"
                                    >
                                        {{ $revision->created_at?->format(
                                            'd M Y, H:i'
                                        ) }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs
                                               text-zinc-500"
                                    >
                                        By
                                        {{ $revision->creator?->name
                                            ?? 'Unknown user' }}
                                    </p>

                                    @if ($revision->reason)
                                        <p
                                            class="mt-1 truncate
                                                   text-xs
                                                   text-zinc-400"
                                        >
                                            {{ $revision->reason }}
                                        </p>
                                    @endif
                                </div>

                                <span
                                    class="shrink-0
                                           text-zinc-400"
                                >
                                    →
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div
                    class="border-t border-zinc-200
                           px-5 py-4"
                >
                    {{ $revisions->links() }}
                </div>
            @endif
        </section>


        {{-- =================================================
             REVISION PREVIEW
        ================================================== --}}
        <section
            class="overflow-hidden
                   rounded-2xl
                   border border-zinc-200
                   bg-white
                   shadow-sm"
        >
            @if ($selectedRevision)

                {{-- =============================================
                     REVISION PREVIEW HEADER
                ============================================== --}}
                <div
                    class="flex flex-col gap-4
                           border-b border-zinc-200
                           px-6 py-4
                           sm:flex-row
                           sm:items-start
                           sm:justify-between"
                >
                    <div>
                        <div
                            class="flex flex-wrap
                                   items-center
                                   gap-2"
                        >
                            <h2
                                class="text-lg font-bold
                                       text-zinc-900"
                            >
                                Revision
                                {{ $selectedRevision->revision_number }}
                            </h2>

                            <span
                                @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-bold uppercase tracking-wide',

                                    'bg-zinc-100 text-zinc-700' =>
                                        $selectedRevision->status ===
                                        \App\Enums\NewsStatus::Draft,

                                    'bg-blue-50 text-blue-700' =>
                                        $selectedRevision->status ===
                                        \App\Enums\NewsStatus::Submitted,

                                    'bg-violet-50 text-violet-700' =>
                                        $selectedRevision->status ===
                                        \App\Enums\NewsStatus::Approved,

                                    'bg-emerald-50 text-emerald-700' =>
                                        $selectedRevision->status ===
                                        \App\Enums\NewsStatus::Published,

                                    'bg-amber-50 text-amber-700' =>
                                        $selectedRevision->status ===
                                        \App\Enums\NewsStatus::Archived,
                                ])
                            >
                                {{ $selectedRevision->status->label() }}
                            </span>
                        </div>

                        <p
                            class="mt-1 text-xs
                                   text-zinc-500"
                        >
                            Saved
                            {{ $selectedRevision->created_at?->format(
                                'd M Y, H:i'
                            ) }}

                            @if ($selectedRevision->creator)
                                by
                                {{ $selectedRevision->creator->name }}
                            @endif
                        </p>
                    </div>

                    <div
                        class="flex flex-wrap
                               items-center gap-2"
                    >
                        @if ($canRestore)
                            <x-admin.icon-action label="Restore Revision {{ $selectedRevision->revision_number }}" icon="arrow-uturn-left" tone="success"
                                type="button"
                                wire:click="restoreSelectedRevision"
                                wire:confirm="Restore Revision {{ $selectedRevision->revision_number }}? The current article will first be saved as a backup revision."
                                wire:loading.attr="disabled"
                                wire:target="restoreSelectedRevision"
                            />
                        @endif

                        <x-admin.icon-action label="Close Preview" icon="x-mark" tone="neutral"
                            type="button"
                            wire:click="clearSelection"
                        />
                    </div>
                </div>


                {{-- =============================================
                     RESTORE ERROR
                ============================================== --}}
                @error('revision')
                    <div
                        class="mx-6 mt-5
                               rounded-xl
                               border border-red-200
                               bg-red-50
                               px-4 py-3
                               text-sm font-medium
                               text-red-700"
                    >
                        {{ $message }}
                    </div>
                @enderror


                {{-- =============================================
                     RESTORE INFORMATION
                ============================================== --}}
                @if (! $canRestore)
                    <div
                        class="mx-6 mt-5
                               rounded-xl
                               border border-amber-200
                               bg-amber-50
                               px-4 py-3"
                    >
                        <p
                            class="text-sm font-semibold
                                   text-amber-800"
                        >
                            Revision restore is unavailable.
                        </p>

                        <p
                            class="mt-1 text-xs
                                   leading-5
                                   text-amber-700"
                        >
                            Restore requires update permission
                            and an editable current workflow state.
                        </p>
                    </div>
                @endif


                {{-- =============================================
                     REVISION METADATA
                ============================================== --}}
                <div
                    class="grid gap-4
                           border-b border-zinc-200
                           bg-zinc-50
                           p-5
                           sm:grid-cols-2
                           lg:grid-cols-3"
                >
                    {{-- Status --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Status
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-semibold
                                   text-zinc-800"
                        >
                            {{ $selectedRevision->status->label() }}
                        </p>
                    </div>

                    {{-- Category --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Category
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-semibold
                                   text-zinc-800"
                        >
                            {{ $selectedRevision->category?->name
                                ?? 'Uncategorised' }}
                        </p>
                    </div>

                    {{-- Saved By --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Saved By
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-semibold
                                   text-zinc-800"
                        >
                            {{ $selectedRevision->creator?->name
                                ?? 'Unknown user' }}
                        </p>
                    </div>

                    {{-- Featured --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Featured
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-semibold
                                   text-zinc-800"
                        >
                            {{ $selectedRevision->is_featured
                                ? 'Yes'
                                : 'No' }}
                        </p>
                    </div>

                    {{-- Publication --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Publication
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-semibold
                                   text-zinc-800"
                        >
                            {{ $selectedRevision->published_at
                                ? $selectedRevision->published_at->format(
                                    'd M Y, H:i'
                                )
                                : 'Not set' }}
                        </p>
                    </div>

                    {{-- Revision Reason --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Reason
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-semibold
                                   text-zinc-800"
                        >
                            {{ $selectedRevision->reason
                                ?? 'No reason recorded' }}
                        </p>
                    </div>
                </div>


                {{-- =============================================
                     ARTICLE SNAPSHOT
                ============================================== --}}
                <div class="space-y-6 p-6">

                    {{-- Title --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Title
                        </p>

                        <h3
                            class="mt-2 text-xl
                                   font-bold
                                   leading-8
                                   text-zinc-950"
                        >
                            {{ $selectedRevision->title }}
                        </h3>
                    </div>


                    {{-- Slug --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Slug
                        </p>

                        <code
                            class="mt-2 inline-block
                                   max-w-full
                                   break-all
                                   rounded-lg
                                   bg-zinc-100
                                   px-3 py-2
                                   text-sm
                                   text-zinc-700"
                        >
                            {{ $selectedRevision->slug }}
                        </code>
                    </div>


                    {{-- Summary --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Summary
                        </p>

                        @if ($selectedRevision->summary)
                            <p
                                class="mt-2
                                       whitespace-pre-line
                                       text-sm leading-7
                                       text-zinc-700"
                            >
                                {{ $selectedRevision->summary }}
                            </p>
                        @else
                            <p
                                class="mt-2 text-sm
                                       italic text-zinc-400"
                            >
                                No summary stored in this revision.
                            </p>
                        @endif
                    </div>


                    {{-- Article Content --}}
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            Article Content
                        </p>

                        <div
                            class="awcms-content
                                   mt-3
                                   rounded-xl
                                   border border-zinc-200
                                   bg-white
                                   p-5"
                        >
                            {!! $selectedRevision->content !!}
                        </div>
                    </div>


                    {{-- =========================================
                         FEATURED IMAGE INFORMATION
                    ========================================== --}}
                    @if ($selectedRevision->featuredImage)
                        <div
                            class="rounded-xl
                                   border border-zinc-200
                                   bg-zinc-50
                                   p-5"
                        >
                            <p
                                class="text-xs font-bold
                                       uppercase tracking-wide
                                       text-zinc-500"
                            >
                                Featured Image Snapshot
                            </p>

                            <p
                                class="mt-3 text-sm
                                       font-semibold
                                       text-zinc-900"
                            >
                                {{ $selectedRevision->featuredImage->title }}
                            </p>

                            <p
                                class="mt-1 break-all
                                       text-xs
                                       text-zinc-500"
                            >
                                {{ $selectedRevision->featuredImage->original_name }}
                            </p>

                            @can('media.view')
                                <a
                                    href="{{ route(
                                        'admin.media.edit',
                                        [
                                            'media' =>
                                                $selectedRevision
                                                    ->featuredImage
                                                    ->id,
                                        ]
                                    ) }}"
                                    target="_blank"
                                    class="mt-3 inline-flex
                                           text-sm font-semibold
                                           text-emerald-700
                                           hover:text-emerald-800"
                                >
                                    View Media →
                                </a>
                            @endcan
                        </div>
                    @elseif ($selectedRevision->featured_image_id)
                        <div
                            class="rounded-xl
                                   border border-amber-200
                                   bg-amber-50
                                   p-5"
                        >
                            <p
                                class="text-sm font-semibold
                                       text-amber-800"
                            >
                                The featured image referenced by
                                this revision is no longer available.
                            </p>
                        </div>
                    @endif


                    {{-- =========================================
                         SEO SNAPSHOT
                    ========================================== --}}
                    <div
                        class="rounded-xl
                               border border-zinc-200
                               bg-zinc-50
                               p-5"
                    >
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-zinc-500"
                        >
                            SEO Snapshot
                        </p>

                        @if ($selectedRevision->seo_title)
                            <div class="mt-3">
                                <p
                                    class="text-xs font-semibold
                                           text-zinc-500"
                                >
                                    SEO Title
                                </p>

                                <p
                                    class="mt-1 text-sm
                                           font-semibold
                                           text-zinc-900"
                                >
                                    {{ $selectedRevision->seo_title }}
                                </p>
                            </div>
                        @endif

                        @if ($selectedRevision->seo_description)
                            <div class="mt-4">
                                <p
                                    class="text-xs font-semibold
                                           text-zinc-500"
                                >
                                    SEO Description
                                </p>

                                <p
                                    class="mt-1 text-sm
                                           leading-6
                                           text-zinc-600"
                                >
                                    {{ $selectedRevision->seo_description }}
                                </p>
                            </div>
                        @endif

                        @if (
                            ! $selectedRevision->seo_title
                            && ! $selectedRevision->seo_description
                        )
                            <p
                                class="mt-3 text-sm
                                       italic text-zinc-400"
                            >
                                No SEO metadata stored in this revision.
                            </p>
                        @endif
                    </div>


                    {{-- =========================================
                         REVISION SAFETY NOTE
                    ========================================== --}}
                    @if ($canRestore)
                        <div
                            class="rounded-xl
                                   border border-emerald-200
                                   bg-emerald-50
                                   p-5"
                        >
                            <p
                                class="text-sm font-semibold
                                       text-emerald-800"
                            >
                                Safe Restore
                            </p>

                            <p
                                class="mt-1 text-xs
                                       leading-5
                                       text-emerald-700"
                            >
                                Restoring this revision first saves
                                the current article as a new backup
                                revision. The current workflow status
                                is preserved.
                            </p>
                        </div>
                    @endif
                </div>

            @else

                {{-- =============================================
                     NO REVISION SELECTED
                ============================================== --}}
                <div
                    class="flex min-h-[32rem]
                           items-center
                           justify-center
                           p-8
                           text-center"
                >
                    <div class="max-w-md">
                        <div
                            class="mx-auto flex
                                   h-14 w-14
                                   items-center
                                   justify-center
                                   rounded-full
                                   bg-emerald-50
                                   text-2xl
                                   text-emerald-700"
                        >
                            ↻
                        </div>

                        <h2
                            class="mt-4 text-lg
                                   font-bold
                                   text-zinc-900"
                        >
                            Select a Revision
                        </h2>

                        <p
                            class="mt-2
                                   text-sm leading-6
                                   text-zinc-500"
                        >
                            Choose a saved revision from the list
                            to inspect the article content and
                            metadata stored at that point in time.
                        </p>

                        @if ($revisions->isEmpty())
                            <p
                                class="mt-3 text-xs
                                       text-zinc-400"
                            >
                                Revision history will appear after
                                the article is updated.
                            </p>
                        @endif
                    </div>
                </div>

            @endif
        </section>
    </div>
    @if ($selectedRevision)
        <section class="rounded-2xl border border-zinc-200 bg-white p-5"><h2 class="mb-4 font-bold">Revision compared with current content</h2><x-admin.field-diff :before="$selectedRevision->only(['title','slug','summary','excerpt','content','seo_title','seo_description'])" :after="$news->only(['title','slug','summary','excerpt','content','seo_title','seo_description'])" /></section>
    @endif
</div>
