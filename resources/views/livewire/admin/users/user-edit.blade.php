<div class="space-y-6">
    <x-admin.page-header
        :title="'Edit User: '.$target->name"
        description="Update account details, role, status and password security."
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.users.index') }}"
                wire:navigate
                class="rounded-xl border border-zinc-300 bg-white
                       px-4 py-2.5 text-sm font-semibold text-zinc-700
                       transition hover:bg-zinc-100"
            >
                Back to Users
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div
            class="rounded-xl border border-emerald-200
                   bg-emerald-50 px-4 py-3 text-sm
                   font-medium text-emerald-800"
        >
            {{ session('status') }}
        </div>
    @endif

    @error('user')
        <div
            class="rounded-xl border border-red-200
                   bg-red-50 px-4 py-3 text-sm
                   font-medium text-red-800"
        >
            {{ $message }}
        </div>
    @enderror

    <form
        wire:submit="save"
        class="space-y-6"
    >
        <section
            class="rounded-2xl border border-zinc-200
                   bg-white p-6 shadow-sm"
        >
            <div
                class="flex flex-col gap-3 border-b border-zinc-100
                       pb-4 sm:flex-row sm:items-start
                       sm:justify-between"
            >
                <div>
                    <h2 class="font-semibold text-zinc-950">
                        Account Information
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Update the account identity, role and access status.
                    </p>
                </div>

                <x-admin.status-badge
                    :status="$target->is_active
                        ? 'active'
                        : 'inactive'"
                />
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="edit-name"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Full name
                    </label>

                    <input
                        id="edit-name"
                       edit-name"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Full name
                    </label>

                    <input
                        id="edit-name"
                        type="text"
                        wire:model.blur="name"
                        autocomplete="name"
                        class="w-full rounded-xl border border-zinc-300
                               px-4 py-2.5 text-sm outline-none
                               focus:border-emerald-500
                               focus:ring-4 focus:ring-emerald-500/10"
                    >

                    @error('name')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="edit-email"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Email address
                    </label>

                    <input
                        id="edit-email"
                        type="email"
                        wire:model.blur="email"
                        autocomplete="email"
                        class="w-full rounded-xl border border-zinc-300
                               px-4 py-2.5 text-sm outline-none
                               focus:border-emerald-500
                               focus:ring-4 focus:ring-emerald-500/10"
                    >

                    @error('email')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <x-admin.users.role-picker
                    :roles="$roles"
                    :value="$role"
                    id="edit-role"
                    :disabled="! auth()->user()?->can('users.assign-role')"
                />

                <div>
                    <span class="mb-2 block text-sm font-medium text-zinc-700">
                        Account status
                    </span>

                    <label
                        class="flex min-h-11 cursor-pointer items-center
                               justify-between rounded-xl border
                               border-zinc-300 px-4 py-2.5"
                    >
                        <span>
                            <span class="block text-sm font-medium text-zinc-800">
                                Active account
                            </span>

                            <span class="block text-xs text-zinc-500">
                                Disabled accounts cannot access AWCMS.
                            </span>
                        </span>

                        <input
                            type="checkbox"
                            wire:model="isActive"
                            @disabled(auth()->id() === $target->id)
                            class="size-5 rounded border-zinc-300
                                   text-emerald-600 focus:ring-emerald-500
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50"
                        >
                    </label>

                    @error('isActive')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="rounded-xl bg-emerald-600 px-5 py-2.5
                           text-sm font-semibold text-white
                           transition hover:bg-emerald-700
                           disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="save">
                        Save Changes
                    </span>

                    <span wire:loading wire:target="save">
                        Saving...
                    </span>
                </button>
            </div>
        </section>
    </form>

    @if (auth()->id() === $target->id)
        <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
            <div><h2 class="font-bold text-emerald-950">Your password</h2><p class="mt-1 text-sm text-emerald-900">Change your own password using your current password.</p></div>
            <a href="{{ route('admin.account.password') }}" wire:navigate class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Change My Password</a>
        </section>
    @endif

    @can('users.reset-password')
        @if (auth()->id() !== $target->id)
            <form
                wire:submit="resetPassword"
                class="rounded-2xl border border-amber-200
                       bg-white p-6 shadow-sm"
            >
                <div class="border-b border-zinc-100 pb-4">
                    <h2 class="font-semibold text-zinc-950">
                        Secure Password Reset
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Resetting the password terminates the user's active
                        sessions and invalidates remembered login access.
                    </p>
                </div>

                @if (session('password_status'))
                    <div
                        class="mt-5 rounded-xl border border-emerald-200
                               bg-emerald-50 px-4 py-3 text-sm
                               font-medium text-emerald-800"
                    >
                        {{ session('password_status') }}
                    </div>
                @endif

                <div
                    class="mt-5 rounded-xl border border-amber-200
                           bg-amber-50 px-4 py-3 text-sm text-amber-800"
                >
                    Passkeys and two-factor authentication are not removed
                    by this password-only reset.
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label
                            for="administrator-password"
                            class="mb-2 block text-sm font-medium text-zinc-700"
                        >
                            Your administrator password
                        </label>

                        <input
                            id="administrator-password"
                            type="password"
                            wire:model="administrator_password"
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-zinc-300
                                   px-4 py-2.5 text-sm outline-none
                                   focus:border-amber-500
                                   focus:ring-4 focus:ring-amber-500/10"
                        >

                        @error('administrator_password')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="new-password"
                            class="mb-2 block text-sm font-medium text-zinc-700"
                        >
                            New password
                        </label>

                        <input
                            id="new-password"
                            type="password"
                            wire:model="new_password"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-zinc-300
                                   px-4 py-2.5 text-sm outline-none
                                   focus:border-amber-500
                                   focus:ring-4 focus:ring-amber-500/10"
                        >

                        @error('new_password')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="new-password-confirmation"
                            class="mb-2 block text-sm font-medium text-zinc-700"
                        >
                            Confirm new password
                        </label>

                        <input
                            id="new-password-confirmation"
                            type="password"
                            wire:model="new_password_confirmation"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-zinc-300
                                   px-4 py-2.5 text-sm outline-none
                                   focus:border-amber-500
                                   focus:ring-4 focus:ring-amber-500/10"
                        >
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="submit"
                        wire:confirm="Reset this user's password and terminate all active sessions?"
                        wire:loading.attr="disabled"
                        wire:target="resetPassword"
                        class="rounded-xl bg-amber-600 px-5 py-2.5
                               text-sm font-semibold text-white
                               transition hover:bg-amber-700
                               disabled:cursor-not-allowed
                               disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="resetPassword">
                            Reset Password
                        </span>

                        <span wire:loading wire:target="resetPassword">
                            Resetting...
                        </span>
                    </button>
                </div>
            </form>
        @endif
    @endcan

    <section
        class="rounded-2xl border border-zinc-200
               bg-zinc-50 p-5"
    >
        <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-zinc-500">Created</dt>
                <dd class="mt-1 font-medium text-zinc-800">
                    {{ $target->created_at->format('Y-m-d H:i') }}
                </dd>
            </div>

            <div>
                <dt class="text-zinc-500">Last updated</dt>
                <dd class="mt-1 font-medium text-zinc-800">
                    {{ $target->updated_at->format('Y-m-d H:i') }}
                </dd>
            </div>

            <div>
                <dt class="text-zinc-500">Last login</dt>
                <dd class="mt-1 font-medium text-zinc-800">
                    {{ $target->last_login_at?->format('Y-m-d H:i')
                        ?? 'Never' }}
                </dd>
            </div>

            <div>
                <dt class="text-zinc-500">Last login IP</dt>
                <dd class="mt-1 font-medium text-zinc-800">
                    {{ $target->last_login_ip ?? 'Not available' }}
                </dd>
            </div>
        </dl>
    </section>
</div>
