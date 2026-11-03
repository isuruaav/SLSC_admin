<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ThemeLayoutLocale;
use App\Enums\ThemeLayoutRegion;
use App\Enums\ThemeLayoutStatus;
use App\Models\ThemeLayout;
use App\Models\ThemeLayoutRevision;
use App\Services\ThemeLayoutSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixThemeHeaderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:fix-header';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore and publish complete responsive header layout (Desktop single-line + Mobile drawer) with search.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting School of Signals header layout restoration and publishing...');

        $enHtml = <<<'HTML'
<div class="topbar managed-school-topbar">
    <div class="container managed-school-container managed-school-topbar-inner">
        <div class="topbar-left">
            <span class="managed-school-topbar-title">
                <i class="fa-solid fa-shield-halved"></i>
                Sri Lanka Signal Corps | School of Signals, Kandy
            </span>
        </div>

        <div class="topbar-right">
            <span class="motto">Technological Sound</span>

            <div class="managed-school-topbar-languages">
                [[language_switcher]]
            </div>
        </div>
    </div>
</div>

<header class="header managed-school-header">
    <div class="managed-school-navbar">
        <div class="container nav-wrap managed-school-container managed-school-navbar-inner">

            <a class="brand managed-school-brand" href="[[site_url:/]]">
                <span class="brand-logo managed-school-logo">
                    [[site_logo]]
                </span>

                <span class="brand-copy managed-school-brand-copy">
                    <span class="brand-title managed-school-brand-title">
                        [[site_name]]
                    </span>

                    <span class="brand-subtitle managed-school-brand-subtitle">
                        Sri Lanka Army
                    </span>
                </span>
            </a>

            <nav class="desktop-nav managed-school-desktop-nav aw-navigation"><a href="[[site_url:/]]">Home</a><a href="[[site_url:/courses]]">Courses</a><a href="[[site_url:/en/pages/aboutus]]">
                    About Us
                </a><a href="[[site_url:/publications]]">Publication</a><div class="drop aw-nav-parent"><a class="drop-toggle" href="#">
        History
        
    <i class="aw-nav-indicator fa-solid fa-chevron-down" aria-hidden="true"></i></a><div class="dropdown aw-nav-submenu"><a href="[[site_url:/en/pages/history-of-school]]">School History</a><a href="[[site_url:/en/history/past-commandants]]">
            Past Commandants
        </a><a href="[[site_url:/en/history/past-chief-instructors]]">
            Past Chief Instructors
 
        </a><a href="[[site_url:/en/history/past-sgt-major]]">
         Past Regemental Sergeant majors
 
        </a></div></div><a href="[[site_url:/galleries]]">Gallery</a><a href="[[site_url:/en/pages/contact-us]]">Contact Us</a></nav>

            <div class="nav-actions managed-school-header-actions">
                <a class="managed-school-conduct-link" href="[[site_url:/document-files/en/code-of-conduct-5/view]]">
                    Code of Conduct
                </a>

                [[search_button]]

                <a class="btn btn-green managed-school-portal" href="https://lms.army.lk">
                    <i class="fa-solid fa-laptop-code"></i>
                    <span>LMS Portal</span>
                </a>

                [[mobile_menu_toggle]]
            </div>
        </div>
    </div>

<div id="mobileMenu" class="mobile-menu">
    <div class="container managed-school-container sos-mobile-navigation">
        [[search_bar]]
<a href="[[site_url:/]]" class="sos-mobile-link"><span class="aw-nav-label">Home</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><a href="[[site_url:/courses]]" class="sos-mobile-link"><span class="aw-nav-label">Courses</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><a href="[[site_url:/en/pages/aboutus]]" class="sos-mobile-link"><span class="aw-nav-label">
                    About Us
                </span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><a href="[[site_url:/publications]]" class="sos-mobile-link"><span class="aw-nav-label">Publication</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><details class="sos-mobile-dropdown"><summary><span class="aw-nav-label">History</span><i class="aw-nav-indicator fa-solid fa-chevron-down" aria-hidden="true"></i></summary><div class="sos-mobile-submenu aw-mobile-submenu"><a href="[[site_url:/en/pages/history-of-school]]" class="sos-mobile-link"><span class="aw-nav-label">School History</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><a href="[[site_url:/en/history/past-commandants]]" class="sos-mobile-link"><span class="aw-nav-label">
            Past Commandants
        </span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><a href="[[site_url:/en/history/past-chief-instructors]]" class="sos-mobile-link"><span class="aw-nav-label">
            Past Chief Instructors
 
        </span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><a href="[[site_url:/en/history/past-sgt-major]]" class="sos-mobile-link"><span class="aw-nav-label">
         Past Regemental Sergeant majors
 
        </span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a></div></details><a href="[[site_url:/galleries]]" class="sos-mobile-link"><span class="aw-nav-label">Gallery</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><a href="[[site_url:/en/pages/contact-us]]" class="sos-mobile-link"><span class="aw-nav-label">Contact Us</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a><a class="sos-mobile-link" href="[[site_url:/document-files/en/code-of-conduct-5/view]]">
            <span>Code of Conduct</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a></div>
</div>
</header>
HTML;

        $siHtml = <<<'HTML'
<div class="topbar managed-school-topbar">
    <div class="container managed-school-container managed-school-topbar-inner">
        <div class="topbar-left">
            <span class="managed-school-topbar-title">
                <i class="fa-solid fa-shield-halved"></i>
                ශ්‍රී ලංකා සංඥා බලකාය | සංඥා පාසල, මහනුවර
            </span>
        </div>

        <div class="topbar-right">
            <span class="motto">තාක්ෂණයෙන් සවිමත්</span>

            <div class="managed-school-topbar-languages">
                [[language_switcher]]
            </div>
        </div>
    </div>
</div>

<header class="header managed-school-header">
    <div class="managed-school-navbar">
        <div class="container nav-wrap managed-school-container managed-school-navbar-inner">

            <a class="brand managed-school-brand" href="[[site_url:/si]]">
                <span class="brand-logo managed-school-logo">
                    [[site_logo]]
                </span>

                <span class="brand-copy managed-school-brand-copy">
                    <span class="brand-title managed-school-brand-title">
                        සංඥා පාසල
                    </span>

                    <span class="brand-subtitle managed-school-brand-subtitle">
                        ශ්‍රී ලංකා යුද්ධ හමුදාව
                    </span>
                </span>
            </a>

            <nav class="desktop-nav managed-school-desktop-nav aw-navigation">

                <a href="[[site_url:/si]]">මුල් පිටුව</a>

                <a href="[[site_url:/si/pages/siaboutus]]">
                    අප ගැන
                </a>

                  <a href="[[site_url:/si/courses]]">
     පාඨමාලා
                </a>

        <a href="[[site_url:/si/publications]]">
     ප්‍රකාශන
                </a>

       <div class="drop aw-nav-parent">
    <a class="drop-toggle" href="#">
        ඉතිහාසය
        <i class="fa-solid fa-chevron-down"></i>
    </a>

    <div class="dropdown aw-nav-submenu">
        <a href="[[site_url:/si/pages/history-of-school]]">පාසලේ ඉතිහාසය</a>

        <a href="[[site_url:/si/history/past-commandants]]">
            හිටපු සේනාවිධායකවරු
        </a>

<a href="[[site_url:/si/history/past-chief-instructors]]">
    හිටපු ප්‍රධාන උපදේශකවරු
</a>

                <a href="[[site_url:/si/history/past-sgt-major]]">
                    හිටපු රෙජිමේන්තු සැරයන් මේජර්වරු
                </a>

 
    </div>
</div>

                <a href="[[site_url:/si/galleries]]">
                    ඡායාරූප
                </a>

                <a href="[[site_url:/si/pages/contact-us]]">අප අමතන්න</a>
            </nav>

            <div class="nav-actions managed-school-header-actions">
                <a class="managed-school-conduct-link" href="[[site_url:/document-files/en/code-of-conduct-5/view]]">
                    චර්යාධර්ම සංග්‍රහය
                </a>

                [[search_button]]

                <a class="btn btn-green managed-school-portal" href="https://lms.army.lk">
                    <i class="fa-solid fa-laptop-code"></i>
                    <span>ඉගෙනුම් පද්ධතිය</span>
                </a>

                [[mobile_menu_toggle]]
            </div>
        </div>
    </div>
<div id="mobileMenu" class="mobile-menu">
    <div class="container managed-school-container sos-mobile-navigation">
        [[search_bar]]


        <a class="sos-mobile-link" href="[[site_url:/si]]">
            <span>මුල් පිටුව</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>

        <a class="sos-mobile-link" href="[[site_url:/si/pages/siaboutus]]">
            <span>අප ගැන</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>

         <a class="sos-mobile-link" href="[[site_url:/si/courses]]">
            <span>පාඨමාලා</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>

        <a class="sos-mobile-link" href="[[site_url:/si/publications]]">
            <span>ප්‍රකාශන</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <details class="sos-mobile-dropdown">
            <summary>
                <span>ඉතිහාසය</span>
                <i class="fa-solid fa-chevron-down"></i>
            </summary>

            <div class="sos-mobile-submenu aw-mobile-submenu">
                <a href="[[site_url:/si/pages/history-of-school]]">
                    පාසලේ ඉතිහාසය
                </a>
                <a href="[[site_url:/si/history/past-commandants]]">
                    හිටපු සේනාවිධායකවරු
                </a>
                <a href="[[site_url:/si/history/past-chief-instructors]]">
                    හිටපු ප්‍රධාන උපදේශකවරු
                </a>

                <a href="[[site_url:/si/history/past-sgt-major]]">
                    හිටපු රෙජිමේන්තු සැරයන් මේජර්වරු
                </a>
              
            </div>
        </details>

        <a class="sos-mobile-link" href="[[site_url:/si/galleries]]">
            <span>ඡායාරූප</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>

        <a class="sos-mobile-link" href="[[site_url:/document-files/en/code-of-conduct-5/view]]">
            <span>චර්යාධර්ම සංග්‍රහය</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>

        <a class="sos-mobile-link" href="[[site_url:/si/pages/contact-us]]">
            <span>අප අමතන්න</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>
</div>
</header>
HTML;

        $navCss = <<<'CSS'
/* =========================================================
   Top Bar Language Switcher (Segmented Pill Button)
   ========================================================= */

.managed-school-topbar .managed-school-topbar-languages,
.topbar .managed-school-topbar-languages,
.topbar-right .managed-school-topbar-languages {
    display: inline-flex !important;
    flex: 0 0 auto !important;
    align-items: center !important;
    margin-left: 12px !important;
}

.managed-school-topbar .cms-language-switcher,
.topbar .cms-language-switcher,
.topbar-right .cms-language-switcher,
nav.cms-language-switcher {
    display: inline-flex !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
    gap: 3px !important;
    margin: 0 !important;
    padding: 3px !important;
    border: 1px solid rgba(255, 255, 255, 0.28) !important;
    border-radius: 10px !important;
    background: rgba(255, 255, 255, 0.12) !important;
    box-sizing: border-box !important;
}

.managed-school-topbar .cms-language-switcher .cms-language-option,
.managed-school-topbar .cms-language-switcher .cms-language-current,
.topbar .cms-language-switcher .cms-language-option,
.topbar .cms-language-switcher .cms-language-current,
nav.cms-language-switcher .cms-language-option,
nav.cms-language-switcher .cms-language-current {
    display: inline-flex !important;
    min-height: 26px !important;
    height: 26px !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
    padding: 2px 10px !important;
    border: 0 !important;
    border-radius: 7px !important;
    background: transparent !important;
    color: #ffffff !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    line-height: 1 !important;
    white-space: nowrap !important;
    text-decoration: none !important;
    box-sizing: border-box !important;
    transition: background-color 180ms ease, color 180ms ease !important;
}

.managed-school-topbar .cms-language-switcher .cms-language-option.active,
.managed-school-topbar .cms-language-switcher .cms-language-option[aria-current="page"],
.managed-school-topbar .cms-language-switcher .cms-language-current,
.topbar .cms-language-switcher .cms-language-option.active,
.topbar .cms-language-switcher .cms-language-option[aria-current="page"],
.topbar .cms-language-switcher .cms-language-current,
nav.cms-language-switcher .cms-language-option.active,
nav.cms-language-switcher .cms-language-option[aria-current="page"],
nav.cms-language-switcher .cms-language-current {
    background: #ffffff !important;
    color: #071b43 !important;
    font-weight: 800 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15) !important;
}

.managed-school-topbar .cms-language-switcher .cms-language-option:not(.active):hover,
.topbar .cms-language-switcher .cms-language-option:not(.active):hover,
nav.cms-language-switcher .cms-language-option:not(.active):hover {
    background: rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
}

.managed-school-topbar .cms-language-switcher .cms-language-option:focus-visible,
.topbar .cms-language-switcher .cms-language-option:focus-visible,
nav.cms-language-switcher .cms-language-option:focus-visible {
    outline: 2px solid #ffcb45 !important;
    outline-offset: 2px !important;
}

/* =========================================================
   Top Bar Responsive Layout (Single line on all screen sizes)
   ========================================================= */

.managed-school-topbar .managed-school-topbar-inner,
.topbar .container {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: nowrap !important;
    gap: 8px !important;
}

@media (max-width: 768px) {
    .managed-school-topbar .managed-school-topbar-inner,
    .topbar .container {
        padding: 5px 12px !important;
        min-height: 36px !important;
        gap: 6px !important;
    }

    .managed-school-topbar .topbar-right .motto,
    .topbar .topbar-right .motto,
    .managed-school-topbar .motto,
    .topbar .motto {
        display: none !important;
    }

    .managed-school-topbar .topbar-left,
    .topbar .topbar-left,
    .managed-school-topbar-title {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        line-height: 1.2 !important;
        text-align: left !important;
    }

    .managed-school-topbar .topbar-right,
    .topbar .topbar-right,
    .managed-school-topbar-languages {
        flex: 0 0 auto !important;
        margin-left: auto !important;
        display: inline-flex !important;
        align-items: center !important;
    }

    .managed-school-topbar .cms-language-switcher,
    .topbar .cms-language-switcher,
    nav.cms-language-switcher {
        display: inline-flex !important;
        flex-wrap: nowrap !important;
        padding: 2px !important;
        gap: 2px !important;
        border-radius: 8px !important;
    }

    .managed-school-topbar .cms-language-option,
    .managed-school-topbar .cms-language-current,
    .topbar .cms-language-option,
    .topbar .cms-language-current,
    nav.cms-language-switcher .cms-language-option,
    nav.cms-language-switcher .cms-language-current {
        min-height: 24px !important;
        height: 24px !important;
        padding: 2px 7px !important;
        font-size: 11px !important;
        border-radius: 6px !important;
    }
}

/* =========================================================
   School of Signals — Modern Navigation (Desktop: min-width 1121px)
   ========================================================= */

@media (min-width: 1121px) {
    /* Hide mobile controls on desktop */
    .managed-school-header .cms-mobile-menu-toggle,
    .managed-school-header .mobile-btn,
    .managed-school-header #mobileMenu,
    .managed-school-header .mobile-menu,
    header.header .mobile-btn,
    header.header #mobileMenu {
        display: none !important;
    }

    .managed-school-header .managed-school-navbar-inner,
    header.header .nav-wrap {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: nowrap !important;
        gap: 14px !important;
    }

    .managed-school-header .managed-school-brand,
    header.header .brand {
        flex: 0 0 auto !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }

    .managed-school-header .managed-school-brand-title,
    header.header .brand-title {
        font-size: clamp(16px, 1.2vw, 19px) !important;
        font-weight: 800 !important;
        white-space: nowrap !important;
        color: var(--navy-900, #071b43) !important;
    }

    .managed-school-header .managed-school-desktop-nav,
    .managed-school-header nav.aw-navigation,
    header.header .desktop-nav {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 1 1 auto !important;
        min-width: 0 !important;
        gap: clamp(4px, 0.75vw, 10px) !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Top-level desktop nav items only (Direct children) */
    .managed-school-header .managed-school-desktop-nav > a,
    .managed-school-header .managed-school-desktop-nav > .drop > .drop-toggle,
    .managed-school-header nav.aw-navigation > a,
    .managed-school-header nav.aw-navigation > .drop > .drop-toggle,
    .managed-school-header nav.aw-navigation > .aw-nav-parent > a,
    header.header .desktop-nav > a,
    header.header .desktop-nav > .drop > .drop-toggle {
        white-space: nowrap !important;
        font-size: clamp(13px, 0.9vw, 14.5px) !important;
        font-weight: 700 !important;
        padding: 8px clamp(6px, 0.65vw, 11px) !important;
        color: var(--navy-900, #071b43) !important;
        text-decoration: none !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        transition: all 0.2s ease !important;
    }

    .managed-school-header .managed-school-desktop-nav > a:hover,
    .managed-school-header .managed-school-desktop-nav > .drop:hover > .drop-toggle,
    .managed-school-header nav.aw-navigation > a:hover,
    .managed-school-header nav.aw-navigation > .aw-nav-parent:hover > a,
    header.header .desktop-nav > a:hover,
    header.header .desktop-nav > .drop:hover > .drop-toggle {
        color: #047857 !important;
        background: rgba(4, 120, 87, 0.08) !important;
    }

    /* Dropdowns inside Desktop Nav */
    .managed-school-header .managed-school-desktop-nav .drop,
    .managed-school-header nav.aw-navigation .drop,
    .managed-school-header nav.aw-navigation .aw-nav-parent,
    header.header .desktop-nav .drop {
        position: relative !important;
    }

    .managed-school-header .managed-school-desktop-nav .dropdown,
    .managed-school-header nav.aw-navigation .aw-nav-submenu,
    .managed-school-header nav.aw-navigation .dropdown,
    header.header .desktop-nav .dropdown {
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        min-width: 260px !important;
        width: max-content !important;
        max-width: 330px !important;
        display: none !important;
        flex-direction: column !important;
        align-items: stretch !important;
        padding: 8px !important;
        margin: 6px 0 0 !important;
        list-style: none !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.12) !important;
        z-index: 1200 !important;
    }

    .managed-school-header .managed-school-desktop-nav .drop:hover > .dropdown,
    .managed-school-header .managed-school-desktop-nav .drop:focus-within > .dropdown,
    .managed-school-header nav.aw-navigation .drop:hover > .dropdown,
    .managed-school-header nav.aw-navigation .aw-nav-parent:hover > .aw-nav-submenu,
    header.header .desktop-nav .drop:hover > .dropdown {
        display: flex !important;
        flex-direction: column !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .managed-school-header .managed-school-desktop-nav .dropdown a,
    .managed-school-header nav.aw-navigation .aw-nav-submenu a,
    .managed-school-header nav.aw-navigation .dropdown a,
    header.header .desktop-nav .dropdown a {
        display: flex !important;
        width: 100% !important;
        box-sizing: border-box !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 10px 14px !important;
        min-height: 40px !important;
        color: #1e293b !important;
        font-size: 13.5px !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        text-decoration: none !important;
        white-space: normal !important;
        text-align: left !important;
        transition: all 0.18s ease !important;
    }

    .managed-school-header .managed-school-desktop-nav .dropdown a:hover,
    .managed-school-header nav.aw-navigation .aw-nav-submenu a:hover,
    .managed-school-header nav.aw-navigation .dropdown a:hover,
    header.header .desktop-nav .dropdown a:hover {
        color: #047857 !important;
        background: #f0fdf4 !important;
        padding-left: 17px !important;
    }

    /* Header Actions */
    .managed-school-header .managed-school-header-actions,
    header.header .nav-actions {
        flex: 0 0 auto !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }

    .managed-school-header .managed-school-conduct-link,
    header.header .conduct-link {
        font-size: 13.5px !important;
        font-weight: 700 !important;
        color: #02669a !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        padding: 6px 10px !important;
        border-radius: 8px !important;
        transition: background 0.2s ease !important;
    }

    .managed-school-header .managed-school-conduct-link:hover,
    header.header .conduct-link:hover {
        background: #e0f2fe !important;
    }

    /* Search Button */
    .managed-school-header .search-toggle-btn,
    .managed-school-header .managed-school-search-btn,
    header.header .search-toggle-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 38px !important;
        height: 38px !important;
        min-width: 38px !important;
        border-radius: 10px !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        color: #1e293b !important;
        font-size: 15px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        text-decoration: none !important;
    }

    .managed-school-header .search-toggle-btn:hover,
    .managed-school-header .managed-school-search-btn:hover,
    header.header .search-toggle-btn:hover {
        background: #f1f5f9 !important;
        border-color: #94a3b8 !important;
        color: #02669a !important;
    }

    /* LMS Portal Button */
    .managed-school-header .managed-school-portal,
    header.header .portal-button,
    header.header .portal-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 16px !important;
        border-radius: 9999px !important;
        background: #009640 !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 13.5px !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        border: none !important;
        transition: background 0.2s ease !important;
    }

    .managed-school-header .managed-school-portal:hover,
    header.header .portal-button:hover,
    header.header .portal-btn:hover {
        background: #007a33 !important;
        color: #ffffff !important;
    }
}

/* =========================================================
   School of Signals — Modern Navigation (Mobile: max-width 1120px)
   ========================================================= */

@media (max-width: 1120px) {
    /* Hide desktop navigation */
    nav.aw-navigation,
    nav.aw-desktop-navigation,
    nav.desktop-nav,
    .desktop-nav,
    .managed-school-desktop-nav,
    .managed-school-header .managed-school-desktop-nav,
    .managed-school-header nav.aw-navigation,
    .managed-school-header nav.aw-desktop-navigation,
    .aw-header nav.aw-desktop-navigation,
    .aw-header nav.aw-navigation,
    header.header .desktop-nav,
    header.header nav.aw-navigation,
    header.header nav.aw-desktop-navigation {
        display: none !important;
    }

    .managed-school-header .managed-school-conduct-link,
    header.header .conduct-link,
    .managed-school-conduct-link {
        display: none !important;
    }

    .managed-school-header .managed-school-portal,
    header.header .portal-button,
    header.header .portal-btn,
    .portal-button {
        display: none !important;
    }

    /* Mobile Header Bar */
    .managed-school-header .managed-school-navbar-inner,
    header.header .nav-wrap {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 100% !important;
        padding: 8px 12px !important;
        box-sizing: border-box !important;
    }

    .managed-school-header .managed-school-brand,
    header.header .brand {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        text-decoration: none !important;
        min-width: 0 !important;
        flex: 1 1 auto !important;
        overflow: hidden !important;
    }

    .managed-school-header .managed-school-brand-copy,
    header.header .brand > span:last-child {
        min-width: 0 !important;
        flex: 1 1 auto !important;
        overflow: hidden !important;
    }

    .managed-school-header .managed-school-brand-title,
    header.header .brand-title {
        font-size: clamp(14px, 3.8vw, 17px) !important;
        font-weight: 800 !important;
        color: var(--navy-900, #071b43) !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: block !important;
    }

    .managed-school-header .managed-school-brand-subtitle,
    header.header .brand-subtitle {
        font-size: 11px !important;
        color: #64748b !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: block !important;
    }

    .managed-school-header .managed-school-header-actions,
    header.header .nav-actions {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        flex: 0 0 auto !important;
    }

    /* Mobile Search Toggle */
    .managed-school-header .search-toggle-btn,
    .managed-school-header .managed-school-search-btn,
    header.header .search-toggle-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
        border-radius: 10px !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        color: #1e293b !important;
        font-size: 15px !important;
        text-decoration: none !important;
    }

    /* Mobile Hamburger Button */
    .managed-school-header .cms-mobile-menu-toggle,
    .managed-school-header .mobile-btn,
    header.header .mobile-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        color: var(--navy-900, #071b43) !important;
        font-size: 18px !important;
        cursor: pointer !important;
    }

    .managed-school-header .cms-mobile-menu-toggle-label {
        display: none !important;
    }

    /* Mobile Menu Drawer */
    .managed-school-header #mobileMenu,
    .managed-school-header .mobile-menu,
    header.header #mobileMenu,
    header.header .mobile-menu {
        display: none !important;
    }

    .managed-school-header #mobileMenu.open,
    .managed-school-header .mobile-menu.open,
    header.header #mobileMenu.open,
    header.header .mobile-menu.open {
        display: block !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100vh !important;
        background: #ffffff !important;
        z-index: 99999 !important;
        overflow-y: auto !important;
        padding: 20px 18px 40px !important;
        box-sizing: border-box !important;
    }

    .managed-school-header .sos-mobile-navigation,
    header.header .sos-mobile-navigation,
    header.header .mobile-menu .container {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        padding: 0 !important;
    }

    /* Mobile Links */
    .managed-school-header .sos-mobile-link,
    header.header .mobile-menu a {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        min-height: 48px !important;
        padding: 12px 6px !important;
        color: #071b43 !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        text-decoration: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        box-sizing: border-box !important;
    }

    /* Mobile Accordion Dropdown */
    .managed-school-header .sos-mobile-dropdown,
    header.header .sos-mobile-dropdown {
        display: block !important;
        width: 100% !important;
    }

    .managed-school-header .sos-mobile-dropdown summary,
    header.header .sos-mobile-dropdown summary {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        min-height: 48px !important;
        padding: 12px 6px !important;
        color: #071b43 !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        cursor: pointer !important;
        list-style: none !important;
    }

    .managed-school-header .sos-mobile-dropdown summary::-webkit-details-marker,
    header.header .sos-mobile-dropdown summary::-webkit-details-marker {
        display: none !important;
    }

    .managed-school-header .sos-mobile-submenu,
    header.header .sos-mobile-submenu {
        padding-left: 18px !important;
        background: #f8fafc !important;
    }

    .managed-school-header .sos-mobile-submenu a,
    header.header .sos-mobile-submenu a {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        min-height: 44px !important;
        padding: 10px 12px !important;
        font-size: 14px !important;
        color: #334155 !important;
        font-weight: 600 !important;
        text-decoration: none !important;
        border-bottom: 1px solid #edf2f7 !important;
    }

    /* Mobile Search Bar inside Drawer */
    .managed-school-header .mobile-search-bar,
    header.header .mobile-search-bar {
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
        margin: 10px 0 14px !important;
        padding: 3px 6px 3px 12px !important;
        box-sizing: border-box !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 12px !important;
        background: #f8fafc !important;
    }

    .managed-school-header .mobile-search-bar input,
    header.header .mobile-search-bar input {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        border: none !important;
        background: transparent !important;
        padding: 8px 6px !important;
        font-size: 14px !important;
        color: #1e293b !important;
        outline: none !important;
    }

    .managed-school-header .mobile-search-bar button,
    header.header .mobile-search-bar button {
        border: none !important;
        background: #02669a !important;
        color: #ffffff !important;
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        font-size: 13px !important;
    }

    /* Mobile Language Switcher inside Drawer */
    .managed-school-header .managed-school-mobile-languages,
    header.header .mobile-language {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        margin: 10px 0 !important;
        padding: 4px 0 !important;
    }

    .managed-school-header .managed-school-mobile-languages a,
    header.header .mobile-language a {
        flex: 1 1 auto !important;
        min-height: 40px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        background: #f8fafc !important;
        color: #1e293b !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        text-decoration: none !important;
    }

    .managed-school-header .managed-school-mobile-languages a.active,
    header.header .mobile-language a.active {
        background: #02669a !important;
        color: #ffffff !important;
        border-color: #02669a !important;
    }
}

/* =========================================================
   Footer Mobile & Tablet Responsiveness
   ========================================================= */

@media (max-width: 900px) {
    .managed-school-footer .managed-school-footer-grid,
    .footer .footer-grid,
    .aw-footer .aw-footer-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 28px !important;
    }

    .managed-school-footer-shell,
    .aw-footer-shell {
        width: calc(100% - 32px) !important;
    }
}

@media (max-width: 768px) {
    .managed-school-footer .managed-school-footer-grid,
    .footer .footer-grid,
    .aw-footer .aw-footer-grid {
        grid-template-columns: 1fr !important;
        gap: 24px !important;
    }

    .managed-school-footer-shell,
    .aw-footer-shell {
        width: calc(100% - 24px) !important;
        padding-top: 32px !important;
    }

    .managed-school-footer-main {
        padding: 32px 0 !important;
    }

    .managed-school-footer-about > p {
        max-width: 100% !important;
        font-size: 13.5px !important;
        line-height: 1.7 !important;
        margin-top: 14px !important;
    }

    .managed-school-footer h2,
    .footer h2,
    .aw-footer h2 {
        font-size: 15px !important;
        margin-bottom: 14px !important;
    }

    .managed-school-footer-bottom-row,
    .footer .footer-bottom .container,
    .aw-footer-bottom {
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        gap: 6px !important;
        padding: 16px 0 !important;
    }

    .managed-school-footer-bottom-row p,
    .footer .footer-bottom p,
    .aw-footer-bottom p {
        text-align: center !important;
        font-size: 12px !important;
    }
}
CSS;

        $sanitizer = app(ThemeLayoutSanitizer::class);
        $cleanCss = $sanitizer->sanitizeCss($navCss);

        $locales = [
            'en' => [
                'enum' => ThemeLayoutLocale::English,
                'html' => $enHtml,
                'label' => 'English',
            ],
            'si' => [
                'enum' => ThemeLayoutLocale::Sinhala,
                'html' => $siHtml,
                'label' => 'Sinhala',
            ],
        ];

        foreach ($locales as $code => $data) {
            $layout = ThemeLayout::query()
                ->where('theme_slug', 'school-of-signals')
                ->where('region', ThemeLayoutRegion::Header->value)
                ->where('locale', $data['enum']->value)
                ->first();

            if (! $layout instanceof ThemeLayout) {
                $layout = new ThemeLayout();
                $layout->uuid = (string) Str::uuid();
                $layout->theme_slug = 'school-of-signals';
                $layout->region = ThemeLayoutRegion::Header;
                $layout->locale = $data['enum'];
                $layout->revision_number = 0;
            }

            $cleanHtml = $sanitizer->sanitizeHtml($data['html']);

            $nextRev = ((int) $layout->revision_number) + 1;

            $layout->forceFill([
                'status' => ThemeLayoutStatus::Published->value,
                'published_at' => now(),
                'published_html' => $cleanHtml,
                'draft_html' => $cleanHtml,
                'published_css' => $cleanCss,
                'draft_css' => $cleanCss,
                'revision_number' => $nextRev,
            ])->save();

            ThemeLayoutRevision::query()->create([
                'theme_layout_id' => $layout->id,
                'revision_number' => $nextRev,
                'content_html' => $cleanHtml,
                'content_css' => $cleanCss,
                'status' => ThemeLayoutStatus::Published->value,
            ]);

            $this->info("✓ Successfully published and secured {$data['label']} Header (#{$layout->id}) with full navigation links, search & modern responsive CSS.");
        }

        // Also update Footer layouts with responsive CSS
        $footerCssResponsive = <<<'CSS'

/* =========================================================
   Footer Mobile & Tablet Responsiveness
   ========================================================= */

@media (max-width: 900px) {
    .managed-school-footer .managed-school-footer-grid,
    .footer .footer-grid,
    .aw-footer .aw-footer-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 28px !important;
    }

    .managed-school-footer-shell,
    .aw-footer-shell {
        width: calc(100% - 32px) !important;
    }
}

@media (max-width: 768px) {
    .managed-school-footer .managed-school-footer-grid,
    .footer .footer-grid,
    .aw-footer .aw-footer-grid {
        grid-template-columns: 1fr !important;
        gap: 24px !important;
    }

    .managed-school-footer-shell,
    .aw-footer-shell {
        width: calc(100% - 24px) !important;
        padding-top: 32px !important;
    }

    .managed-school-footer-main {
        padding: 32px 0 !important;
    }

    .managed-school-footer-about > p {
        max-width: 100% !important;
        font-size: 13.5px !important;
        line-height: 1.7 !important;
        margin-top: 14px !important;
    }

    .managed-school-footer h2,
    .footer h2,
    .aw-footer h2 {
        font-size: 15px !important;
        margin-bottom: 14px !important;
    }

    .managed-school-footer-bottom-row,
    .footer .footer-bottom .container,
    .aw-footer-bottom {
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        gap: 6px !important;
        padding: 16px 0 !important;
    }

    .managed-school-footer-bottom-row p,
    .footer .footer-bottom p,
    .aw-footer-bottom p {
        text-align: center !important;
        font-size: 12px !important;
    }
}
CSS;

        $footerLayouts = ThemeLayout::query()
            ->where('theme_slug', 'school-of-signals')
            ->where('region', ThemeLayoutRegion::Footer->value)
            ->get();

        foreach ($footerLayouts as $fLayout) {
            $existingCss = $fLayout->published_css ?? '';
            if (! str_contains($existingCss, 'Footer Mobile & Tablet Responsiveness')) {
                $updatedCss = $existingCss . "\n\n" . $footerCssResponsive;
            } else {
                $updatedCss = preg_replace(
                    '/\/\* =========================================================\s*Footer Mobile & Tablet Responsiveness[\s\S]*$/',
                    trim($footerCssResponsive),
                    $existingCss
                );
            }
            $cleanFCss = $sanitizer->sanitizeCss($updatedCss);
            $nextRev = ((int) $fLayout->revision_number) + 1;
            $fLayout->forceFill([
                'published_css' => $cleanFCss,
                'draft_css' => $cleanFCss,
                'status' => ThemeLayoutStatus::Published->value,
                'revision_number' => $nextRev,
            ])->save();

            ThemeLayoutRevision::query()->create([
                'theme_layout_id' => $fLayout->id,
                'revision_number' => $nextRev,
                'content_html' => $fLayout->published_html ?? '',
                'content_css' => $cleanFCss,
                'status' => ThemeLayoutStatus::Published->value,
            ]);

            $localeLabel = $fLayout->locale instanceof ThemeLayoutLocale ? $fLayout->locale->name : (string) $fLayout->locale;
            $this->info("✓ Successfully updated {$localeLabel} Footer (#{$fLayout->id}) with responsive mobile styles.");
        }

        $this->info('Done! All header and footer layouts are published and fully responsive.');
        return self::SUCCESS;
    }
}

