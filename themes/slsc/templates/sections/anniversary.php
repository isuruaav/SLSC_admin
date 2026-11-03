<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Anniversary Spotlight
 * File: templates/sections/anniversary.php
 */

use function SLSC\Template\esc;
use function SLSC\Template\icon;

$anniversaryUrl = $content['news'][0]['url'] ?? 'https://alt.army.lk/signaller/features-news';
?>
<aside class="anniversary-section" aria-label="Featured update" data-i18n-aria="featuredUpdate">
    <div class="container anniversary-grid">
        <div class="anniversary-number" aria-label="83rd anniversary">83<sup>rd</sup><span>1943 — 2026</span></div>
        <div class="anniversary-story">
            <p class="eyebrow" data-i18n="featuredUpdate">FEATURED UPDATE</p>
            <h2 data-i18n="anniversaryTitle">Celebrating a Legacy of Service</h2>
            <p data-i18n="anniversaryDesc">The Sri Lanka Signal Corps commemorated its 83rd anniversary in October 2026.</p>
        </div>
        <a class="text-link" href="<?= esc($anniversaryUrl) ?>" target="_blank" rel="noopener noreferrer">
            <span data-i18n="readStory">Read the Story</span><?= icon('arrow-right') ?>
        </a>
    </div>
</aside>

