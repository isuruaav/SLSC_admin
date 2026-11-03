<?php
$registrationFormState ??= \SLSC\Registration\formState();
$registrationValues ??= [];
$registrationErrors ??= [];
$registrationErrorCode ??= '';
$registrationReference ??= '';
$registrationStandalone ??= false;
$registrationInterests = [
    'communications' => ['regInterestCommunications', 'Signals & communications'],
    'technology' => ['regInterestTechnology', 'Information technology'],
    'training' => ['regInterestTraining', 'Training opportunities'],
    'general' => ['regInterestGeneral', 'General interest'],
];
$fieldError = static function (string $field) use ($registrationErrors): void {
    $code = $registrationErrors[$field] ?? '';
    echo '<p id="reg-error-' . e($field) . '" class="field-error" data-error-for="' . e($field) . '" data-error-key="' . e($code) . '"' . ($code === '' ? ' hidden' : '') . '>' . ($code !== '' ? e(\SLSC\Registration\errorMessage($code)) : '') . '</p>';
};
?>
<div id="registrationSuccess" class="registration-success" role="status" tabindex="-1"<?= $registrationReference === '' ? ' hidden' : '' ?>>
    <span class="registration-success-icon" aria-hidden="true"><?= icon('check') ?></span>
    <h3 data-i18n="regReceived">Registration received</h3>
    <p data-i18n="regSuccessIntro">Your details have been saved. Keep your reference for your records.</p>
    <span class="registration-reference-label" data-i18n="regReferenceLabel">YOUR REGISTRATION REFERENCE</span>
    <strong id="registrationReference" class="registration-reference"><?= e($registrationReference) ?></strong>
    <div class="registration-success-actions">
        <?php if ($registrationStandalone): ?>
        <a href="register.php" class="button button-outline" data-i18n="regStartNew">New registration</a>
        <a href="index.php#join-us" class="button button-green" data-i18n="regBackWebsite">Back to website</a>
        <?php else: ?>
        <button id="newRegistration" type="button" class="button button-outline" data-i18n="regStartNew">New registration</button>
        <button type="button" class="button button-green" data-close-registration data-i18n="regDone">Done</button>
        <?php endif; ?>
    </div>
</div>
<form id="registrationForm" class="registration-form" action="register.php" method="post" data-endpoint="api/register.php" aria-describedby="registrationIntro"<?= $registrationReference !== '' ? ' hidden' : '' ?>>
    <input type="hidden" name="_token" value="<?= e($registrationFormState['csrf']) ?>">
    <input type="hidden" name="submission_id" value="<?= e($registrationFormState['submission_id']) ?>">
    <div class="registration-honeypot" aria-hidden="true"><label for="reg-website">Website</label><input id="reg-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
    <p id="registrationRequired" class="registration-required" data-i18n="regRequired">Fields marked * are required.</p>
    <div id="registrationAlert" class="registration-alert" role="alert" tabindex="-1" data-error-key="<?= e($registrationErrorCode) ?>"<?= $registrationErrorCode === '' ? ' hidden' : '' ?>><?= $registrationErrorCode !== '' ? e(\SLSC\Registration\errorMessage($registrationErrorCode)) : '' ?></div>
    <div class="registration-fields">
        <div class="form-field form-field-wide"><label for="reg-full_name"><span data-i18n="regFullName">Full name</span> <span class="required-mark" aria-hidden="true">*</span></label><input id="reg-full_name" name="full_name" type="text" autocomplete="name" required minlength="2" maxlength="120" placeholder="Your full name" data-i18n-placeholder="regNamePlaceholder" value="<?= e($registrationValues['full_name'] ?? '') ?>" aria-describedby="reg-error-full_name"<?= isset($registrationErrors['full_name']) ? ' aria-invalid="true"' : '' ?>><?php $fieldError('full_name'); ?></div>
        <div class="form-field"><label for="reg-email"><span data-i18n="regEmail">Email address</span> <span class="required-mark" aria-hidden="true">*</span></label><input id="reg-email" name="email" type="email" autocomplete="email" inputmode="email" required maxlength="254" placeholder="you@example.com" value="<?= e($registrationValues['email'] ?? '') ?>" aria-describedby="reg-error-email"<?= isset($registrationErrors['email']) ? ' aria-invalid="true"' : '' ?>><?php $fieldError('email'); ?></div>
        <div class="form-field"><label for="reg-phone"><span data-i18n="regPhone">Contact number</span> <span class="required-mark" aria-hidden="true">*</span></label><input id="reg-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" required maxlength="30" placeholder="077 123 4567" value="<?= e($registrationValues['phone'] ?? '') ?>" aria-describedby="reg-error-phone"<?= isset($registrationErrors['phone']) ? ' aria-invalid="true"' : '' ?>><?php $fieldError('phone'); ?></div>
        <div class="form-field"><label for="reg-district"><span data-i18n="regDistrict">District / area</span> <span class="required-mark" aria-hidden="true">*</span></label><input id="reg-district" name="district" type="text" autocomplete="address-level2" required minlength="2" maxlength="80" placeholder="Your district or area" data-i18n-placeholder="regDistrictPlaceholder" value="<?= e($registrationValues['district'] ?? '') ?>" aria-describedby="reg-error-district"<?= isset($registrationErrors['district']) ? ' aria-invalid="true"' : '' ?>><?php $fieldError('district'); ?></div>
        <div class="form-field"><label for="reg-interest"><span data-i18n="regInterest">Area of interest</span> <span class="required-mark" aria-hidden="true">*</span></label><select id="reg-interest" name="interest" required aria-describedby="reg-error-interest"<?= isset($registrationErrors['interest']) ? ' aria-invalid="true"' : '' ?>><option value="" data-i18n="regSelectInterest">Select an interest</option><?php foreach ($registrationInterests as $value => [$key, $label]): ?><option value="<?= e($value) ?>" data-i18n="<?= e($key) ?>"<?= ($registrationValues['interest'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><?php $fieldError('interest'); ?></div>
        <div class="form-field form-field-wide"><label for="reg-message"><span><span data-i18n="regMessage">Message</span> <span class="field-optional" data-i18n="regOptional">(optional)</span></span><span class="message-counter" id="registrationMessageCount" aria-hidden="true">0 / 1000</span></label><textarea id="reg-message" name="message" rows="3" maxlength="1000" placeholder="Tell us a little about your interest…" data-i18n-placeholder="regMessagePlaceholder" aria-describedby="reg-error-message"><?= e($registrationValues['message'] ?? '') ?></textarea><?php $fieldError('message'); ?></div>
    </div>
    <div class="registration-consent"><label><input id="reg-consent" name="consent" type="checkbox" value="1" required aria-describedby="reg-error-consent"<?= ($registrationValues['consent'] ?? '') === '1' ? ' checked' : '' ?>><span data-i18n="regConsent">I agree to share my details for registration follow-up.</span></label><?php $fieldError('consent'); ?></div>
    <p class="registration-privacy"><?= icon('shield') ?><span data-i18n="regPrivacy">Your details are saved on this website for registration follow-up.</span></p>
    <div class="registration-form-actions">
        <?php if ($registrationStandalone): ?><a href="index.php#join-us" class="button button-outline" data-i18n="regCancel">Cancel</a><?php else: ?><button type="button" class="button button-outline" data-close-registration data-i18n="regCancel">Cancel</button><?php endif; ?>
        <button id="registrationSubmit" class="button button-green" type="submit"><span class="registration-spinner" aria-hidden="true"></span><span data-registration-submit data-i18n="regSubmit">Submit registration</span><?= icon('arrow-right') ?></button>
    </div>
</form>
