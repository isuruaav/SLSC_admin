@props([
    'id',
    'model' => 'content',
    'modeModel' => 'editorMode',
    'value' => '',
    'editorMode' => 'visual',
    'showModeSwitcher' => true,
])

@php
    $appBasePath = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');
    $appBaseUrl = url('/');
    $quickUploadUrl = route('admin.media.quick-upload');
    $recentImagesUrl = route('admin.media.recent-images');
@endphp

<div data-draft-model="{{ $model }}"
    x-data="awcmsPageContentEditor(@js($value), @js($editorMode), {
        basePath: @js($appBasePath),
        baseUrl: @js($appBaseUrl),
        uploadUrl: @js($quickUploadUrl),
        recentImagesUrl: @js($recentImagesUrl),
        csrfToken: @js(csrf_token()),
    })"
    x-on:keydown.escape.window="if (fullscreen) toggleFullscreen(); if (imageModalOpen) closeImageModal()"
    x-bind:class="fullscreen ? 'awcms-page-editor-shell--fullscreen' : ''"
    class="awcms-page-editor-shell relative">
    <input x-ref="contentInput" type="hidden" wire:model="{{ $model }}">

    <input x-ref="modeInput" type="hidden" wire:model="{{ $modeModel }}">

    @if ($showModeSwitcher)
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="inline-flex w-fit rounded-xl border border-zinc-200 bg-zinc-100 p-1">
                <button type="button" x-on:click="switchMode('visual')"
                    x-bind:class="mode === 'visual'
                        ?
                        'bg-white text-emerald-800 shadow-sm' :
                        'text-zinc-600 hover:text-zinc-900'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition">
                    Visual Editor
                </button>

                <button type="button" x-on:click="switchMode('html')"
                    x-bind:class="mode === 'html'
                        ?
                        'bg-white text-emerald-800 shadow-sm' :
                        'text-zinc-600 hover:text-zinc-900'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition">
                    HTML + Tailwind
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                <span class="rounded-full bg-zinc-100 px-3 py-1.5 font-semibold text-zinc-700">
                    One content source
                </span>

                <span>
                    Both editors work with the same saved HTML.
                </span>
            </div>
        </div>
    @endif
    <div x-cloak x-show="advancedWarning && mode === 'visual'"
        class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4">
        <p class="text-sm font-semibold text-amber-950">
            Advanced HTML/Tailwind is open in the Visual Editor
        </p>

        <p class="mt-1 text-sm leading-6 text-amber-800">
            You can view and make simple text changes here, but complex custom layouts are safest in
            HTML + Tailwind. Switching tabs does not duplicate the page content.
        </p>
    </div>

    <div x-show="mode === 'visual'" class="space-y-3">
        <div class="awcms-page-editor-toolbar" role="toolbar" aria-label="Visual editor formatting toolbar"
            x-on:pointerdown.capture="captureToolbarSelection($event)">
            <div class="awcms-page-editor-toolbar-group">
                <select aria-label="Heading level" title="Heading level" class="awcms-page-editor-select"
                    x-on:mousedown="rememberSelection()"
                    x-on:change="formatBlock($event.target.value); $event.target.value = 'P'">
                    <option value="P">Paragraph</option>
                    <option value="H1">Heading 1</option>
                    <option value="H2">Heading 2</option>
                    <option value="H3">Heading 3</option>
                </select>
            </div>

            <div class="awcms-page-editor-toolbar-group">
                <button type="button" class="awcms-page-editor-button font-bold" title="Bold" aria-label="Bold"
                    x-on:mousedown.prevent="runCommand('bold')">B</button>
                <button type="button" class="awcms-page-editor-button italic" title="Italic" aria-label="Italic"
                    x-on:mousedown.prevent="runCommand('italic')">I</button>
                <button type="button" class="awcms-page-editor-button underline" title="Underline"
                    aria-label="Underline" x-on:mousedown.prevent="runCommand('underline')">U</button>
            </div>

            <div class="awcms-page-editor-toolbar-group">
                <label class="awcms-page-editor-colour" title="Text colour">
                    <span>A</span>
                    <input type="color" value="#18181b" aria-label="Text colour"
                        x-on:pointerdown="rememberSelection()" x-on:change="setTextColor($event.target.value)">
                </label>

                <label class="awcms-page-editor-colour" title="Background colour">
                    <span class="rounded bg-amber-100 px-1">A</span>
                    <input type="color" value="#fef3c7" aria-label="Background colour"
                        x-on:pointerdown="rememberSelection()" x-on:change="setBackgroundColor($event.target.value)">
                </label>
            </div>

            <div class="awcms-page-editor-toolbar-group">
                <button type="button" class="awcms-page-editor-button" title="Align left" aria-label="Align left"
                    x-on:mousedown.prevent="runCommand('justifyLeft')">L</button>
                <button type="button" class="awcms-page-editor-button" title="Align centre" aria-label="Align centre"
                    x-on:mousedown.prevent="runCommand('justifyCenter')">C</button>
                <button type="button" class="awcms-page-editor-button" title="Align right" aria-label="Align right"
                    x-on:mousedown.prevent="runCommand('justifyRight')">R</button>
                <button type="button" class="awcms-page-editor-button" title="Justify" aria-label="Justify"
                    x-on:mousedown.prevent="runCommand('justifyFull')">J</button>
            </div>

            <div class="awcms-page-editor-toolbar-group">
                <button type="button" class="awcms-page-editor-button" title="Bullet list" aria-label="Bullet list"
                    x-on:mousedown.prevent="runCommand('insertUnorderedList')">• List</button>
                <button type="button" class="awcms-page-editor-button" title="Numbered list"
                    aria-label="Numbered list" x-on:mousedown.prevent="runCommand('insertOrderedList')">1.
                    List</button>
            </div>

            <div class="awcms-page-editor-toolbar-group">
                <button type="button" class="awcms-page-editor-button" title="Insert link" aria-label="Insert link"
                    x-on:mousedown.prevent="rememberSelection()" x-on:click="createLink()">Link</button>
                <button type="button" class="awcms-page-editor-button flex items-center gap-1 font-medium text-emerald-800"
                    title="Insert image / upload" aria-label="Insert image"
                    x-on:mousedown.prevent="rememberSelection()"
                    x-on:click="openImageModal()">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Image</span>
                </button>
                <button type="button" class="awcms-page-editor-button" title="Insert document"
                    aria-label="Insert document" x-on:mousedown.prevent="rememberSelection()"
                    x-on:click="insertDocument()">Document</button>
                <button type="button" class="awcms-page-editor-button" title="Insert table"
                    aria-label="Insert table" x-on:mousedown.prevent="rememberSelection()"
                    x-on:click="insertTable()">Table</button>
                <button type="button" class="awcms-page-editor-button" title="Quote" aria-label="Quote"
                    x-on:mousedown.prevent="formatBlock('BLOCKQUOTE')">Quote</button>
            </div>

            <div class="awcms-page-editor-toolbar-group">
                <button type="button" class="awcms-page-editor-button" title="Undo" aria-label="Undo"
                    x-on:mousedown.prevent="runCommand('undo')">Undo</button>
                <button type="button" class="awcms-page-editor-button" title="Redo" aria-label="Redo"
                    x-on:mousedown.prevent="runCommand('redo')">Redo</button>
                <button type="button" class="awcms-page-editor-button" title="Clear formatting"
                    aria-label="Clear formatting" x-on:mousedown.prevent="clearFormatting()">Clear</button>
            </div>

            <div class="awcms-page-editor-toolbar-group ml-auto">
                <button type="button" class="awcms-page-editor-button" x-on:click="toggleFullscreen()"
                    x-text="fullscreen ? 'Exit Full Screen' : 'Full Screen'"></button>
            </div>
        </div>

        {{-- Visual Editor Canvas with Floating Controls & Drop Indicator --}}
        <div class="relative">
            {{-- Visual Drop Indicator Line --}}
            <div x-ref="dropIndicator" class="awcms-editor-drop-indicator" style="display: none;"></div>

            {{-- Floating Toolbar for Clicked/Selected Image --}}
            <div x-cloak x-show="selectedImage !== null && mode === 'visual'"
                x-ref="floatingToolbar"
                class="awcms-image-floating-toolbar"
                :style="`top: ${floatingToolbarPos.top}px; left: ${floatingToolbarPos.left}px;`">
                <button type="button" x-on:click="setImageAlign('left')"
                    :class="imageAlign === 'left' ? 'active' : ''"
                    title="Float Left (Text wraps around right)">
                    <span>⬅ Left Wrap</span>
                </button>
                <button type="button" x-on:click="setImageAlign('center')"
                    :class="imageAlign === 'center' ? 'active' : ''"
                    title="Center on its own line">
                    <span>⏹ Center</span>
                </button>
                <button type="button" x-on:click="setImageAlign('right')"
                    :class="imageAlign === 'right' ? 'active' : ''"
                    title="Float Right (Text wraps around left)">
                    <span>Right Wrap ➡</span>
                </button>
                <button type="button" x-on:click="setImageAlign('full')"
                    :class="imageAlign === 'full' ? 'active' : ''"
                    title="Full Width Block">
                    <span>↔ Full</span>
                </button>

                <div class="toolbar-divider"></div>

                <button type="button" x-on:click="setImageWidth(25)" title="25% width">25%</button>
                <button type="button" x-on:click="setImageWidth(50)" title="50% width">50%</button>
                <button type="button" x-on:click="setImageWidth(75)" title="75% width">75%</button>
                <button type="button" x-on:click="setImageWidth(100)" title="100% width">100%</button>

                <div class="toolbar-divider"></div>

                <span class="inline-flex items-center gap-1 text-[10px] text-zinc-400 select-none cursor-grab" title="Drag this image anywhere in your content">
                    <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    Drag to move
                </span>

                <div class="toolbar-divider"></div>

                <button type="button" x-on:click="deleteSelectedImage()" class="hover:!bg-red-700" title="Delete image">
                    <svg class="h-3.5 w-3.5 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            <div id="{{ $id }}-visual" x-ref="visual" wire:ignore contenteditable="true" role="textbox"
                aria-multiline="true" aria-label="Page content visual editor"
                data-placeholder="Start typing your page content here..." class="awcms-page-visual-editor"
                x-on:input.debounce.120ms="syncFromVisual()" x-on:mouseup="rememberSelection()"
                x-on:keyup="rememberSelection()" x-on:focus="rememberSelection()"
                x-on:blur="rememberSelection(); syncFromVisual()"></div>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-semibold text-emerald-950">
                    Word-like editing & Drag-and-Drop Images
                </p>

                <p class="mt-1 text-sm leading-6 text-emerald-800">
                    Click an image to float left/right with text wrapping, center, resize, or grab and drag it anywhere across paragraphs.
                    You can also drop image files from your computer directly into the editor.
                </p>
            </div>

            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                <p class="text-sm font-semibold text-sky-950">
                    Direct Image Upload & Media Library
                </p>

                <p class="mt-1 text-sm leading-6 text-sky-800">
                    Click the <strong>Image</strong> button to upload an image directly, pick from the Media Library with visual previews, or paste a path. All media URLs automatically resolve without breaking.
                </p>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mode === 'html'" class="space-y-3">
        <div
            class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-zinc-900">
                    HTML + Tailwind source
                </p>

                <p class="mt-1 text-xs leading-5 text-zinc-500">
                    Edit the same page HTML directly. Tailwind classes are preserved on allowed elements.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                    class="inline-flex w-fit items-center justify-center rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100"
                    x-on:click="formatCode()">
                    Format Code
                </button>

                <button type="button"
                    class="inline-flex w-fit items-center justify-center rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100"
                    x-on:click="toggleFullscreen()" x-text="fullscreen ? 'Exit Full Screen' : 'Full Screen'"></button>
            </div>
        </div>

        <textarea id="{{ $id }}-html" x-ref="code" wire:ignore rows="24" spellcheck="false" wrap="off"
            placeholder='<section class="bg-slate-900 px-6 py-20 text-white">&#10;    <div class="mx-auto max-w-6xl">&#10;        <h1 class="text-4xl font-bold">Page heading</h1>&#10;    </div>&#10;</section>'
            class="awcms-page-code-editor" x-on:input.debounce.120ms="syncFromCode()" x-on:blur="syncFromCode()"></textarea>

        <div class="grid gap-3 lg:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-semibold text-emerald-950">
                    Developer mode
                </p>

                <p class="mt-1 text-sm leading-6 text-emerald-800">
                    Build responsive hero sections, grids, cards and custom layouts with semantic HTML and Tailwind CSS.
                </p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-950">
                    Security
                </p>

                <p class="mt-1 text-sm leading-6 text-amber-800">
                    Scripts, iframes, forms, JavaScript event attributes and arbitrary inline CSS are removed before storage.
                    Visual Editor inline styling is limited to safe layouts, alignments, colours and spacing.
                </p>
            </div>
        </div>
    </div>

    {{-- Interactive Image Insertion & Management Modal --}}
    <div x-cloak x-show="imageModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        role="dialog" aria-modal="true" aria-labelledby="image-modal-title">

        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl border border-zinc-200 flex flex-col max-h-[90vh]"
            x-on:click.outside="closeImageModal()">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 bg-zinc-50">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="image-modal-title" class="text-base font-bold text-zinc-900">Insert Image</h3>
                        <p class="text-xs text-zinc-500">Upload a new image, pick from Media Library, or provide a path</p>
                    </div>
                </div>
                <button type="button" x-on:click="closeImageModal()" class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-zinc-200 bg-zinc-100/70 px-6 pt-2">
                <button type="button" x-on:click="imageTab = 'upload'"
                    :class="imageTab === 'upload' ? 'border-emerald-600 text-emerald-700 font-semibold bg-white' : 'border-transparent text-zinc-600 hover:text-zinc-900'"
                    class="border-b-2 px-4 py-2.5 text-sm transition -mb-px rounded-t-lg">
                    📤 Upload Image
                </button>
                <button type="button" x-on:click="imageTab = 'library'; loadRecentImages()"
                    :class="imageTab === 'library' ? 'border-emerald-600 text-emerald-700 font-semibold bg-white' : 'border-transparent text-zinc-600 hover:text-zinc-900'"
                    class="border-b-2 px-4 py-2.5 text-sm transition -mb-px rounded-t-lg">
                    🖼️ Media Library
                </button>
                <button type="button" x-on:click="imageTab = 'url'"
                    :class="imageTab === 'url' ? 'border-emerald-600 text-emerald-700 font-semibold bg-white' : 'border-transparent text-zinc-600 hover:text-zinc-900'"
                    class="border-b-2 px-4 py-2.5 text-sm transition -mb-px rounded-t-lg">
                    🔗 Path / URL
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                {{-- TAB 1: UPLOAD --}}
                <div x-show="imageTab === 'upload'" class="space-y-4">
                    <p class="text-xs text-zinc-600">New images are saved to Media Library. A matching public image is reused instead of creating another copy.</p>
                    <div class="border-2 border-dashed border-zinc-300 rounded-xl p-6 text-center hover:border-emerald-500 transition cursor-pointer bg-zinc-50/50"
                        x-on:click="$refs.imageFileInput.click()"
                        x-on:dragover.prevent=""
                        x-on:drop.prevent="handleModalFileDrop($event)">
                        <input type="file" x-ref="imageFileInput" class="hidden" accept="image/*" x-on:change="handleModalFileSelect($event)">

                        <template x-if="!modalSelectedFile">
                            <div class="space-y-2">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-zinc-800">Click to choose an image or drag & drop here</p>
                                <p class="text-xs text-zinc-500">Supports JPG, PNG, GIF, WebP, SVG up to 20MB</p>
                            </div>
                        </template>

                        <template x-if="modalSelectedFile">
                            <div class="flex items-center gap-4 text-left">
                                <img :src="modalFilePreview" class="h-20 w-20 object-cover rounded-lg border border-zinc-200">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-zinc-900 truncate" x-text="modalSelectedFile.name"></p>
                                    <p class="text-xs text-zinc-500" x-text="formatBytes(modalSelectedFile.size)"></p>
                                    <span class="inline-block mt-1 text-xs text-emerald-700 font-medium bg-emerald-50 px-2 py-0.5 rounded">Ready to upload</span>
                                </div>
                                <button type="button" x-on:click.stop="clearModalFile()" class="text-zinc-400 hover:text-red-600 text-xs font-semibold p-1">Change</button>
                            </div>
                        </template>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 mb-1">Title / Caption</label>
                            <input type="text" x-model="modalImageTitle" placeholder="Image title" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 mb-1">Alt Text (Accessibility)</label>
                            <input type="text" x-model="modalImageAlt" placeholder="Short description of image" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 mb-1">Layout / Alignment</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button" x-on:click="modalImagePosition = 'left'"
                                :class="modalImagePosition === 'left' ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50'"
                                class="border rounded-lg p-2 text-xs font-medium text-center transition">
                                ⬅ Left Wrap
                            </button>
                            <button type="button" x-on:click="modalImagePosition = 'center'"
                                :class="modalImagePosition === 'center' ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50'"
                                class="border rounded-lg p-2 text-xs font-medium text-center transition">
                                ⏹ Center
                            </button>
                            <button type="button" x-on:click="modalImagePosition = 'right'"
                                :class="modalImagePosition === 'right' ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50'"
                                class="border rounded-lg p-2 text-xs font-medium text-center transition">
                                Right Wrap ➡
                            </button>
                            <button type="button" x-on:click="modalImagePosition = 'full'"
                                :class="modalImagePosition === 'full' ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50'"
                                class="border rounded-lg p-2 text-xs font-medium text-center transition">
                                ↔ Full Width
                            </button>
                        </div>
                    </div>

                    <div x-show="uploadError" class="rounded-lg bg-red-50 p-3 text-xs text-red-700 font-medium" x-text="uploadError"></div>
                </div>

                {{-- TAB 2: MEDIA LIBRARY --}}
                <div x-show="imageTab === 'library'" class="space-y-4">
                    <div class="flex items-center gap-2">
                        <input type="text" x-model="librarySearch" x-on:input.debounce.300ms="loadRecentImages()"
                            placeholder="Search images by name or title..."
                            class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <button type="button" x-on:click="loadRecentImages()" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-100">
                            Refresh
                        </button>
                    </div>

                    <p class="text-xs text-zinc-600">Choose an existing image to reuse it on this page. This does not upload another copy.</p>

                    <div x-show="loadingLibrary" class="py-12 text-center text-sm text-zinc-500">
                        Loading library images...
                    </div>

                    <div x-show="!loadingLibrary && libraryImages.length === 0" class="py-12 text-center text-sm text-zinc-500">
                        No images found. You can upload one in the Upload tab.
                    </div>

                    <div x-show="!loadingLibrary && libraryImages.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-3 max-h-72 overflow-y-auto p-1">
                        <template x-for="img in libraryImages" :key="img.id">
                            <div class="relative group rounded-lg border p-1 cursor-pointer transition text-left"
                                :class="selectedLibraryImage && selectedLibraryImage.id === img.id ? 'border-emerald-600 ring-2 ring-emerald-500 bg-emerald-50/30' : 'border-zinc-200 hover:border-zinc-400 bg-white'"
                                x-on:click="selectedLibraryImage = img; modalImageAlt = img.alt">
                                <div class="aspect-square w-full rounded overflow-hidden bg-zinc-100">
                                    <img :src="img.thumb || img.url" :alt="img.alt" class="h-full w-full object-cover">
                                </div>
                                <p class="mt-1 text-[11px] font-medium text-zinc-800 truncate" x-text="img.title || img.filename"></p>
                            </div>
                        </template>
                    </div>

                    <template x-if="selectedLibraryImage">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-emerald-950 truncate" x-text="'Selected: ' + (selectedLibraryImage.title || selectedLibraryImage.filename)"></span>
                                <span class="text-zinc-500 font-mono text-[11px]" x-text="selectedLibraryImage.path"></span>
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" x-on:click="modalImagePosition = 'left'"
                                    :class="modalImagePosition === 'left' ? 'border-emerald-600 bg-emerald-100 text-emerald-900' : 'border-zinc-200 bg-white text-zinc-700'"
                                    class="border rounded-lg p-1.5 text-xs font-medium text-center transition">
                                    ⬅ Left Wrap
                                </button>
                                <button type="button" x-on:click="modalImagePosition = 'center'"
                                    :class="modalImagePosition === 'center' ? 'border-emerald-600 bg-emerald-100 text-emerald-900' : 'border-zinc-200 bg-white text-zinc-700'"
                                    class="border rounded-lg p-1.5 text-xs font-medium text-center transition">
                                    ⏹ Center
                                </button>
                                <button type="button" x-on:click="modalImagePosition = 'right'"
                                    :class="modalImagePosition === 'right' ? 'border-emerald-600 bg-emerald-100 text-emerald-900' : 'border-zinc-200 bg-white text-zinc-700'"
                                    class="border rounded-lg p-1.5 text-xs font-medium text-center transition">
                                    Right Wrap ➡
                                </button>
                                <button type="button" x-on:click="modalImagePosition = 'full'"
                                    :class="modalImagePosition === 'full' ? 'border-emerald-600 bg-emerald-100 text-emerald-900' : 'border-zinc-200 bg-white text-zinc-700'"
                                    class="border rounded-lg p-1.5 text-xs font-medium text-center transition">
                                    ↔ Full Width
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- TAB 3: PATH / URL --}}
                <div x-show="imageTab === 'url'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 mb-1">Image Path or URL</label>
                        <input type="text" x-model="manualImageUrl" x-on:input.debounce.300ms="updateManualPreview()"
                            placeholder="e.g. /media-files/slsc.jpg or full URL"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <p class="mt-1 text-[11px] text-zinc-500">Paste <code>/media-files/slsc.jpg</code> or <code>slsc.jpg</code>. The site address is added automatically. External image URLs are also supported.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 mb-1">Alt Text (Accessibility)</label>
                        <input type="text" x-model="manualImageAlt" placeholder="Short description of image"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 mb-1">Layout / Alignment</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button" x-on:click="modalImagePosition = 'left'"
                                :class="modalImagePosition === 'left' ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50'"
                                class="border rounded-lg p-2 text-xs font-medium text-center transition">
                                ⬅ Left Wrap
                            </button>
                            <button type="button" x-on:click="modalImagePosition = 'center'"
                                :class="modalImagePosition === 'center' ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50'"
                                class="border rounded-lg p-2 text-xs font-medium text-center transition">
                                ⏹ Center
                            </button>
                            <button type="button" x-on:click="modalImagePosition = 'right'"
                                :class="modalImagePosition === 'right' ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50'"
                                class="border rounded-lg p-2 text-xs font-medium text-center transition">
                                Right Wrap ➡
                            </button>
                            <button type="button" x-on:click="modalImagePosition = 'full'"
                                :class="modalImagePosition === 'full' ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50'"
                                class="border rounded-lg p-2 text-xs font-medium text-center transition">
                                ↔ Full Width
                            </button>
                        </div>
                    </div>

                    {{-- Live Image Preview --}}
                    <div class="rounded-xl border border-zinc-200 p-4 bg-zinc-50">
                        <span class="block text-xs font-semibold text-zinc-500 mb-2">Live Preview:</span>
                        <div class="flex items-center justify-center min-h-32 rounded-lg bg-white border border-zinc-200 p-2 overflow-hidden">
                            <template x-if="manualImagePreviewSrc">
                                <img :src="manualImagePreviewSrc"
                                    x-on:load="manualPreviewLoaded = true; manualPreviewError = false"
                                    x-on:error="manualPreviewError = true; manualPreviewLoaded = false"
                                    class="max-h-48 object-contain rounded">
                            </template>
                            <template x-if="!manualImagePreviewSrc">
                                <span class="text-xs text-zinc-400">Type a valid path or URL above to preview</span>
                            </template>
                            <template x-if="manualPreviewError">
                                <span class="text-xs text-red-600 font-medium">Could not load image from this path. Check filename or URL.</span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4">
                <button type="button" x-on:click="closeImageModal()" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 transition">
                    Cancel
                </button>

                <template x-if="imageTab === 'upload'">
                    <button type="button" x-on:click="submitModalUpload()" :disabled="!modalSelectedFile || uploadingImage"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-600 disabled:opacity-50 transition">
                        <span x-show="uploadingImage" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span x-text="uploadingImage ? 'Uploading...' : 'Upload & Insert Image'"></span>
                    </button>
                </template>

                <template x-if="imageTab === 'library'">
                    <button type="button" x-on:click="submitModalLibrary()" :disabled="!selectedLibraryImage"
                        class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-600 disabled:opacity-50 transition">
                        Insert Selected Image
                    </button>
                </template>

                <template x-if="imageTab === 'url'">
                    <button type="button" x-on:click="submitModalUrl()" :disabled="!manualImageUrl || manualPreviewError"
                        class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-600 disabled:opacity-50 transition">
                        Insert Image
                    </button>
                </template>
            </div>
        </div>
    </div>

    @error($modeModel)
        <p class="mt-3 text-sm font-medium text-red-600">
            {{ $message }}
        </p>
    @enderror

    @error($model)
        <p class="mt-3 text-sm font-medium text-red-600">
            {{ $message }}
        </p>
    @enderror
</div>
