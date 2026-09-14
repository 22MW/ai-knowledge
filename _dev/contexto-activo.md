# Contexto activo

## Plugin objetivo

Plugin actualmente instalado en desarrollo como `woo-kb-generator`, rama
`knowBaseDev`.

## Decisión de identidad aprobada

- Nombre visible: **AI Knowledge & Visibility**
- Nombre corto: **AI Knowledge**
- Slug/carpeta objetivo: `ai-knowledge`
- Text Domain objetivo: `ai-knowledge`
- Namespace de código: se mantiene `WOOKB`/`wookb_*` por ahora.

## Decisión de alcance aprobada

El núcleo sigue siendo la generación de documentos vía IA (OpenAI) +
integración Support Genix. Se añade encima una capa de visibilidad/exposición
para crawlers y agentes IA (JSON estructurado sin IA, Markdown público
descubrible, JSON-LD, panel de diagnóstico). Detalle completo en
`_dev/decisiones.md` y `_dev/roadmap.md`.

## Situación verificada

- No existen instalaciones reales ni datos de producción que conservar.
- Repositorio local con remoto privado y rama `knowBaseDev` publicada.
- Informe de auditoría técnica en `_dev/estado-plugin-informe.md` (estructura,
  hooks, seguridad — sigue vigente como inventario factual).

## Relevo mínimo

No implementar todavía. Siguiente paso: diseñar la Fase 0 (renombrado) y
Fase 1 (endpoint REST de solo lectura) — candidato para el subagente
`arquitecto` cuando el usuario dé permiso para avanzar.
