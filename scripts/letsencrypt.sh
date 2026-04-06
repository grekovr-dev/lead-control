#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
ENV_FILE="${ENV_FILE:-.env.production}"
ACTION="${1:-}"

cd "$REPO_ROOT"

if [[ ! -f "$COMPOSE_FILE" ]]; then
    echo "Compose file not found: $COMPOSE_FILE" >&2
    exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Production env file not found: $ENV_FILE" >&2
    exit 1
fi

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

if [[ -z "${LETS_ENCRYPT_PRIMARY_DOMAIN:-}" ]]; then
    echo "LETS_ENCRYPT_PRIMARY_DOMAIN is missing in $ENV_FILE" >&2
    exit 1
fi

if [[ -z "${LETS_ENCRYPT_DOMAINS:-}" ]]; then
    echo "LETS_ENCRYPT_DOMAINS is missing in $ENV_FILE" >&2
    exit 1
fi

if [[ -z "${LETS_ENCRYPT_EMAIL:-}" ]]; then
    echo "LETS_ENCRYPT_EMAIL is missing in $ENV_FILE" >&2
    exit 1
fi

mapfile -t LETS_ENCRYPT_DOMAIN_LIST < <(
    printf '%s' "$LETS_ENCRYPT_DOMAINS" \
        | tr ',' '\n' \
        | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' \
        | sed '/^$/d'
)

if [[ "${#LETS_ENCRYPT_DOMAIN_LIST[@]}" -eq 0 ]]; then
    echo "LETS_ENCRYPT_DOMAINS is empty after parsing" >&2
    exit 1
fi

PRIMARY_DOMAIN="${LETS_ENCRYPT_PRIMARY_DOMAIN}"
CERTS_DIR="$REPO_ROOT/docker/certbot/conf/live/$PRIMARY_DOMAIN"
CHALLENGE_DIR="$REPO_ROOT/docker/certbot/www/.well-known/acme-challenge"

mkdir -p "$CERTS_DIR" "$CHALLENGE_DIR"

build_san_list() {
    local san=""
    local domain

    for domain in "${LETS_ENCRYPT_DOMAIN_LIST[@]}"; do
        san+="DNS:${domain},"
    done

    printf '%s' "${san%,}"
}

bootstrap() {
    if [[ -s "$CERTS_DIR/fullchain.pem" && -s "$CERTS_DIR/privkey.pem" ]]; then
        echo "Temporary certificate already exists for $PRIMARY_DOMAIN"
        return
    fi

    echo "Generating temporary certificate for $PRIMARY_DOMAIN"
    openssl req \
        -x509 \
        -nodes \
        -newkey rsa:2048 \
        -days 2 \
        -keyout "$CERTS_DIR/privkey.pem" \
        -out "$CERTS_DIR/fullchain.pem" \
        -subj "/CN=$PRIMARY_DOMAIN" \
        -addext "subjectAltName=$(build_san_list)"
}

issue() {
    local -a certbot_args=(
        certonly
        --webroot
        --webroot-path /var/www/certbot
        --email "$LETS_ENCRYPT_EMAIL"
        --agree-tos
        --no-eff-email
        --non-interactive
    )

    local domain

    for domain in "${LETS_ENCRYPT_DOMAIN_LIST[@]}"; do
        certbot_args+=(-d "$domain")
    done

    docker compose -f "$COMPOSE_FILE" run --rm certbot \
        "${certbot_args[@]}"

    docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload
}

renew() {
    docker compose -f "$COMPOSE_FILE" run --rm certbot renew \
        --webroot \
        --webroot-path /var/www/certbot

    docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload
}

case "$ACTION" in
    bootstrap)
        bootstrap
        ;;
    issue)
        issue
        ;;
    renew)
        renew
        ;;
    *)
        echo "Usage: $0 {bootstrap|issue|renew}" >&2
        exit 1
        ;;
esac
