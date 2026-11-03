<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Recruitment / Join Us
 * File: templates/sections/join-us.php
 */

use function SLSC\Template\icon;
?>
<section id="join-us" class="section-space join-section" aria-labelledby="joinTitle">
    <div class="container join-layout">
        <div class="join-copy reveal">
            <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="joinEyebrow">JOIN US</span></p>
            <h2 id="joinTitle" class="section-title" data-i18n="joinTitle">Be part of our journey.</h2>
            <p class="section-subtitle" data-i18n="joinDescription">Interested in signals, technology or training? Register your interest with the Sri Lanka Signal Corps.</p>
            <a class="button button-green join-cta" href="register.php" data-open-registration aria-haspopup="dialog">
                <span data-i18n="joinRegister">Register online</span><?= icon('arrow-right') ?>
            </a>
            <p class="join-note" data-i18n="joinNote">Share your details, choose your interest and receive a registration reference.</p>
        </div>
        <ol class="join-steps reveal" aria-label="Registration steps" data-i18n-aria="joinStepsLabel">
            <li>
                <span class="join-step-number" aria-hidden="true">01</span>
                <div>
                    <h3 data-i18n="joinStepOne">Share your details</h3>
                    <p data-i18n="joinStepOneCopy">Enter your name and contact information.</p>
                </div>
            </li>
            <li>
                <span class="join-step-number" aria-hidden="true">02</span>
                <div>
                    <h3 data-i18n="joinStepTwo">Choose your interest</h3>
                    <p data-i18n="joinStepTwoCopy">Tell us which area you would like to explore.</p>
                </div>
            </li>
            <li>
                <span class="join-step-number" aria-hidden="true">03</span>
                <div>
                    <h3 data-i18n="joinStepThree">Keep your reference</h3>
                    <p data-i18n="joinStepThreeCopy">Submit the form and save your registration reference.</p>
                </div>
            </li>
        </ol>
    </div>
</section>

