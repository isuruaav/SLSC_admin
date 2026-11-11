# Navigation editor

Open **Header & Footer**, choose a language, then select **Header / Top Bar / Navigation**.

- **Header layout & branding** edits the selected language's top-bar title and motto, brand name and subtitle, logo image URL, home link, secondary action, portal link, language buttons and search button. Leave the logo URL empty to use the logo in Site Settings. Header colours and the choice between a single main row and a logo above the menu are also available visually.
- **Apply reference layout** applies the school-style language bar and main header while retaining the current desktop/mobile menu links and CSS outside the base block. Undo restores the previous source. English and Sinhala have separate drafts and published versions; save and publish each language separately.
- Header controls and menu controls edit the same HTML used by **HTML / CSS**. Code changes to the recognised header fields reappear in Visual mode. Editing a menu does not replace existing base CSS, so a custom layout remains intact. The reference preset explicitly refreshes the base styles when selected.

- **Visual** reads the menu links in the current header. Add a link, choose a published page or site section, or enter a custom address. Select a parent or use **Add submenu** for dropdowns. Drag links or use the arrow buttons to change their order.
- **Link icon** opens a searchable picker of 30 bundled Font Awesome icons. The icon appears in both desktop and mobile navigation and in the HTML tab. Select **No icon** to remove it.
- **HTML / CSS** edits the same draft. Desktop links are the source for the corresponding mobile menu. Branding, header actions, custom CSS, and additional mobile action links are retained.
- **Navigation appearance** controls text, background, hover color, font size, and spacing. Its CSS uses `--aw-nav-*` variables that can also be edited in the CSS tab.
- **Undo / Redo** restores source and CSS during the current editing session.
- **Save Draft** stores changes without changing the public website. **Save & Publish** saves the current editor content and publishes the selected language and region.

New empty headers start with Home, News, Courses, Gallery, Publications, and Contact. Removing these links is permanent within the draft; they are not automatically reinserted. **Use default navbar** replaces the current header draft after confirmation and can be undone before leaving the editor.

The visual editor supports ordinary links, nested `ul`/`li` menus, and the theme's `.drop` / `.dropdown` structure inside a `nav` element. Custom mega menu layouts remain available in HTML / CSS and are not partially converted. A header using `[[primary_menu]]` or `[[responsive_primary_navigation]]` is initially unchanged; the first visual menu edit copies its current links into that language's header source.

Preview uses the same sanitization, placeholder rendering, theme stylesheets and language fonts as the public website. Desktop (1280px), tablet (820px), and mobile (390px) viewports scale to fit the editor. The mobile menu starts closed and remains hidden on desktop, including after changing viewports. The preview reserves room for positioned desktop dropdowns, including hidden menus, and grows with expanded mobile submenus without an arbitrary height limit. It remeasures after viewport, font, image, hover and focus changes to avoid clipped menus and nested scrollbars. Use the mobile toggle, expand-menu checkbox, or native dropdown disclosures to inspect navigation. Links do not navigate away from the preview.

The iframe permits the same origin so bundled fonts and images load normally. It does not permit scripts; the preview CSP also blocks scripts, forms and objects. The trusted parent editor handles preview controls and height measurement. PHP, Blade, scripts, and unsafe HTML/CSS in user source remain blocked. If no site logo is configured, preview uses the theme's default logo, as the public theme does.

The default navbar includes icons and a mobile menu button. Published mobile menus close with Escape, outside click, link selection, and desktop resizing; Escape returns focus to the menu button. Current local page links are highlighted for both relative and absolute URLs.

## Validation

The dependency-free preview regression checks cover menu overflow, responsive resizing, interactions and cleanup:

```powershell
node --test tests/Frontend/theme-layout-preview.test.mjs
```

Run the PHP feature tests with:

```powershell
php artisan test tests/Feature/Admin/NavigationEditorTest.php tests/Feature/Admin/ThemeLayoutAdminUiTest.php tests/Feature/Admin/ThemeLayoutServiceTest.php tests/Feature/ThemeLayoutRendererLocaleTest.php
```

The standalone frontend checks use Node's test runner and linkedom 0.18.12's standalone `worker.js`, saved as an `.mjs` file outside the project. Point `NAVIGATION_DOM_MODULE` to that file:

```powershell
$env:NAVIGATION_DOM_MODULE = 'C:\path\to\linkedom-worker.mjs'
node --test tests/Frontend/navigation-editor.test.mjs
```

These checks exercise the editor's DOM transformations and state transitions. They do not replace a browser check of Alpine bindings, drag gestures, and responsive appearance.
