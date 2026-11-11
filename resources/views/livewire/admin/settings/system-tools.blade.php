<div class="space-y-6">
    <x-admin.page-header title="Backups & System Status" description="Keep recovery copies of the database and uploaded files." />
    @if(session('status'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('status') }}</p>@endif
    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="font-bold">Create a recovery backup</h2><p class="mt-1 text-sm text-zinc-500">Backups contain the database, public uploads and private uploaded files.</p></div><button type="button" wire:click="createBackup" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white"><span wire:loading.remove wire:target="createBackup">Create Backup</span><span wire:loading wire:target="createBackup">Creating backup…</span></button></div>
        @if($lastRun)<p class="mt-3 text-sm">Last attempt: {{ $lastRun['finished_at'] }} · {{ $lastRun['success'] ? 'Successful' : 'Failed' }}</p>@endif
        <p class="mt-3 text-xs text-zinc-500">{{ $heartbeat ? 'Scheduler last checked in: '.$heartbeat : 'Scheduler has not checked in. Scheduled backups need the server scheduler to be running.' }}</p>
    </section>
    <form wire:submit="saveSchedule" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold">Automatic backup schedule</h2>
        @if($errors->any())<div role="alert" class="text-sm text-red-700">{{ $errors->first() }}</div>@endif
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <label class="text-sm">Frequency<select wire:model="frequency" class="mt-1 block w-full rounded-xl border p-3"><option value="manual">Manual only</option><option value="daily">Daily</option><option value="weekly">Weekly (Monday)</option></select></label>
            <label class="text-sm">Time<input type="time" wire:model="time" required class="mt-1 block w-full rounded-xl border p-3"></label>
            <label class="text-sm">Time zone<input wire:model="timezone" required class="mt-1 block w-full rounded-xl border p-3"></label>
            <label class="text-sm">Backups to keep<input type="number" min="1" max="30" wire:model="retention" class="mt-1 block w-full rounded-xl border p-3"></label>
        </div>
        <p class="text-xs text-zinc-500">Older backup archives are removed after a successful backup, according to this retention setting.</p>
        <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Save Schedule</button>
    </form>
    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Available backups</h2><div class="mt-4 divide-y">@forelse($archives as $archive)<div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"><div><strong class="break-all">{{ $archive['name'] }}</strong><p class="text-xs text-zinc-500">{{ number_format($archive['size']/1048576, 1) }} MB · {{ \Carbon\CarbonImmutable::createFromTimestamp($archive['modified'])->setTimezone($displayTimezone)->format('d M Y H:i') }}</p></div><a class="font-semibold text-emerald-800 underline" href="{{ route('admin.backups.download', ['filename' => $archive['name']]) }}" download>Download</a></div>@empty<p class="text-sm text-zinc-500">No backup archives yet.</p>@endforelse</div></section>
</div>
