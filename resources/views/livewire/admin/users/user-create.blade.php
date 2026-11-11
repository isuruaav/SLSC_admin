<div class="space-y-6">
    <x-admin.page-header
        title="Create User"
        description="Create a secure administrator account and assign its initial role."
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

    @if ($invitationUrl)
        <section role="status" class="space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5"><h2 class="font-bold">Invitation Ready</h2><p class="text-sm">Share this link privately. It expires after the configured invitation period and can only be used once. No email has been sent.</p><label class="block text-xs font-semibold">Invitation link<input type="text" readonly value="{{ $invitationUrl }}" @click="$el.select()" class="mt-2 block w-full rounded-xl border bg-white p-3 text-sm"></label><a href="{{ route('admin.users.index') }}" wire:navigate class="text-sm font-semibold underline">Back to Users</a></section>
    @else
    <label class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm"><input type="checkbox" wire:model.live="inviteByLink" class="mt-1"><span><strong>Create with an invitation link</strong><br>The user chooses their own password. Leave unchecked to set an initial password manually.</span></label>
    <form
        wire:submit="save"
        class="space-y-6"
    >
        <section
            class="rounded-2xl border border-zinc-200
                   bg-white p-6 shadow-sm"
        >
            <div class="border-b border-zinc-100 pb-4">
                <h2 class="font-semibold text-zinc-950">
                    Account Information
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Enter the user identity and account access status.
                </p>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="name"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Full name
                    </label>

                    <input
                        id="name"
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
                        for="email"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Email address
                    </label>

                    <input
                        id="email"
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
                    id="role"
                    :disabled="false"
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
                                User can sign in immediately.
                            </span>
                        </span>

                        <input
                            type="checkbox"
                            wire:model="isActive"
                            class="size-5 rounded border-zinc-300
                                   text-emerald-600 focus:ring-emerald-500"
                        >
                    </label>

                    @error('isActive')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </section>

        @if (!$inviteByLink)
        <section
            class="rounded-2xl border border-zinc-200
                   bg-white p-6 shadow-sm"
        >
            <div class="border-b border-zinc-100 pb-4">
                <h2 class="font-semibold text-zinc-950">
                    Initial Password
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Use at least 12 characters with uppercase,
                    lowercase, numbers and symbols.
                </p>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="password"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-zinc-300
                               px-4 py-2.5 text-sm outline-none
                               focus:border-emerald-500
                               focus:ring-4 focus:ring-emerald-500/10"
                    >

                    @error('password')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="password-confirmation"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Confirm password
                    </label>

                    <input
                        id="password-confirmation"
                        type="password"
                        wire:model="password_confirmation"
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-zinc-300
                               px-4 py-2.5 text-sm outline-none
                               focus:border-emerald-500
                               focus:ring-4 focus:ring-emerald-500/10"
                    >
                </div>
            </div>
        </section>
        @endif

        <div class="flex justify-end gap-3">
            <a
                href="{{ route('admin.users.index') }}"
                wire:navigate
                class="rounded-xl border border-zinc-300
                       px-5 py-2.5 text-sm font-semibold
                       text-zinc-700 hover:bg-zinc-100"
            >
                Cancel
            </a>

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
                    Create User
                </span>

                <span wire:loading wire:target="save">
                    Creating...
                </span>
            </button>
        </div>
    </form>
    @endif
</div>
