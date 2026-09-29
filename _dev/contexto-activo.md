# Contexto activo

## Plugin objetivo

`ai-knowledge`, rama `knowBaseDev`, repo `github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado — 2026-09-29

- **Última release publicada: 1.4.3** (2026-09-29). Sin tarea de código
  abierta.
- Corrige un fallo real detectado en un sitio en producción
  (bodegasvirei.com, diagnosticado con acceso SSH de solo lectura): cuando
  la API externa de IA (Anthropic/OpenAI) devolvía un error con texto no
  UTF-8 válido, `wp_send_json_error()` fallaba en silencio (HTTP 422, cuerpo
  vacío, sin ningún log) y la pantalla mostraba un aviso genérico de
  "sesión" que no tenía nada que ver con la causa real. Saneado centralizado
  en `AI_Client::generate()` para las 3 rutas (Conectores de WordPress,
  Genix/OpenAI, Genix/Claude).
- Release 1.4.2 (2026-09-28) incluida también: interruptor de Visibilidad IA
  exime `/ai-knowledge-doc/`; `.htaccess`/`robots.txt` con una sola lógica de
  conflictos; dominio en los nombres de archivo descargados.

## Siguiente paso

Pendiente de confirmar en real (bodegasvirei.com u otro sitio) que, la
próxima vez que falle una llamada a la IA, el mensaje de error se ve de
verdad en vez de la pantalla en blanco.

## Notas de proceso

Las reglas de trabajo de este plugin están en `decisiones.md` (sección
«Release y proceso»). Versiones: en desarrollo solo sube el cuarto número
(`MAJOR.MINOR.PATCH.DEV`); el número de release lo decide el usuario. Lo cerrado
del roadmap está en `temp/roadmap-historico.md`.
