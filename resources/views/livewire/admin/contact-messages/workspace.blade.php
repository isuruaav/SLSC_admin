<div class="space-y-6">
    <x-admin.page-header title="Contact Messages" description="Review enquiries, assign follow-up and keep internal notes." />
    @if(session('status'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>@endif
    <section class="flex flex-wrap gap-3 rounded-2xl border border-zinc-200 bg-white p-4">
        <label class="min-w-0 flex-1 text-xs font-semibold">Search<input wire:model.live.debounce.300ms="search" type="search" placeholder="Name, email or subject" class="mt-1 block w-full rounded-xl border border-zinc-300 p-3 text-sm"></label>
        <label class="text-xs font-semibold">Status<select wire:model.live="statusFilter" class="mt-1 block rounded-xl border border-zinc-300 p-3 text-sm"><option value="">All statuses</option><option value="new">New ({{ $newCount }})</option><option value="read">Read</option><option value="resolved">Resolved</option></select></label>
    </section>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="divide-y divide-zinc-100">
                @forelse($messages as $message)
                    <button type="button" wire:click="openMessage({{ $message->id }})" @class(['block w-full px-5 py-4 text-left hover:bg-zinc-50', 'bg-emerald-50' => $selectedMessageId === $message->id])>
                        <div class="flex items-start justify-between gap-3"><strong class="break-words text-zinc-900">{{ $message->subject }}</strong><span class="shrink-0 rounded-full bg-zinc-100 px-2 py-1 text-xs">{{ ucfirst($message->status) }}</span></div>
                        <p class="mt-2 break-words text-sm text-zinc-600">{{ $message->name }} · {{ $message->email }}</p>
                        <p class="mt-2 text-xs text-zinc-400">{{ $message->reference }} · {{ $message->created_at?->format('d M Y H:i') }}</p>
                    </button>
                @empty
                    <p class="p-8 text-center text-sm text-zinc-500">No messages match these filters.</p>
                @endforelse
            </div>
            @if($messages->hasPages())<div class="border-t p-4">{{ $messages->links() }}</div>@endif
        </section>
        <aside>
            @if($selectedMessage)
            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm" wire:key="message-{{ $selectedMessage->id }}">
                <h2 class="break-words text-lg font-bold">{{ $selectedMessage->subject }}</h2>
                <p class="text-sm">{{ $selectedMessage->name }}<br><a class="break-all text-emerald-800 underline" href="mailto:{{ $selectedMessage->email }}">{{ $selectedMessage->email }}</a></p>
                @if($selectedMessage->phone)<p class="text-sm"><a class="text-emerald-800 underline" href="tel:{{ preg_replace('/[^+0-9]/', '', $selectedMessage->phone) }}">{{ $selectedMessage->phone }}</a></p>@endif
                @if($selectedMessage->reference)<p class="text-xs text-zinc-500">Reference: {{ $selectedMessage->reference }}</p>@endif
                @if($selectedMessage->district)<p class="text-sm">District: {{ $selectedMessage->district }} · Interest: {{ $selectedMessage->interest }}</p>@endif
                <p class="whitespace-pre-line rounded-xl bg-zinc-50 p-4 text-sm leading-6">{{ $selectedMessage->message }}</p>
                <form wire:submit="saveMessageDetails" class="space-y-3">
                    <label class="block text-sm font-semibold">Assigned to<select wire:model="assignedTo" class="mt-1 block w-full rounded-xl border border-zinc-300 p-2"><option value="">Unassigned</option>@foreach($staff as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></label>
                    @error('assignedTo')<p role="alert" class="text-xs text-red-700">{{ $message }}</p>@enderror
                    <label class="block text-sm font-semibold">Internal notes<textarea wire:model="notes" rows="4" maxlength="5000" class="mt-1 block w-full rounded-xl border border-zinc-300 p-3 text-sm"></textarea></label>
                    @error('notes')<p role="alert" class="text-xs text-red-700">{{ $message }}</p>@enderror
                    <p class="text-xs text-zinc-500">Notes are visible only to staff who can manage enquiries.</p>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Save Details</button>
                </form>
                @if($selectedMessage->status === 'resolved')
                    <button type="button" wire:click="reopen({{ $selectedMessage->id }})" class="rounded-xl border border-zinc-300 px-4 py-3 text-sm font-semibold">Reopen Message</button>
                @else
                    <button type="button" wire:click="resolve({{ $selectedMessage->id }})" class="rounded-xl border border-emerald-300 px-4 py-3 text-sm font-semibold text-emerald-800">Mark Resolved</button>
                @endif
                <p class="text-xs text-zinc-500">Received {{ $selectedMessage->created_at?->format('d M Y H:i') }}</p>
            </section>
            @else
                <p class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500">Select a message to read it.</p>
            @endif
        </aside>
    </div>
</div>
