# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Confirmado (Fases 0-9 completas)

Fase 9 — Feeds especializados — implementada y confirmada en real
(2026-09-16): `feeds/products.xml` (Google Merchant) y `feeds/content.json`,
enlazados desde `llms.txt` (sección `## Feeds`) y desde el `<head>` de
todas las páginas (`<link rel="alternate">` sitewide). Detalle completo en
[`CHANGELOG.md`](../CHANGELOG.md) y en `_dev/roadmap.md` (Fase 9).

Archivos tocados en esta fase: `includes/class-rest-content.php` (rutas +
`print_feed_links()`), `includes/class-llms-txt.php` (sección `## Feeds`).

## Pendiente de confirmar

- **Sin commitear todavía**: los cambios de Fase 9 (código + CHANGELOG.md +
  readme.txt + roadmap.md + visual.html) están en el working tree de
  `knowBaseDev`, sin commit ni push — pendiente de permiso explícito.
- Excluido a propósito (decisión, no pedido): actualizar el documento
  OpenAPI de la Fase 7 con las 2 rutas de feeds — son formatos fijos sin
  parámetros reales que documentar.

## Pendiente real (sin empezar)

- Fases 10-11 del roadmap.
- UX3 (WooCommerce: checkboxes + prompt por campo, cambio de arquitectura
  de datos, requiere `rol-analista`) y UX4 (visibilidad del botón de
  generar documentos de tienda) — ver `_dev/roadmap.md`, sección "UX
  pendiente de rediseño".
- Estilos inline en PHP (`style="width:100%"`, etc. en varias vistas):
  detectado, no abordado — pendiente de decidir si se mueve a CSS.
- [`llms-faq.md`](../llms-faq.md): contenido del sitio, en `.gitignore`, nunca se commitea.
- Bug sin repetir: botón del editor (Fase 1) sin feedback claro la primera
  vez que se probó.
- Ideas sueltas sin fase (`analisis-jet-geo.md`): tags dinámicos en
  prompts, onboarding por pasos, modo "todos los CPT" en `Scope`.
- Cobertura de Google para avisos en tiempo real (Fase 8): IndexNow no lo
  soporta; requeriría la Search Console Indexing API (OAuth propio) como
  integración aparte — no planificada.

## Decisiones de proceso (aplican siempre en este plugin)

- Commit/push: permiso explícito cada vez, mostrando status+diff+mensaje.
- Cambios de versión: preguntar antes.
- Subagente: solo trabajo grande/aislado; ajustes pequeños los hace la
  sesión principal.
- Nunca "hecho" en algo visual sin confirmación del usuario.
- Ningún color/estilo nuevo sin usar una variable real de Tabler o una
  clase ya definida — ver `_dev/guia-estilo-visual.html` primero.

## Relevo mínimo — siguiente paso

Confirmar con el usuario si se commitea/pushea la Fase 9. Después: decidir
si sigue la Fase 10, la Fase 11 o UX3 (WooCommerce, con `rol-analista`
primero por ser cambio de arquitectura de datos).
