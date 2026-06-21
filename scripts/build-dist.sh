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

rm -rf "$DIST_DIR"
mkdir -p "$STAGING"

git -C "$ROOT_DIR" ls-files -z ':!:dist/*' | tar -C "$ROOT_DIR" --null -T - -cf - | tar -x -C "$STAGING"

if [ -f "$STAGING/.htaccess.example" ]; then
  cp "$STAGING/.htaccess.example" "$STAGING/.htaccess"
fi

mkdir -p \
  "$STAGING/system/cache" \
  "$STAGING/ui/compiled" \
  "$STAGING/ui/cache" \
  "$STAGING/system/uploads/mikrotik_hotspot"

if [ -f "$ROOT_DIR/ui/ui/templates/mikrotik-hotspot-login.html" ]; then
  cp "$ROOT_DIR/ui/ui/templates/mikrotik-hotspot-login.html" "$STAGING/system/uploads/mikrotik_hotspot/login.html"
fi
if [ -d "$ROOT_DIR/ui/ui/templates/mikrotik_hotspot" ]; then
  cp "$ROOT_DIR/ui/ui/templates/mikrotik_hotspot/"*.png "$STAGING/system/uploads/mikrotik_hotspot/" 2>/dev/null || true
fi

mkdir -p "$STAGING/system/uploads"

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

LATEST="$DIST_DIR/wifizone-server-latest.tar.gz"
cp -f "$DIST_DIR/$PACKAGE_NAME" "$LATEST"

GIT_SHA="$(git -C "$ROOT_DIR" rev-parse --short HEAD 2>/dev/null || echo unknown)"
BUILD_JSON="$DIST_DIR/BUILD.json"
cat > "$BUILD_JSON" <<EOF
{
  "package": "$PACKAGE_NAME",
  "built_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "git_sha": "$GIT_SHA",
  "latest": "wifizone-server-latest.tar.gz"
}
EOF

echo "$DIST_DIR/$PACKAGE_NAME"
echo "$LATEST"
echo "$BUILD_JSON"
