<div class="space-y-6">

    @php

        $canUpdate = \Illuminate\Support\Facades\Gate::allows(

            'school-leaders.update'

        );

        $selectedAppointment = $positions->firstWhere(

            'key',

            $roleKey

        );

    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">

        <div>

            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">

                Site Management

            </p>

            <h1 class="mt-1 text-2xl font-black text-zinc-950">

                School Leadership

            </h1>

            <p class="mt-1 max-w-3xl text-sm text-zinc-500">

                Manage present and past leadership appointments from one place.

                Appointment types are managed separately and can be expanded at any time.

            </p>

        </div>

        @if ($canUpdate)

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.school-leadership-profiles.index') }}"
                    class="rounded-xl border border-blue-300 bg-blue-50 px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-100">Leadership Profiles</a>

                <a

                    href="{{ route('admin.school-leadership-positions.index') }}"

                    class="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-bold text-zinc-700 hover:bg-zinc-50"

                >

                    Manage Appointment Types

                </a>

                <button

                    type="button"

                    wire:click="startCreate"

                    class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800"

                >

                    + New Appointment

                </button>

            </div>

        @endif

    </div>

    @if (session('status'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">

            {{ session('status') }}

        </div>

    @endif

    <div class="min-w-0">

        <section class="min-w-0 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-zinc-900">
                            Leadership appointment history
                        </h2>

                        <p class="mt-1 text-xs text-zinc-500">
                            Search, filter and manage present and past leadership records.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            wire:loading
                            wire:target="search,statusFilter,positionFilter,perPage"
                            class="text-xs font-semibold text-emerald-700"
                        >
                            Updating...
                        </span>

                        <span class="rounded-full bg-zinc-200 px-3 py-1 text-xs font-bold text-zinc-700">
                            {{ $schoolLeaders->total() }}
                            {{ $schoolLeaders->total() === 1 ? 'record' : 'records' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="border-b border-zinc-200 p-4">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_190px_150px_110px_auto]">
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400"
                            aria-hidden="true"
                        >
                            <svg
                                class="size-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </span>

                        <input
                            wire:model.live.debounce.300ms="search"
                            type="search"
                            placeholder="Search appointment, rank or name..."
                            class="w-full rounded-xl border border-zinc-300 bg-white py-2.5 pl-9 pr-3 text-sm text-zinc-800 placeholder:text-zinc-400"
                        >
                    </div>

                    <select
                        wire:model.live="positionFilter"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800"
                    >
                        <option value="all">
                            All appointments
                        </option>

                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}">
                                {{ $position->name_en }}
                            </option>
                        @endforeach
                    </select>

                    <select
                        wire:model.live="statusFilter"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800"
                    >
                        <option value="all">All status</option>
                        <option value="present">Present</option>
                        <option value="past">Past</option>
                    </select>

                    <select
                        wire:model.live="perPage"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800"
                        aria-label="Rows per page"
                    >
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                    </select>

                    <button
                        type="button"
                        wire:click="resetTableFilters"
                        class="rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm font-bold text-zinc-700 hover:bg-zinc-50"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-left">
                    <thead class="bg-zinc-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wide text-zinc-600">
                                <button
                                    type="button"
                                    wire:click="sortBy('appointment')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-950"
                                >
                                    Appointment

                                    @if ($sortField === 'appointment')
                                        <span aria-hidden="true">
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    @endif
                                </button>
                            </th>

                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wide text-zinc-600">
                                <button
                                    type="button"
                                    wire:click="sortBy('name')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-950"
                                >
                                    Officer / Rank

                                    @if ($sortField === 'name')
                                        <span aria-hidden="true">
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    @endif
                                </button>
                            </th>

                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wide text-zinc-600">
                                <button
                                    type="button"
                                    wire:click="sortBy('period')"
                                    class="inline-flex items-center gap-1.5 hover:text-zinc-950"
                                >
                                    Period

                                    @if ($sortField === 'period')
                                        <span aria-hidden="true">
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    @endif
                                </button>
                            </th>

                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wide text-zinc-600">
                                Status
                            </th>

                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wide text-zinc-600">
                                Homepage
                            </th>

                            @if ($canUpdate)
                                <th class="px-4 py-3 text-right text-xs font-black uppercase tracking-wide text-zinc-600">
                                    Action
                                </th>
                            @endif
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @forelse ($schoolLeaders as $leader)
                            <tr
                                wire:key="school-leader-row-{{ $leader->id }}"
                                @class([
                                    'transition hover:bg-zinc-50',
                                    'bg-blue-50/50' => $editingId === $leader->id,
                                ])
                            >
                                <td class="min-w-52 px-4 py-4 align-top">
                                    <p class="font-black text-zinc-950">
                                        {{ $leader->position?->name_en ?? $leader->title_en }}
                                    </p>

                                    @if ($leader->position?->name_si)
                                        <p class="mt-1 text-xs text-zinc-500" lang="si">
                                            {{ $leader->position->name_si }}
                                        </p>
                                    @endif

                                    @if (! $leader->position?->is_active)
                                        <span class="mt-2 inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[10px] font-black uppercase text-zinc-600">
                                            Inactive type
                                        </span>
                                    @endif
                                </td>

                                <td class="min-w-64 px-4 py-4 align-top">
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700"
                                            aria-hidden="true"
                                        >
                                            <svg
                                                class="size-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <path d="M12 3 4 7v5c0 5 3.4 8.7 8 9 4.6-.3 8-4 8-9V7l-8-4Z" />
                                                <path d="M9 12h6M12 9v6" />
                                            </svg>
                                        </span>

                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-blue-700">
                                                {{ $leader->rankLabel() ?: 'Rank not added' }}
                                            </p>

                                            <p class="mt-1 font-bold text-zinc-900">
                                                {{ $leader->name_en ?: 'English name not added' }}
                                            </p>

                                            @if ($leader->name_si)
                                                <p class="mt-0.5 text-xs text-zinc-500" lang="si">
                                                    {{ $leader->name_si }}
                                                </p>
                                            @endif

                                            @if ($leader->image)
                                                <p class="mt-1 truncate text-[10px] font-semibold text-zinc-400">
                                                    Image: {{ $leader->image->title ?: $leader->image->original_name }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 align-top text-xs font-semibold text-zinc-600">
                                    <div>
                                        {{ $leader->start_date?->format('d M Y') ?? 'Not added' }}
                                    </div>

                                    <div class="my-1 text-zinc-300">
                                        ↓
                                    </div>

                                    @if ($leader->end_date)
                                        <div>
                                            {{ $leader->end_date->format('d M Y') }}
                                        </div>
                                    @else
                                        <div class="font-black text-emerald-700">
                                            Up to Date
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-top">
                                    @if ($leader->isCurrentAppointment())
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-800">
                                            Present
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-zinc-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-zinc-700">
                                            Past
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-top">
                                    @if ($leader->position?->show_on_home)
                                        <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-blue-700">
                                            Enabled
                                        </span>
                                    @else
                                        <span class="text-sm font-bold text-zinc-300">
                                            —
                                        </span>
                                    @endif
                                </td>

                                @if ($canUpdate)
                                    <td class="px-4 py-4 text-right align-top">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-admin.icon-action label="Edit" icon="pencil-square" tone="primary"
                                                wire:click="edit({{ $leader->id }})"
                                            />
                                            <x-admin.icon-action label="Delete" icon="trash" tone="danger"
                                                wire:click="delete({{ $leader->id }})"
                                                wire:confirm="Delete the appointment for {{ $leader->name_en }}? This will also delete its saved profile. This action cannot be undone."
                                                wire:loading.attr="disabled"
                                                wire:target="delete({{ $leader->id }})"
                                            />
                                            @if ($leader->supportsProfile())
                                                <x-admin.icon-action :label="$leader->profile_enabled ? 'Edit Profile' : 'Create Profile'" icon="document-text" tone="info"
                                                    href="{{ route('admin.school-leadership-profiles.index', ['leader' => $leader->id]) }}"
                                                />
                                                @if ($leader->hasPublicProfile())
                                                    <x-admin.icon-action label="View Profile" icon="eye"
                                                        href="{{ $leader->profileUrlForLocale('en') }}" target="_blank" rel="noopener"
                                                    />
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="{{ $canUpdate ? 6 : 5 }}"
                                    class="px-6 py-14 text-center"
                                >
                                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400">
                                        <svg
                                            class="size-6"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            aria-hidden="true"
                                        >
                                            <circle cx="11" cy="11" r="7" />
                                            <path d="m20 20-3.5-3.5" />
                                        </svg>
                                    </span>

                                    <p class="mt-3 font-bold text-zinc-700">
                                        No leadership records found.
                                    </p>

                                    <p class="mt-1 text-sm text-zinc-500">
                                        Change the search or filters, or create a new appointment.
                                    </p>
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
                            {{ $schoolLeaders->firstItem() ?? 0 }}
                        </span>
                        to
                        <span class="font-black text-zinc-700">
                            {{ $schoolLeaders->lastItem() ?? 0 }}
                        </span>
                        of
                        <span class="font-black text-zinc-700">
                            {{ $schoolLeaders->total() }}
                        </span>
                        records
                    </p>

                    <div>
                        {{ $schoolLeaders->onEachSide(1)->links() }}
                    </div>
                </div>
            </div>
        </section>

        @if ($canUpdate)

            <flux:modal name="school-leader-editor" wire:model="showEditor" @close="closeEditor"
                class="w-[calc(100%-2rem)] min-w-0 max-w-3xl max-h-[calc(100dvh-2rem)] overflow-hidden rounded-2xl p-0!">

                <form

                    wire:submit="save"

                    x-init="$el.closest('dialog').setAttribute('aria-labelledby', 'school-leader-editor-title')"

                    class="flex max-h-[calc(100dvh-2rem)] flex-col overflow-hidden bg-white"

                >

                    <div class="shrink-0 border-b border-zinc-200 bg-zinc-50 px-6 py-4 pr-16">

                        <h2 id="school-leader-editor-title" class="font-bold text-zinc-900">

                            {{ $editingId === null

                                ? 'New leadership appointment'

                                : 'Edit leadership appointment' }}

                        </h2>

                        <p class="mt-1 text-xs text-zinc-500">

                            Select the appointment type first. The rank list will automatically match its rank group.

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

                                for="school-leader-role"

                                class="mb-1.5 block text-sm font-bold text-zinc-800"

                            >

                                Appointment

                                <span class="text-red-600">*</span>

                            </label>

                            <select

                                id="school-leader-role"

                                wire:model.live="roleKey"

                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800"

                            >

                                <option value="">

                                    Select appointment

                                </option>

                                @foreach ($appointmentOptions as $appointment)

                                    <option value="{{ $appointment->key }}">

                                        {{ $appointment->name_en }}

                                        —

                                        {{ $appointment->name_si }}

                                        @if (! $appointment->is_active)

                                            (Inactive)

                                        @endif

                                    </option>

                                @endforeach

                            </select>

                            @error('roleKey')

                                <p class="mt-1 text-xs font-semibold text-red-600">

                                    {{ $message }}

                                </p>

                            @enderror

                        </div>

                        <div>

                            <label

                                for="school-leader-rank"

                                class="mb-1.5 block text-sm font-bold text-zinc-800"

                            >

                                Rank

                                <span class="text-red-600">*</span>

                            </label>

                            <select

                                id="school-leader-rank"

                                wire:model="rank"

                                @disabled($selectedAppointment === null)

                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-100"

                            >

                                <option value="">

                                    {{ $selectedAppointment === null

                                        ? 'Select appointment first'

                                        : 'Select rank' }}

                                </option>

                                @if ($selectedAppointment?->requiresCommissionedRank())

                                    <optgroup label="Commissioned Officers">

                                        @foreach ($rankOptions as $rankOption)

                                            @if ($rankOption->isCommissioned())

                                                <option value="{{ $rankOption->value }}">

                                                    {{ method_exists($rankOption, 'adminLabel')

                                                        ? $rankOption->adminLabel()

                                                        : $rankOption->label() }}

                                                </option>

                                            @endif

                                        @endforeach

                                    </optgroup>

                                @elseif ($selectedAppointment !== null)

                                    <optgroup label="Other Ranks">

                                        @foreach ($rankOptions as $rankOption)

                                            @if (! $rankOption->isCommissioned())

                                                <option value="{{ $rankOption->value }}">

                                                    {{ method_exists($rankOption, 'adminLabel')

                                                        ? $rankOption->adminLabel()

                                                        : $rankOption->label() }}

                                                </option>

                                            @endif

                                        @endforeach

                                    </optgroup>

                                @endif

                            </select>

                            @error('rank')

                                <p class="mt-1 text-xs font-semibold text-red-600">

                                    {{ $message }}

                                </p>

                            @enderror

                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">

                            <div>

                                <label

                                    for="school-leader-name-en"

                                    class="mb-1 block text-xs font-bold text-zinc-700"

                                >

                                    English name

                                    <span class="text-red-600">*</span>

                                </label>

                                <input

                                    id="school-leader-name-en"

                                    wire:model="nameEn"

                                    type="text"

                                    maxlength="180"

                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm"

                                >

                                @error('nameEn')

                                    <p class="mt-1 text-xs text-red-600">

                                        {{ $message }}

                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label

                                    for="school-leader-name-si"

                                    class="mb-1 block text-xs font-bold text-zinc-700"

                                >

                                    සිංහල නම

                                    <span class="text-red-600">*</span>

                                </label>

                                <input

                                    id="school-leader-name-si"

                                    wire:model="nameSi"

                                    type="text"

                                    maxlength="220"

                                    lang="si"

                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm"

                                >

                                @error('nameSi')

                                    <p class="mt-1 text-xs text-red-600">

                                        {{ $message }}

                                    </p>

                                @enderror

                            </div>

                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">

                            <div>

                                <label

                                    for="school-leader-start-date"

                                    class="mb-1 block text-xs font-bold text-zinc-700"

                                >

                                    Start date

                                    <span class="text-red-600">*</span>

                                </label>

                                <input

                                    id="school-leader-start-date"

                                    wire:model="startDate"

                                    type="date"

                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm"

                                >

                                @error('startDate')

                                    <p class="mt-1 text-xs text-red-600">

                                        {{ $message }}

                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label

                                    for="school-leader-end-date"

                                    class="mb-1 block text-xs font-bold text-zinc-700"

                                >

                                    End date

                                    @if (! $isCurrent)

                                        <span class="text-red-600">*</span>

                                    @endif

                                </label>

                                <input

                                    id="school-leader-end-date"

                                    wire:model="endDate"

                                    type="date"

                                    @disabled($isCurrent)

                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-400"

                                >

                                @if ($isCurrent)

                                    <p class="mt-1 text-[11px] font-semibold text-emerald-700">

                                        Present appointment — displayed as Up to Date.

                                    </p>

                                @else

                                    <p class="mt-1 text-[11px] text-zinc-500">

                                        Required for a past appointment.

                                    </p>

                                @endif

                                @error('endDate')

                                    <p class="mt-1 text-xs text-red-600">

                                        {{ $message }}

                                    </p>

                                @enderror

                            </div>

                        </div>

                        <label

                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4"

                        >

                            <input

                                wire:model.live="isCurrent"

                                type="checkbox"

                                class="mt-0.5 size-4 rounded border-zinc-300 text-emerald-700"

                            >

                            <span>

                                <span class="block text-sm font-black text-zinc-900">

                                    Present appointment

                                </span>

                                <span class="mt-1 block text-xs leading-5 text-zinc-500">

                                    Enable this only for the current holder.

                                    Only one present holder is allowed for each appointment type.

                                    Homepage display is controlled by the Appointment Type setting.

                                </span>

                            </span>

                        </label>

                        @error('isCurrent')

                            <p class="text-xs font-semibold text-red-600">

                                {{ $message }}

                            </p>

                        @enderror

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="secondaryTenureEn" class="block text-xs font-bold uppercase tracking-wider text-zinc-700">
                                    Additional Tenure Note (English - Optional)
                                </label>
                                <textarea
                                    id="secondaryTenureEn"
                                    wire:model="secondaryTenureEn"
                                    rows="2"
                                    placeholder="Brig KA Gnanaweera&#10;1992 - 10 - 01 to 1993 - 06 - 27"
                                    class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                                ></textarea>
                                @error('secondaryTenureEn')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="secondaryTenureSi" class="block text-xs font-bold uppercase tracking-wider text-zinc-700">
                                    Additional Tenure Note (Sinhala - Optional)
                                </label>
                                <textarea
                                    id="secondaryTenureSi"
                                    wire:model="secondaryTenureSi"
                                    rows="2"
                                    placeholder="බ්‍රිගේඩියර් කේඒ ඥානවීර&#10;1992 - 10 - 01 සිට 1993 - 06 - 27 දක්වා"
                                    class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                                ></textarea>
                                @error('secondaryTenureSi')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>

                            <p class="text-sm font-black text-zinc-900">

                                Profile image

                            </p>

                            <p class="mt-1 text-xs text-zinc-500">

                                Select a public Media Library image or upload a new one.

                            </p>

                        </div>

                        @can('media.upload')

                            <div class="rounded-xl border-2 border-dashed border-emerald-300 bg-emerald-50/50 p-4">

                                <label

                                    for="school-leader-image"

                                    class="block cursor-pointer text-center"

                                >

                                    @if ($newImage instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)

                                        <img

                                            src="{{ $newImage->temporaryUrl() }}"

                                            alt="New leadership image preview"

                                            class="mx-auto h-44 w-full rounded-lg object-cover"

                                        >

                                        <span class="mt-3 block text-sm font-bold text-emerald-800">

                                            Change selected image

                                        </span>

                                    @else

                                        <span

                                            class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"

                                        >

                                            <svg

                                                class="size-6"

                                                viewBox="0 0 24 24"

                                                fill="none"

                                                stroke="currentColor"

                                                stroke-width="1.8"

                                                aria-hidden="true"

                                            >

                                                <path d="M12 16V4" />

                                                <path d="m7 9 5-5 5 5" />

                                                <path d="M5 20h14a2 2 0 0 0 2-2v-3M3 15v3a2 2 0 0 0 2 2" />

                                            </svg>

                                        </span>

                                        <span class="mt-3 block text-sm font-black text-zinc-900">

                                            Upload New Image

                                        </span>

                                        <span class="mt-1 block text-xs text-zinc-500">

                                            JPG, PNG or WebP — maximum 20 MB

                                        </span>

                                    @endif

                                    <input

                                        id="school-leader-image"

                                        wire:model="newImage"

                                        type="file"

                                        accept="image/jpeg,image/png,image/webp"

                                        class="sr-only"

                                    >

                                </label>

                                @if ($newImage)

                                    <x-admin.icon-action label="Remove New Image" icon="x-mark" tone="danger"
                                        type="button"
                                        wire:click="clearNewImage"
                                    />

                                @endif

                                @error('newImage')

                                    <p class="mt-2 text-xs font-semibold text-red-600">

                                        {{ $message }}

                                    </p>

                                @enderror

                            </div>

                        @endcan

                        <div>

                            <label

                                for="school-leader-library-image"

                                class="mb-1.5 block text-sm font-bold text-zinc-800"

                            >

                                Media Library Image

                            </label>

                            <select

                                id="school-leader-library-image"

                                wire:model="imageMediaId"

                                @disabled($newImage)

                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-400"

                            >

                                <option value="">

                                    No existing image selected

                                </option>

                                @foreach ($mediaAssets as $asset)

                                    <option value="{{ $asset->id }}">

                                        #{{ $asset->id }}

                                        —

                                        {{ $asset->title ?: $asset->original_name }}

                                    </option>

                                @endforeach

                            </select>

                            @error('imageMediaId')

                                <p class="mt-1 text-xs font-semibold text-red-600">

                                    {{ $message }}

                                </p>

                            @enderror

                        </div>

                    </div>

                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4">
                        <button type="button" wire:click="closeEditor" wire:loading.attr="disabled" wire:target="save"
                            class="rounded-xl border border-zinc-300 bg-white px-5 py-3 text-sm font-bold text-zinc-700 hover:bg-zinc-100 disabled:opacity-60">Cancel</button>

                        <button

                            type="submit"

                            wire:loading.attr="disabled"

                            wire:target="save"

                            class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:opacity-60"

                        >

                            <span wire:loading.remove wire:target="save">

                                {{ $editingId === null

                                    ? 'Create Appointment'

                                    : 'Save Changes' }}

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
