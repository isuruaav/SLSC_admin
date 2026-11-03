"""Package today's media/navigation changes and complete browser assets for replacement."""
from datetime import datetime, timedelta, timezone
from hashlib import sha256
import json
from pathlib import Path, PurePosixPath
import posixpath
import re
import subprocess
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

ROOT = Path(__file__).resolve().parents[1]
RELEASE = '2026-10-26-media-navigation-v4'
STAGE = ROOT / 'deploy/updates' / RELEASE
ARCHIVE = STAGE.parent / 'sch_of_slsc-update-2026-10-26-v4.zip'
QA_REPORT = ROOT / 'deploy/audits/2026-10-26-v4-package-validation.json'
CREATED = datetime.now(timezone(timedelta(hours=5, minutes=30)))


def digest(data):
    return sha256(data).hexdigest()


def git_paths(*arguments):
    return set(filter(None, subprocess.check_output(['git', *arguments, '-z'], cwd=ROOT).decode().split('\0')))


changed = git_paths('diff', '--name-only', 'HEAD') | git_paths('ls-files', '--others', '--exclude-standard')
source_files = sorted(path for path in changed if path.startswith(('app/', 'resources/', 'themes/')))
assert len(source_files) == 23, f'Review changed runtime scope before packaging: {source_files}'
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
        public_relative = 'public/' + relative
        add(public_relative)
        assert payload[public_relative] == payload[relative], 'Publish the current theme assets first: ' + relative

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
installer = (ROOT / 'scripts/deploy-code-update.sh').read_bytes().replace(b'\r\n', b'\n')
composer = json.loads((ROOT / 'composer.json').read_text())
commands = '''# Upload this ZIP to the existing Laravel project root (the folder containing artisan).
cd /var/www/html/sch_of_slsc
unzip sch_of_slsc-update-2026-10-26-v4.zip -d update-20261026-v4
# Read-only diagnosis BEFORE replacement; keep the JSON output if hosting still fails.
php update-20261026-v4/diagnose.php "$PWD"
bash update-20261026-v4/deploy.sh "$PWD" --check &&
bash update-20261026-v4/deploy.sh "$PWD"
# Verify the installed guard and collect the sanitized exception trace.
php update-20261026-v4/diagnose.php "$PWD"

# If update-20261026-v4 already exists, use a fresh extraction directory and adjust both paths.
# Use the application owner account. Keep the code backup path printed by the installer.
'''
manifest = {
    'release': RELEASE, 'project': 'sch_of_slsc', 'created_at': CREATED.isoformat(timespec='seconds'),
    'baseline': 'Existing application with the 23 October 2026 latest update installed',
    'scope': ['Media directory thumbnails', 'Connected visual and HTML/CSS navigation editor',
              'Font Awesome icon picker and default mobile navigation', 'Font/logo/viewport/scroll preview fixes',
              'Keyboard and active-link improvements for public navigation',
              'Recover malformed UTF-8 in Livewire display output without modifying signed snapshots or database content',
              'News image preparation progress, early validation, failed/stalled request recovery, correct Sortable bindings',
              'CLI-only hosting upload/encoding diagnostic',
              'UTF-8-safe final HTTP JSON encoding on the standard Livewire update URL'],
    'changed_runtime_files': source_files, 'files': files, 'payload_sha256': payload_digest,
    'composer_compatibility': {key: composer[key] for key in ('require', 'autoload')},
    'required_existing_files': [
        'app/Enums/ThemeLayoutLocale.php', 'app/Enums/ThemeLayoutRegion.php', 'app/Models/ThemeLayout.php',
        'app/Models/ThemeLayoutRevision.php', 'app/Models/Menu.php', 'app/Models/Page.php',
        'app/Services/ThemeLayoutService.php', 'app/Services/ThemeLayoutSanitizer.php',
        'app/Services/ThemeManager.php', 'app/Services/MediaUrlService.php',
        'resources/views/components/layouts/admin.blade.php',
    ],
    'requires_migrations': False, 'requires_npm_on_server': False, 'requires_dependency_install': False,
    'database_content_included': False, 'uploads_included': False,
    'preserved': ['.env', 'database contents', 'uploads', 'hosting .htaccess', 'composer.json', 'composer.lock', 'unlisted files'],
    'replacement_policy': 'Only listed paths are replaced after backing up their original bytes. Server-specific edits to those paths are backed up, then replaced.',
}
validation = {
    'application_checks_from_this_session': {
        'php_feature_tests_passed': 73, 'frontend_tests_passed': 25,
        'alpine_binding_checks': 'passed', 'phpstan': 'passed', 'pint': 'passed', 'vite_build': 'passed',
        'browser_visual_check': 'unavailable in the session',
    },
    'upload_hotfix_checks': {
        'php_feature_tests_passed': 48, 'assertions': 269,
        'includes': ['English/Sinhala/Tamil news image uploads and generated variants',
                     'Same JsonResponse.php:91 failure reproduced with malformed rendered HTML',
                     'Malformed upload output now returns valid JSON after saving the image',
                     'Signed snapshots and valid UTF-8 remain byte-identical',
                     'News editing, translations, media uploads and navigation regressions'],
        'phpstan_changed_runtime_files': 'passed', 'pint_changed_php_files': 'passed',
        'hosting_runtime_validation': 'not available; invalid-byte source remains unconfirmed',
    },
    'upload_preparation_checks': {
        'php_feature_tests_passed': 50, 'assertions': 291,
        'frontend_tests_passed': 37,
        'real_alpine_create_and_edit_bindings': 'passed',
        'packaged_diagnostic_guard_and_image_pipeline_probe': 'passed locally',
        'vite_build': 'passed',
        'includes': ['Early filename, file type, size and count validation',
                     'Progress and server-confirmation states', 'File changes and saving blocked during preparation',
                     'HTTP/network error handling and explicit two-minute unconfirmed state',
                     'Late upload confirmation recovery', 'Correct pending-image reorder context and teardown'],
        'hosting_root_cause': 'Not confirmed. Run diagnose.php on the affected hosting runtime and share its JSON output.',
    },
    'response_boundary_checks': {
        'php_feature_tests_passed': 54, 'assertions': 305,
        'same_router_json_exception_reproduced_before_fix': True,
        'post_hook_and_object_output_encoded_safely': 'passed',
        'signed_snapshot_round_trip_and_tamper_rejection': 'passed',
        'web_and_livewire_header_middleware': 'passed', 'streamed_response_passthrough': 'passed',
        'isolated_route_cache_and_cached_endpoint': 'passed',
        'phpstan': 'passed', 'pint': 'passed',
        'hosting': 'Supplied trace confirms Router JSON encoding failure; origin of malformed bytes and installed hosting fix not yet verified.',
    },
    'package_checks': ['Complete Vite manifest dependencies', 'All relative CSS dependencies',
                       'Source/published theme asset byte equality', 'Sensitive/runtime data excluded',
                       'ZIP CRC, unique safe paths and SHA-256 verification'],
}
if QA_REPORT.is_file():
    qa = json.loads(QA_REPORT.read_text())
    assert qa['payload_sha256'] == payload_digest and qa['installer_sha256'] == digest(installer), 'Repeat installer QA after payload/installer changes.'
    validation['installer_checks'] = qa

readme = f'''# Server update v4 — 26 October 2026

අද කළ Media thumbnails, Visual/HTML navbar editor සහ Font Awesome වෙනස්කම් ඇතුළත් වේ.
ZIP එකේ payload/ ඇතුළත files, server project root එකේ ඒ ඒ paths වලට replace වේ.
Fresh compiled JavaScript/CSS, PDF.js support files, theme fonts/icons සහ default logo ඇතුළත්ය.

## News image upload hotfix

This v4 includes the full original 26 October update, the v2/v3 guards and preparation improvements,
and the final HTTP response encoding fix below.
The reported hosting error occurs when Laravel tries to encode malformed UTF-8 in a Livewire response.
Valid UTF-8 text is retained exactly. Only invalid display bytes in effects/assets are replaced with
the Unicode replacement character. Signed snapshots, article content and stored uploads are not rewritten.
The hosting source of those invalid bytes has not been confirmed; standard local uploads already pass.
The regression test reproduces JsonResponse.php:91 after an image is saved and verifies the response recovery.
Affected component names/counts are logged as "Repaired malformed UTF-8 in Livewire output."
under storage/logs/laravel.log, without recording filenames, article text or image bytes.
This is an application change; no vendor files or JSON response defaults are patched.

## Confirmed response boundary fix (v4)

The supplied hosting trace shows Laravel Router::toResponse constructing JsonResponse from the
Livewire result and failing at JsonResponse.php:91. The news form had rendered; this trace did not
show an article/media insert. An earlier response hook can miss output added by subsequent hooks
or serializable objects. A regression test reproduces this exact failure with the earlier guard active.

V4 registers a controller on the standard Livewire update URL using Livewire::setUpdateRoute.
It lets Livewire handle the request, then returns its final payload as JsonResponse with
JSON_INVALID_UTF8_SUBSTITUTE before the router attempts its default encoding. Valid UTF-8 and signed
snapshots remain intact; streamed responses pass through. Livewire retains its web/CSRF and required
header middleware. Snapshot tampering is still rejected. The route can be cached normally.
The exact producer of the invalid bytes remains unconfirmed; the demonstrated JSON encoding failure
is covered at the final HTTP boundary. Hosting installation/runtime still requires verification.

Use deploy.sh: route:clear is required so an older cached default route does not bypass the controller.
After install, diagnose.php should report utf8_update_endpoint_registered: true.

## Preparation / hosting diagnosis

The news forms now reject invalid names, formats, empty files, sizes and counts before uploading.
They show progress, server confirmation, transfer cancellation and clear error messages, and block
saving while the temporary files are being prepared. An unconfirmed operation shows an explicit
error after two minutes instead of an endless "Preparing" label. That deadline does not claim the
upload succeeded or delete files; late successful confirmation can still restore the ready state.
Create News also fixes invalid Sortable JavaScript syntax, reorder context and cleanup.

The exact hosting-only UTF-8 source remains unconfirmed. A loading-state fix alone does not establish
that the server exception is resolved. Run diagnose.php before and after installation using the commands
below. It is CLI-only and does not update the database or replace code/uploads. It reports source hashes/UTF-8,
compiled-view encoding, the active guard, Composer class-map status, upload settings, storage permissions
and recent sanitized exception stack frames. A tiny synthetic image also exercises the same
orientation/resize/WebP pipeline to expose hosting driver or format failures. It omits .env values,
passwords, SQL and exception arguments and does not read user images for this probe.
CLI PHP/OPcache settings can differ from the web PHP process; its report labels that limitation.

```bash
php update-20261026-v4/diagnose.php "$PWD"
```

If hosting still throws the exception, share that JSON report plus "Copy as Markdown" from the error.
Keep article text saved before refreshing a stalled upload. Check already attached images before retrying.

## Install

Use this delta on the existing application with the 23 October latest update already installed.
It can also be applied if the original 26 October package, v2 or v3 is already installed.
Upload the ZIP to /var/www/html/sch_of_slsc/ (or your existing Laravel root containing artisan).
Open RUN-COMMANDS.txt and run its commands as the application owner. Extract into a fresh directory.
The --check command checks package integrity, PHP syntax and application prerequisites without copying files.
The install command backs up overwritten files, temporarily enables maintenance if the site was up,
copies {len(payload)} files, refreshes an authoritative Composer class map when needed, clears config/route/view
caches, verifies copied hashes, and restores the previous maintenance state.

PHP CLI 8.3+, Bash, sha256sum, tar and normal writable Laravel storage are required.
If the server uses an authoritative Composer class map, Composer CLI must be available.
It runs dump-autoload only in that case; it does not install dependencies or access a package registry.
If your PHP command differs, run the commands with PHP_BIN=/path/to/php before bash.

## What is retained

No migrations, SQL imports, seeds, dependency installs or server npm builds are needed.
The ZIP does not contain .env, databases, uploads, vendor/, node_modules/ or runtime caches.
Saved pages, EN/SI header/footer drafts and published layouts remain in the server database.
Local-only database edits are not transferred. Existing paths listed in FILES.txt are replaced;
other server files and older hashed browser assets are retained. Review that list if the server
has additional code edits. The installer backs up the exact previous files before replacement.

## After install

Refresh Ctrl+F5. Check Media Library thumbnails; Header & Footer > Header > Visual / HTML / CSS;
Link settings > Link icon; desktop/tablet/mobile preview; saved draft versus Save & Publish;
and public mobile navigation. See NAVIGATION-EDITOR.md for editor usage.
Publishing a header from the editor remains a separate administrator action.
For News, refresh the edit screen and inspect its existing image list before retrying an upload:
an earlier failed response can occur after the files were saved. Try one new lowercase JPG first.
If the error remains, copy the full exception with "Copy as Markdown" and the matching Laravel log entry.

## Backup / restore

Keep the private backup directory printed by deploy.sh under storage/app/deployment-backups/.
It contains files-before-update.tar.gz, existing-files.txt and new-files.txt.
If deployment fails after copying starts, the installer leaves maintenance enabled when it enabled it.
To restore code immediately, use the exact backup path printed for this install:

```bash
cd /var/www/html/sch_of_slsc
php artisan down --retry=60
tar -xzf /absolute/printed/backup/files-before-update.tar.gz -C "$PWD"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan up
```

Run the final `up` only if the site was running before installation. Restore before making later code edits,
because tar restores the original listed files. New files from new-files.txt can remain unused after restoring
the old entry points; no database rollback is required. No files or directories are automatically deleted.
'''
metadata = {
    'README.md': readme.encode('utf-8'), 'NAVIGATION-EDITOR.md': (ROOT / 'docs/navigation-editor.md').read_bytes(),
    'deploy.sh': installer, 'RUN-COMMANDS.txt': commands.encode(),
    'diagnose.php': (ROOT / 'scripts/diagnose-news-upload.php').read_bytes().replace(b'\r\n', b'\n'),
    'FILES.txt': ('\n'.join(files) + '\n').encode(),
    'FILES.sha256': ''.join(f'{row["sha256"]}  {path}\n' for path, row in files.items()).encode(),
    'PATCH-MANIFEST.json': (json.dumps(manifest, indent=2, ensure_ascii=False) + '\n').encode('utf-8'),
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
