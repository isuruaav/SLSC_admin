@php
    $corpsNavigation = json_decode(
        file_get_contents(base_path('themes/slsc/navigation/'.$currentLocale.'.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    $navigationHref = static function (array $item) use ($publicHomeUrl): string {
        $href = $item['href'];

        if (str_starts_with($href, 'https://')) {
            return $href;
        }

        if (preg_match('~^/(?:si/)?school-leadership/~', $href)) {
            return url($href);
        }

        if ($href === '/organization' || $href === '/si/organization' || $href === '/si/si-organization') {
            return url(app()->getLocale() === 'si' ? '/si/organization' : '/organization');
        }

        if ($href === '/first-appoinment-holders' || $href === '/si/first-appoinment-holders' || $href === '/si/si-first-appoinment-holders') {
            return url(app()->getLocale() === 'si' ? '/si/first-appoinment-holders' : '/first-appoinment-holders');
        }

        if ($href === '/brigade-commanders' || $href === '/si/brigade-commanders' || $href === '/si/si-brigade-commanders') {
            return url(app()->getLocale() === 'si' ? '/si/brigade-commanders' : '/brigade-commanders');
        }

        $historyAppointmentSlugs = [
            'regt-comdt-col-comdt',
            'chief-signal-officers',
            'directors-of-it',
            '1-bde-comds',
            '2-bde-comds',
            'centre-commandants',
            'regimental-adjutants',
            'regimental-sergeant-major',
            'past-appointments',
        ];
        $cleanSlug = ltrim(preg_replace('~^/(?:si/)?(?:si-)?~', '', $href), '/');
        $normalizedSlug = str_replace('_', '-', $cleanSlug);
        if ($normalizedSlug === 'reg-adjutants') {
            $normalizedSlug = 'regimental-adjutants';
        } elseif ($normalizedSlug === 'reg-sgt-major' || $normalizedSlug === 'regimental-sergent-major') {
            $normalizedSlug = 'regimental-sergeant-major';
        }

        if (in_array($normalizedSlug, $historyAppointmentSlugs, true)) {
            return url(app()->getLocale() === 'si' ? '/si/'.$normalizedSlug : '/'.$normalizedSlug);
        }

        return $publicHomeUrl.(in_array($href, ['/', '/si'], true) ? '' : '#');
    };
@endphp

@foreach ($corpsNavigation as $item)
    @if (isset($item['children']))
        <details class="slsc-nav-group" data-slsc-nav-group>
            <summary class="nav-link">
                <span>{{ $item['label'] }}</span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </summary>
            <ul class="slsc-nav-submenu">
                @foreach ($item['children'] as $child)
                    <li>
                        <a href="{{ $navigationHref($child) }}" @if ($child['new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif>{{ $child['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </details>
    @else
        <a class="nav-link {{ $loop->first && rtrim(url()->current(), '/') === rtrim($publicHomeUrl, '/') ? 'current' : '' }}"
           href="{{ $navigationHref($item) }}"
           @if ($loop->first && rtrim(url()->current(), '/') === rtrim($publicHomeUrl, '/')) aria-current="page" @endif>{{ $item['label'] }}</a>
    @endif
@endforeach
