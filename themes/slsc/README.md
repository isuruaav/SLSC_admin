# Sri Lanka Signal Corps portal

Open **http://localhost/slsc/** with WAMP running. PHP renders the complete page; the interface uses local JavaScript, CSS, SVG icons, fonts and photographs. No production build is required.

## Edit content

- `includes/content.php`: the 15 program titles, categories, official destination URLs and four news stories. Dates use `YYYY-MM-DD`.
- `index.php`: hero slides, Colonel Commandant information, publication and contact sections.
- `assets/js/i18n.js`: English and Sinhala translations. Keep the same keys in both dictionaries.
- `assets/css/base.css`: the shared design. `responsive.css` contains mobile, print and reduced motion styles; `fonts.css` declares local fonts. `style.css` imports them.
- `assets/js/script.js`: search, filters, navigation, slider and accessibility behavior.

Program cards retain their compact 120px desktop / 104px mobile minimum heights. Their sample photograph appears on hover or keyboard focus. Change `--program-bg` in `base.css` to replace it; individual cards can override the same custom property.

## Included interactions

- Site search with Ctrl/Cmd+K or `/`, category scopes, arrow navigation and Escape to close.
- Bilingual program and news searches, category filters, result counts and reset controls.
- Persistent language preference, with a working fallback when browser storage is blocked.
- Slider arrows, dots, pause/play, progress, touch swipe and keyboard controls. Autoplay pauses offscreen, in background tabs and while search is open; reduced motion starts it paused.
- Mobile navigation, active section tracking, scroll progress and back-to-top controls.
- Working navigation and content links when JavaScript is disabled.

## Validation

Verified with PHP 8.4 and Node.js 24. Node is needed only for the development tests.

```text
npm ci --ignore-scripts
npm test
npm run check
php -n -l index.php
php -n -l includes/content.php
```

The tests exercise the rendered PHP markup and interaction behavior using jsdom. Browser layout still requires a visual review; no connected browser was available in the development session.

## Assets and sources

The supplied crest and Colonel Commandant portrait are retained. News and hero photographs, program destinations and publication links come from the [official Signal Corps portal](https://alt.army.lk/signaller/). Original news image URLs are recorded in `includes/content.php`. The 75th anniversary card links to the official anniversary article because the former standalone anniversary site was unavailable.

Inter and Noto Sans Sinhala are stored locally under their SIL Open Font Licenses; the license files are in `assets/fonts/`.
