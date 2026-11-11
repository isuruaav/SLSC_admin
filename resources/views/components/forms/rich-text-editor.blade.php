@props([
    'id',
    'model' => 'content',
    'value' => '',
    'label' => 'Page content',
])

<div>
    <label
        for="{{ $id }}"
        class="mb-2 block text-sm font-semibold
               text-zinc-800"
    >
        {{ $label }}
    </label>

    <div
        wire:ignore
        class="overflow-hidden rounded-xl"
    >
        <input
            id="{{ $id }}-input"
            type="hidden"
            value="{{ $value }}"
        >

        <trix-editor
            data-draft-model="{{ $model }}"
            id="{{ $id }}"
            input="{{ $id }}-input"
            aria-label="{{ $label }}"
            placeholder="Enter the page content..."
            class="trix-content awcms-editor"
            x-data
            x-on:trix-change.debounce.300ms="
                $wire.set(
                    @js($model),
                    $event.target.value,
                    false
                )
            "
            x-on:trix-file-accept="
                $event.preventDefault()
            "
        ></trix-editor>
    </div>

    <p class="mt-2 text-xs leading-5 text-zinc-500">
        Use headings, bold text, lists, links and quotations.
        Images and file attachments will be available through
        the Media Library in a later stage.
    </p>

    @error($model)
        <p class="mt-2 text-sm font-medium text-red-600">
            {{ $message }}
        </p>
    @enderror
</div>
