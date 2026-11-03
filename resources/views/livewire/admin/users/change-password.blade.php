<div class="mx-auto max-w-2xl space-y-6">
    <x-admin.page-header title="Change Password" description="Update the password for your own administrator account." />

    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-zinc-200 bg-zinc-50 px-6 py-5">
            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-800"><i class="fa-solid fa-key" aria-hidden="true"></i></span>
            <div><h2 class="font-bold text-zinc-950">Your account password</h2><p class="mt-1 text-sm text-zinc-500">{{ auth()->user()->email }}</p></div>
        </div>

        <form wire:submit="updatePassword" class="space-y-5 p-6">
            <div>
                <label for="own-current-password" class="mb-2 block text-sm font-semibold text-zinc-800">Current password</label>
                <input id="own-current-password" type="password" wire:model="current_password" autocomplete="current-password" required class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('current_password')<p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="own-new-password" class="mb-2 block text-sm font-semibold text-zinc-800">New password</label>
                <input id="own-new-password" type="password" wire:model="password" autocomplete="new-password" required minlength="12" aria-describedby="own-password-help" class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <p id="own-password-help" class="mt-2 text-xs leading-6 text-zinc-500">Use at least 12 characters, including uppercase and lowercase letters, a number and a symbol.</p>
                @error('password')<p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="own-confirm-password" class="mb-2 block text-sm font-semibold text-zinc-800">Confirm new password</label>
                <input id="own-confirm-password" type="password" wire:model="password_confirmation" autocomplete="new-password" required minlength="12" class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('password_confirmation')<p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <p class="rounded-xl bg-blue-50 px-4 py-3 text-xs leading-6 text-blue-900">You will stay signed in on this browser. Other active sessions will be signed out. Your two-factor authentication and passkeys will stay available.</p>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 pt-5">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-sm font-semibold text-zinc-600 hover:text-zinc-950">Back to dashboard</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="updatePassword">Change Password</span><span wire:loading wire:target="updatePassword">Saving…</span></button>
            </div>
        </form>
    </section>
</div>
