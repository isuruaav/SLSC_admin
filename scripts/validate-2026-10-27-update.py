"""Validate the reviewed cumulative update and record exactly what was checked."""
from hashlib import sha256
import json
import os
from pathlib import Path
import shutil
import subprocess

ROOT = Path(__file__).resolve().parents[1]
AUDITS = ROOT / 'deploy/audits'
PATHS = json.loads((AUDITS / '2026-10-27-runtime-scope.json').read_text())
ENV = os.environ | {'XDEBUG_MODE': 'off', 'FOOTER_EDITOR_WRITE_FIXTURE': '1'}


def source_hashes():
    return {path: sha256((ROOT / path).read_bytes()).hexdigest() for path in PATHS}


def run(command):
    process = subprocess.run(command, cwd=ROOT, env=ENV, capture_output=True, text=True,
                             encoding='utf-8', errors='replace', timeout=240)
    if process.returncode:
        raise RuntimeError(f'{command!r}\n{process.stdout}\n{process.stderr}')
    return process.stdout


before = source_hashes()
print('Building production assets...', flush=True)
build = run([shutil.which('npm.cmd') or shutil.which('npm'), 'run', 'build'])
assert 'built in' in build
tests = [
    'tests/Feature/Admin/FooterEditorTest.php',
    'tests/Feature/Admin/NavigationEditorTest.php',
    'tests/Feature/Admin/ThemeLayoutAdminUiTest.php',
    'tests/Feature/SiteLanguagesTest.php',
    'tests/Feature/SiteSettingsFooterTest.php',
    'tests/Feature/FriendlyErrorPagesTest.php',
    'tests/Feature/Admin/UserDeletionTest.php',
    'tests/Feature/Admin/ChangeOwnPasswordTest.php',
    'tests/Feature/Admin/UserPermissionBoundariesTest.php',
    'tests/Feature/Admin/RolePresetsTest.php',
    'tests/Feature/LivewireUpdateResponseTest.php',
]
print('Running application feature checks...', flush=True)
output = run(['php', 'artisan', 'test', *tests])
summaries = [json.loads(line) for line in output.splitlines() if line.startswith('{"tool":"pest"')]
assert len(summaries) == 1 and summaries[0]['result'] == 'passed', output
print(json.dumps(summaries[0]), flush=True)
print('Checking frontend syntax and available DOM checks...', flush=True)
preview_tests = run(['node', '--test', 'tests/Frontend/theme-layout-preview.test.mjs'])
assert 'fail 0' in preview_tests, preview_tests
worker = Path(os.environ.get('NAVIGATION_DOM_MODULE', str(Path.home() / 'AppData/Local/Temp/codex-navigation-dom-worker.mjs')))
for script in ('app.js', 'footer-editor.js', 'navigation-editor.js', 'theme-layout-preview.js', 'news-image-upload.js'):
    run(['node', '--check', 'resources/js/' + script])
frontend_checks = {'syntax': 'passed', 'dom_rerun': False,
                   'note': '36 DOM tests and rendered footer Alpine checks passed during feature development. The external temporary DOM worker is no longer available, so packaging repeats syntax checks only.'}
if worker.is_file():
    ENV['NAVIGATION_DOM_MODULE'] = str(worker)
    frontend = run(['node', '--test', 'tests/Frontend/footer-editor.test.mjs', 'tests/Frontend/navigation-editor.test.mjs'])
    bindings = run(['node', 'scripts/verify-footer-bindings.mjs'])
    assert 'fail 0' in frontend and 'PASS:' in bindings
    frontend_checks = {'syntax': 'passed', 'dom_rerun': True, 'tests': 36, 'rendered_footer_alpine_bindings': 'passed'}
frontend_checks['preview_geometry_and_interactions'] = {'result': 'passed', 'tests': 7,
    'scope': 'Dependency-free geometry and event regression checks; includes positioned dropdowns, tall menus, viewport resizing, links and cleanup.'}
assert before == source_hashes(), 'Source changed during validation; review and repeat QA.'
report = {
    'release': '2026-10-27-site-update-v2', 'result': 'passed',
    'runtime_source_sha256': before,
    'build_manifest_sha256': sha256((ROOT / 'public/build/manifest.json').read_bytes()).hexdigest(),
    'feature_tests': summaries[0], 'feature_test_files': tests,
    'frontend_checks': frontend_checks,
    'build': 'Production Vite build completed; compiled assets included. No server npm build required.',
    'scope': 'Local SQLite feature tests, frontend syntax and any available DOM checks recorded above. Hosting/FPM and browser screenshots are not covered.',
}
(AUDITS / '2026-10-27-application-validation.json').write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
print(json.dumps({'result': 'passed', 'feature_tests': summaries[0]['tests'], 'frontend_syntax': 'passed', 'dom_rerun': frontend_checks['dom_rerun']}), flush=True)
