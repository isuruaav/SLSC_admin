# Browser visual check — 10 November 2026

Inspected 49 Chrome captures: 32 public views and 17 administrator views.
Desktop viewport: **1440 × 1000**. Mobile viewport: **390 × 844**.
Screenshots use installed Chrome 156 in an isolated headless profile.

Public checks cover the homepage, menu, footer, news listing/article, galleries,
documents, publications, courses, organizations, leadership/history, contact,
registration, search and login. Sinhala contact, registration and course views
were included. Administrator screens use isolated in-memory test data and sample
publication covers.

## Fixed and checked again

| Finding | Change | Evidence |
| --- | --- | --- |
| Contact form appeared unstyled; its spam-check field was visible. | Added self-contained responsive styles, accessible field spacing/focus states and a native hidden attribute for the spam-check field. | [Before](contact-before-mobile.png) · [After, mobile](contact-mobile.png) · [After, desktop](contact-desktop.png) · [Sinhala](contact-si-mobile.png) |
| Desktop admin sidebar appeared on mobile and pushed the page below the fold. | Removed the unscoped mobile CSS override; the desktop sidebar now stays hidden and the mobile drawer opens separately. | [Before](admin-sidebar-before-mobile.png) · [Dashboard after](admin-dashboard-mobile.png) · [Open mobile navigation](admin-navigation-mobile.png) · [Publication cards](admin-publications-mobile.png) |
| Recovery notice crowded the Hero dialog close control. | Placed recovery feedback inside the scrollable form body; aligned the Program schedule and preview with the form padding. | [Before](hero-modal-before-mobile.png) · [Hero after](admin-hero-form-mobile.png) · [Program after](admin-program-form-mobile.png) |

## Results

- No horizontal page overflow or broken images in the 49 captures.
- Public pages produced no uncaught JavaScript exceptions during these checks.
- Public mobile navigation, registration dialog and empty-form validation worked.
- Administrator mobile navigation and Hero/Program dialogs initialized and rendered.
- Contact spam-check fields stayed hidden in English and Sinhala.
- 25 PHP render/regression checks passed; 43 administration JavaScript tests passed.
- Production asset build and Git whitespace checks passed.

Additional screenshots: [registration dialog](registration-modal-mobile.png),
[validation feedback](registration-validation-mobile.png),
[publication form](admin-publication-form-mobile.png),
[document directory](admin-documents-mobile.png),
[invitation form](admin-invitation-form-mobile.png),
[contact inbox](admin-contact-inbox-mobile.png),
[backup settings](admin-backups-mobile.png).

## Content follow-up

The homepage links to **`/pages/aboutus`**, which returns **404**. No active page
with that slug was found. Create the intended About page or update the homepage
link to the appropriate existing content. [Captured 404](page-mobile.png).

Some historical portraits use small source images and look soft when enlarged.
Sharper originals can be replaced through the administration image upload fields.

## Scope and limits

These are Chrome viewport checks, rather than physical-phone or cross-browser
testing. Public content was checked on the running local website. Admin layouts
were rendered with temporary test accounts/content; production administrator
credentials were not used and save/publish/send actions were not exercised in the
browser. Contact-inbox fixtures generated cross-origin background-update errors
because their temporary renderer has a separate origin; these are recorded in
[the raw capture results](results.json), rather than treated as application errors.

No live content was published and no contact or registration messages were sent
by these browser checks.

## Document upload modal follow-up

The document editor now opens in a modal for both Add and Edit. Six additional
captures checked the closed state, desktop creation, 390px/320px mobile creation,
validation errors and editing with file history. The initial page kept the modal
closed, opening focused the English title, and mobile views kept the footer
actions visible while the form body scrolled. No horizontal overflow was found.
The related 34 PHP regression tests, PHPStan, Pint and production build passed.

[Desktop modal](document-modal-add-1440.png) ·
[Mobile modal](document-modal-add-390.png) ·
[Small mobile modal](document-modal-add-320.png) ·
[Validation](document-modal-errors-390.png) ·
[Edit](document-modal-edit-390.png) ·
[Capture metrics](document-modal-results.json).
