#!/usr/bin/env bash
# BCII — Exporta la DB local a backups/bcii-YYYYMMDD-HHMMSS.sql.gz
# Uso: bin/db-export.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT_DIR="${ROOT}/backups"
TS="$(date +%Y%m%d-%H%M%S)"
OUT="${OUT_DIR}/bcii-${TS}.sql"

mkdir -p "${OUT_DIR}"

# wp db export desde el container; la salida va al volumen montado, queda en host
docker exec bcii_wordpress wp --allow-root db export "/var/www/html/backups/bcii-${TS}.sql" --add-drop-table

gzip -9 "${OUT}"
echo "✔ Backup: ${OUT}.gz ($(du -h "${OUT}.gz" | cut -f1))"
