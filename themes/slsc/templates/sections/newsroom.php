<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: News & Updates Room
 * File: templates/sections/newsroom.php
 */

use function SLSC\Template\esc;
use function SLSC\Template\icon;

$newsItems = $newsItems ?? ($content['news'] ?? []);
?>
<section id="news" class="section-space section-muted" aria-labelledby="newsTitle">
    <div class="container">
        <div class="section-heading reveal">
            <div>
                <p class="eyebrow"><span class="label-line" aria-hidden="true"></span><span data-i18n="newsEyebrow">THE LATEST</span></p>
                <h2 id="newsTitle" class="section-title" data-i18n="newsTitle">News & Updates</h2>
                <p class="section-subtitle" data-i18n="newsSubtitle">Discover recent events, achievements and community initiatives.</p>
            </div>
            <a class="button button-outline" href="https://alt.army.lk/signaller/features-news" target="_blank" rel="noopener noreferrer">
                <span data-i18n="allNews">All News</span><?= icon('external') ?>
            </a>
        </div>
        <div class="filter-bar enhanced-only">
            <div class="filter-group" role="group" aria-label="News categories" data-i18n-aria="newsCategories">
                <button class="filter-btn active" type="button" data-news-filter="all" aria-pressed="true" data-i18n="filterAll">All Updates</button>
                <button class="filter-btn" type="button" data-news-filter="events" aria-pressed="false" data-i18n="filterEvents">Events</button>
                <button class="filter-btn" type="button" data-news-filter="welfare" aria-pressed="false" data-i18n="filterWelfare">Welfare</button>
            </div>
            <label class="input-wrap">
                <?= icon('search') ?>
                <span class="sr-only" data-i18n="searchNews">Search news</span>
                <input id="newsSearch" type="search" placeholder="Search news…" data-i18n-placeholder="searchPlaceholder" autocomplete="off">
            </label>
        </div>
        <p class="result-count enhanced-only" id="newsCount" aria-live="polite" aria-atomic="true"><?= count($newsItems) ?> stories</p>
        <div class="news-grid" id="newsGrid">
            <?php foreach ($newsItems as $story): $date = new DateTimeImmutable($story['date']); ?>
            <article class="news-card<?= $story['featured'] ? ' news-featured' : '' ?> reveal" data-news-category="<?= esc($story['category']) ?>" data-search-item="news" data-title-key="<?= esc($story['key']) ?>">
                <a class="news-image" href="<?= esc($story['url']) ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true">
                    <img src="<?= esc($story['image']) ?>" alt="<?= esc($story['alt']) ?>" width="720" height="480" loading="lazy" decoding="async">
                    <?php if ($story['featured']): ?>
                    <span class="image-badge" data-i18n="featuredNews">FEATURED NEWS</span>
                    <?php endif; ?>
                </a>
                <div class="news-body">
                    <div class="news-meta">
                        <span class="news-category" data-i18n="<?= $story['category'] === 'events' ? 'filterEvents' : 'filterWelfare' ?>"><?= ucfirst(esc($story['category'])) ?></span>
                        <time datetime="<?= esc($story['date']) ?>"><?= esc($date->format('d M Y')) ?></time>
                    </div>
                    <h3>
                        <a href="<?= esc($story['url']) ?>" target="_blank" rel="noopener noreferrer" data-entry-link data-entry-title data-i18n="<?= esc($story['key']) ?>"><?= esc($story['title']) ?></a>
                    </h3>
                    <?php if ($story['featured']): ?>
                    <p data-i18n="news1Desc">The Sri Lanka Signal Corps marked its anniversary with a ceremonial parade, recognising its proud history and service.</p>
                    <?php endif; ?>
                    <a class="text-link news-read" href="<?= esc($story['url']) ?>" target="_blank" rel="noopener noreferrer">
                        <span data-i18n="readMore">Read More</span><?= icon('arrow-right') ?>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="empty-state" id="noNews" hidden>
            <?= icon('search') ?>
            <h3 data-i18n="noResults">No matching news articles found.</h3>
            <p data-i18n="tryAnotherSearch">Try another search or reset your filters.</p>
            <button class="button button-outline" type="button" data-reset="news" data-i18n="resetFilters">Reset filters</button>
        </div>
    </div>
</section>

