<header class="aw-header aw-header-reference">
    <div class="aw-topbar">
        <div class="aw-topbar-inner">
            <div class="aw-topbar-title{{ $headerContent['topbar'] === '' ? ' aw-hidden' : '' }}"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span class="aw-topbar-text">{{ $headerContent['topbar'] }}</span></div>
            <div class="aw-topbar-right"><span class="aw-topbar-motto{{ $headerContent['motto'] === '' ? ' aw-hidden' : '' }}">{{ $headerContent['motto'] }}</span><div class="aw-header-languages">[[language_switcher]]</div></div>
        </div>
    </div>
    <div class="aw-header-inner">
        <a class="aw-brand" href="{{ $links[0]['href'] ?? '[[site_url:/]]' }}">
            <span class="aw-brand-logo">[[site_logo]]</span>
            <span class="aw-brand-copy"><span class="aw-brand-title">{{ $headerContent['title'] }}</span><span class="aw-brand-subtitle">{{ $headerContent['subtitle'] }}</span></span>
        </a>
        <nav class="aw-navigation aw-desktop-navigation">
@foreach ($links as $link)
@if (!empty($link['children']))
            <div class="drop aw-nav-parent">
                <a href="{{ $link['href'] ?? '#' }}" class="drop-toggle"><i class="aw-nav-icon {{ $link['icon'] }}" aria-hidden="true"></i><span class="aw-nav-label">{{ $link['label'] }}</span><i class="aw-nav-indicator fa-solid fa-chevron-down" aria-hidden="true"></i></a>
                <div class="dropdown aw-nav-submenu">
@foreach ($link['children'] as $child)
                    <a href="{{ $child['href'] }}"@if (!empty($child['new_tab'])) target="_blank" rel="noopener noreferrer"@endif><i class="aw-nav-icon {{ $child['icon'] }}" aria-hidden="true"></i><span class="aw-nav-label">{{ $child['label'] }}</span></a>
@endforeach
                </div>
            </div>
@else
            <a href="{{ $link['href'] }}"><i class="aw-nav-icon {{ $link['icon'] }}" aria-hidden="true"></i><span class="aw-nav-label">{{ $link['label'] }}</span></a>
@endif
@endforeach
        </nav>
        <div class="aw-header-actions">
            <a class="aw-header-conduct{{ $headerContent['conduct'] === '' ? ' aw-hidden' : '' }}" href="{{ $headerContent['conduct_url'] }}"><span class="aw-action-label">{{ $headerContent['conduct'] }}</span></a>
            <span class="aw-header-search">[[search_button]]</span>
            <a class="aw-header-portal{{ $headerContent['portal'] === '' ? ' aw-hidden' : '' }}" href="{{ $headerContent['portal_url'] }}"><i class="fa-solid fa-laptop-code" aria-hidden="true"></i><span class="aw-action-label">{{ $headerContent['portal'] }}</span></a>
            [[mobile_menu_toggle]]
        </div>
    </div>
    <div id="mobileMenu" class="mobile-menu aw-mobile-menu">
        <div class="aw-mobile-navigation">
@foreach ($links as $link)
@if (!empty($link['children']))
            <details class="sos-mobile-dropdown">
                <summary><span class="aw-nav-label"><i class="aw-nav-icon {{ $link['icon'] }}" aria-hidden="true"></i>{{ $link['label'] }}</span><i class="aw-nav-indicator fa-solid fa-chevron-down" aria-hidden="true"></i></summary>
                <div class="sos-mobile-submenu aw-mobile-submenu">
                    @if (($link['href'] ?? '#') !== '#')
                    <a class="sos-mobile-link" href="{{ $link['href'] }}"><span class="aw-nav-label"><i class="aw-nav-icon {{ $link['icon'] }}" aria-hidden="true"></i>{{ $link['label'] }}</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    @endif
@foreach ($link['children'] as $child)
                    <a class="sos-mobile-link" href="{{ $child['href'] }}"@if (!empty($child['new_tab'])) target="_blank" rel="noopener noreferrer"@endif><span class="aw-nav-label"><i class="aw-nav-icon {{ $child['icon'] }}" aria-hidden="true"></i>{{ $child['label'] }}</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a>
@endforeach
                </div>
            </details>
@else
            <a class="sos-mobile-link" href="{{ $link['href'] }}"><span class="aw-nav-label"><i class="aw-nav-icon {{ $link['icon'] }}" aria-hidden="true"></i>{{ $link['label'] }}</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a>
@endif
@endforeach
        </div>
        <div class="aw-mobile-actions{{ $headerContent['conduct'] === '' && $headerContent['portal'] === '' ? ' aw-hidden' : '' }}">
            <a class="aw-header-conduct{{ $headerContent['conduct'] === '' ? ' aw-hidden' : '' }}" href="{{ $headerContent['conduct_url'] }}"><span class="aw-action-label">{{ $headerContent['conduct'] }}</span></a>
            <a class="aw-header-portal{{ $headerContent['portal'] === '' ? ' aw-hidden' : '' }}" href="{{ $headerContent['portal_url'] }}"><i class="fa-solid fa-laptop-code" aria-hidden="true"></i><span class="aw-action-label">{{ $headerContent['portal'] }}</span></a>
        </div>
    </div>
</header>
