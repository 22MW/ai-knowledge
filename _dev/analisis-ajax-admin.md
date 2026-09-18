# Análisis: acciones AJAX en el admin

Fecha: 2026-09-18
Plugin: `ai-knowledge`
Rama revisada: `knowBaseDev`

## Objetivo

Reducir las recargas completas del admin cuando el usuario guarda ajustes,
genera un borrador o ejecuta una comprobación. Las acciones que descargan
archivos, escriben archivos físicos o son destructivas deben conservar una
confirmación y un flujo tradicional hasta que exista una razón clara para
cambiarlas.

## Evidencia actual

- Las acciones del admin están registradas como `admin_post_*` en
  `admin/class-admin.php`.
- Los handlers comprueban capability y nonce mediante `Admin::verify()`.
- La mayoría termina con `Admin::redirect()` o un `wp_safe_redirect()`.
- `assets/admin.js` ya existe y gestiona el tema, descargas y algunas acciones
  de interfaz, pero no hay un adaptador AJAX común para los formularios.
- Las vistas usan varios formularios independientes; Registro además tiene
  tabla, acciones por fila y acciones bulk con reglas propias para no anidar
  formularios.
- No se ha hecho todavía una prueba de runtime de una implementación AJAX.

Sin una implementación y prueba real no puedo confirmar qué partes concretas
de cada HTML podrán sustituirse sin volver a renderizar la pestaña completa.

## Criterio de decisión

Una acción es buena candidata cuando:

1. devuelve un resultado pequeño y previsible;
2. puede actualizar una zona concreta de la pantalla;
3. no necesita una descarga de navegador;
4. no deja una operación larga sin estado visible;
5. puede conservar un fallback `POST` si JavaScript falla.

## Inventario por pestaña

### Contenido

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Guardar alcance y exclusiones | `wookb_save_content` | AJAX en una segunda fase | Es un guardado normal, pero puede borrar documentos al excluir IDs o términos. Debe mostrar el efecto y mantener fallback. |

### Ajustes

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Guardar ajustes generales | `wookb_save_settings` | AJAX recomendado | Respuesta pequeña: éxito o errores de validación. |
| Sincronizar prompt con Support Genix | `wookb_sync_chatbot_prompt` | Mantener tradicional inicialmente | Tiene dependencia externa y resultado propio; primero hay que definir mensajes y timeout. |

### Negocio

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Guardar datos del negocio | `wookb_save_business_answers` | AJAX recomendado | Guardado acotado; debe avisar también que se actualiza el documento de información. |
| Generar/pulir resumen con IA | `wookb_generate_business_summary_draft` | AJAX recomendado con estado | Operación lenta y con posible `WP_Error`; necesita spinner, respuesta de borrador y error visible. |
| Guardar resumen | `wookb_save_business_summary` | AJAX recomendado | Actualiza el resumen y el documento de información. |

### FAQs

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Cambiar idioma | formulario `GET` | Mantener navegación | Cambia el contexto completo de la pantalla y su idioma activo. |
| Generar/ampliar FAQ | `wookb_generate_faqs_draft` | AJAX recomendado con estado | Devuelve borrador o error de IA. |
| Guardar FAQ | `wookb_save_llms_faq` | AJAX recomendado | Guardado de texto editable y respuesta pequeña. |

### WooCommerce

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Guardar configuración de tienda | `wookb_save_woocommerce_settings` | AJAX recomendado | Guardado normal de campos y selecciones. |
| Generar/actualizar documentos de tienda | `wookb_sync_store_docs` | AJAX posible, segunda fase | Puede generar varios idiomas y devolver errores parciales; necesita resumen estructurado. |
| Pulir documento con IA | `wookb_polish_store_doc` | AJAX recomendado con estado | Devuelve un borrador o error y no debería perder el texto editado. |

### Chatbot

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Guardar ajustes del chat | `wookb_save_chatbot_settings` | AJAX recomendado | Guardado pequeño. |
| Generar borrador | `wookb_generate_prompt_draft` | AJAX recomendado con estado | Puede tardar y devolver error de IA; el borrador debe insertarse en el editor. |
| Normalizar/pulir texto | `wookb_normalize_prompt` | AJAX recomendado con estado | El resultado sustituye el contenido del editor solo cuando llega correctamente. |
| Guardar borrador final | `wookb_save_prompt_draft` | AJAX recomendado | Debe conservar la sincronización con Genix y mostrar su resultado. |

### Registro

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Filtrar/buscar/seleccionar idioma | formularios `GET` | Mantener navegación al principio | Cambia la tabla completa y el estado de la URL; AJAX exigiría renderizar filas y paginación. |
| Generar ahora | `wookb_force_generate` | Mantener tradicional inicialmente | Puede iniciar una cola y el resultado no es inmediato. |
| Borrar todos | `wookb_delete_all` | Mantener tradicional | Acción destructiva con confirmación explícita. |
| Reiniciar cola | `wookb_reset_queue` | Mantener tradicional | Acción operativa con impacto global en la cola. |
| Acciones por fila | `row_action`, `regenerate_single` | Segunda fase | La tabla y las filas expandidas tienen formularios separados; primero hay que definir un refresco seguro de fila. |
| Acciones bulk | `maybe_handle_bulk_action` | Mantener tradicional inicialmente | El formulario tiene acciones propias de WordPress y no debe romperse con una capa AJAX prematura. |

### Generación masiva

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Cancelar generación | `wookb_cancel_seed` | Mantener tradicional inicialmente | Cambia el estado global de la cola. |
| Generar pendientes | `wookb_start_seed` | AJAX solo para iniciar, segunda fase | El inicio puede ser AJAX, pero el progreso debe consultarse por separado. |
| Reiniciar todo | `wookb_start_seed_force` | Mantener tradicional inicialmente | Regenera contenido existente y tiene confirmación fuerte. |
| Guardar cola | `wookb_save_queue_settings` | AJAX recomendado | Guardado pequeño e independiente. |

### Visibilidad IA

| Acción | Handler actual | Decisión | Motivo |
|---|---|---|---|
| Comprobar accesibilidad | `wookb_check_accessibility` | AJAX recomendado | Devuelve un resultado para una zona concreta; evita perder la selección. |
| Guardar acciones de crawlers | `wookb_save_crawler_actions` | AJAX recomendado | Guardado de la tabla de permisos. |
| Guardar modo de visibilidad | `wookb_save_crawler_visibility` | AJAX recomendado | Guardado pequeño y actualización de avisos/previsualización. |
| Descargar copias | `download_*_backup` | Mantener descarga normal | El navegador debe recibir un archivo. |
| Aplicar `llms.txt` físico | `wookb_apply_llms_physical` | Mantener tradicional inicialmente | Escribe fuera del plugin y exige copia previa. |
| Aplicar `robots.txt` | `wookb_apply_robots_block` | Mantener tradicional inicialmente | Escritura real con confirmación y posible riesgo de bloqueo. |
| Descargar/aplicar `.htaccess` | `download_*`, `wookb_apply_htaccess_block` | Mantener tradicional | Descarga y escritura de archivo de servidor; la seguridad pesa más que evitar una recarga. |

## Arquitectura propuesta para una futura implementación

### Fase A — base común

- Registrar acciones `wp_ajax_aikb_*` solo para usuarios autenticados.
- Reutilizar la misma capability de `Admin::capability()`.
- Añadir un nonce específico por acción o un nonce común limitado a esta
  pantalla.
- Crear un adaptador JS para serializar formularios, bloquear doble clic y
  mostrar estados `cargando`, `éxito` y `error`.
- Dejar el formulario `POST` funcional como fallback cuando no haya JavaScript
  o falle la petición.
- Responder con `wp_send_json_success()` y `wp_send_json_error()` con datos
  mínimos, sin exponer claves, prompts privados ni trazas internas.

### Fase B — guardados y resultados pequeños

Convertir primero `save_settings`, `save_queue_settings`, los guardados de
Negocio, FAQs, WooCommerce, Chatbot y visibilidad. Cada handler debe devolver
solo el HTML o los datos necesarios para actualizar su bloque, no la página
entera.

### Fase C — operaciones con IA

Convertir generación/pulido de borradores después de definir:

- mensaje de espera;
- límite de tiempo y error visible;
- conservación del texto que el usuario ya haya editado;
- respuesta parcial o completa;
- bloqueo de doble petición.

### Fase D — cola y Registro

Separar iniciar operación de consultar estado. No hacer AJAX sobre la tabla
completa hasta tener una función fiable para volver a renderizar una fila y
mantener paginación, filtros y formularios independientes.

### Fase E — archivos físicos y descargas

No es prioritario convertir estas acciones. El flujo tradicional hace más
visible la confirmación, mantiene la descarga nativa y reduce el riesgo de
escribir un archivo real sin que el usuario lo perciba.

## Seguridad obligatoria

- Capability comprobada en servidor; nunca confiar en que el botón esté
  oculto.
- Nonce comprobado en cada endpoint AJAX.
- Sanitización idéntica a la actual por tipo de campo.
- Escaping de cualquier HTML que vuelva en la respuesta.
- No devolver contenido sensible que no se muestra ya en esa pestaña.
- Rechazar peticiones repetidas cuando una operación no sea idempotente.
- Mantener confirmación adicional para borrados y archivos físicos.

## Casos límite

- Nonce caducado: mensaje claro y recarga manual solicitada.
- Sesión sin capability: error 403 sin mutar datos.
- Doble clic: botón bloqueado hasta recibir respuesta.
- JavaScript desactivado: formulario tradicional operativo.
- Timeout de IA: conservar el texto previo y mostrar error.
- WooCommerce o Support Genix desactivado: no registrar ni ejecutar la
  acción que depende de él.
- Pantalla móvil: mensajes de estado visibles sin depender solo del color.
- Cambio de pestaña durante una petición: no insertar una respuesta en una
  vista distinta.

## Criterios de aceptación para implementar después

1. Cada acción AJAX aprobada conserva su handler `POST` como fallback.
2. No hay recarga completa al guardar o generar en las acciones migradas.
3. El usuario ve estado de carga, éxito y error.
4. Un nonce inválido y un usuario sin permisos no cambian datos.
5. Una doble pulsación no duplica guardados ni generaciones.
6. Registro, cola, descargas y archivos físicos no se migran sin un plan
   específico y una prueba adicional.
7. `php -l`, `git diff --check` y QA real en cada pestaña pasan antes de
   considerar terminada una fase.

## Fuera de alcance de este análisis

- Implementar endpoints AJAX.
- Reescribir handlers existentes.
- Cambiar datos, opciones o tablas.
- Cambiar el diseño visual general del admin.
- Convertir descargas o escrituras físicas en AJAX.
- Commit, push, cambio de versión, release o deploy.

## Siguiente decisión

Este documento deja preparado el trabajo. La siguiente fase debería aprobar
solo la **Fase A + Fase B**, probarla en las pestañas de guardado simple y
decidir después si se continúa con las operaciones de IA.

## Estado de ejecución

### Fase 1 — base común y guardados simples

Aplicada en código el 2026-09-18, pendiente de QA real por el usuario.

- Endpoints AJAX autenticados para los diez guardados simples aprobados.
- Se reutilizan handlers, capability, nonces y sanitización existentes.
- Los formularios `admin-post.php` se conservan como fallback sin JavaScript.
- La interfaz bloquea doble envío y muestra éxito o error junto al formulario.
- No incluye generación IA, Registro, cola operativa, borrados, descargas ni
  escritura de archivos físicos.
