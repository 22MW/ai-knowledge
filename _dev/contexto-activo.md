# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Commiteado y pusheado (confirmado en `git log`)

8 commits en `knowBaseDev`, Fases 0-5 del roadmap completas, los bugs de la
primera ronda de QA arreglados, y el rediseño completo del CSS del admin
(commit `20ae873`). Detalle en [`CHANGELOG.md`](../CHANGELOG.md); regla
visual permanente en [`decisiones.md`](decisiones.md).

## Confirmado por el usuario, autorizado para commit (pendiente de commitear)

**Fase 6 — Panel "Visibilidad IA", completa y probada en real:**
- Estado de exposición (llms.txt, Markdown, JSON, JSON-LD) con enlace a un
  ejemplo real de cada uno, y contador de contenido pendiente de
  sincronizar (confirmado: 26 pendientes correctos).
- Selector cerrado de contenido ya sincronizado + "Comprobar
  accesibilidad": lee `robots.txt` y noindex/`X-Robots-Tag` en vivo, avisa
  de conflicto entre ambas señales — confirmado detectando un caso real.
- `llms.txt` físico: vista previa + fecha de modificación + botón "Borrar
  archivo físico" (confirmación JS fuerte), explicando antes que el plugin
  genera el suyo dinámicamente al vuelo.
- Archivos: `admin/class-admin.php`, `admin/views/tab-visibilidad-ia.php`
  (nuevo), `includes/class-accessibility-checker.php` (nuevo).

**Bug aparte corregido:** `<select>` del admin con texto invisible en
hover/foco en modo oscuro (`assets/wookb-theme.css`, mismo caso que los
botones — `!important`, ver `decisiones.md`).

## Pendiente real (sin empezar)

- Fases 7-11 del roadmap.
- UX grande sin abordar: "Carga inicial" confusa, WooCommerce con
  selección de campos tipo checkbox + prompt por campo (ver
  `_dev/qa-resultados-fase-0-a-5.md`).
- Estilos inline en PHP (`style="width:100%"`, etc. en varias vistas):
  detectado, no abordado — pendiente de decidir si se mueve a CSS.
- [`llms-faq.md`](../llms-faq.md): contenido del sitio, sin commitear a propósito.
- Bug sin repetir: botón del editor (Fase 1) sin feedback claro la primera
  vez que se probó.
- Ideas sueltas sin fase (`analisis-jet-geo.md`): tags dinámicos en
  prompts, onboarding por pasos, modo "todos los CPT" en `Scope`.

## Decisiones de proceso (aplican siempre en este plugin)

- Commit/push: permiso explícito cada vez, mostrando status+diff+mensaje.
- Cambios de versión: preguntar antes.
- Subagente: solo trabajo grande/aislado; ajustes pequeños los hace la
  sesión principal.
- Nunca "hecho" en algo visual sin confirmación del usuario.
- Ningún color/estilo nuevo sin usar una variable real de Tabler o una
  clase ya definida — ver `_dev/guia-estilo-visual.html` primero.

## Relevo mínimo — siguiente paso

Con el commit de Fase 6 hecho: decidir con el usuario si sigue la Fase 7
(API pública documentada) o el rediseño de UX pendiente (Carga inicial /
selección de campos WooCommerce).
