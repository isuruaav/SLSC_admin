<div wire:key="navigation-editor-{{ $themeSlug }}-{{ $locale }}" wire:ignore
    x-data="awcmsNavigationEditor($wire, @js($navigationConfig))"
    @navigation-editor-sync.window="if (!flush()) $event.preventDefault()" class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-zinc-950">Navigation editor</h2>
            <p class="mt-1 text-sm text-zinc-500">Build your menu visually or edit its HTML and CSS. Both modes share the same draft.</p>
        </div>
        <div class="flex rounded-xl bg-zinc-100 p-1" role="tablist" aria-label="Navigation editor mode">
            <button type="button" @click="switchMode('visual')" role="tab" :aria-selected="mode === 'visual'"
                :class="mode === 'visual' ? 'bg-white text-emerald-800 shadow-sm' : 'text-zinc-600'"
                class="rounded-lg px-4 py-2 text-sm font-bold"><i class="fa-solid fa-wand-magic-sparkles mr-1.5" aria-hidden="true"></i>Visual</button>
            <button type="button" @click="switchMode('code')" role="tab" :aria-selected="mode === 'code'"
                :class="mode === 'code' ? 'bg-white text-emerald-800 shadow-sm' : 'text-zinc-600'"
                class="rounded-lg px-4 py-2 text-sm font-bold"><i class="fa-solid fa-code mr-1.5" aria-hidden="true"></i>HTML / CSS</button>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3">
        <p class="text-xs text-zinc-600">Changes stay in this draft until you publish. Desktop and mobile menu links are updated together.</p>
        <div class="flex items-center gap-2">
            <button type="button" @click="undo(-1)" :disabled="historyIndex <= 0" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold disabled:opacity-40"><i class="fa-solid fa-rotate-left mr-1" aria-hidden="true"></i>Undo</button>
            <button type="button" @click="undo(1)" :disabled="historyIndex >= history.length - 1" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold disabled:opacity-40"><i class="fa-solid fa-rotate-right mr-1" aria-hidden="true"></i>Redo</button>
            <button type="button" @click="defaults()" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold"><i class="fa-solid fa-arrow-rotate-left mr-1" aria-hidden="true"></i>Use default navbar</button>
        </div>
    </div>

    <div x-show="mode === 'visual'" class="space-y-5">
        <p x-show="/\[\[(primary_menu|responsive_primary_navigation)\]\]/i.test(html)" class="rounded-lg bg-blue-50 p-3 text-xs text-blue-900">This header uses the shared site menu. Editing its links visually will create a menu for this header language.</p>
        <template x-if="navigationChoices().length > 1">
            <label class="block text-sm font-bold text-zinc-700">Navigation area
                <select x-model.number="navIndex" @change="read()" class="mt-2 rounded-lg border border-zinc-300 p-2 text-sm">
                    <template x-for="choice in navigationChoices()" :key="choice.index"><option :value="choice.index" x-text="choice.label"></option></template>
                </select>
            </label>
        </template>

        <div x-show="problem" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p x-text="problem"></p>
            <button type="button" @click="switchMode('code')" class="mt-2 font-bold underline">Open HTML / CSS</button>
        </div>

        <div x-show="!problem" class="grid gap-5 lg:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-zinc-200">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3">
                    <div>
                        <h3 class="text-sm font-bold text-zinc-900">Menu links <span class="ml-1 text-zinc-400" x-text="items.length"></span></h3>
                        <p class="mt-1 text-xs text-zinc-500">Drag to reorder. Choose a parent to create a submenu.</p>
                    </div>
                    <button type="button" @click="add()" class="whitespace-nowrap rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white"><i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>Add link</button>
                </div>
                <div x-ref="menuList" class="space-y-2 p-3">
                    <template x-for="item in rows()" :key="item.id">
                        <div :data-menu-id="item.id" :style="{ marginLeft: `${item.depth * 18}px` }"
                            :class="selectedId === item.id ? 'border-emerald-500 bg-emerald-50' : 'border-zinc-200 bg-white'"
                            class="flex items-center gap-2 rounded-lg border p-2">
                            <button type="button" class="navigation-drag cursor-grab px-1 py-3 text-zinc-400" aria-label="Drag menu link" title="Drag to reorder"><i class="fa-solid fa-grip-vertical" aria-hidden="true"></i></button>
                            <button type="button" @click="selectedId = item.id" :aria-pressed="selectedId === item.id" class="min-w-0 flex-1 py-1 text-left">
                                <span class="flex items-center gap-2"><i :class="item.icon || 'fa-solid fa-link'" class="w-5 shrink-0 text-center text-emerald-700" aria-hidden="true"></i><span class="truncate text-sm font-bold text-zinc-800" x-text="item.label"></span></span>
                                <span class="block truncate text-[11px] text-zinc-500" x-text="item.depth ? 'Submenu' : 'Main menu'"></span>
                            </button>
                            <button type="button" @click="move(item.id, -1)" :disabled="!canMove(item.id, -1)" class="size-9 rounded text-zinc-500 hover:bg-zinc-100 disabled:opacity-25" aria-label="Move menu link up"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                            <button type="button" @click="move(item.id, 1)" :disabled="!canMove(item.id, 1)" class="size-9 rounded text-zinc-500 hover:bg-zinc-100 disabled:opacity-25" aria-label="Move menu link down"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                        </div>
                    </template>
                </div>
                <p x-show="items.length === 0" class="px-5 pb-5 text-sm text-zinc-500">Your menu is empty. Add a link to get started.</p>
            </section>

            <section class="rounded-xl border border-zinc-200 p-4">
                <h3 class="mb-4 text-sm font-bold text-zinc-900">Link settings</h3>
                <template x-if="selected()">
                    <div class="space-y-4">
                        <label class="block text-xs font-bold text-zinc-600">Choose a site page or section
                            <select @change="useLink($event.target.value); $event.target.value = ''" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-normal">
                                <option value="">Select a page or enter a custom link below</option>
                                <optgroup label="Site sections">
                                    @foreach ($navigationConfig['links'] as $index => $link)
                                        <option value="{{ $index }}">{{ $link['label'] }}</option>
                                    @endforeach
                                </optgroup>
                                @if (count($navigationConfig['pages']))
                                    <optgroup label="Published pages">
                                        @foreach ($navigationConfig['pages'] as $index => $page)
                                            <option value="{{ count($navigationConfig['links']) + $index }}">{{ $page['label'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </label>
                        <label class="block text-xs font-bold text-zinc-600">Link name
                            <input x-ref="linkLabel" :value="selected().label" @change="update('label', $event.target.value)" maxlength="150" class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-normal">
                        </label>
                        <label class="block text-xs font-bold text-zinc-600">Link address
                            <input x-ref="linkAddress" :value="displayHref()" @change="update('href', $event.target.value)" maxlength="2048" placeholder="/courses or https://example.com" class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-normal">
                            <span class="mt-1 block text-[11px] font-normal text-zinc-500">Use # for a dropdown heading. Local paths work when the site address changes.</span>
                        </label>
                        <label class="block text-xs font-bold text-zinc-600">Parent menu
                            <select x-model="selected().parentId" @change="write()" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-normal">
                                <option value="" :selected="selected().parentId === ''">None — main menu link</option>
                                <template x-for="parent in parentChoices()" :key="parent.id"><option :value="parent.id" :selected="selected().parentId === parent.id" x-text="'— '.repeat(parent.depth) + parent.label"></option></template>
                            </select>
                        </label>
                        <div class="space-y-2">
                            <p class="text-xs font-bold text-zinc-600">Link icon</p>
                            <button x-ref="iconPickerToggle" type="button" @click="iconPickerOpen = !iconPickerOpen; if (iconPickerOpen) $nextTick(() => $refs.iconSearchField?.focus())" :aria-expanded="iconPickerOpen"
                                class="flex min-h-11 w-full items-center gap-3 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-left text-sm">
                                <span class="grid size-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i :class="selected().icon || 'fa-solid fa-ban'" aria-hidden="true"></i></span>
                                <span class="flex-1" x-text="iconName()"></span><i class="fa-solid fa-chevron-down text-xs text-zinc-400" aria-hidden="true"></i>
                            </button>
                            <div x-show="iconPickerOpen" x-cloak @keydown.escape.stop.prevent="iconPickerOpen = false; $refs.iconPickerToggle.focus()" class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                <label class="relative block">
                                    <span class="sr-only">Search Font Awesome icons</span>
                                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-3 text-zinc-400" aria-hidden="true"></i>
                                    <input x-ref="iconSearchField" type="search" x-model="iconSearch" placeholder="Search icons…" class="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-9 pr-3 text-sm">
                                </label>
                                <div class="mt-3 grid max-h-60 grid-cols-3 gap-2 overflow-y-auto p-1" aria-label="Choose a Font Awesome icon">
                                    <button type="button" @click="chooseIcon('')" :aria-pressed="!selected().icon" class="flex min-h-16 flex-col items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white p-2 text-xs hover:border-emerald-400"><i class="fa-solid fa-ban text-lg text-zinc-400" aria-hidden="true"></i>No icon</button>
                                    <template x-for="icon in filteredIcons()" :key="icon.class">
                                        <button type="button" @click="chooseIcon(icon.class)" :aria-label="'Choose ' + icon.label + ' icon'" :aria-pressed="selected().icon === icon.class"
                                            :class="selected().icon === icon.class ? 'border-emerald-500 bg-emerald-50 text-emerald-800' : 'border-zinc-200 bg-white text-zinc-600'"
                                            class="flex min-h-16 flex-col items-center justify-center gap-2 rounded-lg border p-2 text-xs hover:border-emerald-400">
                                            <i :class="icon.class" class="text-lg" aria-hidden="true"></i><span x-text="icon.label"></span>
                                        </button>
                                    </template>
                                </div>
                                <p x-show="filteredIcons().length === 0" class="mt-3 text-xs text-zinc-500">No matching icons. Try another name.</p>
                            </div>
                        </div>
                        <p x-show="error" x-text="error" role="alert" class="text-xs font-semibold text-red-700"></p>
                        <div class="flex flex-wrap justify-between gap-2 border-t border-zinc-100 pt-4">
                            <button type="button" @click="add(selectedId)" class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-800"><i class="fa-solid fa-code-branch mr-1" aria-hidden="true"></i>Add submenu</button>
                            <button type="button" @click="remove()" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700"><i class="fa-solid fa-trash-can mr-1" aria-hidden="true"></i>Remove link</button>
                        </div>
                    </div>
                </template>
                <p x-show="!selected()" class="text-sm text-zinc-500">Select a menu link to edit it.</p>
            </section>
        </div>

        <fieldset x-show="!problem" class="rounded-xl border border-zinc-200 p-4">
            <legend class="px-2 text-sm font-bold text-zinc-900">Navigation appearance</legend>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach (['color' => 'Text', 'background' => 'Background', 'accent' => 'Hover color'] as $key => $label)
                    <label class="text-xs font-bold text-zinc-600">{{ $label }}
                        <input type="color" x-model="style.{{ $key }}" @change="applyStyle()" class="mt-2 block h-10 w-full cursor-pointer rounded-lg border border-zinc-200 bg-white p-1">
                    </label>
                @endforeach
                <label class="text-xs font-bold text-zinc-600">Font size <span x-text="style.size + 'px'"></span>
                    <input type="range" min="11" max="24" x-model.number="style.size" @change="applyStyle()" class="mt-4 w-full accent-emerald-700">
                </label>
                <label class="text-xs font-bold text-zinc-600">Link spacing <span x-text="style.gap + 'px'"></span>
                    <input type="range" min="0" max="48" x-model.number="style.gap" @change="applyStyle()" class="mt-4 w-full accent-emerald-700">
                </label>
            </div>
        </fieldset>
    </div>

    <div x-show="mode === 'code'" x-cloak class="space-y-4">
        <p class="rounded-lg bg-blue-50 p-3 text-xs leading-5 text-blue-900">Edit the same header source here. Links inside a nav element appear in Visual mode. Custom layouts stay intact until you edit their menu visually. CMS placeholders such as [[site_name]] are supported.</p>
        <label class="block text-sm font-bold text-zinc-800">Header HTML
            <textarea x-model="html" @input.debounce.400ms="codeChanged('html')" rows="20" spellcheck="false" class="mt-2 w-full rounded-xl border border-zinc-300 bg-zinc-950 p-4 font-mono text-xs font-normal leading-6 text-zinc-100"></textarea>
        </label>
        <label class="block text-sm font-bold text-zinc-800">Header CSS
            <textarea x-model="css" @input.debounce.400ms="codeChanged()" rows="12" spellcheck="false" class="mt-2 w-full rounded-xl border border-zinc-300 bg-zinc-950 p-4 font-mono text-xs font-normal leading-6 text-zinc-100"></textarea>
        </label>
    </div>

    @include('admin.theme-layouts.layout-preview')
</div>
