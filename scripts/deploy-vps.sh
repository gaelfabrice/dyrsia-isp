#!/usr/bin/env bash
set -euo pipefail

if [ "${1:-}" = "" ]; then
  echo "Usage: scripts/deploy-vps.sh user@146.59.10.164 [/opt/wifizone]"
  exit 1
fi

TARGET="$1"
REMOTE_DIR="${2:-/opt/wifizone}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PACKAGE_PATH="$("$ROOT_DIR/scripts/build-dist.sh")"
PACKAGE_FILE="$(basename "$PACKAGE_PATH")"

echo "Package: $PACKAGE_PATH"

ssh "$TARGET" "mkdir -p '$REMOTE_DIR'"

# Préserver config locale (config.php / .env ne sont pas dans le tarball git).
ssh "$TARGET" "cd '$REMOTE_DIR' && \
  { [ -f config.php ] && cp -a config.php /tmp/wifizone-config.php.bak; true; } && \
  { [ -f .env ] && cp -a .env /tmp/wifizone-env.bak; true; }"

scp "$PACKAGE_PATH" "$TARGET:$REMOTE_DIR/$PACKAGE_FILE"

ssh "$TARGET" "set -e
  cd '$REMOTE_DIR'
  tar -xzf '$PACKAGE_FILE'
  rm -f '$PACKAGE_FILE'
  if [ -f /tmp/wifizone-config.php.bak ]; then
    cp -a /tmp/wifizone-config.php.bak config.php
    rm -f /tmp/wifizone-config.php.bak
  elif [ ! -f config.php ] && [ -f config.sample.php ]; then
    cp config.sample.php config.php
  fi
  if [ -f /tmp/wifizone-env.bak ]; then
    cp -a /tmp/wifizone-env.bak .env
    rm -f /tmp/wifizone-env.bak
  elif [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
  fi
  grep -q 'WIFIZONE_HOTSPOT_DEPLOY_INLINE' .env 2>/dev/null || echo 'WIFIZONE_HOTSPOT_DEPLOY_INLINE=1' >> .env
  grep -q 'WIFIZONE_PPPOE_DEPLOY_INLINE' .env 2>/dev/null || echo 'WIFIZONE_PPPOE_DEPLOY_INLINE=1' >> .env
  grep -q 'PHP_CLI_PATH' .env 2>/dev/null || echo 'PHP_CLI_PATH=/usr/local/bin/php' >> .env
  docker compose -f docker-compose.server.yml build --pull
  docker compose -f docker-compose.server.yml up -d --force-recreate
  docker compose -f docker-compose.server.yml ps
"

echo "Deploy finished on $TARGET ($REMOTE_DIR)"
