<div>
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Site Management</p>
        <h1 class="mt-1 text-2xl font-black text-zinc-950">Site Settings</h1>
        <p class="mt-1 text-sm text-zinc-500">Manage site languages, English and Sinhala site details, theme family, social links and maintenance mode.</p>
    </div>

    @if (session('status'))
        <div
            class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4">
                <h2 class="font-bold text-zinc-900">Site Languages</h2>
                <p class="mt-1 text-sm text-zinc-500">Select the languages to use on your website, then save settings. You can enable Tamil whenever you are ready.</p>
            </div>
            <fieldset class="p-6">
                <legend class="sr-only">Enabled website languages</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($availableLanguages as $language)
                        <label for="site-language-{{ $language->value }}" class="flex items-start gap-3 rounded-xl border border-zinc-200 p-4">
                            <input id="site-language-{{ $language->value }}" type="checkbox" wire:model="enabledLocales" value="{{ $language->value }}" @disabled($language->value === 'en') class="mt-1 rounded border-zinc-300 text-emerald-700">
                            <span><span class="block text-sm font-bold text-zinc-900">{{ $language->nativeLabel() }}</span><span class="mt-1 block text-xs text-zinc-500">{{ $language->label() }} · {{ strtoupper($language->value) }}</span>
                                @if ($language->value === 'en')<span class="mt-2 block text-xs text-emerald-700">Required fallback language</span>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-4 text-xs leading-6 text-zinc-500">Unchecked languages are hidden from visitors and language selectors. Existing translations and layouts are kept, ready to use when enabled again. English remains available as the fallback.</p>
                @error('enabledLocales')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                @error('enabledLocales.*')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
            </fieldset>
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4">
                <h2 class="font-bold text-zinc-900">Identity & Theme</h2>
            </div>
            <div class="grid gap-5 p-6 lg:grid-cols-2">
                <div><label class="mb-1.5 block text-sm font-semibold">Site Name</label><input wire:model="siteName"
                        type="text" maxlength="180"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></div>
                <div class=""><label for="site-siteName-si" class="mb-1.5 block text-sm font-semibold">Site Name (සිංහල)</label>
                    <input id="site-siteName-si" lang="si" wire:model="siteNameSi" type="text" maxlength="180" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                    @error('siteNameSi')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div><label class="mb-1.5 block text-sm font-semibold">Tagline</label><input wire:model="siteTagline"
                        type="text" maxlength="255"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></div>
                <div class=""><label for="site-siteTagline-si" class="mb-1.5 block text-sm font-semibold">Tagline (සිංහල)</label>
                    <input id="site-siteTagline-si" lang="si" wire:model="siteTaglineSi" type="text" maxlength="255" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                    @error('siteTaglineSi')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div><label class="mb-1.5 block text-sm font-semibold">Theme Family</label><select
                        wire:model="themeFamily" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                        <option value="army-unit">Army Unit</option>
                        <option value="training-school">Training School</option>
                        <option value="sfhq">SFHQ</option>
                        <option value="establishment">Establishment</option>
                    </select></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="mb-1.5 block text-sm font-semibold">Primary Colour</label><input
                            wire:model="primaryColor" type="color"
                            class="h-11 w-full rounded-xl border border-zinc-300 bg-white p-1"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold">Accent Colour</label><input
                            wire:model="accentColor" type="color"
                            class="h-11 w-full rounded-xl border border-zinc-300 bg-white p-1"></div>
                </div>
                <div><label class="mb-1.5 block text-sm font-semibold">Logo Media</label><select
                        wire:model="logoMediaId" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                        <option value="">No logo</option>
                        @foreach ($mediaAssets as $asset)
                            <option value="{{ $asset->id }}">#{{ $asset->id }} —
                                {{ $asset->title ?: $asset->original_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="mb-1.5 block text-sm font-semibold">Favicon Media</label><select
                        wire:model="faviconMediaId"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                        <option value="">No favicon</option>
                        @foreach ($mediaAssets as $asset)
                            <option value="{{ $asset->id }}">#{{ $asset->id }} —
                                {{ $asset->title ?: $asset->original_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4">
                <h2 class="font-bold text-zinc-900">Contact & Footer</h2>
            </div>
            <div class="grid gap-5 p-6 lg:grid-cols-2">
                <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-semibold">Address</label>
                    <textarea wire:model="address" rows="3" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></textarea>
                </div>
                <div class="lg:col-span-2"><label for="site-address-si" class="mb-1.5 block text-sm font-semibold">ලිපිනය (සිංහල)</label>
                    <textarea id="site-address-si" lang="si" wire:model="addressSi" rows="3" maxlength="2000" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></textarea>
                    @error('addressSi')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div><label class="mb-1.5 block text-sm font-semibold">Primary Phone</label><input
                        wire:model="phonePrimary" type="text"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></div>
                <div><label class="mb-1.5 block text-sm font-semibold">Secondary Phone</label><input
                        wire:model="phoneSecondary" type="text"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></div>
                <div><label class="mb-1.5 block text-sm font-semibold">Public Email</label><input wire:model="email"
                        type="email" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></div>
                <div><label class="mb-1.5 block text-sm font-semibold">Map URL</label><input wire:model="mapUrl"
                        type="url" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></div>
                <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-semibold">Footer Text</label>
                    <textarea wire:model="footerText" rows="3" maxlength="2000"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></textarea>
                </div>
                <div class="lg:col-span-2"><label for="site-footerText-si" class="mb-1.5 block text-sm font-semibold">Footer Text (සිංහල)</label>
                    <textarea id="site-footerText-si" lang="si" wire:model="footerTextSi" rows="3" maxlength="2000" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></textarea>
                    @error('footerTextSi')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4">
                <h2 class="font-bold text-zinc-900">Commander / Head of Establishment</h2>
            </div>
            <div class="grid gap-5 p-6 lg:grid-cols-2">
                <div><label class="mb-1.5 block text-sm font-semibold">Name</label><input wire:model="commanderName"
                        type="text" maxlength="180"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></div>
                <div class=""><label for="site-commanderName-si" class="mb-1.5 block text-sm font-semibold">නම (සිංහල)</label>
                    <input id="site-commanderName-si" lang="si" wire:model="commanderNameSi" type="text" maxlength="180" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                    @error('commanderNameSi')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div><label class="mb-1.5 block text-sm font-semibold">Title / Appointment</label><input
                        wire:model="commanderTitle" type="text" maxlength="180"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></div>
                <div class=""><label for="site-commanderTitle-si" class="mb-1.5 block text-sm font-semibold">තනතුර (සිංහල)</label>
                    <input id="site-commanderTitle-si" lang="si" wire:model="commanderTitleSi" type="text" maxlength="180" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                    @error('commanderTitleSi')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-semibold">Profile
                        Image</label><select wire:model="commanderImageMediaId"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                        <option value="">No image</option>
                        @foreach ($mediaAssets as $asset)
                            <option value="{{ $asset->id }}">#{{ $asset->id }} —
                                {{ $asset->title ?: $asset->original_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2"><label class="mb-1.5 block text-sm font-semibold">Message</label>
                    <textarea wire:model="commanderMessage" rows="6" maxlength="5000"
                        class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></textarea>
                </div>
                <div class="lg:col-span-2"><label for="site-commanderMessage-si" class="mb-1.5 block text-sm font-semibold">පණිවිඩය (සිංහල)</label>
                    <textarea id="site-commanderMessage-si" lang="si" wire:model="commanderMessageSi" rows="3" maxlength="5000" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm"></textarea>
                    @error('commanderMessageSi')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
            <div class="flex items-start gap-3"><input wire:model="maintenanceMode" type="checkbox" class="mt-1">
                <div class="flex-1">
                    <h2 class="font-bold text-amber-900">Maintenance Mode</h2>
                    <p class="mt-1 text-sm text-amber-800">When enabled, public visitors receive a 503 maintenance
                        page. Authenticated administrators can still preview the site.</p>
                    <textarea wire:model="maintenanceMessage" rows="3" maxlength="2000" placeholder="Optional maintenance message"
                        class="mt-4 w-full rounded-xl border border-amber-300 bg-white px-3 py-2.5 text-sm"></textarea>
                    <label for="site-maintenanceMessage-si" class="mt-4 block text-sm font-semibold text-amber-900">නඩත්තු පණිවිඩය (සිංහල)</label>
                    <textarea id="site-maintenanceMessage-si" lang="si" wire:model="maintenanceMessageSi" rows="3" maxlength="2000"
                        class="mt-2 w-full rounded-xl border border-amber-300 bg-white px-3 py-2.5 text-sm"></textarea>
                    @error('maintenanceMessageSi')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end"><button type="submit"
                class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-800">Save Site
                Settings</button></div>
    </form>

    <section class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4">
            <h2 class="font-bold text-zinc-900">Social Links</h2>
            <p class="mt-1 text-sm text-zinc-500">Choose or enter a platform. Its icon is added automatically on the website.</p>
        </div>
        <div class="grid gap-3 p-6 sm:grid-cols-3">
            <div>
                <label for="social-platform" class="mb-1.5 block text-sm font-semibold">Platform</label>
                <input id="social-platform" wire:model="socialPlatform" type="text" list="social-platforms"
                    maxlength="80" placeholder="Facebook" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                <datalist id="social-platforms">
                    @foreach ($socialPlatforms as $platform)
                        <option value="{{ $platform }}"></option>
                    @endforeach
                </datalist>
                @error('socialPlatform')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="social-label" class="mb-1.5 block text-sm font-semibold">Label (optional)</label>
                <input id="social-label" wire:model="socialLabel" type="text" maxlength="120"
                    placeholder="Official Facebook" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                @error('socialLabel')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="social-url" class="mb-1.5 block text-sm font-semibold">URL</label>
                <input id="social-url" wire:model="socialUrl" type="url" maxlength="2048" placeholder="https://..."
                    class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                @error('socialUrl')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-3"><button type="button" wire:click="addSocialLink"
                    class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white">Add Social Link</button>
            </div>
        </div>
        <div class="divide-y border-t">
            @forelse ($socialLinks as $link)
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <div>
                        <p class="text-sm font-bold text-zinc-900">{{ $link->platform }}</p>
                        <p class="max-w-sm truncate text-xs text-zinc-500">{{ $link->url }}</p>
                    </div>
                    <x-admin.icon-action label="Delete" icon="trash" tone="danger"
                        type="button" wire:click="deleteSocialLink({{ $link->id }})"
                        wire:confirm="Delete this social link?"
                    />
                </div>
            @empty
                <p class="px-6 py-8 text-sm text-zinc-500">No social links.</p>
            @endforelse
        </div>
    </section>
</div>
