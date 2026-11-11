<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Special Programs Directory
 * File: templates/sections/special-programs.php
 */

use function SLSC\Template\esc;
use function SLSC\Template\icon;

$programs = $programs ?? ($content['programs'] ?? []);

$getProgIcon = static function (string $key, string $cat): string {
    return match (true) {
        str_contains($key, 'Conduct') => 'fa-solid fa-scale-balanced',
        str_contains($key, 'Rugby') => 'fa-solid fa-trophy',
        str_contains($key, 'Flag') => 'fa-solid fa-flag',
        str_contains($key, 'Song') => 'fa-solid fa-music',
        str_contains($key, 'March') => 'fa-solid fa-person-marching',
        str_contains($key, 'Sacrifices') => 'fa-solid fa-medal',
        str_contains($key, 'Anniversary') => 'fa-solid fa-gem',
        str_contains($key, 'Servicemen') => 'fa-solid fa-user-shield',
        str_contains($key, 'Junior') => 'fa-solid fa-children',
        str_contains($key, 'Vanitha') => 'fa-solid fa-hand-holding-heart',
        str_contains($key, 'Achievements') => 'fa-solid fa-certificate',
        str_contains($key, 'Bungalows') => 'fa-solid fa-hotel',
        str_contains($key, 'Research') => 'fa-solid fa-microchip',
        str_contains($key, 'Veteran') => 'fa-solid fa-shield-halved',
        str_contains($key, 'History') => 'fa-solid fa-book-open',
        $cat === 'community' => 'fa-solid fa-users',
        $cat === 'resources' => 'fa-solid fa-folder-open',
        default => 'fa-solid fa-landmark',
    };
};

$heritageCount = count(array_filter($programs, static fn($p) => ($p['category'] ?? '') === 'heritage'));
$communityCount = count(array_filter($programs, static fn($p) => ($p['category'] ?? '') === 'community'));
$resourcesCount = count(array_filter($programs, static fn($p) => ($p['category'] ?? '') === 'resources'));
$allCount = count($programs);
?>
<section id="special-programs" class="section-space special-programs-compact" aria-labelledby="programsTitle">
    <div class="container">
        <div class="section-heading reveal programs-heading-compact">
            <div>
                <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="programsEyebrow">EXPLORE & CONNECT</span></p>
                <h2 id="programsTitle" class="section-title" data-i18n="programsTitle">Special Programs</h2>
                <p class="section-subtitle" data-i18n="programsSubtitle">Traditions, community initiatives and resources. All in one place.</p>
            </div>
            <span class="section-index" aria-hidden="true"><?= $allCount ?><span data-i18n="programsLabel">PROGRAMS</span></span>
        </div>

        {{-- Slim Single-Line Filter Bar --}}
        <div class="filter-bar program-filters enhanced-only program-filters-slim">
            <div class="program-search-wrap">
                <?= icon('search', 'search-input-icon') ?>
                <span class="sr-only" data-i18n="searchPrograms">Search programs</span>
                <input id="programSearch" type="search" placeholder="Find a program…" data-i18n-placeholder="programSearchPlaceholder" autocomplete="off" class="program-search-input">
                <button type="button" class="program-search-clear" id="programSearchClear" aria-label="Clear search" style="display: none;">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <label class="select-wrap program-select-wrap">
                <span class="sr-only" data-i18n="programCategories">Program categories</span>
                <select id="programCategory">
                    <option value="all" data-i18n="allPrograms">All programs</option>
                    <option value="heritage" data-i18n="heritageCategory">Heritage & traditions</option>
                    <option value="community" data-i18n="communityCategory">Community & welfare</option>
                    <option value="resources" data-i18n="resourcesCategory">Knowledge & services</option>
                </select>
                <?= icon('chevron-down') ?>
            </label>
            <div class="program-counter-badge">
                <span class="result-count" id="programCount" aria-live="polite" aria-atomic="true"><?= $allCount ?> programs</span>
            </div>
        </div>

        {{-- 4-Column Compact Landscape Banner Grid --}}
        <ul class="programs-grid" id="programGrid">
            <?php foreach ($programs as $index => $program):
                $cat = $program['category'] ?? 'heritage';
                $key = $program['key'] ?? '';
                $iconClass = $getProgIcon($key, $cat);
            ?>
            <li class="program-item reveal" data-program-category="<?= esc($cat) ?>" data-title-key="<?= esc($key) ?>">
                <a class="program-card program-card-compact program-card--<?= esc($cat) ?>" href="<?= esc($program['url']) ?>" target="_blank" rel="noopener noreferrer" data-search-item="programs" data-title-key="<?= esc($key) ?>" data-entry-link title="<?= esc($program['title']) ?>">
                    <?php if (!empty($program['image_url'])): ?>
                        <div class="program-banner-bg has-image">
                            <img src="<?= esc($program['image_url']) ?>" alt="<?= esc($program['title']) ?>" loading="lazy" class="program-banner-img">
                        </div>
                    <?php else: ?>
                        <div class="program-banner-bg fallback-bg fallback-bg--<?= esc($cat) ?>">
                            <i class="<?= esc($iconClass) ?> program-fallback-icon" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>

                    <span class="program-number-tag" aria-hidden="true"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>

                    <div class="program-banner-bar">
                        <span class="program-banner-title" data-i18n="<?= esc($key) ?>" data-entry-title><?= esc($program['title']) ?></span>
                        <span class="program-banner-arrow" aria-hidden="true">
                            <?= icon('external') ?>
                        </span>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="empty-state program-empty-state" id="noPrograms" hidden>
            <div class="empty-icon-circle" aria-hidden="true">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <h3 data-i18n="noPrograms">No programs match your search.</h3>
            <p data-i18n="tryAnotherSearch">Try another search or reset your filters.</p>
            <button class="button button-outline" type="button" data-reset="programs" data-i18n="resetFilters">
                <i class="fa-solid fa-rotate-left" style="margin-right: 6px;"></i>
                <span data-i18n="resetFilters">Reset filters</span>
            </button>
        </div>
    </div>
</section>
