#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${PORT:-8080}"
HOST="${HOST:-0.0.0.0}"
WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
echo "wifizones dev server: http://${HOST}:${PORT}/"
echo "Admin:  http://${HOST}:${PORT}/?_route=admin"
if [ "${WORKERS}" -gt 1 ] 2>/dev/null; then
  export PHP_CLI_SERVER_WORKERS="${WORKERS}"
  echo "Workers: ${WORKERS} (évite le blocage pendant l'envoi MikroTik)"
fi
exec php -S "${HOST}:${PORT}" -t . router.php
