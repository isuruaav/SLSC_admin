# Public page design

The SLSC public theme uses `themes/slsc/assets/css/inner-pages.css` for its shared
navy, green and light surface design. The rules are scoped to `body.slsc-inner-page`
so the home page retains its existing layout. Publish this stylesheet to the matching
`public/themes/slsc/assets/css/` directory when deploying. No Vite build is required
for changes to this stylesheet.

Use `theme-slsc::partials.page-heading` when adding a theme page. Pass `heading`,
optional `headingIntro`, and optional `headingActionUrl` / `headingActionLabel`.
The parent layout provides the breadcrumb trail and the single main landmark;
child views should use sections, articles or divs for their content.

The shared styles cover appointments, history (cards and timeline), organization,
CMS articles, local Special Program pages, galleries, magazines, news listings,
documents, courses, search, contact and registration. Existing CMS title visibility,
scoped custom CSS, content, language links and filtering behavior are preserved.
Embedded flag, contact directory and association magazine headings are aligned
with the shared page design.

Special Program links to external websites and PDFs retain their destinations.
Their appearance is controlled by the destination website, outside this theme.

`tests/Feature/SlscPageDesignTest.php` checks the English and Sinhala page shells,
heading and landmark structure, breadcrumbs, CMS styles and home page isolation.
