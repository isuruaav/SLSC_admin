# AWCMS / SLSC final system verification

Date: 4 November 2026 (Asia/Colombo). Base: `d533e4535953fbc05bedf4b46de80f14b7c2e7eb`, branch `chore/safe-system-cleanup`. PHP 8.4.15, PHPUnit 12.5.30, MySQL 8.4.7. Installed dependency versions were preserved.

## A. Test execution summary

| Category | Discovered | Passed | Failed | Skipped | Assertions |
|---|---:|---:|---:|---:|---:|
| Unit Tests | 21 | 21 | 0 | 0 | 51 |
| Admin Tests | 604 | 604 | 0 | 0 | 3,003 |
| Authentication Tests | 18 | 16 | 0 | 2 | 36 |
| Settings Tests | 11 | 11 | 0 | 0 | 35 |
| Theme Tests | 67 | 67 | 0 | 0 | 238 |
| Public Feature Tests | 124 | 124 | 0 | 0 | 615 |
| Other Tests (135 PHP + 121 JavaScript) | 256 | 256 | 0 | 0 | 795 PHP; JS not reported |
| **TOTAL UNIQUE TESTS** | **1,101** | **1,099** | **0** | **2** | **4,773 PHP** |

Final PHP command **exited 0**: 980 discovered/executed, 978 passed, two intentional skips, **zero failures and zero errors**, 4,773 assertions, 161.923699 seconds in JUnit (162.133 seconds in the Pest summary). Discovery IDs are unique; discovered and executed totals match globally and for every test class. All 121 JavaScript cases passed with exit 0, zero failures/skips/cancellations. Node does not publish counts of individual `assert` calls. JavaScript TAP duration: 0.295598 seconds application, 2.716885 seconds theme.

Case manifests: [PHP](verification-test-manifest.csv), [JavaScript](verification-javascript-manifest.csv). Each PHP dataset has its own row, result, assertion count and duration. Each intentional skip includes its reason.

Accounting rules: Unit takes precedence, followed by theme filenames, Admin, Auth, Settings, public/past feature filenames, and remaining tests. A theme test under Admin appears only under Theme. JavaScript cases appear under Other in the combined table. The CSV manifests list every case and dataset separately. Additional MySQL runs and diagnostic reruns do not increase the unique application inventory.

The initial inventory was **972 PHP cases: 21 Unit and 951 Feature, across 137 test files**. The older 935 Feature / 943 total figures are not the current discovered inventory; no complete older manifest was available to substantiate them. This work added six search cases (including three validation datasets) and two CSRF cases: **980 PHP cases in 138 files**. The JavaScript inventory is **87 application cases plus 34 theme cases**, including eight new Spotlight cases. Total: **1,101 unique automated cases**. The standalone registration PHP checks execute inside one theme JavaScript case and are not counted again.

The two registration skips are intentional: `config/fortify.php` disables public registration. They are `registration screen can be rendered` and `new users can register`. Registration was not enabled to manufacture coverage. No test was disabled or assertion removed.

Initial complete PHP execution: exit 1, 972 cases, 969 passed, one failed, two intentional skips, 4,729 assertions, 258.857592 seconds in JUnit. A subsequent verification attempt was stopped when the document-search security gap was found; it is excluded from final accounting. The final run below supersedes those diagnostic results.

Supplemental MySQL verification, using only `awcms_test_verification_20261104_57d2fe6f`:

| Run | Cases | Passed | Failed/errors/skipped | Assertions | JUnit seconds |
|---|---:|---:|---:|---:|---:|
| Migrations and critical document/news/media/revision/search/category behavior | 80 | 80 | 0 | 297 | 434.306368 |
| Final document visibility, document administration and search regression | 93 | 93 | 0 | 341 | 30.015461 |

These runs cover **145 distinct MySQL cases**, with 28 repeated cases. They are supplemental executions of cases already in the PHP inventory. The second run reused the same disposable schema only after checking that its migration names exactly matched all 67 repository migrations; individual tests retained transaction rollback.

## B. Bug fixes and evidence

| Issue | Root cause and correction | Evidence |
|---|---|---|
| Document factory error | Added typed `HasFactory` support and a schema-compatible factory. Draft documents start at version zero. Removed the invalid `locale` fixture attribute; documents use English/Sinhala columns. Removed the pre-existing factory override that silently discarded `locale`. | Public search and document suites pass on SQLite and MySQL. |
| News category required-field test | Migrations intentionally seed two categories. Assert that a rejected form leaves the original row count unchanged, preserving all required-field assertions and seeded categories. | Affected category UI/service and full suite. |
| Homepage category borrowing | Existing placement tests, category scopes and separate section empty states establish category isolation. Retained the pre-existing removal of cross-category fallback queries. Publication, locale, featured ordering and the three-card limit remain in each category query. | `NewsCategoryPlacementTest`, public news and full regression. |
| Batch-only invalid Livewire JSON | Two language test files emitted whitespace before `<?php`. Discovery sent headers before tests began. Livewire then used its streaming fallback, whose JSON encoding differs from the custom controller. Removed only the leading whitespace. | Nine-case reproduction failed before the correction; header diagnostic identified `PublicNewsLanguageTest.php:1`. The affected 31-case run passed afterward. No output buffering, warning suppression, JSON assertion or security middleware was weakened. |
| Search parameter HTTP 500 | Array-valued `q` or `type` parameters were cast to strings. Added nullable-string validation to the page and suggestion endpoint. | All three regression datasets failed with HTTP 500 / array-to-string warnings before the fix; they now require HTTP 422 validation errors. |
| Private document metadata in search | Search used publication status alone, bypassing current-version/media visibility checks used by document pages. Moved the existing public-document query into `DocumentUploadManager::publicQuery()` and reused it in both controllers. | Regression failed before the fix. Private/deleted current media, missing versions and an older public version superseded by a private current version are excluded in English/Sinhala page and suggestion results. All 93 affected cases pass on both databases. |
| Spotlight accessibility and timing | Added Tab/Shift+Tab containment, cancelled pending debounce/focus timers when closing or clearing, restored initial ArrowUp selection, exposed selection state and prevented delayed results from rendering after close. Added an input label and live result announcements. | Eight DOM interaction cases exercise shortcuts, focus, backdrop/close, suggestions, loading/empty/error states, cancellation, escaping and arrow/Enter navigation. |
| Legacy navigation test dependency and managed-menu regression | Two tests loaded the removed theme script. They now import an SLSC navigation module used by the active theme. Preserved assertions and restored exact-route highlighting, current submenu expansion, Escape/outside/link/desktop-resize closing and focus restoration for managed headers. | All 87 application JavaScript cases and 34 theme cases pass. No legacy theme was restored. |

The four changes already present when this task began were reviewed and retained/refined: homepage fallback removal, Document model factory support, DocumentFactory and the NewsCategory UI fixture correction. Unrelated work was not staged. No production migration or dependency version was changed.

## C. Security verification

- Authentication/password reset/email verification/2FA/passkey settings and session behavior are covered by Auth and Settings suites. Public registration remains disabled.
- Admin role/permission boundaries, Super Administrator behavior, user deletion/security and audit logging are covered by the Admin suites; authorization was not bypassed.
- New `RequestForgeryProtectionTest` cases exercise real CSRF rejection for login and Livewire. They temporarily remove Laravel's usual unit-test CSRF bypass within the already isolated application. Missing tokens return 419; a valid session token reaches Livewire's payload validation and returns 404 for an empty payload.
- Content/HTML/CSS sanitization, template injection rejection and JavaScript result escaping have automated coverage. Structured search inputs now produce validation responses.
- Private documents/publications/media access, deleted files, current versions, upload type/size checks, gallery invariants and deletion permissions have automated coverage. Search now shares public document visibility rules.
- Navigation preview CSP is present and tested. No site-wide response CSP was found in application middleware; deployment/server headers were not audited. A global CSP was not added because it requires a separate compatibility review.
- Login/contact throttling configuration and the standalone registration backend's rate-limit/CSRF checks remain intact. This is test/configuration evidence, not a penetration test or a claim about deployed infrastructure.

## D. Theme, public routes and multilingual verification

Active theme: `slsc`. Manifest detection, safe slug resolution, Blade layouts, locale-aware layout rendering, admin theme layout services/sanitization, public integration and legacy absence tests are included in the full inventory. The obsolete theme is absent from both source and published theme paths.

`php artisan awcms:theme:publish-assets slsc` exited 0 and published 32 files. Source/published hashes match for `script.js`, `search.js` and the new `navigation.js`. Existing required icon/font/image tests pass. The frontend production build exited 0. Its optional `fontaine` optimization and plugin-duration warnings remain informational; no dependency was installed for those warnings.

Public feature suites cover home, news/category/detail/languages, search/suggestions, galleries, documents/files, publications/files, courses, leadership/history profiles, pages/SEO/blocks, menus/footer, contact and friendly error responses. Disabled/draft/future/deleted visibility and access restrictions are exercised where implemented by those suites.

English/Sinhala public defaults remain intact. Tamil remains supported by the settings/localization architecture and is exercised in isolated settings tests; it was not enabled on the development website. Existing saved live language settings/content were not edited. Locale switchers, translated content and SEO links are covered by public language/SEO tests.

Browser tooling inventory returned no usable browser surface and reported an unavailable native pipe. Consequently **desktop/tablet/mobile screenshots, actual browser keyboard input, touch behavior, font glyph rendering, console errors and network-asset loading were not verified**. Spotlight keyboard/focus coverage is executed DOM interaction with JSDOM, not source-only inspection and not a real-browser claim. No CSS or visual redesign was made; visual equivalence was not asserted from a pixel comparison. Node/JSDOM tests of the standalone PHP theme are distinguished from Laravel Blade HTTP tests in the manifests.

## E. Database, storage and environment safety

The development `.env` selects MySQL `slsc_db`; it was not edited. No tests or migration commands targeted that database. There was no cached application config. An ignored `.env.testing` selected SQLite `:memory:`, array cache/session/mail, synchronous queues and local filesystem defaults, with a synthetic test key.

`Tests\TestCase::createApplication()` now rejects an unsafe environment/database before `RefreshDatabase` can migrate: only in-memory SQLite or a separately named `awcms_test_` MySQL database is accepted, and connection URLs are rejected. It isolates cache/session/mail/queue configuration, fakes local/public/S3 disks and prevents unmocked Laravel HTTP-client requests. Test uploads and deletion assertions run against fake storage. No user-uploaded file, private publication or backup was removed. Tests may use their own temporary files; live storage was not used as a fixture.

All 67 migrations completed against both SQLite and the disposable MySQL schema. Critical relationships, revisions, factories, uniqueness/foreign-key behavior and publication/current-version filters are covered by the respective suites. The disposable MySQL schema is retained for reproducibility; test rows roll back. Schema results are not a database backup/integrity audit of production records.

`task-4532` was not running before this task's tests began. Its recovered Antigravity log is one byte (a newline), so its exit code, test counts, assertions and duration cannot be recovered. Later Antigravity logs contain different batch output and were not substituted for this process. No duplicate test run was launched while an original PHP test process was active.

## F. Git, quality checks and reproducibility

Read-only `git ls-remote` confirmed GitHub's branch backup at `d533e4535953fbc05bedf4b46de80f14b7c2e7eb` before edits. Corrections and this report are included in the new local commit on `chore/safe-system-cleanup`; its concrete ID and final working-tree status are in the final task response. No push, hosting merge or deployment was performed.

| Check | Result |
|---|---|
| Composer validation | Exit 0; exact-version constraint recommendations remain |
| Production frontend build | Exit 0 |
| Theme publishing | Exit 0; 32 assets |
| JavaScript syntax checks | Exit 0 for modified theme scripts |
| Repository Pint check | Exit 1; 64 files with formatting findings; broad formatting deliberately not applied |
| PHPStan/Larastan level 7 | Exit 1; 49 findings across 14 files; no baseline, ignore or type-suppression entries added |
| Git whitespace check | Exit 0 |

Full static/style findings are in `verification-static-analysis.csv` and `verification-style-findings.csv`. They remain documented quality-check failures, separate from automated test failures. No security assertion was reduced to address them.

Reproduce from the repository root with installed dependencies and isolated testing configuration:

```powershell
$env:XDEBUG_MODE = 'off'
$env:APP_ENV = 'testing'
New-Item -ItemType Directory -Force test-results | Out-Null
php artisan test --list-tests-xml=test-results/test-manifest-final.xml --no-ansi
php artisan test --compact --log-junit=test-results/full-final.xml --no-ansi
node --test --test-reporter=tap tests/JavaScript/*.mjs tests/Frontend/*.mjs
Push-Location themes/slsc
node --test --test-reporter=tap tests/*.test.js
node --check assets/js/script.js
node --check assets/js/navigation.js
node --check assets/js/search.js
Pop-Location
composer validate --no-check-publish
php vendor/bin/pint --test
php -d memory_limit=1G vendor/bin/phpstan analyse --memory-limit=1G --no-progress -v
npm.cmd run build
php artisan awcms:theme:publish-assets slsc
```

The two existing editor DOM test files require `NAVIGATION_DOM_MODULE` to point to LinkeDOM's standalone worker. This run placed `linkedom@0.18.12/worker.js` in ignored `test-results/linkedom-worker.mjs` and set that variable to its absolute path. SHA-256: `196efeb17c260e001979dbc54a3c30e701a881c6e8a3eedaddc5ad83c99ee5ff`. Theme tests used the already installed JSDOM dependency. No framework/dependency version was changed.

For a fresh MySQL reproduction, create a new **local**, disposable database with the `awcms_test_` prefix, set testing process variables to that database (never `slsc_db`), then run the selected critical files. The first MySQL run used document services, news article/workflow services, media foundation, page revision foundation, public search and category placement. The final document regression used `tests/Feature/PublicSearchTest.php`, `tests/Feature/PublicDocumentTest.php` and `tests/Feature/Admin/Documents`. `RefreshDatabase` will rebuild the selected test database.

Complete process logs, exit-code files, stack traces, raw discovery XML, JUnit XML and TAP are retained in ignored `test-results/`, protected from Apache access by `.htaccess`. Do not publish this diagnostic directory. Tracked CSV manifests preserve case-level results without credentials or live content.

## G. Final verdict

**FINAL TESTING COMPLETED WITH DOCUMENTED FAILURES**

The complete discovered automated inventory has **zero failing tests and zero test errors**. Two registration cases are intentionally skipped for a disabled feature. The overall verdict remains qualified because the repository-wide Pint and PHPStan checks fail and real-browser visual/network/interaction verification was unavailable. No assertion was weakened, failure hidden, business-rule conflict left unresolved, production database modified or legacy theme restored. Verified corrections were committed locally; no push, merge or deployment followed.
