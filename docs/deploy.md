# Deploy

Quest Board is one app per team: its own database, its own branding. `docker-compose.yml` runs everything, on any host with Docker and on Dokploy.

- Image: `Dockerfile` (serversideup/php `8.4-fpm-nginx`, pinned to v5.0.3)
- Stack: `docker-compose.yml`: `app` (nginx + PHP-FPM, port 8080), `queue` (queue worker), `scheduler` (`schedule:work`) and `mysql`
- Volumes: `mysql-data` (the database) and `app-storage` (uploaded logos and images); both survive redeploys

## 1. Environment

Copy `.env.example` to `.env` and set at least:

| Variable                | Value                                                                  |
| ----------------------- | ---------------------------------------------------------------------- |
| `APP_KEY`               | see "Generate APP_KEY" below                                           |
| `APP_URL`               | the address people open, e.g. `https://quests.example.com`             |
| `DB_PASSWORD`           | a long random password; the MySQL container uses it on its first start |
| `APP_DISPLAY_TIMEZONE`  | a PHP time zone for showing dates (default `UTC`)                      |
| `SESSION_SECURE_COOKIE` | `true` when `APP_URL` starts with `https://`                           |

The compose file sets `AUTORUN_ENABLED` (true on `app` only, so migrations and caches run once per start), `HEALTHCHECK_PATH=/up` and `PHP_OPCACHE_ENABLE=1` itself. Never commit a real `.env`.

### Generate APP_KEY

Generate it once and keep it; changing it logs everyone out and breaks anything encrypted.

```bash
echo "base64:$(openssl rand -base64 32)"
```

Paste the printed `base64:...` value into `APP_KEY`.

## 2. Start

```bash
docker compose up -d
```

The first start builds the image, which takes a few minutes. `app` waits for MySQL to be healthy, runs `migrate --force` and the cache steps, and only then starts nginx. A failed step stops the container. `queue` and `scheduler` wait for `app` to be healthy.

## 3. Create the first admin

There is no sign-up, so create the first admin from the `app` container:

```bash
docker compose exec app php artisan quests:create-admin
```

It asks for a name, email and password. For scripts, pass `--name`, `--email` and `--password`. In production the password needs 12 or more characters with mixed case, numbers and symbols. Every other user is added by an admin in Settings › Members.

## 4. HTTPS and a domain

The app serves plain HTTP on `WEB_PORT` (default 8080). Put a reverse proxy in front for HTTPS (Caddy, Traefik, CloudFront, a load balancer). Laravel trusts the `X-Forwarded-*` headers (`bootstrap/app.php`) and generates `https://` links when `APP_URL` starts with `https://`.

### Behind a reverse proxy

- Set `APP_URL=https://quests.example.com` and `SESSION_SECURE_COOKIE=true`.
- Keep `WEB_BIND=127.0.0.1` and point the proxy at `127.0.0.1:8080` (or your `WEB_PORT`).
- A proxy on another machine, such as CloudFront, needs `WEB_BIND=0.0.0.0` plus a firewall that only lets that proxy reach the port.
- An existing install reached this way must set `WEB_BIND` before updating: the default binding only accepts connections from the same host.

The host port is bound to `127.0.0.1` by default. Set `WEB_BIND=0.0.0.0` only for a quick test without a proxy, or for a proxy on another machine.

### On Dokploy

Create a Docker Compose service from this repo, turn on **Isolated Deployment**, paste your `.env` into the **Environment** tab, and add your domain in the **Domains** tab on the **`app`** service, container port **8080**. If port 8080 is already taken on the host, set `WEB_PORT` to another value.

The domain reaches the `app` service over the Docker network, not the host port, so `WEB_BIND` should not affect it. This setup follows the Dokploy docs but has not been tried on a live Dokploy.

## 5. Check the queue and scheduler

- Queue: run `docker compose exec app php artisan quests:ping`, then look for `PingJob ran at …` in `docker compose logs queue`.
- Scheduler: wait two minutes and look for `Heartbeat …` lines in `docker compose logs scheduler` (one per minute).

## 6. Logs

`LOG_CHANNEL=stderr` sends Laravel logs to the container output: `docker compose logs app` for requests and errors, `queue` for jobs, `scheduler` for scheduled commands.

## 7. Update

```bash
git pull
docker compose up -d --build
```

Migrations run on start. The database and uploads stay in their volumes.

## Running a public demo

Public demo mode wipes and re-seeds the database every day. Never enable it on a real install.

Set these values in the demo install's `.env`:

```dotenv
DEMO_MODE=true
DEMO_RESET_TIME=00:00
APP_MAINTENANCE_DRIVER=cache
APP_MAINTENANCE_STORE=shared
```

`DEMO_RESET_TIME` is optional. It uses `HH:MM` in UTC. The `shared` cache store keeps maintenance mode in the `app-storage` volume, so the `app`, `queue` and `scheduler` containers all see it. A new install has no demo data yet, so run a reset once after the first start (this replaces the "Create the first admin" step). Run a reset by hand any time with:

```bash
docker compose exec scheduler php artisan quests:demo-reset
```

The command refuses to run unless `DEMO_MODE=true`. It puts the app in maintenance mode, rebuilds the database with the seeders, deletes uploaded files, clears the cache, and lifts maintenance mode, even if a step fails. It logs when it starts and when it finishes or fails. The scheduler runs it every day at `DEMO_RESET_TIME`.

The seeded accounts (`admin@example.com` and four others) all use the password `password`. The login page and a dismissible banner show this to visitors in demo mode.

Demo mode blocks password changes and resets, email changes, and changes to the seeded accounts' roles or active access. These guards keep the shared sign-in accounts available.
