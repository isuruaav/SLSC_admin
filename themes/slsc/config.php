<?php
declare(strict_types=1);

/**
 * ============================================================================
 * AWCMS Template Engine Configuration & Bootstrap (SLSC Theme)
 * ============================================================================
 * This file serves as the bridge between standalone preview and AWCMS integration.
 * In standalone mode, it loads sample dataset from includes/content.php.
 * In CMS mode, AWCMS can supply $cmsContext or override functions and variables.
 */

namespace SLSC\Template;

use DateTimeImmutable;
use DateTimeZone;

const THEME_NAME        = 'Sri Lanka Signal Corps Official Theme';
const THEME_SLUG        = 'slsc-portal';
const THEME_VERSION     = '2.0.0';
const THEME_AUTHOR      = 'Sri Lanka Signal Corps IT Directorate';
const THEME_TEXT_DOMAIN = 'slsc';

/**
 * Escape HTML output safely.
 */
function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate asset URL with cache-busting timestamp or CMS asset prefix.
 */
function asset(string $path): string
{
    $fullPath = __DIR__ . '/' . ltrim($path, '/');
    $version = file_exists($fullPath) ? filemtime($fullPath) : THEME_VERSION;
    $base = defined('AWCMS_ASSETS_URL') ? constant('AWCMS_ASSETS_URL') : '';
    if ($base !== '') {
        return esc(rtrim($base, '/') . '/' . ltrim($path, '/') . '?v=' . $version);
    }
    return esc(ltrim($path, '/') . '?v=' . $version);
}

/**
 * Render reusable inline SVG icon from icon sprite sheet.
 */
function icon(string $name, string $class = ''): string
{
    return '<svg class="icon ' . esc($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . esc($name) . '"></use></svg>';
}

/**
 * Modular Template Part Loader.
 * Equivalent to get_template_part() or Blade @include in modern CMS engines.
 * 
 * @param string $slug Template relative path (e.g., 'header', 'sections/hero-slider')
 * @param array $args Data arguments to extract and pass directly into the template
 */
function get_part(string $slug, array $args = []): void
{
    $file = __DIR__ . '/templates/' . ltrim($slug, '/') . '.php';
    if (!file_exists($file)) {
        $file = __DIR__ . '/' . ltrim($slug, '/') . '.php';
    }
    
    if (file_exists($file)) {
        // Extract arguments into local scope for clean view rendering
        extract($args, EXTR_SKIP);
        require $file;
    } else {
        trigger_error("Template part [{$slug}] not found at {$file}", E_USER_WARNING);
    }
}

/**
 * CMS Head Hook placeholder (for dynamic meta, SEO, or CMS scripts).
 */
function head(): void
{
    if (function_exists('awcms_head')) {
        awcms_head();
    }
}

/**
 * CMS Footer Hook placeholder (for analytics, bottom scripts).
 */
function footer(): void
{
    if (function_exists('awcms_footer')) {
        awcms_footer();
    }
}

/**
 * Get Colombo Current Year.
 */
function current_year(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y');
}
