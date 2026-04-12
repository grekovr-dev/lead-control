#!/usr/bin/env bash
set -euo pipefail

host_root="/home/hrekov-r/Projects/lead-control"
container_root="/var/www"

args=()
for arg in "$@"; do
  args+=("${arg/$host_root/$container_root}")
done

docker compose exec -T -w /var/www/apps/web app php "${args[@]}"
