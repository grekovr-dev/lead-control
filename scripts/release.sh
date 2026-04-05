#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-$(git -C "$REPO_ROOT" rev-parse --abbrev-ref HEAD)}"

cd "$REPO_ROOT"

if [[ ! -f "$COMPOSE_FILE" ]]; then
    echo "Compose file not found: $COMPOSE_FILE" >&2
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Working tree is not clean. Commit or stash changes before releasing." >&2
    exit 1
fi

echo "[1/5] Creating database backup"
"$SCRIPT_DIR/backup-db.sh"

echo "[2/5] Updating code from ${GIT_REMOTE}/${GIT_BRANCH}"
git pull --ff-only "$GIT_REMOTE" "$GIT_BRANCH"

echo "[3/5] Rebuilding and starting production services"
docker compose -f "$COMPOSE_FILE" up -d --build --remove-orphans \
    public-assets-init \
    app \
    horizon \
    nginx \
    db \
    redis

docker compose -f "$COMPOSE_FILE" restart nginx

echo "[4/5] Clearing caches and running database migrations"
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan optimize:clear
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan migrate --force

echo "[5/5] Rebuilding Laravel caches and restarting Horizon"
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T app php /var/www/apps/web/artisan view:cache
docker compose -f "$COMPOSE_FILE" restart horizon

docker compose -f "$COMPOSE_FILE" ps
