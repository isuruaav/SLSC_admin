@props(['publishedAt' => '', 'featured' => false, 'gallery' => false])

<section class="rounded-2xl border border-zinc-200 bg-white shadow-sm" aria-labelledby="news-publication-options">
    <div class="border-b border-zinc-200 px-6 py-5">
        <h2 id="news-publication-options" class="font-bold text-zinc-950">Publication options</h2>
        <p class="mt-1 text-sm text-zinc-600">Save Draft keeps the article private. Publish makes it visible at the publication time.</p>
    </div>

    <div class="grid gap-5 p-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="published-at" class="mb-2 block text-sm font-semibold text-zinc-800">Publication date/time</label>
            <input id="published-at" type="datetime-local" wire:model.blur="publishedAt" value="{{ $publishedAt }}"
                aria-describedby="published-at-help{{ $errors->has('publishedAt') ? ' published-at-error' : '' }}"
                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
            <p id="published-at-help" class="mt-2 text-xs leading-5 text-zinc-500">Time zone: {{ config('app.timezone') }}. Leave blank to publish now. A future time schedules visibility after you publish.</p>
            @error('publishedAt')
                <p id="published-at-error" class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label for="news-option-featured" class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
            <input id="news-option-featured" type="checkbox" wire:model.live="isFeatured" @checked($featured)
                class="mt-1 h-4 w-4 shrink-0 rounded border-zinc-300 text-emerald-700 focus:ring-emerald-500">
            <span>
                <span class="block text-sm font-semibold text-zinc-900">Featured news article</span>
                <span class="mt-1 block text-xs leading-5 text-zinc-600">Prioritise this article within its category on the homepage and news listing. This does not change its article type.</span>
            </span>
        </label>

        <label for="news-option-gallery" class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <input id="news-option-gallery" type="checkbox" wire:model.live="showInGallery" @checked($gallery)
                class="mt-1 h-4 w-4 shrink-0 rounded border-zinc-300 text-emerald-700 focus:ring-emerald-500">
            <span>
                <span class="block text-sm font-semibold text-zinc-900">Show these images in Gallery</span>
                <span class="mt-1 block text-xs leading-5 text-zinc-600">After publication, public images uploaded to this article appear as a Gallery album. Untick to keep the images only in News.</span>
            </span>
        </label>
        @error('isFeatured')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('showInGallery')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</section>
