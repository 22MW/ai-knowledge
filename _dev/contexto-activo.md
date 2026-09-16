# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Confirmado (Fases 0-8 completas)

Fase 8 — Aviso a buscadores en tiempo real (IndexNow) — implementada y
confirmada en real (2026-09-16): `HTTP 202` de `api.indexnow.org` al
guardar un producto. Detalle completo en [`CHANGELOG.md`](../CHANGELOG.md)
y en `_dev/roadmap.md` (Fase 8).

Archivos nuevos/tocados en esta fase: `includes/class-indexnow.php`
(nuevo), `includes/class-plugin.php`, `includes/class-scope.php`,
`includes/class-sync.php`, `admin/class-admin.php`,
`admin/views/tab-ajustes.php`.

## Pendiente de confirmar

- **Sin commitear todavía**: los cambios de Fase 8 (código + CHANGELOG.md +
  readme.txt + roadmap.md + visual.html) están en el working tree de
  `knowBaseDev`, sin commit ni push — pendiente de permiso explícito.
- Flush de permalinks: la URL de la clave IndexNow (`/{key}.txt`) necesita
  que se guarden los Enlaces Permanentes (o un flush) para responder en
  producción tras el deploy — mismo requisito ya conocido de `llms.txt`.

## Pendiente real (sin empezar)

- Fases 9-11 del roadmap.
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
- Cobertura de Google para avisos en tiempo real: IndexNow no lo soporta;
  requeriría la Search Console Indexing API (OAuth propio) como
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

Confirmar con el usuario si se commitea/pushea la Fase 8. Después: decidir
si sigue la Fase 9 o UX3 (WooCommerce, con `rol-analista` primero por ser
cambio de arquitectura de datos).
