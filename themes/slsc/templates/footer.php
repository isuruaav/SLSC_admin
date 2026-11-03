<?php
declare(strict_types=1);

/**
 * AWCMS Template Part: Footer & Dialog Modals
 * File: templates/footer.php
 */

use function SLSC\Template\esc;
use function SLSC\Template\icon;
use function SLSC\Template\footer;
use function SLSC\Template\current_year;

$navItems = $navItems ?? [
    ['top', 'navHome', 'Home'],
    ['about', 'navAbout', 'Leadership'],
    ['news', 'navNews', 'News & Events'],
    ['special-programs', 'navPrograms', 'Special Programs'],
    ['publications', 'navPublications', 'Publications'],
    ['join-us', 'navJoin', 'Join Us'],
    ['contact', 'navContact', 'Contact'],
];
$year = current_year();
?>
</main>
<footer class="footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand" href="#top">
                <img class="brand-logo" src="assets/images/logo/signals-logo.png" alt="" width="3239" height="4412" loading="lazy">
                <span class="brand-copy"><span class="brand-title" data-i18n="brandName">Sri Lanka Signal Corps</span><span class="brand-motto" data-i18n="motto">SWIFT & SURE</span></span>
            </a>
            <p data-i18n="footerDesc">Honouring a proud heritage of service and advancing professional excellence in military communications and technology.</p>
        </div>
        <div>
            <h2 data-i18n="footerLinks">Quick Links</h2>
            <div class="footer-links">
                <?php foreach (array_slice($navItems, 1) as [$id, $key, $label]): ?>
                <a href="#<?= esc($id) ?>" data-i18n="<?= esc($key) ?>"><?= esc($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h2 data-i18n="footerResources">Resources</h2>
            <div class="footer-links">
                <a href="https://www.army.lk/" target="_blank" rel="noopener noreferrer"><span data-i18n="armyPortal">Army Portal</span><?= icon('external') ?></a>
                <a href="https://alt.army.lk/signaller/organization" target="_blank" rel="noopener noreferrer"><span data-i18n="navOrganization">Organization</span><?= icon('external') ?></a>
                <a href="https://alt.army.lk/slsc_va/" target="_blank" rel="noopener noreferrer"><span data-i18n="programVeterans">Veteran Affairs Section</span><?= icon('external') ?></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <span>© <?= esc($year) ?> <span data-i18n="footerCopyright">Sri Lanka Army. All Rights Reserved.</span></span>
            <a href="#top"><span data-i18n="backTop">Back to top</span><?= icon('arrow-up') ?></a>
        </div>
    </div>
</footer>

<button id="backToTop" class="back-to-top enhanced-only" type="button" aria-label="Back to top" data-i18n-aria="backTop"><?= icon('arrow-up') ?></button>

<!-- Global Site Search Dialog -->
<dialog id="siteSearchDialog" class="search-dialog" aria-labelledby="searchDialogTitle">
    <div class="search-dialog-header">
        <div>
            <p class="eyebrow" data-i18n="searchEyebrow">FIND YOUR WAY</p>
            <h2 id="searchDialogTitle" data-i18n="searchTitle">Search the portal</h2>
        </div>
        <button id="closeSearch" class="icon-button" type="button" aria-label="Close search" data-i18n-aria="closeSearch"><?= icon('close') ?></button>
    </div>
    <label class="global-search-input">
        <?= icon('search') ?>
        <span class="sr-only" data-i18n="searchSite">Search the website</span>
        <input id="siteSearchInput" type="search" placeholder="Search news, programs and pages…" data-i18n-placeholder="globalSearchPlaceholder" autocomplete="off" enterkeyhint="search">
    </label>
    <div class="search-scopes" role="group" aria-label="Search categories" data-i18n-aria="searchCategories">
        <button type="button" data-search-scope="all" class="active" aria-pressed="true" data-i18n="searchAll">All</button>
        <button type="button" data-search-scope="pages" aria-pressed="false" data-i18n="searchPages">Pages</button>
        <button type="button" data-search-scope="news" aria-pressed="false" data-i18n="navNews">News & Events</button>
        <button type="button" data-search-scope="programs" aria-pressed="false" data-i18n="navPrograms">Special Programs</button>
    </div>
    <p id="siteSearchCount" class="result-count" aria-live="polite" aria-atomic="true"></p>
    <ul class="search-results" id="siteSearchResults"></ul>
    <p class="search-empty" id="siteSearchEmpty" hidden data-i18n="noSearchResults">No results found. Try another word.</p>
    <div class="search-dialog-footer">
        <span data-i18n="searchKeyboardHint">Use ↑ ↓ to navigate, Enter to open</span>
        <span><kbd>Esc</kbd> <span data-i18n="toClose">to close</span></span>
    </div>
</dialog>

<!-- Interactive Registration Dialog -->
<dialog id="registrationDialog" class="registration-dialog" aria-labelledby="registrationTitle" aria-describedby="registrationIntro">
    <div class="registration-dialog-top">
        <span class="registration-brand-mark"><?= icon('shield') ?><span data-i18n="brandName">Sri Lanka Signal Corps</span></span>
        <button type="button" class="icon-button" data-close-registration aria-label="Close registration" data-i18n-aria="regClose"><?= icon('close') ?></button>
    </div>
    <div class="registration-dialog-content">
        <header class="registration-heading">
            <p class="eyebrow" data-i18n="joinEyebrow">JOIN US</p>
            <h2 id="registrationTitle" data-i18n="regModalTitle">Online registration</h2>
            <p id="registrationIntro" data-i18n="regIntro">Share your details and register your interest in the Sri Lanka Signal Corps.</p>
        </header>
        <?php require dirname(__DIR__) . '/includes/registration-form.php'; ?>
    </div>
</dialog>

<?php footer(); ?>
</body>
</html>

