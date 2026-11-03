# sch_of_slsc — Project architecture සහ හදිසි උපකාර reference

මෙම වාර්තාව **2026-10-22 දින local working tree එකේ තිබෙන code** පරීක්ෂා කර සකස් කළ snapshot එකකි. Commit කර නොමැති නව files සහ වෙනස්කම්ද ඇතුළත් වේ. Hosting server එකේ code, database rows, Apache configuration හෝ uptime පරීක්ෂා කර ඇති බව මෙයින් අදහස් නොවේ.

## 1. මේ report එක භාවිතා කරන ආකාරය

හදිසි ගැටලුවක් නම් මුලින් **Troubleshooting** වගුව බලන්න. URL එකක් සම්බන්ධ ගැටලුවක් නම් **Routes** tab එකෙන් URL/name එක සොයන්න. File එකක් වෙනස් කිරීමට පෙර **Files** tab එකෙන් එය සොයා එහි **Uses** සහ **Used by** සම්බන්ධතා බලන්න. Database column එකක් නම් **Database** tab එකෙන් model, table, relationships සහ migrations සොයන්න.

`index.html` internet නැතිවත් browser එකෙන් open කළ හැකියි. Data එහිම ඇතුළත් නිසා වෙනම web server එකක් අවශ්‍ය නැහැ. CSV files spreadsheet එකකින් filter කළ හැකියි. Markdown report එක IDE එකෙන් search/read කළ හැකියි. මෙම report එක local reference එකක් ලෙස තබන්න; public website directory එකේ publish කිරීම අවශ්‍ය නැහැ.

**Coverage:** project-owned files {{project_files}}, routes {{routes}}, Eloquent models {{models}}, detected dependency references {{edges}}. Metadata-only full inventory එකේ entries {{inventory_entries}} සහ directories {{directories}} ඇතුළත් වේ. PHP source files {{parsed_php_files}} AST parser එකෙන් parse කර තිබේ.

**Evidence:** PHP imports/type references, literal view/config/path references, Blade components, JS/CSS imports සහ model relationships source එකෙන් හඳුනාගෙන ඇත. Laravel route registration, Livewire component resolution සහ model table names local console bootstrap එකෙන් තහවුරු කර ඇත. Import එකක් තිබීම එම branch එක සෑම request එකකම execute වන බවට සාක්ෂියක් නොවේ. Database-stored HTML, dynamic variable view names, runtime hooks සහ third-party internals සඳහා graph එක සම්පූර්ණ runtime trace එකක් නොවේ. Detected references නැති file එකක් අනවශ්‍ය file එකක් ලෙස සලකන්න එපා.

`.env` content, passwords, APP_KEY, database rows, uploaded documents/images, log contents සහ Git objects වාර්තාවට copy කර නැහැ. Inventory එකේ ඒවායේ path/size metadata පමණක් තිබිය හැකියි. Safe configuration whitelist එක local environment snapshot එකක් පමණි.

## 2. Request එකක් මුල සිට අවසානය දක්වා යන මාර්ගය

```text
Browser / HTTP request
  -> Apache Alias / DocumentRoot + public/.htaccess
  -> public/index.php
  -> vendor/autoload.php
  -> bootstrap/app.php + bootstrap/providers.php
  -> web middleware + authentication/permissions
  -> routes/web.php (account settings: routes/settings.php)
  -> Controller හෝ Livewire component
  -> Service / Model / Database / Storage
  -> Blade view + layout + translations
  -> public/build + public/themes + public/storage URLs
  -> HTML/CSS/JavaScript response
```

`artisan` එකද එම application bootstrap එක භාවිතා කරයි; HTTP request එකක් වෙනුවට console command එකක් handle කරයි. `/up` health route එක `bootstrap/app.php` හරහා register වේ. එය boot health එක පෙන්වයි; සෑම business feature එකකම හෝ database query එකකම සාර්ථකත්වය සහතික කරන්නේ නැහැ.

## 3. Folder responsibilities සහ ඉවත් කිරීමට පෙර දැනගත යුතු දේ

| Folder / file | වගකීම සහ සම්බන්ධතා |
| --- | --- |
| `app/Http/Controllers/` | Public requests, previews සහ dashboard data. Routes විසින් controllers වෙත යොමු කරයි. Controllers models/services භාවිතා කර views වෙත data යවයි. |
| `app/Livewire/` | Admin screens සහ account settings සඳහා server-side state/actions. `wire:click`, `wire:submit`, `wire:model` හරහා Blade UI එකට සම්බන්ධ වේ. |
| `app/Models/` | Eloquent table mapping, casts, relationships, query scopes සහ model events. Database සහ services අතර data layer එකයි. |
| `app/Services/` | Workflows, sanitization, media/file operations, revisions, theme rendering සහ audit logging. UI එකෙන් භාවිතා කරන business rules මෙහි බොහෝ දුරට තිබේ. |
| `app/Enums/`, `Support/`, `Rules/`, `Concerns/` | Valid values, SEO/slug helpers, custom CSS validation සහ shared validation rules. |
| `app/Providers/` | Authentication setup, authorization, view-shared data සහ theme namespace/translation registration. |
| `bootstrap/` | Application boot සහ cached framework metadata. `app.php` සහ `providers.php` runtime සඳහා අවශ්‍යයි. |
| `config/` | Environment variables -> application configuration bindings. `.env` වෙනස් කිරීමක් config cache එක නිසා වහාම පෙනෙන්නේ නැති විය හැකියි. |
| `routes/` | URL -> handler, names, middleware සහ route constraints. Account routes වෙනම `settings.php` තුළ තිබේ. |
| `database/migrations/` | Schema evolution සහ සමහර content data updates. Migration file එකක් runtime model එකක් මෙන් request එකකට run වෙන්නේ නැහැ. |
| `database/seeders/`, `factories/` | Permission/bootstrap data සහ test data. Seeders සමහරක් production data වෙනස් කරන නිසා අරමුණ තේරුම්ගෙන භාවිතා කරන්න. |
| `resources/views/` | Core public/fallback layouts, admin Livewire views, components සහ authentication views. Folder එකම ඉවත් කරන්න බැහැ. |
| `resources/js/`, `css/`, `fonts/`, `tailwind/` | Vite build source, page editor logic සහ CMS Tailwind safelist. Build output `public/build/` තුළ යයි. |
| `themes/school-of-signals/` | Theme manifest, theme views, CSS/JS/fonts සහ theme translations. Public files publish කළත් source views සහ CSS runtime කටයුතු සඳහා තවම අවශ්‍යයි. |
| `lang/` | Core English/Sinhala UI labels, pagination සහ validation messages. Theme translations වෙනම theme namespace එකක තිබේ. |
| `public/` | Web-accessible entry point සහ assets. Web root/Apache Alias මේ folder එකට යොමු විය යුතුයි. |
| `storage/app/public/` | Uploaded public media. `public/storage` මෙයට ඇති link/junction එකයි; duplicate upload copy එකක් නොවේ. |
| `storage/app/private/` | Private files සඳහා configured local disk root. Public media සමඟ හුවමාරු නොකරන්න. |
| `storage/framework/`, `storage/logs/` | Compiled views, caches, test artifacts, sessions සහ logs. Data/uploads සමඟ එකට delete නොකරන්න. |
| `scripts/` | `compile-leadership-profile.mjs` runtime dependency එකකි. `build-*-deployment.py` files deployment packaging tools වේ. Folder එකම අනවශ්‍යයි කියලා ඉවත් කරන්න බැහැ. |
| `docs/apache/` | Apache instructions සහ web server configuration snippets (`sch-of-slsc.conf`). |
| `tests/` | Pest feature/unit checks. Local `Tests\\TestCase` එක RefreshDatabase භාවිතා කරයි. Production site එකේ සාමාන්‍ය request runtime එකට tests අවශ්‍ය නැහැ. |
| `vendor/` | Composer PHP dependencies. `composer.json` + `composer.lock` මත install වේ. Runtime සඳහා අවශ්‍යයි. |
| `node_modules/` | Node dependencies. මේ project එකේ leadership profile save/preview compiler එකටද අවශ්‍යයි. Server platform එකට ගැළපෙන ලෙස install කරන්න. |
| `.github/`, `.cursor/`, `.vscode/` | CI, AI/editor සහ IDE settings. `.git/` version history/metadata වේ. Local development copy එකේ Git history රැකගන්න. |

## 4. Application boot, auth සහ permissions

`bootstrap/app.php` එක `active` -> `EnsureUserIsActive`, `site.public` -> `EnsureSiteIsPublic` aliases register කරයි. `EnsureSiteIsPublic` web middleware stack එකටද append කරයි. Site settings table එකේ maintenance flag enabled නම් public users ට `public.maintenance` view එක සහ HTTP 503 යවයි. Admin/login/settings/password/verification/health paths සඳහා exemptions තිබේ; `admin.access` ඇති user කෙනෙකුට maintenance bypass එකක්ද තිබේ.

`EnsureUserIsActive` disabled account එක logout කර session invalidate කර login route එකට යවයි. `FortifyServiceProvider` login email lowercase/trim කර User lookup, `is_active` සහ password hash check කරයි. Login, password reset, email verification සහ two-factor views `resources/views/livewire/auth/` යටතේ සකස් කරයි. Passkey settings UI එක `resources/js/passkeys.js` සහ passkey Blade components භාවිතා කරයි.

Admin routes සඳහා සාමාන්‍ය chain එක `auth -> active -> verified -> can:admin.access -> feature permission` වේ. Individual Livewire actions/services තුළද Gate checks තිබේ. Sidebar link එක පෙන්වීම permission ලබාදීමක් නොවේ. `AppServiceProvider` හි Super Administrator role check එක Gate::before හරහා authorization එකට සම්බන්ධ වේ. Authentication events සහ business services `AuditLogger` හරහා audit records ලියයි.

`RolesAndPermissionsSeeder`, `SchoolModulePermissionsSeeder` සහ role/permission manager අතර සම්බන්ධය Database/Files tabs තුළ බලන්න. `RetireArchivePermissionsSeeder` legacy archive permissions මකා දමන data change එකකි. එය සාමාන්‍ය troubleshooting command එකක් ලෙස run නොකරන්න.

## 5. Feature අනුව end-to-end connections

### Homepage සහ hero slides

`home` / `home.localized` -> `PublicHomeController` -> SiteSetting, HeroSlide + translations + image variants, SchoolLeader + position/image, published News/Gallery/Document -> active theme `home.blade.php` හෝ `resources/views/public/home.blade.php`. Homepage leadership query එකේ current, active commandant position සඳහා විශේෂ filter එකක් සහ limit 1 තිබේ. වෙනත් leadership appointment එක homepage එකේ නොපෙනීම image/CSS ගැටලුවක් බවට වහාම තීරණය නොකරන්න.

`HeroSlideIndex` -> `resources/views/livewire/admin/hero-slides/hero-slide-index.blade.php` -> HeroSlide/HeroSlideTranslation + MediaAsset. Public button URL resolution HeroSlide model එකේද තිබේ. Button click ගැටලුවක් නම් saved URL, installation prefix, public routes සහ theme hero overlay CSS එක එකට පරීක්ෂා කරන්න.

### CMS Pages

`admin/pages` routes -> PageIndex/PageCreate/PageEdit/PageRevisionHistory + PagePreviewController -> Page, PageRevision, PageWorkflowService, PageRevisionService, sanitizers, slug/SEO/meta helpers. Page form එක HTML සහ visual editor data එකම canonical content field එකක තබයි. Shared form components -> `resources/js/page-content-editor.js` -> `resources/css/app.css` -> Vite build.

Public route -> PublicPageController -> locale + slug + published status + published_at <= now filter -> PageHtmlSanitizer / PageBlockRenderer / PageSeo -> active theme `pages.show` හෝ core `pages.show`. Published record එකක් තිබුණත් locale හෝ schedule වැරදි නම් 404 ලැබිය හැකියි. Legacy page blocks backend එක තවම තිබේ; නව UI එක වෙනස් වූ බව නිසා model/service code එක ඉවත් නොකරන්න.

Custom CSS -> `ValidPageCss` / `PageCss` -> Page + PageRevision `custom_css` -> `components/page/custom-styles.blade.php`. PageCss selectors page-specific scope එකකට යොමු කරයි; invalid stored CSS read කිරීමේදී හිස් result එකක් ලබාදීමට සකස් කර ඇත. CSS වෙනස්කම් නොපෙනේ නම් migration, saved value, scoped markup සහ compiled view/browser cache බලන්න. Database HTML තුළ අලුතින් type කළ Tailwind class එකක් Vite වෙත ස්වයංක්‍රීය source එකක් නොවේ; `resources/tailwind/page-content-safelist.html` සහ build output එකද බලන්න.

### News

NewsIndex/Create/Edit/RevisionHistory + NewsPreviewController -> NewsArticleService, NewsWorkflowService, NewsRevisionService, NewsImageService, NewsCategoryService, NewsDeletionService -> News, NewsRevision, NewsImage, NewsCategory, MediaAsset. PublicNewsController -> published/localized query -> theme news views හෝ core fallback views. English/Sinhala translation grouping සහ featured/multiple images වෙන වෙනම relationships වේ.

Public gallery එකේ news album නොපෙනේ නම් News `show_in_gallery`, published scope, selected locale සහ public image media eligibility බලන්න. News record එක පවතිනවා පමණින් gallery feed එකට ඇතුළත් වෙන්නේ නැහැ.

### Media සහ image variants

MediaUpload/MediaEdit/MediaIndex -> MediaUploadService / MediaReplacementService / MediaMetadataService / MediaDeletionService -> MediaAsset + MediaVariant -> configured disk. MediaUploadSecurity actual MIME, allowed extension, file size සහ image dimensions පරීක්ෂා කරයි. `config/media.php` හි image default limit 10,240 KB, document limit 20,480 KB, maximum dimensions 8,000 x 8,000 සහ maximum pixels 24,000,000 ය. PHP/web server upload limits මෙයට අමතර සීමා විය හැකියි.

MediaImageVariantService variants සෑදීම සහ MediaUrlService thumbnail/medium/original URL තේරීම සිදු කරයි. Image එක බිඳී ගියොත් DB media path/disk/source/visibility, physical file, variant row සහ `public/storage` link එක සම්බන්ධව පරීක්ෂා කරන්න. File එක physical folder එකෙන් පමණක් delete කළොත් DB references බිඳී යා හැකියි.

### Galleries

GalleryIndex/Create/Edit -> GalleryService -> Gallery + GalleryImage + MediaAsset. Cover selection, ordering, image membership සහ publish/unpublish rules service එකේ තිබේ. PublicGalleryController -> PublicGalleryFeed -> published manual galleries සහ eligible News albums එකට pagination කරයි. GalleryLocale URL/localization helper එකයි. Public views `resources/views/public/galleries/` තුළ ඇත; theme layout ඒවා වටා භාවිතා විය හැකියි.

### Documents — දැනට ක්‍රියාත්මක වන simplified upload flow

`admin/documents`, create සහ edit routes තුනම runtime registration අනුව **DocumentUploads** වෙත resolve වේ. DocumentUploads -> DocumentUploadManager -> Document, DocumentVersion සහ MediaAsset. Save කිරීම create/update permission එකට අමතරව `documents.publish` permission ඉල්ලයි; valid version එකක් ඇති විට document එක published බවට සකස් කරයි. English සහ Sinhala current version pointers වෙන වෙනම තබයි.

PublicDocumentController -> locale අනුව eligible version -> `public.documents.upload-index` / `upload-show` හෝ file stream. File view/download endpoints සහ legacy redirects දෙකම routes catalog එකේ තිබේ. පැරණි DocumentIndex/DocumentCreate/DocumentEdit/DocumentService සහ පැරණි document views තවම source/tests තුළ තිබුණත් primary admin routes ඒවා වෙත යන්නේ නැහැ. ඒ files මකාදැමීමට මෙම observation එක තනි සාක්ෂියක් නොවේ.

### School leadership සහ history

SchoolLeaderIndex -> SchoolLeader + SchoolLeadershipPosition + image media. SchoolLeadershipPositionIndex -> dynamic appointment types. SchoolLeaderProfiles -> LeadershipProfileContent -> HTML/CSS sanitizers -> **Node.js process** -> `scripts/compile-leadership-profile.mjs` -> `tailwindcss` in node_modules -> compiled profile CSS stored in SchoolLeader.

PublicSchoolLeadershipController -> enabled/current/history/profile filters -> theme commandant/leader-profile/school-leadership views. Profile document theme partial එකේ sandboxed iframe srcdoc එකක render වේ. `profile_enabled`, active leader, active position සහ language content පරීක්ෂා කරන්න. Profile save/preview පමණක් fail වේ නම් PHP application run වුණත් Node binary/PATH, compiler script හෝ Tailwind dependency අහිමි විය හැකියි.

PastCommandant සහ PastChiefInstructor නමින් legacy models/tables තවම තිබුණත් **දැනට history controllers තුනම SchoolLeader records** query කරයි. Commandants සඳහා `commandant`, chief instructors සඳහා `chief_instructor`, sergeant majors සඳහා `warrant_officer` appointment keys භාවිතා වේ. Position relationship එකක් නොමැති පැරණි rows සඳහා role_key fallback තිබේ. එම public pages legacy history tables වලින් data ගන්නා බව අනුමාන නොකරන්න.

### Menus, theme layouts, site settings සහ contact

AppServiceProvider public/core/theme layouts වෙත siteSettings, primaryMenu සහ publicSocialLinks share කරයි. Menu -> rootItems/children -> MenuItemTranslation -> resolvedUrl chain එක භාවිතා වේ. MenuService සහ models තිබුණත් current routes තුළ admin menu management screen එකක් හඳුනාගෙන නැහැ. Public menus තවම ඒ data භාවිතා කරන නිසා tables/services අනවශ්‍ය නොවේ.

ThemeLayoutIndex -> ThemeLayoutService + ThemeLayoutSanitizer -> ThemeLayout + ThemeLayoutRevision. Theme layout `layout.blade.php` -> ThemeLayoutRenderer -> published header/footer HTML/CSS. Placeholders (`[[primary_menu]]`, `[[contact_details]]`, `[[site_url:/...]]` වැනි) service එකෙන් replace කරයි; DB content Blade/PHP ලෙස evaluate නොකරයි. Requested language layout එක නැත්නම් English fallback එකක් භාවිතා කරයි. Published layout නැත්නම් Blade fallback markup තිබේ.

SiteSettingsIndex -> SiteSettingService -> SiteSetting relationships to logo/favicon/hero media. Site name, colors, Sinhala footer/address සහ maintenance mode මෙයට සම්බන්ධ වේ. PublicContactController POST requests throttle කර ContactMessage records සාදයි; ContactMessageIndex/ContactMessageService admin read/resolve flow එකයි. Mail configuration එක තිබීමෙන් පමණක් මෙම contact flow එක email notification යවන බව අනුමාන නොකරන්න.

RedirectIndex -> RedirectService -> Redirect. Route fallback -> PublicRedirectController. සාමාන්‍ය page route එකක් 404 වීම සහ redirect record එක match වීම වෙන වෙනම branches වේ.

## 6. Theme rendering සහ asset publishing අතර වෙනස

`AWCMS_ACTIVE_THEME` -> `config/awcms.php` -> ThemeManager safe manifest lookup -> ThemeViewResolver validated file -> theme view. ThemeServiceProvider `theme-school-of-signals::...` namespace සහ translation namespace register කරයි. Theme manifest එකේ slug, views සහ assets වැරදි නම් active theme එක resolve නොවී fallback views භාවිතා විය හැකියි.

Theme view source: `themes/school-of-signals/views/`. Theme CSS/JS source: `themes/school-of-signals/assets/`. Browser වෙත URL යන්නේ `public/themes/school-of-signals/assets/` යටතේ ඇති published copies වෙතයි. Source CSS වෙනස් කර publish නොකළොත් browser එක පරණ CSS එකම ගන්න පුළුවන්. `awcms:theme:publish-assets school-of-signals` command එක source assets public folder එකට copy කරයි. මෙය application files වෙනස් කරන operational command එකකි; source එක නිවැරදි බව තහවුරු කර run කරන්න.

`resources/css/app.css`, `resources/js/app.js`, `resources/js/passkeys.js` Vite entry points වේ. `app.js` page-content-editor, Trix සහ SortableJS import කරයි. `@vite` manifest එකෙන් hashed assets load කරයි; `public/build` අවශ්‍යයි. `public/hot` development server URL වෙත යොමු කරන marker එකක් නිසා production package එකට ඇතුළත් නොකරන්න.

Public-only assets source counterpart එකක් නොමැතිව තිබිය හැකියි. Snapshot එකේ `public/themes/.../images/slsc_about.png` theme homepage එකෙන් reference වේ. Theme source images folder එකේ එය නැති බව නිසා public image එක delete නොකරන්න. Asset inventory එකේ source/published hash match තොරතුරු ඇත. CSS URL එකක static reference නොපෙනීම DB-stored HTML තුළද reference නැති බවට සාක්ෂියක් නොවේ.

## 7. Database reference එක කියවීම

Database tab එකේ model එකක් තෝරා table name, soft deletes, fillable fields, casts, relationships සහ create/alter migration links බලන්න. Schema section එක **migration declarations** එකතුවකි; live DB schema dump එකක් නොවේ. Down/rollback declarations සහ repeated alter operations තිබිය හැකියි. `foreignId()->constrained()` තුළ implicit naming හෝ dynamic table names static analysis එකෙන් සම්පූර්ණයෙන් resolve නොවිය හැකියි.

Core content: pages/page_revisions; news/news_revisions/news_images/news_categories; media_assets/media_variants; galleries/gallery_images; documents/document_versions/document_categories. Site structure: menus/menu_items/menu_item_translations, site_settings/social_links/hero_slides/hero_slide_translations, theme_layouts/theme_layout_revisions, redirects/contact_messages/audit_logs. Leadership: school_leaders/school_leadership_positions සහ legacy history tables. Auth/infrastructure: users, roles/permissions/pivots, passkeys, sessions, cache, jobs සහ related tables migrations තුළ තිබේ.

Page/News statuses සඳහා enum values තවම තිබුණත් archive UI/actions retired වී තිබේ. Tests/ArchiveRetirementTest සහ service `archive()` methods පරීක්ෂා කරන්න. Enum එකේ `archived` තිබෙනවා කියලා දැනට archive button/workflow එකක් සක්‍රීයයි කියලා සිතන්න එපා.

Backup එකක් සෑදීමේදී database සහ `storage/app` දෙකම අදාළ වේ. Database backup එකක් පමණක් image/document physical files නැවත ලබාදෙන්නේ නැහැ. Production database engine, table presence සහ migrations state මෙම වාර්තාවෙන් තහවුරු කර නැහැ.

## 8. Troubleshooting — symptom එකෙන් අදාළ files වෙත

| ගැටලුව | මුලින් බලන්න | සම්බන්ධ files / ඊළඟ පරීක්ෂාව |
| --- | --- | --- |
| සියලු pages HTTP 500 | `storage/logs/laravel.log` හි අලුත්ම error එක; PHP version/extensions; vendor/autoload presence | `public/index.php`, `bootstrap/app.php`, `composer.lock`, config. Log එක publicly share කිරීමට පෙර credentials ඉවත් කරන්න. |
| Homepage 503; admin/login වැඩ | SiteSetting maintenance_mode හෝ Laravel maintenance state | `EnsureSiteIsPublic`, `public.maintenance`, `storage/framework/down`; දෙක වෙන වෙනම mechanisms වේ. |
| Login redirect loop / HTTP 419 | Actual URL prefix, session path/domain, HTTPS cookies, writable session storage | `config/session.php`, `config/app.php`, Apache Alias, Fortify views; config cache state. |
| Login disabled user message | User is_active | `EnsureUserIsActive`, `FortifyServiceProvider`; permissions එකක් පමණක් දීමෙන් active flag වෙනස් වෙන්නේ නැහැ. |
| Admin HTTP 403 | user role, admin.access සහ feature/action permission | route middleware, service Gate checks, RolesAndPermissionsSeeder, `UserManagementRules`. |
| Published page HTTP 404 | locale + slug + status + published_at + soft deletion | `PublicPageController`, Page model/scopes, routes; exact `/pages/` URL pattern බලන්න. |
| Theme එක load නොවේ | active theme setting + valid manifest + theme view file | `config/awcms.php`, ThemeManager/Resolver/Provider. |
| Past commandants page theme off කළ පසු fail | Controller එකේ core fallback view reference එකට file එක තිබේද | Snapshot එකේ `public.history.past-commandants` fallback file එක නැහැ; theme view එක තිබේ. `PastCommandantController.php` line 68 fallback branch බලන්න. |
| Header/footer edits නොපෙනේ | right theme/region/locale සහ published vs draft data | ThemeLayoutIndex, ThemeLayoutService/Renderer; English fallback branch බලන්න. |
| CSS/JS පරණයි | source vs published theme copy; Vite build manifest; browser cache | ThemeAssetPublisher, theme assets, public/themes, vite.config.js, public/build. |
| Vite manifest missing | build artifact deploy වී තිබේද | `npm run build` build machine එකේ; upload/install correct output; source code පමණක් upload කිරීම ප්‍රමාණවත් නැහැ. |
| CMS Tailwind class style නොපෙනේ | safelist/source detection සහ build | `resources/tailwind/page-content-safelist.html`, app.css. Profile compiler flow එක මෙයට වෙනස්ය. |
| Page custom CSS නොපෙනේ | custom_css migration + saved CSS + scope validation | PageCss, ValidPageCss, custom-styles component, PageRevisionService. |
| Images HTTP 404 | media disk/path සහ physical file; public/storage link | MediaUrlService, filesystems/media config, media_variants; DB reference රැකගන්න. |
| Upload rejects file | actual MIME/extension + app/PHP/web server limits | MediaUploadSecurity, config/media.php, Livewire temporary uploads. |
| Leadership profile preview/save fail | Node/PATH + compiler file + tailwindcss package | LeadershipProfileContent, scripts/compile-leadership-profile.mjs, node_modules. |
| Leadership profile public 404 | active leader/position, profile_enabled, required language content | SchoolLeader, PublicSchoolLeadershipController, SchoolLeaderProfiles. |
| Document save 403 | documents.create/update සමඟ documents.publish | DocumentUploadManager + DocumentUploads; current routes old DocumentEdit වෙත යන්නේ නැහැ. |
| Sinhala document missing | current_version_si සහ එම version/media eligibility | PublicDocumentController, DocumentUploadManager, DocumentVersion. |
| Gallery/news album missing | published + show_in_gallery + public image requirements | PublicGalleryFeed, GalleryService, NewsImageService, GalleryLocale. |
| Hero button click fail | saved URL/prefix, destination route, overlay CSS | HeroSlide model, both homepage views, theme.css. |
| Sinhala labels නොපෙනේ | locale selection + root/theme translation namespace | `lang/si`, `lang/si.json`, theme/lang/si, ThemeServiceProvider. |
| Removed tests නිසා test command fail | hosting copy එකේ tests/dev tools ඉවත් කළාද | Local full development copy එකෙන් tests run කරන්න; website runtime එක වෙනම පරීක්ෂා කරන්න. |

## 9. ඉක්මන් පරීක්ෂණ සහ operational commands

පහත read-only metadata commands project root (`artisan` තිබෙන directory) එකෙන් run කළ හැකියි. ඒවා database/data recovery හෝ full end-to-end test එකක් නොවේ.

```bash
php artisan --version
php artisan about --only=environment
php artisan route:list --except-vendor
php artisan migrate:status
composer check-platform-reqs --no-dev
node --version
npm --version
```

`migrate:status` configured database එකට connect වී migration state කියවයි. `route:list` success වීමෙන් handlers register වීම පමණක් තහවුරු වේ; authenticated actions execute වන්නේ නැහැ. `/up`, homepage, Sinhala page සහ login browser/curl මගින්ද පරීක්ෂා කරන්න.

```bash
site_url='https://site.army.lk/sch_of_slsc'
for check_path in /up / /si /login; do
  curl -sS -L --max-time 30 -o /dev/null \
    -w "$check_path -> HTTP %{http_code}\n" "$site_url$check_path"
done
```

ඉහත URL එක repository deployment guide එකේ target URL එකයි; report එක සකස් කිරීමේදී එම host එකට request කර නැහැ. Actual hosting prefix/domain වෙනස් නම් URL එක වෙනස් කරන්න. Healthy public site එකක සාමාන්‍ය result එක 200 ය; maintenance/auth configuration අනුව response වෙනස් විය හැකියි. Admin login, save, upload සහ profile preview manually verify කිරීමද අවශ්‍යයි.

පහත commands cache files වෙනස් කරන නිසා ගැටලුවට අදාළ විට පමණක් භාවිතා කරන්න:

```bash
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan config:cache
```

Theme source assets නිවැරදි කර ඇති විට `php artisan awcms:theme:publish-assets school-of-signals` public copies වෙනස් කරයි. Filesystem link අහිමි නම් `php artisan storage:link` උපකාරී විය හැකියි; දැනට තිබෙන physical uploads folder/link එක මුලින් බලන්න. `npm run build` frontend output නැවත ලියයි. `php artisan optimize:clear` default cache entriesද clear කළ හැකි නිසා targeted fix එකක් වෙනුවට සෑම විටම run නොකරන්න.

Existing production installation එකේ `php artisan key:generate`, `migrate:fresh`, `db:wipe`, `composer setup` හෝ අරමුණ නොදන්නා seeders troubleshooting සඳහා run නොකරන්න. `composer setup` project script එක key generation/migration/build කටයුතු ඇතුළත් කරන initial setup script එකකි. Permission problem එකකට project එකම 777 කිරීම වෙනුවට actual web process user/group සහ අවශ්‍ය writable directories (`storage`, `bootstrap/cache`) නිවැරදි කරන්න.

Local full development copy එකේ tests/dev dependencies තිබෙන විට:

```bash
php artisan test
composer lint:check
composer types:check
```

`composer test` script එක config:clear, lint, PHPStan සහ test suite එක එකට run කරයි. `phpunit.xml` in-memory SQLite testing configuration එක දක්වයි; actual environment overrides සහ cache files පරීක්ෂා නොකර production database එකකට tests යොමු නොකරන්න. මේ documentation task එකේ full suite run කර නැහැ.

## 10. Change එකක් කළොත් එකට බලන්න ඕන files

| වෙනස්කම | සම්බන්ධ files සහ verification |
| --- | --- |
| URL/route name | routes -> controller/Livewire -> sidebar, Blade route() calls, MenuItem URLs, SEO canonical URLs, tests. |
| Page field | migration -> Page casts/validation -> PageCreate/Edit -> revisions capture/restore -> preview/public view -> tests. |
| News/image field | migration/model -> article/image/workflow services -> admin forms -> public news/gallery feed -> tests. |
| Leadership profile | migration + SchoolLeader -> profile component/service -> Node compiler -> sandbox view -> profile tests. |
| Upload rule | config/media -> MediaUploadSecurity -> upload/replacement validation -> variants -> document allowlist -> security tests. |
| Theme CSS/JS | source asset -> published copy -> cache-busting/public page -> desktop/mobile visual check. |
| Header/footer/menu | admin DB layout -> sanitization/placeholders -> locale publish -> fallback layout -> menu translations/resolved URLs. |
| Role/permission | permission seeder/role UI -> route can: -> service Gate -> sidebar visibility -> permission boundary tests. |
| Database restore | matching uploads + application version + migration state + existing APP_KEY/config -> public/admin smoke checks. |

## 11. Legacy / convention details that can mislead

Livewire class components ප්‍රථමයෙන් resolve කරන බව installed `vendor/livewire/livewire/src/Factory/Factory.php` තුළ සහ local route metadata තුළ දැකිය හැකියි. `resources/views/components/admin/users/⚡user-*.blade.php` placeholders තිබුණත් current named admin routes resolve වන්නේ `app/Livewire/Admin/Users/` class components වෙතයි. Duplicate-looking views තිබෙනවා කියලා current route handler එක අනුමාන නොකරන්න.

Current DocumentUploads flow, legacy DocumentService/UI සහ existing tests වෙන වෙනම පවතී. Menu admin screen ඉවත් වුවත් public menu rendering තවම සක්‍රීය source references ඇත. `resources/views/livewire/admin/users/audit-logs/` යටතේ ඇති simple view එක සහ main audit-log view එක වෙනම paths වේ. Theme-specific history view එකක් නොතිබුණොත් controller fallback එක භාවිතා කරයි.

Generated/dependency code සඳහා source-level graph එක recursively parse කර නැහැ. Full inventory එකේ `vendor`, `node_modules`, `.git`, uploads සහ generated files සියල්ලේ metadata ලැයිස්තුගත කර තිබේ. Composer package versions, dev/runtime classification සහ package requirements වෙනම package list එකේ ඇත. Binary file එකකට source code call graph එකක් නොලැබීම සාමාන්‍යයි.

## 12. Report නැවත සෑදීම

Project code වෙනස් වූ පසු local project root එකෙන් පහත commands run කරන්න. මේ tools documentation outputs සෑදීම හැර application business data වෙනස් කිරීමට සකස් කර නැහැ. Local Laravel console boot සහ model metadata inspect කරයි; credentials export නොකරයි. Framework bootstrap එකෙන් සාමාන්‍ය cache metadata ඇති විය හැකියි.

```bash
php -d xdebug.mode=off docs/architecture/tools/extract_php.php
php -d xdebug.mode=off docs/architecture/tools/inspect_runtime.php
php -d xdebug.mode=off artisan route:list --json -vv > docs/architecture/routes-runtime.json
python docs/architecture/tools/build_data.py
python docs/architecture/tools/build_report.py
```

PHP AST extractor එකට local development vendor tree එකේ `nikic/php-parser` අවශ්‍යයි. Production `--no-dev` copy එකේ එය නොමැති විය හැකියි. Python report tools standard library පමණක් භාවිතා කරයි. Documentation output folder එක inventory scan එකෙන් ඉවත් කර ඇත්තේ report එක තමන්වම නැවත index නොකිරීමටයි. Files/line numbers වෙනස් වූ විට report එක නැවත generate කරන්න.
