<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Contact & Headquarters Directory
 * File: templates/sections/contact.php
 */

use function SLSC\Template\icon;
?>
<section id="contact" class="section-space contact-section" aria-labelledby="contactTitle">
    <div class="container contact-grid">
        <div class="contact-copy reveal">
            <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="footerContact">CONTACT & LOCATION</span></p>
            <h2 id="contactTitle" class="section-title" data-i18n="contactTitle">Stay connected<br>with the Corps.</h2>
            <div class="address-block">
                <?= icon('location') ?>
                <div>
                    <h3 data-i18n="regimentalCentre">Regimental Centre</h3>
                    <p data-i18n="footerAddress">Regimental Centre, Panagoda, Sri Lanka</p>
                </div>
            </div>
            <a class="text-link" href="https://www.google.com/maps/search/?api=1&amp;query=Sri+Lanka+Signal+Corps+Regimental+Centre+Panagoda" target="_blank" rel="noopener noreferrer">
                <span data-i18n="openMap">Open in Google Maps</span><?= icon('external') ?>
            </a>
        </div>
        <div class="portal-panel reveal">
            <p class="eyebrow" data-i18n="officialResources">OFFICIAL RESOURCES</p>
            <h3 data-i18n="continueExploring">Continue exploring.</h3>
            <a href="https://alt.army.lk/signaller/" target="_blank" rel="noopener noreferrer">
                <?= icon('globe') ?><span data-i18n="signalPortal">Signal Corps portal</span><?= icon('external') ?>
            </a>
            <a href="https://www.army.lk/" target="_blank" rel="noopener noreferrer">
                <?= icon('shield') ?><span data-i18n="armyPortal">Army Portal</span><?= icon('external') ?>
            </a>
            <a href="https://alt.army.lk/signaller/schoolofsignals/" target="_blank" rel="noopener noreferrer">
                <?= icon('book') ?><span data-i18n="schoolSignals">School of Signals</span><?= icon('external') ?>
            </a>
        </div>
    </div>
</section>

