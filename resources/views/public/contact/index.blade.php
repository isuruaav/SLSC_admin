@extends(($activeTheme = config('awcms.active_theme')) && view()->exists("theme-{$activeTheme}::layout") ? "theme-{$activeTheme}::layout" : 'layouts.public')
@section('title', __('public.contact').' | '.($settings->localized('site_name') ?: config('app.name')))
@section('description', __('public.contact_page.description'))
@section('meta_description', __('public.contact_page.description'))
@section('content')
<section class="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="grid gap-8 lg:grid-cols-[1fr_1.4fr]">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--site-primary)">{{ __('public.contact') }}</p>
            <h1 class="mt-2 text-3xl font-black text-zinc-950">{{ __('public.contact_page.title') }}</h1>
            <p class="mt-3 text-sm leading-6 text-zinc-600">{{ __('public.contact_page.intro') }}</p>
            <div class="mt-6 space-y-3 text-sm text-zinc-600">
                @if($settings->localized('address'))<p><strong>{{ __('public.contact_page.address') }}:</strong><br>{{ $settings->localized('address') }}</p>@endif
                @if($settings->phone_primary)<p><strong>{{ __('public.contact_page.phone') }}:</strong> {{ $settings->phone_primary }}</p>@endif
                @if($settings->email)<p><strong>{{ __('public.contact_page.email') }}:</strong> {{ $settings->email }}</p>@endif
            </div>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            @if(session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">{{ session('status') === 'Your message has been received.' ? __('public.contact_page.received') : session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="POST" action="{{ app()->getLocale() === 'si' ? route('contact.store.localized', ['locale' => 'si']) : route('contact.store') }}" class="space-y-4">
                @csrf
                <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                @foreach (['name' => ['text', 150, true], 'email' => ['email', 255, true], 'phone' => ['tel', 50, false], 'subject' => ['text', 200, true]] as $field => [$type, $limit, $required])
                    <div>
                        <label for="contact-{{ $field }}" class="mb-1.5 block text-sm font-semibold">{{ __('public.contact_page.'.$field) }}</label>
                        <input id="contact-{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" @required($required) maxlength="{{ $limit }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">
                    </div>
                @endforeach
                <div>
                    <label for="contact-message" class="mb-1.5 block text-sm font-semibold">{{ __('public.contact_page.message') }}</label>
                    <textarea id="contact-message" name="message" required maxlength="10000" rows="7" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="rounded-xl bg-emerald-800 px-5 py-3 text-sm font-bold text-white">{{ __('public.contact_page.send') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
