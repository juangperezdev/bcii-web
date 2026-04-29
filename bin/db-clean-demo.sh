#!/usr/bin/env bash
# BCII — Limpia contenido demo de Execor del DB local
#
# Borra: posts, elementor templates demo (excepto Default Kit), attachments
# que NO sean assets de marca BCII (logos / retratos / referencias).
#
# Hace backup antes en backups/pre-clean-*.sql.gz por si necesitamos rollback.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TS="$(date +%Y%m%d-%H%M%S)"

# IDs de assets BCII a preservar
KEEP_ATTACHMENTS=(9999 10000 10001 10002 10003 10004 10005 10006)
# Elementor Default Kit
KEEP_ELEMENTOR_LIBRARY=(5 6)

# 1. Backup
echo "→ Backup pre-clean a backups/pre-clean-${TS}.sql.gz"
mkdir -p "${ROOT}/backups"
docker exec bcii_wordpress wp --allow-root db export "/var/www/html/backups/pre-clean-${TS}.sql" --add-drop-table >/dev/null
gzip -9 "${ROOT}/backups/pre-clean-${TS}.sql"

# 2. Borrar todos los posts (post_type=post) — sin blog por ahora
echo "→ Borrando demo posts"
docker exec bcii_wordpress bash -c '
  ids=$(wp --allow-root post list --post_type=post --posts_per_page=-1 --field=ID --format=ids)
  if [ -n "$ids" ]; then wp --allow-root post delete $ids --force >/dev/null && echo "   ✓ $(echo $ids | wc -w) posts"; fi
'

# 3. Borrar elementor_library excepto Kit
echo "→ Borrando elementor_library demo (preserva Default Kit)"
KEEP_LIB_CSV="$(IFS=,; echo "${KEEP_ELEMENTOR_LIBRARY[*]}")"
docker exec bcii_wordpress bash -c "
  ids=\$(wp --allow-root post list --post_type=elementor_library --posts_per_page=-1 --field=ID --format=ids)
  drop=\$(echo \$ids | tr ' ' '\n' | grep -vE '^(${KEEP_LIB_CSV//,/|})\$' | tr '\n' ' ')
  if [ -n \"\$drop\" ]; then wp --allow-root post delete \$drop --force >/dev/null && echo \"   ✓ \$(echo \$drop | wc -w) templates\"; fi
"

# 4. Borrar attachments excepto los nuestros
echo "→ Borrando attachments demo"
KEEP_ATT_CSV="$(IFS=,; echo "${KEEP_ATTACHMENTS[*]}")"
docker exec bcii_wordpress bash -c "
  ids=\$(wp --allow-root post list --post_type=attachment --posts_per_page=-1 --field=ID --format=ids)
  drop=\$(echo \$ids | tr ' ' '\n' | grep -vE '^(${KEEP_ATT_CSV//,/|})\$' | tr '\n' ' ')
  if [ -n \"\$drop\" ]; then wp --allow-root post delete \$drop --force >/dev/null && echo \"   ✓ \$(echo \$drop | wc -w) attachments\"; fi
"

# 5. Limpiar revisiones huérfanas y trash
echo "→ Limpiando revisiones / trash"
docker exec bcii_wordpress wp --allow-root post delete \$(docker exec bcii_wordpress wp --allow-root post list --post_status=trash --posts_per_page=-1 --field=ID --format=ids 2>/dev/null) --force >/dev/null 2>&1 || true
docker exec bcii_wordpress wp --allow-root db query "DELETE FROM wp_posts WHERE post_type='revision';" 2>&1 | tail -1

# 6. Optimizar tablas
echo "→ Optimizando DB"
docker exec bcii_wordpress wp --allow-root db optimize >/dev/null

echo
echo "✔ Cleanup terminado"
echo
echo "Estado actual:"
docker exec bcii_wordpress wp --allow-root post list --post_type=any --post_status=publish --posts_per_page=-1 --fields=ID,post_type,post_title 2>&1 | wc -l
echo "  publicaciones publish (incluye header)"
docker exec bcii_wordpress wp --allow-root post list --post_type=attachment --posts_per_page=-1 --field=ID 2>&1 | tail -n +2 | wc -l
echo "  attachments"
