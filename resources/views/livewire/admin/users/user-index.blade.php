<div class="space-y-6">
    <x-admin.page-header
        title="User Management"
        description="Search, review and control access to AWCMS administrator accounts."
    >
        @can('users.create')
            <x-slot:actions>
                <a
                    href="{{ route('admin.users.create') }}"
                    wire:navigate
                    class="rounded-xl bg-emerald-600 px-4 py-2.5
                           text-sm font-semibold text-white
                           transition hover:bg-emerald-700"
                >
                    Create User
                </a>
            </x-slot:actions>
        @endcan
    </x-admin.page-header>

    {{-- Success message --}}
    @if (session('status'))
        <div
            class="rounded-xl border border-emerald-200
                   bg-emerald-50 px-4 py-3
                   text-sm font-medium text-emerald-800"
        >
            {{ session('status') }}
        </div>
    @endif

    {{-- User management error --}}
    @error('user')
        <div
            class="rounded-xl border border-red-200
                   bg-red-50 px-4 py-3
                   text-sm font-medium text-red-800"
        >
            {{ $message }}
        </div>
    @enderror

    {{-- Filters --}}
    <section
        class="rounded-2xl border border-zinc-200
               bg-white p-5 shadow-sm"
    >
        <div class="grid gap-4 lg:grid-cols-4">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <label
                    for="user-search"
                    class="mb-2 block text-sm font-medium text-zinc-700"
                >
                    Search users
                </label>

                <input
                    id="user-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name or email..."
                    class="w-full rounded-xl border border-zinc-300
                           bg-white px-4 py-2.5 text-sm
                           text-zinc-900 outline-none transition
                           placeholder:text-zinc-400
                           focus:border-emerald-500
                           focus:ring-4 focus:ring-emerald-500/10"
                >
            </div>

            {{-- Role filter --}}
            <div>
                <label
                    for="role-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700"
                >
                    Role
                </label>

                <select
                    id="role-filter"
                    wire:model.live="role"
                    class="w-full rounded-xl border border-zinc-300
                           bg-white px-4 py-2.5 text-sm
                           text-zinc-900 outline-none
                           focus:border-emerald-500
                           focus:ring-4 focus:ring-emerald-500/10"
                >
                    <option value="all">
                        All roles
                    </option>

                    @foreach ($roles as $roleName)
                        <option value="{{ $roleName }}">
                            {{ $roleName }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status filter --}}
            <div>
                <label
                    for="status-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700"
                >
                    Account status
                </label>

                <select
                    id="status-filter"
                    wire:model.live="status"
                    class="w-full rounded-xl border border-zinc-300
                           bg-white px-4 py-2.5 text-sm
                           text-zinc-900 outline-none
                           focus:border-emerald-500
                           focus:ring-4 focus:ring-emerald-500/10"
                >
                    <option value="all">
                        All statuses
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="disabled">
                        Disabled
                    </option>
                </select>
            </div>
        </div>

        <div
            class="mt-4 flex flex-col gap-3 border-t
                   border-zinc-100 pt-4 sm:flex-row
                   sm:items-center sm:justify-between"
        >
            <button
                type="button"
                wire:click="resetFilters"
                class="text-left text-sm font-medium
                       text-emerald-700 transition
                       hover:text-emerald-800"
            >
                Reset filters
            </button>

            <div class="flex items-center gap-2">
                <label
                    for="per-page"
                    class="text-sm text-zinc-500"
                >
                    Rows
                </label>

                <select
                    id="per-page"
                    wire:model.live="perPage"
                    class="rounded-lg border border-zinc-300
                           bg-white px-3 py-2 text-sm
                           text-zinc-900 outline-none
                           focus:border-emerald-500
                           focus:ring-4 focus:ring-emerald-500/10"
                >
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
    </section>

    {{-- Users table --}}
    <section
        class="overflow-hidden rounded-2xl
               border border-zinc-200 bg-white shadow-sm"
    >
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50">
                    <tr>
                        {{-- User sorting --}}
                        <th class="px-5 py-3 text-left">
                            <button
                                type="button"
                                wire:click="sort('name')"
                                class="inline-flex items-center gap-1
                                       text-xs font-semibold uppercase
                                       tracking-wide text-zinc-600
                                       transition hover:text-zinc-950"
                            >
                                User

                                @if ($sortField === 'name')
                                    <span aria-hidden="true">
                                        {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                @endif
                            </button>
                        </th>

                        <th
                            class="px-5 py-3 text-left text-xs
                                   font-semibold uppercase tracking-wide
                                   text-zinc-600"
                        >
                            Role
                        </th>

                        {{-- Status sorting --}}
                        <th class="px-5 py-3 text-left">
                            <button
                                type="button"
                                wire:click="sort('is_active')"
                                class="inline-flex items-center gap-1
                                       text-xs font-semibold uppercase
                                       tracking-wide text-zinc-600
                                       transition hover:text-zinc-950"
                            >
                                Status

                                @if ($sortField === 'is_active')
                                    <span aria-hidden="true">
                                        {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                @endif
                            </button>
                        </th>

                        {{-- Last login sorting --}}
                        <th class="px-5 py-3 text-left">
                            <button
                                type="button"
                                wire:click="sort('last_login_at')"
                                class="inline-flex items-center gap-1
                                       text-xs font-semibold uppercase
                                       tracking-wide text-zinc-600
                                       transition hover:text-zinc-950"
                            >
                                Last login

                                @if ($sortField === 'last_login_at')
                                    <span aria-hidden="true">
                                        {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                @endif
                            </button>
                        </th>

                        <th
                            class="px-5 py-3 text-right text-xs
                                   font-semibold uppercase tracking-wide
                                   text-zinc-600"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($users as $user)
                        @php
                            $actor = auth()->user();

                            $canManage = $actor instanceof \App\Models\User
                                && \App\Support\UserManagementRules::canManageTarget(
                                    $actor,
                                    $user,
                                );
                        @endphp

                        <tr
                            wire:key="user-{{ $user->id }}"
                            class="transition hover:bg-zinc-50/70"
                        >
                            {{-- User information --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-10 shrink-0
                                               items-center justify-center
                                               rounded-full bg-emerald-100
                                               text-sm font-bold
                                               text-emerald-700"
                                    >
                                        {{ $user->initials() }}
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-semibold
                                                   text-zinc-900"
                                        >
                                            {{ $user->name }}

                                            @if (auth()->id() === $user->id)
                                                <span
                                                    class="ml-1 text-xs
                                                           font-medium
                                                           text-zinc-400"
                                                >
                                                    You
                                                </span>
                                            @endif
                                        </p>

                                        <p
                                            class="truncate text-sm
                                                   text-zinc-500"
                                        >
                                            {{ $user->email }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- Roles --}}
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $assignedRole)
                                        <span
                                            class="inline-flex rounded-full
                                                   bg-blue-50 px-2.5 py-1
                                                   text-xs font-semibold
                                                   text-blue-700"
                                        >
                                            {{ $assignedRole->name }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-red-600">
                                            No role assigned
                                        </span>
                                    @endforelse
                                </div>
                            </td>

                            {{-- Account status --}}
                            <td class="px-5 py-4">
                                <x-admin.status-badge
                                    :status="$user->is_active
                                        ? 'active'
                                        : 'inactive'"
                                />
                            </td>

                            {{-- Last login --}}
                            <td class="px-5 py-4">
                                @if ($user->last_login_at)
                                    <p class="text-sm text-zinc-700">
                                        {{ $user->last_login_at->diffForHumans() }}
                                    </p>

                                    <p class="text-xs text-zinc-400">
                                        {{ $user->last_login_ip
                                            ?? 'IP unavailable' }}
                                    </p>
                                @else
                                    <span class="text-sm text-zinc-400">
                                        Never
                                    </span>
                                @endif
                            <p class="mt-2 text-xs {{ $user->two_factor_secret && $user->two_factor_confirmed_at ? 'text-emerald-700' : 'text-zinc-500' }}">{{ $user->two_factor_secret && $user->two_factor_confirmed_at ? 'MFA enabled' : 'MFA not enabled' }}</p>
                                </td>

                            {{-- Actions --}}
                            <td class="px-5 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    @can('users.update')
                                        @if ($canManage)
                                            <x-admin.icon-action label="Edit" icon="pencil-square" tone="info"
                                                href="{{ route(
                                                    'admin.users.edit',
                                                    ['user' => $user->id],
                                                ) }}"
                                                wire:navigate
                                            />
                                        @endif
                                    @endcan

                                    @can('users.delete')
                                        @if ($canManage && auth()->id() !== $user->id)
                                            <x-admin.icon-action label="Delete user" icon="trash" tone="danger"
                                                type="button"
                                                wire:click="deleteUser({{ $user->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="deleteUser"
                                                wire:confirm="Delete {{ $user->name }}'s account? Their login access and active sessions will be removed. Existing website content will be kept. This cannot be undone."
                                            />
                                        @endif
                                    @endcan

                                    @can('users.update')
                                        @if (
                                            $canManage
                                            && auth()->id() !== $user->id
                                        )
                                            <x-admin.icon-action label="{{ $user->is_active ? 'Disable' : 'Activate' }}" :icon="$user->is_active ? 'pause-circle' : 'play-circle'" :tone="$user->is_active ? 'warning' : 'success'"
                                                type="button"
                                                wire:click="toggleActive({{ $user->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleActive({{ $user->id }})"
                                                wire:confirm="Are you sure you want to {{ $user->is_active ? 'disable' : 'activate' }} this account?"
                                            />
                                        @elseif (! $canManage)
                                            <span
                                                class="self-center text-xs
                                                       text-zinc-400"
                                            >
                                                Protected
                                            </span>
                                        @elseif (auth()->id() === $user->id)
                                            <span
                                                class="self-center text-xs
                                                       text-zinc-400"
                                            >
                                                Current account
                                            </span>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="px-5 py-14 text-center"
                            >
                                <p class="font-semibold text-zinc-700">
                                    No users found
                                </p>

                                <p class="mt-1 text-sm text-zinc-500">
                                    Change or reset the current filters.
                                </p>

                                <button
                                    type="button"
                                    wire:click="resetFilters"
                                    class="mt-4 text-sm font-semibold
                                           text-emerald-700
                                           hover:text-emerald-800"
                                >
                                    Reset filters
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </section>
</div>
