# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Confirmado (Fases 0-11 completas)

Fase 10 (plan conjunto de UX del admin, 5 piezas) completa y probada en
real por el usuario (2026-09-16): descripción por pestaña, pestaña
"Contenido" (fusión Alcance+Exclusiones con checkbox simple por término,
modo "todos los CPT", etiquetas de campos custom), y "Ajustes avanzados"
en el Registro. Ver `_dev/roadmap.md` para el detalle completo de cada
pieza.

Durante las pruebas se encontró y corrigió un bug real heredado de la
pieza 5: la fila expandida del Registro anidaba `<form>` dentro del
formulario grande de selección múltiple (HTML inválido), rompiendo
"Borrar seleccionados"/"Regenerar seleccionados" en silencio.

Sin commitear todavía: pendiente de tu permiso.

## Pendiente de confirmar

- No se ha podido ejecutar `php -l` en ninguna sesión reciente (no hay
  binario `php` accesible en este entorno) — validar sintaxis en real en
  LocalWP antes de dar la Fase 10 por cerrada del todo.
- Hay ~100 filas de datos de PRUEBA en la tabla `wookb_crawler_log` (Fase
  11, botón temporal ya retirado). Inofensivas, dentro del tope de 500.

## Pendiente real (sin empezar)

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
- Antes de rediseñar una tabla/vista existente que ya funciona, confirmar
  el diseño exacto columna por columna con el usuario antes de escribir
  código — un rediseño completo sin esa confirmación previa se hizo en la
  pieza 5 (vista Simple/Avanzada) y hubo que revertirlo entero.
- Al cambiar un comportamiento consolidado (p.ej. de "select de 3 estados"
  a "checkbox simple" en términos), probar en real cuanto antes: el diseño
  cerrado por escrito no sustituye ver el resultado en pantalla.

## Relevo mínimo — siguiente paso

Confirmar commit + push del estado actual (Fase 10 completa). Después:
validar `php -l` en real en LocalWP, y decidir el siguiente foco (UX3/UX4,
o una tarea nueva).
