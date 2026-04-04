#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
DB_SERVICE="${DB_SERVICE:-db}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/lead-control/mysql}"
TIMESTAMP="${TIMESTAMP:-$(date +%Y%m%d-%H%M%S)}"

cd "$REPO_ROOT"

if [[ ! -f "$COMPOSE_FILE" ]]; then
    echo "Compose file not found: $COMPOSE_FILE" >&2
    exit 1
fi

db_container_id="$(docker compose -f "$COMPOSE_FILE" ps -q "$DB_SERVICE")"
if [[ -z "$db_container_id" ]]; then
    echo "Database service is not running in $COMPOSE_FILE" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

backup_file="$BACKUP_DIR/lead-control-mysql-$TIMESTAMP.sql.gz"
tmp_backup_file="${backup_file}.tmp"

trap 'rm -f "$tmp_backup_file"' EXIT

docker compose -f "$COMPOSE_FILE" exec -T "$DB_SERVICE" sh -lc '
    set -e
    : "${MYSQL_DATABASE:?Missing MYSQL_DATABASE}"
    : "${MYSQL_USER:?Missing MYSQL_USER}"
    : "${MYSQL_PASSWORD:?Missing MYSQL_PASSWORD}"

    MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump \
        --single-transaction \
        --quick \
        --routines \
        --triggers \
        --events \
        --hex-blob \
        --default-character-set=utf8mb4 \
        --no-tablespaces \
        -u"$MYSQL_USER" \
        "$MYSQL_DATABASE"
' | gzip -9 > "$tmp_backup_file"

mv "$tmp_backup_file" "$backup_file"
sha256sum "$backup_file" > "${backup_file}.sha256"

echo "Backup created: $backup_file"
