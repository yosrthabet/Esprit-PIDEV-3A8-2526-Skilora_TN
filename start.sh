#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
PORT="8000"
HOST="127.0.0.1"
RUN_MIGRATIONS=0
IMPORT_JOBS=0
BUILD_ASSETS=0
USE_PHP_BUILTIN=0

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

info() { printf "${CYAN}[INFO]${NC}  %s\n" "$1"; }
ok() { printf "${GREEN}[OK]${NC}    %s\n" "$1"; }
warn() { printf "${YELLOW}[WARN]${NC}  %s\n" "$1"; }
fail() { printf "${RED}[FAIL]${NC}  %s\n" "$1"; exit 1; }

usage() {
    cat <<'EOF'
Usage: ./start.sh [options]

Options:
  --port=8000          HTTP port (default: 8000, auto-increments if busy)
  --host=127.0.0.1     HTTP host (default: 127.0.0.1)
  --migrate            Run pending Doctrine migrations before start
  --import-jobs        Run recruitment feed import before start
  --build-assets       Run npm run build instead of Vite dev server
  --php-builtin        Force PHP built-in server instead of Symfony CLI
  -h, --help           Show this help

Examples:
  ./start.sh
  ./start.sh --port=8001 --import-jobs
  ./start.sh --migrate --build-assets
EOF
}

for arg in "$@"; do
    case "$arg" in
        --port=*) PORT="${arg#*=}" ;;
        --host=*) HOST="${arg#*=}" ;;
        --migrate) RUN_MIGRATIONS=1 ;;
        --import-jobs) IMPORT_JOBS=1 ;;
        --build-assets) BUILD_ASSETS=1 ;;
        --php-builtin) USE_PHP_BUILTIN=1 ;;
        -h|--help) usage; exit 0 ;;
        *) fail "Unknown option: $arg" ;;
    esac
done

cd "$PROJECT_DIR"

find_available_port() {
    local candidate="$1"
    while lsof -i :"$candidate" -sTCP:LISTEN >/dev/null 2>&1; do
        warn "Port $candidate is already in use — trying $((candidate + 1))"
        candidate=$((candidate + 1))
    done
    printf '%s' "$candidate"
}

echo ""
printf "${CYAN}╔══════════════════════════════════════╗${NC}\n"
printf "${CYAN}║       Skilora WEB_Final Launcher     ║${NC}\n"
printf "${CYAN}╚══════════════════════════════════════╝${NC}\n"
echo ""

[[ -f composer.json ]] || fail "composer.json not found. Run from WEB_Final."
[[ -f package.json ]] || fail "package.json not found. Run from WEB_Final."
[[ -f .env.local ]] || warn ".env.local not found; Symfony will fall back to .env"

command -v php >/dev/null 2>&1 || fail "PHP not found"
ok "PHP $(php -r 'echo PHP_VERSION;')"

command -v composer >/dev/null 2>&1 || fail "Composer not found"
[[ -d vendor ]] || fail "vendor/ missing. Run: composer install"

command -v npm >/dev/null 2>&1 || fail "npm not found"
[[ -d node_modules ]] || fail "node_modules/ missing. Run: npm install"

info "Checking database through Doctrine..."
if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
    ok "Database reachable"
else
    warn "Database is not reachable with current DATABASE_URL"
    warn "Start MariaDB/MySQL, then retry. If needed: docker compose up -d database"
fi

info "Checking Doctrine migration status..."
if php bin/console doctrine:migrations:up-to-date --no-interaction >/dev/null 2>&1; then
    ok "Doctrine migrations are up to date"
else
    warn "Doctrine migrations are pending or status check failed"
    if [[ "$RUN_MIGRATIONS" -eq 1 ]]; then
        info "Running migrations..."
        php bin/console doctrine:migrations:migrate --no-interaction
        ok "Migrations complete"
    else
        warn "Run with --migrate to apply migrations"
    fi
fi

if [[ "$IMPORT_JOBS" -eq 1 ]]; then
    info "Importing recruitment job feed..."
    php bin/console app:recruitment:import-feed || warn "Job import failed"
else
    info "Skipping job import (use --import-jobs to refresh external feed)"
fi

info "Clearing Symfony cache..."
php bin/console cache:clear --no-warmup -q || warn "Cache clear failed"

cleanup() {
    echo ""
    info "Shutting down..."
    [[ -n "${VITE_PID:-}" ]] && kill "$VITE_PID" 2>/dev/null || true
    [[ -n "${SERVER_PID:-}" ]] && kill "$SERVER_PID" 2>/dev/null || true
    exit 0
}
trap cleanup INT TERM

if [[ "$BUILD_ASSETS" -eq 1 ]]; then
    info "Building assets..."
    npm run build
else
    info "Starting Vite dev server..."
    npm run dev -- --host "$HOST" >/tmp/skilora-vite.log 2>&1 &
    VITE_PID=$!
    ok "Vite started (PID $VITE_PID, log: /tmp/skilora-vite.log)"
fi

REQUESTED_PORT="$PORT"
PORT="$(find_available_port "$PORT")"
if [[ "$PORT" != "$REQUESTED_PORT" ]]; then
    ok "Using next available app port: $PORT"
fi

if lsof -i :"$PORT" -sTCP:LISTEN >/dev/null 2>&1; then
    fail "Port $PORT unexpectedly became busy"
else
    if [[ "$USE_PHP_BUILTIN" -eq 0 ]] && command -v symfony >/dev/null 2>&1; then
        info "Starting Symfony server on http://${HOST}:${PORT}..."
        symfony server:start --no-tls --listen-ip="$HOST" --port="$PORT" --allow-http >/tmp/skilora-symfony.log 2>&1 &
        SERVER_PID=$!
        ok "Symfony server started (PID $SERVER_PID, log: /tmp/skilora-symfony.log)"
    else
        info "Starting PHP built-in server on http://${HOST}:${PORT}..."
        php -S "${HOST}:${PORT}" -t public/ >/tmp/skilora-php-server.log 2>&1 &
        SERVER_PID=$!
        ok "PHP server started (PID $SERVER_PID, log: /tmp/skilora-php-server.log)"
    fi
fi

echo ""
printf "${GREEN}╔══════════════════════════════════════════════════╗${NC}\n"
printf "${GREEN}║  Skilora running                                ║${NC}\n"
printf "${GREEN}║  App:   ${NC}http://${HOST}:${PORT}${GREEN}                         ║${NC}\n"
if [[ "$BUILD_ASSETS" -eq 0 ]]; then
printf "${GREEN}║  Vite:  ${NC}http://${HOST}:5173${GREEN}                         ║${NC}\n"
fi
printf "${GREEN}║  Press Ctrl+C to stop managed processes         ║${NC}\n"
printf "${GREEN}╚══════════════════════════════════════════════════╝${NC}\n"
echo ""

while true; do
    sleep 1
done
