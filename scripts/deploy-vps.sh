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

ssh "$TARGET" "mkdir -p '$REMOTE_DIR'"
scp "$PACKAGE_PATH" "$TARGET:$REMOTE_DIR/$PACKAGE_FILE"
ssh "$TARGET" "cd '$REMOTE_DIR' && tar -xzf '$PACKAGE_FILE' && rm '$PACKAGE_FILE' && if [ ! -f .env ]; then cp .env.example .env; fi && docker compose -f docker-compose.server.yml up -d --build"
