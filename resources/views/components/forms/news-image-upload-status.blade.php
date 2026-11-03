@props(['editing' => false])

<div class="mt-3 space-y-3" aria-live="polite" aria-atomic="true">
    <div x-cloak x-show="uploading" class="rounded-xl border border-blue-200 bg-blue-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="flex items-center gap-2 text-sm font-semibold text-blue-800" role="status">
                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                <span x-text="progress >= 100 ? 'Checking images on the server…' : 'Preparing selected images… ' + progress + '%'"></span>
            </p>
            <button type="button" x-show="progress > 0 && progress < 100" x-on:click="cancel()"
                class="rounded-lg border border-blue-300 bg-white px-3 py-1.5 text-xs font-semibold text-blue-800 hover:bg-blue-100">
                Cancel upload
            </button>
        </div>
        <progress x-bind:value="progress" max="100" aria-label="Selected images upload progress"
            class="mt-3 block h-2 w-full overflow-hidden rounded-full accent-blue-600"></progress>
        <p x-show="slow" class="mt-2 text-xs leading-5 text-blue-800">
            This is taking longer than usual. Check your connection. Wait until the images are ready before saving.
        </p>
    </div>

    <p x-cloak x-show="ready && readyCount > 0 && !uploading && !error" class="flex items-center gap-2 text-sm font-semibold text-emerald-800" role="status">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        {{ $editing ? 'Images are ready. Click Upload Images to attach them.' : 'Images are ready. Save the draft below to keep them.' }}
    </p>

    <div id="news-gallery-upload-error" x-cloak x-show="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
        <p x-text="error"></p>
    </div>
</div>
