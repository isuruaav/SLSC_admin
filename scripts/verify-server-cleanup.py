"""Run deletion checks only inside a disposable, verified workspace fixture."""
from hashlib import sha256
import json
import os
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile

ROOT = Path(__file__).resolve().parents[1]
AUDITS = ROOT / 'deploy/audits'
BASH = Path(r'C:\Program Files\Git\bin\bash.exe')
SCRIPT = ROOT / 'scripts/cleanup-server-artifacts.sh'
ZIP = ROOT / 'deploy/updates/sch_of_slsc-update-2026-10-26-v4.zip'


def posix(path):
    value = Path(path).resolve().as_posix()
    return '/' + value[0].lower() + value[2:] if len(value) > 1 and value[1] == ':' else value


with tempfile.TemporaryDirectory(prefix='cleanup-qa-', dir=AUDITS) as temporary:
    fixture = Path(temporary).resolve()
    assert fixture.is_relative_to(AUDITS.resolve())
    app = fixture / 'application'
    protected = {
        'artisan': b'fixture artisan', 'composer.json': b'{}', 'vendor/autoload.php': b'<?php',
        '.env': b'PRESERVED=fixture', 'database/fixture.sqlite': b'fixture database',
        'storage/app/public/upload.jpg': b'fixture upload', 'node_modules/tailwindcss/index.js': b'compiler',
        'scripts/compile-leadership-profile.mjs': b'compiler entry', 'public/build/manifest.json': b'{}',
        'update-20261026-v4/deploy.sh': b'latest installer',
        'update-20261026-v4/PATCH-MANIFEST.json': b'{}', 'tests/fixture.php': b'fixture tests',
    }
    for relative, content in protected.items():
        target = app / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(content)
    shutil.copyfile(ZIP, app / ZIP.name)
    protected[ZIP.name] = ZIP.read_bytes()
    old = {
        'update-20261023/payload/old.txt': b'old release 1',
        'update-20261023-latest/payload/old.txt': b'old release 2',
        'update-20261023-latest-v2/payload/old.txt': b'old release 3',
        'update-20261026/payload/old.txt': b'old release 4',
        'sch_of_slsc-update-2026-10-26.zip': b'old zip',
    }
    odd_name = 'as $group => $records) {'
    if os.name != 'nt':
        old[odd_name] = b'Unknown root file: preserved verbatim in backup.\n'
    for relative, content in old.items():
        target = app / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(content)
    env = os.environ.copy()
    env['PATH'] = str(BASH.parent.parent / 'usr/bin') + os.pathsep + env['PATH']
    declaration = SCRIPT.read_text().split('CANDIDATES=(', 1)[1].split('\n)', 1)[0]
    p = subprocess.run([str(BASH), '-c', 'CANDIDATES=(' + declaration + '\n)\nprintf "%s\\n" "${CANDIDATES[@]}"'],
                       env=env | {'group': 'DO_NOT_EXPAND', 'records': 'DO_NOT_EXPAND'}, capture_output=True, text=True)
    assert p.returncode == 0 and odd_name in p.stdout.splitlines()

    def run(mode, expected=0, directory=app, environment=env):
        assert directory.resolve().is_relative_to(fixture)
        p = subprocess.run([str(BASH), posix(SCRIPT), posix(directory), mode], env=environment,
                           cwd=ROOT, capture_output=True, text=True, timeout=60)
        assert p.returncode == expected, p.stdout + p.stderr
        return p.stdout + p.stderr

    assert 'Preview only' in run('--check')
    assert all((app / path).read_bytes() == data for path, data in (old | protected).items())
    assert not (app / 'storage/app/cleanup-backups').exists()
    checks = ['Preview lists only exact candidates and changes no files']

    (app / ZIP.name).write_bytes(b'damaged latest zip')
    assert 'does not match' in run('--apply', expected=1)
    assert all((app / path).read_bytes() == data for path, data in old.items())
    (app / ZIP.name).write_bytes(protected[ZIP.name])
    checks.append('Invalid retained v4 ZIP blocks deletion')

    failure_env = fixture / 'simulate-tar-failure.sh'
    failure_env.write_text('tar() { return 93; }\n', newline='\n')
    run('--apply', expected=93, environment=env | {'BASH_ENV': posix(failure_env)})
    assert all((app / path).read_bytes() == data for path, data in old.items())
    assert not (app / 'storage/app/cleanup-backups/cleanup-20261026.lock').exists()
    checks.append('Backup failure prevents removal and releases the lock')

    output = run('--apply')
    assert 'Cleanup complete' in output
    assert all(not (app / path).exists() for path in old)
    assert all((app / path).read_bytes() == data for path, data in protected.items())
    backups = [path for path in (app / 'storage/app/cleanup-backups').glob('*/removed-artifacts.tar.gz') if path.stat().st_size > 0]
    assert len(backups) == 1
    with tarfile.open(backups[0], 'r:gz') as archive:
        for path, data in old.items():
            assert archive.extractfile(path).read() == data, path
    checks.append('Removes only named old releases after complete byte-exact backup')
    checks.append('Bash preserves the odd filename and dollar signs literally without variable expansion')
    checks.append('Retains v4, node_modules, scripts, source markers, tests, database, .env and uploads')
    assert 'Nothing to clean' in run('--apply')
    checks.append('Repeated cleanup is harmless')

    outside = fixture / 'outside'
    outside.mkdir()
    (outside / 'keep.txt').write_bytes(b'outside fixture content')
    try:
        os.symlink(outside, app / 'update-20261023', target_is_directory=True)
    except OSError:
        symlink_check = 'not available on this Windows account; script explicitly rejects target symlinks'
    else:
        assert 'symbolic-link cleanup target' in run('--check', expected=1)
        assert (outside / 'keep.txt').read_bytes() == b'outside fixture content'
        symlink_check = 'passed'
        checks.append('Rejects a target symlink and preserves its destination')

    report = {'result': 'passed', 'checks': checks, 'symlink_check': symlink_check,
              'odd_filename_filesystem_check': 'not supported on Windows because the filename contains >; Bash quoting verified separately' if os.name == 'nt' else 'passed',
              'script_sha256': sha256(SCRIPT.read_bytes().replace(b'\r\n', b'\n')).hexdigest(),
              'scope': 'Real Bash/tar/rm on disposable fixtures within deploy/audits; no hosting or real app files deleted.'}
    (AUDITS / '2026-10-26-server-cleanup-validation.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps(report, indent=2))
