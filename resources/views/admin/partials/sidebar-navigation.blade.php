<nav
    class="admin-sidebar-nav px-3 py-4"
    aria-label="Administration navigation"
    x-data="{ navSearch: '' }"
    @keydown.escape.window="navSearch = ''"
>
    <label class="admin-nav-search mb-5 block">
        <span class="sr-only">Search navigation</span>
        <span class="admin-nav-search-box">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input
                x-ref="adminNavSearch"
                x-model.debounce.100ms="navSearch"
                type="search"
                placeholder="Find a section…"
                autocomplete="off"
                aria-label="Find an administration section"
            >
            <kbd>ESC</kbd>
        </span>
        <span class="admin-nav-search-hint">Filter the tools available to your account</span>
    </label>

    @can('dashboard.view')
        <div class="admin-nav-group" x-show="!navSearch.trim() || Array.from($el.querySelectorAll('a')).some(link => link.innerText.toLowerCase().includes(navSearch.trim().toLowerCase()))">
            <p class="admin-nav-heading">Overview</p>
            <x-admin.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="fa-solid fa-house" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">
                Dashboard
            </x-admin.nav-link>
        </div>
    @endcan

    @if (auth()->user()->can('pages.view') || auth()->user()->can('news.view') || auth()->user()->can('galleries.view') || auth()->user()->can('media.view') || auth()->user()->can('documents.view'))
        <div class="admin-nav-group" x-show="!navSearch.trim() || Array.from($el.querySelectorAll('a')).some(link => link.innerText.toLowerCase().includes(navSearch.trim().toLowerCase()))">
            <p class="admin-nav-heading">Content Management</p>
            @can('pages.view')
                <x-admin.nav-link :href="route('admin.pages.index')" :active="request()->routeIs('admin.pages.*')" icon="fa-regular fa-file-lines" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Pages</x-admin.nav-link>
            @endcan
            @can('news.view')
                <x-admin.nav-link :href="route('admin.news.index')" :active="request()->routeIs('admin.news.*')" icon="fa-regular fa-newspaper" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">News</x-admin.nav-link>
            @endcan
            @can('galleries.view')
                <x-admin.nav-link :href="route('admin.galleries.index')" :active="request()->routeIs('admin.galleries.*')" icon="fa-regular fa-images" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Galleries</x-admin.nav-link>
            @endcan
            @can('media.view')
                <x-admin.nav-link :href="route('admin.media.index')" :active="request()->routeIs('admin.media.*')" icon="fa-solid fa-photo-film" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Media Library</x-admin.nav-link>
            @endcan
            @can('documents.view')
                <x-admin.nav-link :href="route('admin.publications.index')" :active="request()->routeIs('admin.publications.*')" icon="fa-regular fa-bookmark" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Publications</x-admin.nav-link>
                <x-admin.nav-link :href="route('admin.documents.index')" :active="request()->routeIs('admin.documents.*')" icon="fa-regular fa-folder-open" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Document Uploads</x-admin.nav-link>
            @endcan
        </div>
    @endif

    @if (auth()->user()->can('organizations.view') || auth()->user()->can('organizations.categories.manage') || auth()->user()->can('organizations.import'))
        <div class="admin-nav-group" x-show="!navSearch.trim() || Array.from($el.querySelectorAll('a')).some(link => link.innerText.toLowerCase().includes(navSearch.trim().toLowerCase()))">
            <p class="admin-nav-heading">Organization Management</p>
            @can('organizations.view')
                <x-admin.nav-link :href="route('admin.organizations.index')" :active="request()->routeIs('admin.organizations.index') || request()->routeIs('admin.organizations.edit')" icon="fa-solid fa-sitemap" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">All Organizations</x-admin.nav-link>
            @endcan
            @can('organizations.create')
                <x-admin.nav-link :href="route('admin.organizations.create')" :active="request()->routeIs('admin.organizations.create')" icon="fa-solid fa-plus" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Add New Organization</x-admin.nav-link>
            @endcan
            @can('organizations.categories.manage')
                <x-admin.nav-link :href="route('admin.organizations.categories.index')" :active="request()->routeIs('admin.organizations.categories.*')" icon="fa-solid fa-tags" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Categories</x-admin.nav-link>
            @endcan
            @can('organizations.import')
                <x-admin.nav-link :href="route('admin.organizations.import.index')" :active="request()->routeIs('admin.organizations.import.*')" icon="fa-solid fa-file-import" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Import Legacy Content</x-admin.nav-link>
            @endcan
        </div>
    @endif

    @if (auth()->user()->can('courses.view') || auth()->user()->can('settings.manage') || auth()->user()->can('hero-slides.view') || auth()->user()->can('school-leaders.view') || auth()->user()->can('first-appointments.view') || auth()->user()->can('brigade-commanders.view'))
        <div class="admin-nav-group" x-show="!navSearch.trim() || Array.from($el.querySelectorAll('a')).some(link => link.innerText.toLowerCase().includes(navSearch.trim().toLowerCase()))">
            <p class="admin-nav-heading">School &amp; Training</p>
            @can('courses.view')
                <x-admin.nav-link :href="route('admin.courses.index')" :active="request()->routeIs('admin.courses.*')" icon="fa-solid fa-graduation-cap" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Courses</x-admin.nav-link>
            @endcan
            @can('hero-slides.view')
                <x-admin.nav-link :href="route('admin.hero-slides.index')" :active="request()->routeIs('admin.hero-slides.*')" icon="fa-regular fa-images" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Hero Slider</x-admin.nav-link>
            @endcan
            @can('settings.manage')
                <x-admin.nav-link :href="route('admin.special-programs.index')" :active="request()->routeIs('admin.special-programs.*')" icon="fa-solid fa-shapes" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Special Programs</x-admin.nav-link>
            @endcan
            @can('first-appointments.view')
                <x-admin.nav-link :href="route('admin.first-appointments.index')" :active="request()->routeIs('admin.first-appointments.*')" icon="fa-solid fa-award" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">1st Appointments Holders</x-admin.nav-link>
            @endcan
            @can('brigade-commanders.view')
                <x-admin.nav-link :href="route('admin.brigade-commanders.index')" :active="request()->routeIs('admin.brigade-commanders.*')" icon="fa-solid fa-medal" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Brigade Commanders</x-admin.nav-link>
            @endcan
            @can('school-leaders.view')
                <x-admin.nav-link :href="route('admin.school-leaders.index')" :active="request()->routeIs('admin.school-leaders.*')" icon="fa-solid fa-user-tie" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">School Leadership</x-admin.nav-link>
                <x-admin.nav-link :href="route('admin.school-leadership-profiles.index')" :active="request()->routeIs('admin.school-leadership-profiles.*')" icon="fa-regular fa-id-card" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Leadership Profiles</x-admin.nav-link>
            @endcan
        </div>
    @endif

    @if (auth()->user()->can('theme-layouts.manage') || auth()->user()->can('settings.manage') || auth()->user()->can('contacts.manage') || auth()->user()->can('redirects.manage'))
        <div class="admin-nav-group" x-show="!navSearch.trim() || Array.from($el.querySelectorAll('a')).some(link => link.innerText.toLowerCase().includes(navSearch.trim().toLowerCase()))">
            <p class="admin-nav-heading">Website &amp; Settings</p>
            @can('theme-layouts.manage')
                <x-admin.nav-link :href="route('admin.theme-layouts.index')" :active="request()->routeIs('admin.theme-layouts.*')" icon="fa-solid fa-table-columns" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Header &amp; Footer</x-admin.nav-link>
            @endcan
            @can('settings.manage')
                <x-admin.nav-link :href="route('admin.site-settings.index')" :active="request()->routeIs('admin.site-settings.*')" icon="fa-solid fa-sliders" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Site Settings</x-admin.nav-link>
            @endcan
            @can('settings.manage')<x-admin.nav-link :href="route('admin.system-tools')" :active="request()->routeIs('admin.system-tools')" icon="fa-solid fa-database" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Backups &amp; System Status</x-admin.nav-link>@endcan
            @can('contacts.manage')
                <livewire:admin.contact-messages.contact-message-nav-link />
            @endcan
            @can('redirects.manage')
                <x-admin.nav-link :href="route('admin.redirects.index')" :active="request()->routeIs('admin.redirects.*')" icon="fa-solid fa-arrow-right-arrow-left" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Redirects</x-admin.nav-link>
            @endcan
        </div>
    @endif

    @if (auth()->user()->can('users.view') || auth()->user()->can('roles.manage') || auth()->user()->can('audit.view'))
        <div class="admin-nav-group" x-show="!navSearch.trim() || Array.from($el.querySelectorAll('a')).some(link => link.innerText.toLowerCase().includes(navSearch.trim().toLowerCase()))">
            <p class="admin-nav-heading">Administration</p>
            @can('users.view')
                <x-admin.nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="fa-solid fa-users" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Users</x-admin.nav-link>
            @endcan
            @can('roles.manage')
                <x-admin.nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')" icon="fa-solid fa-shield-halved" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Roles &amp; Permissions</x-admin.nav-link>
            @endcan
            @can('audit.view')
                <x-admin.nav-link :href="route('admin.audit-logs.index')" :active="request()->routeIs('admin.audit-logs.*')" icon="fa-solid fa-clock-rotate-left" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Activity Logs</x-admin.nav-link>
            @endcan
        </div>
    @endif

    <div class="admin-nav-group" x-show="!navSearch.trim() || Array.from($el.querySelectorAll('a')).some(link => link.innerText.toLowerCase().includes(navSearch.trim().toLowerCase()))">
        <p class="admin-nav-heading">My Account</p>
        <x-admin.nav-link :href="route('admin.account.password')" :active="request()->routeIs('admin.account.password')" icon="fa-solid fa-key" x-show="!navSearch.trim() || $el.innerText.toLowerCase().includes(navSearch.trim().toLowerCase())">Change Password</x-admin.nav-link>
    </div>

    <p
        x-show="navSearch.trim() !== '' && !Array.from($el.closest('nav').querySelectorAll('a')).some(link => link.innerText.toLowerCase().includes(navSearch.trim().toLowerCase()))"
        x-cloak
        class="px-3 py-5 text-center text-xs text-slate-400"
        role="status"
    >
        No matching sections. Try another search.
    </p>
</nav>
