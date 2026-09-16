# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Confirmado (Fases 0-9 y 11 completas; Fase 10 en pausa)

Fase 11 — Gestión de crawlers de IA — implementada, revisada de UX dos
veces tras feedback real del usuario, y confirmada en real (2026-09-16):
catálogo de ~28 bots con acción Permitir/Bloquear por bot, bloqueo real
vía `robots.txt` y `.htaccess` (con descarga obligatoria de seguridad y
vista previa del bloque), logs de accesos. Detalle completo en
[`CHANGELOG.md`](../CHANGELOG.md) y en `_dev/roadmap.md` (Fase 11).

Archivos nuevos/tocados en esta fase: `includes/class-crawler-catalog.php`,
`includes/class-crawler-log.php`, `includes/class-htaccess-guard.php`,
`includes/class-robots-txt-guard.php` (todos nuevos), `includes/class-plugin.php`,
`includes/class-scope.php`, `includes/class-queue.php`, `admin/class-admin.php`,
`admin/views/tab-visibilidad-ia.php`, `assets/wookb-theme.css`, `assets/admin.js`,
`woo-kb-generator.php` (bump `1.0.8.2` → `1.0.8.4`).

Ajustes de CSS aprovechables para el resto del plugin si aparece el mismo
problema en otra pestaña: falta de color en `<option>` de los `<select>` en
oscuro, tablas sin la clase `wp-list-table` (se quedan con blanco de
WordPress core), y falta de regla `:disabled` para botones.

## Pendiente de confirmar

- **Sin commitear todavía**: pendiente de tu permiso explícito para
  commit + push a `knowBaseDev`.
- Hay ~100 filas de datos de PRUEBA en la tabla `wookb_crawler_log` (se
  generaron con un botón temporal ya retirado, para ver el aspecto visual
  con volumen). Son inofensivas (dentro del tope de 500, se irán purgando
  solas), pero si las quieres fuera ya, dímelo — no tengo acceso a la BD
  desde la terminal para borrarlas yo mismo, habría que hacerlo desde
  phpMyAdmin/Adminer o añadiendo un botón "Vaciar logs" al admin.

## Pendiente real (sin empezar)

- Fase 10 — replanteada y en pausa: rediseño Alcance/Exclusiones (selectores
  casi idénticos, fácil confundir incluir/excluir) + campos personalizados
  ACF/Meta Box/Pods (el dato ya es seleccionable, falta etiqueta legible) +
  modo "todos los CPT públicos" en Scope. Requiere `evaluar-cambio`/`rol-analista`
  conjunto antes de tocar código (ver `_dev/roadmap.md`).
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
  prompts, onboarding por pasos.
- Cobertura de Google para avisos en tiempo real (Fase 8): IndexNow no lo
  soporta; requeriría la Search Console Indexing API (OAuth propio) como
  integración aparte — no planificada.
- Documentación OpenAPI (Fase 7) sin actualizar con las rutas de feeds
  (Fase 9) ni con las de crawlers (Fase 11) — decisión explícita, no son
  rutas con parámetros reales que documentar y no estaba pedido.

## Decisiones de proceso (aplican siempre en este plugin)

- Commit/push: permiso explícito cada vez, mostrando status+diff+mensaje.
- Cambios de versión: preguntar antes.
- Subagente: solo trabajo grande/aislado; ajustes pequeños los hace la
  sesión principal.
- Nunca "hecho" en algo visual sin confirmación del usuario.
- Ningún color/estilo nuevo sin usar una variable real de Tabler o una
  clase ya definida — ver `_dev/guia-estilo-visual.html` primero.

## Relevo mínimo — siguiente paso

Confirmar con el usuario si se commitea/pushea la Fase 11. Después: decidir
si se retoma la Fase 10 (con `evaluar-cambio`/`rol-analista` primero) o
UX3 (WooCommerce, con `rol-analista` primero por ser cambio de arquitectura
de datos).
