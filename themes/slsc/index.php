<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Sri Lanka Signal Corps (SLSC) - Main CMS Template Controller
 * ============================================================================
 * Renders the modular theme components and provides hook points for AWCMS.
 */

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/registration.php';

use function SLSC\Template\get_part;

// Global helper wrappers for backward and inline compatibility
if (!function_exists('e')) {
    function e(string $value): string { return \SLSC\Template\esc($value); }
}
if (!function_exists('icon')) {
    function icon(string $name, string $class = ''): string { return \SLSC\Template\icon($name, $class); }
}
if (!function_exists('asset')) {
    function asset(string $path): string { return \SLSC\Template\asset($path); }
}

$registrationFormState = \SLSC\Registration\formState();
$content = require __DIR__ . '/includes/content.php';

// Data definitions (Overrideable by CMS Database queries)
$slides = $slides ?? [
    [
        'image' => 'assets/images/programs-sample.jpg',
        'alt' => 'Sri Lanka Signal Corps ceremonial parade',
        'label' => 'heroLabel1',
        'overline' => 'THE CORPS OF SIGNALS',
        'title' => 'heroTitle1',
        'heading' => 'Excellence in Military Communications.',
        'description' => 'heroDesc1',
        'copy' => 'A proud heritage of service, discipline, innovation and professional excellence.',
        'cta' => 'discover',
        'button' => 'Discover Our Corps',
        'url' => '#about'
    ],
    [
        'image' => 'assets/images/hero-innovation.jpg',
        'alt' => 'Sri Lanka Signal Corps leadership and activities',
        'label' => 'heroLabel2',
        'overline' => 'LEADERSHIP & INNOVATION',
        'title' => 'heroTitle2',
        'heading' => 'Connecting People. Advancing Technology.',
        'description' => 'heroDesc2',
        'copy' => 'Supporting professional development and modern information and communication technology.',
        'cta' => 'exploreOrganization',
        'button' => 'Explore Organization',
        'url' => 'https://alt.army.lk/signaller/organization'
    ],
    [
        'image' => 'assets/images/hero-memorial.jpg',
        'alt' => 'Sri Lanka Signal Corps memorial',
        'label' => 'heroLabel3',
        'overline' => 'HONOUR & REMEMBRANCE',
        'title' => 'heroTitle3',
        'heading' => 'Honouring Our Proud Legacy.',
        'description' => 'heroDesc3',
        'copy' => 'Remembering the dedication, service and sacrifices of our war heroes.',
        'cta' => 'viewStories',
        'button' => 'View Our Stories',
        'url' => 'https://alt.army.lk/signaller/photo-story'
    ]
];

$navigation = $navigation ?? [
    ['top', 'navHome', 'Home'],
    ['about', 'navAbout', 'Leadership'],
    ['news', 'navNews', 'News & Events'],
    ['special-programs', 'navPrograms', 'Special Programs'],
    ['publications', 'navPublications', 'Publications'],
    ['join-us', 'navJoin', 'Join Us'],
    ['contact', 'navContact', 'Contact']
];

$templateData = [
    'content'               => $content,
    'slides'                => $slides,
    'navItems'              => $navigation,
    'registrationFormState' => $registrationFormState
];

// ============================================================================
// Render Modular Layout & Section Widgets
// ============================================================================
get_part('header', $templateData);
get_part('sections/hero-slider', $templateData);
get_part('sections/news-ticker', $templateData);
get_part('sections/anniversary', $templateData);
get_part('sections/leadership', $templateData);
get_part('sections/newsroom', $templateData);
get_part('sections/special-programs', $templateData);
get_part('sections/publications', $templateData);
get_part('sections/join-us', $templateData);
get_part('sections/contact', $templateData);
get_part('footer', $templateData);
