<div data-unsaved-form data-draft-key="media-upload" data-save-actions="save,uploadBatch,retryBatch,useExistingFile" class="mx-auto max-w-6xl space-y-6">
    @include('livewire.admin.media.batch-upload')
    @if ($duplicateMediaId)<div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">This file already exists in the library. <button type="button" wire:click="useExistingFile" class="font-semibold underline">Use Existing File</button></div>@endif

    {{-- Page Header --}}
    <div
        class="flex flex-col gap-4
               lg:flex-row
               lg:items-start
               lg:justify-between"
    >
        <div>
            <h1
                class="text-2xl font-bold
                       text-zinc-950"
            >
                Upload Media
            </h1>

            <p
                class="mt-1 max-w-2xl
                       text-sm leading-6
                       text-zinc-600"
            >
                Upload approved images and PDF
                documents to the AWCMS Media Library.
            </p>
        </div>

        <div
            class="rounded-xl border
                   border-emerald-200
                   bg-emerald-50
                   px-4 py-3
                   text-xs leading-5
                   text-emerald-800"
        >
            JPG, PNG, WebP: max 10 MB
            <br>
            PDF: max 20 MB
        </div>
    </div>

    @if (session('status'))
        <div
            class="rounded-xl border
                   border-emerald-200
                   bg-emerald-50
                   px-4 py-3
                   text-sm font-medium
                   text-emerald-800"
        >
            {{ session('status') }}
        </div>
    @endif

    @if ($uploadedMedia)
        <section class="space-y-3 rounded-xl border border-blue-200 bg-white p-5"
            wire:key="uploaded-media-path-{{ $uploadedMedia->id }}">
            <h2 class="font-semibold text-zinc-900">{{ $uploadedMedia->title }}</h2>
            @if ($uploadedMediaUrl)
                <x-admin.copy-path :path="$uploadedMediaUrl"
                    :label="$uploadedMedia->isImage() ? 'Public image path' : 'Public document path'" />
                <p class="text-sm text-zinc-600">
                    Copy this path, then use the {{ $uploadedMedia->isImage() ? 'Image' : 'Document' }} button
                    in the page editor to insert it before publishing. The site address is added automatically.
                </p>
                <a href="{{ $uploadedMediaUrl }}" target="_blank" rel="noopener noreferrer"
                    class="inline-block text-sm font-semibold text-blue-700 hover:underline">Open uploaded file</a>
            @else
                <p class="text-sm text-zinc-600">This file is private. Public paths are available for Public uploads.</p>
            @endif
        </section>
    @endif

    <form
        wire:submit="save"
        class="space-y-6"
    >
        <div
            class="grid gap-6
                   xl:grid-cols-[minmax(0,1fr)_360px]"
        >
            {{-- Main Upload Form --}}
            <div class="space-y-6">
                {{-- File --}}
                <section
                    class="overflow-hidden
                           rounded-2xl
                           border border-zinc-200
                           bg-white shadow-sm"
                >
                    <div
                        class="border-b border-zinc-200
                               bg-zinc-50
                               px-6 py-4"
                    >
                        <h2
                            class="font-bold
                                   text-zinc-900"
                        >
                            Select File
                        </h2>

                        <p
                            class="mt-1 text-xs
                                   text-zinc-500"
                        >
                            Select one approved image
                            or PDF document.
                        </p>
                    </div>

                    <div class="p-6">
                        <div
                            x-data="{
                                uploading: false,
                                progress: 0
                            }"
                            x-on:livewire-upload-start="
                                uploading = true;
                                progress = 0;
                            "
                            x-on:livewire-upload-finish="
                                uploading = false;
                                progress = 100;
                            "
                            x-on:livewire-upload-cancel="
                                uploading = false;
                                progress = 0;
                            "
                            x-on:livewire-upload-error="
                                uploading = false;
                                progress = 0;
                            "
                            x-on:livewire-upload-progress="
                                progress = $event.detail.progress
                            "
                            class="space-y-4"
                        >
                            <label
                                for="media-file"
                                class="flex cursor-pointer
                                       flex-col items-center
                                       justify-center
                                       rounded-2xl
                                       border-2
                                       border-dashed
                                       border-zinc-300
                                       bg-zinc-50
                                       px-6 py-10
                                       text-center
                                       transition
                                       hover:border-emerald-400
                                       hover:bg-emerald-50/50"
                            >
                                <div
                                    class="flex h-12 w-12
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-white
                                           text-xl font-bold
                                           text-emerald-700
                                           shadow-sm"
                                >
                                    ↑
                                </div>

                                <span
                                    class="mt-4 text-sm
                                           font-bold
                                           text-zinc-900"
                                >
                                    Choose a file
                                </span>

                                <span
                                    class="mt-1 text-xs
                                           text-zinc-500"
                                >
                                    JPG, JPEG, PNG, WebP
                                    or PDF
                                </span>

                                <input
                                    id="media-file"
                                    type="file"
                                    wire:model="file"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                    class="sr-only"
                                >
                            </label>

                            {{-- Upload progress --}}
                            <div
                                x-show="uploading"
                                x-cloak
                                class="rounded-xl
                                       border border-blue-200
                                       bg-blue-50 p-4"
                            >
                                <div
                                    class="flex items-center
                                           justify-between
                                           gap-4"
                                >
                                    <p
                                        class="text-sm
                                               font-semibold
                                               text-blue-800"
                                    >
                                        Uploading temporary file...
                                    </p>

                                    <p
                                        class="text-sm
                                               font-bold
                                               text-blue-800"
                                        x-text="`${progress}%`"
                                    ></p>
                                </div>

                                <div
                                    class="mt-3 h-2
                                           overflow-hidden
                                           rounded-full
                                           bg-blue-100"
                                >
                                    <div
                                        class="h-full
                                               bg-blue-600
                                               transition-all"
                                        x-bind:style="
                                            `width: ${progress}%`
                                        "
                                    ></div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="$cancelUpload('file')"
                                    class="mt-3 text-xs
                                           font-semibold
                                           text-blue-700
                                           hover:underline"
                                >
                                    Cancel upload
                                </button>
                            </div>

                            {{-- Selected file --}}
                            @if ($selectedFileName)
                                <div
                                    class="flex flex-col gap-3
                                           rounded-xl border
                                           border-zinc-200
                                           bg-white p-4
                                           sm:flex-row
                                           sm:items-center
                                           sm:justify-between"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="truncate
                                                   text-sm
                                                   font-semibold
                                                   text-zinc-900"
                                        >
                                            {{ $selectedFileName }}
                                        </p>

                                        @if ($selectedFileSize)
                                            <p
                                                class="mt-1
                                                       text-xs
                                                       text-zinc-500"
                                            >
                                                {{ $selectedFileSize }}
                                            </p>
                                        @endif
                                    </div>

                                    <x-admin.icon-action label="Remove" icon="trash" tone="danger"
                                        type="button"
                                        wire:click="clearFile"
                                    />
                                </div>
                            @endif

                            @error('file')
                                <p
                                    class="rounded-lg
                                           bg-red-50
                                           px-4 py-3
                                           text-sm
                                           font-medium
                                           text-red-700"
                                >
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- Classification --}}
                <section
                    class="overflow-hidden
                           rounded-2xl
                           border border-zinc-200
                           bg-white shadow-sm"
                >
                    <div
                        class="border-b border-zinc-200
                               bg-zinc-50
                               px-6 py-4"
                    >
                        <h2
                            class="font-bold
                                   text-zinc-900"
                        >
                            Classification
                        </h2>
                    </div>

                    <div
                        class="grid gap-5 p-6
                               md:grid-cols-2"
                    >
                        <div>
                            <label
                                for="media-type"
                                class="mb-2 block
                                       text-sm font-semibold
                                       text-zinc-800"
                            >
                                Media Type
                            </label>

                            <select
                                id="media-type"
                                wire:model.live="type"
                                class="w-full rounded-xl
                                       border border-zinc-300
                                       bg-white px-4 py-3
                                       text-sm"
                            >
                                @foreach ($mediaTypes as $mediaType)
                                    <option
                                        value="{{ $mediaType->value }}"
                                    >
                                        {{ $mediaType->label() }}
                                    </option>
                                @endforeach
                            </select>

                            @error('type')
                                <p
                                    class="mt-2 text-sm
                                           font-medium
                                           text-red-600"
                                >
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="media-visibility"
                                class="mb-2 block
                                       text-sm font-semibold
                                       text-zinc-800"
                            >
                                Visibility
                            </label>

                            <select
                                id="media-visibility"
                                wire:model="visibility"
                                class="w-full rounded-xl
                                       border border-zinc-300
                                       bg-white px-4 py-3
                                       text-sm"
                            >
                                @foreach ($visibilities as $item)
                                    <option
                                        value="{{ $item->value }}"
                                    >
                                        {{ $item->label() }}
                                    </option>
                                @endforeach
                            </select>

                            @error('visibility')
                                <p
                                    class="mt-2 text-sm
                                           font-medium
                                           text-red-600"
                                >
                                    {{ $message }}
                                </p>
                            @enderror

                            <p
                                class="mt-2 text-xs
                                       leading-5
                                       text-zinc-500"
                            >
                                Internal and Restricted
                                files are stored outside
                                the public filesystem.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Metadata --}}
                <section
                    class="overflow-hidden
                           rounded-2xl
                           border border-zinc-200
                           bg-white shadow-sm"
                >
                    <div
                        class="border-b border-zinc-200
                               bg-zinc-50
                               px-6 py-4"
                    >
                        <h2
                            class="font-bold
                                   text-zinc-900"
                        >
                            Media Information
                        </h2>
                    </div>

                    <div class="space-y-5 p-6">
                        <div>
                            <label
                                for="media-title"
                                class="mb-2 block
                                       text-sm font-semibold
                                       text-zinc-800"
                            >
                                Title
                            </label>

                            <input
                                id="media-title"
                                type="text"
                                maxlength="255"
                                wire:model="title"
                                placeholder="Optional descriptive title"
                                class="w-full rounded-xl
                                       border border-zinc-300
                                       bg-white px-4 py-3
                                       text-sm"
                            >

                            <p
                                class="mt-2 text-xs
                                       text-zinc-500"
                            >
                                If empty, AWCMS creates a
                                title from the original
                                filename.
                            </p>

                            @error('title')
                                <p
                                    class="mt-2 text-sm
                                           font-medium
                                           text-red-600"
                                >
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        @if ($type === 'image')
                            <div>
                                <label
                                    for="media-alt"
                                    class="mb-2 block
                                           text-sm font-semibold
                                           text-zinc-800"
                                >
                                    Alternative Text
                                </label>

                                <input
                                    id="media-alt"
                                    type="text"
                                    maxlength="255"
                                    wire:model="altText"
                                    placeholder="Describe the image..."
                                    class="w-full rounded-xl
                                           border border-zinc-300
                                           bg-white px-4 py-3
                                           text-sm"
                                >

                                <p
                                    class="mt-2 text-xs
                                           text-zinc-500"
                                >
                                    Use a concise description
                                    for accessibility.
                                </p>

                                @error('altText')
                                    <p
                                        class="mt-2 text-sm
                                               font-medium
                                               text-red-600"
                                    >
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        @endif

                        <div>
                            <label
                                for="media-caption"
                                class="mb-2 block
                                       text-sm font-semibold
                                       text-zinc-800"
                            >
                                Caption
                            </label>

                            <textarea
                                id="media-caption"
                                rows="4"
                                maxlength="2000"
                                wire:model="caption"
                                placeholder="Optional caption or description..."
                                class="w-full resize-y
                                       rounded-xl
                                       border border-zinc-300
                                       bg-white px-4 py-3
                                       text-sm leading-6"
                            ></textarea>

                            @error('caption')
                                <p
                                    class="mt-2 text-sm
                                           font-medium
                                           text-red-600"
                                >
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </section>
            </div>

            {{-- Preview --}}
            <aside class="space-y-6">
                <section
                    class="overflow-hidden
                           rounded-2xl
                           border border-zinc-200
                           bg-white shadow-sm"
                >
                    <div
                        class="border-b border-zinc-200
                               bg-zinc-50
                               px-5 py-4"
                    >
                        <h2
                            class="font-bold
                                   text-zinc-900"
                        >
                            Preview
                        </h2>
                    </div>

                    <div class="p-5">
                        @if ($previewUrl)
                            <img
                                src="{{ $previewUrl }}"
                                alt=""
                                class="max-h-80
                                       w-full
                                       rounded-xl
                                       object-contain
                                       bg-zinc-100"
                            >

                            <p
                                class="mt-3 text-center
                                       text-xs
                                       text-zinc-500"
                            >
                                Temporary image preview
                            </p>
                        @elseif ($selectedFileName)
                            <div
                                class="flex min-h-52
                                       flex-col
                                       items-center
                                       justify-center
                                       rounded-xl
                                       bg-zinc-100
                                       px-5 text-center"
                            >
                                <div
                                    class="rounded-xl
                                           bg-white
                                           px-4 py-3
                                           text-xl
                                           font-black
                                           text-red-700
                                           shadow-sm"
                                >
                                    PDF
                                </div>

                                <p
                                    class="mt-4
                                           break-all
                                           text-sm
                                           font-semibold
                                           text-zinc-800"
                                >
                                    {{ $selectedFileName }}
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           text-zinc-500"
                                >
                                    Document selected
                                </p>
                            </div>
                        @else
                            <div
                                class="flex min-h-52
                                       items-center
                                       justify-center
                                       rounded-xl
                                       border-2
                                       border-dashed
                                       border-zinc-200
                                       bg-zinc-50
                                       px-5 text-center"
                            >
                                <p
                                    class="text-sm
                                           text-zinc-400"
                                >
                                    Select a file to see
                                    its preview.
                                </p>
                            </div>
                        @endif
                    </div>
                </section>

                <section
                    class="rounded-2xl border
                           border-zinc-200
                           bg-white p-5 shadow-sm"
                >
                    <h2
                        class="text-sm font-bold
                               text-zinc-900"
                    >
                        Storage Rules
                    </h2>

                    <div
                        class="mt-4 space-y-3
                               text-xs leading-5
                               text-zinc-600"
                    >
                        <div
                            class="rounded-lg
                                   bg-emerald-50
                                   p-3"
                        >
                            <strong>Public</strong>
                            <br>
                            Public website media.
                        </div>

                        <div
                            class="rounded-lg
                                   bg-amber-50
                                   p-3"
                        >
                            <strong>Internal</strong>
                            <br>
                            Stored on the private disk.
                        </div>

                        <div
                            class="rounded-lg
                                   bg-red-50
                                   p-3"
                        >
                            <strong>Restricted</strong>
                            <br>
                            Stored on the private disk
                            and requires controlled access.
                        </div>
                    </div>
                </section>
            </aside>
        </div>

        {{-- Actions --}}
        <div
            class="flex flex-col gap-3
                   rounded-2xl border
                   border-zinc-200
                   bg-white p-5
                   shadow-sm
                   sm:flex-row
                   sm:items-center
                   sm:justify-between"
        >
            <p
                class="text-xs leading-5
                       text-zinc-500"
            >
                The file is validated again using
                server-side MIME detection before
                permanent storage.
            </p>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex
                       items-center
                       justify-center
                       rounded-xl
                       bg-emerald-700
                       px-5 py-3
                       text-sm font-bold
                       text-white
                       hover:bg-emerald-800
                       disabled:cursor-not-allowed
                       disabled:opacity-60"
            >
                <span
                    wire:loading.remove
                    wire:target="save"
                >
                    Upload Media
                </span>

                <span
                    wire:loading
                    wire:target="save"
                >
                    Processing...
                </span>
            </button>
        </div>
    </form>
</div>
