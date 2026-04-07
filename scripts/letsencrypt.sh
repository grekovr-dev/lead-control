#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
ENV_FILE="${ENV_FILE:-.env.production}"
ACTION="${1:-}"
if [[ $# -gt 0 ]]; then
    shift
fi
RENEW_ARGS=("$@")

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
LIVE_DIR="$REPO_ROOT/docker/certbot/conf/live"
PRIMARY_CERT_DIR="$LIVE_DIR/$PRIMARY_DOMAIN"
ACTIVE_CERT_DIR="$LIVE_DIR/${PRIMARY_DOMAIN}-active"
BOOTSTRAP_CERT_DIR="$LIVE_DIR/${PRIMARY_DOMAIN}-bootstrap"
CHALLENGE_DIR="$REPO_ROOT/docker/certbot/www/.well-known/acme-challenge"

mkdir -p "$LIVE_DIR" "$CHALLENGE_DIR"

log() {
    printf '[letsencrypt] %s\n' "$*"
}

log_command() {
    local arg

    printf '[letsencrypt] Running command:'
    for arg in "$@"; do
        printf ' %q' "$arg"
    done
    printf '\n'
}

build_san_list() {
    local san=""
    local domain

    for domain in "${LETS_ENCRYPT_DOMAIN_LIST[@]}"; do
        san+="DNS:${domain},"
    done

    printf '%s' "${san%,}"
}

has_certificate_material() {
    local cert_dir="$1"

    [[ -e "$cert_dir/fullchain.pem" && -e "$cert_dir/privkey.pem" ]]
}

is_self_signed_certificate() {
    local cert_dir="$1"

    if ! has_certificate_material "$cert_dir"; then
        return 1
    fi

    if ! openssl x509 -in "$cert_dir/fullchain.pem" -noout -issuer >/dev/null 2>&1; then
        return 1
    fi

    [[ "$(openssl x509 -in "$cert_dir/fullchain.pem" -noout -issuer | sed 's/^issuer=//')" == "$(openssl x509 -in "$cert_dir/fullchain.pem" -noout -subject | sed 's/^subject=//')" ]]
}

find_current_certificate_source() {
    local latest_lineage

    if latest_lineage="$(find_latest_certificate_lineage)"; then
        printf '%s' "$latest_lineage"
        return 0
    fi

    if has_certificate_material "$PRIMARY_CERT_DIR" && ! is_self_signed_certificate "$PRIMARY_CERT_DIR"; then
        printf '%s' "$PRIMARY_CERT_DIR"
        return 0
    fi

    return 1
}

link_certificate_alias() {
    local target_dir="$1"
    local source_dir="$2"
    local live_dir="$LIVE_DIR"
    local target_name
    local source_name

    target_name="$(basename "$target_dir")"
    source_name="$(basename "$source_dir")"

    if [[ "$target_name" == "$source_name" ]]; then
        return
    fi

    rm -rf "$target_dir"

    echo "Linking $target_name to $source_name"
    (
        cd "$live_dir"
        ln -s "$source_name" "$target_name"
    )
}

remove_certificate_alias_if_pointing_to_source() {
    local target_dir="$1"
    local source_dir="$2"

    if [[ ! -L "$target_dir" ]]; then
        return
    fi

    if [[ "$(readlink "$target_dir")" != "$(basename "$source_dir")" ]]; then
        return
    fi

    rm -f "$target_dir"
}

sync_certificate_aliases() {
    local source_dir="$1"

    link_certificate_alias "$ACTIVE_CERT_DIR" "$source_dir"
}

bootstrap() {
    local current_source

    log "Starting bootstrap certificate preparation"
    log "Primary domain: $PRIMARY_DOMAIN"
    log "Primary certificate directory: $PRIMARY_CERT_DIR"
    log "Active certificate directory: $ACTIVE_CERT_DIR"
    log "Bootstrap certificate directory: $BOOTSTRAP_CERT_DIR"

    if current_source="$(find_current_certificate_source)"; then
        log "Found current certificate source: $current_source"
        sync_certificate_aliases "$current_source"
        log "Bootstrap finished using existing certificate source"
        return
    fi

    mkdir -p "$BOOTSTRAP_CERT_DIR"
    log "No existing real certificate source found"

    if has_certificate_material "$BOOTSTRAP_CERT_DIR"; then
        log "Temporary bootstrap certificate already exists for $PRIMARY_DOMAIN"
        sync_certificate_aliases "$BOOTSTRAP_CERT_DIR"
        log "Bootstrap finished using existing bootstrap certificate"
        return
    fi

    log "Generating temporary self-signed bootstrap certificate for $PRIMARY_DOMAIN"
    openssl req \
        -x509 \
        -nodes \
        -newkey rsa:2048 \
        -days 2 \
        -keyout "$BOOTSTRAP_CERT_DIR/privkey.pem" \
        -out "$BOOTSTRAP_CERT_DIR/fullchain.pem" \
        -subj "/CN=$PRIMARY_DOMAIN" \
        -addext "subjectAltName=$(build_san_list)"

    sync_certificate_aliases "$BOOTSTRAP_CERT_DIR"
    log "Bootstrap finished using newly generated bootstrap certificate"
}

cleanup_bootstrap_certificate() {
    if ! has_certificate_material "$BOOTSTRAP_CERT_DIR"; then
        return
    fi

    if ! is_self_signed_certificate "$BOOTSTRAP_CERT_DIR"; then
        return
    fi

    echo "Removing temporary certificate for $PRIMARY_DOMAIN before issuing Let's Encrypt"
    remove_certificate_alias_if_pointing_to_source "$ACTIVE_CERT_DIR" "$BOOTSTRAP_CERT_DIR"
    rm -rf "$BOOTSTRAP_CERT_DIR"
}

find_latest_certificate_lineage() {
    local live_dir="$LIVE_DIR"
    local candidate
    local newest_candidate=""
    local newest_suffix=-1
    local suffix
    local base_name

    while IFS= read -r candidate; do
        [[ -d "$candidate" ]] || continue
        [[ "$(basename "$candidate")" != "$PRIMARY_DOMAIN" ]] || continue

        base_name="$(basename "$candidate")"
        suffix="${base_name#${PRIMARY_DOMAIN}-}"
        if [[ "$suffix" == "$base_name" ]] || ! [[ "$suffix" =~ ^[0-9]+$ ]]; then
            continue
        fi

        if (( suffix > newest_suffix )); then
            newest_suffix="$suffix"
            newest_candidate="$candidate"
        fi
    done < <(find "$live_dir" -mindepth 1 -maxdepth 1 -type d -name "${PRIMARY_DOMAIN}-*")

    if [[ -n "$newest_candidate" ]]; then
        printf '%s' "$newest_candidate"
        return 0
    fi

    return 1
}

extract_lineage_from_certbot_output() {
    local certbot_output="$1"
    local lineage

    lineage="$(
        printf '%s\n' "$certbot_output" \
            | sed -n 's#^Certificate is saved at: .*/live/\([^/]\+\)/fullchain\.pem$#\1#p' \
            | tail -n 1
    )"

    if [[ -n "$lineage" ]]; then
        printf '%s' "$lineage"
        return 0
    fi

    return 1
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

    local current_source
    local certbot_output
    local issued_source
    local lineage_name

    log "Starting certificate issue flow"
    log "Compose file: $COMPOSE_FILE"
    log "Environment file: $ENV_FILE"
    log "Primary domain: $PRIMARY_DOMAIN"
    log "All domains: ${LETS_ENCRYPT_DOMAIN_LIST[*]}"
    log "Challenge directory: $CHALLENGE_DIR"
    log "Primary certificate directory: $PRIMARY_CERT_DIR"
    log "Active certificate directory: $ACTIVE_CERT_DIR"
    log "Bootstrap certificate directory: $BOOTSTRAP_CERT_DIR"

    if current_source="$(find_current_certificate_source)"; then
        log "Found existing real certificate source: $current_source"
        sync_certificate_aliases "$current_source"
        log "Reloading nginx to pick up current certificate source"
        log_command docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload

        if docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload; then
            log "Nginx reload completed successfully"
        else
            local exit_code=$?
            log "Nginx reload failed with exit code $exit_code"
            return "$exit_code"
        fi

        log "Issue flow finished without requesting a new certificate"
        return
    fi

    log "No existing real certificate source found"
    log "Cleaning up bootstrap certificate before issuing Let's Encrypt"
    cleanup_bootstrap_certificate

    log_command docker compose -f "$COMPOSE_FILE" run --rm certbot "${certbot_args[@]}"

    if ! certbot_output="$(
        docker compose -f "$COMPOSE_FILE" run --rm certbot \
            "${certbot_args[@]}" 2>&1
    )"; then
        log "Certbot certificate issuance failed"
        printf '%s\n' "$certbot_output" >&2
        exit 1
    fi

    printf '%s\n' "$certbot_output"

    if lineage_name="$(extract_lineage_from_certbot_output "$certbot_output")"; then
        issued_source="$LIVE_DIR/$lineage_name"
        log "Detected issued lineage from certbot output: $issued_source"
    elif current_source="$(find_current_certificate_source)"; then
        issued_source="$current_source"
        log "Could not parse issued lineage from certbot output; using detected current certificate source: $issued_source"
    else
        echo "Could not determine issued certificate lineage for $PRIMARY_DOMAIN" >&2
        exit 1
    fi

    sync_certificate_aliases "$issued_source"
    log "Reloading nginx to pick up issued certificate"
    log_command docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload

    if docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload; then
        log "Nginx reload completed successfully"
    else
        local exit_code=$?
        log "Nginx reload failed with exit code $exit_code"
        return "$exit_code"
    fi

    log "Issue flow finished successfully"
}

renew() {
    local -a certbot_args=(
        renew
        --webroot
        --webroot-path /var/www/certbot
        "${RENEW_ARGS[@]}"
    )

    log "Starting certificate renewal"
    log "Compose file: $COMPOSE_FILE"
    log "Environment file: $ENV_FILE"
    log "Primary domain: $PRIMARY_DOMAIN"
    log "All domains: ${LETS_ENCRYPT_DOMAIN_LIST[*]}"
    log "Challenge directory: $CHALLENGE_DIR"
    log "Primary certificate directory: $PRIMARY_CERT_DIR"
    log "Active certificate directory: $ACTIVE_CERT_DIR"
    log "Bootstrap certificate directory: $BOOTSTRAP_CERT_DIR"
    log_command docker compose -f "$COMPOSE_FILE" run --rm certbot "${certbot_args[@]}"

    if docker compose -f "$COMPOSE_FILE" run --rm certbot "${certbot_args[@]}"; then
        log "Certbot renew completed successfully"
    else
        local exit_code=$?
        log "Certbot renew failed with exit code $exit_code"
        return "$exit_code"
    fi

    log "Reloading nginx to pick up certificate changes"
    log_command docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload

    if docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload; then
        log "Nginx reload completed successfully"
    else
        local exit_code=$?
        log "Nginx reload failed with exit code $exit_code"
        return "$exit_code"
    fi
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
        echo "Usage: $0 {bootstrap|issue|renew [certbot-renew-args...]}" >&2
        exit 1
        ;;
esac
