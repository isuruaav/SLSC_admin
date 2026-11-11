<div data-unsaved-form data-draft-key="organization-{{ $organizationId ?? 'new' }}" data-save-actions="save,restoreRevision" class="mx-auto max-w-7xl space-y-6 pb-20">
    <!-- Top Action Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sticky top-4 z-30">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.organizations.index') }}"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                <i class="fa-solid fa-arrow-left text-xs"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-zinc-950 dark:text-zinc-100">
                    {{ $organizationId ? ($translations['en']['title'] ?: 'Edit Organization') : 'New Organization' }}
                </h1>
                <div class="flex items-center gap-2 text-xs text-zinc-500">
                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $status === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ ucfirst($status) }}
                    </span>
                    <span>•</span>
                    <span class="font-mono">{{ $slug ?: 'auto-generated-slug' }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Preview Button -->
            <button type="button" wire:click="togglePreviewModal"
                class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                <i class="fa-regular fa-eye"></i>
                <span>Live Preview</span>
            </button>

            <!-- Save Draft Button -->
            <button type="button" wire:click="save(false)"
                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-800 shadow-sm hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                <i class="fa-regular fa-floppy-disk"></i>
                <span>Save Draft</span>
            </button>

            <!-- Save & Publish Button -->
            @can('organizations.publish')
                <button type="button" wire:click="save(true)" wire:confirm="Save and publish this organization?"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Save &amp; Publish</span>
                </button>
            @endcan
        </div>
    </div>

    <!-- Error Summary if any -->
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
            <p class="font-bold mb-1">Please correct the following errors:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content Area: Multilingual details & Block Builder (2 cols) -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Multilingual Content Card -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <!-- Language Selector Tabs (English & Sinhala) -->
                <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">Language Information</span>
                    <div class="flex items-center gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
                        <button type="button" wire:click="switchLocale('en')"
                            class="rounded-lg px-3 py-1 text-xs font-semibold transition {{ $activeLocale === 'en' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-800' }}">
                            English
                        </button>
                        <button type="button" wire:click="switchLocale('si')"
                            class="rounded-lg px-3 py-1 text-xs font-semibold transition {{ $activeLocale === 'si' ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-800' }}">
                            සිංහල (Sinhala)
                        </button>
                    </div>
                </div>

                <!-- Tab Form Content -->
                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Unit / Organization Title ({{ strtoupper($activeLocale) }}) *
                            </label>
                            <input type="text" wire:model.live.debounce.300ms="translations.{{ $activeLocale }}.title"
                                placeholder="e.g. 1st Regiment Sri Lanka Signal Corps"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3.5 py-2 text-sm text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Short Form / Code <span class="text-zinc-400 font-normal">(e.g. OCSO, DIT)</span>
                            </label>
                            <input type="text" wire:model="shortName" placeholder="e.g. 1 SLSC, DIT, SOS"
                                class="mt-1 w-full font-mono rounded-xl border border-zinc-300 px-3.5 py-2 text-sm font-bold text-emerald-800 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-emerald-300">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Subtitle / Motto ({{ strtoupper($activeLocale) }})
                        </label>
                        <input type="text" wire:model="translations.{{ $activeLocale }}.subtitle"
                            placeholder="e.g. The First and Foremost / Swift & Sure"
                            class="mt-1 w-full rounded-xl border border-zinc-300 px-3.5 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Overview &amp; Executive Summary ({{ strtoupper($activeLocale) }})
                        </label>
                        <textarea wire:model="translations.{{ $activeLocale }}.summary" rows="3"
                            placeholder="Brief military overview, role, and operational responsibilities…"
                            class="mt-1 w-full rounded-xl border border-zinc-300 px-3.5 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                    </div>

                    <!-- SEO Accordion -->
                    <div x-data="{ open: false }" class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                            <span><i class="fa-solid fa-magnifying-glass mr-1"></i> SEO Metadata ({{ strtoupper($activeLocale) }})</span>
                            <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-3 space-y-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-500">SEO Meta Title</label>
                                <input type="text" wire:model="translations.{{ $activeLocale }}.seo_title"
                                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-500">SEO Meta Description</label>
                                <textarea wire:model="translations.{{ $activeLocale }}.seo_description" rows="2"
                                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Builder Section -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                    <div>
                        <h2 class="text-sm font-bold text-zinc-950 dark:text-zinc-100">Page Content Blocks</h2>
                        <p class="text-xs text-zinc-500">Add, reorder, and structure paragraphs, headings, commanding officer tables, and galleries.</p>
                    </div>

                    <!-- Quick Add Dropdown / Toolbar -->
                    <div x-data="{ addOpen: false }" class="relative">
                        <button type="button" @click="addOpen = !addOpen"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>Add Block</span>
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </button>

                        <div x-show="addOpen" @click.away="addOpen = false" x-cloak
                            class="absolute right-0 mt-2 w-56 rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 z-40 text-xs">
                            <button type="button" wire:click="addBlock('paragraph'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-paragraph w-4 text-emerald-600"></i> Paragraph Block
                            </button>
                            <button type="button" wire:click="addBlock('heading'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-heading w-4 text-blue-600"></i> Heading (H2-H4)
                            </button>
                            <button type="button" wire:click="addBlock('table'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-table w-4 text-amber-600"></i> Table (Past COs/Data)
                            </button>
                            <button type="button" wire:click="addBlock('image'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-regular fa-image w-4 text-purple-600"></i> Single Image
                            </button>
                            <button type="button" wire:click="addBlock('gallery'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-images w-4 text-pink-600"></i> Image Gallery
                            </button>
                            <button type="button" wire:click="addBlock('contact'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-address-card w-4 text-indigo-600"></i> Contact Box
                            </button>
                            <button type="button" wire:click="addBlock('timeline'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-timeline w-4 text-teal-600"></i> Timeline / History
                            </button>
                            <button type="button" wire:click="addBlock('list'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-list-ul w-4 text-cyan-600"></i> Ordered/Bullet List
                            </button>
                            <button type="button" wire:click="addBlock('two_column'); addOpen = false;" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-table-columns w-4 text-orange-600"></i> Two Columns
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Block Items List -->
                @if (empty($blocks))
                    <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-xs text-zinc-500 dark:border-zinc-700">
                        <i class="fa-solid fa-cubes text-3xl text-zinc-300 dark:text-zinc-700 mb-2"></i>
                        <p class="font-medium">No content blocks yet</p>
                        <p class="text-zinc-400">Click "Add Block" above to insert paragraphs, commanding officer tables, or images.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($blocks as $idx => $b)
                            <div class="group rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 transition hover:border-zinc-300 hover:bg-white dark:border-zinc-800 dark:bg-zinc-800/40 dark:hover:bg-zinc-800/80"
                                wire:key="block-{{ $b['temp_id'] }}">
                                <!-- Block Bar -->
                                <div class="flex items-center justify-between pb-3 border-b border-zinc-200/80 dark:border-zinc-700/80">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-zinc-200 font-mono text-[11px] font-bold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                            {{ $idx + 1 }}
                                        </span>
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 font-mono text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            {{ strtoupper($b['block_type']) }}
                                        </span>
                                        <input type="text" wire:model="blocks.{{ $idx }}.title" placeholder="Block Title (optional)"
                                            class="rounded-lg border border-transparent bg-transparent px-2 py-1 text-xs font-semibold text-zinc-800 hover:border-zinc-300 focus:border-emerald-600 focus:bg-white focus:outline-none dark:text-zinc-200">
                                    </div>

                                    <div class="flex items-center gap-1 text-zinc-400">
                                        <button type="button" wire:click="moveBlockUp({{ $idx }})" title="Move up" class="p-1 hover:text-emerald-600">
                                            <i class="fa-solid fa-arrow-up text-xs"></i>
                                        </button>
                                        <button type="button" wire:click="moveBlockDown({{ $idx }})" title="Move down" class="p-1 hover:text-emerald-600">
                                            <i class="fa-solid fa-arrow-down text-xs"></i>
                                        </button>
                                        <button type="button" wire:click="duplicateBlock({{ $idx }})" title="Duplicate" class="p-1 hover:text-blue-600">
                                            <i class="fa-regular fa-copy text-xs"></i>
                                        </button>
                                        <button type="button" wire:click="removeBlock({{ $idx }})" title="Delete" class="p-1 hover:text-red-600">
                                            <i class="fa-regular fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Block Body by Type -->
                                <div class="pt-3 text-xs">
                                    {{-- 1. PARAGRAPH / RICH TEXT --}}
                                    @if ($b['block_type'] === 'paragraph' || $b['block_type'] === 'rich_text')
                                        <textarea wire:model="blocks.{{ $idx }}.content" rows="4"
                                            placeholder="Enter paragraph text content…"
                                            class="w-full rounded-xl border border-zinc-300 bg-white p-3 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>

                                    {{-- 2. HEADING --}}
                                    @elseif ($b['block_type'] === 'heading')
                                        <div class="grid grid-cols-4 gap-2">
                                            <div>
                                                <select wire:model="blocks.{{ $idx }}.structured_data.level"
                                                    class="w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs font-mono dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                                    <option value="h2">H2 (Major)</option>
                                                    <option value="h3">H3 (Section)</option>
                                                    <option value="h4">H4 (Sub)</option>
                                                </select>
                                            </div>
                                            <div class="col-span-3">
                                                <input type="text" wire:model="blocks.{{ $idx }}.content" placeholder="Heading Text"
                                                    class="w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs font-semibold text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                            </div>
                                        </div>

                                    {{-- 3. INTERACTIVE DATA TABLE --}}
                                    @elseif ($b['block_type'] === 'table')
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-semibold text-zinc-500">Expandable Rows &amp; Columns</span>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" wire:click="addTableColumn({{ $idx }})"
                                                        class="rounded-lg bg-zinc-200 px-2 py-1 text-[11px] font-semibold text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-200">
                                                        <i class="fa-solid fa-plus text-[9px]"></i> Add Column
                                                    </button>
                                                    <button type="button" wire:click="addTableRow({{ $idx }})"
                                                        class="rounded-lg bg-emerald-700 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-emerald-800">
                                                        <i class="fa-solid fa-plus text-[9px]"></i> Add Row
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                                <table class="w-full border-collapse text-left text-xs">
                                                    <thead>
                                                        <tr class="border-b border-zinc-200 bg-zinc-100/70 dark:border-zinc-700 dark:bg-zinc-800/80">
                                                            @foreach ($b['structured_data']['headers'] ?? [] as $colIdx => $header)
                                                                <th class="p-2">
                                                                    <div class="flex items-center gap-1">
                                                                        <input type="text" wire:model="blocks.{{ $idx }}.structured_data.headers.{{ $colIdx }}"
                                                                            class="w-full rounded border border-zinc-300 bg-white px-1.5 py-1 text-[11px] font-bold text-zinc-800 focus:border-emerald-600 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-100">
                                                                        <button type="button" wire:click="removeTableColumn({{ $idx }}, {{ $colIdx }})" title="Remove Column"
                                                                            class="text-zinc-400 hover:text-red-500">
                                                                            <i class="fa-solid fa-xmark text-[10px]"></i>
                                                                        </button>
                                                                    </div>
                                                                </th>
                                                            @endforeach
                                                            <th class="w-8 p-2"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                                                        @foreach ($b['structured_data']['rows'] ?? [] as $rowIdx => $row)
                                                            <tr>
                                                                @foreach ($row as $colIdx => $cell)
                                                                    <td class="p-1.5">
                                                                        <input type="text" wire:model="blocks.{{ $idx }}.structured_data.rows.{{ $rowIdx }}.{{ $colIdx }}"
                                                                            class="w-full rounded border border-zinc-200 bg-zinc-50/50 px-1.5 py-1 text-[11px] text-zinc-800 focus:border-emerald-600 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                                                    </td>
                                                                @endforeach
                                                                <td class="p-1.5 text-center">
                                                                    <button type="button" wire:click="removeTableRow({{ $idx }}, {{ $rowIdx }})" title="Delete Row"
                                                                        class="text-zinc-400 hover:text-red-600">
                                                                        <i class="fa-regular fa-trash-can text-xs"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    {{-- 4. SINGLE IMAGE --}}
                                    @elseif ($b['block_type'] === 'image')
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-500">Image URL / Path</label>
                                                <input type="text" wire:model.live.debounce.300ms="blocks.{{ $idx }}.content" placeholder="https://… or /storage/…"
                                                    class="mt-1 w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs focus:border-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-500">Caption</label>
                                                <input type="text" wire:model="blocks.{{ $idx }}.structured_data.caption" placeholder="Photo caption"
                                                    class="mt-1 w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs focus:border-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                            </div>
                                            @if (! empty($b['content']))
                                                <div class="sm:col-span-2 mt-2">
                                                    <img src="{{ $b['content'] }}" alt="Preview" class="h-32 rounded-xl object-cover border border-zinc-200 dark:border-zinc-700">
                                                </div>
                                            @endif
                                        </div>

                                    {{-- 5. GALLERY --}}
                                    @elseif ($b['block_type'] === 'gallery')
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-semibold text-zinc-500">Gallery Items</span>
                                                <button type="button" wire:click="addGalleryImage({{ $idx }})"
                                                    class="rounded-lg bg-emerald-700 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-emerald-800">
                                                    <i class="fa-solid fa-plus text-[9px]"></i> Add Image
                                                </button>
                                            </div>
                                            @foreach ($b['structured_data']['images'] ?? [] as $imgIdx => $img)
                                                <div class="flex items-center gap-2">
                                                    <input type="text" wire:model="blocks.{{ $idx }}.structured_data.images.{{ $imgIdx }}.url" placeholder="Image URL"
                                                        class="w-1/2 rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                                    <input type="text" wire:model="blocks.{{ $idx }}.structured_data.images.{{ $imgIdx }}.caption" placeholder="Caption"
                                                        class="w-1/2 rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                                    <button type="button" wire:click="removeGalleryImage({{ $idx }}, {{ $imgIdx }})" class="text-zinc-400 hover:text-red-500">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>

                                    {{-- 6. TIMELINE --}}
                                    @elseif ($b['block_type'] === 'timeline')
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-semibold text-zinc-500">Historical Events</span>
                                                <button type="button" wire:click="addTimelineItem({{ $idx }})"
                                                    class="rounded-lg bg-emerald-700 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-emerald-800">
                                                    <i class="fa-solid fa-plus text-[9px]"></i> Add Event
                                                </button>
                                            </div>
                                            @foreach ($b['structured_data'] ?? [] as $tIdx => $tItem)
                                                <div class="grid grid-cols-6 gap-2 items-center">
                                                    <input type="text" wire:model="blocks.{{ $idx }}.structured_data.{{ $tIdx }}.year" placeholder="Year / Date"
                                                        class="col-span-1 rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs font-mono dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                                    <input type="text" wire:model="blocks.{{ $idx }}.structured_data.{{ $tIdx }}.title" placeholder="Event Title"
                                                        class="col-span-2 rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs font-semibold dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                                    <input type="text" wire:model="blocks.{{ $idx }}.structured_data.{{ $tIdx }}.description" placeholder="Description"
                                                        class="col-span-2 rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                                    <div class="col-span-1 text-right">
                                                        <button type="button" wire:click="removeTimelineItem({{ $idx }}, {{ $tIdx }})" class="text-zinc-400 hover:text-red-500">
                                                            <i class="fa-regular fa-trash-can"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                    {{-- 7. LIST --}}
                                    @elseif ($b['block_type'] === 'list')
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <select wire:model="blocks.{{ $idx }}.structured_data.type" class="rounded-lg border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                                                    <option value="unordered">Bullet List</option>
                                                    <option value="ordered">Numbered List</option>
                                                </select>
                                                <button type="button" wire:click="addListItem({{ $idx }})"
                                                    class="rounded-lg bg-emerald-700 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-emerald-800">
                                                    <i class="fa-solid fa-plus text-[9px]"></i> Add Item
                                                </button>
                                            </div>
                                            @foreach ($b['structured_data']['items'] ?? [] as $lIdx => $lItem)
                                                <div class="flex items-center gap-2">
                                                    <input type="text" wire:model="blocks.{{ $idx }}.structured_data.items.{{ $lIdx }}" placeholder="List item text"
                                                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                                    <button type="button" wire:click="removeListItem({{ $idx }}, {{ $lIdx }})" class="text-zinc-400 hover:text-red-500">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>

                                    {{-- 8. CONTACT --}}
                                    @elseif ($b['block_type'] === 'contact')
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-500">Telephone</label>
                                                <input type="text" wire:model="blocks.{{ $idx }}.structured_data.telephone"
                                                    class="mt-1 w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-500">Email</label>
                                                <input type="email" wire:model="blocks.{{ $idx }}.structured_data.email"
                                                    class="mt-1 w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                            </div>
                                            <div class="col-span-2">
                                                <label class="block text-[11px] font-semibold text-zinc-500">Location Address</label>
                                                <input type="text" wire:model="blocks.{{ $idx }}.structured_data.location"
                                                    class="mt-1 w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                            </div>
                                        </div>

                                    {{-- 9. TWO COLUMNS --}}
                                    @elseif ($b['block_type'] === 'two_column')
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-500">Left Column</label>
                                                <textarea wire:model="blocks.{{ $idx }}.structured_data.col1" rows="3"
                                                    class="mt-1 w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-500">Right Column</label>
                                                <textarea wire:model="blocks.{{ $idx }}.structured_data.col2" rows="3"
                                                    class="mt-1 w-full rounded-xl border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Attributes (1 col) -->
        <div class="space-y-6">
            <!-- Publishing & Status -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Status &amp; Hierarchy</h3>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Publication Status</label>
                    <select wire:model="status"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Category</label>
                    <select wire:model="categoryId"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">-- No Category --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name_en }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Parent Formation (Optional)</label>
                    <select wire:model="parentId"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">-- None (Top Level) --</option>
                        @foreach ($potentialParents as $p)
                            <option value="{{ $p->id }}">{{ $p->titleForLocale('en') }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Display Order</label>
                    <input type="number" wire:model="sortOrder"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                </div>

                <div class="flex items-center pt-2">
                    <label class="flex items-center gap-2 text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">
                        <input type="checkbox" wire:model="isFeatured" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600">
                        <span>Feature on directory homepage</span>
                    </label>
                </div>
            </div>

            <!-- Identifier & Metadata -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Unit Identifiers</h3>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Slug *</label>
                    <input type="text" wire:model="slug"
                        class="mt-1 w-full font-mono rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    @error('slug') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Short Name / Code</label>
                    <input type="text" wire:model="shortName" placeholder="e.g. 1 SLSC, DIT, SOS"
                        class="mt-1 w-full font-mono rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Featured Image URL</label>
                    <input type="text" wire:model.live.debounce.300ms="featuredImageUrl" placeholder="https://… or /assets/…"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    @if ($featuredImageUrl)
                        <div class="mt-2">
                            <img src="{{ $featuredImageUrl }}" alt="Featured" class="h-28 w-full rounded-xl object-cover border border-zinc-200 dark:border-zinc-700">
                        </div>
                    @endif
                </div>
            </div>

            <!-- Contact Information -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Contact &amp; Location</h3>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Telephone</label>
                    <input type="text" wire:model="telephone" placeholder="+94 11 2514333"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Email</label>
                    <input type="email" wire:model="email" placeholder="unit@army.lk"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Location</label>
                    <input type="text" wire:model="location" placeholder="Panagoda, Kandy, etc."
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                </div>
            </div>
        </div>
    </div>

    <!-- Live Preview Modal -->
    @if ($showPreviewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-8 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-500">Public Page Live Preview ({{ strtoupper($activeLocale) }})</h2>
                    </div>
                    <button type="button" wire:click="togglePreviewModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="mt-6 space-y-6">
                    <!-- Hero Header Preview -->
                    <div class="rounded-2xl bg-gradient-to-br from-zinc-950 via-zinc-900 to-emerald-950 p-8 text-white shadow-lg">
                        @if ($shortName)
                            <span class="rounded bg-emerald-600/40 px-2.5 py-1 font-mono text-xs font-bold text-emerald-300 border border-emerald-500/30">
                                {{ $shortName }}
                            </span>
                        @endif
                        <h1 class="mt-3 text-3xl font-extrabold tracking-tight">
                            {{ $translations[$activeLocale]['title'] ?: 'Unit Title' }}
                        </h1>
                        @if (! empty($translations[$activeLocale]['subtitle']))
                            <p class="mt-2 text-sm text-emerald-300/90 font-medium">
                                {{ $translations[$activeLocale]['subtitle'] }}
                            </p>
                        @endif
                        @if (! empty($translations[$activeLocale]['summary']))
                            <p class="mt-4 text-xs text-zinc-300 leading-relaxed max-w-2xl">
                                {{ $translations[$activeLocale]['summary'] }}
                            </p>
                        @endif
                    </div>

                    <!-- Rendered Blocks Preview -->
                    <div class="space-y-6 py-4">
                        @foreach ($blocks as $pb)
                            <div class="space-y-2">
                                @if ($pb['block_type'] === 'heading')
                                    <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-2 dark:border-zinc-800">
                                        {{ $pb['content'] }}
                                    </h2>
                                @elseif ($pb['block_type'] === 'paragraph' || $pb['block_type'] === 'rich_text')
                                    @if ($pb['title'])
                                        <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-200">{{ $pb['title'] }}</h3>
                                    @endif
                                    <p class="text-xs text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line">{{ $pb['content'] }}</p>
                                @elseif ($pb['block_type'] === 'table')
                                    @if ($pb['title'])
                                        <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-200">{{ $pb['title'] }}</h3>
                                    @endif
                                    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200">
                                                <tr>
                                                    @foreach ($pb['structured_data']['headers'] ?? [] as $th)
                                                        <th class="p-2.5 font-bold">{{ $th }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                                @foreach ($pb['structured_data']['rows'] ?? [] as $tr)
                                                    <tr>
                                                        @foreach ($tr as $tc)
                                                            <td class="p-2.5 text-zinc-700 dark:text-zinc-300">{{ $tc }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @elseif ($pb['block_type'] === 'contact')
                                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 text-xs dark:border-emerald-900 dark:bg-emerald-950/30">
                                        <p class="font-bold text-emerald-900 dark:text-emerald-200 mb-2">{{ $pb['title'] ?: 'Contact Information' }}</p>
                                        <div class="space-y-1 text-zinc-700 dark:text-zinc-300">
                                            @if (! empty($pb['structured_data']['telephone']))
                                                <p><i class="fa-solid fa-phone mr-2 text-emerald-600"></i> {{ $pb['structured_data']['telephone'] }}</p>
                                            @endif
                                            @if (! empty($pb['structured_data']['email']))
                                                <p><i class="fa-solid fa-envelope mr-2 text-emerald-600"></i> {{ $pb['structured_data']['email'] }}</p>
                                            @endif
                                            @if (! empty($pb['structured_data']['location']))
                                                <p><i class="fa-solid fa-location-dot mr-2 text-emerald-600"></i> {{ $pb['structured_data']['location'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <button type="button" wire:click="togglePreviewModal"
                        class="rounded-xl bg-zinc-900 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900">
                        Close Preview
                    </button>
                </div>
            </div>
        </div>
    @endif
    @include('livewire.admin.organizations.revisions')
</div>
