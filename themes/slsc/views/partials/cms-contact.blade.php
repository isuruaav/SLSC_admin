@php
    $si = app()->getLocale() === 'si';
    $contactSettings = \App\Models\SiteSetting::query()->first();
    $contactName = $contactSettings?->localized('site_name') ?: config('app.name');
    $contactAddress = $contactSettings?->localized('address');
    $contactEmail = $contactSettings?->email;
    $mapUrl = $contactSettings?->map_url;
    $mapScheme = is_string($mapUrl) ? parse_url($mapUrl, PHP_URL_SCHEME) : null;
    $safeMap = is_string($mapUrl) && filter_var($mapUrl, FILTER_VALIDATE_URL)
        && in_array($mapScheme, ['http', 'https'], true);
    $contactFields = [
        'name' => [$si ? 'ඔබගේ නම' : 'Your name', 'text', 150, true],
        'email' => [$si ? 'විද්‍යුත් තැපෑල' : 'Email address', 'email', 255, true],
        'phone' => [$si ? 'දුරකථන අංකය (අනිවාර්ය නොවේ)' : 'Phone number (optional)', 'tel', 50, false],
        'subject' => [$si ? 'විෂය' : 'Subject', 'text', 200, true],
    ];
@endphp
@push('styles')
<style>
.sos-contact-wrap{background:#f5f8fc;padding:48px 0 64px;color:#17213d}
.sos-contact-shell{width:calc(100% - 32px);max-width:1180px;margin:auto;display:grid;grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr);gap:28px;align-items:start}
.sos-contact-panel{min-width:0;border:1px solid #dce5f2;border-radius:22px;padding:32px;background:#fff;box-shadow:0 12px 32px rgba(7,19,51,.05)}
.sos-contact-panel h2{font-size:26px;line-height:1.4;color:#0d1536;margin:0 0 12px}
.sos-contact-intro{color:#526079;line-height:1.8;margin:0 0 24px}
.sos-contact-detail{padding:20px 0;border-top:1px solid #e4ebf4;overflow-wrap:anywhere}
.sos-contact-detail h3{font-size:15px;color:#007634;margin:0 0 8px}
.sos-contact-detail p,.sos-contact-detail a{font-size:17px;line-height:1.8}
.sos-contact-detail a{display:block;color:#075e88;text-decoration:underline;text-underline-offset:3px}
.sos-contact-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.sos-contact-field{min-width:0}.sos-contact-field label{display:block;font-size:15px;font-weight:700;margin:0 0 8px}
.sos-contact-field input,.sos-contact-field textarea{box-sizing:border-box;width:100%;max-width:100%;border:1px solid #bac8db;border-radius:10px;padding:12px 14px;background:#fff;color:#17213d;font:inherit;font-size:16px;line-height:1.5}
.sos-contact-field textarea{resize:vertical;min-height:160px}.sos-contact-message{margin-top:20px}
.sos-contact-submit{display:inline-flex;justify-content:center;min-height:48px;align-items:center;margin-top:22px;padding:12px 24px;border:0;border-radius:12px;background:#008451;color:#fff;font:inherit;font-weight:800;cursor:pointer}
.sos-contact-submit:hover{background:#006b40}.sos-contact-wrap :focus-visible{outline:3px solid #0076b3;outline-offset:3px}
.sos-contact-error{color:#b42318;font-size:14px;margin-top:6px}.sos-contact-alert{padding:14px 16px;border-radius:10px;margin-bottom:20px;background:#eafff4;color:#006331;line-height:1.7}
.sos-contact-alert-error{background:#fff0ee;color:#b42318}.sos-contact-honey{display:none}
@media(max-width:820px){.sos-contact-shell{grid-template-columns:minmax(0,1fr)}.sos-contact-wrap{padding:30px 0 42px}}
@media(max-width:520px){.sos-contact-panel{padding:22px}.sos-contact-fields{grid-template-columns:minmax(0,1fr)}.sos-contact-submit{width:100%}}
</style>
@endpush
<section class="sos-contact-wrap" aria-label="{{ $si ? 'සම්බන්ධතා තොරතුරු' : 'Contact information' }}">
    <div class="sos-contact-shell">
        <aside class="sos-contact-panel">
            <h2>{{ $contactName }}</h2>
            <p class="sos-contact-intro">{{ $si ? 'විමසීම් සඳහා පහත සම්බන්ධතා භාවිත කරන්න.' : 'Use the contact details below to get in touch with us.' }}</p>
            @if ($contactAddress)
                <div class="sos-contact-detail">
                    <h3>{{ $si ? 'ලිපිනය' : 'Our address' }}</h3>
                    <p>{!! nl2br(e($contactAddress)) !!}</p>
                </div>
            @endif
            @if ($contactSettings?->phone_primary || $contactSettings?->phone_secondary)
                <div class="sos-contact-detail">
                    <h3>{{ $si ? 'අප අමතන්න' : 'Call us' }}</h3>
                    @foreach ([$contactSettings?->phone_primary, $contactSettings?->phone_secondary] as $phone)
                        @if (is_string($phone) && trim($phone) !== '')
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>
                        @endif
                    @endforeach
                </div>
            @endif
            @if (is_string($contactEmail) && filter_var($contactEmail, FILTER_VALIDATE_EMAIL))
                <div class="sos-contact-detail">
                    <h3>{{ $si ? 'විද්‍යුත් තැපෑල' : 'Email us' }}</h3>
                    <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                </div>
            @endif
            @if ($safeMap)
                <div class="sos-contact-detail">
                    <h3>{{ $si ? 'අප වෙත පැමිණෙන්න' : 'Find us' }}</h3>
                    <a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">{{ $si ? 'සිතියම විවෘත කරන්න →' : 'Open location map →' }}</a>
                </div>
            @endif
        </aside>
        <div class="sos-contact-panel">
            <h2>{{ $si ? 'පණිවිඩයක් යවන්න' : 'Send us a message' }}</h2>
            <p class="sos-contact-intro">{{ $si ? 'ඔබගේ විමසීම සහ සම්බන්ධ කරගත හැකි තොරතුරු ඇතුළත් කරන්න.' : 'Tell us about your enquiry and how we can reach you.' }}</p>
            @if (session('status') === 'Your message has been received.')
                <div class="sos-contact-alert" role="status">{{ $si ? 'ඔබගේ පණිවිඩය ලැබී ඇත. ස්තූතියි.' : 'Your message has been received. Thank you.' }}</div>
            @endif
            @if ($errors->any())
                <div class="sos-contact-alert sos-contact-alert-error" role="alert">
                    {{ $si ? 'පහත තොරතුරු පරීක්ෂා කර නැවත යවන්න.' : 'Please check the fields below and try again.' }}
                    @error('website') <p>{{ $message }}</p> @enderror
                </div>
            @endif
            <form method="POST" action="{{ $si ? route('contact.store.localized', ['locale' => 'si']) : route('contact.store') }}">
                @csrf
                <div class="sos-contact-honey" aria-hidden="true">
                    <label for="sos-contact-website">{{ __('public.contact_page.website') }}</label>
                    <input id="sos-contact-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                </div>
                <div class="sos-contact-fields">
                    @foreach ($contactFields as $field => [$label, $type, $limit, $required])
                        <div class="sos-contact-field">
                            <label for="sos-contact-{{ $field }}">{{ $label }}{{ $required ? ' *' : '' }}</label>
                            <input id="sos-contact-{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                                value="{{ old($field) }}" maxlength="{{ $limit }}" @required($required)
                                @if ($errors->has($field)) aria-invalid="true" aria-describedby="sos-error-{{ $field }}" @endif>
                            @error($field) <p class="sos-contact-error" id="sos-error-{{ $field }}">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
                <div class="sos-contact-field sos-contact-message">
                    <label for="sos-contact-message">{{ $si ? 'ඔබගේ පණිවිඩය' : 'Your message' }} *</label>
                    <textarea id="sos-contact-message" name="message" rows="6" maxlength="10000" required
                        @error('message') aria-invalid="true" aria-describedby="sos-error-message" @enderror>{{ old('message') }}</textarea>
                    @error('message') <p class="sos-contact-error" id="sos-error-message">{{ $message }}</p> @enderror
                </div>
                <button class="sos-contact-submit" type="submit">{{ $si ? 'පණිවිඩය යවන්න' : 'Send message' }}</button>
            </form>
        </div>
    </div>
</section>
