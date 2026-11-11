<div class="admin-news mx-auto max-w-7xl space-y-6"
    x-data="awcmsNewsImageUpload($wire)"
    x-on:livewire-upload-start="started($event)"
    x-on:livewire-upload-progress="advanced($event)"
    x-on:livewire-upload-finish="finished($event)"
    x-on:livewire-upload-error="if ($event.detail.property === 'galleryUploads') failed()"
    x-on:livewire-upload-cancel="cancelled($event)"
    x-on:submit.capture="guardSubmit($event)">



    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">



        <div>



            <a href="{{ route('admin.news.index') }}" wire:navigate

                class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">



                ← Back to News



            </a>







            <h1 class="mt-2 text-2xl font-bold text-zinc-950">



                {{ $bilingual ? 'Edit English + Sinhala News' : 'Edit News Article' }}



            </h1>







            <p class="mt-1 text-sm text-zinc-600">



                {{ $bilingual
                    ? 'Edit the English and Sinhala versions together. Shared settings and images stay synchronized.'
                    : 'Edit the draft article. Published articles must be unpublished before editing.' }}



            </p>



        </div>







        <div class="flex flex-wrap gap-2">

            <x-admin.icon-action label="{{ $bilingual ? 'Preview English' : 'Preview' }}" icon="eye" tone="info"
                href="{{ route('admin.news.preview', $news) }}" target="_blank" rel="noopener noreferrer"
            />

            @if ($bilingual && $sinhalaNews instanceof \App\Models\News)
                <x-admin.icon-action label="Preview සිංහල" icon="eye" tone="info"
                    href="{{ route('admin.news.preview', $sinhalaNews) }}" target="_blank" rel="noopener noreferrer"
                />
            @endif

        </div>



    </div>







    @if (session('status'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">



            {{ session('status') }}



        </div>

    @endif







    @if ($errors->any())



        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">



            <p class="text-sm font-bold text-red-800">Please correct the highlighted fields.</p>







            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">



                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach



            </ul>



        </div>



    @endif







    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">



        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">



            <div>



                <p class="text-xs font-bold uppercase tracking-wide text-zinc-500">Language versions</p>







                <div class="mt-2 flex flex-wrap gap-2">



                    @foreach ($locales as $localeOption)

                        @php



                            $translation = $news->translationVersions->first(

                                fn(\App\Models\News $version): bool => $version->locale === $localeOption,

                            );



                        @endphp







                        @if ($translation instanceof \App\Models\News)

                            <a href="{{ $translation->status === \App\Enums\NewsStatus::Draft

                                ? route('admin.news.edit', $translation)

                                : route('admin.news.preview', $translation) }}"

                                @if ($translation->status !== \App\Enums\NewsStatus::Draft) target="_blank" rel="noopener noreferrer" @else wire:navigate @endif

                                @class([

                                    'rounded-full px-3 py-1.5 text-xs font-bold',



                                    'bg-emerald-100 text-emerald-800' => $translation->id === $news->id,



                                    'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' =>

                                        $translation->id !== $news->id,

                                ])>



                                {{ $localeOption->nativeLabel() }} ✓



                            </a>

                        @else

                            @can('news.create')

                                <a href="{{ route('admin.news.translations.create', ['newsId' => $news->id, 'locale' => $localeOption->value]) }}"

                                    wire:navigate

                                    class="rounded-full border border-dashed border-zinc-300 px-3 py-1.5 text-xs font-bold text-zinc-500 hover:border-emerald-300 hover:text-emerald-700">



                                    + {{ $localeOption->nativeLabel() }}



                                </a>

                            @endcan

                        @endif

                    @endforeach



                </div>



            </div>







            <div class="rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-800">



                @if ($bilingual)
                    <span class="font-bold">English + සිංහල</span> edited together
                @else
                    Current: <span class="font-bold">{{ $news->locale->nativeLabel() }}</span>
                @endif



            </div>



        </div>



    </section>







    @if (!$editable)



        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">



            <p class="text-sm font-bold text-amber-900">Editing locked</p>



            <p class="mt-1 text-sm leading-6 text-amber-800">



                @if ($bilingual)
                    English and Sinhala versions are edited together. Both versions must be Draft before editing.
                @else
                    This article is currently <span class="font-semibold">{{ $status->label() }}</span>.
                @endif



                @if ($canUnpublishForEditing)
                    Unpublish the published versions to return them to Draft before editing.
                @endif



            </p>

            @if ($bilingual)
                <ul class="mt-3 space-y-1 text-sm text-amber-900">
                    @foreach ($lockedVersions as $version)
                        <li><span class="font-semibold">{{ $version['language'] }}:</span> {{ $version['status'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($canUnpublishForEditing)
                @can('news.publish')
                    <button type="button" wire:click="unpublishForEditing"
                        wire:confirm="Unpublish this article and any linked English/Sinhala versions edited together with it? They will return to Draft."
                        wire:loading.attr="disabled" wire:target="unpublishForEditing"
                        class="mt-4 inline-flex items-center justify-center rounded-xl bg-amber-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-800 disabled:cursor-wait disabled:opacity-60">
                        Unpublish and edit
                    </button>
                @else
                    <p class="mt-3 text-sm text-amber-800">Ask a user with publishing permission to unpublish these versions.</p>
                @endcan
            @endif



        </div>

    @else

        <form wire:submit="save" data-unsaved-form data-draft-key="news-edit" class="space-y-6">
            <x-admin.publishing-checklist :title="$title" :content="$content" :translated-title="$sinhalaTitle ?? ''" :blocks="$blocks ?? []" />
        <x-admin.editor-status />



            @if ($bilingual)

                <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">

                    <div class="border-b border-zinc-200 px-6 py-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="font-bold text-zinc-950">Article settings</h2>
                                <p class="mt-1 text-sm text-zinc-600">
                                    These settings are shared by both English and Sinhala versions.
                                </p>
                            </div>

                            <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">
                                EN + සිංහල
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-5 p-6 lg:grid-cols-2">

                        <x-admin.news.article-type-picker :value="$categoryId" :categories="$categories" class="lg:col-span-2" />

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="news-slug" class="text-sm font-semibold text-zinc-800">
                                    Public URL slug
                                </label>

                                <button type="button" wire:click="regenerateSlug"
                                    class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                                    Generate from English title
                                </button>
                            </div>

                            <input id="news-slug" type="text" wire:model.live.debounce.300ms="slug"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">

                            <div class="mt-3 space-y-1 text-xs text-zinc-500">
                                <p>English: <span class="font-medium text-zinc-700">/news/{{ $slug }}</span></p>
                                <p>සිංහල: <span class="font-medium text-zinc-700">/si/news/{{ $slug }}</span></p>
                            </div>

                            @error('slug')
                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                </section>



                <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm"
                    x-data="{
                        language: '{{ $errors->has('sinhalaTitle') || $errors->has('sinhalaSummary') || $errors->has('sinhalaContent') ? 'si' : 'en' }}'
                    }">

                    <div class="border-b border-zinc-200 px-6 py-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 class="font-bold text-zinc-950">News content</h2>
                                <p class="mt-1 text-sm text-zinc-600">
                                    Edit both language versions here. The original content editor is locked for this article.
                                </p>
                            </div>

                            <div class="flex flex-col items-start gap-2 sm:items-end">
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">
                                    {{ $editorMode === 'html' ? 'HTML + Tailwind' : 'Visual Editor' }} · Locked
                                </span>

                                <div class="inline-flex rounded-xl border border-zinc-200 bg-zinc-100 p-1">
                                    <button type="button" x-on:click="language = 'en'"
                                        x-bind:class="language === 'en' ? 'bg-white text-emerald-800 shadow-sm' : 'text-zinc-600 hover:text-zinc-900'"
                                        class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition">
                                        <span>English</span>
                                        @if ($errors->has('title') || $errors->has('summary') || $errors->has('content'))
                                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                        @endif
                                    </button>

                                    <button type="button" x-on:click="language = 'si'"
                                        x-bind:class="language === 'si' ? 'bg-white text-emerald-800 shadow-sm' : 'text-zinc-600 hover:text-zinc-900'"
                                        class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition">
                                        <span>සිංහල</span>
                                        @if ($errors->has('sinhalaTitle') || $errors->has('sinhalaSummary') || $errors->has('sinhalaContent'))
                                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="language === 'en'" class="space-y-6 p-6">

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="news-title-en" class="text-sm font-semibold text-zinc-800">News title</label>
                                <span class="text-xs font-semibold text-zinc-400">English</span>
                            </div>

                            <input id="news-title-en" type="text" wire:model.live.debounce.300ms="title" maxlength="255"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">

                            @error('title')
                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="news-summary-en" class="mb-2 block text-sm font-semibold text-zinc-800">Summary</label>
                            <textarea id="news-summary-en" wire:model="summary" rows="3" maxlength="2000"
                                class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"></textarea>

                            @error('summary')
                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div wire:key="edit-english-content-editor-{{ $editorMode }}">
                            <x-forms.page-content-editor
                                id="news-content-en"
                                model="content"
                                mode-model="editorMode"
                                :value="$content"
                                :editor-mode="$editorMode"
                                :show-mode-switcher="false"
                            />

                            @error('content')
                                <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <div x-cloak x-show="language === 'si'" class="space-y-6 p-6">

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="news-title-si" class="text-sm font-semibold text-zinc-800">News title</label>
                                <span class="text-xs font-semibold text-zinc-400">සිංහල</span>
                            </div>

                            <input id="news-title-si" type="text" wire:model="sinhalaTitle" maxlength="255"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">

                            @error('sinhalaTitle')
                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="news-summary-si" class="mb-2 block text-sm font-semibold text-zinc-800">Summary</label>
                            <textarea id="news-summary-si" wire:model="sinhalaSummary" rows="3" maxlength="2000"
                                class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"></textarea>

                            @error('sinhalaSummary')
                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div wire:key="edit-sinhala-content-editor-{{ $editorMode }}">
                            <x-forms.page-content-editor
                                id="news-content-si"
                                model="sinhalaContent"
                                mode-model="editorMode"
                                :value="$sinhalaContent"
                                :editor-mode="$editorMode"
                                :show-mode-switcher="false"
                            />

                            @error('sinhalaContent')
                                <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                </section>

            @else

            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">



                <div class="border-b border-zinc-200 px-6 py-5">



                    <h2 class="font-bold text-zinc-950">Article details</h2>



                </div>







                <div class="grid gap-5 p-6 lg:grid-cols-2">



                    <div>



                        <label for="news-title" class="mb-2 block text-sm font-semibold text-zinc-800">News



                            title</label>



                        <input id="news-title" type="text" wire:model.live.debounce.300ms="title"

                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">



                        @error('title')

                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                        @enderror



                    </div>







                    <x-admin.news.article-type-picker :value="$categoryId" :categories="$categories" class="lg:col-span-2" />







                    <div class="lg:col-span-2">



                        <div class="mb-2 flex items-center justify-between gap-3">



                            <label for="news-slug" class="text-sm font-semibold text-zinc-800">URL slug</label>



                            <button type="button" wire:click="regenerateSlug"

                                class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Generate from



                                title</button>



                        </div>







                        <div

                            class="flex overflow-hidden rounded-xl border border-zinc-300 focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-500/10">



                            <span

                                class="flex items-center border-r border-zinc-300 bg-zinc-50 px-3 text-sm text-zinc-500">



                                /{{ $news->locale->value }}/news/



                            </span>



                            <input id="news-slug" type="text" wire:model.live.debounce.300ms="slug"

                                class="min-w-0 flex-1 border-0 bg-white px-4 py-3 text-sm outline-none focus:ring-0">



                        </div>



                        @error('slug')

                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                        @enderror



                    </div>







                    <div class="lg:col-span-2">



                        <label for="news-summary" class="mb-2 block text-sm font-semibold text-zinc-800">Summary</label>



                        <textarea id="news-summary" wire:model="summary" rows="3" maxlength="2000"

                            class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"></textarea>



                        @error('summary')

                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                        @enderror



                    </div>



                </div>



            </section>







            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">

                <div class="border-b border-zinc-200 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                        <div>

                            <h2 class="font-bold text-zinc-950">News content</h2>

                            <p class="mt-1 text-sm leading-6 text-zinc-600">
                                This article must continue to use the editor selected when it was created.
                                The editor mode cannot be changed while editing.
                            </p>

                        </div>

                        <span
                            class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">

                            {{ $editorMode === 'html' ? 'HTML + Tailwind' : 'Visual Editor' }} · Locked

                        </span>

                    </div>

                </div>

                <div class="p-6">

                    <x-forms.page-content-editor
                        id="news-content"
                        model="content"
                        mode-model="editorMode"
                        :value="$content"
                        :editor-mode="$editorMode"
                        :show-mode-switcher="false"
                    />

                    @error('content')
                        <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror

                </div>

            </section>












            @endif



            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">



                <div class="border-b border-zinc-200 px-6 py-5">



                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">



                        <div>



                            <h2 class="font-bold text-zinc-950">Article images</h2>



                            <p class="mt-1 text-sm text-zinc-600">



                                @if ($bilingual)
                                    These images are shared by the English and Sinhala versions and are stored only once.
                                @else
                                    These images appear after the Body. Desktop layout shows four images per row.
                                @endif



                            </p>



                        </div>







                        <span class="w-fit rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">



                            {{ $news->images->count() }} image{{ $news->images->count() === 1 ? '' : 's' }}



                        </span>



                    </div>



                </div>







                <div class="space-y-6 p-6">



                    @if ($news->images->isNotEmpty())



                        <div x-data="{



                            sortable: null,



                            savingOrder: false,







                            init() {



                                this.sortable = new window.Sortable(this.$refs.imageGrid, {



                                    animation: 180,



                                    handle: '[data-drag-handle]',



                                    draggable: '[data-news-image-id]',



                                    ghostClass: 'opacity-40',



                                    chosenClass: 'ring-2',



                                    dragClass: 'shadow-xl',







                                    onEnd: async () => {



                                        const ids = Array.from(



                                            this.$refs.imageGrid.querySelectorAll('[data-news-image-id]')



                                        ).map((element) => Number(element.dataset.newsImageId));







                                        this.savingOrder = true;







                                        try {



                                            await this.$wire.reorderGalleryImages(ids);



                                        } finally {



                                            this.savingOrder = false;



                                        }



                                    },



                                });



                            },







                            destroy() {



                                if (this.sortable) {



                                    this.sortable.destroy();



                                }



                            }



                        }">



                            <div class="mb-3 flex items-center justify-between gap-3">



                                <p class="text-xs font-medium text-zinc-500">



                                    Drag images using the handle to change their display order.



                                </p>







                                <div x-show="savingOrder" x-cloak

                                    class="flex items-center gap-2 text-xs font-semibold text-blue-700">



                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">



                                        <circle class="opacity-25" cx="12" cy="12" r="10"

                                            stroke="currentColor" stroke-width="4"></circle>







                                        <path class="opacity-75" fill="currentColor"

                                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>



                                    </svg>







                                    Saving order...



                                </div>



                            </div>







                            <div x-ref="imageGrid" class="space-y-3">

                                @foreach ($news->images as $newsImage)

                                    @php

                                        $newsImageUrl = \App\Http\Controllers\PublicNewsController::imageUrl(

                                            $newsImage->media,

                                        );



                                        $isFeaturedImage =

                                            (string) $news->featured_image_id === (string) $newsImage->media_asset_id;

                                    @endphp



                                    <article wire:key="news-image-{{ $newsImage->id }}"

                                        data-news-image-id="{{ $newsImage->id }}"

                                        class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:border-zinc-300 hover:shadow-md sm:flex-row">

                                        {{-- Image --}}

                                        <div

                                            class="relative h-44 w-full shrink-0 overflow-hidden bg-zinc-100 sm:h-auto sm:w-44 md:w-52">

                                            <button type="button" data-drag-handle title="Drag to reorder"

                                                aria-label="Drag image to reorder"

                                                class="absolute left-2 top-2 z-10 inline-flex h-9 w-9 cursor-grab items-center justify-center rounded-lg bg-zinc-950/70 text-white shadow-sm backdrop-blur transition hover:bg-zinc-950/90 active:cursor-grabbing">

                                                <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"

                                                    aria-hidden="true">

                                                    <circle cx="9" cy="5" r="1.5" />

                                                    <circle cx="15" cy="5" r="1.5" />

                                                    <circle cx="9" cy="12" r="1.5" />

                                                    <circle cx="15" cy="12" r="1.5" />

                                                    <circle cx="9" cy="19" r="1.5" />

                                                    <circle cx="15" cy="19" r="1.5" />

                                                </svg>

                                            </button>



                                            @if ($newsImageUrl)

                                                <img src="{{ $newsImageUrl }}"

                                                    alt="{{ $newsImage->media?->alt_text ?: $news->title }}"

                                                    class="h-full w-full select-none object-cover" draggable="false">

                                            @else

                                                <div

                                                    class="flex h-full min-h-32 items-center justify-center px-4 text-center text-xs font-semibold text-zinc-400">

                                                    Preview unavailable

                                                </div>

                                            @endif



                                            @if ($isFeaturedImage)

                                                <span

                                                    class="absolute bottom-2 left-2 rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white shadow">

                                                    Featured

                                                </span>

                                            @endif

                                        </div>



                                        {{-- Details --}}

                                        <div class="flex min-w-0 flex-1 items-center px-4 py-4">

                                            <div class="min-w-0">

                                                <p class="truncate text-sm font-bold text-zinc-900">

                                                    {{ $newsImage->media?->title ?: $newsImage->media?->original_name ?: 'News image' }}

                                                </p>



                                                <p class="mt-1 text-xs text-zinc-500">

                                                    Article image

                                                </p>



                                                <p class="mt-2 text-xs text-zinc-400">

                                                    Drag the handle to change display order.

                                                </p>

                                            </div>

                                        </div>



                                        {{-- Actions --}}

                                        <div

                                            class="flex shrink-0 flex-col justify-center gap-2 border-t border-zinc-100 bg-zinc-50/70 p-3 sm:w-48 sm:border-l sm:border-t-0">

                                            <label

                                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition

                        {{ $isFeaturedImage

                            ? 'border-emerald-300 bg-emerald-50 text-emerald-800'

                            : 'border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800' }}">

                                                <input type="radio" name="featured-news-image"

                                                    value="{{ $newsImage->id }}" @checked($isFeaturedImage)

                                                    wire:click="selectFeaturedImage({{ $newsImage->id }})"

                                                    class="h-4 w-4 border-zinc-300 text-emerald-700 focus:ring-emerald-500">



                                                <span>

                                                    {{ $isFeaturedImage ? 'Featured image' : 'Set as featured' }}

                                                </span>

                                            </label>



                                            <x-admin.icon-action label="Remove" icon="trash" tone="danger"
                                                type="button"
                                                wire:click="removeGalleryImage({{ $newsImage->id }})"
                                                wire:confirm="Remove this image from the news article? The Media Library asset will be kept."
                                                wire:loading.attr="disabled"
                                                wire:target="removeGalleryImage({{ $newsImage->id }})"
                                            />

                                        </div>

                                    </article>

                                @endforeach

                            </div>



                        </div>

                    @else

                        <div

                            class="rounded-xl border-2 border-dashed border-zinc-200 bg-zinc-50 px-5 py-8 text-center">



                            <p class="text-sm font-semibold text-zinc-700">



                                No article images uploaded yet.



                            </p>



                        </div>



                    @endif









                    @if ($news->featured_image_id !== null)

                        <div

                            class="flex flex-col gap-3 border-t border-zinc-200 pt-5 sm:flex-row sm:items-center sm:justify-between">



                            <p class="text-sm text-zinc-600">The selected image is used on news cards and social



                                previews.</p>



                            <x-admin.icon-action label="Clear featured image" icon="x-mark" tone="danger"
                                type="button" wire:click="clearFeaturedImage"
                                wire:confirm="Clear the featured image from this article?"
                            />



                        </div>

                    @endif







                    @if (auth()->user()?->can('media.upload') && auth()->user()?->can('news.update'))



                        <div class="border-t border-zinc-200 pt-5">



                            <label for="news-gallery-uploads" class="mb-2 block text-sm font-semibold text-zinc-800">



                                Add multiple images



                            </label>







                            <input id="news-gallery-uploads" type="file" wire:model="galleryUploads" multiple
                                x-on:change.capture="validateSelection($event)"
                                x-bind:disabled="uploading || needsRefresh" data-existing-image-count="{{ $news->images->count() }}"
                                aria-describedby="news-gallery-upload-help news-gallery-upload-error" x-bind:aria-invalid="!!error"

                                accept="image/jpeg,image/png,image/webp"

                                class="block w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-zinc-700 hover:file:bg-zinc-200">







                            <p id="news-gallery-upload-help" class="mt-2 text-xs leading-5 text-zinc-500">
                                Maximum 30 images per article. Maximum 1 MB per image and 30 MB total.
                                JPG, JPEG, PNG and WEBP only. File names must use lowercase letters only.
                                Uploaded files are stored through the existing secure Media Library pipeline.
                            </p>







                            <x-forms.news-image-upload-status :editing="true" />







                            @if ($galleryUploads !== [])



                                <div class="mt-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4">



                                    <p class="text-sm font-semibold text-zinc-900">



                                        Selected images ({{ count($galleryUploads) }})



                                    </p>







                                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">



                                        @foreach ($galleryUploads as $upload)

                                            <figure class="overflow-hidden rounded-lg border border-zinc-200 bg-white">



                                                <div class="aspect-[4/3] overflow-hidden bg-zinc-100">



                                                    <img src="{{ $upload->temporaryUrl() }}"

                                                        alt="Selected news image" class="h-full w-full object-cover">



                                                </div>







                                                <figcaption

                                                    class="truncate px-3 py-2 text-xs font-medium text-zinc-600">



                                                    {{ $upload->getClientOriginalName() }}



                                                </figcaption>



                                            </figure>

                                        @endforeach



                                    </div>



                                </div>



                            @endif







                            @error('galleryUploads')

                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                            @enderror







                            @error('galleryUploads.*')

                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                            @enderror







                            <button type="button" wire:click="uploadGalleryImages" wire:loading.attr="disabled"
                                wire:target="galleryUploads,uploadGalleryImages"
                                x-bind:disabled="uploading || readyCount === 0" @disabled($galleryUploads === [])

                                class="mt-4 inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60">



                                <span wire:loading.remove wire:target="uploadGalleryImages">Upload Images</span>



                                <span wire:loading wire:target="uploadGalleryImages">Uploading...</span>



                            </button>



                        </div>

                    @else

                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">



                            Multiple image upload requires both News Update and Media Upload permissions.



                        </div>



                    @endif



                </div>



            </section>







            <x-admin.news.publication-options :published-at="$publishedAt" :featured="$isFeatured" :gallery="$showInGallery" />







            <div

                class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:items-center sm:justify-end">



                <a href="{{ route('admin.news.index') }}" wire:navigate

                    class="inline-flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">Cancel</a>







                <button type="submit" wire:loading.attr="disabled" wire:target="save,saveAndPublish,galleryUploads,uploadGalleryImages"
                    x-bind:disabled="uploading"

                    class="inline-flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60">



                    <span wire:loading.remove wire:target="save">
                        {{ $bilingual ? 'Save English + Sinhala Draft' : 'Save Draft' }}
                    </span>



                    <span wire:loading wire:target="save">Saving...</span>



                </button>

                @can('news.publish')
                    <button type="button" wire:click="saveAndPublish" wire:confirm="Save these changes and publish this article and its linked language version?"
                        wire:loading.attr="disabled" wire:target="save,saveAndPublish,galleryUploads,uploadGalleryImages"
                        x-bind:disabled="uploading"
                        class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveAndPublish">Save &amp; Publish</span>
                        <span wire:loading wire:target="saveAndPublish">Publishing...</span>
                    </button>
                @endcan



            </div>



        </form>



    @endif



</div>
