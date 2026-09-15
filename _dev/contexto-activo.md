# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Commiteado y pusheado (confirmado en `git log`)

7 commits en `knowBaseDev`, Fases 0-5 del roadmap completas, más los bugs de
la primera ronda de QA arreglados. Detalle en `CHANGELOG.md`.

## Confirmado visualmente por el usuario (pendiente de commit)

Rediseño completo del CSS del admin (`assets/wookb-theme.css`), a raíz de
varias rondas de feedback visual real:

- Sistema de 6 variantes de botón (neutro/primario/peligro/éxito/info),
  usando solo variables reales de Tabler, mismo tamaño siempre
  (`padding: .5625rem 1rem; font-size: .875rem`, valores reales de `.btn`
  de Tabler, no inventados).
- Sin bordes decorativos en cajas/paneles/botones (solo color de fondo
  sólido); bordes solo en campos de formulario y separadores de tabla.
- Hover de botones con `filter: invert(1)` + `!important` (WordPress core
  gana el empate de especificidad si no se fuerza).
- Tabla del Registro: layout arreglado (`table-layout: auto`), fila
  expandida "Ver/editar Markdown" ligada visualmente a su fila, columna
  Acciones con límite/Generar/Borrar apilados y mismo ancho.
- Nuevo `_dev/guia-estilo-visual.html`: carga el CSS real del plugin
  (no una copia), única fuente de verdad visual, claro y oscuro.
- Todo documentado como regla permanente en `_dev/decisiones.md`.

**Este bloque está confirmado por el usuario y autorizado para commit.**

## Pendiente real (sin empezar)

- Fases 6-11 del roadmap.
- UX grande sin abordar: "Carga inicial" confusa, WooCommerce con
  selección de campos tipo checkbox + prompt por campo (ver
  `_dev/qa-resultados-fase-0-a-5.md`).
- Estilos inline en PHP (`style="width:100%"`, etc. en varias vistas):
  detectado, no abordado — pendiente de decidir si se mueve a CSS.
- `llms-faq.md`: contenido del sitio, sin commitear a propósito.
- Bug sin repetir: botón del editor (Fase 1) sin feedback claro la primera
  vez que se probó.

## Decisiones de proceso (aplican siempre en este plugin)

- Commit/push: permiso explícito cada vez, mostrando status+diff+mensaje.
- Cambios de versión: preguntar antes.
- Subagente: solo trabajo grande/aislado; ajustes pequeños los hace la
  sesión principal.
- Nunca "hecho" en algo visual sin confirmación del usuario.
- Ningún color/estilo nuevo sin usar una variable real de Tabler o una
  clase ya definida — ver `_dev/guia-estilo-visual.html` primero.

## Relevo mínimo — siguiente paso

Con el commit de este bloque hecho: decidir con el usuario si sigue la
Fase 6 o el rediseño de UX pendiente (Carga inicial / WooCommerce).
