<div class="admin-news space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900">News Articles</h1>
            <p class="mt-1 text-sm text-zinc-500">
                Manage news articles and translations for your enabled site languages.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if ($canManageCategories)
                <a href="{{ route('admin.news.categories.index') }}" wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm transition-all hover:border-zinc-300 hover:bg-zinc-50">
                    <svg class="h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Categories
                </a>
            @endif

            @can('news.create')
                <a href="{{ route('admin.news.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-emerald-600/20 transition-all hover:bg-emerald-700 active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Create News
                </a>
            @endcan
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-4 py-3.5 text-sm font-medium text-emerald-900 shadow-sm">
            <svg class="h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @error('workflow')
        <div class="flex items-center gap-3 rounded-xl border border-red-200/80 bg-red-50/80 px-4 py-3.5 text-sm font-medium text-red-900 shadow-sm">
            <svg class="h-5 w-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <!-- Filters Section -->
    <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm">
        <div class="news-filters">
            <div class="news-filters__search">
                <label for="news-search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Search</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input id="news-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Search title, slug or summary..."
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 pl-10 pr-4 py-2 text-sm text-zinc-800 outline-none transition-all placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                </div>
            </div>

            <div>
                <label for="news-locale" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Language</label>
                <select id="news-locale" wire:model.live="locale"
                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2 text-sm text-zinc-800 outline-none transition-all focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    <option value="all">All languages</option>
                    @foreach ($locales as $localeOption)
                        <option value="{{ $localeOption->value }}">{{ $localeOption->label() }} — {{ $localeOption->nativeLabel() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="news-category" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Category</label>
                <select id="news-category" wire:model.live="category"
                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2 text-sm text-zinc-800 outline-none transition-all focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    <option value="all">All categories</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="news-type" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Article Type</label>
                <select id="news-type" wire:model.live="newsType"
                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2 text-sm text-zinc-800 outline-none transition-all focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    <option value="all">All types</option>
                    <option value="photo_story">Photo Story</option>
                    <option value="news_feature">News Feature</option>
                </select>
            </div>

            <div>
                <label for="news-status" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</label>
                <select id="news-status" wire:model.live="status"
                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2 text-sm text-zinc-800 outline-none transition-all focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    <option value="all">All statuses</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="news-records" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Records</label>
                <select id="news-records" wire:model.live="recordState"
                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2 text-sm text-zinc-800 outline-none transition-all focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    <option value="active">Active</option>
                    <option value="trashed">Trash</option>
                </select>
            </div>
        </div>

        <div class="news-filter-footer mt-4 border-t border-zinc-100 pt-3.5">
            <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:text-emerald-800 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset filters
            </button>

            <div class="flex items-center gap-2">
                <label for="news-per-page" class="text-xs font-medium text-zinc-500">Rows per page</label>
                <select id="news-per-page" wire:model.live="perPage"
                    class="rounded-lg border border-zinc-200 bg-zinc-50/50 px-2.5 py-1 text-xs text-zinc-700 outline-none focus:border-emerald-500 focus:bg-white">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
    </section>

    <!-- Table & Cards Section -->
    <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
        
        <!-- Mobile & Tablet Card Layout (< xl screen) -->
        <div class="divide-y divide-zinc-100 xl:hidden">
            @forelse ($news as $article)
                @php
                    $statusClasses = match ($article->status) {
                        \App\Enums\NewsStatus::Draft => 'bg-zinc-100 text-zinc-700 ring-zinc-200',
                        \App\Enums\NewsStatus::Submitted => 'bg-blue-50 text-blue-700 ring-blue-200',
                        \App\Enums\NewsStatus::ChangesRequested => 'bg-red-50 text-red-700 ring-red-200',
                        \App\Enums\NewsStatus::Approved => 'bg-violet-50 text-violet-700 ring-violet-200',
                        \App\Enums\NewsStatus::Published => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        \App\Enums\NewsStatus::Archived => 'bg-amber-50 text-amber-700 ring-amber-200',
                    };
                    $statusIcon = match ($article->status) {
                        \App\Enums\NewsStatus::Draft => 'fa-regular fa-file-lines',
                        \App\Enums\NewsStatus::Submitted => 'fa-regular fa-paper-plane',
                        \App\Enums\NewsStatus::ChangesRequested => 'fa-solid fa-rotate-left',
                        \App\Enums\NewsStatus::Approved => 'fa-regular fa-circle-check',
                        \App\Enums\NewsStatus::Published => 'fa-solid fa-globe',
                        \App\Enums\NewsStatus::Archived => 'fa-solid fa-box-archive',
                    };
                @endphp

                <article wire:key="mobile-news-{{ $article->id }}" class="p-4 sm:p-5 transition-colors hover:bg-zinc-50/50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm font-semibold text-zinc-900 leading-snug">{{ $article->title }}</h2>
                                @if ($article->is_featured)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200/60 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                        ★ Featured
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 truncate text-xs text-zinc-400 font-mono">/{{ $article->locale->value }}/news/{{ $article->slug }}</p>
                        </div>
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full ring-1 ring-inset {{ $statusClasses }}"
                            title="{{ $article->status->label() }}" aria-label="{{ $article->status->label() }}">
                            <i class="{{ $statusIcon }}" aria-hidden="true"></i>
                        </span>
                    </div>

                    <div class="mt-3.5 grid grid-cols-2 gap-3 border-y border-zinc-100 py-3 text-xs">
                        <div>
                            <span class="text-zinc-400 block text-[10px] uppercase font-semibold">Language</span>
                            <span @class([
                                'mt-1 inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-bold tracking-wide',
                                'border-blue-200 bg-blue-50 text-blue-700' => $article->locale === \App\Enums\NewsLocale::English,
                                'border-emerald-200 bg-emerald-50 text-emerald-700' => $article->locale === \App\Enums\NewsLocale::Sinhala,
                                'border-violet-200 bg-violet-50 text-violet-700' => $article->locale === \App\Enums\NewsLocale::Tamil,
                            ]) title="{{ $article->locale->nativeLabel() }}">
                                {{ strtoupper($article->locale->value) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-[10px] uppercase font-semibold">Category / Type</span>
                            <span class="mt-0.5 font-medium text-zinc-700 block truncate">{{ $article->category?->name ?? 'Uncategorised' }}</span>
                            <span class="mt-0.5 inline-block text-[10px] font-bold {{ $article->isPhotoStory() ? 'text-purple-700' : 'text-emerald-700' }}">
                                {{ $article->typeLabel() }}
                            </span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-[10px] uppercase font-semibold">Author</span>
                            <span class="mt-0.5 font-medium text-zinc-700 block truncate">{{ $article->creator?->name ?? 'Unknown' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-[10px] uppercase font-semibold">Updated</span>
                            <span class="mt-0.5 font-medium text-zinc-700 block">{{ $article->updated_at?->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-3">
                        @if ($recordState === 'trashed')
                            @can('news.view')
                                <x-admin.icon-action label="Preview" icon="eye" tone="neutral"
                                    href="{{ route('admin.news.preview', $article) }}" target="_blank" rel="noopener noreferrer"
                                />
                            @endcan
                            @can('news.delete')
                                <x-admin.icon-action label="Restore" icon="arrow-uturn-left" tone="success"
                                    type="button" wire:click="restoreArticle({{ $article->id }})"
                                    wire:confirm="Restore this news article from Trash?"
                                    wire:loading.attr="disabled"
                                    wire:target="restoreArticle({{ $article->id }})"
                                />
                                <x-admin.icon-action label="Delete Permanently" icon="trash" tone="danger"
                                    type="button" wire:click="forceDeleteArticle({{ $article->id }})"
                                    wire:confirm="Permanently delete this news article and its revision history? This action cannot be undone."
                                    wire:loading.attr="disabled"
                                    wire:target="forceDeleteArticle({{ $article->id }})"
                                />
                            @endcan
                        @else
                            @can('news.view')
                                <x-admin.icon-action label="Preview" icon="eye" tone="neutral"
                                    href="{{ route('admin.news.preview', $article) }}" target="_blank" rel="noopener noreferrer"
                                />
                            @endcan
                            @if ($article->status === \App\Enums\NewsStatus::Published)
                                @can('news.publish')
                                    <x-admin.icon-action label="Unpublish" icon="eye-slash" tone="warning"
                                        type="button" wire:click="unpublishArticle({{ $article->id }})"
                                        wire:confirm="{{ in_array($article->locale, [\App\Enums\NewsLocale::English, \App\Enums\NewsLocale::Sinhala], true) ? 'Unpublish this article and its linked English/Sinhala versions and return them to Draft?' : 'Unpublish this news article and return it to Draft?' }}"
                                        wire:loading.attr="disabled"
                                        wire:target="unpublishArticle({{ $article->id }})"
                                    />
                                @endcan
                            @else
                                @can('news.publish')
                                    <x-admin.icon-action label="Publish" icon="arrow-up-tray" tone="success"
                                        type="button" wire:click="publishArticle({{ $article->id }})"
                                        wire:confirm="Publish this news article?"
                                        wire:loading.attr="disabled"
                                        wire:target="publishArticle({{ $article->id }})"
                                    />
                                @endcan
                            @endif
                            @if ($article->status === \App\Enums\NewsStatus::Draft)
                                @can('news.update')
                                    <x-admin.icon-action label="Edit" icon="pencil-square" tone="info"
                                        href="{{ route('admin.news.edit', $article) }}" wire:navigate
                                    />
                                @endcan
                                @can('news.delete')
                                    <x-admin.icon-action label="Delete" icon="trash" tone="danger"
                                        type="button" wire:click="deleteArticle({{ $article->id }})"
                                        wire:confirm="Move this draft news article to Trash?"
                                        wire:loading.attr="disabled"
                                        wire:target="deleteArticle({{ $article->id }})"
                                    />
                                @endcan
                            @endif
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-5 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100">
                        <svg class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <p class="mt-3 font-semibold text-zinc-800">No news articles found</p>
                    <p class="mt-1 text-sm text-zinc-500">Try adjusting your filters or create a new article.</p>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table Layout (>= xl screen) -->
        <div class="hidden overflow-x-auto xl:block">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-200/80 bg-zinc-50/70 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                        <th class="py-3.5 pl-6 pr-4">
                            <button type="button" wire:click="sort('title')" class="inline-flex items-center gap-1 hover:text-zinc-900 transition-colors">
                                Article
                                @if ($sortField === 'title')
                                    <span class="text-emerald-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3.5">
                            <button type="button" wire:click="sort('locale')" class="inline-flex items-center gap-1 hover:text-zinc-900 transition-colors">
                                Language
                                @if ($sortField === 'locale')
                                    <span class="text-emerald-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5">
                            <button type="button" wire:click="sort('status')" class="inline-flex items-center gap-1 hover:text-zinc-900 transition-colors">
                                Status
                                @if ($sortField === 'status')
                                    <span class="text-emerald-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3.5">Author</th>
                        <th class="px-4 py-3.5">
                            <button type="button" wire:click="sort('updated_at')" class="inline-flex items-center gap-1 hover:text-zinc-900 transition-colors">
                                Updated
                                @if ($sortField === 'updated_at')
                                    <span class="text-emerald-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3.5">
                            <button type="button" wire:click="sort('published_at')" class="inline-flex items-center gap-1 hover:text-zinc-900 transition-colors">
                                Published
                                @if ($sortField === 'published_at')
                                    <span class="text-emerald-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="py-3.5 pl-4 pr-6 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 text-sm">
                    @forelse ($news as $article)
                        @php
                            $statusClasses = match ($article->status) {
                                \App\Enums\NewsStatus::Draft => 'bg-zinc-100 text-zinc-700 ring-zinc-200',
                                \App\Enums\NewsStatus::Submitted => 'bg-blue-50 text-blue-700 ring-blue-200',
                                \App\Enums\NewsStatus::ChangesRequested => 'bg-red-50 text-red-700 ring-red-200',
                                \App\Enums\NewsStatus::Approved => 'bg-violet-50 text-violet-700 ring-violet-200',
                                \App\Enums\NewsStatus::Published => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                \App\Enums\NewsStatus::Archived => 'bg-amber-50 text-amber-700 ring-amber-200',
                            };
                            $statusIcon = match ($article->status) {
                                \App\Enums\NewsStatus::Draft => 'fa-regular fa-file-lines',
                                \App\Enums\NewsStatus::Submitted => 'fa-regular fa-paper-plane',
                                \App\Enums\NewsStatus::ChangesRequested => 'fa-solid fa-rotate-left',
                                \App\Enums\NewsStatus::Approved => 'fa-regular fa-circle-check',
                                \App\Enums\NewsStatus::Published => 'fa-solid fa-globe',
                                \App\Enums\NewsStatus::Archived => 'fa-solid fa-box-archive',
                            };
                        @endphp

                        <tr wire:key="news-{{ $article->id }}" class="group transition-colors hover:bg-zinc-50/70">
                            <!-- Article Title & Summary -->
                            <td class="py-4 pl-6 pr-4 align-top">
                                <div class="max-w-md">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('admin.news.preview', $article) }}" target="_blank" class="font-medium text-zinc-900 transition-colors hover:text-emerald-700 leading-snug">
                                            {{ $article->title }}
                                        </a>
                                        {{-- @if ($article->is_featured)
                                            <span class="inline-flex items-center rounded-full bg-amber-50 border border-amber-200/60 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">
                                                Featured
                                            </span>
                                        @endif --}}
                                    </div>
{{-- 
                                    <p class="mt-1 text-xs text-zinc-400 font-mono">/{{ $article->locale->value }}/news/{{ $article->slug }}</p> --}}

                                    <!-- Translation Indicators -->
                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                        @foreach ($locales as $localeOption)
                                            @php
                                                $translation = $article->translationVersions->first(
                                                    fn(\App\Models\News $version): bool => $version->locale === $localeOption,
                                                );
                                            @endphp

                                            @if ($translation instanceof \App\Models\News)
                                                <span @class([
                                                    'inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                                    'bg-emerald-100/80 text-emerald-800 border border-emerald-200/50' => $localeOption === $article->locale,
                                                    'bg-zinc-100 text-zinc-600 border border-zinc-200/50' => $localeOption !== $article->locale,
                                                ])>
                                                    {{ strtoupper($localeOption->value) }}
                                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </span>
                                            @elseif ($recordState !== 'trashed')
                                                @can('news.create')
                                                    <a href="{{ route('admin.news.translations.create', ['newsId' => $article->id, 'locale' => $localeOption->value]) }}"
                                                        wire:navigate
                                                        class="inline-flex items-center gap-0.5 rounded-md border border-dashed border-zinc-300 bg-white px-1.5 py-0.5 text-[10px] font-bold text-zinc-400 transition-all hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50/30">
                                                        + {{ strtoupper($localeOption->value) }}
                                                    </a>
                                                @endcan
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- @if ($article->summary)
                                        <p class="mt-2 text-xs text-zinc-500 line-clamp-2 leading-relaxed">
                                            {{ \Illuminate\Support\Str::limit($article->summary, 110) }}
                                        </p>
                                    @endif --}}
                                </div>
                            </td>

                            <!-- Language -->
                            <td class="whitespace-nowrap px-4 py-4 align-top">
                                <span @class([
                                    'inline-flex min-w-12 justify-center rounded-md border px-2.5 py-1 text-[11px] font-bold tracking-wide',
                                    'border-blue-200 bg-blue-50 text-blue-700' => $article->locale === \App\Enums\NewsLocale::English,
                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $article->locale === \App\Enums\NewsLocale::Sinhala,
                                    'border-violet-200 bg-violet-50 text-violet-700' => $article->locale === \App\Enums\NewsLocale::Tamil,
                                ]) title="{{ $article->locale->nativeLabel() }}">
                                    {{ strtoupper($article->locale->value) }}
                                </span>
                                {{-- <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">
                                    {{ $article->locale->value }}
                                </p> --}}
                            </td>

                            <!-- Category & Type -->
                            <td class="whitespace-nowrap px-4 py-4 align-top">
                                @if ($article->category)
                                    <span class="inline-flex rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700">
                                        {{ $article->category->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-zinc-400 italic">Uncategorised</span>
                                @endif
                                <div class="mt-1.5">
                                    @if ($article->isPhotoStory())
                                        <span class="inline-flex items-center gap-1 rounded-md bg-purple-50 border border-purple-200/60 px-2 py-0.5 text-[10px] font-bold text-purple-700">
                                            <i class="fa-solid fa-camera-retro"></i> Photo Story
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                            <i class="fa-solid fa-newspaper"></i> News Feature
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="whitespace-nowrap px-4 py-4 align-top">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full ring-1 ring-inset {{ $statusClasses }}"
                                    title="{{ $article->status->label() }}" aria-label="{{ $article->status->label() }}">
                                    <i class="{{ $statusIcon }}" aria-hidden="true"></i>
                                </span>
                            </td>

                            <!-- Author -->
                            <td class="whitespace-nowrap px-4 py-4 align-top">
                                <p class="font-medium text-zinc-800">{{ $article->creator?->name ?? 'Unknown' }}</p>
                                @if ($article->updater && !$article->updater->is($article->creator))
                                    <p class="mt-0.5 text-[11px] text-zinc-400">Ed. {{ $article->updater->name }}</p>
                                @endif
                            </td>

                            <!-- Updated Date -->
                            <td class="whitespace-nowrap px-4 py-4 align-top">
                                <p class="font-mono text-xs text-zinc-700">{{ $article->updated_at?->format('Y-m-d') }}</p>
                                <p class="font-mono text-[11px] text-zinc-400">{{ $article->updated_at?->format('H:i') }}</p>
                            </td>

                            <!-- Published Date -->
                            <td class="whitespace-nowrap px-4 py-4 align-top">
                                @if ($article->published_at)
                                    <p class="font-mono text-xs text-zinc-700">{{ $article->published_at->format('Y-m-d') }}</p>
                                    <p class="font-mono text-[11px] text-zinc-400">{{ $article->published_at->format('H:i') }}</p>
                                @else
                                    <span class="text-xs text-zinc-400 italic">Not published</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="whitespace-nowrap py-4 pl-4 pr-6 text-right align-top">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($recordState === 'trashed')
                                        @can('news.view')
                                            <x-admin.icon-action label="Preview" icon="eye" tone="neutral"
                                                href="{{ route('admin.news.preview', $article) }}" target="_blank" rel="noopener noreferrer"
                                            />
                                        @endcan
                                        @can('news.delete')
                                            <x-admin.icon-action label="Restore" icon="arrow-uturn-left" tone="success"
                                                type="button" wire:click="restoreArticle({{ $article->id }})"
                                                wire:confirm="Restore this news article from Trash?"
                                                wire:loading.attr="disabled"
                                                wire:target="restoreArticle({{ $article->id }})"
                                            />
                                            <x-admin.icon-action label="Delete Permanently" icon="trash" tone="danger"
                                                type="button" wire:click="forceDeleteArticle({{ $article->id }})"
                                                wire:confirm="Permanently delete this news article and its revision history? This action cannot be undone."
                                                wire:loading.attr="disabled"
                                                wire:target="forceDeleteArticle({{ $article->id }})"
                                            />
                                        @endcan
                                    @else
                                        @can('news.view')
                                            <x-admin.icon-action label="Preview" icon="eye" tone="neutral"
                                                href="{{ route('admin.news.preview', $article) }}" target="_blank" rel="noopener noreferrer"
                                            />
                                        @endcan

                                        @if ($article->status === \App\Enums\NewsStatus::Published)
                                            @can('news.publish')
                                                <x-admin.icon-action label="Unpublish" icon="eye-slash" tone="warning"
                                                    type="button" wire:click="unpublishArticle({{ $article->id }})"
                                                    wire:confirm="{{ in_array($article->locale, [\App\Enums\NewsLocale::English, \App\Enums\NewsLocale::Sinhala], true) ? 'Unpublish this article and its linked English/Sinhala versions and return them to Draft?' : 'Unpublish this news article and return it to Draft?' }}"
                                                    wire:loading.attr="disabled"
                                                    wire:target="unpublishArticle({{ $article->id }})"
                                                />
                                            @endcan
                                        @else
                                            @can('news.publish')
                                                <x-admin.icon-action label="Publish" icon="arrow-up-tray" tone="success"
                                                    type="button" wire:click="publishArticle({{ $article->id }})"
                                                    wire:confirm="Publish this news article?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="publishArticle({{ $article->id }})"
                                                />
                                            @endcan
                                        @endif

                                        @if ($article->status === \App\Enums\NewsStatus::Draft)
                                            @can('news.update')
                                                <x-admin.icon-action label="Edit" icon="pencil-square" tone="info"
                                                    href="{{ route('admin.news.edit', $article) }}" wire:navigate
                                                />
                                            @endcan

                                            @can('news.delete')
                                                <x-admin.icon-action label="Delete" icon="trash" tone="danger"
                                                    type="button" wire:click="deleteArticle({{ $article->id }})"
                                                    wire:confirm="Move this draft news article to Trash?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteArticle({{ $article->id }})"
                                                />
                                            @endcan
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100">
                                    <svg class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                    </svg>
                                </div>
                                <p class="mt-3 font-semibold text-zinc-800">No news articles found</p>
                                <p class="mt-1 text-sm text-zinc-500">Try adjusting your search or filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if ($news->hasPages())
            <div class="border-t border-zinc-200/80 bg-zinc-50/50 px-5 py-3.5">
                {{ $news->links() }}
            </div>
        @endif
    </section>
</div>
