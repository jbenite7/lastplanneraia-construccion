#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

DOCKER_APP_DB_HOST=db \
DOCKER_APP_DB_PORT=3306 \
docker compose -f "${PROJECT_ROOT}/docker-compose.yml" up -d "$@" db app
