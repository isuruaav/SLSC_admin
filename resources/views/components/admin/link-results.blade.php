@props(['results' => []])
@if ($results)
<div class="mt-3 space-y-2 text-xs" role="status">
    @foreach ($results as $result)
    <div @class(['rounded-lg border p-3', 'border-red-200 bg-red-50 text-red-800' => $result['status'] === 'broken', 'border-zinc-200 bg-zinc-50 text-zinc-700' => $result['status'] !== 'broken'])><strong class="block break-all">{{ $result['url'] }}</strong><span>{{ $result['message'] }}</span></div>
    @endforeach
</div>
@endif
