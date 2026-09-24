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

## Pendiente de QA real — versión 1.3.0

1. «Reemplazar .htaccess»: **probado en un servidor real** (2026-09-25, según
   el usuario). Sin repetir salvo cambios.
2. Documentos de producto: bloque «Datos de compra» con productos simples y
   variables reales (precio, descuento, envío, impuestos, variaciones,
   campos personalizados).
3. Asistente rediseñado: generación por paso, resumen y conexión de IA.
4. Interruptor «Incluir llms.txt para los modelos desactivados»: guardado con
   recarga y propuestas de robots.txt y .htaccess actualizadas.
5. Logo del menú y del icono de actualización (solo se ve con una
   actualización pendiente).
6. Constructores de página (Elementor u otros): no consta que el contenido
   guardado fuera de `post_content` llegue al documento.
7. Prueba visual de los catálogos de idioma (`ca`, `de_DE`, `en_US`, `eu`,
   `fr_FR`) con cada locale.

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

Los catálogos `ca`, `de_DE`, `en_US`, `eu` y `fr_FR` están al día con las
cadenas de la 1.3.0 (2026-09-25); los `.mo` y JSON JavaScript los genera el
usuario. Pendiente únicamente el QA visual en WordPress con cada locale activo.

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

### Modo WP: documentos sin IA

Plan en [`modo-wp-sin-ia.md`](modo-wp-sin-ia.md). Interruptor global IA/WP,
opción A/B para ediciones, valor `auto_wp` en `override_mode` y badge
`Auto · IA` / `Auto · WP`. Sin implementar.

### Estrategia de idiomas

Plan en [`estrategia-idiomas.md`](estrategia-idiomas.md): capa común para
WPML, Polylang y TranslatePress, idioma principal desde Negocio y check
«Separar contenido por idioma». Sin implementar. Incluye el problema de
TranslatePress del informe de supershippingwoo.com (problema 1).

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

- [`roadmap-historico.md`](roadmap-historico.md): fases, rondas y evidencia de trabajo cerrado.
- [`decisiones.md`](decisiones.md): decisiones permanentes.
- [`contexto-activo.md`](contexto-activo.md): relevo de la tarea activa.
- [`docs/index.md`](../docs/index.md): índice de documentación del plugin.

## Seguimiento de releases

- Las releases 1.2.0 a 1.3.0 se han fusionado en `main` con el script sin
  conflictos (2026-09-25); la divergencia histórica de la 1.1.1 ya no bloquea.
  El script ya no sube un tag local (lo crea GitHub al crear la release).
