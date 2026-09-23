# Release notes

## 1.1.3.4 — desarrollo — 2026-09-23

- Añadido el prompt GEO reutilizable en bienvenida y resumen final, con copia
  directa y URL dinámica del sitio.
- Mejorada la pantalla de archivos del servidor y la verificación de reglas.
- Incluidos los ajustes visuales pendientes del asistente.

## 1.1.3.3 — desarrollo — 2026-09-22

- Corregida la actualización y verificación de `robots.txt` desde el plugin y
  el asistente, comparando únicamente las reglas gestionadas por AI Knowledge.
- Añadida la pantalla de archivos del servidor con descarga manual de
  `.htaccess`, actualización protegida de `robots.txt` y comprobación AJAX.

## 1.1.3.2 — desarrollo — 2026-09-21

- Ajustado el asistente de configuración: navegación AJAX, estados, cargador
  visual y formularios completos por pantalla.
- Mejorada la gestión de crawlers por categorías, con acciones masivas y
  descripciones agrupadas sin repetir contenido.
- Revisada la explicación de visibilidad, `robots.txt` y `.htaccess`.

## 1.1.3.1 — desarrollo — 2026-09-20

- Sincronizados los idiomas de interfaz español, catalán, alemán, inglés y
  francés con catálogos `.po`, `.mo` y JSON.
- Actualizados los índices de documentación pública e interna para explicar
  los idiomas disponibles y pedir contacto si falta o falla una traducción.
- Reorganizados los documentos de trabajo temporales bajo `_dev/temp/`.

## 1.1.3 — 2026-09-20

- Release estable con internacionalización PHP/JavaScript en español, catalán
  e inglés.
- Documentación por locale con fallback a `docs/`.
- Consolidación de crawlers, filtros y reglas del servidor de `1.1.2.2`.
- Validación técnica superada: 47 archivos PHP, 3 JSON de traducción, versión
  y `git diff --check` correctos.
- ZIP limpio generado sin `_dev/`, Git, `.DS_Store`, logs ni entornos locales.
