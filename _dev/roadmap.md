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

### Internacionalización del plugin y documentación por idioma

Implementación local realizada; queda pendiente la prueba visual y la
traducción lingüística real de los catálogos:

- Crear `languages/ai-knowledge.pot` como plantilla universal.
- Crear inicialmente `languages/ai-knowledge-es_ES.po`; el español es el
  idioma base.
- Revisar todos los textos visibles del plugin: PHP, JavaScript, botones,
  errores, avisos y mensajes AJAX.
- Internacionalizar los textos dinámicos de `assets/admin.js` y generar el
  JSON de traducción JavaScript si WordPress lo necesita.
- Mantener `docs/` como documentación base en español.
- Permitir traducciones opcionales en `docs/{locale}/`, por ejemplo
  `docs/en_US/` o `docs/fr_FR/`.
- Si existe `docs/{locale}/archivo.md`, usarlo; si no existe, usar
  `docs/archivo.md` como fallback.
- No crear ahora copias traducidas de los `.md`, ni sustituir ni borrar la
  documentación española.

Implementado: `class-admin.php` selecciona `docs/{locale}/archivo.md` cuando
existe y vuelve a `docs/archivo.md` cuando no existe; `admin.js` usa
`wp.i18n`; el script se encola con `wp-i18n` y carga sus traducciones.

Los catálogos inglés y catalán están completados; sus `.mo` y JSON JavaScript
se incluyen en el release `1.1.3`. Pendiente únicamente el QA visual en
WordPress con cada locale activo.

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

## Plan aprobado pendiente de implementación — publicación, AJAX y prompts por documento

Plan detallado: [`plan-publicacion-ajax-prompts.md`](plan-publicacion-ajax-prompts.md).

Ajuste posterior confirmado: la documentación destinada a Genix se copiará
íntegra desde el contenido original publicado a Markdown, sin IA, sin resumen,
sin reescritura y sin límite de caracteres. La instalación de Genix dentro de
este workspace confirma que `new-genix-slug` es `docs_single_slug` (rewrite de
URL), mientras que el `post_type` interno sigue siendo `sgkb-docs`. La
exclusión debe usar el identificador interno y las URLs deben obtenerse del
registro real de WordPress.

Alcance funcional confirmado por el usuario el 2026-09-23. Todavía no
implementado ni validado.

### Objetivo

- Permitir marcar cada documento del Registro como público.
- Un documento público se publica como `.md` y se incluye en `/llms.txt`.
- Añadir generación individual por AJAX desde Ajustes avanzados, sin recarga.
- Añadir prompt guardable por documento e idioma.
- Si el prompt está vacío, usar el prompt genérico actual.
- Aplicar el límite de caracteres de la fila o, si no existe, el límite general
  de Ajustes tanto al prompt como a la generación.
- No crear ni actualizar posts `sgkb-docs` de Support Genix; conservar el
  Markdown propio y publicarlo solo cuando corresponda.
- Permitir seleccionar tipos públicos, pero excluir `sgkb-docs` de todas las
  rutas de alcance, configuración, cola, generación manual, editor, REST y
  feeds.
- Generar únicamente para posts publicados; revisar también generación por ID,
  traducciones, cambios de estado y documentos existentes.

### Fases previstas

1. Separar publicación Markdown/llms de la integración Genix.
2. Añadir estado público por fila y su persistencia.
3. Añadir prompt por documento e idioma.
4. Integrar prompt personalizado/genérico y límite de caracteres en el
   generador.
5. Añadir acciones AJAX para guardar y generar desde Registro.
6. Excluir `sgkb-docs` de todo el alcance, incluido “Todos los tipos públicos”
   e inclusión manual por ID.
7. Auditar y corregir todos los caminos para aceptar únicamente
   `post_status = publish`.
8. Ejecutar validación estática y QA real con Genix activo/desactivado,
   JavaScript activo/desactivado y WPML si está disponible.

### Decisión posterior

Los `sgkb-docs` antiguos no se borrarán automáticamente en esta primera
implementación. Se mantendrán intactos y se podrá preparar una limpieza
separada si el usuario la solicita.

- Tags dinámicos en prompts y texto manual (`{post.title}`, datos de producto, etc.).
- Asistente de configuración por pasos: consultar el plan de asistente en _dev.
  MVP pendiente de validación funcional antes de implementar.
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
