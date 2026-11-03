# Site languages

Open **Site Settings → Site Languages**. Select English, Sinhala and/or Tamil, then click **Save Site Settings**. For the current site, English and Sinhala are enabled initially. Check Tamil and save when you are ready to use it.

English stays enabled as the fallback language. Enabling a language exposes its existing translations; enter translated content manually. This setting does not translate articles or add translation files automatically.

The saved selection controls public language switchers, page/news translation links, alternate-language SEO links, navigation previews, header/footer language tabs and the language choices for pages and news. Disabled public language URLs return 404. Disabling a language keeps all its content, draft/published layouts and revision history. An administrator editing an existing disabled-language page or article can still see its current language; new language choices use the enabled list.

Super Administrators and Site Administrators can manage languages through the existing `settings.manage` permission. Other roles need that permission explicitly; their current access is unchanged.

Existing installations need the new settings column:

```bash
php artisan migrate --path=database/migrations/2026_10_27_120000_add_enabled_locales_to_site_settings.php --force
```

Until a selection is saved, the site uses its existing `awcms.theme_locales` defaults. Saved settings override that initial theme list, so Tamil can be enabled from the admin panel without editing configuration files.

Validation:

```bash
php artisan test tests/Feature/SiteLanguagesTest.php tests/Feature/SiteSettingsFooterTest.php tests/Feature/Admin/ThemeLayoutAdminUiTest.php tests/Feature/Admin/RolePresetsTest.php
```
