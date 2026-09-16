# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Commiteado y pusheado (confirmado en `git log`)

13 commits en `knowBaseDev`, Fases 0-7 del roadmap completas: UX2 (Carga
inicial rediseñada) + ajuste general de largo de texto (`768d239`) y
Fase 7 — OpenAPI + enlace desde `llms.txt` (`f58fc94`), sobre `334492e`
(Fase 6) y `20ae873` (rediseño CSS). Detalle completo en
[`CHANGELOG.md`](../CHANGELOG.md); regla visual permanente en
[`decisiones.md`](decisiones.md).

## Pendiente de confirmar

- `CHANGELOG.md`: tiene la entrada de Fase 7 escrita pero sin commitear
  todavía — revisar si sigue así antes de continuar.

## Pendiente real (sin empezar)

- Fases 8-11 del roadmap.
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

## Decisiones de proceso (aplican siempre en este plugin)

- Commit/push: permiso explícito cada vez, mostrando status+diff+mensaje.
- Cambios de versión: preguntar antes.
- Subagente: solo trabajo grande/aislado; ajustes pequeños los hace la
  sesión principal.
- Nunca "hecho" en algo visual sin confirmación del usuario.
- Ningún color/estilo nuevo sin usar una variable real de Tabler o una
  clase ya definida — ver `_dev/guia-estilo-visual.html` primero.

## Relevo mínimo — siguiente paso

Cerrar el commit pendiente de `CHANGELOG.md` (Fase 7 ya confirmada en
navegador). Después: decidir con el usuario si sigue la Fase 8 o UX3
(WooCommerce, con `rol-analista` primero por ser cambio de arquitectura de
datos).
