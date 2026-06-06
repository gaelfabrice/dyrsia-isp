#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
STAGING="$DIST_DIR/staging"
PACKAGE_NAME="wifizone-server-$(date +%Y%m%d%H%M%S).tar.gz"

if ! git -C "$ROOT_DIR" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Error: build-dist requires a git repository." >&2
  exit 1
fi

if ! git -C "$ROOT_DIR" diff-index --quiet HEAD -- 2>/dev/null; then
  echo "Warning: uncommitted changes detected — dist reflects the last commit only." >&2
fi

rm -rf "$DIST_DIR"
mkdir -p "$STAGING"

git -C "$ROOT_DIR" archive HEAD | tar -x -C "$STAGING"

if [ -f "$STAGING/.htaccess.example" ]; then
  cp "$STAGING/.htaccess.example" "$STAGING/.htaccess"
fi

mkdir -p \
  "$STAGING/system/cache" \
  "$STAGING/ui/compiled" \
  "$STAGING/ui/cache" \
  "$STAGING/system/uploads"

rm -rf \
  "$STAGING/tests" \
  "$STAGING/.github" \
  "$STAGING/scan" \
  "$STAGING/docs"

rm -f \
  "$STAGING/phpunit.xml" \
  "$STAGING/router_debug.php" \
  "$STAGING/CHECKPOINT.md" \
  "$STAGING/.DS_Store"

tar -czf "$DIST_DIR/$PACKAGE_NAME" -C "$STAGING" .
rm -rf "$STAGING"

echo "$DIST_DIR/$PACKAGE_NAME"
