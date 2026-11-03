# AWCMS Template Integration Guide (Sri Lanka Signal Corps Theme)

This document explains how this **Sri Lanka Signal Corps (SLSC)** template is structured and how to easily integrate it into your **AWCMS** (Army Web Content Management System) or custom CMS.

---

## 1. Modular Architecture Overview

Instead of a monolithic single-file layout, the template is broken into isolated, reusable component parts:

```text
e:\wamp64\www\slsc/
│
├── config.php                     # Central Template Configuration & CMS Hook System
├── template.json                  # AWCMS Theme Manifest (Widget zones, metadata)
├── index.php                      # Main Template Controller & View Assembler
├── register.php                   # Standalone Registration Page
│
├── templates/                     # Layout Components & Section Partials
│   ├── header.php                 # HTML Head, Topbar, Branding, Navigation
│   ├── footer.php                 # Footer Links, Resources, Modals, Scripts
│   └── sections/                  # Discrete CMS Section Widgets:
│       ├── hero-slider.php        # Carousel Widget
│       ├── news-ticker.php        # Breaking News Ticker Widget
│       ├── anniversary.php        # 83rd Anniversary Spotlight Widget
│       ├── leadership.php         # Colonel Commandant Spotlight Widget
│       ├── newsroom.php           # News & Events Grid with Live Filter/Search
│       ├── special-programs.php   # 15 Special Programs Grid Widget
│       ├── publications.php       # The Signaller 3D Publication Widget
│       ├── join-us.php            # Recruitment & Online Registration Teaser
│       └── contact.php            # Regimental Headquarters & Map Widget
│
├── includes/
│   ├── content.php                # Sample Data Arrays (Swap with CMS DB Queries)
│   ├── icons.php                  # Inline SVG Sprite Sheet
│   ├── registration.php           # Backend Registration & SQLite Storage Logic
│   └── registration-form.php      # Reusable Form Component
│
└── assets/
    ├── css/ (style.css, base.css, responsive.css, registration.css, fonts.css)
    ├── js/  (script.js, i18n.js, helpers.js, registration.js)
    ├── fonts/ (Inter, Noto Sans Sinhala)
    └── images/ (Optimized photographs, Signal Corps crest)
```

---

## 2. Connecting to AWCMS

### A. Template Loader (`config.php`)
Every section is rendered using the modular loader function:
```php
use function SLSC\Template\get_part;

get_part('sections/hero-slider', $templateData);
```
In your CMS, `get_part()` automatically extracts all variables passed into `$templateData` (e.g., `$slides`, `$newsItems`, `$programs`) into the local scope of that partial.

### B. Dynamic Content Injection from CMS Database
Currently, default content is loaded from `includes/content.php`. When integrating with AWCMS, replace those arrays in `index.php` with CMS database calls:

```php
// In AWCMS Controller (e.g., SlscHomeController.php):
$slides = $cms->getSliderSlides('home_slider');
$newsItems = $cms->getNewsArticles(['limit' => 4, 'status' => 'published']);
$programs = $cms->getSpecialPrograms(['order' => 'sort_order ASC']);

// Pass into template:
$templateData = [
    'slides'    => $slides,
    'newsItems' => $newsItems,
    'programs'  => $programs,
    'navItems'  => $cms->getMenuItems('main_menu'),
];

get_part('header', $templateData);
get_part('sections/hero-slider', $templateData);
// ... render other widgets
get_part('footer', $templateData);
```

### C. CMS Head & Footer Hooks
The template already includes standard CMS hook functions in `config.php`:
* `\SLSC\Template\head()` inside `templates/header.php`:
  Automatically calls `awcms_head()` if defined, allowing the CMS to inject SEO meta tags, OpenGraph data, or custom stylesheets.
* `\SLSC\Template\footer()` inside `templates/footer.php`:
  Automatically calls `awcms_footer()` if defined, allowing the CMS to inject analytics scripts or tracking codes.

### D. Asset URL Prefixing
If your CMS serves themes from a dynamic directory (such as `/content/themes/slsc/`), define the asset constant:
```php
define('AWCMS_ASSETS_URL', 'https://your-domain.lk/themes/slsc');
```
The `\SLSC\Template\asset('assets/css/style.css')` function will automatically prefix all asset URLs with cache-busting version timestamps (`?v=...`).

---

## 3. Widget & Section Customization (AWCMS Page Builder)

Each section in `templates/sections/` can be turned on, turned off, or rearranged inside AWCMS by altering the render sequence:

| Widget File | Purpose | CMS Data Fields |
|---|---|---|
| `hero-slider.php` | Featured story carousel | `image`, `heading`, `overline`, `button`, `url` |
| `news-ticker.php` | Top headline ticker | Array of strings (`ticker1`, `ticker2`, etc.) |
| `leadership.php` | Commandant profile | `name`, `rank`, `decorations`, `photo`, `motto` |
| `newsroom.php` | Press releases & updates | `title`, `date`, `category`, `image`, `url` |
| `special-programs.php` | 15 official programs | `title`, `category` (heritage/community/resources), `url` |
| `publications.php` | Signaller Magazine | `edition_year`, `magazine_url`, `cover_image` |
| `join-us.php` | Recruitment portal | `title`, `steps`, registration modal trigger |
| `contact.php` | Cantonment contact | `address`, `map_url`, `portal_links` |

---

## 4. Testing & Verification

Before deployment to AWCMS production servers, run the local automated test suite:
```bash
npm test
```
All 26 unit and integration tests verify:
* Zero broken links or missing anchors
* 100% coverage for English & Sinhala (`i18n.js`) translations
* Real-time search and filter responsiveness
* Valid CSRF tokens and rate-limited SQLite registrations
* WCAG accessibility (keyboard focus, screen readers, reduced-motion)

