# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-27

- **Última release publicada: 1.4.0** (2026-09-26). Versión dev actual:
  **1.4.0.1**, subida a `knowBaseDev` tras probar la 1.4.0 en un sitio real
  (`docthinks`/`plugins.local`) y corregir lo que falló.
- Corregido: página de Visibilidad IA servida como texto plano (bug real de
  `do_robots()`), botones «Copiar Prompt» del asistente que no copiaban
  (enganche de clic no delegado), recuadro con borde para los prompts y para
  el resumen de Generación masiva, «Datos de compra» → «Datos adicionales» en
  contenidos que no son productos.
- Sin tarea de código abierta. Pendiente real en `roadmap.md`.

## Siguiente paso

A elegir por el usuario a partir del roadmap.

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). Versiones: en desarrollo solo sube el cuarto número
(`MAJOR.MINOR.PATCH.DEV`); el número de release lo decide el usuario. Lo cerrado
del roadmap está en `temp/roadmap-historico.md`.
