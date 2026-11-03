    <section class="overflow-hidden rounded-xl border border-zinc-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3">
            <div><h3 class="text-sm font-bold text-zinc-900"><i class="fa-solid fa-eye mr-1.5 text-emerald-700" aria-hidden="true"></i>Live preview <span x-show="previewBusy" role="status" class="ml-2 text-xs font-normal text-zinc-400"><i class="fa-solid fa-spinner fa-spin mr-1" aria-hidden="true"></i>Updating…</span></h3>
                <p class="mt-1 text-xs text-zinc-500">{{ $previewHint ?? 'Try the menu button and dropdowns here. Links stay inside the preview.' }}</p></div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="setPreviewWidth('desktop')" :aria-pressed="previewWidth === 'desktop'" :class="previewWidth === 'desktop' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-600'" class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-bold"><i class="fa-solid fa-desktop mr-1" aria-hidden="true"></i>Desktop</button>
                <button type="button" @click="setPreviewWidth('tablet')" :aria-pressed="previewWidth === 'tablet'" :class="previewWidth === 'tablet' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-600'" class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-bold"><i class="fa-solid fa-tablet-screen-button mr-1" aria-hidden="true"></i>Tablet</button>
                <button type="button" @click="setPreviewWidth('mobile')" :aria-pressed="previewWidth === 'mobile'" :class="previewWidth === 'mobile' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-600'" class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-bold"><i class="fa-solid fa-mobile-screen-button mr-1" aria-hidden="true"></i>Mobile</button>
                <button type="button" @click="refreshPreview()" :disabled="previewBusy" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold disabled:opacity-40"><i class="fa-solid fa-arrows-rotate mr-1" aria-hidden="true"></i>Refresh</button>
            </div>
        </div>
        <p x-show="previewError" x-text="previewError" role="alert" class="bg-amber-50 p-3 text-xs font-semibold text-amber-800"></p>
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-4 py-2 text-xs text-zinc-500">
            <span><span x-text="frameWidth()"></span>px viewport <span x-show="previewScale < 1">· scaled to fit</span></span>
            @if ($showMobileToggle ?? true)
            <label x-show="previewWidth !== 'desktop'" class="flex items-center gap-2"><input type="checkbox" :checked="previewMenuExpanded" @change="setPreviewMenu($event.target.checked)" class="rounded border-zinc-300 text-emerald-700">Expand mobile menu</label>
            @endif
        </div>
        <div x-ref="previewStage" class="overflow-hidden bg-zinc-100 p-3">
            <div :style="{ width: `${frameWidth() * previewScale}px`, height: `${previewHeight * previewScale}px` }" class="relative mx-auto overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
                <iframe x-ref="preview" @load="previewLoaded()" title="{{ $previewTitle ?? 'Navigation preview' }}" sandbox="allow-same-origin" :style="{ width: `${frameWidth()}px`, height: `${previewHeight}px`, transform: `scale(${previewScale})`, transformOrigin: 'top left' }" class="absolute left-0 top-0 block border-0 bg-white"></iframe>
            </div>
        </div>
    </section>
