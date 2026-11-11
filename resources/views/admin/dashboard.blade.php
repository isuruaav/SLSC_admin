<x-layouts.admin :title="__('Dashboard')">
    <div class="space-y-6">
        <div class="rounded-2xl border border-zinc-200 bg-white px-6 py-7 shadow-sm sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <x-admin.page-header title="AWCMS Dashboard"
                    description="A clear view of your website content, incoming enquiries and recent administration activity." />
                <a href="{{ route('home') }}" target="_blank" rel="noreferrer"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-zinc-700">
                    View Website <span class="ml-2" aria-hidden="true">↗</span>
                </a>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Content overview">
            <x-admin.stat-card title="Pages" :value="$pageCount" description="All page records"
                class="border-l-4 border-l-emerald-600" />
            <x-admin.stat-card title="News" :value="$newsCount" description="News articles"
                class="border-l-4 border-l-sky-600" />
            <x-admin.stat-card title="Galleries" :value="$galleryCount" description="Photo galleries"
                class="border-l-4 border-l-amber-500" />
            <x-admin.stat-card title="Documents" :value="$documentCount" description="PDF documents"
                class="border-l-4 border-l-violet-600" />
            @can('documents.view')
                <x-admin.stat-card title="Publications" :value="$publicationCount" description="Magazines and journals"
                    class="border-l-4 border-l-teal-600" />
            @endcan
            <x-admin.stat-card title="New Messages" :value="$newContactCount" description="Unread enquiries"
                class="border-l-4 border-l-rose-600" />
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Content to Review</h2><p class="mt-1 text-xs text-zinc-500">Review content readiness and language coverage.</p><div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach($workNotices as $notice)<a href="{{ $notice['url'] }}" wire:navigate class="rounded-xl border border-zinc-200 p-4 text-sm hover:border-emerald-400"><strong class="block text-xl text-emerald-800">{{ $notice['count'] }}</strong><span>{{ $notice['label'] }}</span></a>@endforeach</div></section>
        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
            <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm"
                aria-labelledby="activity-title">
                <div class="flex items-start justify-between gap-4 border-b border-zinc-200 bg-zinc-50 px-6 py-5">
                    <div>
                        <h2 id="activity-title" class="font-bold text-zinc-900">Recent Activity</h2>
                        <p class="mt-1 text-sm text-zinc-500">The latest security and content events.</p>
                    </div>
                    <span class="rounded-full bg-zinc-200 px-2.5 py-1 text-xs font-bold text-zinc-600">Last 8</span>
                </div>
                <div class="divide-y divide-zinc-100">
                    @forelse($recentActivity as $activity)
                        <article class="flex gap-4 px-6 py-5">
                            <span
                                class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-black text-emerald-700"
                                aria-hidden="true">•</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                    <h3 class="text-sm font-bold text-zinc-900">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $activity->event)) }}</h3>
                                    <time class="shrink-0 text-xs text-zinc-400"
                                        datetime="{{ $activity->created_at?->toIso8601String() }}">{{ $activity->created_at?->format('d M Y, H:i') }}</time>
                                </div>
                                <p class="mt-1 text-sm leading-6 text-zinc-600">{{ $activity->description }}</p>
                                <p class="mt-2 text-xs font-semibold text-zinc-400">
                                    {{ $activity->actor_name ?: 'System' }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <p class="text-sm font-semibold text-zinc-700">No activity recorded yet</p>
                            <p class="mt-1 text-sm text-zinc-500">Changes made by administrators will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <div class="space-y-6">
                <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm"
                    aria-labelledby="actions-title">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="actions-title" class="font-bold text-zinc-900">Quick Actions</h2>
                        <span class="text-xs font-semibold text-zinc-400">Common tasks</span>
                    </div>
                    <div class="mt-4 grid gap-2">
                        @can('pages.create')
                            <a href="{{ route('admin.pages.create') }}" wire:navigate
                                class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800"><span>Create
                                    Page</span><span aria-hidden="true">→</span></a>
                        @endcan
                        @can('news.create')
                            <a href="{{ route('admin.news.create') }}" wire:navigate
                                class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800"><span>Create
                                    News Article</span><span aria-hidden="true">→</span></a>
                        @endcan
                        @can('media.upload')
                            <a href="{{ route('admin.media.upload') }}" wire:navigate
                                class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800"><span>Upload
                                    Media</span><span aria-hidden="true">→</span></a>
                        @endcan
                        @can('documents.create')
                            @can('media.upload')
                                <a href="{{ route('admin.publications.index') }}" wire:navigate
                                    class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800"><span>Upload Publication</span><span aria-hidden="true">→</span></a>
                            @endcan
                            <a href="{{ route('admin.documents.create') }}" wire:navigate
                                class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800"><span>Create
                                    Document</span><span aria-hidden="true">→</span></a>
                        @endcan

                        @can('settings.manage')
                            <a href="{{ route('admin.site-settings.index') }}" wire:navigate
                                class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800"><span>Site
                                    Settings</span><span aria-hidden="true">→</span></a>
                        @endcan
                    </div>
                </section>

                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6"
                    aria-labelledby="foundation-title">
                    <h2 id="foundation-title" class="font-bold text-emerald-950">Continue Editing</h2>
                    <p class="mt-1 text-xs text-emerald-800">Your team's most recently updated drafts.</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($drafts as $draft)
                            <a href="{{ $draft['url'] }}" wire:navigate class="block rounded-xl bg-white p-3 text-sm text-emerald-950 hover:underline">
                                <span class="block text-xs text-emerald-700">{{ $draft['type'] }} · {{ $draft['updated_at']?->diffForHumans() }}</span>
                                <strong class="mt-1 block">{{ $draft['title'] }}</strong>
                            </a>
                        @empty
                            <p class="text-sm text-emerald-800">No drafts waiting to be edited.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    </div>
</x-layouts.admin>
