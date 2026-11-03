<div class="space-y-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
            Theme Management
        </p>

        <h1 class="mt-1 text-2xl font-black text-zinc-950">
            Header &amp; Footer
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Manage header and footer layouts for your enabled site languages
            with visual header and footer builders, HTML / CSS and live preview.
        </p>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!$themeAvailable)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
            <h2 class="font-bold text-amber-950">
                No valid active theme is configured
            </h2>

            <p class="mt-2 text-sm text-amber-800">
                Set
                <code class="rounded bg-amber-100 px-1.5 py-0.5 font-mono">
                    AWCMS_ACTIVE_THEME
                </code>
                to an installed theme slug, then clear the configuration cache.
            </p>
        </section>
    @else
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div
                class="flex flex-col gap-4 border-b border-zinc-200 bg-zinc-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                        Active theme
                    </p>

                    <p class="mt-1 font-bold text-zinc-950">
                        {{ $themeName }}

                        <span class="font-mono text-xs font-medium text-zinc-500">
                            ({{ $themeSlug }})
                        </span>
                    </p>
                </div>

                @if ($layout)
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-blue-100 px-3 py-1 font-bold text-blue-800">
                            {{ $layout->locale->nativeLabel() }}
                        </span>

                        @if (
                            $layout->status->value === 'published' &&
                                ($layout->draft_html !== $layout->published_html || $layout->draft_css !== $layout->published_css))
                            <span class="rounded-full bg-amber-100 px-3 py-1 font-bold text-amber-800">
                                Unpublished draft changes
                            </span>
                        @else
                            <span
                                class="rounded-full px-3 py-1 font-bold
                                    {{ $layout->status->value === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $layout->status->label() }}
                            </span>
                        @endif

                        <span class="rounded-full bg-zinc-200 px-3 py-1 font-semibold text-zinc-700">
                            Revision {{ $layout->revision_number }}
                        </span>

                        @if ($layout->published_at)
                            <span class="text-zinc-500">
                                Published {{ $layout->published_at->format('Y-m-d H:i') }}
                            </span>
                        @endif
                    </div>
                @else
                    <span class="w-fit rounded-full bg-zinc-200 px-3 py-1 text-xs font-bold text-zinc-700">
                        Not configured
                    </span>
                @endif
            </div>

            {{-- Language tabs --}}
            <div class="border-b border-zinc-200 bg-white px-6 pt-5">
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-zinc-500">
                    Layout language
                </p>

                <div class="flex flex-wrap gap-2" role="tablist" aria-label="Theme layout language">
                    @foreach ($locales as $localeOption)
                        <button type="button" wire:key="theme-layout-locale-{{ $localeOption->value }}"
                            wire:click="selectLocale('{{ $localeOption->value }}')"
                            wire:confirm="Switch to {{ $localeOption->label() }}? Any unsaved editor changes will be discarded."
                            class="rounded-t-xl border border-b-0 px-5 py-3 text-sm font-bold
                                {{ $locale === $localeOption->value
                                    ? 'border-zinc-300 bg-zinc-50 text-emerald-700'
                                    : 'border-transparent bg-white text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950' }}"
                            role="tab" aria-selected="{{ $locale === $localeOption->value ? 'true' : 'false' }}">
                            <span>{{ $localeOption->nativeLabel() }}</span>

                            <span class="ml-1 text-xs font-semibold opacity-60">
                                {{ strtoupper($localeOption->value) }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Region tabs --}}
            <div class="border-b border-zinc-200 bg-zinc-50 px-6 pt-4">
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-zinc-500">
                    Layout region
                </p>

                <div class="flex flex-wrap gap-2" role="tablist" aria-label="Theme layout region">
                    <button type="button" wire:click="selectRegion('header')"
                        wire:confirm="Switch to Header? Any unsaved editor changes will be discarded."
                        class="rounded-t-xl border border-b-0 px-5 py-3 text-sm font-bold
                            {{ $region === 'header'
                                ? 'border-zinc-300 bg-white text-emerald-700'
                                : 'border-transparent bg-zinc-100 text-zinc-600 hover:text-zinc-950' }}"
                        role="tab" aria-selected="{{ $region === 'header' ? 'true' : 'false' }}">
                        Header / Top Bar / Navigation
                    </button>

                    <button type="button" wire:click="selectRegion('footer')"
                        wire:confirm="Switch to Footer? Any unsaved editor changes will be discarded."
                        class="rounded-t-xl border border-b-0 px-5 py-3 text-sm font-bold
                            {{ $region === 'footer'
                                ? 'border-zinc-300 bg-white text-emerald-700'
                                : 'border-transparent bg-zinc-100 text-zinc-600 hover:text-zinc-950' }}"
                        role="tab" aria-selected="{{ $region === 'footer' ? 'true' : 'false' }}">
                        Footer
                    </button>
                </div>
            </div>

            <form wire:submit="saveDraft" @submit.capture="if (!$dispatch('navigation-editor-sync') || !$dispatch('footer-editor-sync')) { $event.preventDefault(); $event.stopImmediatePropagation(); }" class="space-y-6 p-6">
                @if ($region === 'header')
                    @include('admin.theme-layouts.navigation-editor')
                @else
                    @include('admin.theme-layouts.footer-editor')
                @endif

                <div
                    class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" @click.capture="if (!$dispatch('navigation-editor-sync') || !$dispatch('footer-editor-sync')) { $event.preventDefault(); $event.stopImmediatePropagation(); }" wire:click="saveAndPublish"
                        wire:confirm="Save and publish this {{ strtoupper($locale) }} {{ $region }} layout?"
                        wire:loading.attr="disabled"
                        class="rounded-xl border border-emerald-700 px-5 py-3 text-sm font-bold text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">
                        Save &amp; Publish
                    </button>

                    <button type="submit" wire:loading.attr="disabled"
                        class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">
                        Save {{ strtoupper($locale) }} Draft
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4">
                <h2 class="font-bold text-zinc-900">
                    Approved CMS placeholders
                </h2>

                <p class="mt-1 text-xs text-zinc-500">
                    Place these tokens inside the HTML editor. They will be replaced
                    with escaped and secure CMS content on the public website.
                </p>
            </div>

            <div class="grid gap-3 p-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($placeholders as $placeholder)
                    <code
                        class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs font-semibold text-zinc-800">
                        [[{{ $placeholder }}]]
                    </code>
                @endforeach
            </div>
        </section>
    @endif
</div>
