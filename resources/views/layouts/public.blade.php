@php
    $publicLocale = app()->getLocale();
    $publicHomeUrl = $publicLocale === 'en' ? route('home') : route('home.localized', ['locale' => $publicLocale]);
    $publicNewsUrl = $publicLocale === 'en' ? route('news.index') : route('news.index.localized', ['locale' => $publicLocale]);
    $publicGalleryUrl = \App\Services\GalleryLocale::indexUrl($publicLocale);
    $publicDocumentsUrl = $publicLocale === 'si' ? route('documents.index.localized', ['locale' => 'si']) : route('documents.index');
    $publicContactUrl = $publicLocale === 'si' ? route('contact.create.localized', ['locale' => 'si']) : route('contact.create');
    $publicSiteName = $siteSettings?->localized('site_name') ?: config('app.name');
    $publicTagline = $siteSettings?->localized('site_tagline');
    $publicAddress = $siteSettings?->localized('address');
    $publicFooterText = $siteSettings?->localized('footer_text');
    $publicDescription = app()->getLocale() === 'si'
        ? ($publicTagline ?: __('public.site_description'))
        : ($siteSettings?->default_seo_description ?: $publicTagline ?: __('public.site_description'));
    $primaryColour = $siteSettings?->primary_color ?: '#166534';
    $accentColour = $siteSettings?->accent_color ?: '#ca8a04';
    $logoUrl = $siteSettings?->logo ? app(\App\Services\MediaUrlService::class)->thumbnailOrOriginal($siteSettings->logo) : null;
    $faviconUrl = $siteSettings?->favicon ? app(\App\Services\MediaUrlService::class)->thumbnailOrOriginal($siteSettings->favicon) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-family="{{ $siteSettings?->theme_family ?: 'army-unit' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', app()->getLocale() === 'si' ? $publicSiteName : ($siteSettings?->default_seo_title ?: $publicSiteName))</title>
    <meta name="description" content="@yield('description', $publicDescription)">
    @hasSection('canonical')<link rel="canonical" href="@yield('canonical')">@endif
    @if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
    @yield('meta')
    <style>
        @font-face{font-family:"AWCMS Exo 2";src:url("{{ asset('themes/slsc/assets/fonts/Exo2-Regular.woff2') }}") format("woff2");font-style:normal;font-weight:400;font-display:swap;unicode-range:U+0000-0D7F,U+0E00-10FFFF}
        @font-face{font-family:"AWCMS Noto Serif Sinhala";src:url("{{ asset('themes/slsc/assets/fonts/NotoSerifSinhala-Regular.woff2') }}") format("woff2");font-style:normal;font-weight:400;font-display:swap;unicode-range:U+0D80-0DFF,U+200C-200D}
        :root{--awcms-font-family:"AWCMS Exo 2","AWCMS Noto Serif Sinhala",sans-serif;--site-primary:{{ $primaryColour }};--site-accent:{{ $accentColour }};}
        html[data-theme-family="training-school"] body{background:#f8fafc;}
        html[data-theme-family="sfhq"] header{border-bottom-color:#d4d4d8;}
        html[data-theme-family="establishment"] body{background:#fafafa;}
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased" x-data="{ mobileMenu: false }">
    <div class="h-1" style="background:var(--site-accent)"></div>
    <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ $publicHomeUrl }}" class="flex min-w-0 items-center gap-3">
                @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ __('public.logo_label', ['name' => $publicSiteName]) }}" class="h-12 w-12 rounded-lg object-contain">@endif
                <span class="min-w-0"><span class="block truncate text-lg font-black tracking-tight text-zinc-950">{{ $publicSiteName }}</span>@if($publicTagline)<span class="block truncate text-xs text-zinc-500">{{ $publicTagline }}</span>@endif</span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('public.primary_navigation') }}">
                @if($primaryMenu)
                    @foreach($primaryMenu->rootItems->where('is_active', true) as $item)
                        @if($item->children->where('is_active', true)->isNotEmpty())
                            <div class="group relative"><a href="{{ $item->resolvedUrl() }}" @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="inline-flex rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">{{ $item->labelForLocale() }} ▾</a><div class="invisible absolute left-0 top-full z-50 min-w-56 rounded-xl border border-zinc-200 bg-white p-2 opacity-0 shadow-xl transition group-hover:visible group-hover:opacity-100">@foreach($item->children->where('is_active', true) as $child)<a href="{{ $child->resolvedUrl() }}" @if($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="block rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">{{ $child->labelForLocale() }}</a>@endforeach</div></div>
                        @else
                            <a href="{{ $item->resolvedUrl() }}" @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">{{ $item->labelForLocale() }}</a>
                        @endif
                    @endforeach
                @else
                    <a href="{{ $publicHomeUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">{{ __('public.home') }}</a>
                    <a href="{{ $publicNewsUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">{{ __('public.news') }}</a>
                    <a href="{{ $publicGalleryUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">{{ __('public.galleries') }}</a>
                    <a href="{{ $publicDocumentsUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">{{ __('public.documents') }}</a>
                    <a href="{{ $publicContactUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">{{ __('public.contact') }}</a>
                @endif
                @auth<a href="{{ route('admin.dashboard') }}" class="ml-2 rounded-lg px-3 py-2 text-sm font-bold text-white" style="background:var(--site-primary)">{{ __('public.admin') }}</a>@endauth
            </nav>

            <button type="button" @click="mobileMenu = ! mobileMenu" class="rounded-xl border border-zinc-200 p-2.5 text-zinc-700 lg:hidden" aria-label="{{ __('public.toggle_navigation') }}">☰</button>
        </div>
        <div x-cloak x-show="mobileMenu" class="border-t border-zinc-200 bg-white px-4 py-4 lg:hidden">
            <nav class="mx-auto grid max-w-7xl gap-1">
                @if($primaryMenu)
                    @foreach($primaryMenu->rootItems->where('is_active', true) as $item)
                        <a href="{{ $item->resolvedUrl() }}" @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700">{{ $item->labelForLocale() }}</a>
                        @foreach($item->children->where('is_active', true) as $child)<a href="{{ $child->resolvedUrl() }}" @if($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="ml-5 rounded-lg px-3 py-2 text-sm text-zinc-600">— {{ $child->labelForLocale() }}</a>@endforeach
                    @endforeach
                @else
                    <a href="{{ $publicHomeUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold">{{ __('public.home') }}</a><a href="{{ $publicNewsUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold">{{ __('public.news') }}</a><a href="{{ $publicGalleryUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold">{{ __('public.galleries') }}</a><a href="{{ $publicDocumentsUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold">{{ __('public.documents') }}</a><a href="{{ $publicContactUrl }}" class="rounded-lg px-3 py-2 text-sm font-semibold">{{ __('public.contact') }}</a>
                @endif
            </nav>
        </div>
    </header>

    <main>@yield('content')</main>

    <footer class="mt-16 border-t border-zinc-800 bg-zinc-950 text-zinc-300">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">
            <div><p class="text-lg font-black text-white">{{ $publicSiteName }}</p><p class="mt-3 text-sm leading-6 text-zinc-400">{{ $publicFooterText ?: $publicTagline }}</p></div>
            <div><p class="text-sm font-bold uppercase tracking-wide text-white">{{ __('public.contact') }}</p><div class="mt-3 space-y-1 text-sm text-zinc-400">@if($publicAddress)<p>{{ $publicAddress }}</p>@endif @if($siteSettings?->phone_primary)<p>{{ $siteSettings->phone_primary }}</p>@endif @if($siteSettings?->email)<p>{{ $siteSettings->email }}</p>@endif</div></div>
            <div><p class="text-sm font-bold uppercase tracking-wide text-white">{{ __('public.connect') }}</p><div class="mt-3 flex flex-wrap gap-2">@foreach($publicSocialLinks as $social)<a href="{{ $social->url }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-zinc-700 px-3 py-2 text-xs font-semibold text-zinc-300 hover:bg-zinc-800">{{ $social->label ?: $social->platform }}</a>@endforeach</div></div>
        </div>
        <div class="border-t border-zinc-800 px-4 py-4 text-center text-xs text-zinc-500">&copy; {{ now()->year }} {{ $publicSiteName }}. {{ __('public.rights_reserved') }}</div>
    </footer>
</body>
</html>
