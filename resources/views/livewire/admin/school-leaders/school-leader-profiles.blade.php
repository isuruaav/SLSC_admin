<div data-unsaved-form data-draft-key="profile-{{ $editingId }}" data-save-actions="save" class="space-y-6">
    @php($canUpdate = \Illuminate\Support\Facades\Gate::allows('school-leaders.update'))

    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">School Leadership</p>
            <h1 class="mt-1 text-2xl font-black text-zinc-950">Leadership Profiles</h1>
            <p class="mt-2 max-w-3xl text-sm text-zinc-500">Manage website visibility and optional English and Sinhala biographies for present and past appointment holders. A photo and name can be shown without a biography.</p>
        </div>
        <a href="{{ route('admin.school-leaders.index') }}" class="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-bold text-zinc-700">Manage Appointments</a>
    </header>

    @if (session('status'))
        <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <label for="profile-leader" class="block text-sm font-bold text-zinc-900">Select a present or past appointment holder</label>
        <select id="profile-leader" wire:model.live="selectedLeaderId" class="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm" @disabled($leaders->isEmpty())>
            @forelse ($leaders as $leader)
                <option value="{{ $leader->id }}">{{ $leader->appointmentLabel() }} — {{ $leader->name_en }} ({{ $leader->isCurrentAppointment() ? 'Present' : 'Past' }}{{ $leader->end_date ? ' · '.$leader->end_date->format('d M Y') : '' }}{{ $leader->is_active ? '' : ' · Hidden' }})</option>
            @empty
                <option value="">Create a leadership appointment first</option>
            @endforelse
        </select>
        <p class="mt-2 text-xs text-zinc-500">Portraits, names, ranks and appointment dates are edited under Manage Appointments. Biography content is optional in either language. Saved content stays with the officer when the appointment becomes past.</p>
    </section>

    @if ($selectedLeader)
        <div wire:key="leader-profile-editor-{{ $editingId }}" x-data="{ language: 'en' }" class="space-y-6">
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white p-6">
                <div class="flex items-center gap-4">
                    @if ($selectedLeader->image)
                        <img src="{{ app(\App\Services\MediaUrlService::class)->mediumOrOriginal($selectedLeader->image) }}" alt="{{ $selectedLeader->name_en }}" class="h-20 w-16 rounded-lg object-cover">
                    @endif
                    <div>
                        <h2 class="font-bold text-zinc-950">{{ $selectedLeader->name_en }}</h2>
                        <p class="mt-1 text-sm text-zinc-500">{{ $selectedLeader->appointmentLabel() }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <span @class(['rounded-full px-3 py-1.5 text-xs font-bold', 'bg-blue-100 text-blue-800' => $selectedLeader->is_active, 'bg-zinc-100 text-zinc-600' => ! $selectedLeader->is_active])>{{ $selectedLeader->is_active ? 'Appointment visible' : 'Appointment hidden' }}</span>
                    <span @class(['rounded-full px-3 py-1.5 text-xs font-bold', 'bg-emerald-100 text-emerald-800' => $selectedLeader->profile_enabled, 'bg-zinc-100 text-zinc-600' => ! $selectedLeader->profile_enabled])>{{ $selectedLeader->profile_enabled ? 'Profile enabled' : 'Profile disabled' }}</span>
                    @if ($selectedLeader->supportsProfile())
                        <x-admin.icon-action label="View English" icon="eye" tone="info"
                            href="{{ $selectedLeader->appointmentUrlForLocale('en') }}" target="_blank" rel="noopener"
                        />
                        <x-admin.icon-action label="සිංහල බලන්න" icon="eye" tone="info"
                            href="{{ $selectedLeader->appointmentUrlForLocale('si') }}" target="_blank" rel="noopener"
                        />
                    @endif
                </div>
            </section>

            @if ($canUpdate)
                <form wire:submit="save" class="space-y-6">
                    <label class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                        <input type="checkbox" wire:model="appointmentEnabled" class="mt-1 rounded border-blue-300 text-blue-700 focus:ring-blue-600">
                        <span><strong class="block text-sm text-blue-950">Show appointment on website / පත්වීම වෙබ් අඩවියේ පෙන්වන්න</strong><span class="mt-1 block text-xs text-blue-800">Shows the officer's photo, name and appointment details, even without a biography. Turn this off to hide the appointment's cards and page. Saved biography content is retained.</span></span>
                    </label>
                    <label class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        <input type="checkbox" wire:model="profileEnabled" class="mt-1 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-600">
                        <span><strong class="block text-sm text-emerald-950">Enable public profile / පැතිකඩ සක්‍රිය කරන්න</strong><span class="mt-1 block text-xs text-emerald-800">Optional. Shows biography content and Read More links in each language where content has been added. Content is not required in either language. Turning this off keeps the photo and name page visible when the appointment is enabled.</span></span>
                    </label>

                    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 p-4">
                            <div class="flex gap-2" role="tablist" aria-label="Profile language">
                                <button type="button" role="tab" :aria-selected="language === 'en'" @click="language = 'en'" :class="language === 'en' ? 'bg-blue-700 text-white' : 'bg-white text-zinc-700'" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-bold">English</button>
                                <button type="button" role="tab" :aria-selected="language === 'si'" @click="language = 'si'" :class="language === 'si' ? 'bg-blue-700 text-white' : 'bg-white text-zinc-700'" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-bold">සිංහල</button>
                            </div>
                            <span class="text-xs font-semibold text-zinc-500">Visual biography editor</span>
                        </div>

                        @foreach (['en' => 'English', 'si' => 'සිංහල'] as $locale => $label)
                            <div x-show="language === '{{ $locale }}'" @if ($locale === 'si') x-cloak @endif class="space-y-3 p-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <label for="profile-html-{{ $locale }}" class="text-sm font-bold text-zinc-900">{{ $label }} biography, qualifications and appointments</label>
                                    <button type="button" wire:click="insertTemplate('{{ $locale }}')" class="text-xs font-bold text-blue-700 underline">Insert starter template</button>
                                </div>
                                <div wire:key="profile-editor-{{ $editingId }}-{{ $locale }}-{{ $editorVersion }}">
                                    <x-forms.page-content-editor id="profile-html-{{ $locale }}" :model="$locale === 'en' ? 'profileHtmlEn' : 'profileHtmlSi'" :mode-model="$locale === 'en' ? 'profileEditorModeEn' : 'profileEditorModeSi'" :value="$locale === 'en' ? $profileHtmlEn : $profileHtmlSi" :editor-mode="$locale === 'en' ? $profileEditorModeEn : $profileEditorModeSi" />
                                </div>
                            </div>
                        @endforeach
                        <p class="border-t border-zinc-200 px-5 py-4 text-xs leading-6 text-zinc-500">Write the biography visually and use headings for qualifications and appointments. HTML and custom styles are optional advanced tools.</p>
                    </section>

                    <section class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                        <details><summary class="cursor-pointer font-semibold">Advanced styles (optional)</summary><label for="profile-css" class="block text-sm font-bold text-zinc-900">Custom CSS — shared by both languages</label>
                        <p class="text-xs text-zinc-500">Style classes used in your HTML. The site's colour variables, such as var(--blue-700), are available.</p>
                        <textarea id="profile-css" wire:model="profileCss" rows="8" spellcheck="false" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 p-4 font-mono text-sm leading-6 text-zinc-100" placeholder=".profile-heading { color: var(--blue-700); }"></textarea></details>
                    </section>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <span wire:loading wire:target="save,preview" class="text-sm font-semibold text-emerald-700">Generating profile styles…</span>
                        <x-admin.icon-action label="Update Preview" icon="arrow-path" tone="info"
                            type="button" wire:click="preview" wire:loading.attr="disabled"
                        />
                        <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:opacity-50">Save Profile</button>
                    </div>
                </form>
            @else
                <p class="rounded-xl border border-zinc-200 bg-white p-5 text-sm text-zinc-600">Read-only access. Profile editing requires School Leadership update permission.</p>
            @endif

            @if ($previewEn !== '' || $previewSi !== '')
                <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                    <h2 class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 font-bold text-zinc-900">Profile Preview</h2>
                    @if ($previewEn !== '')
                        <iframe x-show="language === 'en'" title="English profile preview" sandbox="allow-same-origin" srcdoc="{{ $previewEn }}" class="h-[36rem] w-full border-0"></iframe>
                    @else
                        <p x-show="language === 'en'" class="p-5 text-sm text-zinc-500">No English biography. The public page shows the officer's photo and name.</p>
                    @endif
                    @if ($previewSi !== '')
                        <iframe x-show="language === 'si'" x-cloak title="Sinhala profile preview" sandbox="allow-same-origin" srcdoc="{{ $previewSi }}" class="h-[36rem] w-full border-0"></iframe>
                    @else
                        <p x-show="language === 'si'" x-cloak class="p-5 text-sm text-zinc-500">සිංහල චරිතාපදානයක් නැත. වෙබ් පිටුවේ නිලධාරියාගේ ඡායාරූපය සහ නම පෙන්වයි.</p>
                    @endif
                </section>
            @endif
        </div>
    @endif
</div>
