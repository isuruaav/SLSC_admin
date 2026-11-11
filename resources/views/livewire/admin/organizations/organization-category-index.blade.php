<div class="mx-auto max-w-6xl space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.organizations.index') }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-100">Organization Categories</h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Manage categories and drag rows to adjust display order</p>
                </div>
            </div>
        </div>

        <button type="button" wire:click="startCreate"
            class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
            <i class="fa-solid fa-plus"></i>
            <span>Add Category</span>
        </button>
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

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                <tr>
                    <th class="w-10 px-3 py-3 text-center" title="Drag to reorder"><i class="fa-solid fa-arrows-up-down text-zinc-400"></i></th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Category Name (EN)</th>
                    <th class="px-4 py-3">Sinhala</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Units Count</th>
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
                                draggable: 'tr[data-category-id]',
                                ghostClass: 'bg-emerald-50/60',
                                onEnd: () => {
                                    const orderedIds = Array.from(this.$el.querySelectorAll('tr[data-category-id]'))
                                        .map(el => parseInt(el.dataset.categoryId, 10));
                                    $wire.reorderCategories(orderedIds);
                                }
                            });
                        });
                    }
                }"
                class="divide-y divide-zinc-200 dark:divide-zinc-800"
            >
                @forelse ($categories as $cat)
                    <tr wire:key="category-{{ $cat->id }}" data-category-id="{{ $cat->id }}" class="transition hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                        <td class="w-10 px-3 py-3 text-center">
                            <button type="button" data-drag-handle class="cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1" title="Drag to reorder">
                                <i class="fa-solid fa-grip-vertical text-sm"></i>
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono font-bold">{{ $cat->sort_order }}</td>
                        <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100">{{ $cat->name_en }}</td>
                        <td class="px-4 py-3">{{ $cat->name_si ?: '—' }}</td>
                        <td class="px-4 py-3 font-mono text-[11px] text-zinc-400">{{ $cat->slug }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                {{ $cat->organizations_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($cat->is_active)
                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Active</span>
                            @else
                                <span class="rounded bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-zinc-500 dark:bg-zinc-800">Inactive</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <button type="button" wire:click="startEdit({{ $cat->id }})"
                                    class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-100 hover:text-emerald-700 dark:hover:bg-zinc-800 dark:hover:text-emerald-400">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" wire:click="deleteCategory({{ $cat->id }})"
                                    wire:confirm="Are you sure you want to delete category '{{ $cat->name_en }}'?"
                                    class="rounded-lg p-1.5 text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-xs text-zinc-500">
                            No categories created yet. Click "Add Category" or seed categories.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Form -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800">
                    <h2 class="text-base font-bold text-zinc-950 dark:text-zinc-100">
                        {{ $editingCategoryId ? 'Edit Category' : 'Create Category' }}
                    </h2>
                    <button type="button" wire:click="cancel" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Name (English) *</label>
                        <input type="text" wire:model.live.debounce.300ms="name_en"
                            class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        @error('name_en') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Name (Sinhala)</label>
                        <input type="text" wire:model="name_si"
                            class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Slug *</label>
                        <input type="text" wire:model="slug"
                            class="mt-1 w-full font-mono rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        @error('slug') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Sort Order</label>
                            <input type="number" wire:model="sort_order"
                                class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-xs text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="flex items-center gap-2 text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600">
                                <span>Active in filters</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <button type="button" wire:click="cancel"
                            class="rounded-xl border border-zinc-300 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            Cancel
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800">
                            {{ $editingCategoryId ? 'Save Changes' : 'Create Category' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

