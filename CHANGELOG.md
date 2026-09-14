# Changelog

Todas las modificaciones relevantes de este plugin se documentan en este archivo.

## [1.0.8] - 2026-09-15

### Cambiado
- Renombrado de identidad (Fase 0): nombre visible del plugin de "WOO Knowledge Base Generator" a **"AI Knowledge & Visibility"**, y Text Domain de `woo-kb-generator` a `ai-knowledge`. El slug interno de la página de admin (`page=woo-kb-generator`), las constantes `WOOKB_*` y el namespace `WOOKB\` se mantienen sin cambios (decisión explícita: no tocar identificadores internos sin necesidad).
- Fase 1: control manual de documentos (modo manual con texto fijado a mano, límite de caracteres por documento, aviso de "origen actualizado") y botón "Añadir a la base de conocimiento" en el editor de cualquier post/CPT.

## [1.0.7] - 2026-08-22

### Añadido
- Skin visual completo del panel de administración (Alcance, Exclusiones, Registro) basado en Tabler: tema oscuro/claro, checkboxes y tabla legibles en ambos modos, chips clicables para listas de CPTs/taxonomías/campos custom.
- Auto-continuación de la cola de generación: "Reiniciar cola" ya no requiere pulsar varias veces, avanza sola en lotes de 20 hasta vaciarse.
- Refresco automático de las reglas de enrutado (`flush_rewrite_rules()`) al terminar la cola o generar por ID/URL suelto, como red de seguridad.
- Cabecera del plugin actualizada (autoría, URI, text domain).
- Documentación: `readme.txt`, este `CHANGELOG.md` y un documento comercial de referencia.

### Corregido
- **Bug de origen de la "Carga inicial":** `run_seed_batch()` encolaba el mismo ID de contenido para todos los idiomas activos sin comprobar si existía traducción real, generando filas de cola "huérfanas" que nunca se procesaban (quedaban en cola para siempre). Ahora resuelve la traducción real de cada idioma antes de encolar, igual que ya hacía correctamente el sincronizador de altas/ediciones.
- **Enlaces del chatbot redirigiendo a la home:** las reglas de enrutado del tipo de contenido interno `sgkb-docs` quedaron desactualizadas tras una limpieza de contenido, haciendo que WordPress no reconociera esas URLs y cayera en la página de inicio por defecto. Corregido con un refresco de permalinks; ahora se previene automáticamente.
- Registro: el título "Estado" de la cabecera de la tabla desaparecía por una regla CSS demasiado amplia que también afectaba a la cabecera, no solo a la celda de datos.
- Registro: la columna "Estado" mostraba un badge con aspecto de doble contorno por colisión de nombre entre la clase nativa de WordPress y un componente del framework visual (Tabler) con el mismo nombre; ahora es texto plano.
- Avisos (notices) del panel invisibles en modo oscuro (texto negro fijo sobre fondo oscuro).
- Avisos de plugins ajenos (WooCommerce, Newsletter, WPML) apareciendo dentro de la caja del plugin por compartir la clase genérica `wrap` de wp-admin.

## [1.0.1] - 2026-08-22

### Añadido
- Límite de caracteres configurable por fila en el Registro (uso puntual, no se guarda).
- Selección múltiple de filas en el Registro con acciones en lote (borrar, regenerar).
- Generación directa por ID o URL, sin depender de que la fila exista en el Registro ni de esperar al cron.
- Sincronización con Support Genix **Lite**, además de la versión Pro (antes solo se parcheaba Pro).

### Corregido
- Colisión entre las acciones en lote del Registro y el enrutado `admin-post.php` (los formularios competían por el mismo campo `action`).

## [1.0.0] - 2026-08-20 a 2026-08-21

### Añadido
- Plugin completo: generación de documentos `.md` desde productos WooCommerce y otros CPTs.
- Cola de generación con límite diario configurable, vía Action Scheduler (o WP-Cron como alternativa).
- Integración WPML: un documento por idioma activo, con `trid` propio para los documentos `sgkb-docs` (evita colisiones de traducción con el contenido original).
- Publicación dinámica de `/llms.txt` desde el registro interno de documentos (no un archivo estático).
- Sección de FAQ pública editable, incluida en `/llms.txt`.
- Documentos compuestos automáticos de información de tienda (cómo comprar, condiciones, envío y pago, catálogo), uno por idioma, generados desde la configuración real de WooCommerce.
- Conexión con Support Genix: creación/actualización de posts `sgkb-docs`, y asistente de redacción del prompt de sistema del chatbot sincronizado con el ajuste nativo de Genix.
- Redirección automática de visitas directas a los documentos internos hacia la página/producto real, según el idioma real de navegación.
- Varias mitigaciones de comportamiento del chatbot: filtro de relevancia sobre resultados de búsqueda (para activar la recuperación por historial de conversación cuando la búsqueda inicial encuentra solo resultados irrelevantes), manejo de idiomas no soportados por el sitio, límite configurable de "documentos relacionados" mostrados en el chat, resúmenes en formato de bullets en vez de prosa.
- Paridad de interfaz entre las pestañas Alcance y Exclusiones, con enlaces cruzados entre idiomas.
