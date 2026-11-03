"""Package reviewed runtime changes and prebuilt assets without runtime data or secrets."""
from datetime import datetime, timedelta, timezone
from hashlib import sha256
import json
from pathlib import Path, PurePosixPath
import posixpath
import re
import subprocess
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

ROOT = Path(__file__).resolve().parents[1]
AUDITS = ROOT / 'deploy/audits'
RELEASE = '2026-10-27-site-update-v2'
STAGE = ROOT / 'deploy/updates' / RELEASE
ARCHIVE = STAGE.parent / 'sch_of_slsc-update-2026-10-27-v2.zip'
MIGRATION = 'database/migrations/2026_10_27_120000_add_enabled_locales_to_site_settings.php'
CREATED = datetime.now(timezone(timedelta(hours=5, minutes=30)))


def digest(data):
    return sha256(data).hexdigest()


source_files = json.loads((AUDITS / '2026-10-27-runtime-scope.json').read_text())
assert len(source_files) == 77
assert [p for p in source_files if p.startswith('database/migrations/')] == [MIGRATION]
qa = json.loads((AUDITS / '2026-10-27-application-validation.json').read_text())
assert qa['result'] == 'passed'
assert qa['runtime_source_sha256'] == {p: digest((ROOT / p).read_bytes()) for p in source_files}, 'Repeat QA after source changes.'
assert qa['build_manifest_sha256'] == digest((ROOT / 'public/build/manifest.json').read_bytes())
previous = json.loads((ROOT / 'deploy/updates/2026-10-26-roles-and-site-update-v5/PATCH-MANIFEST.json').read_text())
assert set(previous['changed_runtime_files']).issubset(source_files), 'Cumulative package is missing a prior update dependency.'
payload = {}


def add(relative):
    source = ROOT / relative
    assert source.is_file() and not source.is_symlink() and source.resolve().is_relative_to(ROOT), relative
    assert not any(part.startswith('.') for part in PurePosixPath(relative).parts), relative
    assert not relative.startswith(('storage/', 'vendor/', 'node_modules/', 'public/storage/', 'bootstrap/cache/'))
    payload[relative] = source.read_bytes()


for relative in source_files:
    add(relative)
for source in (ROOT / 'public/build').rglob('*'):
    if source.is_file():
        add(source.relative_to(ROOT).as_posix())
for source in (ROOT / 'themes/school-of-signals/assets').rglob('*'):
    if source.is_file():
        relative = source.relative_to(ROOT).as_posix()
        add(relative)
        add('public/' + relative)
        assert payload[relative] == payload['public/' + relative], 'Theme assets need publishing: ' + relative

assert not any(p.endswith(('.env', '.sqlite', '.sql', '.log')) for p in payload)
for name in ('composer.json', 'composer.lock', 'package.json', 'package-lock.json'):
    assert json.loads((ROOT / name).read_bytes()) == json.loads(subprocess.check_output(['git', 'show', 'HEAD:' + name], cwd=ROOT))
for item in json.loads(payload['public/build/manifest.json']).values():
    for dependency in [item['file'], *item.get('css', []), *item.get('assets', [])]:
        assert 'public/build/' + dependency in payload
for relative, data in payload.items():
    if not relative.endswith('.css'):
        continue
    for dependency in re.findall(r'url\(([^)]+)\)', data.decode('utf-8')):
        dependency = dependency.strip(' \"\'')
        if dependency.startswith(('data:', '#')) or '://' in dependency:
            continue
        dependency = dependency.split('?', 1)[0].split('#', 1)[0]
        target = ('public/' + dependency.lstrip('/')) if dependency.startswith('/') else posixpath.normpath(posixpath.join(posixpath.dirname(relative), dependency))
        assert target in payload, f'Missing asset: {relative} -> {target}'

files = {p: {'bytes': len(data), 'sha256': digest(data)} for p, data in sorted(payload.items())}
payload_digest = digest(json.dumps(files, sort_keys=True).encode())
installer = (ROOT / 'scripts/deploy-2026-10-27-update.sh').read_bytes().replace(b'\r\n', b'\n')
composer = json.loads((ROOT / 'composer.json').read_bytes())
commands = '''# Upload the ZIP to the existing Laravel project root containing artisan.
# Run these commands from that directory, as the application owner.
unzip sch_of_slsc-update-2026-10-27-v2.zip -d update-20261027-v2
bash update-20261027-v2/deploy.sh "$PWD" --check &&
bash update-20261027-v2/deploy.sh "$PWD"

# Use a fresh extraction directory if update-20261027-v2 already exists.
# The installer backs up replaced files, clears config/route/view caches,
# runs ONLY the included language-settings migration, and runs roles:setup.
# No db:seed, user/password changes, database imports or server npm build.
# Existing role grants, users, content, uploads and .env are retained.
# Refresh the browser with Ctrl+F5 after a successful installation.
'''
manifest = {
    'release': RELEASE, 'project': 'sch_of_slsc', 'created_at': CREATED.isoformat(timespec='seconds'),
    'baseline': 'Existing application with the 23 October latest update; cumulative with the 26 October v5 roles/navbar/media update.',
    'scope': ['Visual footer columns, blocks, appearance, shared HTML/CSS and responsive preview',
              'Complete header preview with positioned dropdowns and expanded mobile menus',
              'Site language selection; English/Sinhala defaults and optional Tamil',
              'Friendly multilingual browser error pages with preserved HTTP status and JSON behavior',
              'User account deletion with permission/owner protections and retained content',
              'Own password change for Super Administrators and other admins',
              'Prior roles/navbar/media/news-upload fixes and complete compiled/theme assets'],
    'changed_runtime_files': source_files, 'files': files, 'payload_sha256': payload_digest,
    'composer_compatibility': {key: composer[key] for key in ('require', 'autoload')},
    'required_existing_files': previous['required_existing_files'] + [
        'app/Models/SiteSetting.php', 'app/Models/AuditLog.php',
        'app/Http/Middleware/EnsureSiteIsPublic.php', 'app/Http/Middleware/EnsureUserIsActive.php',
        'app/Livewire/Actions/Logout.php', 'app/Concerns/PasswordValidationRules.php',
    ],
    'requires_migrations': True, 'migration_paths': [MIGRATION],
    'requires_npm_on_server': False, 'requires_dependency_install': False,
    'database_content_included': False, 'uploads_included': False,
    'database_action': 'One nullable enabled_locales JSON column; roles:setup creates only missing recommended roles and permissions. Existing grants and user assignments are retained.',
    'preserved': ['.env', 'database content', 'users/passwords', 'existing role grants and assignments', 'uploads', 'hosting .htaccess', 'composer.json', 'composer.lock', 'unlisted files'],
    'replacement_policy': 'Back up original bytes, then replace only listed paths. No automatic file deletions.',
}
validation = {'application_checks': qa, 'package_checks': [
    'All 26 October v5 runtime dependencies included in the cumulative scope',
    'Build manifest/CSS dependencies present; theme source and public assets identical',
    'Runtime data, credentials, .env, uploads, vendor and node_modules excluded',
    'Unique safe ZIP paths, CRC and SHA-256 checksums verified',
]}
package_qa_path = AUDITS / '2026-10-27-v2-package-validation.json'
if package_qa_path.is_file():
    package_qa = json.loads(package_qa_path.read_text())
    assert package_qa['result'] == 'passed'
    assert package_qa['payload_sha256'] == payload_digest and package_qa['installer_sha256'] == digest(installer), 'Repeat installer QA.'
    validation['installer_checks'] = package_qa

readme = f'''# Server update — 27 October 2026

This v2 ZIP fixes clipped header dropdown previews and includes today's footer editor, site languages, friendly errors, user deletion and own-password
change, together with the 26 October v5 role/navbar/media/upload dependencies. It targets the existing application
with the 23 October latest update and also works for hosts already on 26 October v5.

## Install

Upload the ZIP into the existing Laravel project directory containing `artisan`, not inside `public`.
Run RUN-COMMANDS.txt from that directory as the application owner. Use a fresh extraction directory.
The --check command verifies package integrity, safe paths, PHP syntax, prerequisites and Composer compatibility
without copying application files or running a migration. The actual installer:

1. Creates a private backup of all replaced files under storage/app/deployment-backups.
2. Temporarily enables maintenance if the site was running.
3. Copies only the {len(files)} listed files and verifies their hashes.
4. Refreshes an authoritative Composer autoload map when required, with no dependency install or scripts.
5. Clears config, route and view caches.
6. Runs only `{MIGRATION}` using `php artisan migrate --path=... --force`.
7. Runs `php artisan roles:setup` to create missing recommended roles/permissions without resetting grants.
8. Returns the site to its original maintenance state.

PHP CLI 8.3+, Bash, unzip, tar, sha256sum, realpath and writable Laravel storage are required. Authoritative
Composer class maps need the existing Composer CLI. There is no server npm build or Composer install.

## Use the changes

- Header & Footer → Footer → Visual: columns, text, links, CMS blocks, appearance, desktop/mobile preview.
- Site Settings → Site Languages: English and Sinhala initially; check Tamil and save when needed.
- Users → Delete user: users.delete is required. Existing content is kept; own/last active owner accounts are protected.
- My Account → Change Password, or Password in the admin header: change your own password with your current password.
- Unknown Laravel URLs and 4xx/5xx exceptions display friendly error screens in English, Sinhala or Tamil.

FOOTER-EDITOR.md, SITE-LANGUAGES.md, USER-ROLES.md and ERROR-PAGES.md provide usage details.
Saved headers, footers, language preferences, users and passwords come from the hosting database. This ZIP
does not export local database records. Existing saved language preferences are retained. Until settings are saved,
the school theme defaults to English/Sinhala. No users are deleted and no passwords are changed by installing code.

## Data and validation

The only schema change adds a nullable JSON column. No SQL import, destructive reseeding, role reset, upload
replacement or file cleanup runs. .env, databases, sessions, logs, uploads, vendor, node_modules and hosting
.htaccess files are excluded. The backup contains code/assets only; the installer does not export a database backup.

VALIDATION.json records the actual local application and isolated installer checks. Browser screenshots and the
hosting PHP/FPM/OPcache environment are outside that validation. After successful installation, use Ctrl+F5 and
check Footer → Visual, Site Languages, the user Delete action, Change Password and an intentionally missing URL.
If the host disables OPcache timestamp validation, reload PHP through the hosting panel after replacing code.

## If installation stops

Keep the private backup path printed by deploy.sh. A failure after copying leaves maintenance enabled when
the installer enabled it and releases the deployment lock. Correct the cause and rerun the installer. If it was
already in maintenance before a retry, it stays in maintenance; run `php artisan up` only after a successful retry
and only if the site should be online.

To restore the original code, replace the backup path with the exact printed path:

```bash
php artisan down --retry=60
tar -xzf /absolute/printed/backup/files-before-update.tar.gz -C "$PWD"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan up
```

Only run the final up if the site was running before installation. Restore before making later code edits.
The added nullable column can remain when restoring old code; no database rollback or user reassignment is needed.
New files can remain unused after restoring the original entry points. Existing hashed assets are never deleted.
'''
metadata = {
    'README.md': readme.encode(), 'RUN-COMMANDS.txt': commands.encode(), 'deploy.sh': installer,
    'FILES.txt': ('\n'.join(files) + '\n').encode(),
    'FILES.sha256': ''.join(f'{row["sha256"]}  {p}\n' for p, row in files.items()).encode(),
    'PATCH-MANIFEST.json': (json.dumps(manifest, indent=2) + '\n').encode(),
    'VALIDATION.json': (json.dumps(validation, indent=2) + '\n').encode(),
}
for filename, source in [('FOOTER-EDITOR.md', 'footer-editor.md'), ('SITE-LANGUAGES.md', 'site-languages.md'),
                         ('USER-ROLES.md', 'user-roles.md'), ('ERROR-PAGES.md', 'error-pages.md'), ('NAVIGATION-EDITOR.md', 'navigation-editor.md')]:
    metadata[filename] = (ROOT / 'docs' / source).read_bytes()
entries = {'payload/' + p: data for p, data in sorted(payload.items())} | metadata
entries['PACKAGE.sha256'] = ''.join(f'{digest(data)}  {p}\n' for p, data in sorted(entries.items())).encode()
STAGE.mkdir(parents=True, exist_ok=True)
for path, data in entries.items():
    target = STAGE / path
    assert target.resolve().is_relative_to(STAGE.resolve())
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_bytes(data)
assert {p.relative_to(STAGE).as_posix() for p in STAGE.rglob('*') if p.is_file()} == set(entries)
with ZipFile(ARCHIVE, 'w', ZIP_DEFLATED, compresslevel=9) as archive:
    for path, data in sorted(entries.items()):
        info = ZipInfo(path, CREATED.timetuple()[:6])
        info.create_system = 3
        info.external_attr = (0o100755 if path == 'deploy.sh' else 0o100644) << 16
        info.compress_type = ZIP_DEFLATED
        archive.writestr(info, data)
with ZipFile(ARCHIVE) as archive:
    assert archive.testzip() is None and len(archive.namelist()) == len(set(archive.namelist())) == len(entries)
    for path, data in entries.items():
        assert not path.startswith('/') and '..' not in PurePosixPath(path).parts and '\\' not in path
        assert archive.read(path) == data
ARCHIVE.with_suffix('.zip.sha256').write_text(digest(ARCHIVE.read_bytes()) + '  ' + ARCHIVE.name + '\n', encoding='ascii')
ARCHIVE.with_name(ARCHIVE.stem + '-COMMANDS.txt').write_text(commands, encoding='utf-8', newline='\n')
print(json.dumps({'archive': str(ARCHIVE), 'bytes': ARCHIVE.stat().st_size, 'payload_files': len(files),
                  'runtime_source_files': len(source_files), 'sha256': digest(ARCHIVE.read_bytes()),
                  'installer_qa': 'passed' if 'installer_checks' in validation else 'pending'}, indent=2))
