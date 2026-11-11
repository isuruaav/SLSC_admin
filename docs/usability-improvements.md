# Usability improvements

Implementation checklist for the administration and public website:

- [x] Registration submission and confirmation reference
- [x] Documents: drafts, explicit publishing, trash recovery and version history
- [x] Courses: dedicated permissions, bilingual qualifications and public filters
- [x] Organization import comparison, snapshots and restore
- [x] Leadership visual profile editing and historical year filters
- [x] Dashboard review and translation notices
- [x] News and Pages: publication checklist and revision comparison
- [x] Media: batch upload, retry, duplicate warning and reuse
- [x] Galleries: drag ordering and batch captions
- [x] Publications: language filter and issue metadata
- [x] Hero slides and programs: visibility scheduling and mobile preview
- [x] Contact messages: assignment, internal notes, reopen and contact links
- [x] Users: invitations, last login and MFA status
- [x] Header and Footer: revision restore and internal link checks
- [x] Settings: backup status, downloads and schedule controls
- [x] Redirects: search, pagination, destination checks and cycle validation
- [x] Audit: filtered CSV export and readable field changes
- [x] Search and sitemap: all public content sections
- [x] Shared form protection and responsive admin tables
- [x] Regression tests, static analysis and production asset build

Recovery copies in the browser are text only and stay in the current tab. Saving a
form persists its content to the application. Backup schedules require the Laravel
scheduler to run on the server. Invitation links are generated for the administrator
to share; creating an invitation does not send mail automatically.

Documents and publications start as drafts. Use **Save & Publish** when a file is
ready for visitors. Trash recovery returns content to Draft. Document file history
also offers private administrator previews and a way to select an earlier version.
**Add Document** and **Edit** open the document upload form in a modal. Validation
errors keep it open; a successful save closes it. Cancel clears the selected files
and form input before another document is opened.

**Copy Path** in Media, batch uploads, Documents and published Publications shows
an application-relative reference, such as `/media-files/photo.jpg`,
`/document-files/en/guide/view` or `/publications/{uuid}/pdf`. Private and draft
files do not gain a public path. The Image and Document buttons in the page editor
accept these paths. Visual editing stores local references without the domain or
deployment folder; previews and public content add the current installation's
base folder automatically. Keep `APP_URL` set to the installation's actual base
address, including its subfolder when applicable. External image/video/document
links retain their full URLs. Existing full URLs remain accepted. Video uploads
remain governed by the existing media configuration (external links by default).
The file-path follow-up passed 149 PHP tests and 155 JavaScript tests, including
visual-editor serialization and root/subfolder installations. Scoped PHPStan,
Pint, Blade compilation and the production Vite build also passed.

The page editor opens the **Media Library** tab first. Selecting a library image
inserts its existing path without another upload. When the same image contents
are uploaded again through the editor, the existing accessible public image is
reused; its library title and alt text are preserved. Private files are excluded
from the picker and public-image reuse.

In **Special Programs**, use **Create Page** to open the page editor in a new tab.
Publish the page, return to the program form, click **Refresh pages**, then select
the page. The form fills the English or Sinhala page path automatically. Manual
paths such as `pages/about` and `/pages/about` are also accepted. Existing relative
links and new links use the current installation's base folder when opened from
the homepage or administration list. Draft, future and deleted pages cannot be
selected, and a manually entered unpublished page path gets a validation message.
This follow-up passed 78 PHP tests, 156 JavaScript tests, scoped PHPStan and Pint,
Blade compilation and the production build.

The **Training Manager** role manages courses without granting access to site
settings. Existing custom roles retain their permissions; course access can be
granted separately in role management.

Organization imports include a current/incoming comparison and recovery snapshots.
Published units must be unpublished before an import can replace their content.
Header/footer recovery changes the draft while preserving the live layout.

The public registration form is also available at `/register-interest` and
`/si/register-interest`. A successful submission becomes an enquiry in Contact
Messages with its confirmation reference. Creating an invitation or registration
does not send email automatically.

**Settings → Backups & System Status** shows archives, the latest backup result
and the scheduler heartbeat. Schedules default to **Manual only**. For automatic
backups, configure the server to run `php artisan schedule:run` from the project
directory every minute. Weekly schedules run on Monday in the selected time zone.
Downloading archives requires the site-settings permission.

Internal link checks inspect application routes and published content. External
websites are identified for manual checking; the checker does not fetch them.
Visual previews help with mobile cropping, but they do not replace checking the
finished pages in a browser.

Verification completed on 10 November 2026:

- Full PHP regression run exercised 1,089 tests: 1,083 passed and two were skipped.
  Four assertions still expected the previous dashboard text, save confirmations
  and role counts. Those expectations were updated for the requested behavior;
  all 23 tests in the affected files passed on the final rerun.
- All 43 administration JavaScript tests and all 38 theme tests passed.
- PHPStan passed across the complete application, database and routes with zero
  errors. Pint passed for the changed PHP files.
- Blade compilation, production Vite build and Git whitespace checks passed.
- The local database migration was applied. Existing content was preserved.
- A real database/public/private-files backup was created without pruning existing
  archives. Its 1,162 entries passed the ZIP CRC integrity check. Backups are
  downloaded through the permission-protected admin route; Apache direct access
  to the archive directory is denied.
- Follow-up Chrome visual verification covered 49 desktop/mobile views. Contact
  styling, the mobile admin sidebar and modal recovery-feedback placement were
  fixed and checked again. See [screenshots and findings](visual-qa/README.md),
  including the remaining missing `/pages/aboutus` content link.
- No operating system scheduler task was installed; automatic backups remain
  opt-in.
