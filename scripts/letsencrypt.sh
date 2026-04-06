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

mkdir -p "$REPO_ROOT/docker/certbot/conf/live" "$CHALLENGE_DIR"

build_san_list() {
    local san=""
    local domain

    for domain in "${LETS_ENCRYPT_DOMAIN_LIST[@]}"; do
        san+="DNS:${domain},"
    done

    printf '%s' "${san%,}"
}

is_self_signed_certificate() {
    local cert_file="$1"
    local issuer
    local subject

    issuer="$(openssl x509 -in "$cert_file" -noout -issuer | sed 's/^issuer=//')"
    subject="$(openssl x509 -in "$cert_file" -noout -subject | sed 's/^subject=//')"

    [[ "$issuer" == "$subject" ]]
}

bootstrap() {
    mkdir -p "$CERTS_DIR"

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

cleanup_bootstrap_certificate() {
    if [[ ! -f "$CERTS_DIR/fullchain.pem" ]]; then
        return
    fi

    if ! is_self_signed_certificate "$CERTS_DIR/fullchain.pem"; then
        return
    fi

    echo "Removing temporary certificate for $PRIMARY_DOMAIN before issuing Let's Encrypt"
    rm -rf "$CERTS_DIR"
}

find_existing_certificate_lineage() {
    local live_dir="$REPO_ROOT/docker/certbot/conf/live"
    local candidate

    for candidate in "$live_dir"/"$PRIMARY_DOMAIN"*; do
        [[ -d "$candidate" ]] || continue

        if [[ -f "$candidate/fullchain.pem" ]] && ! is_self_signed_certificate "$candidate/fullchain.pem"; then
            printf '%s' "$candidate"
            return 0
        fi
    done

    return 1
}

ensure_primary_certificate_link() {
    local source_dir="$1"
    local live_dir="$REPO_ROOT/docker/certbot/conf/live"
    local target_dir="$live_dir/$PRIMARY_DOMAIN"

    if [[ -L "$target_dir" ]]; then
        return
    fi

    if [[ -d "$target_dir" ]]; then
        rmdir "$target_dir"
    fi

    echo "Linking $PRIMARY_DOMAIN to $(basename "$source_dir")"
    (
        cd "$live_dir"
        ln -s "$(basename "$source_dir")" "$PRIMARY_DOMAIN"
    )
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

    cleanup_bootstrap_certificate

    if existing_certificate_lineage="$(find_existing_certificate_lineage)"; then
        ensure_primary_certificate_link "$existing_certificate_lineage"
        docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload
        return
    fi

    docker compose -f "$COMPOSE_FILE" run --rm certbot \
        "${certbot_args[@]}"

    if existing_certificate_lineage="$(find_existing_certificate_lineage)"; then
        ensure_primary_certificate_link "$existing_certificate_lineage"
    fi

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
