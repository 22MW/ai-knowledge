# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-25

- Última release publicada: **1.3.0** (2026-09-24), fusionada en `main`.
- Sin tarea de código abierta. Cambios sin commitear en el árbol: `docs/`,
  `languages/` (`.po` y `.pot` actualizados; los `.mo` y `.json` los genera el
  usuario), `_dev/` y `_dev/deploy-release.sh`.

## Hecho recientemente (detalle en `CHANGELOG.md` y `decisiones.md`)

- Asistente rediseñado, Registro con AJAX, artículos exclusivos de Genix,
  productos con «Datos de compra», arreglos de Schema y `products.xml`,
  interruptor de llms.txt, «Reemplazar .htaccess», logo del menú y de
  actualizaciones.
- Documentación de usuario (`docs/`) y técnica (`documentacion-tecnica.md`)
  al día con la 1.3.0.

## Estrategia de idiomas (2026-09-25)

Fases 0 a 6 implementadas en el árbol, sin commitear y sin probar (detalle en
`estrategia-idiomas.md`, «Estado por fase»). `php -l` y `git diff --check`
pasados. QA pendiente: la matriz de pruebas del plan (sin plugin, WPML con el
check marcado/desmarcado y cambio de check, instalación antigua, Polylang,
TranslatePress, chatbot con un único documento). Riesgo abierto: Genix filtra
por idioma actual con WPML.

## Ajustes de idiomas tras la prueba en docthinks (2026-09-25)

Aplicados en el árbol por el `desarrollador`, sin commitear y sin probar
(`php -l` y `git diff --check` pasados). Fuente:
`ajustes-idiomas-docthinks.md`. QA pendiente: repetir en docthinks con el check
desmarcado y marcado (URLs por idioma, apartado «Idiomas», conteo de Carga
inicial, `/xx/llms.txt`, campo de idiomas en Negocio y asistente, botón
«Reiniciar todo» tras guardar el check y su confirmación con el número de
documentos). Tras cambiar `llms.txt`/rutas, guardar Ajustes → Enlaces
permanentes no hace falta (no hay reglas nuevas).

## Pendiente

Ver `roadmap.md`: QA real de la 1.3.0, modo WP sin IA, estrategia de idiomas,
lado páginas del informe de incidencias y traducciones de textos fijos.

## Siguiente paso

Probar la estrategia de idiomas y decidir el commit; después, el modo WP sin IA
(`modo-wp-sin-ia.md`).

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). La versión anterior de este archivo, con el historial
por tareas, está en Git (commit `4325ba1` y anteriores).
