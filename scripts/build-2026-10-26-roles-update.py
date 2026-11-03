"""Build the cumulative v5 hosting update from reviewed code and validated assets."""
from datetime import datetime, timedelta, timezone
from hashlib import sha256
import json
from pathlib import Path, PurePosixPath
import posixpath
import re
import subprocess
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

ROOT = Path(__file__).resolve().parents[1]
RELEASE = '2026-10-26-roles-and-site-update-v5'
STAGE = ROOT / 'deploy/updates' / RELEASE
ARCHIVE = STAGE.parent / 'sch_of_slsc-update-2026-10-26-v5.zip'
APP_QA = ROOT / 'deploy/audits/2026-10-26-v5-application-validation.json'
INSTALLER_QA = ROOT / 'deploy/audits/2026-10-26-v5-package-validation.json'
CREATED = datetime.now(timezone(timedelta(hours=5, minutes=30)))


def digest(data):
    return sha256(data).hexdigest()


def git_paths(*arguments):
    return set(filter(None, subprocess.check_output(['git', *arguments, '-z'], cwd=ROOT).decode().split('\0')))


changed = git_paths('diff', '--name-only', 'HEAD') | git_paths('ls-files', '--others', '--exclude-standard')
source_files = sorted(path for path in changed if path.startswith(('app/', 'resources/', 'themes/', 'database/seeders/')))
assert len(source_files) == 34, f'Review runtime scope before packaging: {source_files}'
assert [path for path in source_files if path.startswith('database/')] == ['database/seeders/RolesAndPermissionsSeeder.php']
qa = json.loads(APP_QA.read_text())
assert qa['result'] == 'passed'
assert qa['runtime_source_sha256'] == {path: digest((ROOT / path).read_bytes()) for path in source_files}, 'Repeat application QA after source changes.'
assert qa['build_manifest_sha256'] == digest((ROOT / 'public/build/manifest.json').read_bytes()), 'Build changed since QA.'
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
        assert payload['public/' + relative] == payload[relative], 'Publish theme assets before packaging: ' + relative

assert not any(path.endswith(('.sqlite', '.sql', '.env', '.log')) for path in payload)
for relative in ('composer.json', 'composer.lock', 'package.json', 'package-lock.json'):
    assert json.loads((ROOT / relative).read_bytes()) == json.loads(subprocess.check_output(['git', 'show', 'HEAD:' + relative], cwd=ROOT)), relative
build = json.loads(payload['public/build/manifest.json'])
for item in build.values():
    for dependency in [item['file'], *item.get('css', []), *item.get('assets', [])]:
        assert 'public/build/' + dependency in payload, dependency
for relative, data in payload.items():
    if not relative.endswith('.css'):
        continue
    for dependency in re.findall(r'url\(([^)]+)\)', data.decode('utf-8')):
        dependency = dependency.strip(' \"\'')
        if dependency.startswith(('data:', '#')) or '://' in dependency:
            continue
        dependency = dependency.split('?', 1)[0].split('#', 1)[0]
        target = ('public/' + dependency.lstrip('/')) if dependency.startswith('/') else posixpath.normpath(posixpath.join(posixpath.dirname(relative), dependency))
        assert target in payload, f'Missing CSS dependency: {relative} -> {target}'

files = {path: {'bytes': len(data), 'sha256': digest(data)} for path, data in sorted(payload.items())}
payload_digest = digest(json.dumps(files, sort_keys=True).encode())
installer = (ROOT / 'scripts/deploy-roles-update.sh').read_bytes().replace(b'\r\n', b'\n')
composer = json.loads((ROOT / 'composer.json').read_bytes())
commands = '''# Run from the existing Laravel project root containing artisan.
cd /var/www/html/sch_of_slsc
unzip sch_of_slsc-update-2026-10-26-v5.zip -d update-20261026-v5
bash update-20261026-v5/deploy.sh "$PWD" --check &&
bash update-20261026-v5/deploy.sh "$PWD"

# deploy.sh already runs roles:setup. It only creates missing recommended roles.
# Existing users, custom role permissions, assignments, uploads and .env are retained.
# If update-20261026-v5 exists, extract to a fresh directory and adjust the paths.
# Optional read-only upload diagnosis:
# php update-20261026-v5/diagnose.php "$PWD"
'''
manifest = {
    'release': RELEASE, 'project': 'sch_of_slsc', 'created_at': CREATED.isoformat(timespec='seconds'),
    'baseline': 'Existing application with the 23 October latest update; compatible with 26 October v1-v4',
    'scope': ['Six recommended roles, role templates, friendly permission management and role access previews',
              'Missing-role setup preserving existing grants and user assignments',
              'Media directory thumbnails', 'Connected visual and HTML/CSS navbar editor with Font Awesome',
              'Public mobile navigation and preview fixes', 'News image upload preparation improvements',
              'UTF-8-safe Livewire response encoding and CLI upload diagnosis', 'Complete compiled frontend and theme assets'],
    'changed_runtime_files': source_files, 'files': files, 'payload_sha256': payload_digest,
    'composer_compatibility': {key: composer[key] for key in ('require', 'autoload')},
    'required_existing_files': [
        'app/Enums/ThemeLayoutLocale.php', 'app/Enums/ThemeLayoutRegion.php', 'app/Models/ThemeLayout.php',
        'app/Models/ThemeLayoutRevision.php', 'app/Models/Menu.php', 'app/Models/Page.php', 'app/Models/User.php',
        'app/Services/ThemeLayoutService.php', 'app/Services/ThemeLayoutSanitizer.php',
        'app/Services/ThemeManager.php', 'app/Services/MediaUrlService.php', 'app/Services/AuditLogger.php',
        'app/Livewire/Admin/Users/UserCreate.php', 'app/Livewire/Admin/Users/UserEdit.php',
        'config/permission.php', 'resources/views/components/layouts/admin.blade.php',
    ],
    'requires_migrations': False, 'requires_npm_on_server': False, 'requires_dependency_install': False,
    'database_content_included': False, 'uploads_included': False,
    'database_action': 'roles:setup registers missing permissions and creates only missing recommended roles; it never resets existing roles or reassigns users',
    'preserved': ['.env', 'existing database content', 'existing role grants and user assignments', 'uploads',
                  'hosting .htaccess', 'composer.json', 'composer.lock', 'unlisted files'],
    'replacement_policy': 'Back up original bytes, then replace only listed paths. No automatic file deletions.',
}
validation = {'application_checks': qa, 'package_checks': [
    'Manifest and CSS dependencies present', 'Source/published theme assets identical',
    'Runtime data and secrets excluded', 'ZIP CRC, safe unique paths and SHA-256 verified',
]}
if INSTALLER_QA.is_file():
    installer_qa = json.loads(INSTALLER_QA.read_text())
    assert installer_qa['result'] == 'passed'
    assert installer_qa['payload_sha256'] == payload_digest and installer_qa['installer_sha256'] == digest(installer), 'Repeat installer QA.'
    validation['installer_checks'] = installer_qa

readme = f'''# Hosting update v5 - 26 October 2026

This cumulative update includes today's media/navbar/news-upload changes and the new user-friendly role management.
It is suitable for the existing application with the 23 October latest update, including hosts already on v4.
The package replaces {len(files)} code/asset files. Inspect FILES.txt for the exact list.

## Install

Upload the ZIP to the Laravel root /var/www/html/sch_of_slsc (the directory containing artisan).
Run RUN-COMMANDS.txt as the application owner. Use a fresh extraction directory.
The --check step verifies hashes, PHP syntax, prerequisites and Composer compatibility without replacing files.
The installer backs up overwritten files under storage/app/deployment-backups, temporarily enables maintenance
if the website was running, copies only listed files, verifies them, refreshes an authoritative Composer map
when needed, clears config/route/view caches, runs roles:setup and restores the original maintenance state.

PHP CLI 8.3+, Bash, tar, sha256sum, realpath and writable application storage are required.
An authoritative Composer class map requires the existing Composer CLI. No dependency installs or server npm builds run.

## Roles

Open Admin -> Roles & Permissions. Use Users -> Create/Edit User -> Primary role to assign access.
Recommended roles: Super Administrator, Site Administrator, Publisher, Content Editor, Media Operator and Auditor.
See USER-ROLES.md for the access matrix and template workflow.
The installer runs `php artisan roles:setup` automatically. It creates missing recommended roles and permissions only.
Existing custom roles, grants, direct user permissions and user assignments stay intact. It creates no users/passwords.
Do not run the general role seeder on an existing customized installation; that explicitly restores default grants.

## Preserved data and scope

No migrations, SQL imports, destructive reseeding or file deletions run. The archive excludes .env, databases,
uploads, vendor, node_modules, sessions, runtime caches and logs. Saved news/pages/header-footer content stays
in the hosting database. Local database content is not exported. Existing server edits to listed code paths
are backed up before replacement; unlisted files and older hashed browser assets are retained.

## QA and checks after installation

VALIDATION.json contains the application and isolated installer checks. Local automated checks do not verify
the hosting PHP/FPM/OPcache environment or an authenticated browser session; browser visual QA was unavailable.
Refresh with Ctrl+F5, review the role access preview and permission editor, test one lowercase news image,
and check media thumbnails and public mobile navigation. The UTF-8 guard changes response encoding only;
it does not rewrite article text, signed snapshots or stored images. NAVIGATION-EDITOR.md covers navbar editing.
For the existing upload diagnostic, run `php update-20261026-v5/diagnose.php "$PWD"` (CLI-only/read-only).
CLI settings can differ from the web process. The original producer of hosting-invalid bytes is not established.

## Failure and code recovery

Keep the printed private backup directory. It contains files-before-update.tar.gz, existing-files.txt and new-files.txt.
A failure after copying starts leaves maintenance enabled if the installer enabled it, and releases its lock.
Correct the failure and rerun the installer; missing-role setup is safe to repeat. A site already down stays down.
To restore code using the exact backup path printed for this installation:

```bash
cd /var/www/html/sch_of_slsc
php artisan down --retry=60
tar -xzf /absolute/printed/backup/files-before-update.tar.gz -C "$PWD"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan up
```

Use the final up only if the site was running before deployment. Restore before later code edits; the archive
restores the original listed files and any backed-up authoritative Composer metadata. New files can remain unused
after restoring the original entry points. Newly created roles/permissions are retained; there is no automatic
database rollback or user reassignment. The installer never deletes uploads or database content.
'''
metadata = {
    'README.md': readme.encode(), 'USER-ROLES.md': (ROOT / 'docs/user-roles.md').read_bytes(),
    'NAVIGATION-EDITOR.md': (ROOT / 'docs/navigation-editor.md').read_bytes(),
    'deploy.sh': installer, 'RUN-COMMANDS.txt': commands.encode(),
    'diagnose.php': (ROOT / 'scripts/diagnose-news-upload.php').read_bytes().replace(b'\r\n', b'\n'),
    'FILES.txt': ('\n'.join(files) + '\n').encode(),
    'FILES.sha256': ''.join(f'{row["sha256"]}  {path}\n' for path, row in files.items()).encode(),
    'PATCH-MANIFEST.json': (json.dumps(manifest, indent=2) + '\n').encode(),
    'VALIDATION.json': (json.dumps(validation, indent=2) + '\n').encode(),
}
entries = {'payload/' + path: data for path, data in sorted(payload.items())} | metadata
entries['PACKAGE.sha256'] = ''.join(f'{digest(data)}  {path}\n' for path, data in sorted(entries.items())).encode()
STAGE.mkdir(parents=True, exist_ok=True)
for path, data in entries.items():
    target = STAGE / path
    assert target.resolve().is_relative_to(STAGE.resolve())
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_bytes(data)
assert {path.relative_to(STAGE).as_posix() for path in STAGE.rglob('*') if path.is_file()} == set(entries)
with ZipFile(ARCHIVE, 'w', ZIP_DEFLATED, compresslevel=9) as archive:
    for path, data in sorted(entries.items()):
        info = ZipInfo(path, CREATED.timetuple()[:6])
        info.create_system = 3
        info.external_attr = (0o100755 if path == 'deploy.sh' else 0o100644) << 16
        info.compress_type = ZIP_DEFLATED
        archive.writestr(info, data)
with ZipFile(ARCHIVE) as archive:
    assert archive.testzip() is None
    assert len(archive.namelist()) == len(set(archive.namelist())) == len(entries)
    for path, data in entries.items():
        assert not path.startswith('/') and '..' not in PurePosixPath(path).parts and '\\' not in path
        assert archive.read(path) == data
ARCHIVE.with_suffix('.zip.sha256').write_text(digest(ARCHIVE.read_bytes()) + '  ' + ARCHIVE.name + '\n', encoding='ascii')
ARCHIVE.with_name(ARCHIVE.stem + '-COMMANDS.txt').write_text(commands, encoding='utf-8', newline='\n')
print(json.dumps({'archive': str(ARCHIVE), 'bytes': ARCHIVE.stat().st_size, 'payload_files': len(files),
                  'changed_runtime_files': len(source_files), 'sha256': digest(ARCHIVE.read_bytes()),
                  'installer_qa': 'passed' if 'installer_checks' in validation else 'pending'}, indent=2))
