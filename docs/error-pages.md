# Friendly error pages

Normal browser requests show a standalone recovery page for HTTP 4xx and 5xx exceptions, including 403, 404, 419, 429, 500 and 503. Unknown addresses, expired forms and server failures retain their original HTTP status codes. The screen uses the site's navy/green appearance, local fonts and optional theme logo.

The page offers a homepage link, a back link when the referring page belongs to the same installation, and a retry link for temporary errors on safe requests. Expired forms are not resubmitted. Error messages are available in English, Sinhala and Tamil, selected from the requested language or application locale. The homepage link uses the always-enabled English root, so disabled languages do not cause a recovery loop.

The renderer uses no site settings, database queries, theme layout, Vite manifest, JavaScript or external services. It is suitable for database and asset-build failures. It does not display exception messages or traces in browser error screens, including when local debug mode is enabled. Laravel still reports exceptions normally.

JSON/API errors, Livewire responses, authentication redirects and form validation redirects keep their existing behavior. Exception response headers such as `Retry-After` and `Allow` are retained. Error pages are not cached or indexed, and HEAD responses remain empty.

Implementation: `app/Services/ErrorPageRenderer.php`, `resources/views/errors/friendly.blade.php`, `lang/{en,si,ta}/errors.php` and the exception response callback in `bootstrap/app.php`.

Run the checks with:

```bash
php artisan test tests/Feature/FriendlyErrorPagesTest.php tests/Feature/LivewireUpdateResponseTest.php
```
