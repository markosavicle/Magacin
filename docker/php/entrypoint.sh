#!/usr/bin/env bash
# Container start-up for Magacin. CONTAINER_ROLE decides what runs before the main command:
#   app        wait for Postgres, migrate, seed the admin (and demo data if SEED_DEMO=true), cache config
#   scheduler  wait for the app to finish the above, cache config, then run the command (schedule:work)
set -euo pipefail

ROLE="${CONTAINER_ROLE:-app}"
STATE_DIR="storage/app/private/.docker"   # on the storage volume, so it survives restarts
READY_FILE="/tmp/magacin-ready"
mkdir -p "$STATE_DIR" storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs
chmod 700 "$STATE_DIR"

log() { printf '[magacin:%s] %s\n' "$ROLE" "$*"; }

# One random value per install, generated on first start by the app container and kept on the volume.
persisted_secret() { # $1 file name, $2 generator command
    local file="$STATE_DIR/$1"
    if [ ! -s "$file" ]; then
        if [ "$ROLE" != "app" ]; then
            for _ in $(seq 1 60); do [ -s "$file" ] && break; sleep 1; done
        else
            (umask 077; eval "$2" > "$file")
        fi
    fi
    cat "$file"
}

if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="$(persisted_secret app_key 'echo "base64:$(head -c 32 /dev/urandom | base64)"')"
    export APP_KEY
    log "APP_KEY not set in .env, using the one generated for this install."
fi

if [ "${DB_CONNECTION:-}" = "pgsql" ]; then
    log "Waiting for Postgres at ${DB_HOST}:${DB_PORT:-5432}..."
    for i in $(seq 1 60); do
        pg_isready -q -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -d "$DB_DATABASE" && break
        [ "$i" = 60 ] && { log "Postgres did not come up in 60s."; exit 1; }
        sleep 1
    done
fi

if [ "$ROLE" = "app" ]; then
    [ -e "$READY_FILE" ] && unlink "$READY_FILE"
    php artisan migrate --force --no-interaction

    export ADMIN_EMAIL="${ADMIN_EMAIL:-admin@magacin.example}"
    generated_password=false
    if [ -z "${ADMIN_PASSWORD:-}" ]; then
        ADMIN_PASSWORD="$(persisted_secret admin_password 'head -c 18 /dev/urandom | base64 | tr -d "/+=" | cut -c1-20')"
        export ADMIN_PASSWORD
        generated_password=true
    fi
    php artisan db:seed --force --no-interaction   # AdminUserSeeder: creates the admin once, never resets it

    if [ "${SEED_DEMO:-false}" = "true" ]; then
        php artisan db:seed --class=DemoSeeder --force --no-interaction   # does nothing if already seeded
    fi

    php artisan optimize --no-interaction >/dev/null
    touch "$READY_FILE"

    log "Ready on ${APP_URL:-http://localhost:8080}"
    if [ "$generated_password" = true ]; then
        log "Admin login: ${ADMIN_EMAIL} / ${ADMIN_PASSWORD} (generated for this install, kept in the storage volume)"
        log "Set ADMIN_PASSWORD in .env before the first start to choose your own."
    else
        log "Admin login: ${ADMIN_EMAIL} (password from ADMIN_PASSWORD in .env)"
    fi
else
    php artisan optimize --no-interaction >/dev/null
fi

exec "$@"
