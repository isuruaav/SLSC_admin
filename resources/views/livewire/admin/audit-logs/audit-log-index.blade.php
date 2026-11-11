<div class="space-y-6">
    <x-admin.page-header
        title="Audit Logs"
        description="Review authentication activity, administrator actions and security-sensitive account changes."
    />

    <button type="button" wire:click="exportCsv" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Export Filtered CSV</button>
    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="md:col-span-2">
                <label for="audit-search" class="mb-2 block text-sm font-medium text-zinc-700">
                    Search logs
                </label>

                <input
                    id="audit-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Event, actor, email, IP, request ID..."
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                >
            </div>

            <div>
                <label for="audit-event" class="mb-2 block text-sm font-medium text-zinc-700">
                    Event
                </label>

                <select
                    id="audit-event"
                    wire:model.live="event"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                >
                    <option value="all">All events</option>

                    @foreach ($events as $eventName)
                        <option value="{{ $eventName }}">
                            {{ $eventName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="audit-from" class="mb-2 block text-sm font-medium text-zinc-700">
                    From date
                </label>

                <input
                    id="audit-from"
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                >
            </div>

            <div>
                <label for="audit-to" class="mb-2 block text-sm font-medium text-zinc-700">
                    To date
                </label>

                <input
                    id="audit-to"
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                >
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-zinc-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <button
                type="button"
                wire:click="resetFilters"
                class="text-left text-sm font-semibold text-emerald-700 hover:text-emerald-800"
            >
                Reset filters
            </button>

            <div class="flex items-center gap-2">
                <label for="audit-per-page" class="text-sm text-zinc-500">Rows</label>

                <select
                    id="audit-per-page"
                    wire:model.live="perPage"
                    class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm"
                >
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">Event</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">Actor</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">Description</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">IP Address</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100">
                    @forelse ($logs as $log)
                        @php
                            $badgeClasses = match (true) {
                                str_starts_with($log->event, 'auth.login-failed') => 'bg-red-50 text-red-700',
                                str_starts_with($log->event, 'auth.') => 'bg-blue-50 text-blue-700',
                                str_contains($log->event, 'disabled'),
                                str_contains($log->event, 'password-reset') => 'bg-amber-50 text-amber-700',
                                str_contains($log->event, 'created'),
                                str_contains($log->event, 'activated') => 'bg-emerald-50 text-emerald-700',
                                default => 'bg-zinc-100 text-zinc-700',
                            };
                        @endphp

                        <tr wire:key="audit-log-{{ $log->id }}" class="hover:bg-zinc-50/70">
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-sm font-medium text-zinc-800">
                                    {{ $log->created_at->format('Y-m-d') }}
                                </p>

                                <p class="text-xs text-zinc-500">
                                    {{ $log->created_at->format('H:i:s') }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClasses }}">
                                    {{ $log->event }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-zinc-800">
                                    {{ $log->actor_name ?? 'Unauthenticated' }}
                                </p>

                                <p class="text-xs text-zinc-500">
                                    {{ $log->actor_email ?? 'No actor email' }}
                                </p>
                            </td>

                            <td class="max-w-md px-5 py-4">
                                <p class="text-sm text-zinc-700">
                                    {{ $log->description }}
                                </p>

                                <p class="mt-1 text-xs text-zinc-400">
                                    {{ $log->request_method }} {{ $log->request_path }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-zinc-600">
                                {{ $log->ip_address ?? 'Unavailable' }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                <x-admin.icon-action label="Details" icon="information-circle" tone="neutral"
                                    type="button"
                                    wire:click="viewLog({{ $log->id }})"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="font-semibold text-zinc-700">No audit logs found</p>
                                <p class="mt-1 text-sm text-zinc-500">Change or reset the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </section>

    @if ($selectedLog)
        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between border-b border-zinc-100 pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-950">
                        Audit Log #{{ $selectedLog->id }}
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        {{ $selectedLog->event }}
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="closeDetails"
                    class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100"
                >
                    Close
                </button>
            </div>

            <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase text-zinc-500">Actor</dt>
                    <dd class="mt-1 text-sm text-zinc-800">{{ $selectedLog->actor_name ?? 'Unauthenticated' }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase text-zinc-500">Subject</dt>
                    <dd class="mt-1 break-all text-sm text-zinc-800">
                        {{ $selectedLog->subject_type ?? 'None' }} #{{ $selectedLog->subject_id ?? 'N/A' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase text-zinc-500">Request ID</dt>
                    <dd class="mt-1 break-all text-sm text-zinc-800">{{ $selectedLog->request_id }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase text-zinc-500">User Agent</dt>
                    <dd class="mt-1 break-words text-sm text-zinc-800">{{ $selectedLog->user_agent ?? 'Unavailable' }}</dd>
                </div>
            </dl>

            <div class="mt-6"><x-admin.field-diff :before="$selectedLog->old_values ?? []" :after="$selectedLog->new_values ?? []" /></div>
            <details class="mt-4"><summary class="text-sm font-semibold">Raw event data</summary><div class="mt-6 grid gap-5 lg:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900">Old values</h3>
                    <pre class="mt-2 max-h-96 overflow-auto rounded-xl bg-zinc-950 p-4 text-xs leading-6 text-zinc-100">{{ json_encode($selectedLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'No old values' }}</pre>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-zinc-900">New values</h3>
                    <pre class="mt-2 max-h-96 overflow-auto rounded-xl bg-zinc-950 p-4 text-xs leading-6 text-zinc-100">{{ json_encode($selectedLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'No new values' }}</pre>
                </div>
            </div></details>
        </section>
    @endif
</div>
