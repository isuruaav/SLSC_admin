<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Special Programs Directory
 * File: templates/sections/special-programs.php
 */

use function SLSC\Template\esc;
use function SLSC\Template\icon;

$programs = $programs ?? ($content['programs'] ?? []);
?>
<section id="special-programs" class="section-space" aria-labelledby="programsTitle">
    <div class="container">
        <div class="section-heading reveal">
            <div>
                <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="programsEyebrow">EXPLORE & CONNECT</span></p>
                <h2 id="programsTitle" class="section-title" data-i18n="programsTitle">Special Programs</h2>
                <p class="section-subtitle" data-i18n="programsSubtitle">Traditions, community initiatives and resources. All in one place.</p>
            </div>
            <span class="section-index" aria-hidden="true"><?= count($programs) ?><span data-i18n="programsLabel">PROGRAMS</span></span>
        </div>
        <div class="filter-bar program-filters enhanced-only">
            <label class="input-wrap">
                <?= icon('search') ?>
                <span class="sr-only" data-i18n="searchPrograms">Search programs</span>
                <input id="programSearch" type="search" placeholder="Find a program…" data-i18n-placeholder="programSearchPlaceholder" autocomplete="off">
            </label>
            <label class="select-wrap">
                <span class="sr-only" data-i18n="programCategories">Program categories</span>
                <select id="programCategory">
                    <option value="all" data-i18n="allPrograms">All programs</option>
                    <option value="heritage" data-i18n="heritageCategory">Heritage & traditions</option>
                    <option value="community" data-i18n="communityCategory">Community & welfare</option>
                    <option value="resources" data-i18n="resourcesCategory">Knowledge & services</option>
                </select>
                <?= icon('chevron-down') ?>
            </label>
            <span class="result-count" id="programCount" aria-live="polite" aria-atomic="true"><?= count($programs) ?> programs</span>
        </div>
        <ul class="programs-grid" id="programGrid">
            <?php foreach ($programs as $index => $program): ?>
            <li class="program-item reveal" data-program-category="<?= esc($program['category']) ?>" data-title-key="<?= esc($program['key']) ?>">
                <a class="program-card" href="<?= esc($program['url']) ?>" target="_blank" rel="noopener noreferrer" data-search-item="programs" data-title-key="<?= esc($program['key']) ?>" data-entry-link>
                    <span class="program-number" aria-hidden="true"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <h3 data-i18n="<?= esc($program['key']) ?>" data-entry-title><?= esc($program['title']) ?></h3>
                    <?= icon('external', 'program-arrow') ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="empty-state" id="noPrograms" hidden>
            <?= icon('search') ?>
            <h3 data-i18n="noPrograms">No programs match your search.</h3>
            <p data-i18n="tryAnotherSearch">Try another search or reset your filters.</p>
            <button class="button button-outline" type="button" data-reset="programs" data-i18n="resetFilters">Reset filters</button>
        </div>
    </div>
</section>

