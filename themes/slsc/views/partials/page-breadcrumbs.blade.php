@php
    $breadcrumbLocale = app()->getLocale();
    $breadcrumbIsSi = $breadcrumbLocale === 'si';
    $breadcrumbTitle = trim(strip_tags(explode('|', $__env->yieldContent('title', $publicSiteName))[0]));
    $breadcrumbParent = null;
    $breadcrumbPrefix = $breadcrumbLocale === 'en' ? '' : '/'.$breadcrumbLocale;

    if (request()->routeIs('school-leadership.*') && ! request()->routeIs('school-leadership.index', 'school-leadership.localized')) {
        $breadcrumbParent = [$breadcrumbIsSi ? 'පත්වීම්' : 'Appointments', url($breadcrumbPrefix.'/school-leadership')];
    } elseif (request()->routeIs('organization.show*')) {
        $breadcrumbParent = [$breadcrumbIsSi ? 'සංවිධානය' : 'Organization', url($breadcrumbPrefix.'/organization')];
    } elseif (request()->routeIs('galleries.show*', 'galleries.news*')) {
        $breadcrumbParent = [$breadcrumbIsSi ? 'ඡායාරූප ගැලරිය' : 'Photo Gallery', url($breadcrumbPrefix.'/galleries')];
    } elseif (request()->routeIs('news.show*')) {
        $breadcrumbParent = [$breadcrumbIsSi ? 'පුවත්' : 'News', url($breadcrumbPrefix.'/news')];
    }
@endphp
<div class="slsc-page-trail">
    <nav class="container" aria-label="{{ $breadcrumbIsSi ? 'පිටු මාර්ගය' : 'Breadcrumb' }}">
        <ol>
            <li><a href="{{ $publicHomeUrl }}"><i class="fa-solid fa-house" aria-hidden="true"></i><span>{{ $breadcrumbIsSi ? 'මුල් පිටුව' : 'Home' }}</span></a></li>
            @if ($breadcrumbParent)
                <li><a href="{{ $breadcrumbParent[1] }}">{{ $breadcrumbParent[0] }}</a></li>
            @endif
            <li><span aria-current="page">{{ $breadcrumbTitle }}</span></li>
        </ol>
    </nav>
</div>
