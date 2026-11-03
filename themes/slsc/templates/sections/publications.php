<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Digital Publications & Journal
 * File: templates/sections/publications.php
 */

use function SLSC\Template\icon;
?>
<section id="publications" class="publication-section" aria-labelledby="publicationTitle">
    <div class="container">
        <div class="publication-panel reveal">
            <div class="publication-copy">
                <p class="eyebrow" data-i18n="pubEyebrow">DIGITAL PUBLICATIONS</p>
                <h2 id="publicationTitle" data-i18n="pubTitle">The Signaller</h2>
                <p data-i18n="pubDesc">Explore the publications and stories that preserve the heritage, achievements and professional identity of the Sri Lanka Signal Corps.</p>
                <a class="button button-white" href="https://alt.army.lk/signaller/content/%E2%80%98-signaller%E2%80%99-magazine-2024-launched-web" target="_blank" rel="noopener noreferrer">
                    <?= icon('book') ?><span data-i18n="pubButton">Explore Publication</span><?= icon('external') ?>
                </a>
            </div>
            <div class="publication-art" aria-hidden="true">
                <div class="book-shadow"></div>
                <div class="book-cover">
                    <span class="book-edition">2024</span>
                    <img src="assets/images/logo/signals-logo.png" alt="" width="3239" height="4412" loading="lazy">
                    <span class="book-name">THE<br>SIGNALLER</span>
                    <span class="book-corps">SRI LANKA SIGNAL CORPS</span>
                    <span class="tricolour"></span>
                </div>
            </div>
        </div>
    </div>
</section>

