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

Use rebuild only when changing image-level dependencies (`Dockerfile`, system packages, PHP extensions):

```bash
docker compose up -d --build app
```

Shortcut:

```bash
./docker/use_docker_db.sh --build
docker compose up -d adminer
```

## Optional MAMP fallback

If you need temporary rollback to MAMP DB:

```bash
./docker/use_mamp_db.sh
```

This requires MAMP MySQL running on `127.0.0.1:8889`.

## Adminer quick access

- URL: `http://localhost:8082`
- System: `MySQL`
- Server (Docker DB): `db`
- Username: `root`
- Password: value of `DB_PASS` in `.env`
- Database: `lastplanneraia_dev`
