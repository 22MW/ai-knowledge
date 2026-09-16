=== AI Knowledge & Visibility ===
Contributors: 22mw
Tags: woocommerce, chatbot, ia, llms.txt, wpml, support genix
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.8.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Genera automáticamente una base de conocimiento en Markdown a partir de tu catálogo WooCommerce (y otros CPT), la publica en /llms.txt para buscadores de IA y la conecta con el chatbot de Support Genix.

== Description ==

**AI Knowledge & Visibility** convierte tus productos, páginas y contenidos de WooCommerce en documentos de conocimiento estructurados, listos para dos usos:

1. **Alimentar el chatbot de Support Genix** con información real y actualizada de tu tienda, en vez de depender de que el chatbot "adivine" o de mantener un prompt manual interminable.
2. **Publicar `/llms.txt`**, el archivo estándar que los motores de búsqueda de IA (ChatGPT, Perplexity, Claude y similares) usan para entender de qué trata tu web — lo que hoy se conoce como optimización GEO (Generative Engine Optimization), el equivalente a SEO para inteligencias artificiales.

= Características principales =

* Generación automática de documentos `.md` a partir de productos, páginas y otros tipos de contenido (CPTs) que elijas.
* Cola de generación con límite diario configurable (o sin límite, para cargas iniciales supervisadas).
* Compatible con WPML: genera un documento por cada idioma activo, resolviendo traducciones reales y evitando falsos "documentos puente" cuando no hace falta.
* Publicación pública de `/llms.txt`, generado dinámicamente desde el propio registro de documentos (no un archivo estático que se queda desactualizado).
* Sección de FAQ pública editable, incluida en `/llms.txt`.
* Los generadores de Negocio, FAQs y documentos WooCommerce aceptan instrucciones breves o prompts completos que prevalecen sobre el formato predeterminado sin poder inventar datos; el resultado se revisa antes de guardar en Negocio y FAQs.
* Conexión directa con Support Genix (Pro y Lite): crea/actualiza los posts `sgkb-docs` que el chatbot usa como fuente, y sincroniza el prompt de sistema del chatbot con un asistente de redacción propio.
* Documentos compuestos automáticos de "información de tienda" (cómo comprar, condiciones, envío y pago, catálogo) generados desde la configuración real de WooCommerce, no inventados. Sus instrucciones opcionales de pulido pueden cambiar el formato y la estructura, pero nunca añadir datos ausentes; un documento completo creado externamente puede pegarse manualmente desde Registro.
* Panel de administración con pestañas especializadas; los ajustes exclusivos del chatbot, como el límite de documentos relacionados, viven en Chatbot y no en los ajustes generales.
* Funciona igual sin Support Genix instalado: en ese caso, el plugin sigue generando y publicando `/llms.txt` de forma independiente.
* En WordPress 7.0 o superior puede usar los Conectores nativos de Anthropic, OpenAI o Google, con selección automática o manual del modelo disponible. Support Genix se mantiene como origen alternativo.
* El enfoque detallado del negocio se conserva como dato fuente independiente del resumen público generado para `/llms.txt`.
* Sin dependencias obligatorias: WooCommerce, WPML y Support Genix se detectan en tiempo real; si no están, esas funciones concretas simplemente no se activan.

== Installation ==

1. Sube la carpeta `ai-knowledge` a `/wp-content/plugins/`.
2. Activa el plugin desde el menú **Plugins** de WordPress.
3. Ve al nuevo menú **Base de conocimiento IA** en el panel de administración.
4. En la pestaña **Contenido**, elige qué tipos de contenido (productos, páginas, etc.) quieres incluir y qué excluir.
5. Revisa los **Ajustes** (origen y modelo de IA, y longitud de los documentos).
6. Ve a **Generación masiva** y pulsa "Generar pendientes" para crear los documentos pendientes del catálogo.
7. Sigue el progreso desde la pestaña **Registro**.

No hace falta configurar nada más para que `/llms.txt` empiece a publicarse: se activa automáticamente en cuanto hay documentos generados.

= Conexión con Support Genix (opcional) =

Si tienes instalado y activo el plugin Support Genix (Pro o Lite), AI Knowledge & Visibility detecta su presencia automáticamente y:

* Crea/actualiza los documentos internos (`sgkb-docs`) que el chatbot usa como fuente de respuestas.
* Ofrece, en la pestaña **Prompt**, un asistente para redactar y sincronizar el prompt de sistema del chatbot.

Si no tienes Support Genix, estas dos funciones quedan inactivas sin errores ni avisos molestos — el resto del plugin (generación de documentos y `/llms.txt`) sigue funcionando igual.

== Frequently Asked Questions ==

= ¿Necesito WooCommerce para usar este plugin? =

No es obligatorio. El plugin puede generar documentos a partir de cualquier tipo de contenido público de WordPress (páginas, entradas, otros CPTs). Si tienes WooCommerce, además puede generar documentos compuestos específicos de tienda (cómo comprar, condiciones, catálogo).

= ¿Necesito Support Genix instalado? =

No. Sin Support Genix, el plugin sigue generando los documentos `.md` y publicando `/llms.txt` con total normalidad. Lo único que no estará disponible es la conexión directa con el chatbot (los documentos `sgkb-docs` y la pestaña Prompt).

= ¿Qué es `/llms.txt` y para qué sirve? =

Es un archivo Markdown propuesto en la raíz del sitio que da a los LLM (modelos de lenguaje) un índice curado de tu contenido más importante. Es una convención emergente, no un estándar ratificado todavía — cada vez más motores de búsqueda de inteligencia artificial (ChatGPT, Perplexity, etc.) lo consultan para entender el contenido de una web sin tener que rastrear página por página. Puedes ver la propuesta oficial en https://llmstxt.org/. Este plugin lo genera y lo mantiene actualizado automáticamente a partir de tu catálogo real, sin que tengas que escribirlo ni actualizarlo a mano.

= ¿Qué pasa si mi web tiene varios idiomas (WPML)? =

El plugin detecta WPML automáticamente y genera un documento por cada idioma activo, resolviendo las traducciones reales de cada producto o página. Si un contenido no tiene traducción a un idioma concreto, no se genera un documento falso para ese idioma — solo se generan los que existen de verdad.

= ¿Cuánto tarda en generar todo mi catálogo? =

Depende del límite diario configurado (por defecto, 100 documentos al día, para controlar el coste y la carga del servidor). Se puede desactivar temporalmente ese límite en Ajustes para cargas iniciales supervisadas, y volver a activarlo después.

= ¿Se sube contenido a algún sitio externo? =

Solo se envía el contenido necesario al origen de IA seleccionado (Conectores de WordPress o Support Genix) para generar el resumen de cada documento. Las credenciales de los Conectores se gestionan en WordPress y AI Knowledge no las copia ni las muestra. El resto del funcionamiento (registro, publicación de `/llms.txt`, conexión con Genix) ocurre enteramente dentro de tu propio WordPress.

= ¿Qué pasa si borro o despublico un producto? =

El plugin detecta el borrado/despublicación y elimina automáticamente el documento asociado (el archivo `.md`, el post `sgkb-docs` si existe, y la fila de su registro interno), para que `/llms.txt` y el chatbot no sigan citando contenido que ya no existe.

== Changelog ==

Ver `CHANGELOG.md` para el historial completo y detallado de cambios.

= 1.0.8.6 =
Limpieza de Ajustes: retirado el prompt global obsoleto y movido el límite de documentos relacionados a la pestaña Chatbot con guardado independiente.

= 1.0.8.5 =
Pestaña WooCommerce editable con selección de datos, pulido mediante instrucciones privadas protegidas, mejoras equivalentes en Negocio y FAQs, y guardado manual más claro desde Registro.

= 1.0.8.4 =
Pestaña WooCommerce con impuestos/IVA autodetectados, control manual por documento, API REST de contenido, descubrimiento de Markdown público, JSON-LD, panel "Visibilidad IA" con comprobación de accesibilidad, documentación OpenAPI de la API, aviso en tiempo real a buscadores compatibles con IndexNow, feeds especializados (Google Merchant y JSON) enlazados desde llms.txt y desde el `<head>` del sitio, gestión de crawlers de IA (catálogo de bots conocidos, bloqueo real por robots.txt/.htaccess con descarga obligatoria de seguridad, y logs de accesos), rediseño del alcance de contenido (pestañas Alcance y Exclusiones fusionadas en "Contenido", modo "todos los tipos públicos", "Ajustes avanzados" en el Registro), reorganización de datos del negocio/chatbot/FAQs (nuevas pestañas Negocio y FAQs con generación con IA, FAQs multiidioma reales, pestaña Chatbot visible solo con Support Genix instalado), y pestaña WooCommerce con envíos/impuestos/pagos/catálogo seleccionables, datos autodetectados ahora editables y persistentes, y pulido con instrucciones que prevalecen sobre el formato sin poder inventar datos.

= 1.0.7 =
Ajustes de aspecto del panel de administración (tema visual), corrección de un bug de enrutado que hacía que algunos enlaces del chatbot llevaran a la página de inicio en vez de al contenido real, y corrección del bug de "Carga inicial" que generaba filas de cola huérfanas cuando el contenido se regeneraba desde cero.

= 1.0.1 =
Registro: límite de caracteres configurable por fila, selección múltiple para acciones en lote, generación directa por ID/URL sin depender del cron, sincronización con Support Genix Lite además de Pro.

= 1.0.0 =
Primera versión funcional: generación de documentos desde WooCommerce/CPTs, cola con límite diario, integración WPML, publicación de `/llms.txt`, conexión con Support Genix y FAQ pública.

== Upgrade Notice ==

= 1.0.7 =
Si tu web usa WPML y notas enlaces del chatbot que llevan a la home en vez de a la página correcta, esta versión lo corrige — revisa la pestaña Registro tras actualizar.
