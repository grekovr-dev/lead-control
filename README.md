# lead-control

Lead generation and conversion tracking system.

This repository uses a monorepo layout:

- `apps/web` is the Laravel delivery layer
- `src/Inbound` contains the domain and application core
- `docker-compose.yml` is the local development stack
- `docker-compose.prod.yml` is the production stack for a single VPS

## VPS bootstrap

Use this flow for the first production deployment on a VPS.

### 1. Clone the repository into an existing directory

```bash
cd /srv/lead-control
git clone git@github.com:<org>/<repo>.git .
```

If the directory already exists and the repo is already cloned, update it instead:

```bash
cd /srv/lead-control
git pull
```

### 2. Prepare the production env file

Copy the example file and fill in real production values:

```bash
cp .env.production.example .env.production
```

At minimum, set:

- `APP_URL`
- `APP_KEY`
- `DB_PASSWORD`
- `MYSQL_PASSWORD`
- `MYSQL_ROOT_PASSWORD`

For the first bootstrap, `APP_URL` can point to the server IP address.

### 3. Generate `APP_KEY`

Build only the `app` service and print the key:

```bash
docker compose -f docker-compose.prod.yml run --rm --no-deps app php /var/www/apps/web/artisan key:generate --show
```

Copy the generated value into `.env.production` as `APP_KEY`.

### 4. Start the production stack

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

### 5. Run migrations

```bash
docker compose -f docker-compose.prod.yml exec -T app php /var/www/apps/web/artisan migrate --force
```

### 6. Rebuild caches

```bash
docker compose -f docker-compose.prod.yml exec -T app php /var/www/apps/web/artisan optimize:clear
docker compose -f docker-compose.prod.yml exec -T app php /var/www/apps/web/artisan config:cache
docker compose -f docker-compose.prod.yml exec -T app php /var/www/apps/web/artisan route:cache
docker compose -f docker-compose.prod.yml exec -T app php /var/www/apps/web/artisan view:cache
```

### 7. Make a backup before each later release

```bash
./scripts/backup-db.sh
```

### 8. Use the manual release flow for later deployments

```bash
./scripts/release.sh
```

## Notes

- Production secrets must live only in `.env.production` on the VPS.
- Do not use `migrate:fresh` in production.
- Do not remove MySQL volumes as part of deployment.
- Local development remains on `docker-compose.yml` in the repository root.
