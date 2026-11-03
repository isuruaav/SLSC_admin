<header class="aw-header">
    <div class="aw-header-inner">
        <a class="aw-brand" href="{{ $links[0]['href'] }}">
            [[site_logo]]
            <span>[[site_name]]</span>
        </a>
        <nav class="aw-navigation aw-desktop-navigation">
@foreach ($links as $link)
            <a href="{{ $link['href'] }}"><i class="aw-nav-icon {{ $link['icon'] }}" aria-hidden="true"></i><span class="aw-nav-label">{{ $link['label'] }}</span></a>
@endforeach
        </nav>
        <div class="aw-header-actions">[[mobile_menu_toggle]]</div>
    </div>
    <div id="mobileMenu" class="mobile-menu aw-mobile-menu">
        <div class="aw-mobile-navigation">
@foreach ($links as $link)
            <a class="sos-mobile-link" href="{{ $link['href'] }}"><span class="aw-nav-label"><i class="aw-nav-icon {{ $link['icon'] }}" aria-hidden="true"></i>{{ $link['label'] }}</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a>
@endforeach
        </div>
    </div>
</header>
