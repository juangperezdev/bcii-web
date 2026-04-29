#!/usr/bin/env bash
# BCII — Genera/actualiza seed/bcii-seed.sql.gz para versionar en git
#
# Uso:
#   bin/seed-create.sh                              # usa URL guardada en seed/.url
#   bin/seed-create.sh https://dev.tudominio.com    # setea URL y genera
#
# El seed contiene la DB local con las URLs ya reescritas para el sitio
# online. Se commitea al repo y se importa desde wp-admin (WP Migrate Lite)
# en el sitio online tras el deploy git.
#
# La DB local NO se modifica (usamos `wp search-replace --export`).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SEED_DIR="${ROOT}/seed"
URL_FILE="${SEED_DIR}/.url"
LOCAL_URL="http://localhost:8000"

mkdir -p "${SEED_DIR}"

# Resolver URL remota
if [[ $# -ge 1 ]]; then
  REMOTE_URL="${1%/}"
  echo "${REMOTE_URL}" > "${URL_FILE}"
elif [[ -f "${URL_FILE}" ]]; then
  REMOTE_URL="$(cat "${URL_FILE}")"
else
  echo "✘ Falta URL remota."
  echo "   Primer uso: $0 https://dev.tudominio.com"
  exit 1
fi

echo "→ Generando seed con URLs ${LOCAL_URL} → ${REMOTE_URL}"

# Salida directa a /var/www/html/seed/ (montado en host como ./seed/)
docker exec bcii_wordpress wp --allow-root search-replace \
  "${LOCAL_URL}" "${REMOTE_URL}" \
  --all-tables \
  --skip-columns=guid \
  --export="/var/www/html/seed/bcii-seed.sql" >/dev/null

# Segundo pase: URLs JSON-escaped dentro de _elementor_data.
# wp search-replace unseraliza PHP pero no decodifica JSON-en-string, así
# que las URLs en formato `http:\/\/localhost:8000` (con `/` JSON-escaped)
# quedan crudas. En el dump SQL aparecen como `http:\\/\\/localhost:8000`
# (el SQL agrega otro layer de backslash escape).
LOCAL_PATTERN="${LOCAL_URL//\//\\\\/}"     # http:\\/\\/localhost:8000
REMOTE_PATTERN="${REMOTE_URL//\//\\\\/}"   # https:\\/\\/bcii.fortesting.us

LOCAL_PATTERN="${LOCAL_PATTERN}" REMOTE_PATTERN="${REMOTE_PATTERN}" \
  perl -i -pe 's|\Q$ENV{LOCAL_PATTERN}\E|$ENV{REMOTE_PATTERN}|g' "${SEED_DIR}/bcii-seed.sql"

# Comprimir (overwrite)
gzip -9 -f "${SEED_DIR}/bcii-seed.sql"

SIZE=$(du -h "${SEED_DIR}/bcii-seed.sql.gz" | cut -f1)
echo
echo "✔ ${SEED_DIR}/bcii-seed.sql.gz (${SIZE})"
echo
echo "Próximos pasos:"
echo "  1. git add seed/ wp-content/uploads/2026/04/ && git commit -m 'chore: update seed'"
echo "  2. git push  (deploya al online)"
echo "  3. En el wp-admin online: WP Migrate → Import → seleccionás bcii-seed.sql.gz"
echo "     (descargado del repo o desde ${REMOTE_URL%/}/seed/bcii-seed.sql.gz si está accesible)"
