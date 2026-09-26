# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-26

- **Última release publicada: 1.3.2.** En el árbol de trabajo hay **1.4.1
  preparada pero sin commit, sin push y sin publicar** (versión, `CHANGELOG.md`
  y `readme.txt` sincronizados). ZIP de prueba: `~/Downloads/ai-knowledge-1.4.1.zip`.
- 1.4.0: los 11 puntos de `informe-pendientes-verificados.md`. 1.4.1: carpeta
  `wp-content/llm/` → `wp-content/ai-knowledge/` (migración + redirect 301),
  resumen público generado en el paso Negocio, arreglos de Support Genix
  (sintaxis sin `php` en el PATH, relevancia por título desactivada con
  interruptor, `sync()` sin falso error, clave de Claude), botones del último
  paso, bug de `unlink` y idioma de las respuestas AJAX (hipótesis WPML).
- Nada probado en WordPress real. La clave de Claude de Genix no se ha probado
  contra la API. Los `.mo` se compilaron con un script propio (sin pérdidas);
  los JSON de traducción de JavaScript **no** se han regenerado.

## Pendiente

- QA real de la 1.4.1 (lista en `roadmap.md`), sobre todo: migración de carpeta
  y redirect, reinstalar filtros de Genix, idioma del asistente con WPML,
  desinstalación con los dos checks.
- Regenerar los JSON de traducción y revisar euskera y alemán.
- Commit, push y release: solo con permiso explícito.
- Ver `roadmap.md`.

## Siguiente paso

Probar el ZIP en `docthinks` (con copia). Si todo va bien, pedir commit y release.

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). Lo cerrado del roadmap está en
`temp/roadmap-historico.md`.
