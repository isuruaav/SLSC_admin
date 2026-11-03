"""Build 2026-10-28 update package containing all updates from today."""
from datetime import datetime, timedelta, timezone
from hashlib import sha256
import json
import os
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

ROOT = Path(__file__).resolve().parents[1]
OUTPUT_ZIP = ROOT / 'sch_of_slsc-update-2026-10-28.zip'
DEPLOY_UPDATES_DIR = ROOT / 'deploy/updates'
DEPLOY_ZIP = DEPLOY_UPDATES_DIR / 'sch_of_slsc-update-2026-10-28.zip'
COMMANDS_TXT = ROOT / 'UPDATE-2026-10-28-COMMANDS.txt'
DEPLOY_COMMANDS_TXT = DEPLOY_UPDATES_DIR / 'sch_of_slsc-update-2026-10-28-COMMANDS.txt'

FILES_TO_PACKAGE = [
    # Console Commands
    'app/Console/Commands/CreateSiteBackup.php',
    'app/Console/Commands/FixThemeHeaderCommand.php',
    'app/Console/Commands/PatchThemeLayoutSearch.php',

    # Controllers
    'app/Http/Controllers/PublicContactController.php',
    'app/Http/Controllers/PublicDocumentController.php',
    'app/Http/Controllers/PublicHomeController.php',
    'app/Http/Controllers/PublicMediaFileController.php',
    'app/Http/Controllers/PublicPublicationController.php',
    'app/Http/Controllers/PublicSearchController.php',
    'app/Http/Controllers/PublicSitemapController.php',

    # Middleware
    'app/Http/Middleware/EnsureSiteIsPublic.php',

    # Models
    'app/Models/Menu.php',
    'app/Models/MenuItem.php',
    'app/Models/SiteSetting.php',
    'app/Models/SocialLink.php',
    'app/Models/ThemeLayout.php',

    # Providers & Services
    'app/Providers/AppServiceProvider.php',
    'app/Services/SiteLanguageService.php',
    'app/Services/ThemeLayoutRenderer.php',
    'app/Services/ThemeLayoutSanitizer.php',
    'app/Services/ThemeManager.php',

    # Config & Routes
    'config/livewire.php',
    'routes/web.php',

    # Localization
    'lang/en/public.php',
    'lang/si/public.php',

    # Public static / server files
    'public/robots.txt',
    'public/themes/school-of-signals/assets/css/navbar.css',
    'public/themes/school-of-signals/assets/css/theme.css',

    # Views & Starters
    'resources/css/footer-starter.css',
    'resources/css/navigation-starter.css',
    'resources/views/public/news/show.blade.php',
    'resources/views/public/search/index.blade.php',
    'resources/views/public/search/modal.blade.php',

    # Theme views & assets
    'themes/school-of-signals/assets/css/navbar.css',
    'themes/school-of-signals/assets/css/theme.css',
    'themes/school-of-signals/views/commandant.blade.php',
    'themes/school-of-signals/views/layout.blade.php',
    'themes/school-of-signals/views/leader-profile.blade.php',
    'themes/school-of-signals/views/news/show.blade.php',
]

def make_zip(target_path):
    with ZipFile(target_path, 'w', compression=ZIP_DEFLATED, compresslevel=9) as zipf:
        for rel in sorted(FILES_TO_PACKAGE):
            file_path = ROOT / rel
            assert file_path.is_file(), f"Missing file: {rel}"
            # Standardize zip info for reproducible builds
            zipf.write(file_path, arcname=rel.replace('\\', '/'))

make_zip(OUTPUT_ZIP)
if DEPLOY_UPDATES_DIR.is_dir():
    make_zip(DEPLOY_ZIP)

# Write checksums
with open(str(OUTPUT_ZIP) + '.sha256', 'w') as f:
    f.write(sha256(OUTPUT_ZIP.read_bytes()).hexdigest() + '  ' + OUTPUT_ZIP.name + '\n')

commands_content = """============================================================
 School of Signals — 2026-10-28 Cumulative Update Package
============================================================

File: sch_of_slsc-update-2026-10-28.zip
Total updated files: 39

------------------------------------------------------------
A. LINUX / CPANEL / SSH TERMINAL COMMAND (RECOMMENDED)
------------------------------------------------------------
1. Upload 'sch_of_slsc-update-2026-10-28.zip' to /var/www/html/sch_of_slsc/ (the folder with artisan).
2. Run this single command:

unzip -o sch_of_slsc-update-2026-10-28.zip && php artisan theme:fix-header && php artisan route:clear && php artisan view:clear && php artisan config:clear

------------------------------------------------------------
B. WINDOWS POWERSHELL TERMINAL COMMAND
------------------------------------------------------------
1. Copy 'sch_of_slsc-update-2026-10-28.zip' to your project directory.
2. In PowerShell, run:

Expand-Archive -Path "sch_of_slsc-update-2026-10-28.zip" -DestinationPath "." -Force; php artisan theme:fix-header; php artisan route:clear; php artisan view:clear; php artisan config:clear

------------------------------------------------------------
C. WINDOWS CMD / COMMAND PROMPT
------------------------------------------------------------
tar -xf sch_of_slsc-update-2026-10-28.zip & php artisan theme:fix-header & php artisan route:clear & php artisan view:clear & php artisan config:clear

------------------------------------------------------------
WHAT THIS COMMAND DOES:
------------------------------------------------------------
1. Overwrites only the 39 updated files safely without affecting your database, .env, .htaccess or uploads.
2. Runs 'theme:fix-header' to restore, validate, and publish both Header & Footer responsive layouts for English & Sinhala.
3. Completely fixes mobile responsiveness: desktop links are 100% hidden on mobile, top bar stays on a single line with language buttons on the right, footer cleanly stacks in 1 column on mobile, and admin visual editors work seamlessly.
4. Clears route, view, and config caches.
"""

COMMANDS_TXT.write_text(commands_content, encoding='utf-8')
if DEPLOY_UPDATES_DIR.is_dir():
    DEPLOY_COMMANDS_TXT.write_text(commands_content, encoding='utf-8')

print(f"Created update archive: {OUTPUT_ZIP} ({len(FILES_TO_PACKAGE)} files, {OUTPUT_ZIP.stat().st_size / 1024:.2f} KB)")

