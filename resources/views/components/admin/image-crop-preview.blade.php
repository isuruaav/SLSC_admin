@props(['url' => null, 'upload' => null])
@php
    if ($upload instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile && in_array($upload->getMimeType(), ['image/jpeg','image/png','image/webp'], true)) {
        try { $url = $upload->temporaryUrl(); } catch (\Throwable) {}
    }
@endphp
@if ($url)
<details class="rounded-xl border border-zinc-200 p-4"><summary class="cursor-pointer text-sm font-semibold">Preview mobile image crop</summary>
    <div class="mt-3 max-w-[390px] overflow-hidden rounded-xl border border-zinc-200"><img src="{{ $url }}" alt="Mobile image crop preview" style="display:block;width:100%;aspect-ratio:16/10;object-fit:cover;object-position:center"></div>
    <p class="mt-2 text-xs text-zinc-500">Check that faces and essential text stay near the centre.</p>
</details>
@endif
