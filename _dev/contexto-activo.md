# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Confirmado (Fases 0-9 y 11 completas; Fase 10 en pausa/replanteada)

Fases 0-9 y 11 commiteadas y pusheadas. Fase 10 replanteada varias veces
(ver `_dev/roadmap.md`): plan conjunto de UX del admin con 5 piezas.

**Estado de la Fase 10 ahora mismo (2026-09-16):**
- Pieza 1 (descripción por pestaña en las 8 pestañas): **hecha**, pendiente
  de commit.
- Pieza 5 (Registro): se implementó primero una versión con dos vistas
  completas (Simple/Avanzada) — **se probó, no gustó, y se revirtió por
  completo** (código descartado, `admin/class-registry-table.php` vuelto a
  su versión commiteada). El enfoque definitivo, mucho más simple, queda
  documentado en el roadmap (pieza 5): una sola tabla como la de siempre,
  con el desplegable "Ver/editar Markdown" renombrado a "Ajustes
  avanzados" y ampliado para contener Puente/Hash/límite de
  caracteres/botones manual — **sin implementar todavía**, a la espera de
  retomarlo.
- Piezas 2, 3, 4 (Alcance/Exclusiones unificado, campos custom
  ACF/Meta Box/Pods, modo "todos los CPT"): sin empezar.

## Pendiente de confirmar

- **Sin commitear todavía**: pieza 1 (descripciones) + revert de la pieza
  5 + roadmap actualizado. Pendiente de tu permiso explícito para commit +
  push a `knowBaseDev`.
- Hay ~100 filas de datos de PRUEBA en la tabla `wookb_crawler_log` (Fase
  11, botón temporal ya retirado). Inofensivas, dentro del tope de 500.

## Pendiente real (sin empezar)

- Fase 10, piezas 2-5 (ver roadmap): Alcance/Exclusiones unificado con
  interruptor de 3 estados por término (Incluir/Excluir/Sin decidir, neutro
  por defecto), campos custom ACF/Meta Box/Pods, modo "todos los CPT", y
  "Ajustes avanzados" del Registro.
- UX3 (WooCommerce: checkboxes + prompt por campo, cambio de arquitectura
  de datos, requiere `rol-analista`) y UX4 (visibilidad del botón de
  generar documentos de tienda).
- Estilos inline en PHP (`style="width:100%"`, etc. en varias vistas):
  detectado, no abordado.
- [`llms-faq.md`](../llms-faq.md): contenido del sitio, en `.gitignore`, nunca se commitea.
- Bug sin repetir: botón del editor (Fase 1) sin feedback claro la primera
  vez que se probó.
- Ideas sueltas sin fase (`analisis-jet-geo.md`): tags dinámicos en
  prompts, onboarding por pasos.
- Cobertura de Google para avisos en tiempo real (Fase 8): IndexNow no lo
  soporta; requeriría la Search Console Indexing API (OAuth propio) — no
  planificada.
- Documentación OpenAPI (Fase 7) sin actualizar con las rutas de feeds
  (Fase 9) ni con las de crawlers (Fase 11) — decisión explícita.

## Decisiones de proceso (aplican siempre en este plugin)

- Commit/push: permiso explícito cada vez, mostrando status+diff+mensaje.
- Cambios de versión: preguntar antes.
- Subagente: solo trabajo grande/aislado; ajustes pequeños los hace la
  sesión principal.
- Nunca "hecho" en algo visual sin confirmación del usuario.
- Ningún color/estilo nuevo sin usar una variable real de Tabler o una
  clase ya definida — ver `_dev/guia-estilo-visual.html` primero.
- **Nuevo (2026-09-16):** antes de rediseñar una tabla/vista existente que
  ya funciona, confirmar el diseño exacto columna por columna con el
  usuario antes de escribir código — un rediseño completo sin esa
  confirmación previa se hizo y hubo que revertirlo entero.

## Relevo mínimo — siguiente paso

Confirmar commit + push del estado actual (descripciones + revert + roadmap).
Después: implementar la Fase 10 pieza 5 ("Ajustes avanzados" del Registro,
ya bien definida) o empezar por la pieza 2 (Alcance/Exclusiones unificado).
