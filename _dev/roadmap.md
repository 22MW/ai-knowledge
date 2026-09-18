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

### Plan propuesto — Visibilidad IA, reglas y `llms.txt`

**Implementación actual:** modo global «Solo permitir visibilidad de
`llms.txt`», `llms.txt` físico gestionado y regenerado automáticamente,
comparación de reglas y vista de archivo actual/propuesto. En `robots.txt`,
las reglas externas contradictorias se comentan y el bloque del plugin se
reemplaza entero. En `.htaccess`, la propuesta conserva el contenido actual
y genera un bloque nuevo para copiar o descargar; no se sobrescribe
automáticamente.

**Pendiente de QA real:** confirmar con el archivo de cada sitio que las
reglas antiguas se comentan correctamente, que el bloque nuevo no se duplica
y que los bots bloqueados solo reciben `200` en `/llms.txt` y `404` en el
resto. Mantener dos bloques visibles en la pantalla («Actual» y «Después
del cambio») para revisar antes de copiar o guardar.

1. **Definir el modo de visibilidad.** Añadir «Solo visibilidad de
   `llms.txt`» como opción explícita para los crawlers seleccionados: permitir
   `/llms.txt` y limitar el resto de rutas según el agente, sin cambiar la
   visibilidad para usuarios normales.
2. **Analizar antes de escribir.** Leer el `robots.txt` y `.htaccess` reales,
   identificar reglas originales y compararlas con las reglas que produciría
   la configuración actual. Mostrar advertencias claras cuando haya
   contradicciones.
3. **Separar reglas externas y del plugin.** Mantener las reglas originales;
   encapsular las del plugin entre marcadores propios, con comentarios que
   expliquen cualquier conflicto detectado. Nunca borrar silenciosamente una
   regla externa.
4. **Actualizar de forma idempotente.** En cada aplicación, reemplazar
   únicamente el bloque gestionado por AI Knowledge. No duplicar reglas. Si
   la configuración queda vacía, eliminar solo el bloque del plugin y dejar
   intacto todo lo demás.
5. **Proteger cada modificación.** Antes de aplicar cambios, exigir lectura y
   descarga de la copia actual de cada archivo, confirmación fuerte y una
   comprobación final del contenido escrito. `robots.txt` y `.htaccess` deben
   tener copias y confirmaciones independientes.
6. **Convertir `llms.txt` en archivo físico.** Generarlo en la raíz del sitio;
   si ya existe, leerlo y exigir su descarga previa antes de sustituirlo.
   Marcar el archivo generado para que las actualizaciones futuras sustituyan
   solo el contenido gestionado por el plugin, sin confundirlo con un archivo
   externo.
7. **Validar en staging/local.** Probar reglas sin conflicto, conflicto con
   reglas originales, segunda actualización sin duplicados, configuración
   vacía, archivo inexistente y archivo existente. Comprobar además que un
   agente puede leer `/llms.txt` en el modo nuevo.

Este plan requiere `planificar-cambio` antes de tocar código. Las decisiones
sobre el formato exacto de los comentarios, los marcadores y la prioridad de
las reglas quedan pendientes de esa planificación.

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

## Cambio aplicado — documentación en el admin (2026-09-18)

- La documentación pública del plugin vive ahora en `docs/`, fuera de `_dev/`.
- Cada pestaña enlaza su guía correspondiente mediante «Leer documentación».
- La guía se abre en un panel lateral derecho y se muestra como lectura normal.
- Pendiente de QA visual real: comprobar el ancho, el cierre, Escape y la
  lectura en cada pestaña.
