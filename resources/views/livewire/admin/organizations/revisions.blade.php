@if ($revisions->isNotEmpty())
<section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
    <h2 class="font-bold text-zinc-900">Content recovery history</h2><p class="mt-1 text-xs text-zinc-500">Restoring creates a new recovery snapshot. Restored content stays in Draft.</p>
    <div class="mt-4 divide-y">
        @foreach ($revisions as $revision)
        <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
            <span>{{ $revision->summary }} · {{ $revision->created_at?->format('d M Y H:i') }}</span>
            @can('organizations.update')<button type="button" wire:click="restoreRevision({{ $revision->id }})" wire:confirm="Restore this snapshot as a draft? Current content will be saved in recovery history." class="font-semibold text-emerald-800 underline">Restore snapshot</button>@endcan
        </div>
        @endforeach
    </div>
</section>
@endif
