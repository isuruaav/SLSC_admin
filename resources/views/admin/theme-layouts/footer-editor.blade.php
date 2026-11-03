<div wire:key="footer-editor-{{ $themeSlug }}-{{ $locale }}" wire:ignore
    x-data="awcmsFooterEditor($wire, @js($footerConfig))"
    @footer-editor-sync.window="if (!flush()) $event.preventDefault()" class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-zinc-950">Footer visual editor</h2>
            <p class="mt-1 text-sm text-zinc-500">Arrange columns and edit text, links and CMS blocks. Visual and HTML / CSS share the same draft.</p>
        </div>
        <div class="flex rounded-xl bg-zinc-100 p-1" role="tablist" aria-label="Footer editor mode">
            <button type="button" @click="switchMode('visual')" role="tab" :aria-selected="mode === 'visual'" :class="mode === 'visual' ? 'bg-white text-emerald-800 shadow-sm' : 'text-zinc-600'" class="rounded-lg px-4 py-2 text-sm font-bold"><i class="fa-solid fa-wand-magic-sparkles mr-1.5" aria-hidden="true"></i>Visual</button>
            <button type="button" @click="switchMode('code')" role="tab" :aria-selected="mode === 'code'" :class="mode === 'code' ? 'bg-white text-emerald-800 shadow-sm' : 'text-zinc-600'" class="rounded-lg px-4 py-2 text-sm font-bold"><i class="fa-solid fa-code mr-1.5" aria-hidden="true"></i>HTML / CSS</button>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3">
        <p class="text-xs text-zinc-600">{{ strtoupper($locale) }} footer draft. Changes appear on the website after publishing.</p>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="undo(-1)" :disabled="historyIndex <= 0" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold disabled:opacity-40"><i class="fa-solid fa-rotate-left mr-1" aria-hidden="true"></i>Undo</button>
            <button type="button" @click="undo(1)" :disabled="historyIndex >= history.length - 1" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold disabled:opacity-40"><i class="fa-solid fa-rotate-right mr-1" aria-hidden="true"></i>Redo</button>
            <button type="button" @click="defaults()" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold">Use default footer</button>
        </div>
    </div>

    @if ($locale !== 'en')
        <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Until this language has a published footer, the website uses the published English footer.</p>
    @endif

    <div x-show="mode === 'visual'" class="space-y-5">
        <div x-show="problem" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p x-text="problem"></p>
            <button type="button" @click="switchMode('code')" class="mt-2 font-bold underline">Open HTML / CSS</button>
        </div>
        <div x-show="!problem" class="space-y-5">
            <section class="rounded-xl border border-zinc-200 p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div><h3 class="text-sm font-bold text-zinc-900">Footer columns</h3><p class="mt-1 text-xs text-zinc-500">Select a column here or in the preview. Drag columns to reorder them.</p></div>
                    <button type="button" @click="addColumn()" :disabled="!hasGrid" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white disabled:opacity-40"><i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>Add column</button>
                </div>
                <div x-ref="columnList" class="flex flex-wrap gap-2">
                    <template x-for="column in columns" :key="column.index">
                        <div :data-column-index="column.index" :class="columnIndex === column.index ? 'border-emerald-500 bg-emerald-50' : 'border-zinc-200 bg-white'" class="flex items-center gap-1 rounded-lg border p-1">
                            <button type="button" :disabled="!column.movable" class="footer-drag cursor-grab px-2 py-3 text-zinc-400 disabled:cursor-default disabled:opacity-25" aria-label="Drag footer column"><i class="fa-solid fa-grip-vertical" aria-hidden="true"></i></button>
                            <button type="button" @click="selectColumn(column.index)" :aria-pressed="columnIndex === column.index" class="max-w-48 truncate px-2 py-2 text-sm font-bold text-zinc-700" x-text="column.label"></button>
                            <button type="button" @click="reorderColumns(column.index, column.index - 1)" :disabled="!canMoveColumn(column.index, -1)" class="size-8 rounded text-zinc-500 disabled:opacity-25" aria-label="Move column left"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                            <button type="button" @click="reorderColumns(column.index, column.index + 1)" :disabled="!canMoveColumn(column.index, 1)" class="size-8 rounded text-zinc-500 disabled:opacity-25" aria-label="Move column right"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                        </div>
                    </template>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="overflow-hidden rounded-xl border border-zinc-200">
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3">
                        <h3 class="text-sm font-bold text-zinc-900">Column blocks <span class="ml-1 text-zinc-400" x-text="blocks.length"></span></h3>
                        <button type="button" x-show="selectedColumn()?.movable" @click="removeColumn()" class="rounded-lg px-3 py-2 text-xs font-bold text-red-700">Remove column</button>
                    </div>
                    <div class="space-y-2 p-3">
                        <template x-for="block in blocks" :key="block.index">
                            <button type="button" @click="selectBlock(block.index)" :aria-pressed="blockIndex === block.index" :class="blockIndex === block.index ? 'border-emerald-500 bg-emerald-50' : 'border-zinc-200 bg-white'" class="flex w-full items-center gap-3 rounded-lg border px-3 py-3 text-left">
                                <i :class="block.kind === 'link' || block.kind === 'brand' ? 'fa-solid fa-link' : block.kind === 'widget' ? 'fa-solid fa-cube' : block.kind === 'heading' ? 'fa-solid fa-heading' : 'fa-solid fa-align-left'" class="w-5 text-center text-emerald-700" aria-hidden="true"></i>
                                <span class="min-w-0"><span class="block truncate text-sm font-bold text-zinc-800" x-text="block.label"></span><span class="text-[11px] capitalize text-zinc-500" x-text="block.kind === 'widget' ? 'CMS block' : block.kind"></span></span>
                            </button>
                        </template>
                        <p x-show="!blocks.length" class="p-2 text-sm text-zinc-500">This column is empty. Add a block below.</p>
                    </div>
                    <div x-show="selectedColumn()" class="border-t border-zinc-200 bg-zinc-50 p-4">
                        <p class="mb-2 text-xs font-bold text-zinc-600">Add a block</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['heading' => 'Heading', 'text' => 'Text', 'link' => 'Link', 'site_logo' => 'Logo', 'site_name' => 'Site name', 'site_tagline' => 'Tagline', 'contact_details' => 'Contact details', 'social_links' => 'Social links'] as $kind => $label)
                                <button type="button" @click="addBlock('{{ $kind }}')" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold text-zinc-700"><i class="fa-solid fa-plus mr-1 text-emerald-700" aria-hidden="true"></i>{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-zinc-200 p-4">
                    <h3 class="mb-4 text-sm font-bold text-zinc-900">Block settings</h3>
                    <template x-if="selected()">
                        <div class="space-y-4">
                            <template x-if="selected().kind === 'widget'">
                                <div class="rounded-lg bg-blue-50 p-4 text-sm leading-6 text-blue-900">
                                    <p class="font-bold" x-text="selected().label"></p>
                                    <p>This block displays the current site information automatically.</p>
                                    @can('settings.manage')
                                        <a href="{{ route('admin.site-settings.index') }}" target="_blank" rel="noopener" class="mt-2 inline-block font-bold underline">Edit details in Site Settings <span class="sr-only">(opens a new tab)</span></a>
                                    @endcan
                                </div>
                            </template>
                            <template x-if="!['widget', 'brand'].includes(selected().kind)">
                                <label class="block text-xs font-bold text-zinc-600">Text
                                    <textarea x-ref="blockText" :value="selected().text" @input="update('text', $event.target.value)" rows="4" maxlength="5000" class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-normal"></textarea>
                                </label>
                            </template>
                            <template x-if="['link', 'brand'].includes(selected().kind)">
                                <label class="block text-xs font-bold text-zinc-600">Link address
                                    <input x-ref="blockAddress" :value="displayHref()" @change="update('href', $event.target.value)" maxlength="2048" placeholder="/contact or https://example.com" class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-normal">
                                </label>
                            </template>
                            <template x-if="selected().kind === 'link'">
                                <div class="space-y-4">
                                    <label class="block text-xs font-bold text-zinc-600">Choose a site page or section
                                        <select @change="useLink($event.target.value); $event.target.value = ''" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-normal">
                                            <option value="">Select a page</option>
                                            <optgroup label="Site sections">
                                                @foreach ($footerConfig['links'] as $index => $link)
                                                    <option value="{{ $index }}">{{ $link['label'] }}</option>
                                                @endforeach
                                            </optgroup>
                                            @if (count($footerConfig['pages']))
                                                <optgroup label="Published pages">
                                                    @foreach ($footerConfig['pages'] as $index => $page)
                                                        <option value="{{ count($footerConfig['links']) + $index }}">{{ $page['label'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                    </label>
                                    <label class="block text-xs font-bold text-zinc-600">Link icon
                                        <select :value="selected().icon" @change="update('icon', $event.target.value)" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-normal">
                                            <option value="">No icon</option>
                                            <template x-if="selected().icon && !@js(array_column($footerConfig['icons'], 'class')).includes(selected().icon)"><option :value="selected().icon">Current icon</option></template>
                                            @foreach ($footerConfig['icons'] as $icon)
                                                <option value="{{ $icon['class'] }}">{{ $icon['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            </template>
                            <div class="flex flex-wrap justify-between gap-2 border-t border-zinc-100 pt-4">
                                <div class="flex gap-2">
                                    <button type="button" @click="moveBlock(-1)" :disabled="!siblingBlock(-1)" class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-bold disabled:opacity-30" aria-label="Move block up"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                    <button type="button" @click="moveBlock(1)" :disabled="!siblingBlock(1)" class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-bold disabled:opacity-30" aria-label="Move block down"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                </div>
                                <button type="button" @click="removeBlock()" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700"><i class="fa-solid fa-trash-can mr-1" aria-hidden="true"></i>Remove block</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!selected()" class="text-sm text-zinc-500">Select a block to edit its content.</p>
                    <p x-show="error" x-text="error" role="alert" class="mt-3 text-xs font-semibold text-red-700"></p>
                </section>
            </div>

            <fieldset class="rounded-xl border border-zinc-200 p-4">
                <legend class="px-2 text-sm font-bold text-zinc-900">Footer appearance</legend>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach (['background' => 'Background', 'color' => 'Text', 'heading' => 'Headings', 'accent' => 'Icons / hover'] as $key => $label)
                        <label class="text-xs font-bold text-zinc-600">{{ $label }}<input type="color" x-model="style.{{ $key }}" class="mt-2 block h-10 w-full cursor-pointer rounded-lg border border-zinc-200 bg-white p-1"></label>
                    @endforeach
                    @foreach (['size' => ['Font size', 11, 24], 'gap' => ['Column spacing', 0, 64], 'padding' => ['Top padding', 0, 100], 'columns' => ['Desktop columns', 1, 6]] as $key => [$label, $min, $max])
                        <label class="text-xs font-bold text-zinc-600">{{ $label }} <span x-text="style.{{ $key }} + '{{ $key === 'columns' ? '' : 'px' }}'"></span><input type="range" min="{{ $min }}" max="{{ $max }}" x-model.number="style.{{ $key }}" class="mt-4 w-full accent-emerald-700"></label>
                    @endforeach
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-zinc-500">Apply these settings to restyle the footer. Tablet and mobile columns adapt automatically.</p><button type="button" @click="applyStyle()" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white">Apply appearance</button></div>
            </fieldset>
        </div>
    </div>

    <div x-show="mode === 'code'" x-cloak class="space-y-4">
        <p class="rounded-lg bg-blue-50 p-3 text-xs leading-5 text-blue-900">Edit this footer's source. CMS placeholders are supported. PHP, scripts and unsafe HTML / CSS are blocked when saving and previewing.</p>
        <label class="block text-sm font-bold text-zinc-800">Footer HTML<textarea x-model="html" @input.debounce.400ms="codeChanged()" rows="20" spellcheck="false" class="mt-2 w-full rounded-xl border border-zinc-300 bg-zinc-950 p-4 font-mono text-xs font-normal leading-6 text-zinc-100"></textarea></label>
        <label class="block text-sm font-bold text-zinc-800">Footer CSS<textarea x-model="css" @input.debounce.400ms="codeChanged()" rows="12" spellcheck="false" class="mt-2 w-full rounded-xl border border-zinc-300 bg-zinc-950 p-4 font-mono text-xs font-normal leading-6 text-zinc-100"></textarea></label>
    </div>

    @include('admin.theme-layouts.layout-preview', ['previewTitle' => 'Footer preview', 'previewHint' => 'Click a footer column to select it. Check the layout at each screen size.', 'showMobileToggle' => false])
</div>
