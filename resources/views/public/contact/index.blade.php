@extends(($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout") ? "theme-{$activeTheme}::layout" : 'layouts.public')
@section('title', __('public.contact').' | '.($settings->localized('site_name') ?: config('app.name')))
@section('description', __('public.contact_page.description'))
@section('meta_description', __('public.contact_page.description'))
@push('styles')
<style>
    .aw-contact{padding:64px 20px;background:#f5f9f7;color:#10263b}
    .aw-contact *{box-sizing:border-box}
    .aw-contact-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr);gap:48px;max-width:1100px;margin-inline:auto;align-items:start}
    .aw-contact-kicker{margin:0;color:#14804c;font-size:12px;font-weight:750;letter-spacing:.12em;text-transform:uppercase}
    .aw-contact h1{margin:12px 0 16px;font-size:clamp(28px,3vw,38px);line-height:1.3;color:#071b3a}
    .aw-contact-intro,.aw-contact-details{font-size:14px;line-height:1.9;color:#526578}
    .aw-contact-details{display:grid;gap:16px;margin-top:28px;overflow-wrap:anywhere}
    .aw-contact-details p{margin:0}
    .aw-contact-details strong{color:#183b33}
    .aw-contact-details a{color:#116b46;text-decoration:underline;text-underline-offset:3px}
    .aw-contact-card{min-width:0;padding:28px;border:1px solid #d9e5df;border-radius:18px;background:#fff;box-shadow:0 12px 36px rgba(16,55,38,.06)}
    .aw-contact-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px 16px}
    .aw-contact-field{min-width:0}
    .aw-contact-wide,.aw-contact-submit{grid-column:1/-1}
    .aw-contact label{display:block;margin-bottom:8px;font-size:13px;font-weight:700;color:#183b33}
    .aw-contact-required{color:#14804c}
    .aw-contact input:not([hidden]),.aw-contact textarea{display:block;width:100%;min-height:46px;padding:11px 12px;border:1px solid #cbdad2;border-radius:9px;background:#fff;color:#10263b;font:inherit;font-size:14px;line-height:1.6}
    .aw-contact textarea{min-height:160px;resize:vertical}
    .aw-contact input:focus,.aw-contact textarea:focus{outline:3px solid rgba(20,128,76,.16);outline-offset:1px;border-color:#14804c}
    .aw-contact [hidden]{display:none!important}
    .aw-contact-submit{justify-self:start;min-height:46px;padding:12px 24px;border:0;border-radius:9px;background:#126b48;color:#fff;font:inherit;font-size:14px;font-weight:750;cursor:pointer}
    .aw-contact-submit:hover{background:#0b5135}
    .aw-contact-submit:focus-visible{outline:3px solid #60c394;outline-offset:3px}
    .aw-contact-notice{margin-bottom:22px;padding:14px 16px;border:1px solid #a8d8bd;border-radius:10px;background:#edf9f2;color:#145c38;font-size:14px;line-height:1.7}
    .aw-contact-errors{border-color:#ebbec2;background:#fff3f4;color:#962c37}
    .aw-contact-errors ul{margin:0;padding-left:18px}
    @media(max-width:760px){.aw-contact{padding:32px 16px 42px}.aw-contact-layout{grid-template-columns:1fr;gap:26px}.aw-contact-card{padding:22px 18px}.aw-contact-form{grid-template-columns:1fr;gap:18px}.aw-contact-details{margin-top:20px}.aw-contact-submit{justify-self:stretch}}
</style>
@endpush
@section('content')
<section class="aw-contact">
    <div class="aw-contact-layout">
        <div>
            <p class="aw-contact-kicker">{{ __('public.contact') }}</p>
            <h1>{{ __('public.contact_page.title') }}</h1>
            <p class="aw-contact-intro">{{ __('public.contact_page.intro') }}</p>
            <div class="aw-contact-details">
                @if($settings->localized('address'))<p><strong>{{ __('public.contact_page.address') }}:</strong><br>{{ $settings->localized('address') }}</p>@endif
                @if($settings->phone_primary)<p><strong>{{ __('public.contact_page.phone') }}:</strong> <a class="underline" href="tel:{{ preg_replace('/[^+0-9]/', '', $settings->phone_primary) }}">{{ $settings->phone_primary }}</a></p>@endif
                @if($settings->email)<p><strong>{{ __('public.contact_page.email') }}:</strong> <a class="underline" href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></p>@endif
            </div>
        </div>
        <div class="aw-contact-card">
            @if(session('status'))
                <div class="aw-contact-notice" role="status">{{ session('status') === 'Your message has been received.' ? __('public.contact_page.received') : session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="aw-contact-notice aw-contact-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="POST" action="{{ app()->getLocale() === 'si' ? route('contact.store.localized', ['locale' => 'si']) : route('contact.store') }}" class="aw-contact-form">
                @csrf
                <input type="text" name="website" value="" hidden tabindex="-1" autocomplete="off" aria-hidden="true">
                @foreach (['name' => ['text', 150, true], 'email' => ['email', 255, true], 'phone' => ['tel', 50, false], 'subject' => ['text', 200, true]] as $field => [$type, $limit, $required])
                    <div class="aw-contact-field">
                        <label for="contact-{{ $field }}">{{ __('public.contact_page.'.$field) }} @if($required)<span class="aw-contact-required" aria-hidden="true">*</span>@endif</label>
                        <input id="contact-{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" @required($required) maxlength="{{ $limit }}" autocomplete="{{ ['name' => 'name', 'email' => 'email', 'phone' => 'tel', 'subject' => 'off'][$field] }}">
                    </div>
                @endforeach
                <div class="aw-contact-field aw-contact-wide">
                    <label for="contact-message">{{ __('public.contact_page.message') }} <span class="aw-contact-required" aria-hidden="true">*</span></label>
                    <textarea id="contact-message" name="message" required maxlength="10000" rows="7">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="aw-contact-submit">{{ __('public.contact_page.send') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
