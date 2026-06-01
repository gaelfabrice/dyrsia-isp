#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${PORT:-8080}"
HOST="${HOST:-127.0.0.1}"
echo "wifizones dev server: http://${HOST}:${PORT}/"
echo "Admin:  http://${HOST}:${PORT}/?_route=admin"
exec php -S "${HOST}:${PORT}" -t . router.php
