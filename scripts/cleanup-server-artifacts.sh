#!/usr/bin/env bash
# Server-only cleanup of the exact artifacts in the supplied 26 October listing.
# Keep the latest v4 release and all application/runtime files.
set -Eeuo pipefail
umask 077

APP_DIR="${1:-$PWD}"
MODE="${2:---check}"
if [[ $# -gt 2 || ( "$MODE" != '--check' && "$MODE" != '--apply' ) ]]; then
    echo 'Usage: bash cleanup-server-artifacts.sh /absolute/laravel/root [--check|--apply]' >&2
    exit 1
fi
for required in realpath tar mktemp rm mkdir sha256sum; do command -v "$required" >/dev/null; done
APP_DIR="$(CDPATH= cd -- "$APP_DIR" && pwd -P)"
[[ "$APP_DIR" != '/' ]] || { echo 'Refusing filesystem root.' >&2; exit 1; }
for required in artisan composer.json vendor/autoload.php; do
    [[ -f "$APP_DIR/$required" ]] || { echo "Not the Laravel project root: missing $required" >&2; exit 1; }
done
[[ -d "$APP_DIR/storage/app" ]] || { echo 'Private Laravel backup storage is missing.' >&2; exit 1; }

CANDIDATES=(
    'update-20261023'
    'update-20261023-latest'
    'update-20261023-latest-v2'
    'update-20261026'
    'sch_of_slsc-update-2026-10-26.zip'
    'as $group => $records) {'
)
TARGETS=()
for relative in "${CANDIDATES[@]}"; do
    target="$APP_DIR/$relative"
    if [[ -L "$target" ]]; then
        echo "Refusing symbolic-link cleanup target: $relative" >&2
        exit 1
    fi
    [[ -e "$target" ]] || continue
    [[ -f "$target" || -d "$target" ]] || { echo "Unexpected file type: $relative" >&2; exit 1; }
    resolved="$(realpath -e -- "$target")"
    [[ "$resolved" == "$APP_DIR/$relative" ]] || { echo "Target escaped project root: $relative" >&2; exit 1; }
    TARGETS+=("$relative")
done
if [[ ${#TARGETS[@]} -eq 0 ]]; then
    echo 'Nothing to clean. All listed old artifacts are already absent.'
    exit 0
fi
echo 'Only these exact project-root artifacts will be backed up and removed:'
printf '  %s\n' "${TARGETS[@]}"
echo 'Keeping v4 ZIP/folder, node_modules, scripts, source, vendor, .env, database and storage.'
if [[ "$MODE" == '--check' ]]; then
    echo 'Preview only. No files changed. Use --apply to back up and remove the listed artifacts.'
    exit 0
fi

# Retain a verified copy of the latest release before removing older releases.
[[ -f "$APP_DIR/update-20261026-v4/deploy.sh" && -f "$APP_DIR/update-20261026-v4/PATCH-MANIFEST.json" ]] || {
    echo 'Keep the complete update-20261026-v4 folder in the project root first.' >&2; exit 1;
}
[[ -f "$APP_DIR/sch_of_slsc-update-2026-10-26-v4.zip" ]] || { echo 'Keep the v4 ZIP before cleanup.' >&2; exit 1; }
LATEST_SHA="$(sha256sum -- "$APP_DIR/sch_of_slsc-update-2026-10-26-v4.zip")"
[[ "${LATEST_SHA%% *}" == '69b07e8b0799b77a119e413b95d072a0537087046a25e1d654bc756bf324e5ae' ]] || {
    echo 'The retained v4 ZIP does not match the verified release. No artifacts removed.' >&2; exit 1;
}

resolved="$(realpath -e -- "$APP_DIR/storage/app")"
[[ "$resolved" == "$APP_DIR/"* ]] || { echo 'Private backup storage must stay inside this project.' >&2; exit 1; }
BACKUP_ROOT="$APP_DIR/storage/app/cleanup-backups"
[[ ! -L "$BACKUP_ROOT" ]] || { echo 'Refusing symbolic-link backup directory.' >&2; exit 1; }
mkdir -p -- "$BACKUP_ROOT"
resolved="$(realpath -e -- "$BACKUP_ROOT")"
[[ "$resolved" == "$APP_DIR/"* ]] || { echo 'Backup directory escaped project root.' >&2; exit 1; }
LOCK_DIR="$BACKUP_ROOT/cleanup-20261026.lock"
mkdir -- "$LOCK_DIR" 2>/dev/null || { echo "Cleanup is already running or its lock remains: $LOCK_DIR" >&2; exit 1; }
trap 'rmdir -- "$LOCK_DIR" 2>/dev/null || true' EXIT
BACKUP_DIR="$(mktemp -d "$BACKUP_ROOT/cleanup-20261026-XXXXXX")"
chmod 700 "$BACKUP_DIR"
printf '%s\0' "${TARGETS[@]}" > "$BACKUP_DIR/removed-files.nul"
printf '%s\n' "${TARGETS[@]}" > "$BACKUP_DIR/removed-files.txt"
tar --one-file-system -C "$APP_DIR" -czf "$BACKUP_DIR/removed-artifacts.tar.gz" --null -T "$BACKUP_DIR/removed-files.nul"
tar -tzf "$BACKUP_DIR/removed-artifacts.tar.gz" >/dev/null
echo "Verified backup: $BACKUP_DIR/removed-artifacts.tar.gz"

for relative in "${TARGETS[@]}"; do
    target="$APP_DIR/$relative"
    [[ ! -L "$target" ]] || { echo "Target changed to a symbolic link: $relative" >&2; exit 1; }
    resolved="$(realpath -e -- "$target")"
    [[ "$resolved" == "$APP_DIR/$relative" ]] || { echo "Target changed outside project root: $relative" >&2; exit 1; }
    if [[ -d "$target" ]]; then
        rm -rf --one-file-system -- "$target"
    else
        rm -f -- "$target"
    fi
    echo "Removed: $relative"
done
echo 'Cleanup complete. Latest v4 release and application files were retained.'
printf 'Restore if needed: tar -xzf %q -C %q\n' "$BACKUP_DIR/removed-artifacts.tar.gz" "$APP_DIR"
