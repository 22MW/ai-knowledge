# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Confirmado (Fases 0-9 y 11 completas; Fase 10 en curso)

Fases 0-9 y 11 commiteadas y pusheadas. Fase 10 replanteada varias veces
(ver `_dev/roadmap.md`): plan conjunto de UX del admin con 5 piezas.

**Estado de la Fase 10 ahora mismo (2026-09-16):**
- Pieza 1 (descripción por pestaña en las 8 pestañas): **hecha y
  commiteada** (`568bf17`).
- Pieza 5 (Registro, "Ajustes avanzados"): **hecha**, pendiente de commit.
  Puente/Hash movidos dentro del desplegable renombrado "Ajustes
  avanzados"; quitado el campo de límite puntual de la columna Acciones;
  añadido selector "por página" (20/50/100) en la toolbar (fuera del plan
  original, pedido aparte); reordenado "Generar por ID o URL" antes de la
  toolbar. Sin probar todavía en real por el usuario.
- Piezas 2, 3, 4 (Alcance/Exclusiones unificado, campos custom
  ACF/Meta Box/Pods, modo "todos los CPT"): sin empezar.

## Pendiente de confirmar

- **Sin commitear todavía**: pieza 5 completa + fix de encoding en
  `assets/admin.js` (emojis del botón de tema) + edición manual del
  usuario en `_dev/guia-estilo-visual.html` + roadmap/changelog
  actualizados. Pendiente de tu permiso explícito para commit + push a
  `knowBaseDev`.
- No se ha podido ejecutar `php -l` en esta sesión (no hay binario `php`
  accesible en este entorno) — validar en real en LocalWP.
- Hay ~100 filas de datos de PRUEBA en la tabla `wookb_crawler_log` (Fase
  11, botón temporal ya retirado). Inofensivas, dentro del tope de 500.

## Pendiente real (sin empezar)

- Fase 10, piezas 2-4 (ver roadmap): Alcance/Exclusiones unificado con
  interruptor de 3 estados por término (Incluir/Excluir/Sin decidir, neutro
  por defecto), campos custom ACF/Meta Box/Pods, modo "todos los CPT".
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

Confirmar commit + push del estado actual (pieza 5 completa). Después:
probar en real en LocalWP (no se pudo validar con `php -l` en esta
sesión) y seguir con la pieza 2 (Alcance/Exclusiones unificado).
