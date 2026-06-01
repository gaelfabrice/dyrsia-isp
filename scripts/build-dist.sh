#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
PACKAGE_NAME="dyrsia-server-$(date +%Y%m%d%H%M%S).tar.gz"

rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR"

tar \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='config.php' \
  --exclude='dist' \
  --exclude='ui/cache/*.php' \
  --exclude='ui/compiled/*.php' \
  --exclude='system/cache/*' \
  --exclude='system/uploads/invoices/*' \
  -czf "$DIST_DIR/$PACKAGE_NAME" \
  -C "$ROOT_DIR" .

echo "$DIST_DIR/$PACKAGE_NAME"
