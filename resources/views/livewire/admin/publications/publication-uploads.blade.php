<div class="space-y-6" x-data="{ preparing: false }" x-on:publication-upload-start="preparing = true" x-on:publication-upload-end="preparing = false">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-zinc-950">Publications</h1>
            <p class="mt-1 text-sm text-zinc-500">Upload a PDF, check its cover, then save a draft or publish.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('documents.update')
            <button type="button" wire:click="toggleHeaderSettings" class="rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold hover:bg-zinc-50 flex items-center gap-2">
                <svg class="h-4 w-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Customize Page Title</span>
            </button>
            @endcan
            <a href="{{ route('publications.index') }}" target="_blank" rel="noopener" class="rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold">View public page ↗</a>
            @can('documents.create')
                @can('media.upload')
                    <button type="button" wire:click="create" x-bind:disabled="preparing" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">+ Upload Publication</button>
                @endcan
            @endcan
        </div>
    </header>

    @if (session('publication-success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('publication-success') }}</div>
    @endif

    @if ($headerSettingsOpen)
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50/50 p-5 shadow-sm sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-zinc-950">Customize Public Page Header</h2>
                    <p class="text-xs text-zinc-600">Change the title, badge, and description on the public publications page.</p>
                </div>
                <button type="button" wire:click="toggleHeaderSettings" class="text-sm font-semibold text-zinc-500 hover:text-zinc-800">Close</button>
            </div>
            <form wire:submit="saveHeaderSettings" data-unsaved-form data-draft-key="publication-header" class="space-y-4">
                <x-admin.editor-status />
                @if ($errors->any())
                    <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800">
                        <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="publication-header-pageTitleEn" class="mb-1 block text-xs font-bold uppercase tracking-wider text-zinc-700">Page Title (English) *</label>
                        <input id="publication-header-pageTitleEn" type="text" wire:model="pageTitleEn" required class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm" placeholder="e.g. Signaller Magazine">
                    </div>
                    <div>
                        <label for="publication-header-pageTitleSi" class="mb-1 block text-xs font-bold uppercase tracking-wider text-zinc-700">Page Title (Sinhala)</label>
                        <input id="publication-header-pageTitleSi" type="text" wire:model="pageTitleSi" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm" placeholder="e.g. සිග්නලර් සඟරාව">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="publication-header-pageKickerEn" class="mb-1 block text-xs font-bold uppercase tracking-wider text-zinc-700">Badge / Kicker (English)</label>
                        <input id="publication-header-pageKickerEn" type="text" wire:model="pageKickerEn" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm" placeholder="e.g. Explore our library">
                    </div>
                    <div>
                        <label for="publication-header-pageKickerSi" class="mb-1 block text-xs font-bold uppercase tracking-wider text-zinc-700">Badge / Kicker (Sinhala)</label>
                        <input id="publication-header-pageKickerSi" type="text" wire:model="pageKickerSi" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm" placeholder="e.g. අපගේ සඟරා">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="publication-header-pageSubtitleEn" class="mb-1 block text-xs font-bold uppercase tracking-wider text-zinc-700">Subtitle / Description (English)</label>
                        <textarea id="publication-header-pageSubtitleEn" wire:model="pageSubtitleEn" rows="2" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm" placeholder="e.g. Explore our latest journals and publications. Select a cover to read the PDF."></textarea>
                    </div>
                    <div>
                        <label for="publication-header-pageSubtitleSi" class="mb-1 block text-xs font-bold uppercase tracking-wider text-zinc-700">Subtitle / Description (Sinhala)</label>
                        <textarea id="publication-header-pageSubtitleSi" wire:model="pageSubtitleSi" rows="2" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm" placeholder="e.g. ශ්‍රී ලංකා සංඥා බලකායේ නවතම සඟරා. PDF ගොනුව කියවීමට කවරය මත ක්ලික් කරන්න."></textarea>
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Save Title &amp; Settings</button>
                    <button type="button" wire:click="toggleHeaderSettings" class="text-sm font-medium text-zinc-600 hover:text-zinc-900">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    @if ($formOpen)
        <form wire:submit="save" wire:key="publication-form-{{ $formVersion }}" data-publication-upload data-unsaved-form data-draft-key="publication-{{ $editingId ?? 'new' }}" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
            <x-admin.editor-status />
            <div class="mb-5 flex items-center justify-between gap-4">
                <h2 class="text-lg font-bold">{{ $editingId ? 'Edit publication' : 'Upload publication' }}</h2>
                <button type="button" wire:click="cancel" data-publication-control x-bind:disabled="preparing" class="text-sm font-semibold text-zinc-500">Cancel</button>
            </div>
            @if ($errors->any())
                <div role="alert" class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_200px]">
                <div class="space-y-5">
                    <div>
                        <label for="publication-locale" class="mb-2 block text-sm font-semibold">Publication language</label>
                        <select id="publication-locale" wire:model.live="locale" required class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm">
                            <option value="en">English</option>
                            <option value="si">සිංහල</option>
                        </select>
                        <p class="mt-1 text-xs text-zinc-500">Upload each language as a separate publication with its own PDF.</p>
                    </div>
                    <div>
                        <label for="publication-title" class="mb-2 block text-sm font-semibold">{{ $locale === 'si' ? 'සිංහල මාතෘකාව' : 'English title' }}</label>
                        <input id="publication-title" wire:model="title" data-publication-title type="text" required maxlength="255" lang="{{ $locale }}" placeholder="{{ $locale === 'si' ? 'ප්‍රකාශන නම ඇතුළත් කරන්න' : 'e.g. Signals Journal - October 2026' }}" class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm">
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2"><label class="text-sm font-semibold">Issue / edition<input wire:model="issueNumber" maxlength="80" placeholder="e.g. Volume 12, Issue 2" class="mt-2 block w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm"></label><label class="text-sm font-semibold">Issue year<input type="number" wire:model="issueYear" min="1900" max="9999" placeholder="e.g. 2026" class="mt-2 block w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm"></label></div>
                    @can('media.upload')
                        <div wire:ignore class="rounded-xl border-2 border-dashed border-emerald-200 bg-emerald-50 p-5">
                            <label for="publication-pdf-{{ $formVersion }}" class="mb-3 block text-sm font-semibold">{{ $editingId ? 'Replace PDF (optional)' : 'PDF file' }}</label>
                            <input id="publication-pdf-{{ $formVersion }}" data-publication-pdf-input x-bind:disabled="preparing" type="file" accept="application/pdf,.pdf" class="block w-full text-sm">
                            <p class="mt-3 text-xs leading-5 text-zinc-600">PDF only, up to 50 MB, without a password. Generate a cover from the first page or upload your own.</p>
                            <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" data-publication-auto-cover checked> Generate cover automatically</label>
                        </div>
                        <div wire:ignore class="rounded-xl border-2 border-dashed border-zinc-200 bg-white p-5">
                            <label for="publication-cover-{{ $formVersion }}" class="mb-3 block text-sm font-semibold">{{ $editingId ? 'Replace cover (required when replacing the PDF)' : 'Cover image (optional if generated automatically)' }}</label>
                            <input id="publication-cover-{{ $formVersion }}" data-publication-cover-input x-bind:disabled="preparing" type="file" accept="image/jpeg,.jpg,.jpeg" class="block w-full text-sm">
                            <p class="mt-3 text-xs leading-5 text-zinc-600">JPG/JPEG only, up to 2 MB and 1400 × 1600 pixels. You can replace the generated cover here.</p>
                            <p data-publication-upload-status role="status" aria-live="polite" class="mt-3 text-sm text-emerald-800"></p>
                        </div>
                    @endcan
                    <p class="text-sm text-zinc-600">{{ $isPublished ? 'This publication is live. Saving changes updates the website.' : 'A draft is private until you choose Save & Publish.' }}</p>
                </div>
                <div>
                    <p class="mb-2 text-sm font-semibold">Cover image preview</p>
                    <div wire:ignore class="flex aspect-[3/4] items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100">
                        <img data-publication-cover-preview @if ($editing) src="{{ route('admin.publications.cover', ['publication' => $editing->uuid]) }}" @else hidden @endif alt="Publication first-page preview" class="h-full w-full object-contain">
                        <span data-publication-cover-placeholder @if ($editing) hidden @endif class="p-5 text-center text-sm text-zinc-400">Generate a cover from your PDF or select a JPG image to preview it.</span>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-zinc-200 pt-5">
                <button type="submit" data-publication-control x-bind:disabled="preparing" wire:loading.attr="disabled" wire:target="save,saveAndPublish,pdf,cover" class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">{{ $isPublished ? 'Save Changes' : 'Save Draft' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
                @can('documents.publish')
                    @if (!$isPublished)
                        <button type="button" wire:click="saveAndPublish" data-publication-control x-bind:disabled="preparing" wire:loading.attr="disabled" class="rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white disabled:opacity-50">
                            <span wire:loading.remove wire:target="saveAndPublish">Save &amp; Publish</span>
                            <span wire:loading wire:target="saveAndPublish">Publishing…</span>
                        </button>
                    @endif
                @endcan
            </div>
        </form>
    @endif

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-wrap gap-3 border-b border-zinc-200 p-4">
            <input type="search" wire:model.live.debounce.300ms="search" aria-label="Search publications" placeholder="Search publications…" class="min-w-0 flex-1 rounded-xl border border-zinc-300 px-4 py-3 text-sm">
            <select wire:model.live="languageFilter" aria-label="Language" class="rounded-xl border border-zinc-300 px-4 py-3 text-sm"><option value="all">All languages</option><option value="en">English</option><option value="si">සිංහල</option></select>
            <select wire:model.live="statusFilter" aria-label="Publication status" class="rounded-xl border border-zinc-300 px-4 py-3 text-sm">
                <option value="all">All publications</option><option value="published">Published</option><option value="draft">Drafts</option><option value="trashed">Trash</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table role="table" aria-label="Publications" class="admin-responsive-table w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500"><tr><th class="px-5 py-4">Publication</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Uploaded</th><th class="px-5 py-4 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($publications as $publication)
                        <tr wire:key="publication-{{ $publication->id }}">
                            <td data-card-title class="px-5 py-4">
                                @if ($publication->trashed())
                                    <strong class="block text-zinc-900">{{ $publication->title }}</strong>
                                    <span class="mt-1 block text-xs text-zinc-500">{{ $publication->locale === 'si' ? 'සිංහල' : 'English' }} · PDF · {{ number_format($publication->size_bytes / 1048576, 1) }} MB</span>
                                @else
                                <a href="{{ route('admin.publications.pdf', ['publication' => $publication->uuid]) }}" target="_blank" rel="noopener" class="flex min-w-48 items-center gap-4">
                                    <img src="{{ route('admin.publications.cover', ['publication' => $publication->uuid]) }}" alt="" width="48" height="64" loading="lazy" class="h-16 w-12 shrink-0 rounded border border-zinc-200 bg-zinc-50 object-contain">
                                    <span><strong class="block text-zinc-900">{{ $publication->title }}</strong><span class="mt-1 block text-xs text-zinc-500">{{ $publication->locale === 'si' ? 'සිංහල' : 'English' }} · PDF · {{ number_format($publication->size_bytes / 1048576, 1) }} MB</span></span>
                                </a>
                                @endif
                                @if ($publication->isPublic())
                                    <x-admin.copy-path :path="route('publications.pdf', ['publication' => $publication->uuid])"
                                        label="Public PDF path" class="mt-3 max-w-sm" />
                                @endif
                            </td>
                            <td data-label="Status" class="px-4 py-4"><span @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $publication->is_published && !$publication->trashed(), 'bg-zinc-100 text-zinc-600' => !$publication->is_published || $publication->trashed()])>{{ $publication->trashed() ? 'In Trash' : ($publication->is_published ? 'Published' : 'Draft') }}</span></td>
                            <td data-label="Uploaded" class="whitespace-nowrap px-4 py-4 text-zinc-500">{{ $publication->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($publication->trashed())
                                        @can('documents.delete')
                                            <x-admin.icon-action label="Restore as draft" icon="arrow-uturn-left" tone="success" wire:click="restore({{ $publication->id }})" wire:loading.attr="disabled" />
                                        @endcan
                                    @else
                                    @can('documents.update')
                                        @if (!$publication->is_published || auth()->user()->can('documents.publish'))
                                            <x-admin.icon-action label="Edit" icon="pencil-square" tone="info"
                                                wire:click="edit({{ $publication->id }})"
                                                x-bind:disabled="preparing" wire:loading.attr="disabled"
                                            />
                                        @endif
                                    @endcan
                                    @can('documents.publish')
                                        <x-admin.icon-action
                                            :label="$publication->is_published ? 'Unpublish' : 'Publish'"
                                            :icon="$publication->is_published ? 'eye-slash' : 'arrow-up-tray'"
                                            :tone="$publication->is_published ? 'warning' : 'success'"
                                            wire:click="{{ $publication->is_published ? 'unpublish' : 'publish' }}({{ $publication->id }})"
                                            x-bind:disabled="preparing" wire:loading.attr="disabled"
                                        />
                                    @endcan
                                    @can('documents.delete')
                                        <x-admin.icon-action label="Delete" icon="trash" tone="danger"
                                            wire:click="delete({{ $publication->id }})"
                                            wire:confirm="Remove this publication from the website?"
                                            x-bind:disabled="preparing" wire:loading.attr="disabled"
                                        />
                                    @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-14 text-center text-zinc-500">{{ $search !== '' || $statusFilter !== 'all' ? 'No publications match your filters.' : 'No publications yet. Upload your first PDF to get started.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($publications->hasPages())<div class="border-t border-zinc-200 p-4">{{ $publications->links() }}</div>@endif
    </section>
</div>
