<div class="space-y-6">
    @php
        $canCreate = \Illuminate\Support\Facades\Gate::allows('hero-slides.create');
        $canUpdate = \Illuminate\Support\Facades\Gate::allows('hero-slides.update');
        $canManageForm = $canCreate || $canUpdate;
        $canSave = $editingId === null ? $canCreate : $canUpdate;
    @endphp
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                Site Management
            </p>
            <h1 class="mt-1 text-2xl font-black text-zinc-950">
                Hero Slider
            </h1>
            <p class="mt-1 text-sm text-zinc-500">
                Manage English and Sinhala homepage slides, images, buttons,
                visibility and display order.
            </p>
        </div>
        @can('hero-slides.create')
            <button type="button" wire:click="startCreate" wire:loading.attr="disabled" wire:target="startCreate,edit"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-800 disabled:opacity-60">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Create Hero Slide
            </button>
        @endcan
    </div>
    @if (session('status'))
        <div
            class="rounded-xl border border-emerald-200 bg-emerald-50
                   px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif
    <div class="min-w-0">
        <section class="overflow-hidden rounded-2xl border border-zinc-200
                   bg-white shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-3
                       border-b border-zinc-200 bg-zinc-50 px-6 py-4">
                <div>
                    <h2 class="font-bold text-zinc-900">
                        Homepage Slides
                    </h2>
                    <p class="mt-1 text-xs text-zinc-500">
                        Slides appear according to the order shown below.
                    </p>
                </div>
                <span
                    class="rounded-full bg-zinc-200 px-3 py-1
                           text-xs font-bold text-zinc-700">
                    {{ $heroSlides->count() }}
                    {{ $heroSlides->count() === 1 ? 'slide' : 'slides' }}
                </span>
            </div>
            <div class="divide-y divide-zinc-200">
                @forelse ($heroSlides as $slide)
                    @php
                        $english = $slide->translation(\App\Enums\PageLocale::English, fallbackToEnglish: false);
                        $sinhala = $slide->translation(\App\Enums\PageLocale::Sinhala, fallbackToEnglish: false);
                    @endphp
                    <article wire:key="hero-slide-{{ $slide->id }}" class="p-5 sm:p-6">
                        <div class="flex flex-col gap-5 sm:flex-row">
                            <div
                                class="flex h-28 w-full shrink-0 items-center
                                       justify-center overflow-hidden rounded-xl
                                       border border-zinc-200 bg-zinc-100
                                       sm:w-44">
                                @if ($slide->image)
                                    @php
                                        $slideThumb = app(\App\Services\MediaUrlService::class)->thumbnailOrOriginal($slide->image);
                                    @endphp
                                    @if ($slideThumb)
                                        <img src="{{ $slideThumb }}" alt="{{ $slide->image->title ?: $slide->image->original_name }}"
                                            class="h-full w-full object-cover">
                                    @else
                                        <div class="space-y-1 px-4 text-center">
                                            <svg class="mx-auto size-7 text-emerald-700" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                                <circle cx="8.5" cy="9" r="1.5" />
                                                <path d="m4 17 5-5 4 4 2-2 5 5" />
                                            </svg>
                                            <p
                                                class="line-clamp-2 text-xs
                                                       font-semibold text-zinc-600">
                                                {{ $slide->image->title ?: $slide->image->original_name }}
                                            </p>
                                        </div>
                                    @endif
                                @else
                                    <div class="text-center text-zinc-400">
                                        <svg class="mx-auto size-7" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path
                                                d="m3 3 18 18M10.5 6H19a2 2 0 0 1 2 2v9.5M6 6H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h13" />
                                            <path d="m3 17 4-4 3 3 1.5-1.5" />
                                        </svg>
                                        <p class="mt-1 text-xs font-semibold">
                                            No image
                                        </p>
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex flex-wrap items-start
                                           justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap gap-2">
                                            <span @class([
                                                'rounded-full px-2.5 py-1 text-[11px] font-bold',
                                                'bg-emerald-100 text-emerald-800' => $slide->is_active,
                                                'bg-zinc-200 text-zinc-600' => !$slide->is_active,
                                            ])>
                                                {{ $slide->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <span
                                                class="rounded-full bg-blue-50
                                                       px-2.5 py-1 text-[11px]
                                                       font-bold text-blue-700">
                                                Order {{ $slide->sort_order }}
                                            </span>
                                        </div>
                                        <h3
                                            class="mt-3 truncate text-base
                                                   font-black text-zinc-950">
                                            {{ $english?->title ?? $slide->title }}
                                        </h3>
                                        <p class="mt-1 truncate text-sm
                                                   font-semibold text-zinc-600"
                                            lang="si">
                                            {{ $sinhala?->title ?? 'Sinhala translation not added' }}
                                        </p>
                                        @if ($english?->subtitle ?? $slide->subtitle)
                                            <p
                                                class="mt-2 line-clamp-2
                                                       text-xs text-zinc-500">
                                                {{ $english?->subtitle ?? $slide->subtitle }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    @can('hero-slides.update')
                                        <x-admin.icon-action label="Move slide up" icon="arrow-up" tone="neutral"
                                            type="button" wire:click="moveUp({{ $slide->id }})"
                                        />
                                    @endcan
                                    @can('hero-slides.update')
                                        <x-admin.icon-action label="Move slide down" icon="arrow-down" tone="neutral"
                                            type="button" wire:click="moveDown({{ $slide->id }})"
                                        />
                                    @endcan
                                    @can('hero-slides.update')
                                        <x-admin.icon-action label="{{ $slide->is_active ? 'Deactivate' : 'Activate' }}" :icon="$slide->is_active ? 'pause-circle' : 'play-circle'" :tone="$slide->is_active ? 'warning' : 'success'"
                                            type="button" wire:click="toggleActive({{ $slide->id }})"
                                        />
                                    @endcan
                                    @can('hero-slides.update')
                                        <x-admin.icon-action label="Edit" icon="pencil-square" tone="info"
                                            type="button" wire:click="edit({{ $slide->id }})"
                                        />
                                    @endcan
                                    @can('hero-slides.delete')
                                        <x-admin.icon-action label="Delete" icon="trash" tone="danger"
                                            type="button" wire:click="delete({{ $slide->id }})"
                                            wire:confirm="Delete this hero slide and all its translations?"
                                        />
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-14 text-center">
                        <p class="font-bold text-zinc-700">
                            No hero slides have been created.
                        </p>
                        <p class="mt-1 text-sm text-zinc-500">
                            No homepage slides are available.
                        </p>
                    </div>
                @endforelse
            </div>
        </section>
        @if ($canManageForm)
            <flux:modal name="hero-slide-editor" wire:model="showEditor" @close="closeEditor"
                class="w-[calc(100%-2rem)] min-w-0 max-w-5xl max-h-[calc(100dvh-2rem)] overflow-hidden rounded-2xl p-0!">
                @if ($canSave)
                    <form wire:submit="save"
                        x-init="$el.closest('dialog').setAttribute('aria-labelledby', 'hero-slide-editor-title')"
                        class="flex max-h-[calc(100dvh-2rem)] flex-col overflow-hidden bg-white">
                        <div class="shrink-0 border-b border-zinc-200 bg-zinc-50 px-6 py-4 pr-16">
                            <h2 id="hero-slide-editor-title" class="font-bold text-zinc-900">
                                {{ $editingId === null ? 'Create Hero Slide' : 'Edit Hero Slide' }}
                            </h2>
                            <p class="mt-1 text-xs text-zinc-500">
                                Both English and Sinhala titles are required.
                            </p>
                        </div>
                        <div data-draft-feedback-container class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6">
                            @php
                                $existingMedia = $imageMediaId ? \App\Models\MediaAsset::find($imageMediaId) : null;
                                $existingPreviewUrl = $existingMedia ? app(\App\Services\MediaUrlService::class)->mediumOrOriginal($existingMedia) : null;
                            @endphp
                            <x-admin.visibility-schedule />
                            @if ($newImage || $existingPreviewUrl)<x-admin.image-crop-preview :url="$existingPreviewUrl" :upload="$newImage" />@endif
                            @if ($errors->any())
                                <div role="alert" tabindex="-1" x-init="$el.focus()"
                                    class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <p class="font-bold">Please correct the following errors:</p>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="grid min-w-0 items-start gap-6 lg:grid-cols-2">
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm font-black text-zinc-900">
                                            Slider Image
                                        </p>
                                        <p class="mt-1 text-xs text-zinc-500">
                                            Upload a new image or select an existing public image
                                            from the Media Library.
                                        </p>
                                    </div>
                                    @can('media.upload')
            <div
                                        class="rounded-xl border-2 border-dashed border-emerald-300
                           bg-emerald-50/50 p-4">
                                        <label for="new-hero-image" class="block cursor-pointer text-center">
                                            @if ($newImage instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                                <img src="{{ $newImage->temporaryUrl() }}" alt="New slider image preview"
                                                    class="mx-auto h-44 w-full rounded-lg object-cover">
                                                <span class="mt-3 block text-sm font-bold text-emerald-800">
                                                    Change selected image
                                                </span>
                                            @elseif ($existingPreviewUrl)
                                                <img src="{{ $existingPreviewUrl }}" alt="Current slider image preview"
                                                    class="mx-auto h-44 w-full rounded-lg object-cover">
                                                <span class="mt-3 block text-sm font-bold text-emerald-800">
                                                    Click to replace current image
                                                </span>
                                            @else
                                                <span
                                                    class="mx-auto flex size-12 items-center
                                       justify-center rounded-full bg-emerald-100
                                       text-emerald-700">
                                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.8" aria-hidden="true">
                                                        <path d="M12 16V4" />
                                                        <path d="m7 9 5-5 5 5" />
                                                        <path d="M5 20h14a2 2 0 0 0 2-2v-3M3 15v3a2 2 0 0 0 2 2" />
                                                    </svg>
                                                </span>
                                                <span class="mt-3 block text-sm font-black text-zinc-900">
                                                    Upload New Image
                                                </span>
                                                <span class="mt-1 block text-xs text-zinc-500">
                                                    JPG, PNG or WebP — maximum 20 MB
                                                </span>
                                            @endif
                                            <input id="new-hero-image" wire:model="newImage" type="file"
                                                accept="image/jpeg,image/png,image/webp" class="sr-only">
                                        </label>
                                        <div wire:loading wire:target="newImage" class="mt-3 w-full">
                                            <div
                                                class="rounded-lg bg-blue-50 px-3 py-2
                                   text-center text-xs font-bold text-blue-700">
                                                Uploading image preview...
                                            </div>
                                        </div>
                                        @if ($newImage)
                                            <x-admin.icon-action label="Remove New Image" icon="x-mark" tone="danger"
                                                type="button" wire:click="clearNewImage"
                                            />
                                        @endif
                                        @error('newImage')
                                            <p class="mt-2 text-xs font-semibold text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                    <div x-data="{ showDetails: false }" class="space-y-3">
                                        <button type="button" x-on:click="showDetails = ! showDetails"
                                            class="text-xs font-bold text-emerald-700
                               hover:text-emerald-900">
                                            <span x-show="! showDetails">
                                                + Add image title and alternative text
                                            </span>
                                            <span x-show="showDetails" x-cloak>
                                                − Hide image details
                                            </span>
                                        </button>
                                        <div x-show="showDetails" x-collapse x-cloak class="grid gap-3">
                                            <div>
                                                <label for="new-image-title" class="mb-1 block text-xs font-bold text-zinc-700">
                                                    Image title
                                                </label>
                                                <input id="new-image-title" wire:model="newImageTitle" type="text"
                                                    maxlength="255" placeholder="Homepage hero image"
                                                    class="w-full rounded-lg border border-zinc-300
                                       bg-white px-3 py-2.5 text-sm">
                                                @error('newImageTitle')
                                                    <p class="mt-1 text-xs text-red-600">
                                                        {{ $message }}
                                                    </p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="new-image-alt-text"
                                                    class="mb-1 block text-xs font-bold text-zinc-700">
                                                    Alternative text
                                                </label>
                                                <input id="new-image-alt-text" wire:model="newImageAltText" type="text"
                                                    maxlength="255" placeholder="Describe the image for accessibility"
                                                    class="w-full rounded-lg border border-zinc-300
                                       bg-white px-3 py-2.5 text-sm">
                                                @error('newImageAltText')
                                                    <p class="mt-1 text-xs text-red-600">
                                                        {{ $message }}
                                                    </p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="h-px flex-1 bg-zinc-200"></span>
                                        <span class="text-[11px] font-black uppercase text-zinc-400">
                                            Or choose existing
                                        </span>
                                        <span class="h-px flex-1 bg-zinc-200"></span>
                                    </div>

            @endcan
            <div>
                                        <label for="hero-image" class="mb-1.5 block text-sm font-bold text-zinc-800">
                                            Media Library Image
                                        </label>
                                        <select id="hero-image" wire:model="imageMediaId" @disabled($newImage)
                                            class="w-full rounded-xl border border-zinc-300
                               bg-white px-3 py-2.5 text-sm text-zinc-800
                               disabled:cursor-not-allowed disabled:bg-zinc-100
                               disabled:text-zinc-400">
                                            <option value="">No existing image selected</option>
                                            @foreach ($mediaAssets as $asset)
                                                <option value="{{ $asset->id }}">
                                                    #{{ $asset->id }} —
                                                    {{ $asset->title ?: $asset->original_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($newImage)
                                            <p class="mt-1 text-xs text-zinc-500">
                                                The new uploaded image will be used instead of the
                                                selected Media Library image.
                                            </p>
                                        @endif
                                        @error('imageMediaId')
                                            <p class="mt-1 text-xs font-semibold text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="min-w-0 space-y-5">
                                    <fieldset
                                        class="space-y-4 rounded-xl border border-blue-200
                                               bg-blue-50/50 p-4">
                                        <legend class="px-2 text-sm font-black text-blue-900">
                                            English
                                        </legend>
                                        <div>
                                            <label for="english-title" class="mb-1 block text-xs font-bold text-zinc-700">
                                                Title
                                            </label>
                                            <input id="english-title" wire:model="englishTitle" type="text" maxlength="180"
                                                class="w-full rounded-lg border border-zinc-300
                                                       bg-white px-3 py-2.5 text-sm">
                                            @error('englishTitle')
                                                <p class="mt-1 text-xs text-red-600">
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="english-subtitle" class="mb-1 block text-xs font-bold text-zinc-700">
                                                Subtitle
                                            </label>
                                            <textarea id="english-subtitle" wire:model="englishSubtitle" rows="2" maxlength="255"
                                                class="w-full rounded-lg border border-zinc-300
                                                       bg-white px-3 py-2.5 text-sm"></textarea>
                                        </div>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label for="english-button-label" class="mb-1 block text-xs font-bold text-zinc-700">
                                                    Button label
                                                </label>
                                                <input id="english-button-label" wire:model="englishButtonLabel" type="text"
                                                    maxlength="100" placeholder="Learn More"
                                                    class="w-full rounded-lg border border-zinc-300
                                                           bg-white px-3 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label for="english-button-url" class="mb-1 block text-xs font-bold text-zinc-700">
                                                    Button URL
                                                </label>
                                                <input id="english-button-url" wire:model="englishButtonUrl" type="text"
                                                    maxlength="2048" placeholder="/en/pages/about-us"
                                                    class="w-full rounded-lg border border-zinc-300
                                                           bg-white px-3 py-2.5 text-sm">
                                                @error('englishButtonUrl')
                                                    <p class="mt-1 text-xs text-red-600">
                                                        {{ $message }}
                                                    </p>
                                                @enderror
                                            </div>
                                        </div>
                                    </fieldset>
                                    <fieldset
                                        class="space-y-4 rounded-xl border border-emerald-200
                                               bg-emerald-50/50 p-4">
                                        <legend class="px-2 text-sm font-black text-emerald-900">
                                            Sinhala
                                        </legend>
                                        <div>
                                            <label for="sinhala-title" class="mb-1 block text-xs font-bold text-zinc-700">
                                                මාතෘකාව
                                            </label>
                                            <input id="sinhala-title" wire:model="sinhalaTitle" type="text" maxlength="180"
                                                lang="si"
                                                class="w-full rounded-lg border border-zinc-300
                                                       bg-white px-3 py-2.5 text-sm">
                                            @error('sinhalaTitle')
                                                <p class="mt-1 text-xs text-red-600">
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="sinhala-subtitle" class="mb-1 block text-xs font-bold text-zinc-700">
                                                උප මාතෘකාව
                                            </label>
                                            <textarea id="sinhala-subtitle" wire:model="sinhalaSubtitle" rows="2" maxlength="255" lang="si"
                                                class="w-full rounded-lg border border-zinc-300
                                                       bg-white px-3 py-2.5 text-sm"></textarea>
                                        </div>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label for="sinhala-button-label" class="mb-1 block text-xs font-bold text-zinc-700">
                                                    බොත්තමේ පෙළ
                                                </label>
                                                <input id="sinhala-button-label" wire:model="sinhalaButtonLabel" type="text"
                                                    maxlength="100" lang="si" placeholder="වැඩි විස්තර"
                                                    class="w-full rounded-lg border border-zinc-300
                                                           bg-white px-3 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label for="sinhala-button-url" class="mb-1 block text-xs font-bold text-zinc-700">
                                                    Button URL
                                                </label>
                                                <input id="sinhala-button-url" wire:model="sinhalaButtonUrl" type="text"
                                                    maxlength="2048" placeholder="/si/pages/about-us"
                                                    class="w-full rounded-lg border border-zinc-300
                                                           bg-white px-3 py-2.5 text-sm">
                                                @error('sinhalaButtonUrl')
                                                    <p class="mt-1 text-xs text-red-600">
                                                        {{ $message }}
                                                    </p>
                                                @enderror
                                            </div>
                                        </div>
                                    </fieldset>
                                    <label
                                        class="flex cursor-pointer items-center gap-3
                                               rounded-xl border border-zinc-200 p-4">
                                        <input wire:model="isActive" type="checkbox"
                                            class="size-4 rounded border-zinc-300
                                                   text-emerald-700">
                                        <span>
                                            <span class="block text-sm font-bold text-zinc-900">
                                                Active slide
                                            </span>
                                            <span class="block text-xs text-zinc-500">
                                                Show this slide on the public homepage.
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                            <button type="button" wire:click="closeEditor" wire:loading.attr="disabled" wire:target="save"
                                class="rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm font-bold text-zinc-700 hover:bg-zinc-50 disabled:opacity-60">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="save,newImage"
                                class="rounded-xl bg-emerald-700 px-4
                                       py-3 text-sm font-bold text-white
                                       hover:bg-emerald-800 disabled:opacity-60">
                                <span wire:loading.remove wire:target="save">
                                    {{ $editingId === null ? 'Create Slide' : 'Save Changes' }}
                                </span>
                                <span wire:loading wire:target="save">
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                @endif
            </flux:modal>
        @endif
    </div>
</div>
