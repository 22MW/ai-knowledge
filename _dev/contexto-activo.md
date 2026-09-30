# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-30

- **Última release publicada: 1.4.4** (2026-09-30). Sin tarea de código
  abierta.
- Incluye:
  - Términos de taxonomía marcados en Contenido → se añaden al `.md` en un
    bloque propio ("Categorías y características"), directo desde la BD sin
    pasar por la IA. Mismo criterio en los dos sitios (filtro de alcance y
    contenido del documento), taxonomía por taxonomía: nada marcado = todo
    entra; algo marcado = filtra las dos cosas. Botón «Seleccionar todos».
  - `/llms.txt` agrupa por tipo de contenido (CPT: Productos, Páginas...),
    no por categoría de producto — evita duplicar la organización que ya
    hace el catálogo de tienda.
  - Release 1.4.3 (2026-09-29) incluida también: saneado UTF-8 de mensajes
    de error de IA (evita respuestas 422 vacías y silenciosas).
- Confirmado por el usuario en real (plugins.local) tras regenerar: `/llms.txt`
  ya no muestra categorías, solo CPTs. Un aviso de "categoría vieja" que vio
  después era caché del navegador, no del servidor (confirmado con curl y
  con la fecha del archivo físico en disco).

## Siguiente paso

A elegir por el usuario a partir del roadmap. Pendiente concreto: probar en
real (bodegasvirei.com) que, si vuelve a fallar la IA, el mensaje se ve.

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). Versiones: en desarrollo solo sube el cuarto número
(`MAJOR.MINOR.PATCH.DEV`); el número de release lo decide el usuario. Lo cerrado
del roadmap está en `temp/roadmap-historico.md`.
