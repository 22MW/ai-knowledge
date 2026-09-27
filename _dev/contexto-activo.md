# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-28

- **Última release publicada: 1.4.2** (2026-09-28). Sin tarea de código
  abierta.
- Incluye: el interruptor de Visibilidad IA exime también `/ai-knowledge-doc/`
  (no solo `/llms.txt`); en `.htaccess` y `robots.txt` se unificó en una sola
  función la detección/comentado de conflictos con reglas de terceros (aviso,
  vista previa, descarga y botón real, antes con 3-4 lógicas distintas por
  archivo que podían no coincidir); los archivos descargados (llms.txt,
  robots.txt, .htaccess) llevan el dominio del sitio en el nombre.
- Sin probar aún en la web real por el usuario (queda pendiente de QA real).

## Siguiente paso

A elegir por el usuario a partir del roadmap. Pendiente concreto: probar en
real el arreglo de conflictos de `.htaccess`/`robots.txt` con las reglas de
terceros del sitio.

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). Versiones: en desarrollo solo sube el cuarto número
(`MAJOR.MINOR.PATCH.DEV`); el número de release lo decide el usuario. Lo cerrado
del roadmap está en `temp/roadmap-historico.md`.
