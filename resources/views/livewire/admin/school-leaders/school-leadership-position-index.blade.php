<div class="space-y-6">
    @php
        $canUpdate = \Illuminate\Support\Facades\Gate::allows(
            'school-leaders.update'
        );
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                School Leadership
            </p>

            <h1 class="mt-1 text-2xl font-black text-zinc-950">
                Appointment Types
            </h1>

            <p class="mt-1 max-w-3xl text-sm text-zinc-500">
                Create and manage dynamic leadership appointment categories, rank groups,
                homepage visibility and display order.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('admin.school-leaders.index') }}"
                class="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-bold text-zinc-700 hover:bg-zinc-50"
            >
                ← School Leadership
            </a>

            @if ($canUpdate)
                <button
                    type="button"
                    wire:click="startCreate"
                    class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800"
                >
                    + New Appointment Type
                </button>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @error('delete')
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="min-w-0">
        <section class="min-w-0 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-zinc-900">
                            Appointment type directory
                        </h2>

                        <p class="mt-1 text-xs text-zinc-500">
                            Search, filter, sort and manage appointment types.
                        </p>
                    </div>

                    <span class="rounded-full bg-zinc-200 px-3 py-1 text-xs font-bold text-zinc-700">
                        {{ $positions->total() }} types
                    </span>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-[minmax(220px,1fr)_170px_170px_190px_100px]">
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>

                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search appointment, Sinhala name or key..."
                            class="w-full rounded-xl border border-zinc-300 bg-white py-2.5 pl-9 pr-3 text-sm text-zinc-800"
                        >
                    </div>

                    <select
                        wire:model.live="rankGroupFilter"
                        class="rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800"
                    >
                        <option value="all">All rank groups</option>

                        @foreach ($rankGroups as $group)
                            <option value="{{ $group->value }}">
                                {{ $group->label() }}
                            </option>
                        @endforeach
                    </select>

                    <select
                        wire:model.live="statusFilter"
                        class="rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800"
                    >
                        <option value="all">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <select
                        wire:model.live="homepageFilter"
                        class="rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800"
                    >
                        <option value="all">All homepage settings</option>
                        <option value="yes">Homepage enabled</option>
                        <option value="no">Homepage disabled</option>
                    </select>

                    <select
                        wire:model.live="perPage"
                        class="rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800"
                    >
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                    </select>
                </div>

                @if (
                    trim($search) !== ''
                    || $rankGroupFilter !== 'all'
                    || $statusFilter !== 'all'
                    || $homepageFilter !== 'all'
                )
                    <div class="mt-3 flex justify-end">
                        <button
                            type="button"
                            wire:click="resetTableFilters"
                            class="text-xs font-bold text-emerald-700 hover:text-emerald-900"
                        >
                            Clear filters
                        </button>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[950px] w-full text-left">
                    <thead class="border-b border-zinc-200 bg-white">
                        <tr class="text-[11px] font-black uppercase tracking-wider text-zinc-500">
                            <th class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="sortBy('appointment')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-900"
                                >
                                    Appointment Type
                                    @if ($sortField === 'appointment')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>

                            <th class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="sortBy('rank_group')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-900"
                                >
                                    Rank Group
                                    @if ($sortField === 'rank_group')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>

                            <th class="px-4 py-3 text-center">
                                <button
                                    type="button"
                                    wire:click="sortBy('leaders')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-900"
                                >
                                    Leaders
                                    @if ($sortField === 'leaders')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>

                            <th class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="sortBy('homepage')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-900"
                                >
                                    Homepage
                                    @if ($sortField === 'homepage')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>

                            <th class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="sortBy('status')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-900"
                                >
                                    Status
                                    @if ($sortField === 'status')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>

                            <th class="px-4 py-3 text-center">
                                <button
                                    type="button"
                                    wire:click="sortBy('sort_order')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-900"
                                >
                                    Sort
                                    @if ($sortField === 'sort_order')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>

                            @if ($canUpdate)
                                <th class="px-4 py-3 text-right">
                                    Actions
                                </th>
                            @endif
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($positions as $position)
                            <tr
                                wire:key="position-row-{{ $position->id }}"
                                @class([
                                    'transition hover:bg-zinc-50',
                                    'bg-blue-50/60' => $editingId === $position->id,
                                ])
                            >
                                <td class="px-4 py-4 align-top">
                                    <div class="min-w-[230px]">
                                        <p class="font-black text-zinc-950">
                                            {{ $position->name_en }}
                                        </p>

                                        <p class="mt-1 text-sm text-zinc-500" lang="si">
                                            {{ $position->name_si }}
                                        </p>

                                        <p class="mt-1 font-mono text-[10px] text-zinc-400">
                                            {{ $position->key }}
                                        </p>
                                    </div>
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-lg bg-zinc-100 px-2.5 py-1.5 text-xs font-bold text-zinc-700">
                                        {{ $position->rank_group->label() }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center align-top">
                                    <span class="inline-flex min-w-8 justify-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-black text-zinc-700">
                                        {{ $position->leaders_count }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 align-top">
                                    @if ($position->show_on_home)
                                        <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-blue-700">
                                            Homepage
                                        </span>
                                    @else
                                        <span class="text-xs font-semibold text-zinc-400">
                                            Disabled
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-top">
                                    @if ($position->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-700">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-zinc-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-zinc-600">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 text-center align-top">
                                    <span class="text-sm font-black text-zinc-700">
                                        {{ $position->sort_order }}
                                    </span>
                                </td>

                                @if ($canUpdate)
                                    <td class="px-4 py-4 text-right align-top">
                                        <div class="flex justify-end gap-2">
                                            <x-admin.icon-action label="Edit" icon="pencil-square" tone="info"
                                                type="button"
                                                wire:click="edit({{ $position->id }})"
                                            />
                                            <x-admin.icon-action label="Delete appointment type" icon="trash" tone="danger"
                                                type="button"
                                                wire:click="delete({{ $position->id }})"
                                                wire:confirm="Delete the appointment type {{ $position->name_en }}? This cannot be undone."
                                                wire:loading.attr="disabled"
                                                wire:target="delete({{ $position->id }})"
                                            />
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="{{ $canUpdate ? 7 : 6 }}"
                                    class="px-6 py-14 text-center"
                                >
                                    <div class="mx-auto max-w-md">
                                        <p class="font-black text-zinc-700">
                                            No appointment types found.
                                        </p>

                                        <p class="mt-1 text-sm text-zinc-500">
                                            Change the search or filters, or create a new appointment type.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-xs font-semibold text-zinc-500">
                        Showing
                        <span class="font-black text-zinc-700">
                            {{ $positions->firstItem() ?? 0 }}
                        </span>
                        to
                        <span class="font-black text-zinc-700">
                            {{ $positions->lastItem() ?? 0 }}
                        </span>
                        of
                        <span class="font-black text-zinc-700">
                            {{ $positions->total() }}
                        </span>
                        appointment types
                    </p>

                    <div>
                        {{ $positions->onEachSide(1)->links() }}
                    </div>
                </div>
            </div>
        </section>

        @if ($canUpdate)
            <flux:modal name="school-leadership-position-editor" wire:model="showEditor" @close="closeEditor"
                class="w-[calc(100%-2rem)] min-w-0 max-w-2xl max-h-[calc(100dvh-2rem)] overflow-hidden rounded-2xl p-0!">
                <form
                    wire:submit="save"
                    x-init="$el.closest('dialog').setAttribute('aria-labelledby', 'school-leadership-position-editor-title')"
                    class="flex max-h-[calc(100dvh-2rem)] flex-col overflow-hidden bg-white"
                >
                    <div class="shrink-0 border-b border-zinc-200 bg-zinc-50 px-6 py-4 pr-16">
                        <h2 id="school-leadership-position-editor-title" class="font-bold text-zinc-900">
                            {{ $editingId === null
                                ? 'New appointment type'
                                : 'Edit appointment type' }}
                        </h2>

                        <p class="mt-1 text-xs text-zinc-500">
                            Configure the appointment name, allowed rank group,
                            homepage visibility and display order.
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain p-6">
                        @if ($errors->any())
                            <div role="alert" tabindex="-1" x-init="$el.focus()"
                                class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                <p class="font-bold">Please correct the following errors:</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div>
                            <label
                                for="position-name-en"
                                class="mb-1.5 block text-sm font-bold text-zinc-800"
                            >
                                English name
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="position-name-en"
                                wire:model.live.debounce.300ms="nameEn"
                                type="text"
                                maxlength="180"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm"
                                placeholder="e.g. Training Officer"
                            >

                            @error('nameEn')
                                <p class="mt-1 text-xs font-semibold text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="position-system-key" class="mb-1.5 block text-sm font-bold text-zinc-800">
                                System key <span class="text-red-600">*</span>
                            </label>
                            <input id="position-system-key" wire:model.live.debounce.300ms="systemKey"
                                type="text" maxlength="50" autocapitalize="none" spellcheck="false"
                                aria-describedby="position-system-key-help{{ $errors->has('systemKey') ? ' position-system-key-error' : '' }}"
                                @if ($errors->has('systemKey')) aria-invalid="true" @endif
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 font-mono text-sm"
                                placeholder="e.g. training_officer">
                            <p id="position-system-key-help" class="mt-1.5 text-xs leading-relaxed text-zinc-500">
                                Generated from the English name for new types. You can edit it using lowercase letters, numbers and underscores (maximum 50 characters).
                                @if ($editingId !== null)
                                    Built-in appointment keys also control their homepage placement and older appointment links.
                                @endif
                            </p>
                            @error('systemKey')
                                <p id="position-system-key-error" class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="position-name-si"
                                class="mb-1.5 block text-sm font-bold text-zinc-800"
                            >
                                Sinhala name
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="position-name-si"
                                wire:model="nameSi"
                                type="text"
                                maxlength="220"
                                lang="si"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm"
                                placeholder="e.g. පුහුණු නිලධාරී"
                            >

                            @error('nameSi')
                                <p class="mt-1 text-xs font-semibold text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="position-rank-group"
                                class="mb-1.5 block text-sm font-bold text-zinc-800"
                            >
                                Rank group
                                <span class="text-red-600">*</span>
                            </label>

                            <select
                                id="position-rank-group"
                                wire:model="rankGroup"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm"
                            >
                                <option value="">
                                    Select rank group
                                </option>

                                @foreach ($rankGroups as $group)
                                    <option value="{{ $group->value }}">
                                        {{ $group->label() }}
                                    </option>
                                @endforeach
                            </select>

                            @error('rankGroup')
                                <p class="mt-1 text-xs font-semibold text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="position-sort-order"
                                class="mb-1.5 block text-sm font-bold text-zinc-800"
                            >
                                Sort order
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="position-sort-order"
                                wire:model="sortOrder"
                                type="number"
                                min="1"
                                max="9999"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm"
                            >

                            <p class="mt-1 text-[11px] text-zinc-500">
                                Lower numbers appear first.
                            </p>

                            @error('sortOrder')
                                <p class="mt-1 text-xs font-semibold text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-blue-200 bg-blue-50/50 p-4">
                            <input
                                wire:model="showOnHome"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-zinc-300 text-blue-700"
                            >

                            <span>
                                <span class="block text-sm font-black text-zinc-900">
                                    Show present holder on homepage
                                </span>

                                <span class="mt-1 block text-xs leading-5 text-zinc-500">
                                    If enabled, the present holder of this appointment
                                    can be displayed on the homepage.
                                </span>
                            </span>
                        </label>

                        @error('showOnHome')
                            <p class="text-xs font-semibold text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                            <input
                                wire:model="isActive"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-zinc-300 text-emerald-700"
                            >

                            <span>
                                <span class="block text-sm font-black text-zinc-900">
                                    Active appointment type
                                </span>

                                <span class="mt-1 block text-xs leading-5 text-zinc-500">
                                    Active appointment types are available when
                                    creating new leadership records.
                                </span>
                            </span>
                        </label>

                        @error('isActive')
                            <p class="text-xs font-semibold text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4">
                        <button type="button" wire:click="closeEditor" wire:loading.attr="disabled" wire:target="save"
                            class="rounded-xl border border-zinc-300 bg-white px-5 py-3 text-sm font-bold text-zinc-700 hover:bg-zinc-100 disabled:opacity-60">Cancel</button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white hover:bg-emerald-800 disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $editingId === null
                                    ? 'Create Appointment Type'
                                    : 'Update Appointment Type' }}
                            </span>

                            <span wire:loading wire:target="save">
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </flux:modal>
        @endif
    </div>
</div>
