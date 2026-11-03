@props([
    'title' => 'Dashboard',
])

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full"
>
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        {{ $title }} | {{ config('awcms.name', 'AWCMS') }}
    </title>

    <link rel="stylesheet" href="{{ asset('themes/school-of-signals/assets/fonts/fontawesome-7/css/all.min.css') }}">

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    @livewireStyles
    <style>
        @font-face{font-family:"AWCMS Exo 2";src:url("{{ asset('themes/school-of-signals/assets/fonts/Exo2-Regular.woff2') }}") format("woff2");font-style:normal;font-weight:400;font-display:swap;unicode-range:U+0000-0D7F,U+0E00-10FFFF}
        @font-face{font-family:"AWCMS Noto Serif Sinhala";src:url("{{ asset('themes/school-of-signals/assets/fonts/NotoSerifSinhala-Regular.woff2') }}") format("woff2");font-style:normal;font-weight:400;font-display:swap;unicode-range:U+0D80-0DFF,U+200C-200D}
        .admin-sidebar{background:linear-gradient(180deg,#101f33 0%,#0d192a 100%);color:#fff}
        .admin-brand{display:flex;height:76px;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,.08);padding:0 20px}
        .admin-brand-mark{display:grid;width:39px;height:39px;flex:none;place-items:center;border:1px solid rgba(129,211,166,.24);border-radius:12px;background:linear-gradient(145deg,#1b8155,#176746);color:#e5fff0;box-shadow:0 5px 16px #0a100d55}
        .admin-brand-name{overflow:hidden;color:#f8fafc;font-size:14px;font-weight:750;letter-spacing:.02em;text-overflow:ellipsis;white-space:nowrap}
        .admin-brand-caption{margin-top:3px;color:#92a2b6;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase}
        .admin-sidebar-nav{flex:1;overflow-y:auto;scrollbar-color:#344357 transparent;scrollbar-width:thin}
        .admin-nav-search-box{position:relative;display:flex;min-height:39px;align-items:center;gap:9px;border:1px solid rgba(255,255,255,.1);border-radius:10px;background:rgba(255,255,255,.045);padding:0 10px;color:#8293a8;transition:border-color .15s,background .15s}
        .admin-nav-search-box:focus-within{border-color:rgba(92,190,133,.55);background:rgba(255,255,255,.075);box-shadow:0 0 0 3px rgba(40,150,91,.12)}
        .admin-nav-search-box>i{font-size:11px}
        .admin-nav-search-box input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:#f8fafc;font-size:11px}
        .admin-nav-search-box input::placeholder{color:#8b9aae}
        .admin-nav-search-box input::-webkit-search-cancel-button{cursor:pointer}
        .admin-nav-search-box kbd{flex:none;border:1px solid rgba(255,255,255,.1);border-radius:4px;padding:2px 4px;color:#8b9aae;font-size:8px}
        .admin-nav-search-hint{display:block;margin:6px 3px 0;color:#66788e;font-size:9px}
        .admin-nav-group+.admin-nav-group{margin-top:18px;border-top:1px solid rgba(255,255,255,.07);padding-top:15px}
        .admin-nav-heading{margin:0 0 8px;padding:0 10px;color:#8293a8;font-size:9px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .admin-sidebar-foot{border-top:1px solid rgba(255,255,255,.08);padding:12px}
        .admin-site-link{display:flex;align-items:center;justify-content:space-between;gap:10px;border:1px solid rgba(255,255,255,.1);border-radius:10px;background:rgba(255,255,255,.035);padding:10px 12px;color:#cbd5e1;font-size:12px;font-weight:650;transition:.15s}
        .admin-site-link:hover{border-color:rgba(111,207,152,.3);background:rgba(111,207,152,.1);color:#fff}
        .admin-topbar{border-bottom:1px solid #e6ebef;background:rgba(255,255,255,.94);box-shadow:0 2px 9px rgba(20,35,50,.035);backdrop-filter:blur(12px)}
        .admin-main{min-height:calc(100vh - 140px);background:radial-gradient(ellipse at top left,rgba(231,243,236,.42),transparent 34%),#f5f7f8}
        .admin-page-context{color:#8491a0;font-size:10px;font-weight:750;letter-spacing:.11em;text-transform:uppercase}
        .admin-user-chip{display:flex;align-items:center;gap:10px;border-left:1px solid #e8edf1;padding-left:16px}
        .admin-shell :focus-visible{outline:3px solid #36a66b;outline-offset:3px}
        .admin-shell button,.admin-shell a{-webkit-tap-highlight-color:transparent}
        .admin-nav-group,[x-show]{transition:opacity .16s ease,transform .16s ease}
        .admin-skip-link{position:fixed;top:8px;left:8px;z-index:100;transform:translateY(-150%);border-radius:8px;background:#fff;padding:10px 14px;color:#123c2a;font-size:13px;font-weight:700;box-shadow:0 4px 16px #0002}
        .admin-skip-link:focus{transform:translateY(0)}
        @media(prefers-reduced-motion:reduce){.admin-shell *,.admin-shell *::before,.admin-shell *::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
        @media(max-width:1023px){.admin-brand{height:76px}.admin-sidebar{display:flex;flex-direction:column}}
    </style>
</head>

<body
    class="admin-shell min-h-full bg-zinc-100 text-zinc-950 antialiased"
    x-data="{ sidebarOpen: false }"
>
    <a href="#admin-main" class="admin-skip-link">Skip to main content</a>
    {{-- Mobile overlay --}}
    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-zinc-950/70 lg:hidden"
        @click="sidebarOpen = false"
    ></div>

    {{-- Mobile sidebar --}}
    <aside
        x-cloak
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-[286px] flex-col
               text-white shadow-2xl lg:hidden"
        @click="if ($event.target.closest('a')) sidebarOpen = false"
    >
        <div class="admin-brand justify-between">
            <a
                href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-3"
            >
                <div class="admin-brand-mark overflow-hidden bg-white p-1">
                    <img src="{{ asset('crest.png') }}" alt="" class="h-full w-full rounded-lg object-contain">
                </div>
                <div>
                    <p class="admin-brand-name">{{ config('awcms.name', 'AWCMS') }}</p>
                    <p class="admin-brand-caption">Admin workspace</p>
                </div>
            </a>

            <button
                type="button"
                class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white"
                @click="sidebarOpen = false"
                aria-label="Close navigation"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        @include('admin.partials.sidebar-navigation')
        <div class="admin-sidebar-foot">
            <a href="{{ route('home') }}" target="_blank" rel="noreferrer" class="admin-site-link">
                <span><i class="fa-solid fa-arrow-up-right-from-square mr-2 text-emerald-300" aria-hidden="true"></i>View public website</span>
                <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
            </a>
        </div>
    </aside>

    <div class="min-h-screen lg:flex">
        {{-- Desktop sidebar --}}
        <aside
            class="admin-sidebar sticky top-0 hidden h-screen w-[286px] shrink-0 flex-col
                   text-white lg:flex"
        >
            <div class="admin-brand">
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3"
                >
                    <div class="admin-brand-mark overflow-hidden bg-white p-1">
                        <img src="{{ asset('crest.png') }}" alt="" class="h-full w-full rounded-lg object-contain">
                    </div>
                    <div>
                        <p class="admin-brand-name">{{ config('awcms.name', 'AWCMS') }}</p>
                        <p class="admin-brand-caption">Admin workspace</p>
                    </div>
                </a>
            </div>

            @include('admin.partials.sidebar-navigation')
            <div class="admin-sidebar-foot">
                <a href="{{ route('home') }}" target="_blank" rel="noreferrer" class="admin-site-link">
                    <span><i class="fa-solid fa-arrow-up-right-from-square mr-2 text-emerald-300" aria-hidden="true"></i>View public website</span>
                    <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
                </a>
            </div>
        </aside>

        {{-- Main content area --}}
        <div class="min-w-0 flex-1">
            <header
                class="admin-topbar sticky top-0 z-30 flex min-h-[76px]
                       items-center justify-between
                       px-4
                       sm:px-6 lg:px-8"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="grid size-10 place-items-center rounded-xl border border-zinc-200
                               bg-white text-zinc-700 shadow-sm transition
                               hover:border-emerald-200 hover:bg-emerald-50 lg:hidden"
                        @click="sidebarOpen = true"
                        aria-label="Open navigation"
                    >
                        <i class="fa-solid fa-bars-staggered" aria-hidden="true"></i>
                    </button>

                    <div class="min-w-0">
                        <p class="admin-page-context">
                            Management workspace <span class="mx-1 text-zinc-300">/</span> <span class="normal-case tracking-normal">{{ $title }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="admin-user-chip">
                        <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-zinc-900">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="text-xs text-zinc-500">
                            {{ auth()->user()->roles->pluck('name')->join(', ') }}
                        </p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-sm font-bold text-emerald-700 ring-1 ring-inset ring-emerald-100">
                            {{ auth()->user()->initials() }}
                        </div>
                    </div>

                    <a href="{{ route('admin.account.password') }}" wire:navigate title="Change your password" aria-label="Change your password" class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800">
                        <i class="fa-solid fa-key" aria-hidden="true"></i><span class="hidden sm:inline">Password</span>
                    </a>

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl border border-zinc-200
                                   bg-white px-3 py-2 text-xs font-semibold text-zinc-600
                                   transition hover:border-red-200 hover:bg-red-50 hover:text-red-700"
                        >
                            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span class="hidden sm:inline">Sign out</span>
                        </button>
                    </form>
                </div>
            </header>

            <main id="admin-main" tabindex="-1" class="admin-main p-4 sm:p-6 lg:p-8">
                <div class="mx-auto w-full max-w-7xl">
                    {{ $slot }}
                </div>
            </main>

            <footer
                class="border-t border-zinc-200 bg-white/90
                       px-4 py-4 sm:px-6 lg:px-8"
            >
                <div
                    class="mx-auto flex max-w-7xl flex-col
                           gap-1 text-xs text-zinc-500
                           sm:flex-row sm:items-center
                           sm:justify-between"
                >
                    <p>
                        © {{ now()->year }}
                        {{ config('awcms.full_name') }}
                    </p>

                    <p>
                        Version {{ config('awcms.version') }}
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <x-admin.confirmation-dialog />

    @fluxScripts
    @livewireScripts
</body>
</html>
