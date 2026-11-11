<!DOCTYPE html>
<html lang="{{ $previewLocale }}" class="js{{ $previewLocale === 'si' ? ' is-si' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'none'; object-src 'none'; form-action 'none'; style-src 'unsafe-inline' {{ request()->getSchemeAndHttpHost() }} https://fonts.googleapis.com; img-src {{ request()->getSchemeAndHttpHost() }} data:; font-src {{ request()->getSchemeAndHttpHost() }} https://fonts.gstatic.com;">
    <base href="{{ url('/') }}/">
    @if ($themeSlug === 'slsc')
        @include('theme-slsc::partials.stylesheets')
    @else
        @php($themeStylesheet = app(\App\Services\ThemeManager::class)->find($themeSlug)['assets']['css'] ?? 'assets/css/theme.css')
        @foreach (array_unique([$themeStylesheet, 'assets/css/navbar.css']) as $stylesheet)
            @if (is_file(base_path('themes/'.$themeSlug.'/'.$stylesheet)))
                <link rel="stylesheet" href="{{ asset('themes/'.$themeSlug.'/'.$stylesheet) }}?v={{ filemtime(base_path('themes/'.$themeSlug.'/'.$stylesheet)) }}">
            @endif
        @endforeach
        <link rel="stylesheet" href="{{ asset('themes/slsc/assets/fonts/fontawesome-7/css/all.min.css') }}">
    @endif
    <style>
        {!! $css !!}
        /* Preview behavior only. Public menu scrolling is unchanged. */
        html { height: auto !important; min-height: 0 !important; overflow: hidden; }
        body { margin: 0; height: auto !important; min-height: 0 !important; max-height: none !important; overflow: visible !important; background: #f4f6f8; }
        #mobileMenu { max-height: none !important; overflow: visible !important; }
        @media (min-width: 1121px) {
            #mobileMenu { display: none !important; }
        }
        @media (max-width: 1120px) {
            #mobileMenu:not(.open) { display: none !important; }
            #mobileMenu.open { display: block !important; }
        }
    </style>
</head>
<body class="{{ $previewLocale === 'si' ? 'is-si' : '' }}" data-preview-current-url="{{ url($previewLocale === 'en' ? '/' : '/'.$previewLocale) }}">{!! $html !!}</body>
</html>
