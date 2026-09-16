# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Commiteado y pusheado (confirmado en `git log`)

10 commits en `knowBaseDev`, Fases 0-6 del roadmap completas, UX2 (Carga
inicial rediseñada) y el ajuste general de largo de texto (commit
`768d239`, sobre `334492e` — Fase 6 — y `20ae873` — rediseño CSS). Detalle
completo en [`CHANGELOG.md`](../CHANGELOG.md); regla visual permanente en
[`decisiones.md`](decisiones.md).

## En curso ahora mismo

**Fase 7 — API pública documentada.** Evaluada y planificada, plan
aprobado por el usuario, pendiente de implementar:
- Documento OpenAPI 3.0 **real, escrito a mano** (no el índice nativo de
  WordPress, que no es formato OpenAPI — hallazgo de `evaluar-cambio`) en
  `GET /wp-json/ai-knowledge/v1/openapi.json`.
- Describe las 2 rutas ya existentes de Fase 3 (`/content/{id}`,
  `/{post_type}`) y su respuesta tipada según `Extractor_Base`/`Extractor_Woo`.
- Detalle completo del plan en `_dev/roadmap.md`, sección Fase 7.
- Archivo a tocar: `includes/class-rest-content.php` (nueva ruta + método
  `get_openapi_spec()`).

## Pendiente real (sin empezar)

- Fases 8-11 del roadmap.
- UX3 (WooCommerce: checkboxes + prompt por campo, cambio de arquitectura
  de datos, requiere `rol-analista`) y UX4 (visibilidad del botón de
  generar documentos de tienda) — ver `_dev/roadmap.md`, sección "UX
  pendiente de rediseño".
- Estilos inline en PHP (`style="width:100%"`, etc. en varias vistas):
  detectado, no abordado — pendiente de decidir si se mueve a CSS.
- [`llms-faq.md`](../llms-faq.md): contenido del sitio, sin commitear a propósito.
- Bug sin repetir: botón del editor (Fase 1) sin feedback claro la primera
  vez que se probó.
- Ideas sueltas sin fase (`analisis-jet-geo.md`): tags dinámicos en
  prompts, onboarding por pasos, modo "todos los CPT" en `Scope`.

## Decisiones de proceso (aplican siempre en este plugin)

- Commit/push: permiso explícito cada vez, mostrando status+diff+mensaje.
- Cambios de versión: preguntar antes.
- Subagente: solo trabajo grande/aislado; ajustes pequeños los hace la
  sesión principal.
- Nunca "hecho" en algo visual sin confirmación del usuario.
- Ningún color/estilo nuevo sin usar una variable real de Tabler o una
  clase ya definida — ver `_dev/guia-estilo-visual.html` primero.

## Relevo mínimo — siguiente paso

Implementar Fase 7 (plan ya aprobado, ver arriba) con `implementar-cambio`.
Después: decidir con el usuario si sigue la Fase 8 o UX3 (WooCommerce, con
`rol-analista` primero por ser cambio de arquitectura de datos).
