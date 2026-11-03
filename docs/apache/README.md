# Clean application URL on Apache2

Target URL: `https://site.army.lk/sch_of_slsc`

Application root: `/var/www/html/sch_of_slsc`

`sch-of-slsc.conf` maps the `/sch_of_slsc` URL prefix to the application's `public` directory. It also redirects existing `/sch_of_slsc/public/...` links to the corresponding new URL while preserving the path and query string. This config applies to the Apache instance; if this prefix must exist on only one virtual host, include the snippet inside that host instead.

## Install

Upload `docs/apache/sch-of-slsc.conf` into the same relative directory on the hosting server. On Debian/Ubuntu Apache2, run:

```bash
cd /var/www/html/sch_of_slsc
sudo install -m 0644 docs/apache/sch-of-slsc.conf /etc/apache2/conf-available/sch-of-slsc.conf
sudo a2enconf sch-of-slsc
sudo apache2ctl configtest
```

Continue only if the config test reports `Syntax OK`. If an existing virtual host already defines an Alias or ProxyPass for this application prefix, inspect that existing mapping before reloading.

Edit the hosting `.env` and set:

```dotenv
APP_URL=https://site.army.lk/sch_of_slsc
SESSION_PATH=/sch_of_slsc
```

If `ASSET_URL` is explicitly set to the previous application URL ending in `/public`, update that value to `https://site.army.lk/sch_of_slsc` too. An independently configured CDN URL can stay as configured.

Then run:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
sudo systemctl reload apache2
```

## Verify

Check the homepage, login, and a public build asset:

```bash
curl -I https://site.army.lk/sch_of_slsc/
curl -I https://site.army.lk/sch_of_slsc/login
curl -I https://site.army.lk/sch_of_slsc/build/manifest.json
curl -I https://site.army.lk/sch_of_slsc/public/
curl -I https://site.army.lk/sch_of_slsc/.env
```

The homepage, login, and manifest should be available. The old `/public/` URL should redirect to `/sch_of_slsc/`. The `.env` URL must return 403 or 404. Verify that a public uploaded image loads and that admin login works under the new URL.

The initial redirect uses 302 so the change can be verified without a permanent browser redirect. Once verified, change `RedirectMatch 302` to `RedirectMatch 301`, test the Apache configuration, and reload Apache.

## Roll back this mapping

```bash
sudo a2disconf sch-of-slsc
sudo apache2ctl configtest
```

Restore the previous `APP_URL`, `SESSION_PATH`, and any changed `ASSET_URL`, clear and rebuild the Laravel config cache, then reload Apache after a successful config test.

References: [Laravel deployment](https://laravel.com/docs/13.x/deployment#server-configuration), [Apache Alias](https://httpd.apache.org/docs/2.4/mod/mod_alias.html#alias), [Apache RedirectMatch](https://httpd.apache.org/docs/2.4/mod/mod_alias.html#redirectmatch).
