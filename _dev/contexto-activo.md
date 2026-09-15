# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Commiteado y pusheado (confirmado en `git log`)

9 commits en `knowBaseDev`, Fases 0-6 del roadmap completas, los bugs de la
primera ronda de QA arreglados, rediseño del CSS del admin (commit
`20ae873`) y Fase 6 — panel "Visibilidad IA" (commit `334492e`). Detalle en
[`CHANGELOG.md`](../CHANGELOG.md); regla visual permanente en
[`decisiones.md`](decisiones.md).

## Confirmado por el usuario, autorizado para commit (pendiente de commitear)

**UX2 — rediseño de "Carga inicial", completo:**
- Botón único sustituido por "Generar pendientes" (comportamiento de
  siempre) + "Reiniciar todo" (fuerza regenerar también lo ya sincronizado,
  confirmación JS fuerte), en fila. `$force` propagado por toda la cadena
  async en `includes/class-queue.php`.
- Límite diario/tamaño de lote/debounce movidos aquí desde Ajustes, acción
  de guardado propia `wookb_save_queue_settings` (`Scope::update_settings()`
  con merge — no reescribe el resto de Ajustes; se corrigió a mitad de
  implementación un bug real donde `save_settings()` seguía reseteando
  esas 3 claves a su valor por defecto).
- Resumen "Total de documentos" visible en todas las pestañas, no solo Registro.
- Botones de variantes ya existentes (neutro/primario), sin crear estilo nuevo.

**Nuevo ajuste general "Largo del texto generado (caracteres)"** en
Ajustes: antes fijo en código (`Generator::BODY_CHAR_LIMIT = 1000`), sin
ningún sitio del admin donde verlo o cambiarlo. El límite por documento del
Registro (Fase 1) sigue teniendo prioridad si está puesto.

Archivos: `includes/class-queue.php`, `admin/class-admin.php`,
`admin/views/tab-carga-inicial.php`, `admin/views/tab-ajustes.php`,
`assets/wookb-theme.css`, `includes/class-generator.php`,
`includes/class-scope.php`.

## Pendiente real (sin empezar)

- Fases 7-11 del roadmap.
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

Con el commit de UX2 + límite de caracteres hecho: decidir con el usuario
si sigue la Fase 7 (API pública documentada) o UX3 (WooCommerce, con
`rol-analista` primero por ser cambio de arquitectura de datos).
