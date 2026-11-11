<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-zinc-950">
                    Edit Page
                </h1>

                <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700">
                    Draft
                </span>
            </div>

            <p class="mt-1 text-sm text-zinc-600">
                Edit this page using its Visual Editor or HTML + Tailwind mode.
            </p>
        </div>

        <a
            href="{{ route('admin.pages.index') }}"
            wire:navigate
            class="inline-flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50"
        >
            Back to Pages
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" data-unsaved-form data-draft-key="pages-edit" class="space-y-6">
            <x-admin.publishing-checklist :title="$title" :content="$content" :translated-title="$sinhalaTitle ?? ''" :blocks="$blocks ?? []" />
        <x-admin.editor-status />
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-bold text-zinc-950">
                        Language versions
                    </h2>

                    <p class="mt-1 text-sm text-zinc-600">
                        Each language has its own manually entered title, content, custom CSS, editor mode and publishing status. No automatic translation is used.
                    </p>
                </div>

                @php
                    $currentLocale = \App\Enums\PageLocale::tryFrom($locale);
                @endphp

                <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                    Current: {{ $currentLocale?->nativeLabel() ?? strtoupper($locale) }}
                </span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                @foreach ($locales as $localeOption)
                    @php
                        $version = $translations->first(
                            fn (\App\Models\Page $translation): bool => $translation->locale === $localeOption,
                        );
                        $isCurrent = $localeOption->value === $locale;
                    @endphp

                    <div @class([
                        'rounded-xl border p-4',
                        'border-emerald-300 bg-emerald-50' => $isCurrent,
                        'border-zinc-200 bg-zinc-50' => ! $isCurrent,
                    ])>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-zinc-900">
                                    {{ $localeOption->nativeLabel() }}
                                </p>

                                <p class="mt-0.5 text-xs text-zinc-500">
                                    {{ $localeOption->label() }}
                                </p>
                            </div>

                            @if ($isCurrent)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-bold text-emerald-800">
                                    Current
                                </span>
                            @elseif ($version instanceof \App\Models\Page && $version->trashed())
                                <span class="rounded-full bg-red-50 px-2 py-1 text-[11px] font-bold text-red-700">
                                    In Trash
                                </span>
                            @elseif ($version instanceof \App\Models\Page)
                                <span class="rounded-full bg-blue-50 px-2 py-1 text-[11px] font-bold text-blue-700">
                                    {{ $version->status->label() }}
                                </span>
                            @else
                                <span class="rounded-full bg-zinc-200 px-2 py-1 text-[11px] font-bold text-zinc-600">
                                    Missing
                                </span>
                            @endif
                        </div>

                        @if (! $isCurrent)
                            <div class="mt-3">
                                @if ($version instanceof \App\Models\Page && ! $version->trashed())
                                    @if ($version->status === \App\Enums\PageStatus::Draft)
                                        <x-admin.icon-action label="Edit {{ $localeOption->nativeLabel() }} version" icon="pencil-square" tone="info"
                                            href="{{ route('admin.pages.edit', $version) }}"
                                            wire:navigate
                                        />
                                    @else
                                        <x-admin.icon-action label="Preview {{ $localeOption->nativeLabel() }} version" icon="eye" tone="info"
                                            href="{{ route('admin.pages.preview', $version) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        />
                                    @endif
                                @elseif (! ($version instanceof \App\Models\Page))
                                    @can('pages.create')
                                        <a
                                            href="{{ route('admin.pages.translations.create', ['pageId' => $page->id, 'locale' => $localeOption->value]) }}"
                                            wire:navigate
                                            class="text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                                        >
                                            + Add {{ $localeOption->nativeLabel() }} version
                                        </a>
                                    @endcan
                                @else
                                    <span class="text-xs text-zinc-500">
                                        Restore the existing version from Trash before creating another one.
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="space-y-5">
                <div>
                    <label for="page-title" class="mb-2 block text-sm font-semibold text-zinc-800">
                        Page title
                    </label>

                    <input
                        id="page-title"
                        type="text"
                        wire:model.live.debounce.300ms="title"
                        autocomplete="off"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                    >

                    @error('title')
                        <p class="mt-2 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                        <input
                            type="checkbox"
                            wire:model="showTitle"
                            class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-emerald-700 focus:ring-emerald-500"
                        >

                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-zinc-900">
                                Display page title on public page
                            </span>

                            <span class="mt-1 block text-xs leading-5 text-zinc-500">
                                Uncheck this when the page content already includes its own main heading.
                                The title is still used in the admin area and browser metadata.
                            </span>
                        </span>
                    </label>
                </div>

                <div>
                    <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <label for="page-slug" class="block text-sm font-semibold text-zinc-800">
                            URL slug
                        </label>

                        <button
                            type="button"
                            wire:click="regenerateSlug"
                            class="text-left text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                        >
                            Regenerate from title
                        </button>
                    </div>

                    <div class="flex overflow-hidden rounded-xl border border-zinc-300 focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-500/10">
                        <span class="flex items-center border-r border-zinc-300 bg-zinc-50 px-3 text-sm text-zinc-500">
                            /{{ $locale }}/pages/
                        </span>

                        <input
                            id="page-slug"
                            type="text"
                            wire:model.live.debounce.300ms="slug"
                            autocomplete="off"
                            class="min-w-0 flex-1 border-0 bg-white px-4 py-3 text-sm outline-none focus:ring-0"
                        >
                    </div>

                    <p class="mt-2 text-xs text-amber-700">
                        Changing the slug changes the public page URL.
                    </p>

                    @error('slug')
                        <p class="mt-2 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="page-excerpt" class="mb-2 block text-sm font-semibold text-zinc-800">
                        Short description
                    </label>

                    <textarea
                        id="page-excerpt"
                        wire:model="excerpt"
                        rows="3"
                        maxlength="500"
                        class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                    ></textarea>

                    <div class="mt-2 flex justify-between gap-3">
                        @error('excerpt')
                            <p class="text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @else
                            <span></span>
                        @enderror

                        <p class="text-xs text-zinc-500">
                            {{ mb_strlen($excerpt) }}/500
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-6 py-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-zinc-950">
                            Page content
                        </h2>

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
                <x-forms.page-content-editor
                    id="page-content"
                    model="content"
                    mode-model="editorMode"
                    :value="$content"
                    :editor-mode="$editorMode"
                />
            </div>
        </section>

        <x-forms.page-custom-css />

        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="text-sm font-semibold text-amber-950">
                Draft editing safeguard
            </h2>

            <p class="mt-1 text-sm leading-6 text-amber-800">
                Only Draft pages may be edited. Submitted, Approved, Published and Archived
                pages must first be returned to Draft through the content workflow.
            </p>
        </section>

        <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{ route('admin.pages.index') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-50"
            >
                Cancel
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">
                    Save Changes
                </span>

                <span wire:loading wire:target="save">
                    Saving...
                </span>
            </button>
        </div>
    </form>
</div>
