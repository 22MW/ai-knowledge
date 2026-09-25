# Roadmap activo — AI Knowledge & Visibility

Este documento contiene únicamente trabajo pendiente o por decidir. Lo cerrado está en [`temp/roadmap-historico.md`](temp/roadmap-historico.md).

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

## Pendiente de QA real — versión 1.3.0

1. Documentos de producto: bloque «Datos de compra» con productos simples y
   variables reales (precio, descuento, envío, impuestos, variaciones,
   campos personalizados).
2. Asistente rediseñado: generación por paso, resumen y conexión de IA.
3. Interruptor «Incluir llms.txt para los modelos desactivados»: guardado con
   recarga y propuestas de robots.txt y .htaccess actualizadas.
4. Logo del menú y del icono de actualización (solo se ve con una
   actualización pendiente).
5. Constructores de página (Elementor u otros): no consta que el contenido
   guardado fuera de `post_content` llegue al documento.
6. Prueba visual de los catálogos de idioma (`ca`, `de_DE`, `en_US`, `eu`,
   `fr_FR`) con cada locale.

## Pendiente — estrategia de idiomas

La estrategia está implementada y probada con WPML real (docthinks, 2026-09-25).
Falta:

1. **Genix:** el chatbot responde «no tengo información» incluso en español con
   los documentos correctos y publicados. Incidencia de Genix, aparte de los
   idiomas. Después, comprobar el chatbot en inglés y en catalán con un único
   documento por contenido (riesgo: la búsqueda de Genix filtra por el idioma
   del visitante).
2. **Comprobar con otros plugins de idiomas y otros escenarios:**
   - Sitio sin plugin de idiomas: «Idiomas» de Negocio sin el check, línea del
     idioma sin español fijo.
   - Instalación antigua con documentos ya en varios idiomas: tras actualizar,
     el check sale marcado y no cambia nada.
   - Polylang: idiomas detectados, check, «Generar pendientes» con todos los
     contenidos, `llms.txt` sin prefijo.
   - TranslatePress: idiomas detectados, sin check, `/es/llms.txt` sirve lo
     mismo que la raíz (el informe de supershippingwoo.com). Su API está escrita
     de memoria de la documentación y no está verificada.
3. Validar si las IA siguen de verdad los enlaces «Disponible en» (evidencia
   débil sobre `hreflang`).

## Pendiente de publicación documental

- Sustituir las 15 marcas `[SCREENSHOT]`/`[VIDEO]` de [`docs/`](docs/index.md) por medios reales antes de publicar la documentación.

## Pendiente de revisión Git

- La versión 1.3.2 (estrategia de idiomas) está preparada en `knowBaseDev` pero sin
  commitear ni publicar. El commit, el push y la release los decide el usuario.
- Revisar el diff pendiente de `Scope::is_included()` junto al fix publicado de `tax_query` (`v1.1.0.1`) y decidir si se confirma commit y push. No se ha propuesto ni autorizado ninguna operación Git en este roadmap.

## Próximas mejoras, sin empezar

### Internacionalización del plugin

Implementada. Queda el QA visual en WordPress con cada locale activo (`ca`,
`de_DE`, `en_US`, `eu`, `fr_FR`) y los `.mo` y JSON JavaScript, que genera el
usuario.

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

### Modo WP: documentos sin IA

Plan en [`modo-wp-sin-ia.md`](modo-wp-sin-ia.md). Interruptor global IA/WP,
opción A/B para ediciones, valor `auto_wp` en `override_mode` y badge
`Auto · IA` / `Auto · WP`. Sin implementar.

### Lado páginas del informe de incidencias

Sin hacer: `Article` para páginas y Markdown sin precios (problema 4, parte de
páginas).

### Traducciones y limpieza pendientes

- Etiquetas del bloque «Datos de compra» (`Generator::PURCHASE_LABELS`) y
  «Estándar»/«cualquiera» en `Extractor_Woo`: español fijo, sin `__()`.
- Los `confirm()` de `assets/admin.js` (robots.txt y .htaccess) están en
  español fijo.
- Filas antiguas de FAQ y tienda en otros idiomas: siguen en el Registro sin
  regenerarse; se borran a mano desde el Registro.

### UX3 — WooCommerce: selección y prompt por campo a nivel de producto

Nueva arquitectura para seleccionar y aportar texto por campo de cada producto. Es distinta de la selección ya implementada de envíos, impuestos, pagos y categorías. Requiere `rol-analista` antes de tocar código.

### UX4 — Visibilidad del botón de documentos de tienda

Revisar en pantalla si «Generar documentos de tienda» es suficientemente visible y está bien etiquetado.

### Estilos inline PHP

Hay estilos inline como `style="width:100%"` en varias vistas. Detectado, no abordado; no es autorización para un refactor general.

## Ideas sin planificar

- Tags dinámicos en prompts y texto manual (`{post.title}`, datos de producto, etc.).
- Cobertura de Google en avisos en tiempo real mediante Search Console Indexing API con OAuth propio. IndexNow no cubre Google.
- Actualizar OpenAPI con las rutas de feeds. Está explícitamente fuera del alcance actual.

## Referencias

- [`temp/roadmap-historico.md`](temp/roadmap-historico.md): fases, rondas y evidencia de trabajo cerrado.
- [`decisiones.md`](decisiones.md): decisiones permanentes.
- [`contexto-activo.md`](contexto-activo.md): relevo de la tarea activa.
- [`temp/estrategia-idiomas.md`](temp/estrategia-idiomas.md): plan y estado de la estrategia de idiomas.
- [`docs/index.md`](../docs/index.md): índice de documentación del plugin.
