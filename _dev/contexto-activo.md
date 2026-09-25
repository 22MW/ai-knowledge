# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-25

- **Versión 1.3.2 preparada, sin publicar.** Cabecera, `AIKB_VERSION`, `Stable
  tag`, `readme.txt` y `CHANGELOG.md` en 1.3.2; ZIP limpio comprobado en
  `dist/ai-knowledge-1.3.2.zip` (fuera del repo). Última release publicada:
  1.3.1.
- Incluye la estrategia de idiomas, probada con WPML real (docthinks); plan y
  estado en `temp/estrategia-idiomas.md`. Textos nuevos traducidos (`.pot`,
  `.po`, `.mo` y JSON) y documentación (`docs/`, `_dev/`, propuesta comercial)
  al día.
- Sin commitear: todo lo posterior al commit `acba11c` (tandas 2 y 3, traducciones,
  documentación, versión 1.3.2). Falta el commit, el push de `knowBaseDev` y
  ejecutar `_dev/deploy-release.sh`, con el OK explícito del usuario en cada paso.

## Pendiente

Ver `roadmap.md`: Genix (incidencia aparte) y comprobar con otros plugins de
idiomas (sin plugin, instalación antigua, Polylang, TranslatePress), QA real de
la 1.3.0, modo WP sin IA y traducciones de textos fijos.

## Siguiente paso

Commit, push y release 1.3.2 (con OK explícito); después, las
comprobaciones con otros plugins de idiomas o el modo WP sin IA
(`modo-wp-sin-ia.md`).

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). Lo cerrado del roadmap está en
`temp/roadmap-historico.md`.
