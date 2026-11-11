<div data-unsaved-form data-draft-key="gallery-{{ $galleryId }}" data-save-actions="saveAllImageDetails,publish" class="mx-auto max-w-5xl space-y-6">
    @php($published = $gallery->status === \App\Enums\GalleryStatus::Published)
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-zinc-950">Edit Gallery</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ $published ? 'Published — unpublish to edit.' : ($editable ? 'Draft — save and publish when ready.' : 'Unpublished — return to Draft to edit.') }}</p>
        </div>
        <a href="{{ route('admin.galleries.index') }}" wire:navigate class="font-semibold text-emerald-700">← All Galleries</a>
    </div>
    @if (session('status'))
        <div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    <section class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <form wire:submit="save" data-save-actions="save,publish,selectCover" class="space-y-4">
            <label for="gallery-title" class="block text-sm font-semibold">English title</label>
            <input id="gallery-title" wire:model="title" type="text" maxlength="255" required @disabled(! $editable)
                class="w-full rounded-xl border border-zinc-300 px-4 py-3 disabled:bg-zinc-100">

            <div>
                <label for="gallery-title-si" class="mb-2 block text-sm font-semibold">සිංහල මාතෘකාව</label>
                <input id="gallery-title-si" wire:model="titleSi" type="text" lang="si" maxlength="255" @disabled(! $editable)
                    class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm disabled:bg-zinc-100">
                <p class="mt-1 text-xs text-zinc-500">Optional. Leave blank to display the English title on the Sinhala page.</p>
                @error('titleSi')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @if ($editable)
                <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">Save Changes</button>
            @endif
        </form>
        <div class="flex flex-wrap gap-3 border-t border-zinc-200 pt-5">
            @can('galleries.publish')
                @if ($editable)
                    <x-admin.icon-action label="Publish" icon="arrow-up-tray" tone="success"
                        type="button" wire:click="publish" wire:confirm="Save the changes and publish this gallery?" wire:loading.attr="disabled"
                    />
                @else
                    <x-admin.icon-action label="{{ $published ? 'Unpublish' : 'Return to Draft' }}" icon="eye-slash" tone="warning"
                        type="button" wire:click="unpublish" wire:confirm="Return this gallery to Draft? Its images will be kept." wire:loading.attr="disabled"
                    />
                @endif
            @endcan
            @can('galleries.delete')
                @if (! $published)
                    <x-admin.icon-action label="Delete Gallery" icon="trash" tone="danger"
                        type="button" wire:click="delete" wire:confirm="Delete this gallery?" wire:loading.attr="disabled"
                    />
                @endif
            @endcan
        </div>
    </section>
    @if ($editable)
        @can('media.upload')
            <form wire:submit="uploadImages" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6">
                <label for="gallery-uploads" class="block text-sm font-semibold">Add more photographs</label>
                <input id="gallery-uploads" type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                <p class="text-xs text-zinc-500">Select up to 20 JPG, PNG or WebP images, 8 MB each.</p>
                <p wire:loading wire:target="uploads" role="status" class="text-sm text-emerald-700">Preparing images…</p>
                @if ($uploads !== [])<p class="text-sm text-zinc-600">{{ count($uploads) }} images selected</p>@endif
                <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">Upload Images</button>
                @foreach ($imageUploadResults as $index => $result)<div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-xs"><span class="break-all">{{ $result['name'] }} · {{ $result['message'] }}</span>@if (!$result['success'])<button type="button" wire:click="retryImageUpload({{ $index }})" wire:loading.attr="disabled" class="font-semibold text-emerald-800 underline">Retry Image</button>@endif</div>@endforeach
            </form>
        @endcan
    @endif
    <section class="rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="mb-5 text-lg font-bold">Photographs ({{ $gallery->images->count() }})</h2>
        <div @if($editable) data-gallery-sort @endif class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($gallery->images as $image)
                <div data-gallery-image="{{ $image->id }}" wire:key="gallery-image-{{ $image->id }}" class="overflow-hidden rounded-xl border border-zinc-200">
                    @if ($previewUrls[$image->id] ?? null)
                        <img src="{{ $previewUrls[$image->id] }}" alt="{{ $image->alt_text ?: $gallery->title }}" loading="lazy" class="aspect-square w-full object-cover">
                    @endif
                    <div class="space-y-2 p-3 text-xs">
                        @if ((int) $gallery->cover_media_id === (int) $image->media_asset_id)
                            <p class="font-bold text-emerald-700">Cover image</p>
                        @endif
                        @if ($editable)
                            <button type="button" data-gallery-drag aria-label="Drag to reorder image" class="cursor-grab rounded-lg border px-3 py-2 text-zinc-600">↕ Drag to reorder</button>
                            <label class="block">Caption<input wire:model="imageCaptions.{{ $image->id }}" maxlength="1000" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2"></label>
                            <label class="block">Image description<input wire:model="imageAltTexts.{{ $image->id }}" maxlength="255" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2"></label>
                            @error('imageCaptions.'.$image->id)<p class="text-red-700">{{ $message }}</p>@enderror
                            @error('imageAltTexts.'.$image->id)<p class="text-red-700">{{ $message }}</p>@enderror
                            <div class="flex flex-wrap gap-3">
                                <x-admin.icon-action label="Set cover" icon="photo" tone="info"
                                    type="button" wire:click="selectCover({{ $image->id }})" wire:loading.attr="disabled"
                                />
                                <x-admin.icon-action label="Remove" icon="trash" tone="danger"
                                    type="button" wire:click="removeImage({{ $image->id }})" wire:confirm="Remove this image from the gallery?" wire:loading.attr="disabled"
                                />
                            </div>
                            <div class="flex gap-3">
                                @if (! $loop->first)
                                    <x-admin.icon-action label="Move image earlier" icon="chevron-left" tone="neutral"
                                        type="button" wire:click="moveImageUp({{ $image->id }})" wire:loading.attr="disabled" aria-label="Move image earlier"
                                    />
                                @endif
                                @if (! $loop->last)
                                    <x-admin.icon-action label="Move image later" icon="chevron-right" tone="neutral"
                                        type="button" wire:click="moveImageDown({{ $image->id }})" wire:loading.attr="disabled" aria-label="Move image later"
                                    />
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @if ($editable && $gallery->images->isNotEmpty())<button type="button" wire:click="saveAllImageDetails" wire:loading.attr="disabled" class="mt-4 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Save All Captions &amp; Descriptions</button>@endif
        @if ($gallery->images->isEmpty())<p class="text-sm text-zinc-500">Upload images before publishing.</p>@endif
    </section>
</div>
