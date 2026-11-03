<div class="space-y-6"
    x-data="{ search: '', dirty: false, canLeave() { return !this.dirty || window.confirm('Discard unsaved permission changes?'); } }"
    x-on:role-editor-reset.window="dirty = false; search = ''"
    x-on:role-editor-changed.window="dirty = true">
    <x-admin.page-header title="Roles & Permissions" description="Choose what each team member can do. Start with a recommended role, then adjust its access.">
        <x-slot:actions>
            @can('users.view')
                <a href="{{ route('admin.users.index') }}" wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                    <i class="fa-solid fa-users" aria-hidden="true"></i> Manage users
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <i class="fa-solid fa-circle-check mr-2" aria-hidden="true"></i>{{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Please check the highlighted fields.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    <p class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
        <i class="fa-solid fa-lightbulb mr-1" aria-hidden="true"></i>
        Start most staff with <strong>Content Editor</strong>. Give <strong>Publisher</strong> to the person who approves and publishes content.
        Keep <strong>Super Administrator</strong> for the site owner.
    </p>

    @if ($canManageRoles && $missingRecommendedRoles !== [])
        <section class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-amber-950">Complete your recommended roles</h2>
                <p class="mt-1 text-sm text-amber-900">Missing: {{ implode(', ', $missingRecommendedRoles) }}. Existing roles and user assignments will be kept.</p>
            </div>
            <button type="button" x-on:click="if (canLeave()) $wire.installRecommendedRoles()"
                wire:loading.attr="disabled" wire:target="installRecommendedRoles"
                class="shrink-0 rounded-xl bg-amber-900 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
                <span wire:loading.remove wire:target="installRecommendedRoles">Add recommended roles</span>
                <span wire:loading wire:target="installRecommendedRoles">Adding roles...</span>
            </button>
        </section>
    @endif

    <div class="grid min-w-0 gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
        <aside class="min-w-0 space-y-5">
            <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 px-5 py-4">
                    <h2 class="font-bold text-zinc-950">Your roles <span class="ml-1 text-sm font-medium text-zinc-500">({{ $roles->count() }})</span></h2>
                    <p class="mt-1 text-xs text-zinc-500">Select a role to review its current access.</p>
                </div>
                <div class="divide-y divide-zinc-100">
                    @forelse ($roles as $role)
                        <button type="button" wire:key="role-choice-{{ $role->id }}"
                            x-on:click="if (canLeave()) $wire.selectRole({{ $role->id }})"
                            aria-pressed="{{ $selectedRoleId === $role->id ? 'true' : 'false' }}"
                            @class(['flex w-full gap-3 px-5 py-4 text-left transition focus-visible:outline-2 focus-visible:outline-emerald-600',
                                'bg-emerald-50' => $selectedRoleId === $role->id, 'hover:bg-zinc-50' => $selectedRoleId !== $role->id])>
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-emerald-800">
                                <i class="{{ \App\Support\RolePresets::icon($role->name) }}" aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block break-words text-sm font-bold text-zinc-950">{{ $role->name }}</span>
                                <span class="mt-1 block text-xs leading-5 text-zinc-600">{{ \App\Support\RolePresets::description($role->name) }}</span>
                                <span class="mt-2 flex flex-wrap gap-2 text-xs font-medium text-zinc-500">
                                    <span>{{ $role->users_count }} {{ $role->users_count === 1 ? 'user' : 'users' }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ $role->name === 'Super Administrator' ? 'Full access' : $role->permissions_count.' permissions' }}</span>
                                </span>
                            </span>
                        </button>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-zinc-500">No roles found. Add the recommended roles to get started.</p>
                    @endforelse
                </div>
            </section>
            @if ($canManageRoles)
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-zinc-950"><i class="fa-solid fa-user-plus mr-1 text-emerald-700" aria-hidden="true"></i> Create a custom role</h2>
                    <p class="mt-1 text-xs leading-5 text-zinc-500">Choose a starting template and give the role a clear name.</p>
                    <form x-on:submit.prevent="if (canLeave()) $wire.createRole()" class="mt-4 space-y-4">
                        <div>
                            <label for="new-role-name" class="mb-1.5 block text-sm font-semibold text-zinc-800">Role name</label>
                            <input id="new-role-name" wire:model="newRoleName" maxlength="100" required placeholder="e.g. News team"
                                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                            @error('newRoleName')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="new-role-template" class="mb-1.5 block text-sm font-semibold text-zinc-800">Start with</label>
                            <select id="new-role-template" wire:model="newRoleTemplate" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm">
                                @foreach ($templates as $name => $template)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                                <option value="blank">Blank role — choose access yourself</option>
                            </select>
                            @error('newRoleTemplate')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="createRole"
                            class="w-full rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800 disabled:opacity-50">
                            <span wire:loading.remove wire:target="createRole">Create role</span>
                            <span wire:loading wire:target="createRole">Creating...</span>
                        </button>
                    </form>
                </section>
            @endif
        </aside>

        <section class="min-w-0 self-start overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            @if ($selectedRole)
                @php
                    $isProtected = $selectedRole->name === 'Super Administrator';
                    $editable = $canManageRoles && ! $isProtected;
                    $members = $roles->firstWhere('id', $selectedRoleId)?->users_count ?? 0;
                    $searchTerms = $groupedPermissions->flatMap(fn ($items, $group) => $items->map(fn ($item) => \App\Support\PermissionCatalog::groupLabel($group).' '.\App\Support\PermissionCatalog::label($item->name)))->values()->all();
                @endphp
                <div class="space-y-4 border-b border-zinc-200 bg-zinc-50 px-5 py-5 sm:px-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-zinc-950">{{ $selectedRole->name }}</h2>
                            <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-600">{{ \App\Support\RolePresets::description($selectedRole->name) }}</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-zinc-700">
                            <span x-text="$wire.selectedPermissions.length">{{ count($selectedPermissions) }}</span> permissions
                        </span>
                    </div>
                    @if ($isProtected)
                        <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                            <i class="fa-solid fa-lock mr-1" aria-hidden="true"></i> Super Administrator always has full system access. This role is protected.
                        </p>
                    @elseif (! $canManageRoles)
                        <p class="text-sm font-semibold text-blue-800">Read-only access. Only a Super Administrator can change role permissions.</p>
                    @else
                        <p class="text-xs leading-5 text-zinc-600">Saving updates access for {{ $members }} {{ $members === 1 ? 'user' : 'users' }} assigned to this role. Required access is selected automatically.</p>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div class="min-w-0 flex-1">
                                <label for="permission-template" class="mb-1.5 block text-xs font-semibold text-zinc-700">Use a recommended access template</label>
                                <select id="permission-template" wire:model="permissionTemplate" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm">
                                    @foreach ($templates as $name => $template)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                                </select>
                            </div>
                            <button type="button" wire:click="applyTemplate" x-on:click="dirty = true" wire:loading.attr="disabled" wire:target="applyTemplate"
                                class="rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-100 disabled:opacity-50">Load template</button>
                        </div>
                        <p class="text-xs text-zinc-500">Loading a template replaces the selections below. Changes apply when you save.</p>
                    @endif
                    <div>
                        <label for="permission-search" class="mb-1.5 block text-sm font-semibold text-zinc-800">Find access</label>
                        <input id="permission-search" type="search" x-model="search" placeholder="Search modules or actions, e.g. news or publish"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                    </div>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
                    @foreach ($groupedPermissions as $group => $permissions)
                        @php
                            $groupLabel = \App\Support\PermissionCatalog::groupLabel($group);
                            $groupSearch = $permissions->map(fn ($item) => $groupLabel.' '.\App\Support\PermissionCatalog::label($item->name))->implode(' ');
                            $allSelected = count(array_intersect($permissions->pluck('name')->all(), $selectedPermissions)) === $permissions->count();
                        @endphp
                        <fieldset wire:key="role-permissions-{{ $selectedRoleId }}-{{ $group }}"
                            x-show="@js($groupSearch).toLowerCase().includes(search.toLowerCase().trim())"
                            class="min-w-0 rounded-xl border border-zinc-200 p-4">
                            <legend class="px-1 text-sm font-bold text-zinc-900">{{ $groupLabel }}</legend>
                            @if ($editable)
                                <button type="button" wire:click="togglePermissionGroup(@js($group))" x-on:click="dirty = true" wire:loading.attr="disabled"
                                    wire:target="togglePermission,togglePermissionGroup,applyTemplate,savePermissions"
                                    aria-label="{{ $allSelected ? 'Clear' : 'Select all' }} {{ $groupLabel }} permissions"
                                    class="mb-2 text-xs font-semibold text-emerald-700 hover:text-emerald-900 disabled:opacity-50">{{ $allSelected ? 'Clear group' : 'Select all' }}</button>
                            @endif
                            <div class="space-y-1">
                                @foreach ($permissions as $permission)
                                    <label x-show="@js($groupLabel.' '.\App\Support\PermissionCatalog::label($permission->name)).toLowerCase().includes(search.toLowerCase().trim())"
                                        class="flex min-w-0 items-start gap-3 rounded-lg px-2 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
                                        <input type="checkbox" wire:click="togglePermission(@js($permission->name))" x-on:click="dirty = true"
                                            @checked(in_array($permission->name, $selectedPermissions, true))
                                            x-bind:checked="$wire.selectedPermissions.includes(@js($permission->name))"
                                            @disabled(! $editable) wire:loading.attr="disabled" wire:target="togglePermission,togglePermissionGroup,applyTemplate,savePermissions"
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-zinc-300 text-emerald-700 focus:ring-emerald-500 disabled:cursor-not-allowed">
                                        <span class="min-w-0 break-words leading-5">{{ \App\Support\PermissionCatalog::label($permission->name) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                    <p x-cloak x-show="search && !@js($searchTerms).some(term => term.toLowerCase().includes(search.toLowerCase().trim()))"
                        class="py-6 text-center text-sm text-zinc-500 sm:col-span-2">No access matches your search. Try a module name or clear the search.</p>
                </div>
                @if ($editable)
                    <div class="flex flex-col gap-3 border-t border-zinc-200 bg-zinc-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="text-xs leading-5 text-zinc-600">
                            <p x-show="dirty" x-cloak role="status" class="font-bold text-amber-800">You have unsaved changes.</p>
                            <p>An empty selection removes this role's access.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="discardChanges" wire:loading.attr="disabled"
                                class="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">Discard changes</button>
                            <button type="button" wire:click="savePermissions" wire:loading.attr="disabled" wire:target="savePermissions,togglePermission,togglePermissionGroup,applyTemplate"
                                class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-800 disabled:opacity-50">
                                <span wire:loading.remove wire:target="savePermissions"><i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Save Permissions</span>
                                <span wire:loading wire:target="savePermissions">Saving...</span>
                            </button>
                        </div>
                    </div>
                @endif
            @else
                <div class="px-6 py-16 text-center">
                    <i class="fa-solid fa-user-shield mb-3 text-3xl text-zinc-300" aria-hidden="true"></i>
                    <h2 class="font-bold text-zinc-900">No role selected</h2>
                    <p class="mt-2 text-sm text-zinc-500">Select or create a role to see its access.</p>
                    @if (! $canManageRoles)<p class="mt-2 text-sm text-blue-800">Read-only access.</p>@endif
                </div>
            @endif
        </section>
    </div>
</div>
