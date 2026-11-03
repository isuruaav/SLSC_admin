<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-950">
                Create Page
            </h1>

            <p class="mt-1 text-sm text-zinc-600">
                Create a page with either the Visual Editor or the HTML + Tailwind editor.
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

    <form wire:submit="save" wire:confirm="Create this page as a draft?" class="space-y-6">
        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="space-y-5">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-zinc-900">
                                Page language
                            </p>

                            <p class="mt-1 text-xs leading-5 text-zinc-500">
                                Content for each enabled language is entered manually. AWCMS does not auto-translate page wording.
                            </p>
                        </div>

                        @if ($translationSourcePageId !== null)
                            @php
                                $selectedLocale = collect($locales)->first(
                                    fn (\App\Enums\PageLocale $localeOption): bool => $localeOption->value === $locale,
                                );
                            @endphp

                            <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                {{ $selectedLocale?->nativeLabel() ?? strtoupper($locale) }}
                            </span>
                        @endif
                    </div>

                    @if ($translationSourcePageId !== null)
                        <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                            <p class="text-sm font-semibold text-blue-950">
                                Creating a manual translation of “{{ $translationSourceTitle }}”
                            </p>

                            <p class="mt-1 text-xs leading-5 text-blue-800">
                                The source title and content are intentionally not copied. Enter the approved translation yourself.
                            </p>
                        </div>
                    @else
                        <label for="page-locale" class="mt-4 mb-2 block text-sm font-semibold text-zinc-800">
                            Language
                        </label>

                        <select
                            id="page-locale"
                            wire:model.live="locale"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                        >
                            @foreach ($locales as $localeOption)
                                <option value="{{ $localeOption->value }}">
                                    {{ $localeOption->label() }} — {{ $localeOption->nativeLabel() }}
                                </option>
                            @endforeach
                        </select>

                        @error('locale')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label for="page-title" class="mb-2 block text-sm font-semibold text-zinc-800">
                        Page title
                    </label>

                    <input
                        id="page-title"
                        type="text"
                        wire:model.live.debounce.300ms="title"
                        autocomplete="off"
                        placeholder="Example: About the Regiment"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
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
                            Generate from title
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
                            placeholder="about-the-regiment"
                            class="min-w-0 flex-1 border-0 bg-white px-4 py-3 text-sm outline-none focus:ring-0"
                        >
                    </div>

                    <p class="mt-2 text-xs text-zinc-500">
                        Duplicate slugs are automatically changed to
                        <span class="font-medium">slug-2, slug-3</span>.
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
                        placeholder="Optional short summary for page listings."
                        class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
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

        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
            <h2 class="text-sm font-semibold text-blue-950">
                Draft workflow
            </h2>

            <p class="mt-1 text-sm leading-6 text-blue-800">
                The new page is saved as Draft until it is submitted, approved and published.
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
                    Save Draft
                </span>

                <span wire:loading wire:target="save">
                    Saving...
                </span>
            </button>
        </div>
    </form>
</div>
