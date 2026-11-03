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

                Create News Article

            </h1>



            <p class="mt-1 text-sm text-zinc-600">

                @if ($translationSourceNewsId === null)
                    Create the English article and optionally add a Sinhala version. Shared images are uploaded only once.
                @else
                    Create this language version manually. No automatic translation is performed.
                @endif

            </p>

        </div>



        @if ($canManageCategories ?? auth()->user()?->can('news.categories.manage'))

            <a href="{{ route('admin.news.categories.index') }}" wire:navigate

                class="inline-flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">

                Manage Categories

            </a>

        @endif

    </div>



    @if ($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

            <p class="text-sm font-bold text-red-800">

                Please correct the highlighted fields.

            </p>



            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif



    <form wire:submit="save" wire:confirm="Save this news article as a draft?" class="space-y-6">

        @if ($translationSourceNewsId === null)

        {{-- =========================================================

        Shared article settings

    ========================================================== --}}

        <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">

            <div class="border-b border-zinc-200 px-6 py-5">

                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                    <div>

                        <h2 class="font-bold text-zinc-950">

                            Article settings

                        </h2>



                        <p class="mt-1 text-sm text-zinc-600">

                            These settings are shared by every language version created for this article.

                        </p>

                    </div>



                    <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">

                        English + optional සිංහල

                    </span>

                </div>

            </div>



            <div class="grid gap-5 p-6 lg:grid-cols-2">

                <x-admin.news.article-type-picker :value="$categoryId" :categories="$categories" class="lg:col-span-2" />



                {{-- Shared slug --}}

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

                        placeholder="annual-training-programme"

                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">



                    <div class="mt-3 space-y-1 text-xs text-zinc-500">

                        <p>

                            English:

                            <span class="font-medium text-zinc-700">

                                /news/{{ $slug ?: 'article-slug' }}

                            </span>

                        </p>



                        <p>

                            Sinhala:

                            <span class="font-medium text-zinc-700">

                                /si/news/{{ $slug ?: 'article-slug' }}

                            </span>

                        </p>

                    </div>



                    @error('slug')

                        <p class="mt-2 text-sm font-medium text-red-600">

                            {{ $message }}

                        </p>

                    @enderror

                </div>

            </div>

        </section>





        {{-- =========================================================

        Editor selection

    ========================================================== --}}

        <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">

            <div class="border-b border-zinc-200 px-6 py-5">

                <h2 class="font-bold text-zinc-950">

                    Content editor

                </h2>



                <p class="mt-1 text-sm text-zinc-600">

                    Choose one editor for this article. If a Sinhala version is added, it uses the same editor.

                </p>

            </div>



            <div class="grid gap-4 p-6 md:grid-cols-2">



                <label @class([

                    'relative flex cursor-pointer gap-4 rounded-2xl border p-5 transition',

                    'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/10' =>

                        $editorMode === 'visual',

                    'border-zinc-200 bg-white hover:border-zinc-300' =>

                        $editorMode !== 'visual',

                ])>

                    <input type="radio" wire:model.live="editorMode" value="visual"

                        class="mt-1 h-4 w-4 border-zinc-300 text-emerald-700 focus:ring-emerald-500">



                    <span>

                        <span class="block text-sm font-bold text-zinc-950">

                            Visual Editor

                        </span>



                        <span class="mt-1 block text-sm leading-6 text-zinc-600">

                            Recommended for normal users. Write and format content like a document editor.

                        </span>



                        <span

                            class="mt-3 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">

                            Recommended

                        </span>

                    </span>

                </label>



                <label @class([

                    'relative flex cursor-pointer gap-4 rounded-2xl border p-5 transition',

                    'border-blue-500 bg-blue-50 ring-2 ring-blue-500/10' =>

                        $editorMode === 'html',

                    'border-zinc-200 bg-white hover:border-zinc-300' => $editorMode !== 'html',

                ])>

                    <input type="radio" wire:model.live="editorMode" value="html"

                        class="mt-1 h-4 w-4 border-zinc-300 text-blue-700 focus:ring-blue-500">



                    <span>

                        <span class="block text-sm font-bold text-zinc-950">

                            HTML + Tailwind

                        </span>



                        <span class="mt-1 block text-sm leading-6 text-zinc-600">

                            Advanced mode for custom HTML layouts and Tailwind CSS.

                        </span>



                        <span

                            class="mt-3 inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold text-zinc-700">

                            Advanced

                        </span>

                    </span>

                </label>



            </div>



            @error('editorMode')

                <p class="px-6 pb-6 text-sm font-medium text-red-600">

                    {{ $message }}

                </p>

            @enderror

        </section>





        {{-- =========================================================

        Language content

    ========================================================== --}}

        <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm" x-data="{

            language: '{{ $errors->has('sinhalaTitle') || $errors->has('sinhalaSummary') || $errors->has('sinhalaContent')

                ? 'si'

                : 'en' }}'

        }">



            <div class="border-b border-zinc-200 px-6 py-5">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>

                        <h2 class="font-bold text-zinc-950">

                            News content

                        </h2>



                        <p class="mt-1 text-sm text-zinc-600">

                            English is required. Sinhala is optional. If you start the Sinhala version, complete both its title and content.

                        </p>

                    </div>



                    <div class="inline-flex rounded-xl border border-zinc-200 bg-zinc-100 p-1">



                        <button type="button" x-on:click="language = 'en'"

                            x-bind:class="language === 'en'

                                ?

                                'bg-white text-emerald-800 shadow-sm' :

                                'text-zinc-600 hover:text-zinc-900'"

                            class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition">

                            <span>English</span>



                            @if ($errors->has('title') || $errors->has('summary') || $errors->has('content'))

                                <span class="h-2 w-2 rounded-full bg-red-500"></span>

                            @endif

                        </button>



                        <button type="button" x-on:click="language = 'si'"

                            x-bind:class="language === 'si'

                                ?

                                'bg-white text-emerald-800 shadow-sm' :

                                'text-zinc-600 hover:text-zinc-900'"

                            class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition">

                            <span>සිංහල <span class="text-[10px] font-medium text-zinc-400">Optional</span></span>



                            @if ($errors->has('sinhalaTitle') || $errors->has('sinhalaSummary') || $errors->has('sinhalaContent'))

                                <span class="h-2 w-2 rounded-full bg-red-500"></span>

                            @endif

                        </button>



                    </div>

                </div>

            </div>





            {{-- English --}}

            <div x-show="language === 'en'" class="space-y-6 p-6">

                <div>

                    <div class="mb-2 flex items-center justify-between">

                        <label for="news-title-en" class="text-sm font-semibold text-zinc-800">

                            News title

                        </label>



                        <span class="text-xs font-semibold text-zinc-400">

                            English

                        </span>

                    </div>



                    <input id="news-title-en" type="text" wire:model.live.debounce.300ms="title" maxlength="255"

                        placeholder="Example: Annual Training Programme Begins"

                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">



                    @error('title')

                        <p class="mt-2 text-sm font-medium text-red-600">

                            {{ $message }}

                        </p>

                    @enderror

                </div>





                <div>

                    <label for="news-summary-en" class="mb-2 block text-sm font-semibold text-zinc-800">

                        Summary

                    </label>



                    <textarea id="news-summary-en" wire:model="summary" rows="3" maxlength="2000"

                        placeholder="Short English summary shown in news listings."

                        class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"></textarea>



                    @error('summary')

                        <p class="mt-2 text-sm font-medium text-red-600">

                            {{ $message }}

                        </p>

                    @enderror

                </div>





                <div>

                    <div class="mb-3 flex items-center justify-between gap-3">

                        <div>

                            <p class="text-sm font-semibold text-zinc-800">

                                News content

                            </p>



                            <p class="mt-1 text-xs text-zinc-500">

                                English article body.

                            </p>

                        </div>



                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">

                            {{ $editorMode === 'visual' ? 'Visual Editor' : 'HTML + Tailwind' }}

                        </span>

                    </div>



                    <div wire:key="english-content-editor-{{ $editorMode }}">

                        <x-forms.page-content-editor id="news-content-en" model="content" mode-model="editorMode"

                            :value="$content" :editor-mode="$editorMode" :show-mode-switcher="false" />

                    </div>

                </div>

            </div>





            {{-- Sinhala --}}

            <div x-cloak x-show="language === 'si'" class="space-y-6 p-6">

                <div>

                    <div class="mb-2 flex items-center justify-between">

                        <label for="news-title-si" class="text-sm font-semibold text-zinc-800">

                            News title

                        </label>



                        <span class="text-xs font-semibold text-zinc-400">

                            සිංහල

                        </span>

                    </div>



                    <input id="news-title-si" type="text" wire:model="sinhalaTitle" maxlength="255"

                        placeholder="සිංහල ප්‍රවෘත්ති මාතෘකාව"

                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">



                    @error('sinhalaTitle')

                        <p class="mt-2 text-sm font-medium text-red-600">

                            {{ $message }}

                        </p>

                    @enderror

                </div>





                <div>

                    <label for="news-summary-si" class="mb-2 block text-sm font-semibold text-zinc-800">

                        Summary

                    </label>



                    <textarea id="news-summary-si" wire:model="sinhalaSummary" rows="3" maxlength="2000"

                        placeholder="ප්‍රවෘත්තියේ කෙටි සාරාංශය"

                        class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"></textarea>



                    @error('sinhalaSummary')

                        <p class="mt-2 text-sm font-medium text-red-600">

                            {{ $message }}

                        </p>

                    @enderror

                </div>





                <div>

                    <div class="mb-3 flex items-center justify-between gap-3">

                        <div>

                            <p class="text-sm font-semibold text-zinc-800">

                                News content

                            </p>



                            <p class="mt-1 text-xs text-zinc-500">

                                සිංහල ප්‍රවෘත්ති අන්තර්ගතය.

                            </p>

                        </div>



                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">

                            {{ $editorMode === 'visual' ? 'Visual Editor' : 'HTML + Tailwind' }}

                        </span>

                    </div>



                    <div wire:key="sinhala-content-editor-{{ $editorMode }}">

                        <x-forms.page-content-editor id="news-content-si" model="sinhalaContent"

                            mode-model="editorMode" :value="$sinhalaContent" :editor-mode="$editorMode" :show-mode-switcher="false" />

                    </div>

                </div>

            </div>



        </section>

    

        @else

        <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">

            <div class="border-b border-zinc-200 px-6 py-5">

                <h2 class="font-bold text-zinc-950">Article details</h2>

                <p class="mt-1 text-sm text-zinc-600">Language, title, category and public URL.</p>

            </div>



            <div class="grid gap-5 p-6 lg:grid-cols-2">

                <div class="lg:col-span-2">

                    <p class="text-sm font-semibold text-zinc-800">Language</p>



                    @if ($translationSourceNewsId !== null)

                        @php

                            $selectedLocale = collect($locales)->first(

                                fn(\App\Enums\NewsLocale $option): bool => $option->value === $locale,

                            );

                        @endphp



                        <div class="mt-2 rounded-xl border border-blue-200 bg-blue-50 p-4">

                            <div class="flex flex-wrap items-center gap-2">

                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-800">

                                    {{ $selectedLocale?->nativeLabel() ?? strtoupper($locale) }}

                                </span>



                                <span class="text-sm font-semibold text-blue-950">

                                    Manual translation of “{{ $translationSourceTitle }}”

                                </span>

                            </div>



                            <p class="mt-2 text-xs leading-5 text-blue-800">

                                Source title, summary and body are not copied. Enter the approved translation manually.

                            </p>

                        </div>

                    @else

                        <select wire:model.live="locale"

                            class="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">

                            @foreach ($locales as $localeOption)

                                <option value="{{ $localeOption->value }}">

                                    {{ $localeOption->label() }} — {{ $localeOption->nativeLabel() }}

                                </option>

                            @endforeach

                        </select>

                    @endif



                    @error('locale')

                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                    @enderror

                </div>



                <div>

                    <label for="news-title" class="mb-2 block text-sm font-semibold text-zinc-800">

                        News title

                    </label>



                    <input id="news-title" type="text" wire:model.live.debounce.300ms="title"

                        placeholder="Example: Annual Training Programme Begins"

                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">



                    @error('title')

                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                    @enderror

                </div>



                <div>

                    <label for="news-category" class="mb-2 block text-sm font-semibold text-zinc-800">

                        Category

                    </label>



                    <select id="news-category" wire:model.live="categoryId"

                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">

                        <option value="">Select category</option>



                        @foreach ($categories as $category)

                            <option value="{{ $category->id }}">{{ $category->name }}</option>

                        @endforeach

                    </select>



                    @error('categoryId')

                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                    @enderror

                </div>



                <div class="lg:col-span-2">

                    <div class="mb-2 flex items-center justify-between gap-3">

                        <label for="news-slug" class="text-sm font-semibold text-zinc-800">URL slug</label>



                        <button type="button" wire:click="regenerateSlug"

                            class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">

                            Generate from title

                        </button>

                    </div>



                    <div

                        class="flex overflow-hidden rounded-xl border border-zinc-300 focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-500/10">

                        <span class="flex items-center border-r border-zinc-300 bg-zinc-50 px-3 text-sm text-zinc-500">

                            /{{ $locale }}/news/

                        </span>



                        <input id="news-slug" type="text" wire:model.live.debounce.300ms="slug"

                            placeholder="annual-training-programme"

                            class="min-w-0 flex-1 border-0 bg-white px-4 py-3 text-sm outline-none focus:ring-0">

                    </div>



                    <p class="mt-2 text-xs text-zinc-500">

                        The same slug may be used in EN, SI and TA because uniqueness is enforced per language.

                    </p>



                    @error('slug')

                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                    @enderror

                </div>



                <div class="lg:col-span-2">

                    <label for="news-summary" class="mb-2 block text-sm font-semibold text-zinc-800">

                        Summary

                    </label>



                    <textarea id="news-summary" wire:model="summary" rows="3" maxlength="2000"

                        placeholder="Short summary shown in news listings."

                        class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"></textarea>



                    @error('summary')

                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>

                    @enderror

                </div>

            </div>

        </section>



        <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">

            <div class="border-b border-zinc-200 px-6 py-5">

                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                    <div>

                        <h2 class="font-bold text-zinc-950">News content</h2>

                        <p class="mt-1 text-sm text-zinc-600">

                            Use the Word-like Visual Editor or switch to HTML + Tailwind for advanced layouts.

                        </p>

                    </div>



                    <span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">

                        Visual + Code

                    </span>

                </div>

            </div>



            <div class="p-6">

                <x-forms.page-content-editor id="news-content" model="content" mode-model="editorMode" :value="$content"

                    :editor-mode="$editorMode" />



                @error('content')

                    <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>

                @enderror

            </div>

        </section>





        

        @endif

<section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">

            <div class="border-b border-zinc-200 px-6 py-5">

                <h2 class="font-bold text-zinc-950">Article images</h2>

                <p class="mt-1 text-sm text-zinc-600">

                    Upload multiple images for this article. They are shown after the Body in a four-column grid on

                    desktop.

                </p>

            </div>



            <div class="p-6">

                @if (auth()->user()?->can('media.upload') && auth()->user()?->can('news.update'))

                    <label for="news-gallery-uploads" class="mb-2 block text-sm font-semibold text-zinc-800">

                        Upload images

                    </label>



                    <input id="news-gallery-uploads" type="file" wire:model="galleryUploads" multiple
                        x-on:change.capture="validateSelection($event)"
                        x-bind:disabled="uploading || needsRefresh" data-existing-image-count="0"
                        aria-describedby="news-gallery-upload-help news-gallery-upload-error" x-bind:aria-invalid="!!error"

                        accept="image/jpeg,image/png,image/webp"

                        class="block w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-zinc-700 hover:file:bg-zinc-200">



                    <p id="news-gallery-upload-help" class="mt-2 text-xs leading-5 text-zinc-500">

                        Maximum 30 images per article.

                        Maximum 1 MB per image and 30 MB total.

                        JPG, JPEG, PNG and WEBP only.

                        File names must use lowercase letters only.

                        After selecting images, drag them to set the display order.

                    </p>



                    <x-forms.news-image-upload-status />



                    @if ($galleryUploads !== [])

                        <div class="mt-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4" x-data="{

                            sortable: null,

                            savingOrder: false,



                            init() {

                                if (!window.Sortable) {

                                    console.error('SortableJS is not available.');

                                    return;

                                }



                                this.sortable = new window.Sortable(

                                    this.$refs.pendingImageList, {

                                        animation: 180,

                                        handle: '[data-drag-handle]',

                                        draggable: '[data-pending-image-index]',



                                        ghostClass: 'opacity-40',



                                        onEnd: async () => {

                                            const indexes = Array.from(

                                                this.$refs.pendingImageList.querySelectorAll(

                                                    '[data-pending-image-index]'

                                                )

                                            ).map((item) => {

                                                return Number(

                                                    item.dataset.pendingImageIndex

                                                );

                                            });



                                            this.savingOrder = true;



                                            try {

                                                await this.$wire.reorderPendingGalleryImages(

                                                    indexes

                                                );

                                            } finally {

                                                this.savingOrder = false;

                                            }

                                        },

                                    }

                                );

                            },

                            destroy() {
                                this.sortable?.destroy();
                            }

                        }">

                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                <div>

                                    <p class="text-sm font-semibold text-zinc-900">

                                        Selected images ({{ count($galleryUploads) }})

                                    </p>



                                    <p class="mt-1 text-xs text-zinc-500">

                                        Drag images using the handle to change their display order.

                                    </p>

                                </div>



                                <div x-show="savingOrder" x-cloak class="text-xs font-semibold text-blue-700">

                                    Saving order...

                                </div>

                            </div>



                            <div x-ref="pendingImageList" class="mt-4 space-y-3">

                                @foreach ($galleryUploads as $index => $upload)

                                    <article wire:key="pending-news-image-{{ $upload->getFilename() }}"

                                        data-pending-image-index="{{ $index }}"

                                        class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:border-zinc-300 hover:shadow-md sm:flex-row">

                                        {{-- Image --}}

                                        <div

                                            class="relative h-40 w-full shrink-0 overflow-hidden bg-zinc-100 sm:h-32 sm:w-44">

                                            <button type="button" data-drag-handle

                                                class="absolute left-2 top-2 z-10 inline-flex h-9 w-9 cursor-grab items-center justify-center rounded-lg border border-white/40 bg-zinc-950/75 text-white shadow-sm backdrop-blur hover:bg-zinc-950 active:cursor-grabbing"

                                                aria-label="Drag to reorder image" title="Drag to reorder">

                                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"

                                                    aria-hidden="true">

                                                    <circle cx="8" cy="6" r="1.5" />

                                                    <circle cx="16" cy="6" r="1.5" />

                                                    <circle cx="8" cy="12" r="1.5" />

                                                    <circle cx="16" cy="12" r="1.5" />

                                                    <circle cx="8" cy="18" r="1.5" />

                                                    <circle cx="16" cy="18" r="1.5" />

                                                </svg>

                                            </button>



                                            <img src="{{ $upload->temporaryUrl() }}"

                                                alt="Selected news image {{ $loop->iteration }}"

                                                class="h-full w-full select-none object-cover" draggable="false">

                                        </div>



                                        {{-- Details --}}

                                        <div class="flex min-w-0 flex-1 items-center px-4 py-4">

                                            <div class="min-w-0">

                                                <div

                                                    class="mb-1 inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-bold text-zinc-600">

                                                    Image {{ $loop->iteration }}

                                                </div>



                                                <p class="truncate text-sm font-semibold text-zinc-900"

                                                    title="{{ $upload->getClientOriginalName() }}">

                                                    {{ $upload->getClientOriginalName() }}

                                                </p>



                                                <p class="mt-1 text-xs text-zinc-500">

                                                    {{ number_format($upload->getSize() / 1024, 0) }}

                                                    KB

                                                </p>



                                                <p class="mt-2 text-xs text-zinc-400">

                                                    Drag this image to change its position.

                                                </p>

                                            </div>

                                        </div>



                                        {{-- Position --}}

                                        <div

                                            class="flex shrink-0 items-center justify-center border-t border-zinc-200 bg-zinc-50 px-5 py-4 sm:w-32 sm:border-l sm:border-t-0">

                                            <div class="text-center">

                                                <span

                                                    class="block text-[11px] font-semibold uppercase tracking-wide text-zinc-400">

                                                    Position

                                                </span>



                                                <span class="mt-1 block text-lg font-bold text-zinc-900">

                                                    {{ $loop->iteration }}

                                                </span>

                                            </div>

                                        </div>

                                    </article>

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

                class="inline-flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">

                Cancel

            </a>



            <button type="submit" wire:loading.attr="disabled"
                x-bind:disabled="uploading"

                class="inline-flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60">

                <span wire:loading.remove wire:target="save">Save Draft</span>

                <span wire:loading wire:target="save">Saving...</span>

            </button>



            @if ($translationSourceNewsId === null && auth()->user()?->can('news.publish'))

                <button type="button" wire:click="saveAndPublish" wire:confirm="Save and publish this news article now?" wire:loading.attr="disabled"
                    x-bind:disabled="uploading"

                    class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60">

                    <span wire:loading.remove wire:target="saveAndPublish">Save &amp; Publish</span>

                    <span wire:loading wire:target="saveAndPublish">Publishing...</span>

                </button>

            @endif

        </div>

    </form>

</div>
