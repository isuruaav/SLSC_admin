@php($stylesheetAssetBase = asset('themes/slsc/assets'))
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo:ital,wght@0,100..900;1,100..900&family=Maname&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ $stylesheetAssetBase }}/fonts/fontawesome-7/css/all.min.css?v=7.0.0">
@foreach (['fonts', 'base', 'navbar', 'news', 'registration', 'responsive', 'style', 'navigation', 'leadership', 'organization', 'first-appointments', 'brigade-commanders', 'past-appointments', 'pages'] as $stylesheet)
    <link rel="stylesheet" href="{{ $stylesheetAssetBase }}/css/{{ $stylesheet }}.css?v={{ is_file(public_path('themes/slsc/assets/css/'.$stylesheet.'.css')) ? filemtime(public_path('themes/slsc/assets/css/'.$stylesheet.'.css')) : 1 }}">
@endforeach
