@php
    $title = __('errors.'.$messageKey.'.title', [], $errorLocale);
    $message = __('errors.'.$messageKey.'.message', [], $errorLocale);
    $schoolTheme = in_array(config('awcms.active_theme'), ['slsc', 'school-of-signals'], true);
    $siteName = $schoolTheme ? __('public.school_name', [], $errorLocale) : config('app.name', 'AWCMS');
    $logoRelative = is_file(public_path('themes/slsc/assets/images/logo.png'))
        ? 'themes/slsc/assets/images/logo.png'
        : (is_file(public_path('themes/school-of-signals/assets/images/logo.png')) ? 'themes/school-of-signals/assets/images/logo.png' : null);
    $showLogo = (bool) $logoRelative;
@endphp
<!DOCTYPE html>
<html lang="{{ $errorLocale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#101f33">
    <title>{{ __('errors.page_title', ['code' => $status, 'title' => $title], $errorLocale) }} | {{ $siteName }}</title>
    <style>
        @font-face { font-family: "Error Exo"; src: url("{{ asset('themes/slsc/assets/fonts/Exo2-Regular.woff2') }}") format("woff2"); font-style: normal; font-weight: 400; font-display: swap; unicode-range: U+0000-0D7F,U+0E00-10FFFF; }
        @font-face { font-family: "Error Sinhala"; src: url("{{ asset('themes/slsc/assets/fonts/NotoSerifSinhala-Regular.woff2') }}") format("woff2"); font-style: normal; font-weight: 400; font-display: swap; unicode-range: U+0D80-0DFF,U+200C-200D; }
        :root { color-scheme: light; font-family: "Error Exo", "Error Sinhala", "Nirmala UI", system-ui, sans-serif; color: #172c43; background: #f4f7f9; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100svh; background: radial-gradient(ellipse at 0 100%, #e0eee9 0, transparent 48%), radial-gradient(ellipse at 100% 0, #e7eef7 0, transparent 45%), #f4f7f9; }
        a { color: inherit; }
        a:focus-visible { outline: 3px solid #0d9165; outline-offset: 5px; border-radius: 8px; }
        .shell { display: flex; flex-direction: column; width: min(1180px, calc(100% - 64px)); min-height: 100svh; margin: 0 auto; }
        .site-header { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 34px 0; }
        .brand { display: inline-flex; align-items: center; gap: 13px; min-width: 0; text-decoration: none; }
        .brand-mark { display: grid; flex: 0 0 48px; width: 48px; height: 48px; place-items: center; border: 1px solid #d4e0df; border-radius: 14px; background: #fff; color: #08734f; box-shadow: 0 5px 16px #18324708; }
        .brand-mark img { display: block; max-width: 36px; max-height: 38px; object-fit: contain; }
        .brand-name { display: block; font-size: 18px; font-weight: 800; line-height: 1.5; }
        .brand-caption { display: block; margin-top: 2px; color: #657685; font-size: 11px; letter-spacing: .05em; }
        .header-link { flex-shrink: 0; font-size: 12px; font-weight: 700; text-decoration: none; color: #536779; }
        .header-link:hover { color: #08734f; }
        main { display: flex; flex: 1; align-items: center; padding: 30px 0 60px; }
        .error-card { display: grid; grid-template-columns: .95fr 1.05fr; width: 100%; overflow: hidden; border: 1px solid #dce5eb; border-radius: 28px; background: #fff; box-shadow: 0 24px 80px #1631490b, 0 3px 10px #16314904; }
        .art { display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; min-width: 0; padding: 64px 32px 50px; overflow: hidden; background: linear-gradient(145deg, #101f33 0%, #122d43 60%, #123e42 100%); color: #fff; }
        .art::before { position: absolute; inset: 0; background-image: linear-gradient(#ffffff04 1px, transparent 1px), linear-gradient(90deg, #ffffff04 1px, transparent 1px); background-size: 36px 36px; content: ""; opacity: .4; }
        .signal { position: relative; display: grid; width: 238px; height: 238px; place-items: center; }
        .signal svg { position: absolute; inset: 0; width: 100%; height: 100%; }
        .status-number { position: relative; font-family: "Error Exo", system-ui, sans-serif; font-size: 86px; font-weight: 800; letter-spacing: -.06em; line-height: 1; text-shadow: 0 5px 20px #07192b55; }
        .art-caption { position: relative; margin: 27px 0 0; border: 1px solid #8ffbc32e; border-radius: 30px; padding: 8px 16px; color: #b2ead3; background: #091b2d44; font-size: 12px; line-height: 1.8; text-align: center; }
        .copy { min-width: 0; padding: 64px 54px 54px; align-self: center; }
        .eyebrow { display: flex; align-items: center; gap: 8px; margin: 0 0 17px; color: #08734f; font-size: 12px; font-weight: 700; letter-spacing: .04em; }
        .eyebrow::before { width: 7px; height: 7px; border-radius: 50%; background: #0c9669; content: ""; }
        h1 { margin: 0; color: #12263c; font-size: clamp(27px, 3vw, 39px); font-weight: 800; line-height: 1.32; letter-spacing: -.025em; overflow-wrap: anywhere; }
        .message { margin: 21px 0 0; color: #617384; font-size: 15px; line-height: 1.95; }
        .actions { display: flex; flex-wrap: wrap; gap: 11px; margin-top: 30px; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 48px; max-width: 100%; padding: 12px 19px; border: 1px solid #d9e2e9; border-radius: 11px; text-decoration: none; text-align: center; font-size: 13px; font-weight: 700; line-height: 1.7; transition: background .15s, border-color .15s; }
        .button svg { width: 16px; height: 16px; flex-shrink: 0; }
        .primary { border-color: #08734f; background: #08734f; color: #fff; box-shadow: 0 4px 10px #08734f12; }
        .primary:hover { border-color: #065b3f; background: #065b3f; }
        .secondary { background: #fff; color: #314b60; }
        .secondary:hover { border-color: #b4c4cf; background: #f5f8fa; }
        .extra-actions { display: flex; flex-wrap: wrap; gap: 12px 22px; margin-top: 19px; }
        .text-link { color: #08734f; font-size: 12px; line-height: 1.8; text-underline-offset: 4px; }
        .help { margin: 30px 0 0; padding-top: 21px; border-top: 1px solid #eaf0f3; color: #7a8995; font-size: 11px; line-height: 1.9; }
        .site-footer { display: flex; justify-content: space-between; gap: 15px; padding: 0 0 28px; color: #7c8b98; font-size: 11px; line-height: 1.8; }
        html[lang="si"] h1, html[lang="ta"] h1 { font-size: clamp(25px, 2.6vw, 34px); line-height: 1.6; letter-spacing: 0; }
        html[lang="si"] .message, html[lang="ta"] .message { font-size: 14px; line-height: 2.1; }
        @media (max-width: 900px) { .copy { padding: 46px 34px; } .signal { width: 208px; height: 208px; } .status-number { font-size: 76px; } .art { padding: 50px 20px; } }
        @media (max-width: 700px) { .shell { width: min(520px, calc(100% - 32px)); } .site-header { padding: 24px 0; } .header-link { display: none; } main { padding: 12px 0 30px; } .error-card { grid-template-columns: 1fr; border-radius: 22px; } .art { padding: 28px 20px 25px; } .signal { width: 150px; height: 150px; } .status-number { font-size: 59px; } .art-caption { margin-top: 13px; font-size: 11px; padding: 5px 13px; } .copy { padding: 30px 26px 28px; } h1 { font-size: 29px; } .message { font-size: 14px; margin-top: 16px; } .actions { margin-top: 24px; } .help { margin-top: 25px; } .site-footer { flex-direction: column; gap: 3px; padding-bottom: 22px; } }
        @media (max-width: 380px) { .copy { padding: 27px 21px; } .actions .button { width: 100%; } .brand-name { font-size: 16px; } }
        @media (prefers-reduced-motion: reduce) { .button { transition: none; } }
    </style>
</head>
<body>
    <div class="shell">
        <header class="site-header">
            <a class="brand" href="{{ $homeUrl }}">
                <span class="brand-mark" aria-hidden="true">
                    @if ($showLogo)
                        <img src="{{ asset($logoRelative) }}" alt="" width="36" height="38">
                    @else
                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3 4 6v5c0 5 4 8 8 10 4-2 8-5 8-10V6l-8-3Z"/><path d="m8 12 3 3 5-6"/></svg>
                    @endif
                </span>
                <span><span class="brand-name">{{ $siteName }}</span><span class="brand-caption">{{ __('errors.site', [], $errorLocale) }}</span></span>
            </a>
            <a class="header-link" href="{{ $homeUrl }}">{{ __('errors.home', [], $errorLocale) }} &rarr;</a>
        </header>

        <main id="main-content">
            <section class="error-card" aria-labelledby="error-title">
                <div class="art" aria-hidden="true">
                    <div class="signal">
                        <svg viewBox="0 0 240 240" fill="none">
                            <circle cx="120" cy="120" r="111" stroke="#8ffbc3" stroke-opacity=".12"/>
                            <circle cx="120" cy="120" r="88" stroke="#8ffbc3" stroke-opacity=".3" stroke-dasharray="3 8"/>
                            <circle cx="120" cy="120" r="65" fill="#ffffff" fill-opacity=".025" stroke="#8ffbc3" stroke-opacity=".13"/>
                            <path d="M120 5V24M120 216v19M5 120h19M216 120h19" stroke="#8ffbc3" stroke-opacity=".5" stroke-width="2"/>
                            <path d="M40 54 72 86M168 154l32 32" stroke="#8ffbc3" stroke-opacity=".15"/>
                            <circle cx="193" cy="70" r="8" fill="#112d42" stroke="#8ffbc3" stroke-width="2"/>
                            <circle cx="47" cy="170" r="5" fill="#8ffbc3"/>
                            <circle cx="193" cy="70" r="2.5" fill="#8ffbc3"/>
                            <path d="m42 40 5 5m0-5-5 5m155 157 5 5m0-5-5 5" stroke="#8ffbc3" stroke-opacity=".6" stroke-linecap="round"/>
                        </svg>
                        <span class="status-number">{{ $status }}</span>
                    </div>
                    <p class="art-caption">{{ __('errors.code', ['code' => $status], $errorLocale) }}</p>
                </div>

                <div class="copy">
                    <p class="eyebrow">{{ __($status >= 500 ? 'errors.server_eyebrow' : 'errors.eyebrow', [], $errorLocale) }}</p>
                    <h1 id="error-title">{{ $title }}</h1>
                    <p class="message">{{ $message }}</p>
                    <nav class="actions" aria-label="{{ __('errors.home', [], $errorLocale) }}">
                        <a class="button primary" href="{{ $homeUrl }}" data-error-home>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7M5 9v11h14V9M9 20v-7h6v7"/></svg>
                            {{ __('errors.home', [], $errorLocale) }}
                        </a>
                        @if ($backUrl !== null)
                            <a class="button secondary" href="{{ $backUrl }}" data-error-back>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 5-7 7 7 7M5 12h14"/></svg>
                                {{ __('errors.back', [], $errorLocale) }}
                            </a>
                        @endif
                    </nav>
                    @if ($retryUrl !== null || $showSignIn)
                        <div class="extra-actions">
                            @if ($retryUrl !== null)<a class="text-link" href="{{ $retryUrl }}" data-error-retry>{{ __('errors.retry', [], $errorLocale) }}</a>@endif
                            @if ($showSignIn)<a class="text-link" href="{{ url('/login') }}">{{ __('errors.sign_in', [], $errorLocale) }}</a>@endif
                        </div>
                    @endif
                    <p class="help">{{ __('errors.help', ['code' => $status], $errorLocale) }}</p>
                </div>
            </section>
        </main>

        <footer class="site-footer"><span>{{ $siteName }} &copy; {{ date('Y') }}</span><span>{{ __('errors.footer', [], $errorLocale) }}</span></footer>
    </div>
</body>
</html>
