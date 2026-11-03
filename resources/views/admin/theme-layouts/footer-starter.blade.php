<footer class="aw-footer">
    <div class="aw-footer-shell">
        <div class="aw-footer-grid">
            <section>
                <div class="aw-footer-logo">[[site_logo]]</div>
                <h2>[[site_name]]</h2>
                <p>[[site_tagline]]</p>
            </section>
            <section>
                <h2>{{ __('public.quick_links', [], $footerLocale) }}</h2>
                <div class="aw-footer-links">
@foreach ($links as $link)
                    <a class="aw-footer-link" href="{{ $link['href'] }}"><i class="{{ $link['icon'] }}" aria-hidden="true"></i><span>{{ $link['label'] }}</span></a>
@endforeach
                </div>
            </section>
            <section>
                <h2>{{ __('public.contact', [], $footerLocale) }}</h2>
                [[contact_details]]
            </section>
            <section>
                <h2>{{ __('public.follow_us', [], $footerLocale) }}</h2>
                [[social_links]]
            </section>
        </div>
        <div class="aw-footer-bottom"><p>© [[copyright_year]] [[site_name]]. {{ __('public.rights_reserved', [], $footerLocale) }}</p></div>
    </div>
</footer>
