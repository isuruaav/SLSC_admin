<div class="mx-auto max-w-7xl space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.organizations.index') }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-100">Legacy Content Importer</h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Official source: <span class="font-mono text-emerald-700 dark:text-emerald-400">https://alt.army.lk/signaller/organization</span> (All 17 Units)
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="importSelected"
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-white px-3.5 py-2 text-xs font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50 dark:border-emerald-700 dark:bg-zinc-900 dark:text-emerald-300">
                <i class="fa-solid fa-check-double"></i>
                <span>Import Selected ({{ count($selectedKeys) }})</span>
            </button>

            <button type="button" wire:click="importAll"
                wire:confirm="Import all 17 public units into Draft status? This will not publish any content automatically."
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800">
                <i class="fa-solid fa-cloud-arrow-down"></i>
                <span>Import All 17 Units</span>
            </button>
        </div>
    </div>

    <!-- Security & Policy Notice -->
    <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-xs text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-200 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2.5">
            <i class="fa-solid fa-shield-halved text-base text-amber-600 dark:text-amber-400"></i>
            <div>
                <p class="font-semibold">Safe Import Pipeline</p>
                <p class="text-[11px] text-amber-800/80 dark:text-amber-300/80">
                    SSRF protection active for <span class="font-mono font-bold">alt.army.lk</span>. Records are created in <span class="font-semibold">Draft status</span>. HTML is strictly sanitized.
                </p>
            </div>
        </div>
        <label class="flex items-center gap-2 cursor-pointer font-medium">
            <input type="checkbox" wire:model.live="overwriteExisting" class="rounded border-amber-400 text-amber-600 focus:ring-amber-500">
            <span>Overwrite existing drafts</span>
        </label>
    </div>

    <!-- Status Banners -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200" role="alert">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if (session('warning'))
        <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 shadow-sm dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200" role="alert">
            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    <!-- Unit Preview Table -->
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        <th class="w-10 px-4 py-3 text-center">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600">
                        </th>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Unit / Organization</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-center">Paragraphs</th>
                        <th class="px-4 py-3 text-center">Tables</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Source URL</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($discovered as $unit)
                        <tr class="transition hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" wire:model.live="selectedKeys" value="{{ $unit['key'] }}"
                                    class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600">
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-[11px] text-zinc-400">{{ sprintf('%02d', $unit['index']) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $unit['name_en'] }}</span>
                                    @if ($unit['short_name'])
                                        <span class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                            {{ $unit['short_name'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-[11px] text-zinc-500">
                                {{ $unit['category_slug'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center font-mono text-zinc-600 dark:text-zinc-300">
                                {{ $unit['paragraph_count'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center font-mono text-zinc-600 dark:text-zinc-300">
                                {{ $unit['table_count'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($unit['is_imported'])
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        Imported ({{ ucfirst($unit['status']) }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                        Ready to Import
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-[10px] text-zinc-400 truncate max-w-xs" title="{{ $unit['source_url'] }}">
                                {{ $unit['source_url'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" wire:click="previewUnit('{{ $unit['key'] }}')"
                                        title="Preview structured content"
                                        class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>
                                    <button type="button" wire:click="importSingle('{{ $unit['key'] }}')"
                                        title="{{ $unit['is_imported'] ? 'Re-import unit' : 'Import unit' }}"
                                        class="inline-flex items-center gap-1 rounded-lg bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold text-zinc-700 transition hover:bg-emerald-700 hover:text-white dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-emerald-700">
                                        <i class="fa-solid fa-download text-[10px]"></i>
                                        <span>{{ $unit['is_imported'] ? 'Re-import' : 'Import' }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Structured Content Preview Modal -->
    @if ($previewData)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-3xl max-h-[85vh] overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div>
                        <h2 class="text-base font-bold text-zinc-950 dark:text-zinc-100">
                            {{ $previewData['title_en'] }} ({{ $previewData['short_name'] }})
                        </h2>
                        <p class="font-mono text-xs text-zinc-400">{{ $previewData['source_url'] }}</p>
                    </div>
                    <button type="button" wire:click="closePreview" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-4 text-xs">
                    @if ($previewExisting)
                        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4"><h3 class="font-bold">Compare before overwrite</h3><table class="mt-3 w-full text-left"><thead><tr><th>Field</th><th>Current</th><th>Incoming</th></tr></thead><tbody><tr><th>English title</th><td>{{ $previewExisting->titleForLocale('en') }}</td><td>{{ $previewData['title_en'] }}</td></tr><tr><th>Short name</th><td>{{ $previewExisting->short_name }}</td><td>{{ $previewData['short_name'] }}</td></tr><tr><th>Content blocks</th><td>{{ $previewExisting->content_blocks_count }}</td><td>{{ count($previewData['blocks']) }}</td></tr></tbody></table><p class="mt-3">Overwrite creates a recovery snapshot and returns the unit to Draft.</p></div>
                    @endif
                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-800/50">
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">English Summary</p>
                        <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $previewData['summary_en'] }}</p>
                    </div>

                    <div class="space-y-3">
                        <p class="font-bold uppercase tracking-wider text-zinc-400 text-[10px]">Structured Content Blocks ({{ count($previewData['blocks']) }})</p>
                        @foreach ($previewData['blocks'] as $b)
                            <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                                <div class="flex items-center justify-between">
                                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ strtoupper($b['block_type']) }}
                                    </span>
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $b['title'] ?? '' }}</span>
                                </div>
                                @if (isset($b['content']))
                                    <p class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $b['content'] }}</p>
                                @endif
                                @if (isset($b['structured_data']['rows']))
                                    <div class="mt-2 overflow-x-auto">
                                        <table class="w-full text-left text-[11px] text-zinc-600 dark:text-zinc-300 border-collapse">
                                            <thead>
                                                <tr class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-100/50">
                                                    @foreach ($b['structured_data']['headers'] as $h)
                                                        <th class="p-1.5 font-semibold">{{ $h }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($b['structured_data']['rows'] as $r)
                                                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                                        @foreach ($r as $cell)
                                                            <td class="p-1.5">{{ $cell }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <button type="button" wire:click="closePreview"
                        class="rounded-xl border border-zinc-300 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        Close
                    </button>
                    <button type="button" wire:click="importSingle('{{ $previewKey }}'); closePreview();"
                        class="rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800">
                        Import This Unit Now
                    </button>
                </div>
            </div>
        </div>
    @endif
    @include('livewire.admin.organizations.revisions')
</div>
