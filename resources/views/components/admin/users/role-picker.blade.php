@props(['roles', 'value' => '', 'id' => 'role', 'disabled' => false])

@php
    $roleNames = collect($roles)->all();
    $previewRole = in_array($value, $roleNames, true)
        ? \Spatie\Permission\Models\Role::query()->where('guard_name', 'web')->where('name', $value)->with('permissions')->first()
        : null;
    $previewGroups = $previewRole?->permissions->sortBy('name')->groupBy(fn ($permission) => \App\Support\PermissionCatalog::group($permission->name))
        ->reject(fn ($permissions, $group) => in_array($group, ['admin', 'dashboard'], true)) ?? collect();
@endphp

<div>
    <label for="{{ $id }}" class="mb-2 block text-sm font-semibold text-zinc-800">Primary role</label>
    <select id="{{ $id }}" wire:model.live="role" @disabled($disabled) aria-describedby="{{ $id }}-help"
        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 disabled:cursor-not-allowed disabled:bg-zinc-100">
        <option value="">Select a role</option>
        @foreach ($roleNames as $roleName)
            <option value="{{ $roleName }}">{{ $roleName }}{{ $roleName === 'Content Editor' ? ' — recommended for most staff' : '' }}</option>
        @endforeach
    </select>
    @error('role')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

    <div id="{{ $id }}-help" class="mt-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3" aria-live="polite">
        @if ($previewRole)
            <p class="flex items-center gap-2 text-sm font-bold text-zinc-900">
                <i class="{{ \App\Support\RolePresets::icon($value) }} text-emerald-700" aria-hidden="true"></i>
                {{ $value }}
            </p>
            <p class="mt-1 text-xs leading-5 text-zinc-600">Suggested use: {{ \App\Support\RolePresets::description($value) }}</p>
            @if ($value === 'Super Administrator')
                <p class="mt-2 text-xs font-semibold text-amber-900">Full system access, including all users and role settings.</p>
            @elseif (! $previewRole->permissions->contains('name', 'admin.access'))
                <p class="mt-2 text-xs font-semibold text-amber-900">This role has no admin workspace access. Update its permissions before assigning it to staff.</p>
            @else
                <p class="mt-3 text-xs font-bold text-zinc-700">Current role access</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @forelse ($previewGroups as $group => $permissions)
                        <span class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs text-zinc-700">{{ \App\Support\PermissionCatalog::groupLabel($group) }}</span>
                    @empty
                        <span class="text-xs text-zinc-600">Admin workspace only</span>
                    @endforelse
                </div>
                @if ($previewGroups->isNotEmpty())
                    <details class="mt-3 text-xs text-zinc-700">
                        <summary class="cursor-pointer font-semibold text-emerald-800">View allowed actions</summary>
                        <dl class="mt-2 space-y-2">
                            @foreach ($previewGroups as $group => $permissions)
                                <div>
                                    <dt class="font-semibold">{{ \App\Support\PermissionCatalog::groupLabel($group) }}</dt>
                                    <dd class="mt-0.5 leading-5 text-zinc-600">{{ $permissions->map(fn ($permission) => \App\Support\PermissionCatalog::label($permission->name))->implode(', ') }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </details>
                @endif
            @endif
        @else
            <p class="text-xs leading-5 text-zinc-600">Choose a role to see its current access. Content Editor is a good starting point for staff who prepare news and pages.</p>
        @endif
        @if ($disabled)
            <p class="mt-2 text-xs font-semibold text-zinc-700">Your account can edit this user's profile, but cannot change their role.</p>
        @endif
    </div>
    @if (! $disabled && $roleNames === [])
        <p class="mt-2 text-xs font-semibold text-amber-900">No assignable roles are available. Ask the Super Administrator to set up a role for this account.</p>
    @endif
</div>
