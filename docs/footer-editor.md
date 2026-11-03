# Footer visual editor

Open **Header & Footer**, choose the language, then select **Footer**. Each language has its own draft and published footer.

- Select a column card or click a column in the live preview. Drag the cards or use their arrow buttons to reorder columns. The copyright bar stays below the columns.
- Choose a block to edit its text or link. Link settings offer site sections, published pages in the selected language, custom addresses and Font Awesome icons. Existing branding, icons and surrounding HTML are retained.
- Add headings, text, links, site logo, site name, tagline, contact details or social links. CMS blocks use the current Site Settings; the settings link opens in a new tab. Blocks and columns can be removed, and sibling blocks can be moved up or down.
- **Footer appearance** controls background, text, heading and accent colors, font size, spacing, top padding and desktop column count. Click **Apply appearance** to restyle the footer. Tablet and mobile layouts adapt automatically. Existing CSS stays in place until you apply appearance settings or replace the draft with the default footer.
- **HTML / CSS** edits the same source. **Undo / Redo** restores source and CSS during this editing session. **Use default footer** replaces the draft after confirmation and can be undone.
- **Save Draft** stores changes without publishing. **Save & Publish** saves the current editor content and publishes the selected language's footer. Switching regions or languages discards unsaved changes after confirmation. Unpublished languages fall back to the published English footer.

The supplied School of Signals footer, the default footer and ordinary section-based footers are supported. Other custom layouts can be edited in HTML / CSS. Opening an existing footer preserves its source and CSS exactly; missing or intentionally empty blocks are not reinserted. Empty drafts receive a starter in the editor without writing to the database.

Preview shares the public layout sanitizer and placeholder renderer. Its iframe permits same-origin assets but no scripts, forms or navigation. Desktop, tablet and mobile widths are 1280px, 820px and 390px, scaled to fit the editor.

## Validation

```powershell
php artisan test tests/Feature/Admin/FooterEditorTest.php tests/Feature/Admin/NavigationEditorTest.php tests/Feature/Admin/ThemeLayoutAdminUiTest.php tests/Feature/Admin/ThemeLayoutServiceTest.php tests/Feature/ThemeLayoutRendererLocaleTest.php
$env:NAVIGATION_DOM_MODULE = 'C:\path\to\linkedom-worker.mjs'
node --test tests/Frontend/footer-editor.test.mjs tests/Frontend/navigation-editor.test.mjs
```

The DOM tests use the same external standalone linkedom worker described in [navigation-editor.md](navigation-editor.md). To exercise the actual rendered Blade view with the bundled Alpine runtime:

```powershell
$env:FOOTER_EDITOR_WRITE_FIXTURE = '1'
php artisan test tests/Feature/Admin/FooterEditorTest.php
node scripts/verify-footer-bindings.mjs
```
