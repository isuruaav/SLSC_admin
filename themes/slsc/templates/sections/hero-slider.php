<?php
declare(strict_types=1);

/**
 * AWCMS Section Widget: Hero Slider Carousel
 * File: templates/sections/hero-slider.php
 */

use function SLSC\Template\esc;
use function SLSC\Template\icon;

$slides = $slides ?? [
    ['image' => 'assets/images/programs-sample.jpg', 'alt' => 'Sri Lanka Signal Corps ceremonial parade', 'label' => 'heroLabel1', 'overline' => 'THE CORPS OF SIGNALS', 'title' => 'heroTitle1', 'heading' => 'Excellence in Military Communications.', 'description' => 'heroDesc1', 'copy' => 'A proud heritage of service, discipline, innovation and professional excellence.', 'cta' => 'discover', 'button' => 'Discover Our Corps', 'url' => '#about'],
    ['image' => 'assets/images/hero-innovation.jpg', 'alt' => 'Sri Lanka Signal Corps leadership and activities', 'label' => 'heroLabel2', 'overline' => 'LEADERSHIP & INNOVATION', 'title' => 'heroTitle2', 'heading' => 'Connecting People. Advancing Technology.', 'description' => 'heroDesc2', 'copy' => 'Supporting professional development and modern information and communication technology.', 'cta' => 'exploreOrganization', 'button' => 'Explore Organization', 'url' => 'https://alt.army.lk/signaller/organization'],
    ['image' => 'assets/images/hero-memorial.jpg', 'alt' => 'Sri Lanka Signal Corps memorial', 'label' => 'heroLabel3', 'overline' => 'HONOUR & REMEMBRANCE', 'title' => 'heroTitle3', 'heading' => 'Honouring Our Proud Legacy.', 'description' => 'heroDesc3', 'copy' => 'Remembering the dedication, service and sacrifices of our war heroes.', 'cta' => 'viewStories', 'button' => 'View Our Stories', 'url' => 'https://alt.army.lk/signaller/photo-story']
];
?>
<section class="hero-section" aria-label="Featured stories" data-i18n-aria="featuredStories">
    <div class="hero-slider" id="heroSlider" role="region" aria-roledescription="carousel" aria-label="Featured stories" tabindex="0" data-i18n-aria="featuredStories">
        <?php foreach ($slides as $index => $slide): ?>
        <article class="hero-slide<?= $index === 0 ? ' is-active' : '' ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"<?= $index !== 0 ? ' inert' : '' ?>>
            <img class="hero-image" src="<?= esc($slide['image']) ?>" alt="<?= esc($slide['alt']) ?>" width="1090" height="326" <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
            <div class="container hero-inner">
                <div class="hero-content">
                    <p class="hero-overline"><span class="hero-pulse" aria-hidden="true"></span><span data-i18n="<?= esc($slide['label']) ?>"><?= esc($slide['overline']) ?></span></p>
                    <h1 data-i18n="<?= esc($slide['title']) ?>"><?= esc($slide['heading']) ?></h1>
                    <p class="hero-description" data-i18n="<?= esc($slide['description']) ?>"><?= esc($slide['copy']) ?></p>
                    <div class="hero-links">
                        <a class="button button-green" href="<?= esc($slide['url']) ?>"<?= str_starts_with($slide['url'], 'https:') ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                            <span data-i18n="<?= esc($slide['cta']) ?>"><?= esc($slide['button']) ?></span><?= icon('arrow-right') ?>
                        </a>
                        <a class="hero-secondary" href="#special-programs">
                            <span data-i18n="navPrograms">Special Programs</span><?= icon('arrow-right') ?>
                        </a>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <div class="container hero-bottom enhanced-only">
            <div class="hero-pagination">
                <span class="hero-count"><strong id="activeSlideNumber">01</strong><span>/ 03</span></span>
                <div class="slider-dots" role="group" aria-label="Choose a slide" data-i18n-aria="chooseSlide">
                    <?php for ($i = 0; $i < count($slides); $i++): ?>
                    <button type="button" class="slider-dot<?= $i === 0 ? ' active' : '' ?>" data-slide="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>><span></span></button>
                    <?php endfor; ?>
                </div>
                <span class="hero-progress" aria-hidden="true"><span id="heroProgress"></span></span>
            </div>
            <div class="slider-controls">
                <button id="toggleSlider" class="slider-arrow" type="button" aria-label="Pause slideshow" data-i18n-aria="pauseSlider" aria-pressed="false"><?= icon('pause', 'pause-icon') ?><?= icon('play', 'play-icon') ?></button>
                <button id="prevSlide" class="slider-arrow" type="button" aria-label="Previous slide" data-i18n-aria="previousSlide"><?= icon('chevron-left') ?></button>
                <button id="nextSlide" class="slider-arrow" type="button" aria-label="Next slide" data-i18n-aria="nextSlide"><?= icon('chevron-right') ?></button>
            </div>
        </div>
        <p id="slideStatus" class="sr-only" aria-live="polite" aria-atomic="true"></p>
    </div>
</section>

