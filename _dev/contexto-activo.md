# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-27

- **Última release publicada: 1.4.1** (2026-09-27). Sin tarea de código
  abierta.
- La 1.4.1 recoge lo probado y corregido de la 1.4.0 (Visibilidad IA como
  texto plano, botones de copiar, recuadros de estilo, Generación masiva
  reordenada, «Datos adicionales») más un hallazgo de una auditoría GEO
  externa: documentos con guion bajo en el slug de origen daban 404 en
  `llms.txt` (lista blanca del servidor de documentos sin `_`).

## Siguiente paso

A elegir por el usuario a partir del roadmap.

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). Versiones: en desarrollo solo sube el cuarto número
(`MAJOR.MINOR.PATCH.DEV`); el número de release lo decide el usuario. Lo cerrado
del roadmap está en `temp/roadmap-historico.md`.
