@props([
    'path',
    'label' => 'Public file path',
])
@php($displayPath = app(\App\Services\SiteUrlService::class)->portable((string) $path))

<div x-data="awcmsCopyPath()" {{ $attributes->class(['min-w-0 space-y-2']) }}>
    <div class="flex flex-wrap items-end gap-2">
        <label class="block min-w-0 flex-1">
            <span class="mb-1 block text-xs font-semibold text-zinc-600">{{ $label }}</span>
            <input x-ref="path" type="text" readonly value="{{ $displayPath }}"
                x-on:click="$el.select()" aria-label="{{ $label }}"
                class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-700">
        </label>
        <button type="button" x-on:click="copy()"
            class="shrink-0 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
            x-text="copied ? 'Copied' : 'Copy Path'">Copy Path</button>
    </div>
    <p x-cloak x-show="message" x-text="message" role="status"
        class="text-xs text-zinc-600"></p>
</div>
