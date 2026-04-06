#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-$(git -C "$REPO_ROOT" rev-parse --abbrev-ref HEAD)}"
DB_WAIT_TIMEOUT_SECONDS="${DB_WAIT_TIMEOUT_SECONDS:-60}"

cd "$REPO_ROOT"

if [[ ! -f "$COMPOSE_FILE" ]]; then
    echo "Compose file not found: $COMPOSE_FILE" >&2
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Working tree is not clean. Commit or stash changes before releasing." >&2
    exit 1
fi

db_container_id="$(docker compose -f "$COMPOSE_FILE" ps -q db || true)"

if [[ -n "$db_container_id" ]]; then
    echo "[1/7] Creating database backup"
    "$SCRIPT_DIR/backup-db.sh"
else
    echo "[1/7] Skipping database backup because the database service is not running yet"
fi

echo "[2/7] Updating code from ${GIT_REMOTE}/${GIT_BRANCH}"
git pull --ff-only "$GIT_REMOTE" "$GIT_BRANCH"

echo "[3/7] Preparing temporary TLS material for nginx"
"$SCRIPT_DIR/letsencrypt.sh" bootstrap

echo "[4/7] Rebuilding and starting production services"
docker compose -f "$COMPOSE_FILE" up -d --build --remove-orphans \
    public-assets-init \
    app \
    horizon \
    nginx \
    db \
    redis

docker compose -f "$COMPOSE_FILE" restart nginx

wait_for_database() {
    local elapsed_seconds=0

    while true; do
        if docker compose -f "$COMPOSE_FILE" exec -T db sh -lc 'mysqladmin ping -h 127.0.0.1 -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" --silent' >/dev/null 2>&1; then
            return
        fi

        if (( elapsed_seconds >= DB_WAIT_TIMEOUT_SECONDS )); then
            echo "Database did not become ready within ${DB_WAIT_TIMEOUT_SECONDS} seconds" >&2
            exit 1
        fi

        sleep 2
        elapsed_seconds=$((elapsed_seconds + 2))
    done
}

echo "[5/7] Waiting for database to become ready"
wait_for_database

echo "[6/7] Clearing caches and running database migrations"
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan optimize:clear
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan migrate --force

echo "[7/7] Rebuilding Laravel caches and restarting Horizon"
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan view:cache
docker compose -f "$COMPOSE_FILE" restart horizon

docker compose -f "$COMPOSE_FILE" ps
