@props(['before' => [], 'after' => []])
@php
    $old = \Illuminate\Support\Arr::dot(is_array($before) ? $before : []);
    $new = \Illuminate\Support\Arr::dot(is_array($after) ? $after : []);
    $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
    $keys = array_values(array_filter($keys, static fn ($key) => ($old[$key] ?? null) !== ($new[$key] ?? null)));
    $display = static fn ($value) => is_bool($value) ? ($value ? 'Yes' : 'No') : (is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE));
@endphp
<div class="overflow-x-auto"><table class="w-full text-left text-xs"><thead><tr class="border-b"><th class="p-2">Field</th><th class="p-2">Before</th><th class="p-2">After</th></tr></thead><tbody>
@forelse ($keys as $key)
    @if (($old[$key] ?? null) !== ($new[$key] ?? null))<tr class="border-b align-top"><th class="p-2">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $key)) }}</th><td class="max-w-sm whitespace-pre-wrap break-words bg-red-50 p-2">{{ $display($old[$key] ?? null) }}</td><td class="max-w-sm whitespace-pre-wrap break-words bg-emerald-50 p-2">{{ $display($new[$key] ?? null) }}</td></tr>@endif
@empty
    <tr><td colspan="3" class="p-3 text-zinc-500">No field changes recorded.</td></tr>
@endforelse
</tbody></table></div>
