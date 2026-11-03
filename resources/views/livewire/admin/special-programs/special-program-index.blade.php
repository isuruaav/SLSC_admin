<div class="mx-auto max-w-7xl space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800">
                    <i class="fa-solid fa-shapes text-sm"></i>
                </span>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-950">Special Programs</h1>
            </div>
            <p class="mt-1 text-sm text-zinc-600">
                Manage special program links displayed on the homepage in both English and Sinhala (ද්විභාෂා).
            </p>
        </div>

        <button type="button" wire:click="startCreate"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Add Special Program</span>
        </button>
    </div>

    <!-- Status Banner -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Filters Section -->
    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Search -->
            <div class="sm:col-span-2">
                <label for="program-search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Search</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                    <input id="program-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Search English or Sinhala title, URL..."
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 pl-9 pr-4 py-2 text-sm text-zinc-800 outline-none transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <label for="category-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Category</label>
                <select id="category-filter" wire:model.live="categoryFilter"
                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2 text-sm text-zinc-800 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    <option value="all">All Categories</option>
                    @foreach ($categories as $catKey => $catData)
                        <option value="{{ $catKey }}">{{ $catData['en'] }} ({{ $catData['si'] }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</label>
                <select id="status-filter" wire:model.live="statusFilter"
                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2 text-sm text-zinc-800 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    <option value="all">All Statuses</option>
                    <option value="active">Active only</option>
                    <option value="inactive">Inactive only</option>
                </select>
            </div>
        </div>
    </section>

    <!-- Programs Table Section -->
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-200/80 bg-zinc-50/70 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                        <th class="py-3.5 pl-6 pr-2 w-14">
                            <button type="button" wire:click="sortBy('sort_order')" class="inline-flex items-center gap-1 hover:text-zinc-900 transition">
                                Order
                                @if ($sortField === 'sort_order')
                                    <span class="text-emerald-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-3.5 w-16">Image</th>
                        <th class="px-4 py-3.5">
                            <button type="button" wire:click="sortBy('title_en')" class="inline-flex items-center gap-1 hover:text-zinc-900 transition">
                                Program Title (English &amp; Sinhala)
                                @if ($sortField === 'title_en')
                                    <span class="text-emerald-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5">Target URL</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="py-3.5 pl-4 pr-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-sm">
                    @forelse ($programs as $program)
                        @php
                            $categoryBg = match ($program->category) {
                                'heritage' => 'bg-amber-50 text-amber-800 border-amber-200/60',
                                'community' => 'bg-blue-50 text-blue-800 border-blue-200/60',
                                'resources' => 'bg-purple-50 text-purple-800 border-purple-200/60',
                                default => 'bg-zinc-100 text-zinc-700 border-zinc-200',
                            };
                        @endphp
                        <tr wire:key="program-{{ $program->id }}" class="group transition hover:bg-zinc-50/60">
                            <!-- Sort Order -->
                            <td class="py-4 pl-6 pr-2 align-top">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-zinc-100 text-xs font-bold text-zinc-700">
                                    {{ str_pad((string)$program->sort_order, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>

                            <!-- Image Thumbnail -->
                            <td class="py-4 px-3 align-top">
                                @if ($program->imageUrl())
                                    <img src="{{ $program->imageUrl() }}" alt="{{ $program->title_en }}"
                                        class="h-10 w-12 rounded-lg object-cover border border-zinc-200 shadow-xs transition hover:scale-110">
                                @else
                                    <div class="flex h-10 w-12 items-center justify-center rounded-lg border border-dashed border-zinc-200 bg-zinc-50 text-zinc-400" title="Uses default theme backdrop">
                                        <i class="fa-regular fa-image text-xs"></i>
                                    </div>
                                @endif
                            </td>

                            <!-- Title (EN + SI) -->
                            <td class="py-4 px-4 align-top">
                                <div class="max-w-md space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700">EN</span>
                                        <span class="font-semibold text-zinc-950">{{ $program->title_en }}</span>
                                    </div>
                                    @if ($program->title_si)
                                        <div class="flex items-center gap-2 text-zinc-600">
                                            <span class="inline-flex items-center rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">සිං</span>
                                            <span class="text-xs">{{ $program->title_si }}</span>
                                        </div>
                                    @else
                                        <p class="text-xs text-zinc-400 italic">No Sinhala translation added</p>
                                    @endif
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="whitespace-nowrap px-4 py-4 align-top">
                                <span class="inline-flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-semibold {{ $categoryBg }}">
                                    {{ $program->categoryLabel('en') }}
                                </span>
                                <p class="mt-1 text-[11px] text-zinc-500">{{ $program->categoryLabel('si') }}</p>
                            </td>

                            <!-- Target URL -->
                            <td class="px-4 py-4 align-top">
                                <div class="max-w-xs truncate">
                                    <a href="{{ $program->url }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 hover:text-emerald-800 hover:underline">
                                        <span class="truncate">{{ $program->url }}</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                    </a>
                                    @if ($program->open_in_new_tab)
                                        <span class="block text-[10px] text-zinc-400 mt-0.5">Opens in new tab</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Toggle -->
                            <td class="whitespace-nowrap px-4 py-4 align-top text-center">
                                <button type="button" wire:click="toggleActive({{ $program->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold transition {{ $program->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 hover:bg-emerald-100' : 'bg-zinc-100 text-zinc-600 ring-1 ring-inset ring-zinc-500/20 hover:bg-zinc-200' }}"
                                    title="Click to toggle status">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $program->is_active ? 'bg-emerald-600' : 'bg-zinc-400' }}"></span>
                                    {{ $program->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 pl-4 pr-6 align-top text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $program->id }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700"
                                        title="Edit Special Program">
                                        <i class="fa-solid fa-pencil text-xs"></i>
                                    </button>
                                    <button type="button" wire:click="delete({{ $program->id }})"
                                        wire:confirm="Are you sure you want to delete '{{ $program->title_en }}'?"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 transition hover:border-red-500 hover:bg-red-50 hover:text-red-700"
                                        title="Delete Special Program">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400">
                                    <i class="fa-solid fa-shapes text-lg"></i>
                                </div>
                                <p class="mt-3 font-semibold text-zinc-800">No special programs found</p>
                                <p class="mt-1 text-sm text-zinc-500">Try adjusting your filters or click "Add Special Program" to create one.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($programs->hasPages())
            <div class="border-t border-zinc-100 px-6 py-4">
                {{ $programs->links() }}
            </div>
        @endif
    </section>

    <!-- Slide-over / Modal Editor -->
    @if ($showEditor)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-zinc-950/50 backdrop-blur-sm transition-opacity" wire:click="closeEditor"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl border border-zinc-200 bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl">
                    
                    <!-- Modal Header -->
                    <div class="border-b border-zinc-200 bg-zinc-50/50 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-800">
                                <i class="fa-solid fa-shapes text-xs"></i>
                            </span>
                            <h3 class="text-lg font-bold text-zinc-950" id="modal-title">
                                {{ $editingId ? 'Edit Special Program' : 'Add New Special Program' }}
                            </h3>
                        </div>
                        <button type="button" wire:click="closeEditor" class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 transition">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>

                    <!-- Modal Body / Form -->
                    <form wire:submit="save">
                        <div class="space-y-4 px-6 py-5">
                            
                            <!-- English Title -->
                            <div>
                                <label for="form-title-en" class="mb-1.5 flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-700">
                                    <span>Program Title (English) <span class="text-red-500">*</span></span>
                                    <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-800">EN</span>
                                </label>
                                <input id="form-title-en" type="text" wire:model="titleEn"
                                    placeholder="e.g. Code of Conduct"
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
                                @error('titleEn')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Sinhala Title -->
                            <div>
                                <label for="form-title-si" class="mb-1.5 flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-700">
                                    <span>Program Title (Sinhala / සිංහල)</span>
                                    <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">සිංහල</span>
                                </label>
                                <input id="form-title-si" type="text" wire:model="titleSi"
                                    placeholder="උදා: ආචාර ධර්ම පද්ධතිය"
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
                                <p class="mt-1 text-[11px] text-zinc-500">Displayed when visitors view the website in Sinhala.</p>
                                @error('titleSi')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Target URL -->
                            <div>
                                <label for="form-url" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-700">
                                    Target URL / Document Link <span class="text-red-500">*</span>
                                </label>
                                <input id="form-url" type="text" wire:model="url"
                                    placeholder="https://alt.army.lk/... or /public/document.pdf"
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm font-mono text-zinc-900 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
                                <p class="mt-1 text-[11px] text-zinc-500">Enter a complete external URL or relative internal path.</p>
                                @error('url')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Program Image / Card Backdrop -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-700">
                                    Program Image / Card Backdrop
                                </label>
                                <div class="space-y-3">
                                    @if ($newImage)
                                        <div class="relative inline-block overflow-hidden rounded-xl border border-emerald-300 bg-emerald-50/50 p-2.5">
                                            <img src="{{ $newImage->temporaryUrl() }}" alt="Preview" class="h-28 w-auto max-w-full rounded-lg object-cover shadow-xs">
                                            <div class="mt-2 flex items-center justify-between gap-3">
                                                <span class="text-xs font-semibold text-emerald-800">New Image Selected</span>
                                                <button type="button" wire:click="removeImage" class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700">
                                                    <i class="fa-solid fa-trash-can text-[10px]"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    @elseif ($currentImageUrl)
                                        <div class="relative inline-block overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 p-2.5">
                                            <img src="{{ $currentImageUrl }}" alt="Current Image" class="h-28 w-auto max-w-full rounded-lg object-cover shadow-xs">
                                            <div class="mt-2 flex items-center justify-between gap-3">
                                                <span class="text-xs text-zinc-600">Current Image</span>
                                                <button type="button" wire:click="removeImage" class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700">
                                                    <i class="fa-solid fa-trash-can text-[10px]"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50/60 p-4 transition hover:border-emerald-500 hover:bg-emerald-50/20">
                                        <input id="form-program-image" type="file" wire:model="newImage" accept="image/png,image/jpeg,image/webp" class="sr-only">
                                        <label for="form-program-image" class="cursor-pointer text-center">
                                            <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 group-hover:bg-emerald-100 group-hover:text-emerald-700">
                                                <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                                            </div>
                                            <p class="mt-2 text-xs font-semibold text-zinc-800">
                                                <span class="text-emerald-700 hover:underline">Click to upload image</span> or drag and drop
                                            </p>
                                            <p class="mt-0.5 text-[11px] text-zinc-500">PNG, JPG, WebP up to 10MB</p>
                                        </label>
                                        <div wire:loading wire:target="newImage" class="mt-2 text-xs font-semibold text-emerald-700">
                                            <i class="fa-solid fa-spinner fa-spin mr-1"></i> Uploading preview...
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-1 text-[11px] text-zinc-500">Displayed on the homepage when visitors interact with this program card.</p>
                                @error('newImage')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Category -->
                                <div>
                                    <label for="form-category" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-700">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select id="form-category" wire:model="category"
                                        class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
                                        @foreach ($categories as $catKey => $catData)
                                            <option value="{{ $catKey }}">{{ $catData['en'] }} ({{ $catData['si'] }})</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Sort Order -->
                                <div>
                                    <label for="form-sort-order" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-700">
                                        Display Order <span class="text-red-500">*</span>
                                    </label>
                                    <input id="form-sort-order" type="number" min="0" wire:model="sortOrder"
                                        class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
                                    <p class="mt-1 text-[11px] text-zinc-500">Lower numbers appear first on homepage.</p>
                                    @error('sortOrder')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Options: Active & New Tab -->
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50/60 p-3.5 space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <span class="text-sm font-semibold text-zinc-900">Active on Homepage</span>
                                        <span class="block text-xs text-zinc-500">Display this program in the homepage Special Programs directory.</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer border-t border-zinc-200/60 pt-3">
                                    <input type="checkbox" wire:model="openInNewTab" class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <span class="text-sm font-semibold text-zinc-900">Open in new browser tab</span>
                                        <span class="block text-xs text-zinc-500">Opens link with target="_blank".</span>
                                    </div>
                                </label>
                            </div>

                        </div>

                        <!-- Modal Actions -->
                        <div class="border-t border-zinc-200 bg-zinc-50/50 px-6 py-4 flex items-center justify-end gap-3">
                            <button type="button" wire:click="closeEditor"
                                class="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 transition focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                                <span wire:loading.remove wire:target="save">
                                    <i class="fa-solid fa-check text-xs"></i>
                                    {{ $editingId ? 'Update Program' : 'Save Program' }}
                                </span>
                                <span wire:loading wire:target="save">
                                    <i class="fa-solid fa-spinner fa-spin text-xs"></i> Saving...
                                </span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    @endif
</div>

