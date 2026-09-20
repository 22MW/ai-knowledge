# Traducciones JavaScript de AI Knowledge

## Qué hace el script

`_dev/make_i18n_json.py` genera los archivos JSON que WordPress necesita para
cargar las traducciones JavaScript definidas en los archivos `.po` de
`languages/`.

El script es manual: no se ejecuta al guardar un `.po`, al activar el plugin ni
durante ninguna petición de WordPress. Tampoco genera archivos `.mo`.

## Ejecución manual

Desde la carpeta del plugin:

```bash
cd "/Users/22mw/Local Sites/plugins/app/public/wp-content/plugins/ai-knowledge"
python3 _dev/make_i18n_json.py
```

En LocalWP el script busca automáticamente el PHP instalado en Local y usa
`/usr/local/bin/wp` si WP-CLI no está en el `PATH`. También puedes indicarlos
manualmente con rutas reales:

La configuración queda en `_dev/i18n-config.yml`. También puedes sobrescribir
las rutas en la ejecución:

```bash
PHP_BIN="/ruta/real/a/php" WP_BIN="/usr/local/bin/wp" \
python3 _dev/make_i18n_json.py
```

Si `php` y `wp` están disponibles en el `PATH`, basta con:

```bash
./_dev/make-i18n-json.sh
```

El script recorre todos los `.po` de `languages/` y genera el JSON con hash
que WordPress asocia al JavaScript del plugin. Si no hay PHP, WP-CLI o ningún
`.po`, termina mostrando el motivo.

## Flujo por idioma

1. Editar o crear `languages/ai-knowledge-{locale}.po`.
2. Completar las traducciones `msgstr`.
3. Ejecutar manualmente el script.
4. Revisar los JSON generados.
5. Generar el `.mo` por separado cuando se necesiten traducciones PHP.

No se crean copias traducidas de `docs/` con este script.

## Estado actual

- El catálogo inglés `languages/ai-knowledge-en_US.po` fue traducido y
  completado manualmente por el propietario del plugin.
- El `.mo` no lo genera este proyecto: se crea aparte cuando se prepare la
  distribución PHP.
- El JSON JavaScript se genera manualmente con `_dev/make_i18n_json.py`.
