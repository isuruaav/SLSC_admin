<fieldset class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
    <legend class="px-2 text-sm font-semibold">Visibility schedule</legend>
    <p class="mb-3 text-xs text-zinc-500">Optional. Times use Asia/Colombo. Active items are shown only during this period.</p>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="text-sm">Show from<input type="datetime-local" wire:model="startsAt" class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white p-2"></label>
        <label class="text-sm">Hide after<input type="datetime-local" wire:model="endsAt" class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white p-2"></label>
    </div>
    @error('startsAt')<p role="alert" class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror
    @error('endsAt')<p role="alert" class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror
</fieldset>
