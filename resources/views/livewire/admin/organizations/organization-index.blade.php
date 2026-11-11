<div class="mx-auto max-w-7xl space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                    <i class="fa-solid fa-sitemap text-base"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-100">Organization Management</h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Headquarters, Directorates, Brigades, Regiments &amp; Training Establishments</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('organizations.categories.manage')
                <a href="{{ route('admin.organizations.categories.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    <i class="fa-solid fa-tags"></i>
                    <span>Categories</span>
                </a>
            @endcan

            @can('organizations.import')
                <a href="{{ route('admin.organizations.import.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900">
                    <i class="fa-solid fa-file-import"></i>
                    <span>Import Legacy Content</span>
                </a>
            @endcan

            @can('organizations.create')
                <a href="{{ route('admin.organizations.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Organization</span>
                </a>
            @endcan
        </div>
    </div>

    <!-- Status Banner -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200" role="alert">
            <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Filters Bar -->
    <div class="grid grid-cols-1 gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-4">
        <!-- Search -->
        <div class="relative sm:col-span-2">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-zinc-400"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, short name or slug…"
                class="w-full rounded-xl border border-zinc-300 bg-zinc-50/50 py-2 pl-9 pr-4 text-xs text-zinc-900 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
        </div>

        <!-- Category -->
        <div>
            <select wire:model.live="category"
                class="w-full rounded-xl border border-zinc-300 bg-zinc-50/50 px-3 py-2 text-xs text-zinc-900 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="all">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name_en }}</option>
                @endforeach
            </select>
        </div>

        <!-- Status -->
        <div>
            <select wire:model.live="status"
                class="w-full rounded-xl border border-zinc-300 bg-zinc-50/50 px-3 py-2 text-xs text-zinc-900 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="all">All Statuses</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table role="table" aria-label="Organizations" class="admin-responsive-table w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Organization / Unit</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Languages</th>
                        <th class="px-4 py-3">Blocks</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($organizations as $org)
                        <tr class="transition hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                            <!-- Sort Order & Movement -->
                            <td data-label="Order" class="whitespace-nowrap px-4 py-3 font-mono text-xs">
                                <div class="flex items-center gap-1">
                                    <span class="w-5 text-center font-bold text-zinc-700 dark:text-zinc-300">{{ $org->sort_order }}</span>
                                    <div class="flex flex-col">
                                        <button type="button" wire:click="moveUp({{ $org->id }})" title="Move up" aria-label="Move up"
                                            class="text-[10px] text-zinc-400 hover:text-emerald-600">
                                            <i class="fa-solid fa-chevron-up"></i>
                                        </button>
                                        <button type="button" wire:click="moveDown({{ $org->id }})" title="Move down" aria-label="Move down"
                                            class="text-[10px] text-zinc-400 hover:text-emerald-600">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>

                            <!-- Organization Title & Short Name -->
                            <td data-card-title class="px-4 py-3">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('admin.organizations.edit', $org->id) }}"
                                            class="font-semibold text-zinc-900 hover:text-emerald-700 dark:text-zinc-100 dark:hover:text-emerald-400">
                                            {{ $org->titleForLocale('en') }}
                                        </a>

                                        @if ($editingShortNameId === $org->id)
                                            <div class="inline-flex items-center gap-1" @click.outside="$wire.cancelEditShortName()">
                                                <input type="text" wire:model="editingShortNameValue" wire:keydown.enter="saveShortName" wire:keydown.escape="cancelEditShortName"
                                                    placeholder="e.g. OCSO" autofocus
                                                    class="h-6 w-28 rounded border border-emerald-500 bg-white px-1.5 font-mono text-[11px] font-bold text-zinc-900 shadow-sm focus:outline-none dark:bg-zinc-800 dark:text-zinc-100">
                                                <button type="button" wire:click="saveShortName" title="Save short name" aria-label="Save short name"
                                                    class="inline-flex h-6 w-6 items-center justify-center rounded bg-emerald-700 text-white hover:bg-emerald-800">
                                                    <i class="fa-solid fa-check text-[10px]"></i>
                                                </button>
                                                <button type="button" wire:click="cancelEditShortName" title="Cancel" aria-label="Cancel"
                                                    class="inline-flex h-6 w-6 items-center justify-center rounded bg-zinc-200 text-zinc-600 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300">
                                                    <i class="fa-solid fa-xmark text-[10px]"></i>
                                                </button>
                                            </div>
                                        @else
                                            <button type="button" wire:click="startEditShortName({{ $org->id }}, '{{ addslashes($org->short_name ?? '') }}')"
                                                title="Click to edit short form (e.g. OCSO, DIT, 1 Sig Bde)" aria-label="Click to edit short form (e.g. OCSO, DIT, 1 Sig Bde)"
                                                class="group inline-flex items-center gap-1 rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-zinc-700 hover:bg-emerald-100 hover:text-emerald-900 transition dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-emerald-950 dark:hover:text-emerald-200">
                                                <span>{{ $org->short_name ?: '+ Short Form' }}</span>
                                                <i class="fa-solid fa-pen text-[8px] text-zinc-400 group-hover:text-emerald-600"></i>
                                            </button>
                                        @endif
                                    </div>
                                    <span class="font-mono text-[10px] text-zinc-400">{{ $org->slug }}</span>
                                </div>
                            </td>

                            <!-- Category -->
                            <td data-label="Category" class="whitespace-nowrap px-4 py-3">
                                @if ($org->category)
                                    <span class="inline-flex items-center rounded-lg bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $org->category->name_en }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 italic">None</span>
                                @endif
                            </td>

                            <!-- Languages (English & Sinhala) -->
                            <td data-label="Languages" class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-1 font-mono text-[10px]">
                                    @php
                                        $locales = $org->translations->pluck('locale')->toArray();
                                    @endphp
                                    <span class="rounded px-1.5 py-0.5 font-bold {{ in_array('en', $locales) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800' }}">EN</span>
                                    <span class="rounded px-1.5 py-0.5 font-bold {{ in_array('si', $locales) ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800' }}">SI</span>
                                </div>
                            </td>

                            <!-- Content Blocks Count -->
                            <td data-label="Content blocks" class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-[11px] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    <i class="fa-solid fa-cubes text-[10px]"></i>
                                    {{ $org->content_blocks_count }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td data-label="Status" class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $org->status->badgeClasses() }}">
                                    {{ $org->status->label() }}
                                </span>
                            </td>

                            <!-- Updated -->
                            <td data-label="Updated" class="whitespace-nowrap px-4 py-3 text-[11px] text-zinc-500">
                                {{ $org->updated_at?->diffForHumans() ?? '—' }}
                            </td>

                            <!-- Actions -->
                            <td data-label="Actions" class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Public Preview / View -->
                                    <a href="{{ route('organization.show', $org->slug) }}" target="_blank"
                                        title="View Public Page" aria-label="View Public Page"
                                        class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.organizations.edit', $org->id) }}"
                                        title="Edit Organization" aria-label="Edit Organization"
                                        class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-100 hover:text-emerald-700 dark:hover:bg-zinc-800 dark:hover:text-emerald-400">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>

                                    <!-- Publish / Unpublish Toggle -->
                                    @can('organizations.publish')
                                        <button type="button" wire:click="togglePublish({{ $org->id }})"
                                            title="{{ $org->status === \App\Enums\OrganizationStatus::Published ? 'Unpublish (set to draft)' : 'Publish immediately' }}" aria-label="{{ $org->status === \App\Enums\OrganizationStatus::Published ? 'Unpublish (set to draft)' : 'Publish immediately' }}"
                                            class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-100 hover:text-amber-600 dark:hover:bg-zinc-800 dark:hover:text-amber-400">
                                            @if ($org->status === \App\Enums\OrganizationStatus::Published)
                                                <i class="fa-solid fa-eye-slash text-xs"></i>
                                            @else
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            @endif
                                        </button>
                                    @endcan

                                    <!-- Delete -->
                                    @can('organizations.delete')
                                        <button type="button" wire:click="deleteOrganization({{ $org->id }})"
                                            wire:confirm="Are you sure you want to delete '{{ $org->titleForLocale('en') }}'? This action cannot be undone."
                                            title="Delete Organization" aria-label="Delete Organization"
                                            class="rounded-lg p-1.5 text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400">
                                            <i class="fa-regular fa-trash-can text-xs"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-xs text-zinc-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fa-solid fa-sitemap text-3xl text-zinc-300 dark:text-zinc-700"></i>
                                    <p class="font-medium text-zinc-700 dark:text-zinc-300">No organizations found</p>
                                    <p class="text-zinc-400">Try modifying your search or click "Import Legacy Content" to load standard units.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($organizations->hasPages())
            <div class="border-t border-zinc-200 bg-zinc-50/50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/30">
                {{ $organizations->links() }}
            </div>
        @endif
    </div>
</div>

