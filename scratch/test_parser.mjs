import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const html = `
<header class="aw-header">
    <div class="aw-header-inner">
        <a class="aw-brand" href="[[site_url:/]]">
            [[site_logo]]
            <span>[[site_name]]</span>
        </a>
        <nav class="aw-navigation aw-desktop-navigation">
            <a href="[[site_url:/]]"><i class="aw-nav-icon fa-solid fa-house" aria-hidden="true"></i><span class="aw-nav-label">Home</span></a>
            <div class="drop aw-nav-parent">
                <a href="#" class="drop-toggle"><i class="aw-nav-icon fa-solid fa-user-tie" aria-hidden="true"></i><span class="aw-nav-label">Appointments</span><i class="aw-nav-indicator fa-solid fa-chevron-down" aria-hidden="true"></i></a>
                <div class="dropdown aw-nav-submenu">
                    <a href="[[site_url:/colonel-commandant]]"><i class="aw-nav-icon fa-solid fa-user-tie" aria-hidden="true"></i><span class="aw-nav-label">Colonel Commandant</span></a>
                    <a href="[[site_url:/chief-signal-officer]]"><i class="aw-nav-icon fa-solid fa-user-tie" aria-hidden="true"></i><span class="aw-nav-label">Chief Signal Officer</span></a>
                </div>
            </div>
            <a href="[[site_url:/organization]]"><i class="aw-nav-icon fa-solid fa-sitemap" aria-hidden="true"></i><span class="aw-nav-label">Organization</span></a>
        </nav>
        <div class="aw-header-actions">[[mobile_menu_toggle]]</div>
    </div>
    <div id="mobileMenu" class="mobile-menu aw-mobile-menu">
        <div class="aw-mobile-navigation">
            <a class="sos-mobile-link" href="[[site_url:/]]"><span class="aw-nav-label"><i class="aw-nav-icon fa-solid fa-house" aria-hidden="true"></i>Home</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <details class="sos-mobile-dropdown">
                <summary><span class="aw-nav-label"><i class="aw-nav-icon fa-solid fa-user-tie" aria-hidden="true"></i>Appointments</span><i class="aw-nav-indicator fa-solid fa-chevron-down" aria-hidden="true"></i></summary>
                <div class="sos-mobile-submenu aw-mobile-submenu">
                    <a class="sos-mobile-link" href="[[site_url:/colonel-commandant]]"><span class="aw-nav-label"><i class="aw-nav-icon fa-solid fa-user-tie" aria-hidden="true"></i>Colonel Commandant</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <a class="sos-mobile-link" href="[[site_url:/chief-signal-officer]]"><span class="aw-nav-label"><i class="aw-nav-icon fa-solid fa-user-tie" aria-hidden="true"></i>Chief Signal Officer</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </details>
            <a class="sos-mobile-link" href="[[site_url:/organization]]"><span class="aw-nav-label"><i class="aw-nav-icon fa-solid fa-sitemap" aria-hidden="true"></i>Organization</span><i class="aw-nav-indicator fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    </div>
</header>
`;

console.log("HTML length:", html.length);

