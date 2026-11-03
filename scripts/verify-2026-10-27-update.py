"""Exercise the packaged deployment script on an isolated app fixture, never the live app."""
from hashlib import sha256
import json
import os
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile
from zipfile import ZipFile

ROOT = Path(__file__).resolve().parents[1]
ARCHIVE = ROOT / 'deploy/updates/sch_of_slsc-update-2026-10-27-v2.zip'
AUDITS = ROOT / 'deploy/audits'
BASH = Path(r'C:\Program Files\Git\bin\bash.exe')
assert BASH.is_file()
checks = []


def bash_path(path):
    value = Path(path).resolve().as_posix()
    return '/' + value[0].lower() + value[2:] if len(value) > 1 and value[1] == ':' else value


with tempfile.TemporaryDirectory(prefix='qa-site-20261027-', dir=AUDITS) as temporary:
    fixture = Path(temporary).resolve()
    assert fixture.is_relative_to(AUDITS.resolve())
    release = fixture / 'release'
    application = fixture / 'application with spaces'
    with ZipFile(ARCHIVE) as archive:
        assert archive.testzip() is None
        for name in archive.namelist():
            assert (release / name).resolve().is_relative_to(release.resolve())
        archive.extractall(release)
    manifest = json.loads((release / 'PATCH-MANIFEST.json').read_text())
    for relative in manifest['required_existing_files']:
        target = application / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes((ROOT / relative).read_bytes())
    before = {}
    for relative in manifest['changed_runtime_files']:
        old = subprocess.run(['git', 'show', 'HEAD:' + relative], cwd=ROOT, capture_output=True)
        if old.returncode == 0:
            target = application / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(old.stdout)
            before[relative] = old.stdout
    for directory in ['storage/app/public/uploads', 'storage/framework', 'vendor', 'bootstrap', 'public/build', 'database']:
        (application / directory).mkdir(parents=True, exist_ok=True)
    shutil.copyfile(ROOT / 'composer.json', application / 'composer.json')
    (application / '.env').write_text('APP_ENV=testing\nQA_RETAINED=keep\n')
    (application / 'database/retained.sqlite').write_bytes(b'Fixture database content, never opened by deployment.')
    (application / 'storage/app/public/uploads/retained.jpg').write_bytes(b'Fixture upload retained.')
    (application / 'public/build/manifest.json').write_text('{"old":"manifest"}\n')
    before['public/build/manifest.json'] = (application / 'public/build/manifest.json').read_bytes()
    (application / 'vendor/autoload.php').write_text('<?php return new class { public function isClassMapAuthoritative(): bool { return false; } };\n')
    (application / 'bootstrap/app.php').write_text('''<?php
return new class(dirname(__DIR__)) {
    public function __construct(private string $root) {}
    public function make(string $abstract): object { return new class { public function bootstrap(): void {} }; }
    public function isDownForMaintenance(): bool { return is_file($this->root.'/storage/framework/down'); }
};
''')
    before['bootstrap/app.php'] = (application / 'bootstrap/app.php').read_bytes()
    (application / 'artisan').write_text('''<?php
$command = $argv[1] ?? '';
file_put_contents(__DIR__.'/storage/framework/qa-commands.jsonl', json_encode(array_slice($argv, 1))."\\n", FILE_APPEND);
if ($command === 'down') file_put_contents(__DIR__.'/storage/framework/down', 'maintenance');
if ($command === 'up' && is_file(__DIR__.'/storage/framework/down')) unlink(__DIR__.'/storage/framework/down');
if ($command === 'view:clear' && is_file(__DIR__.'/storage/framework/qa-fail-view-clear')) exit(9);
if ($command === 'roles:setup' && is_file(__DIR__.'/storage/framework/qa-fail-roles-setup')) exit(10);
if ($command === 'migrate') {
    if (($argv[2] ?? '') !== '--path=database/migrations/2026_10_27_120000_add_enabled_locales_to_site_settings.php' || ($argv[3] ?? '') !== '--force' || ($argv[4] ?? '') !== '--no-interaction' || count($argv) !== 5) exit(13);
    if (is_file(__DIR__.'/storage/framework/qa-fail-migration')) exit(11);
}
if (!in_array($command, ['down', 'up', 'config:clear', 'route:clear', 'view:clear', 'migrate', 'roles:setup'], true)) exit(12);
''')
    protected = {path: (application / path).read_bytes() for path in ['.env', 'composer.json', 'database/retained.sqlite', 'storage/app/public/uploads/retained.jpg']}
    wrapper = fixture / 'qa-php.sh'
    wrapper.write_text('''#!/usr/bin/env bash
set -euo pipefail
if [[ "${1:-}" == '-r' && "${2:-}" == *'isDownForMaintenance'* ]]; then
    if [[ -f "$3/storage/framework/down" ]]; then printf down; else printf up; fi
else
    exec "$REAL_PHP" "$@"
fi
''', encoding='utf-8', newline='\n')
    wrapper.chmod(0o755)
    environment = os.environ.copy()
    environment['PATH'] = str(BASH.parent.parent / 'usr/bin') + os.pathsep + environment['PATH']
    environment['REAL_PHP'] = bash_path(shutil.which('php'))
    environment['PHP_BIN'] = bash_path(wrapper)
    environment['XDEBUG_MODE'] = 'off'

    def run(check=False, success=True, extra_environment=None):
        process = subprocess.run([str(BASH), bash_path(release / 'deploy.sh'), bash_path(application), *(['--check'] if check else [])],
                                 cwd=ROOT, env=environment | (extra_environment or {}), text=True,
                                 capture_output=True, timeout=180)
        if (process.returncode == 0) != success:
            raise AssertionError(f'Unexpected exit {process.returncode}:\n{process.stdout}\n{process.stderr}')
        return process.stdout + process.stderr

    print('Checking real Bash/PHP preflight without changes...', flush=True)
    assert 'checks passed' in run(check=True)
    assert all((application / path).read_bytes() == data for path, data in before.items())
    assert not (application / 'storage/app/deployment-backups').exists()
    checks.append('Real PHP/Bash preflight accepts prerequisites and changes no application files')

    print('Checking tampered ZIP payload rejection...', flush=True)
    target = release / 'payload/app/Services/NavigationEditorService.php'
    original = target.read_bytes()
    target.write_bytes(original + b'\n// tampered\n')
    run(check=True, success=False)
    target.write_bytes(original)
    assert all((application / path).read_bytes() == data for path, data in before.items())
    checks.append('Tampered payload fails before backup or copying')

    print('Checking missing authoritative Composer CLI rejection...', flush=True)
    autoload = application / 'vendor/autoload.php'
    original_autoload = autoload.read_bytes()
    autoload.write_bytes(original_autoload.replace(b'return false;', b'return true;'))
    assert 'Composer CLI is required' in run(check=True, success=False, extra_environment={'COMPOSER_BIN': 'qa-missing-composer-command'})
    autoload.write_bytes(original_autoload)
    checks.append('Authoritative autoload fails preflight when Composer CLI is absent')

    print('Checking installation, complete backup and data retention...', flush=True)
    output = run()
    assert 'Update complete' in output
    for relative, expected in manifest['files'].items():
        assert sha256((application / relative).read_bytes()).hexdigest() == expected['sha256'], relative
    assert all((application / path).read_bytes() == data for path, data in protected.items())
    assert not (application / 'storage/framework/down').exists()
    backups = sorted((application / 'storage/app/deployment-backups').glob('*/files-before-update.tar.gz'))
    assert len(backups) == 1
    with tarfile.open(backups[0], 'r:gz') as backup:
        for path, data in before.items():
            assert backup.extractfile(path).read() == data, path
    log = application / 'storage/framework/qa-commands.jsonl'
    commands = [json.loads(line)[0] for line in log.read_text().splitlines()]
    assert commands == ['down', 'config:clear', 'route:clear', 'view:clear', 'migrate', 'roles:setup', 'up'], commands
    checks.append(f'Copies all {len(manifest["files"])} payload files byte-for-byte and backs up original replaced files')
    checks.append('Preserves .env, database fixture, uploads, Composer settings and running-site maintenance state')
    checks.append('Runs only the single explicit additive language-settings migration plus maintenance/cache commands and missing-role setup; no imports, destructive reseeding or dependency installs')

    print('Checking pre-existing maintenance state...', flush=True)
    (application / 'storage/framework/down').write_text('original maintenance')
    log.write_text('')
    run()
    assert (application / 'storage/framework/down').read_text() == 'original maintenance'
    commands = [json.loads(line)[0] for line in log.read_text().splitlines()]
    assert commands == ['config:clear', 'route:clear', 'view:clear', 'migrate', 'roles:setup'], commands
    checks.append('An already-down site remains down after installation')

    print('Checking interrupted install maintenance and lock cleanup...', flush=True)
    (application / 'storage/framework/down').unlink()
    (application / 'storage/framework/qa-fail-view-clear').write_text('simulate a failed cache command')
    assert 'Update stopped' in run(success=False)
    assert (application / 'storage/framework/down').is_file()
    assert not (application / 'storage/app/deployment-backups' / (manifest['release'] + '.lock')).exists()
    assert all((application / path).read_bytes() == data for path, data in protected.items())
    checks.append('A failed cache command leaves maintenance enabled and releases the deployment lock')

    print('Checking missing-role setup failure and safe retry...', flush=True)
    (application / 'storage/framework/qa-fail-view-clear').unlink()
    (application / 'storage/framework/down').unlink()
    failure = application / 'storage/framework/qa-fail-roles-setup'
    failure.write_text('simulate failed role setup')
    log.write_text('')
    assert 'Update stopped' in run(success=False)
    assert (application / 'storage/framework/down').is_file()
    commands = [json.loads(line)[0] for line in log.read_text().splitlines()]
    assert commands == ['down', 'config:clear', 'route:clear', 'view:clear', 'migrate', 'roles:setup'], commands
    assert all((application / path).read_bytes() == data for path, data in protected.items())
    failure.unlink()
    log.write_text('')
    run()
    assert (application / 'storage/framework/down').is_file()
    commands = [json.loads(line)[0] for line in log.read_text().splitlines()]
    assert commands == ['config:clear', 'route:clear', 'view:clear', 'migrate', 'roles:setup'], commands
    checks.append('Failed role setup leaves maintenance on and supports a safe retry; existing data remains intact')

    print('Checking migration failure and exact migration scope...', flush=True)
    (application / 'storage/framework/down').unlink()
    failure = application / 'storage/framework/qa-fail-migration'
    failure.write_text('simulate a failed migration')
    log.write_text('')
    assert 'Update stopped' in run(success=False)
    assert (application / 'storage/framework/down').is_file()
    commands = [json.loads(line)[0] for line in log.read_text().splitlines()]
    assert commands == ['down', 'config:clear', 'route:clear', 'view:clear', 'migrate'], commands
    assert all((application / path).read_bytes() == data for path, data in protected.items())
    failure.unlink()
    log.write_text('')
    run()
    assert (application / 'storage/framework/down').is_file()
    migration_calls = [json.loads(line) for line in log.read_text().splitlines() if json.loads(line)[0] == 'migrate']
    assert migration_calls == [['migrate', '--path=database/migrations/2026_10_27_120000_add_enabled_locales_to_site_settings.php', '--force', '--no-interaction']]
    checks.append('Migration is restricted to the included file; migration failure keeps maintenance enabled and a retry does not reset data')

    report = {
        'release': manifest['release'], 'result': 'passed', 'checks': checks,
        'payload_sha256': manifest['payload_sha256'],
        'installer_sha256': sha256((release / 'deploy.sh').read_bytes()).hexdigest(),
        'scope': 'Real Git Bash and PHP syntax/preflight on isolated filesystem fixtures, including a project path with spaces. Maintenance detection and Artisan are simulated to avoid any hosting or local database access.',
    }
    (AUDITS / '2026-10-27-v2-package-validation.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps({'result': 'passed', 'checks': len(checks)}, indent=2), flush=True)
