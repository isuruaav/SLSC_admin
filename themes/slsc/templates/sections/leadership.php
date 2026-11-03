<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Leadership / Colonel Commandant
 * File: templates/sections/leadership.php
 */

use function SLSC\Template\icon;
?>
<section id="about" class="section-space commandant-section" aria-labelledby="commandantTitle">
    <div class="container commandant-layout">
        <div class="commandant-visual reveal">
            <div class="portrait-frame">
                <img src="assets/images/maj_gen_bandaranayake_new.jpg" alt="Major General K M G Bandaranayake USP ndc psc" width="300" height="372" loading="lazy">
            </div>
            <div class="portrait-caption"><?= icon('shield') ?><span data-i18n="brandName">Sri Lanka Signal Corps</span></div>
        </div>
        <div class="commandant-content reveal">
            <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="commandantEyebrow">OUR LEADERSHIP</span></p>
            <h2 id="commandantTitle" class="section-title">
                <span data-i18n="commandantTitle">Colonel Commandant</span>
                <span class="commandant-corps" data-i18n="brandName">Sri Lanka Signal Corps</span>
            </h2>
            <div class="commandant-identity">
                <h3 lang="en">Major General<br>K M G Bandaranayake</h3>
                <p lang="en">USP <span>ndc</span> psc</p>
            </div>
            <div class="commandant-motto">
                <span class="tricolour" aria-hidden="true"></span>
                <span data-i18n="motto">SWIFT & SURE</span>
            </div>
            <p class="muted commandant-note" data-i18n="mottoSubtitle">The proud motto of the Sri Lanka Signal Corps</p>
        </div>
    </div>
</section>

