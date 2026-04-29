# BCII — Seed de contenido para el sitio online

Esta carpeta contiene la **DB y media de marca** que se versionan en git
para mantener espejado el contenido del sitio online de desarrollo, sin
necesidad de SSH al server.

## Flujo de deploy de contenido

```
[ local ] → seed/bcii-seed.sql.gz → git push → [ online server filesystem ]
                                                         ↓
                                                  wp-admin manual:
                                                  WP Migrate → Import
```

## Workflow

### Vos (desarrollador) actualizás contenido

1. Editás contenido en local (páginas, menús, Elementor, etc.)
2. Generás un seed nuevo:
   ```bash
   bin/seed-create.sh                    # usa URL guardada
   # o, primera vez:
   bin/seed-create.sh https://dev.bcii.com
   ```
3. Commit + push:
   ```bash
   git add seed/ wp-content/uploads/2026/04/
   git commit -m "chore: actualizar seed de contenido"
   git push
   ```

### El sitio online recibe el deploy (automático vía git)

Tras el `git push`, el server online ya tiene los archivos nuevos en disco:
- Código del child theme actualizado
- `seed/bcii-seed.sql.gz` actualizado
- Media de marca en `wp-content/uploads/2026/04/` actualizada

**Pero la DB no se importa sola** — hay que disparar manualmente.

### Cliente / vos importan el seed (1 click en wp-admin)

1. Abrir el wp-admin del online: `https://dev.bcii.com/wp-admin/`
2. Ir a **Tools → WP Migrate**
3. Elegir **Import** → seleccionar el archivo:
   - **Opción a)** Descargar `seed/bcii-seed.sql.gz` desde el repo a tu máquina y subirlo con el form
   - **Opción b)** Si tu host expone el filesystem, indicarle ruta absoluta (no recomendado)
4. **Find & Replace**: ya viene con URLs reescritas. Dejá los campos vacíos.
5. Click **Import**.

## Qué incluye el seed

- Todas las páginas (con su `_elementor_data`)
- Menús, opciones del site, configuración del theme
- Posts, attachments metadata
- Permalinks, front page settings
- URLs **ya reescritas** al dominio online

## Qué NO incluye

- Media files que sube el cliente desde el wp-admin online → quedan ahí
- Configuración de wp-config.php (vive por entorno)
- Datos sensibles del entorno local (DB credentials, etc.)

## Seguridad

`seed/.htaccess` bloquea acceso HTTP público a esta carpeta. **No** se sirve
por web aunque esté en el repo.

Si usás Nginx en lugar de Apache, agregá esto al config del server:

```nginx
location /seed/ {
    deny all;
    return 403;
}
```

## ⚠️ Sobre el "source of truth"

Este flujo asume que **el contenido se edita en local y se empuja al online**.
Si el cliente edita contenido en el online, el próximo `Import` lo sobreescribe.

Para edición bidireccional, mejor usar [Opción B (mu-plugin)](../bin/) o
una versión Pro de WP Migrate con sync online → local.
