<?php
declare(strict_types=1);

/**
 * AWCMS Template Part: Header & Navigation
 * File: templates/header.php
 */

use function SLSC\Template\esc;
use function SLSC\Template\asset;
use function SLSC\Template\icon;
use function SLSC\Template\head;

$siteTitle       = $siteTitle ?? 'Sri Lanka Signal Corps | Swift & Sure';
$siteDescription = $siteDescription ?? 'Discover the Sri Lanka Signal Corps: leadership, news, special programs and publications. Swift & Sure.';
$navItems        = $navItems ?? [
    ['top', 'navHome', 'Home'],
    ['about', 'navAbout', 'Leadership'],
    ['news', 'navNews', 'News & Events'],
    ['special-programs', 'navPrograms', 'Special Programs'],
    ['publications', 'navPublications', 'Publications'],
    ['join-us', 'navJoin', 'Join Us'],
    ['contact', 'navContact', 'Contact'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($siteTitle) ?></title>
    <meta name="description" content="<?= esc($siteDescription) ?>">
    <meta name="theme-color" content="#071b3a">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= esc($siteTitle) ?>">
    <meta property="og:description" content="<?= esc($siteDescription) ?>">
    <link rel="icon" type="image/png" href="assets/images/logo/signals-logo.png">
    <link rel="preload" as="image" href="assets/images/programs-sample.jpg" fetchpriority="high">
    <link rel="preload" as="font" type="font/woff2" href="assets/fonts/inter-latin.woff2" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="assets/fonts/noto-sinhala.woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <script type="module" src="<?= asset('assets/js/script.js') ?>"></script>
    <?php head(); ?>
</head>
<body id="top">
<?php require dirname(__DIR__) . '/includes/icons.php'; ?>
<a class="skip-link" href="#mainContent" data-i18n="skipContent">Skip to content</a>
<div class="topbar">
    <div class="container topbar-inner">
        <span class="topbar-label"><?= icon('shield') ?><span data-i18n="topOfficial">Sri Lanka Army | Official Information Portal</span></span>
        <a href="https://www.army.lk/" target="_blank" rel="noopener noreferrer"><span data-i18n="armyPortal">Army Portal</span><?= icon('external') ?></a>
    </div>
</div>
<header class="site-header" id="siteHeader">
    <div class="container brand-bar">
        <a class="brand" href="#top">
            <img class="brand-logo" src="assets/images/logo/signals-logo.png" alt="Sri Lanka Signal Corps crest" width="3239" height="4412">
            <span class="brand-copy"><span class="brand-eyebrow">SRI LANKA ARMY</span><span class="brand-title" data-i18n="brandName">Sri Lanka Signal Corps</span><span class="brand-motto" data-i18n="motto">SWIFT & SURE</span></span>
        </a>
        <div class="header-actions">
            <button class="search-trigger enhanced-only" type="button" data-open-search data-i18n-aria="searchSite" aria-label="Search the website" aria-haspopup="dialog"><?= icon('search') ?><span data-i18n="searchShort">Search</span><kbd>Ctrl K</kbd></button>
            <div class="language-switcher enhanced-only" role="group" aria-label="Website language" data-i18n-aria="websiteLanguage">
                <button type="button" class="lang-btn active" data-lang="en" aria-pressed="true" lang="en">EN</button>
                <button type="button" class="lang-btn" data-lang="si" aria-pressed="false" lang="si">සිං</button>
            </div>
            <button id="mobileMenuBtn" class="menu-button enhanced-only" type="button" aria-controls="navPanel" aria-expanded="false" aria-label="Open navigation" data-i18n-aria="openMenu"><?= icon('menu', 'menu-open-icon') ?><?= icon('close', 'menu-close-icon') ?></button>
        </div>
    </div>
    <nav class="main-nav" aria-label="Main navigation" data-i18n-aria="mainNavigation">
        <div class="container nav-shell">
            <div id="navPanel" class="nav-panel">
                <?php foreach ($navItems as [$id, $key, $label]): ?>
                <a class="nav-link<?= $id === 'top' ? ' current' : '' ?>" href="#<?= esc($id) ?>" data-i18n="<?= esc($key) ?>" data-page-link<?= $id === 'top' ? ' aria-current="location"' : '' ?>><?= esc($label) ?></a>
                <?php endforeach; ?>
            </div>

        </div>
    </nav>
    <div class="scroll-progress" aria-hidden="true"><span id="scrollProgress"></span></div>
</header>
<main id="mainContent" tabindex="-1">

