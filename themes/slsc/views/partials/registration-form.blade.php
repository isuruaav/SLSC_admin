@php
    $registrationInterests = [
        'communications' => ['regInterestCommunications', 'Signals & communications'],
        'technology' => ['regInterestTechnology', 'Information technology'],
        'training' => ['regInterestTraining', 'Training opportunities'],
        'general' => ['regInterestGeneral', 'General interest'],
    ];
@endphp
<div id="registrationSuccess" class="registration-success" role="status" tabindex="-1" hidden>
    <span class="registration-success-icon" aria-hidden="true">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg>
    </span>
    <h3 data-i18n="regReceived">{{ __('theme-slsc::interface.regReceived') }}</h3>
    <p data-i18n="regSuccessIntro">{{ __('theme-slsc::interface.regSuccessIntro') }}</p>
    <span class="registration-reference-label" data-i18n="regReferenceLabel">{{ __('theme-slsc::interface.regReferenceLabel') }}</span>
    <strong id="registrationReference" class="registration-reference"></strong>
    <div class="registration-success-actions">
        <button id="newRegistration" type="button" class="button button-outline" data-i18n="regStartNew">{{ __('theme-slsc::interface.regStartNew') }}</button>
        <button type="button" class="button button-green" data-close-registration data-i18n="regDone">{{ __('theme-slsc::interface.regDone') }}</button>
    </div>
</div>
<form id="registrationForm" class="registration-form" action="{{ app()->getLocale() === 'si' ? route('contact.store.localized', ['locale' => 'si']) : route('contact.store') }}" method="post" aria-describedby="registrationIntro">
    @csrf
    <div class="registration-honeypot" aria-hidden="true">
        <label for="reg-website">Website</label>
        <input id="reg-website" name="website" type="text" tabindex="-1" autocomplete="off">
    </div>
    <p id="registrationRequired" class="registration-required" data-i18n="regRequired">{{ __('theme-slsc::interface.regRequired') }}</p>
    <div id="registrationAlert" class="registration-alert" role="alert" tabindex="-1" hidden></div>
    <div class="registration-fields">
        <div class="form-field form-field-wide">
            <label for="reg-full_name"><span data-i18n="regFullName">{{ __('theme-slsc::interface.regFullName') }}</span> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="reg-full_name" name="full_name" type="text" autocomplete="name" required minlength="2" maxlength="120" placeholder="{{ __('theme-slsc::interface.regNamePlaceholder') }}" data-i18n-placeholder="regNamePlaceholder">
            <p id="reg-error-full_name" class="field-error" data-error-for="full_name" hidden></p>
        </div>
        <div class="form-field">
            <label for="reg-email"><span data-i18n="regEmail">{{ __('theme-slsc::interface.regEmail') }}</span> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="reg-email" name="email" type="email" autocomplete="email" inputmode="email" required maxlength="254" placeholder="you@example.com">
            <p id="reg-error-email" class="field-error" data-error-for="email" hidden></p>
        </div>
        <div class="form-field">
            <label for="reg-phone"><span data-i18n="regPhone">{{ __('theme-slsc::interface.regPhone') }}</span> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="reg-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" required maxlength="30" placeholder="077 123 4567">
            <p id="reg-error-phone" class="field-error" data-error-for="phone" hidden></p>
        </div>
        <div class="form-field">
            <label for="reg-district"><span data-i18n="regDistrict">{{ __('theme-slsc::interface.regDistrict') }}</span> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="reg-district" name="district" type="text" autocomplete="address-level2" required minlength="2" maxlength="80" placeholder="{{ __('theme-slsc::interface.regDistrictPlaceholder') }}" data-i18n-placeholder="regDistrictPlaceholder">
            <p id="reg-error-district" class="field-error" data-error-for="district" hidden></p>
        </div>
        <div class="form-field">
            <label for="reg-interest"><span data-i18n="regInterest">{{ __('theme-slsc::interface.regInterest') }}</span> <span class="required-mark" aria-hidden="true">*</span></label>
            <select id="reg-interest" name="interest" required>
                <option value="" data-i18n="regSelectInterest">{{ __('theme-slsc::interface.regSelectInterest') }}</option>
                @foreach ($registrationInterests as $value => [$key, $label])
                    <option value="{{ $value }}" data-i18n="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <p id="reg-error-interest" class="field-error" data-error-for="interest" hidden></p>
        </div>
        <div class="form-field form-field-wide">
            <label for="reg-message">
                <span><span data-i18n="regMessage">{{ __('theme-slsc::interface.regMessage') }}</span> <span class="field-optional" data-i18n="regOptional">{{ __('theme-slsc::interface.regOptional') }}</span></span>
                <span class="message-counter" id="registrationMessageCount" aria-hidden="true">0 / 1000</span>
            </label>
            <textarea id="reg-message" name="message" rows="3" maxlength="1000" placeholder="{{ __('theme-slsc::interface.regMessagePlaceholder') }}" data-i18n-placeholder="regMessagePlaceholder"></textarea>
            <p id="reg-error-message" class="field-error" data-error-for="message" hidden></p>
        </div>
    </div>
    <div class="registration-consent">
        <label>
            <input id="reg-consent" name="consent" type="checkbox" value="1" required>
            <span data-i18n="regConsent">{{ __('theme-slsc::interface.regConsent') }}</span>
        </label>
        <p id="reg-error-consent" class="field-error" data-error-for="consent" hidden></p>
    </div>
    <p class="registration-privacy">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-shield"></use></svg>
        <span data-i18n="regPrivacy">{{ __('theme-slsc::interface.regPrivacy') }}</span>
    </p>
    <div class="registration-form-actions">
        <button type="button" class="button button-outline" data-close-registration data-i18n="regCancel">{{ __('theme-slsc::interface.regCancel') }}</button>
        <button id="registrationSubmit" class="button button-green" type="submit">
            <span class="registration-spinner" aria-hidden="true"></span>
            <span data-registration-submit data-i18n="regSubmit">{{ __('theme-slsc::interface.regSubmit') }}</span>
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg>
        </button>
    </div>
</form>

