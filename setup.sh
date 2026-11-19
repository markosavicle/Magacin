#!/usr/bin/env bash
# Magacin Docker helper. Works on Linux, macOS, and Windows (Git Bash or WSL) with Docker Desktop.
# The Makefile calls this script, so `make demo` and `./setup.sh demo` do the same thing.
#
#   ./setup.sh demo      build, start everything, seed demo data, print the URL and admin login
#   ./setup.sh env       create .env with random DB and admin passwords (only if .env does not exist)
#   ./setup.sh up        start without rebuilding        ./setup.sh down     stop (data is kept)
#   ./setup.sh test      run the test suite in a container
#   ./setup.sh admin     print the admin login           ./setup.sh logs     follow logs
#   ./setup.sh backup    run a backup now                ./setup.sh verify   run the balance check
#   ./setup.sh shell     shell in the app container      ./setup.sh reset    delete all data and start over
set -euo pipefail
cd "$(dirname "$0")"

COMPOSE=(docker compose)
STATE_DIR=storage/app/private/.docker

say()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
fail() { printf '\033[1;31mError:\033[0m %s\n' "$*" >&2; exit 1; }

require_docker() {
    command -v docker >/dev/null 2>&1 || fail "Docker is not installed. Install Docker Desktop (Windows/macOS) or Docker Engine (Linux)."
    docker compose version >/dev/null 2>&1 || fail "Docker Compose v2 is missing (the 'docker compose' command)."
    docker info >/dev/null 2>&1 || fail "Docker is not running. Start Docker Desktop and try again."
}

random_secret() { # URL- and .env-safe
    if command -v openssl >/dev/null 2>&1; then openssl rand -base64 32; else head -c 32 /dev/urandom | base64; fi \
        | tr -d '/+=\n' | cut -c1-"${1:-24}"
}

set_env_value() { # key value: fill a key in .env only if it is empty
    local key="$1" value="$2"
    if grep -qE "^${key}=$" .env; then
        sed -i.bak "s|^${key}=$|${key}=${value}|" .env && rm -f .env.bak
    elif ! grep -qE "^${key}=" .env; then
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

cmd_env() {
    if [ -f .env ]; then
        say ".env already exists, leaving it as it is."
        return
    fi
    cp .env.example .env
    set_env_value DB_PASSWORD "$(random_secret 32)"
    set_env_value ADMIN_EMAIL "admin@magacin.example"
    set_env_value ADMIN_PASSWORD "$(random_secret 20)"
    say "Created .env with a random DB password and admin password."
}

wait_until_ready() {
    say "Waiting for the app (migrations and demo data run on first start)..."
    for _ in $(seq 1 120); do
        if [ "$("${COMPOSE[@]}" ps --format '{{.Health}}' web 2>/dev/null)" = "healthy" ]; then return 0; fi
        if [ "$("${COMPOSE[@]}" ps --format '{{.State}}' app 2>/dev/null)" = "exited" ]; then break; fi
        sleep 2
    done
    "${COMPOSE[@]}" logs --tail=60 app
    fail "The app did not become healthy. Logs are above; './setup.sh logs' follows them."
}

cmd_admin() {
    local email password
    email="$(grep -E '^ADMIN_EMAIL=' .env 2>/dev/null | cut -d= -f2- || true)"
    password="$(grep -E '^ADMIN_PASSWORD=' .env 2>/dev/null | cut -d= -f2- || true)"
    email="${email:-admin@magacin.example}"
    if [ -z "$password" ]; then
        password="$("${COMPOSE[@]}" exec -T app cat "$STATE_DIR/admin_password" 2>/dev/null || echo '(see: ./setup.sh logs)')"
    fi
    local port
    port="$(grep -E '^APP_PORT=' .env 2>/dev/null | cut -d= -f2- || true)"
    printf '\n  URL:      http://localhost:%s\n  E-mail:   %s\n  Password: %s\n\n' "${port:-8080}" "$email" "$password"
}

cmd_demo() {
    require_docker
    cmd_env
    say "Building images and starting Postgres, PHP-FPM, Nginx and the scheduler..."
    "${COMPOSE[@]}" up --build -d
    wait_until_ready
    say "Magacin is running with fictional demo data (Niš food & drinks wholesaler)."
    cmd_admin
}

cmd_test() {
    require_docker
    say "Running the test suite in a container (SQLite in memory, same as CI)..."
    "${COMPOSE[@]}" --profile tools run --rm --build tests
}

cmd_reset() {
    require_docker
    read -r -p "This deletes the database and storage volumes (all invoices, partners, backups). Type 'yes': " answer
    [ "$answer" = "yes" ] || fail "Cancelled."
    "${COMPOSE[@]}" down -v --remove-orphans
    say "All data removed. Run './setup.sh demo' to start fresh."
}

case "${1:-demo}" in
    demo)   cmd_demo ;;
    env)    cmd_env ;;
    up)     require_docker; "${COMPOSE[@]}" up -d; wait_until_ready; cmd_admin ;;
    down)   require_docker; "${COMPOSE[@]}" down ;;
    logs)   require_docker; "${COMPOSE[@]}" logs -f --tail=100 ;;
    admin)  require_docker; cmd_admin ;;
    test)   cmd_test ;;
    shell)  require_docker; "${COMPOSE[@]}" exec app bash ;;
    backup) require_docker; "${COMPOSE[@]}" exec app php artisan backup:run ;;
    verify) require_docker; "${COMPOSE[@]}" exec app php artisan partners:verify-balances ;;
    reset)  cmd_reset ;;
    -h|--help|help) sed -n '2,12p' "$0" | sed 's/^# \{0,1\}//' ;;
    *) fail "Unknown command '$1'. Run './setup.sh help'." ;;
esac
