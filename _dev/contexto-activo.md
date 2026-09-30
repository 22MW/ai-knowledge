# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-30

- **Última release publicada: 1.4.5** (2026-09-30). Sin tarea de código
  abierta.
- Incluye:
  - Fecha de última actualización visible en cada `.md`, bajo el título
    (igual formato que `/llms.txt`).
  - `products.xml`: arreglado un caso real de feed inválido
    ("Entity 'nbsp' not defined") cuando la descripción de un producto
    tenía entidades HTML sin decodificar. Encontrado en un sitio real
    (solodevino.com).
  - Release 1.4.4 (mismo día): taxonomías marcadas → `.md` (bloque
    "Categorías y características"), `/llms.txt` agrupado por CPT.

## Siguiente paso

A elegir por el usuario a partir del roadmap. Pendientes de QA real:
- `products.xml` con el arreglo de `&nbsp;` en un producto real que lo tenía.
- Que "Reiniciar todo" (cola en segundo plano) termine de procesar todos los
  documentos de un catálogo grande, para confirmar que todos llevan ya el
  bloque de taxonomías.
- bodegasvirei.com: confirmar que, si vuelve a fallar la IA, el mensaje real
  se ve (arreglo de la 1.4.3).

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). Versiones: en desarrollo solo sube el cuarto número
(`MAJOR.MINOR.PATCH.DEV`); el número de release lo decide el usuario. Lo cerrado
del roadmap está en `temp/roadmap-historico.md`.
