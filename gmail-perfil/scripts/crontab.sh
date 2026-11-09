#!/usr/bin/env bash
set -euo pipefail

# Cron: 0 8 * * 1 /ruta/al/proyecto/gmail-perfil/scripts/crontab.sh
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/.." && pwd)"

cd "${PROJECT_DIR}"
docker compose up --build app