#!/usr/bin/env bash
# Code/assets update plus missing-role setup. No migrations, imports or user reassignment.
set -Eeuo pipefail
umask 022

RELEASE_DIR="$(CDPATH= cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
APP_DIR="${1:-$PWD}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
RELEASE="2026-10-26-roles-and-site-update-v5"

if [[ $# -gt 2 || ( $# -eq 2 && "$2" != "--check" ) ]]; then
    echo "Usage: bash deploy.sh /absolute/path/to/laravel-project [--check]" >&2
    exit 1
fi
APP_DIR="$(CDPATH= cd -- "$APP_DIR" && pwd -P)"
for required in "$PHP_BIN" sha256sum tar mktemp cp realpath; do command -v "$required" >/dev/null; done
for required in artisan .env vendor/autoload.php bootstrap/app.php composer.json; do
    [[ -f "$APP_DIR/$required" ]] || { echo "Missing $required. Select the existing Laravel project root." >&2; exit 1; }
done
for required in payload FILES.txt FILES.sha256 PACKAGE.sha256 PATCH-MANIFEST.json; do
    [[ -e "$RELEASE_DIR/$required" ]] || { echo "Extract the complete update ZIP first." >&2; exit 1; }
done
"$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' || { echo 'PHP CLI 8.3+ is required.' >&2; exit 1; }
(cd "$RELEASE_DIR" && sha256sum --check PACKAGE.sha256 >/dev/null)
(cd "$RELEASE_DIR/payload" && sha256sum --check "$RELEASE_DIR/FILES.sha256" >/dev/null)

while IFS= read -r relative; do
    [[ -n "$relative" && "$relative" != /* && "$relative" != *'..'* && "$relative" != *\\* ]] || {
        echo "Invalid payload path: $relative" >&2; exit 1;
    }
    case "$relative" in app/*|resources/*|themes/*|public/build/*|public/themes/*|database/seeders/RolesAndPermissionsSeeder.php) ;;
        *) echo "Unexpected payload path: $relative" >&2; exit 1 ;;
    esac
    resolved="$(realpath -m -- "$APP_DIR/$relative")"
    [[ "$resolved" == "$APP_DIR/"* && ! -L "$APP_DIR/$relative" && ! -d "$APP_DIR/$relative" ]] || {
        echo "Unsafe target path: $relative" >&2; exit 1;
    }
    if [[ "$relative" == *.php && "$relative" != *.blade.php ]]; then
        "$PHP_BIN" -l "$RELEASE_DIR/payload/$relative" >/dev/null
    fi
done < "$RELEASE_DIR/FILES.txt"

AUTOLOAD_AUTHORITATIVE="$("$PHP_BIN" -r '
$manifest = json_decode(file_get_contents($argv[2]), true, 512, JSON_THROW_ON_ERROR);
$composer = json_decode(file_get_contents($argv[1]."/composer.json"), true, 512, JSON_THROW_ON_ERROR);
foreach ($manifest["composer_compatibility"] as $key => $expected) {
    if (($composer[$key] ?? []) != $expected) { fwrite(STDERR, "Hosting Composer ".$key." settings differ from this release.\n"); exit(1); }
}
foreach ($manifest["required_existing_files"] as $path) {
    if (!is_file($argv[1]."/".$path)) { fwrite(STDERR, "Missing prerequisite: ".$path.". Apply the 23 October update first.\n"); exit(1); }
}
$loader = require $argv[1]."/vendor/autoload.php";
echo $loader->isClassMapAuthoritative() ? "1" : "0";
' "$APP_DIR" "$RELEASE_DIR/PATCH-MANIFEST.json")"
[[ "$AUTOLOAD_AUTHORITATIVE" == '0' || "$AUTOLOAD_AUTHORITATIVE" == '1' ]] || { echo 'Could not inspect Composer autoload.' >&2; exit 1; }
if [[ "$AUTOLOAD_AUTHORITATIVE" == '1' ]]; then
    command -v "$COMPOSER_BIN" >/dev/null || { echo 'Composer CLI is required to register the new service in the authoritative class map.' >&2; exit 1; }
    resolved="$(realpath -- "$APP_DIR/vendor/composer")"
    [[ "$resolved" == "$APP_DIR/"* && ! -L "$APP_DIR/vendor/composer" ]] || { echo 'Composer metadata must stay inside this application for backup.' >&2; exit 1; }
fi
[[ -d "$APP_DIR/storage/app" && -d "$APP_DIR/storage/framework" ]] || { echo 'Existing writable Laravel storage is required.' >&2; exit 1; }
resolved="$(realpath -- "$APP_DIR/storage/app")"
[[ "$resolved" == "$APP_DIR/"* ]] || { echo 'Private backup storage must stay inside this application.' >&2; exit 1; }
if [[ "${2:-}" == '--check' ]]; then
    echo 'Package integrity, PHP syntax, prerequisites and Composer checks passed. No files were replaced.'
    exit 0
fi

BACKUP_ROOT="$APP_DIR/storage/app/deployment-backups"
mkdir -p "$BACKUP_ROOT"
resolved="$(realpath -- "$BACKUP_ROOT")"
[[ "$resolved" == "$APP_DIR/"* && ! -L "$BACKUP_ROOT" ]] || { echo 'Unsafe backup directory.' >&2; exit 1; }
LOCK_DIR="$BACKUP_ROOT/$RELEASE.lock"
mkdir "$LOCK_DIR" 2>/dev/null || { echo "An update is already running, or its lock remains: $LOCK_DIR" >&2; exit 1; }
trap 'rmdir -- "$LOCK_DIR" 2>/dev/null || true' EXIT
BACKUP_DIR="$(mktemp -d "$BACKUP_ROOT/$RELEASE-XXXXXX")"
chmod 700 "$BACKUP_DIR"
on_error() {
    local code=$?
    echo "Update stopped (exit $code). Code backup: $BACKUP_DIR/files-before-update.tar.gz" >&2
    echo 'If maintenance was enabled, keep it enabled until the update is corrected or backed-up files are restored. See README.md.' >&2
    exit "$code"
}
trap on_error ERR
: > "$BACKUP_DIR/existing-files.txt"
: > "$BACKUP_DIR/new-files.txt"
while IFS= read -r relative; do
    if [[ -e "$APP_DIR/$relative" ]]; then printf '%s\n' "$relative" >> "$BACKUP_DIR/existing-files.txt";
    else printf '%s\n' "$relative" >> "$BACKUP_DIR/new-files.txt"; fi
done < "$RELEASE_DIR/FILES.txt"
if [[ "$AUTOLOAD_AUTHORITATIVE" == '1' ]]; then printf '%s\n' vendor/composer >> "$BACKUP_DIR/existing-files.txt"; fi
tar -C "$APP_DIR" -czf "$BACKUP_DIR/files-before-update.tar.gz" -T "$BACKUP_DIR/existing-files.txt"
echo "Code backup: $BACKUP_DIR/files-before-update.tar.gz"

cd "$APP_DIR"
MAINTENANCE_STATE="$("$PHP_BIN" -r '
require $argv[1]."/vendor/autoload.php";
$app = require $argv[1]."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo $app->isDownForMaintenance() ? "down" : "up";
' "$APP_DIR")"
MAINTENANCE_STARTED=0
if [[ "$MAINTENANCE_STATE" == 'up' ]]; then "$PHP_BIN" artisan down --retry=60; MAINTENANCE_STARTED=1;
elif [[ "$MAINTENANCE_STATE" != 'down' ]]; then echo 'Could not determine the maintenance state.' >&2; exit 1; fi

while IFS= read -r relative; do
    mkdir -p "$(dirname -- "$APP_DIR/$relative")"
    cp -- "$RELEASE_DIR/payload/$relative" "$APP_DIR/$relative"
done < "$RELEASE_DIR/FILES.txt"
(cd "$APP_DIR" && sha256sum --check "$RELEASE_DIR/FILES.sha256" >/dev/null)
if [[ "$AUTOLOAD_AUTHORITATIVE" == '1' ]]; then
    "$COMPOSER_BIN" dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts --no-interaction
fi
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan roles:setup
if [[ "$MAINTENANCE_STARTED" == '1' ]]; then "$PHP_BIN" artisan up; fi
trap - ERR
echo "Update complete: $(wc -l < "$RELEASE_DIR/FILES.txt") files."
echo "Backup: $BACKUP_DIR/files-before-update.tar.gz"
echo 'Missing recommended roles are ready; existing grants and users were preserved. No migrations or database imports were run. Refresh with Ctrl+F5.'
