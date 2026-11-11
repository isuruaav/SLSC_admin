<section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
    <h2 class="font-bold text-zinc-900">Upload multiple files</h2>
    <p class="text-sm text-zinc-500">Select up to 20 files. Images and documents are validated separately. Failed files can be retried without uploading successful files again.</p>
    <input wire:model="batchFiles" type="file" multiple accept="image/jpeg,image/png,image/webp,application/pdf,.doc,.docx,.xls,.xlsx" aria-label="Select batch files" class="block w-full text-sm">
    @error('batchFiles')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
    <p wire:loading wire:target="batchFiles" role="status" class="text-sm text-emerald-700">Preparing selected files…</p>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="reuseDuplicates"> Reuse matching files already in the library</label>
    <button type="button" wire:click="uploadBatch" wire:loading.attr="disabled" wire:target="batchFiles,uploadBatch,retryBatch" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Upload Selected Files</button>
    <p wire:loading wire:target="uploadBatch,retryBatch" role="status" class="text-sm text-emerald-700">Uploading and checking files…</p>
    <div class="divide-y">
        @foreach($batchResults as $index => $result)
            <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                <div class="min-w-0 flex-1">
                    <strong class="break-all">{{ $result['name'] }}</strong>
                    <p @class(['text-xs', 'text-red-700' => $result['status'] === 'error', 'text-emerald-700' => $result['status'] !== 'error'])>{{ $result['message'] }}</p>
                    @if($result['url'])<x-admin.copy-path :path="$result['url']" class="mt-3 max-w-md" />@endif
                </div>
                @if($result['status'] === 'error')
                    <button type="button" wire:click="retryBatch({{ $index }})" wire:loading.attr="disabled" class="font-semibold text-emerald-800 underline">Retry File</button>
                @elseif($result['url'])
                    <a href="{{ $result['url'] }}" target="_blank" rel="noopener" class="font-semibold text-emerald-800 underline">View File</a>
                @endif
            </div>
        @endforeach
    </div>
</section>
