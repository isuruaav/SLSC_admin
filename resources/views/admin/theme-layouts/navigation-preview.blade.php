<!DOCTYPE html>
<html lang="{{ $previewLocale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'none'; object-src 'none'; form-action 'none'; style-src 'unsafe-inline' {{ request()->getSchemeAndHttpHost() }}; img-src {{ request()->getSchemeAndHttpHost() }} data:; font-src {{ request()->getSchemeAndHttpHost() }};">
    <base href="{{ url('/') }}/">
    <link rel="stylesheet" href="{{ asset('themes/'.$themeSlug.'/assets/css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/'.$themeSlug.'/assets/css/navbar.css') }}?v={{ is_file(base_path('themes/'.$themeSlug.'/assets/css/navbar.css')) ? filemtime(base_path('themes/'.$themeSlug.'/assets/css/navbar.css')) : 1 }}">
    <link rel="stylesheet" href="{{ asset('themes/slsc/assets/fonts/fontawesome-7/css/all.min.css') }}">
    <style>
        {!! $css !!}
        /* Preview behavior only. Public menu scrolling is unchanged. */
        html { height: auto !important; min-height: 0 !important; overflow: hidden; }
        body { margin: 0; height: auto !important; min-height: 0 !important; max-height: none !important; overflow: visible !important; background: #f4f6f8; }
        #mobileMenu { max-height: none !important; overflow: visible !important; }
        @media (max-width: 1120px) {
            #mobileMenu:not(.open) { display: none !important; }
            #mobileMenu.open { display: block !important; }
        }
    </style>
</head>
<body>{!! $html !!}</body>
</html>
