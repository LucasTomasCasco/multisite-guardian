#!/bin/sh
# Builds the installable ZIP. It contains runtime files only: no Composer, tests or Docker setup.
#
#   sh scripts/build-release.sh   ->   dist/multisite-guardian-<version>.zip

set -eu

ROOT=$(cd "$(dirname "$0")/.." && pwd)
VERSION=$(sed -n 's/^ \* Version: *//p' "$ROOT/multisite-guardian.php" | head -1)
DIST="$ROOT/dist"
STAGE="$DIST/multisite-guardian"

rm -rf "$DIST"
mkdir -p "$STAGE"

cp "$ROOT/multisite-guardian.php" "$ROOT/uninstall.php" "$ROOT/LICENSE" "$ROOT/README.md" "$ROOT/CHANGELOG.md" "$STAGE/"
cp -R "$ROOT/src" "$ROOT/views" "$ROOT/languages" "$STAGE/"

(cd "$DIST" && zip -rq "multisite-guardian-$VERSION.zip" multisite-guardian)
rm -rf "$STAGE"

printf '%s\n' "$DIST/multisite-guardian-$VERSION.zip"
