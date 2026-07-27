#!/usr/bin/env bash
# Serveur de dev local — ne charge PAS le .env prod (Render).
# IMPORTANT : lancez ce script depuis Terminal.app (avec WireGuard actif),
# pas depuis un environnement sandboxé, sinon l'API MikroTik (10.0.0.x) timeout.
set -euo pipefail
cd "$(dirname "$0")"

PORT="${PORT:-8082}"
PIDFILE=".dev-server.pid"
# Multi-process via PHP_CLI_SERVER_WORKERS (ex. 4). Sur certains macOS/PHP,
# les workers plantent ("Failed to poll event") et tuent les déploiements async —
# défaut stable = 1. Pour forcer le multi : PHP_CLI_SERVER_WORKERS=4 ./dev-server.sh
WORKERS="${PHP_CLI_SERVER_WORKERS:-1}"

stop_port() {
  pkill -f "php .* -S localhost:${PORT}" 2>/dev/null || true
  pkill -f "php .* -S 0.0.0.0:${PORT}" 2>/dev/null || true
  pkill -f "php -S localhost:${PORT}" 2>/dev/null || true
  pkill -f "php -S 0.0.0.0:${PORT}" 2>/dev/null || true
  if command -v lsof >/dev/null 2>&1; then
    for pid in $(lsof -nP -iTCP:"${PORT}" -sTCP:LISTEN -t 2>/dev/null || true); do
      kill "${pid}" 2>/dev/null || true
    done
  fi
  sleep 1
}

start_server() {
  local workers="$1"
  : > php_dev_stdout.log
  if [[ "${workers}" -gt 1 ]]; then
    export PHP_CLI_SERVER_WORKERS="${workers}"
    nohup env PHP_CLI_SERVER_WORKERS="${workers}" \
      php -d max_execution_time=600 -d default_socket_timeout=120 \
      -S "0.0.0.0:${PORT}" router.php >> php_dev_stdout.log 2>&1 &
  else
    unset PHP_CLI_SERVER_WORKERS || true
    nohup php -d max_execution_time=600 -d default_socket_timeout=120 \
      -S "0.0.0.0:${PORT}" router.php >> php_dev_stdout.log 2>&1 &
  fi
  echo $! > "$PIDFILE"
  sleep 2
}

server_ok() {
  local code
  code="$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "http://127.0.0.1:${PORT}/" || true)"
  [[ "${code}" == "200" ]]
}

export APP_URL="http://localhost:${PORT}"
export APP_STAGE=Dev
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=wifizones
export DB_USERNAME=root
export DB_PASSWORD=

stop_port
start_server "${WORKERS}"

# Si les workers plantent (Failed to poll event), retomber en mono-process.
if ! server_ok; then
  if [[ "${WORKERS}" -gt 1 ]]; then
    echo "ATTENTION — workers=${WORKERS} instables, bascule en mono-process…"
    stop_port
    start_server 1
    WORKERS=1
  fi
fi

# Seconde vérif après une courte pause (les workers peuvent mourir 1–2 s après le start).
sleep 2
if ! server_ok; then
  if [[ "${WORKERS}" -gt 1 ]]; then
    echo "ATTENTION — workers morts après démarrage, bascule en mono-process…"
    stop_port
    start_server 1
    WORKERS=1
  fi
fi

if server_ok; then
  MASTER_PID="$(cat "$PIDFILE")"
  PROC_COUNT="$(pgrep -P "${MASTER_PID}" 2>/dev/null | wc -l | tr -d ' ' || true)"
  LISTEN_COUNT="$(lsof -nP -iTCP:"${PORT}" -sTCP:LISTEN -t 2>/dev/null | wc -l | tr -d ' ' || true)"
  PROC_COUNT="${PROC_COUNT:-0}"
  LISTEN_COUNT="${LISTEN_COUNT:-0}"
  echo "OK — http://localhost:${PORT} (workers=${WORKERS}, children=${PROC_COUNT}, listeners=${LISTEN_COUNT})"
else
  echo "ERREUR — le serveur ne répond pas. Voir php_dev_stdout.log"
  exit 1
fi

ROUTER_IP="$(mysql -N -u root wifizones -e "SELECT SUBSTRING_INDEX(ip_address,':',1) FROM tbl_routers WHERE enabled=1 ORDER BY id DESC LIMIT 1;" 2>/dev/null || true)"
if [[ -n "${ROUTER_IP}" ]]; then
  if nc -z -G 3 "${ROUTER_IP}" 8728 2>/dev/null; then
    echo "API MikroTik ${ROUTER_IP}:8728 — OK"
  else
    echo "ATTENTION — API MikroTik ${ROUTER_IP}:8728 injoignable. Activez WireGuard puis relancez."
  fi
fi
