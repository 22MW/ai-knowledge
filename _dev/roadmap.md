# Roadmap activo — AI Knowledge & Visibility

Este documento contiene únicamente trabajo pendiente o por decidir. El historial de fases y rondas cerradas está en [`roadmap-historico.md`](roadmap-historico.md).

## Pendiente de QA real

### Conectores WordPress 7.0

La integración está implementada: AI Client nativo como origen junto a Support Genix, adaptador central y selección Automático/modelo explícito para proveedores Anthropic, OpenAI y Google. Falta probar en un entorno real:

1. Conectar un proveedor y guardar Automático y un modelo explícito.
2. Generar documentos, resumen de Negocio, FAQs y documentos WooCommerce.
3. Probar la traducción auxiliar del chatbot.

No presentar este flujo como validado hasta realizar esas pruebas.

### Negocio y WooCommerce

1. Confirmar en real que guardar o vaciar el resumen público de `llms.txt` no modifica «Enfoque del negocio».
2. Probar la pestaña WooCommerce: checkboxes, snapshot editable y «Pulir redacción con IA», especialmente la llamada a IA.
3. Repetir la comprobación de feedback del botón del editor de documentos (Fase 1), que quedó sin evidencia suficiente en la primera prueba.

## Pendiente de publicación documental

- Sustituir las 13 marcas `[SCREENSHOT]`/`[VIDEO]` de [`docs/`](docs/index.md) por medios reales antes de publicar la documentación.

## Pendiente de revisión Git

- Revisar el diff pendiente de `Scope::is_included()` junto al fix publicado de `tax_query` (`v1.1.0.1`) y decidir si se confirma commit y push. No se ha propuesto ni autorizado ninguna operación Git en este roadmap.

## Próximas mejoras, sin empezar

### Visibilidad IA, reglas y `llms.txt`

La implementación está cerrada y trasladada al histórico. Solo queda el QA
real indicado arriba.

### Gestión avanzada de crawlers — pendiente de QA real

Ampliar la gestión de crawlers de la pestaña Visibilidad IA sin cambiar todavía
las reglas reales del servidor:

- Organizar la tabla por tipo: búsqueda IA, entrenamiento, asistente bajo
  demanda, SEO/scraping y scanners de seguridad.
- Mantener el bloque propio sustituible, las reglas originales conservadas y
  comentadas si contradicen la propuesta, y las vistas «Actual» y «Después
  del cambio».
- Avisar de que el `User-Agent` puede falsificarse y no autentica al crawler.

La interfaz, el catálogo ampliado, las reglas agrupadas y el orden anterior a
WordPress están implementados. La tabla muestra inicialmente 10 crawlers y se
puede desplegar completa; los filtros siguen actuando sobre todo el catálogo.
`Amazonbot` queda permitido y ya no existe el estado «Sin decidir».
Queda pendiente validar visualmente la tabla y probar en un `.htaccess` real
los modos de bloqueo total y `llms_only`, con copia previa y comprobación de
que WordPress sigue respondiendo correctamente.

### UX3 — WooCommerce: selección y prompt por campo a nivel de producto

Nueva arquitectura para seleccionar y aportar texto por campo de cada producto. Es distinta de la selección ya implementada de envíos, impuestos, pagos y categorías. Requiere `rol-analista` antes de tocar código.

### UX4 — Visibilidad del botón de documentos de tienda

Revisar en pantalla si «Generar documentos de tienda» es suficientemente visible y está bien etiquetado.

### Estilos inline PHP

Hay estilos inline como `style="width:100%"` en varias vistas. Detectado, no abordado; no es autorización para un refactor general.

## Ideas sin planificar

- Tags dinámicos en prompts y texto manual (`{post.title}`, datos de producto, etc.).
- Onboarding por pasos para la primera configuración.
- Cobertura de Google en avisos en tiempo real mediante Search Console Indexing API con OAuth propio. IndexNow no cubre Google.
- Actualizar OpenAPI con las rutas de feeds. Está explícitamente fuera del alcance actual.

## Referencias

- [`roadmap-historico.md`](roadmap-historico.md): fases, rondas y evidencia de trabajo cerrado.
- [`decisiones.md`](decisiones.md): decisiones permanentes.
- [`contexto-activo.md`](contexto-activo.md): relevo de la tarea activa.
- [`docs/index.md`](../docs/index.md): índice de documentación del plugin.

## Release 1.1.1 — seguimiento Git

- Pendiente separado: resolver la divergencia histórica de la rama `main`
  estable del plugin con `knowBaseDev` antes de volver a automatizar el merge
  del script de release. No se debe resolver eliminando o restaurando `_dev/`
  sin una decisión explícita.

## Release 1.1.2 — preparación

- Pendiente publicar la navegación contextual del popup y sus ajustes visuales
  después de ejecutar la validación técnica y preparar el ZIP.
