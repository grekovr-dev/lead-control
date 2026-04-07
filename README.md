# lead-control

Lead generation and conversion tracking system.

This repository uses a monorepo layout:

- `apps/web` is the Laravel delivery layer
- `src/Inbound` contains the domain and application core
- `docker-compose.yml` is the local development stack
- `docker-compose.prod.yml` is the production stack for a single VPS

## VPS bootstrap

Use this flow for the first production deployment on a fresh VPS.

### 1. Prepare an empty directory and clone the repository

```bash
mkdir -p /srv/lead-control
cd /srv/lead-control
git clone git@github.com:<org>/<repo>.git .
```

If the repository is already cloned, update it instead:

```bash
cd /srv/lead-control
git pull --ff-only
```

### 2. Create the production env file

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
- `LETS_ENCRYPT_PRIMARY_DOMAIN`
- `LETS_ENCRYPT_DOMAINS`
- `LETS_ENCRYPT_EMAIL`

For the very first bootstrap, `APP_URL` can temporarily point to the server IP address.

### 3. Make sure the production nginx template is present

The production stack uses:

- `docker/nginx.prod.conf.template`

The certbot directories are already present in the repository checkout on the VPS, so you do not need to create them manually.

### 4. Run the first production release

Use the release script for the initial bootstrap:

```bash
./scripts/release.sh
```

This script will:

- skip the database backup if the database is not running yet
- prepare TLS material for nginx through the `*-active` alias
- start the production stack
- run migrations
- rebuild Laravel caches
- restart Horizon

### 5. Issue the real Let's Encrypt certificate

After the stack is up and DNS points to the VPS, issue the production certificate:

```bash
./scripts/letsencrypt.sh issue
```

If a real Let's Encrypt certificate already exists, this command will not request a new one. It will only sync the active certificate alias and reload nginx.

### 6. Understand the certificate flow

The production TLS flow uses three commands:

- `./scripts/letsencrypt.sh bootstrap`
- `./scripts/letsencrypt.sh issue`
- `./scripts/letsencrypt.sh renew`

Operational behavior:

- `bootstrap` prepares the certificate path used by nginx
- nginx always reads the certificate from `docker/certbot/conf/live/<primary-domain>-active`
- if a real certificate lineage already exists, `bootstrap` points `*-active` to that lineage
- if no real certificate exists yet, `bootstrap` generates a temporary self-signed certificate in `docker/certbot/conf/live/<primary-domain>-bootstrap` and points `*-active` to it
- `issue` requests the first real Let's Encrypt certificate only when no real certificate lineage exists yet
- `renew` delegates renewal to `certbot renew` and then reloads nginx

To test renewal safely without issuing a real renewal, use:

```bash
./scripts/letsencrypt.sh renew --dry-run
```

### 7. Verify HTTPS

```bash
curl -I http://your-domain
curl -I https://your-domain
```

You should see HTTP redirecting to HTTPS and HTTPS serving the site normally.

### 8. Use the manual release flow for later deployments

```bash
./scripts/release.sh
```

## Notes

- Production secrets must live only in `.env.production` on the VPS.
- Do not use `migrate:fresh` in production.
- Do not remove MySQL volumes as part of deployment.
- Keep only one active certbot certificate lineage for a domain whenever possible to make `certbot renew` predictable.
- Local development remains on `docker-compose.yml` in the repository root.
