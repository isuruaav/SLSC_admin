<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Live News Ticker
 * File: templates/sections/news-ticker.php
 */

use function SLSC\Template\icon;
?>
<div class="news-ticker" aria-label="Latest headlines" data-i18n-aria="latestHeadlines">
    <div class="container ticker-inner">
        <span class="ticker-label"><span class="status-dot" aria-hidden="true"></span><span data-i18n="latest">LATEST NEWS</span></span>
        <div class="ticker-window">
            <div class="ticker-track">
                <span data-i18n="ticker1">Signal Corps celebrates its 83rd anniversary</span>
                <span class="ticker-separator" aria-hidden="true">•</span>
                <span data-i18n="ticker2">Welfare initiatives support Signal Corps families</span>
                <span class="ticker-separator" aria-hidden="true">•</span>
                <span data-i18n="ticker3">Excellence in training, technology and service</span>
            </div>
        </div>
        <a class="ticker-all" href="#news"><span data-i18n="viewAll">View All</span><?= icon('arrow-right') ?></a>
    </div>
</div>

