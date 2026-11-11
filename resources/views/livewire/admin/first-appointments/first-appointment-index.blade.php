<div class="mx-auto max-w-7xl space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600/10 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <i class="fa-solid fa-award text-lg"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-100">1st Appointments Holders</h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Manage historic first appointment holders of the Sri Lanka Signal Corps</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ url('/first-appoinment-holders') }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i>
                <span>View Public Page</span>
            </a>

            @can('first-appointments.manage')
                <button type="button" wire:click="startCreate"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Appointment Holder</span>
                </button>
            @endcan
        </div>
    </div>

    <!-- Status Banners -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200" role="alert">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-sm dark:border-red-800 dark:bg-red-950/50 dark:text-red-200" role="alert">
            <i class="fa-solid fa-circle-exclamation text-red-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Filters Bar -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="relative flex-1 max-w-md">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-zinc-400"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by appointment, officer name or year..."
                class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 py-2 pl-9 pr-3 text-xs text-zinc-800 placeholder-zinc-400 transition focus:border-emerald-600 focus:bg-white focus:outline-none dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-200">
        </div>

        <div class="flex items-center gap-2">
            <select wire:model.live="statusFilter"
                class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:border-emerald-600 focus:outline-none dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                <option value="all">All Statuses</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                <tr>
                    <th class="w-10 px-3 py-3 text-center" title="Drag to reorder"><i class="fa-solid fa-arrows-up-down text-zinc-400"></i></th>
                    <th class="w-16 px-4 py-3">Photo</th>
                    <th class="px-4 py-3">Appointment Title</th>
                    <th class="px-4 py-3">Officer Rank &amp; Name</th>
                    <th class="px-4 py-3">Year / Period</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody
                x-data="{
                    sortable: null,
                    init() {
                        this.$nextTick(() => {
                            if (!window.Sortable) return;
                            if (this.sortable) this.sortable.destroy();
                            this.sortable = new window.Sortable(this.$el, {
                                animation: 150,
                                handle: '[data-drag-handle]',
                                draggable: 'tr[data-holder-id]',
                                ghostClass: 'bg-emerald-50/60',
                                onEnd: () => {
                                    const orderedIds = Array.from(this.$el.querySelectorAll('tr[data-holder-id]'))
                                        .map(el => parseInt(el.dataset.holderId, 10));
                                    $wire.reorderHolders(orderedIds);
                                }
                            });
                        });
                    }
                }"
                class="divide-y divide-zinc-200 dark:divide-zinc-800"
            >
                @forelse ($holders as $holder)
                    <tr wire:key="holder-{{ $holder->id }}" data-holder-id="{{ $holder->id }}" class="transition hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                        <td class="w-10 px-3 py-3 text-center">
                            @can('first-appointments.manage')
                                <button type="button" data-drag-handle class="cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1" title="Drag to reorder">
                                    <i class="fa-solid fa-grip-vertical text-sm"></i>
                                </button>
                            @else
                                <span class="text-zinc-300">—</span>
                            @endcan
                        </td>
                        <td class="px-4 py-3">
                            @if ($holder->imageUrl())
                                <img src="{{ $holder->imageUrl() }}" alt="{{ $holder->name_en }}"
                                    class="h-12 w-10 rounded-lg object-cover border border-zinc-200 shadow-xs dark:border-zinc-700">
                            @else
                                <div class="flex h-12 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700">
                                    <i class="fa-solid fa-user text-xs"></i>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $holder->appointment_en }}</div>
                            @if ($holder->appointment_si)
                                <div class="text-[11px] text-zinc-400">{{ $holder->appointment_si }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ $holder->name_en }}</div>
                            @if ($holder->name_si)
                                <div class="text-[11px] text-zinc-400">{{ $holder->name_si }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($holder->period_en)
                                <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    <i class="fa-regular fa-calendar text-[10px] text-zinc-400"></i>
                                    {{ $holder->period_en }}
                                </span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono font-bold">{{ $holder->sort_order }}</td>
                        <td class="px-4 py-3">
                            @can('first-appointments.manage')
                                <button type="button" wire:click="toggleActive({{ $holder->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-semibold transition {{ $holder->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $holder->is_active ? 'bg-emerald-600 dark:bg-emerald-400' : 'bg-zinc-400' }}"></span>
                                    <span>{{ $holder->is_active ? 'Active' : 'Inactive' }}</span>
                                </button>
                            @else
                                <span class="rounded px-2 py-0.5 text-[10px] font-semibold {{ $holder->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-500' }}">
                                    {{ $holder->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            @endcan
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @can('first-appointments.manage')
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" wire:click="edit({{ $holder->id }})"
                                        class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-100 hover:text-emerald-700 dark:hover:bg-zinc-800 dark:hover:text-emerald-400" title="Edit">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <button type="button" wire:click="delete({{ $holder->id }})"
                                        wire:confirm="Are you sure you want to delete '{{ $holder->appointment_en }}'?"
                                        class="rounded-lg p-1.5 text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400" title="Delete">
                                        <i class="fa-regular fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-xs text-zinc-500">
                            No appointment holders found. Click "Add Appointment Holder" to add one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($holders->hasPages())
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
                {{ $holders->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form (Create / Edit) -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/60 p-4 backdrop-blur-sm overflow-y-auto">
            <div class="w-full max-w-2xl rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 my-8">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div>
                        <h2 class="text-base font-bold text-zinc-950 dark:text-zinc-100">
                            {{ $editingId ? 'Edit Appointment Holder' : 'Add New Appointment Holder' }}
                        </h2>
                        <p class="text-xs text-zinc-500">Multilingual details (English &amp; Sinhala)</p>
                    </div>
                    <button type="button" wire:click="cancel" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <form wire:submit="save" class="mt-4 space-y-4">
                <div class="grid gap-3 sm:grid-cols-2"><label class="text-sm font-semibold">Start date (optional)<input type="date" wire:model="startDate" class="mt-1 block w-full rounded-xl border border-zinc-300 p-3"></label><label class="text-sm font-semibold">End date (optional)<input type="date" wire:model="endDate" class="mt-1 block w-full rounded-xl border border-zinc-300 p-3"></label></div><p class="text-xs text-zinc-500">Structured dates support year filters. Existing period text is kept for records without exact dates.</p>
                    <!-- Dual language: Appointment Title -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Appointment Title (English) *</label>
                            <input type="text" wire:model="appointmentEn" placeholder="e.g. 1st Regimental Commander"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                            @error('appointmentEn') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Appointment Title (Sinhala)</label>
                            <input type="text" wire:model="appointmentSi" placeholder="e.g. 1 වන රෙජිමේන්තු අණදෙන නිලධාරී"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                    </div>

                    <!-- Dual language: Officer Name -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Officer Rank &amp; Name (English) *</label>
                            <input type="text" wire:model="nameEn" placeholder="e.g. Col CJ Abeyrathne"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                            @error('nameEn') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Officer Rank &amp; Name (Sinhala)</label>
                            <input type="text" wire:model="nameSi" placeholder="e.g. කර්නල් සීජේ අබේරත්න"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                    </div>

                    <!-- Dual language: Period / Year -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Year / Period (English)</label>
                            <input type="text" wire:model="periodEn" placeholder="e.g. 1988"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Year / Period (Sinhala)</label>
                            <input type="text" wire:model="periodSi" placeholder="e.g. 1988"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                    </div>

                    <!-- Photo Upload -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Officer Portrait Photo</label>
                        <div class="mt-2 flex items-center gap-4">
                            @if ($newImage)
                                <div class="relative">
                                    <img src="{{ $newImage->temporaryUrl() }}" class="h-20 w-16 rounded-lg object-cover border border-emerald-500 shadow-sm">
                                    <span class="absolute -top-2 -right-2 rounded-full bg-emerald-600 p-1 text-[10px] text-white">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                </div>
                            @elseif ($currentImageUrl)
                                <div class="relative">
                                    <img src="{{ $currentImageUrl }}" class="h-20 w-16 rounded-lg object-cover border border-zinc-300 dark:border-zinc-700">
                                </div>
                            @endif

                            <div class="flex-1">
                                <input type="file" wire:model="newImage" accept="image/*"
                                    class="w-full text-xs text-zinc-500 file:mr-3 file:rounded-xl file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300">
                                <p class="mt-1 text-[11px] text-zinc-400">JPG, PNG or WebP. Portrait aspect ratio recommended.</p>
                                @error('newImage') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Order and Active toggle -->
                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Display Order</label>
                            <input type="number" wire:model="sortOrder"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="flex items-center gap-2 text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                <input type="checkbox" wire:model="isActive" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600">
                                <span>Active (Visible on public page)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <button type="button" wire:click="cancel"
                            class="rounded-xl border border-zinc-300 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            Cancel
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800">
                            {{ $editingId ? 'Save Changes' : 'Add Holder' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

