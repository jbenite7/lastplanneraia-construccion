# Docker Operation Guide

Compose stack name: `last-planner-aia`.

## Ports

- App (Docker Apache): `http://localhost:8081`
- DB (Docker MySQL host port): `127.0.0.1:3307`
- Adminer (MySQL web UI): `http://localhost:8082`

## Docker-first startup

The app is configured to use Docker MySQL by default (`DB_HOST=db`, `DB_PORT=3306`).

```bash
docker compose up -d --build db app adminer
```

## Development mode (live code changes)

This repository includes `docker-compose.override.yml` with a bind mount:

- `./:/var/www/html`

That means local PHP/JS/CSS changes are reflected immediately in the `app` container without rebuilding the image.

```bash
docker compose up -d app
```

## Password reset setup

If you want the forgot-password flow to work in Docker, make sure your `.env` includes:

- `APP_URL=http://localhost:8081`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

The reset flow also requires the database patch `database/patches/20260329_create_password_reset_tokens.sql`.

Typical local verification:

```bash
docker compose up -d --build db app adminer
docker compose exec app php -r "require 'vendor/autoload.php'; echo getenv('APP_URL') ?: 'APP_URL missing';"
```

Use rebuild only when changing image-level dependencies (`Dockerfile`, system packages, PHP extensions):

```bash
docker compose up -d --build app
```

Shortcut:

```bash
./docker/use_docker_db.sh --build
docker compose up -d adminer
```

## Adminer quick access

- URL: `http://localhost:8082`
- System: `MySQL`
- Server (Docker DB): `db`
- Username: `root`
- Password: value of `DB_PASS` in `.env`
- Database: `lastplanneraia_dev`
