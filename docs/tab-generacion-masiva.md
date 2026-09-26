# Pestaña Generación masiva

[← Volver al índice](index.md)

Genera de golpe todos los documentos pendientes de tu alcance configurado
en [Contenido](tab-contenido.md), y controla la velocidad a la que se
generan para no disparar tu gasto de IA.

## El resumen de arriba

Antes de generar nada, esta pantalla te dice:

- Cuántos documentos posibles entran en tu alcance actual. Por defecto es
  uno por contenido; si marcas «Crear por idioma» (ver más abajo), uno por cada
  versión que existe.
- Cuántos ya están sincronizados.
- Tu límite diario actual (o un aviso bien visible si lo tienes
  desactivado).
- El estado de la generación: documentos por estado (En cola, Generando,
  Listo, Error), enlace al Registro y el botón «Procesar ahora».

[SCREENSHOT: resumen de alcance con el contador de documentos posibles]

## Generar pendientes

Es el botón que usarás casi siempre. Es **seguro repetirlo**: no vuelve a
generar lo que ya está sincronizado y sin cambios, solo lo nuevo o lo que
falló anteriormente.

> Si actualizas desde una versión anterior a la 1.3.0, tus productos ya
> generados se regenerarán una sola vez al pulsar este botón, para incluir el
> bloque «Datos de compra» (ver [WooCommerce](tab-woocommerce.md)).

## Crear por idioma (webs con varios idiomas)

Solo aparece si tu plugin de idiomas crea un contenido distinto por idioma
(WPML y Polylang), y también en el paso Negocio del asistente.

- **Desmarcado (recomendado):** un único documento por contenido, en el idioma
  principal. Todas las traducciones lo enlazan.
- **Marcado:** además, un documento por cada traducción que exista (sin
  documentos puente). Salen en `/llms.txt` y se anuncian en la página de su
  traducción.

Al guardar el cambio aparece, en el mismo sitio, un aviso con el botón
**Reiniciar todo**: púlsalo para borrar y regenerar lo que ya no corresponde.

## Reiniciar todo

Este botón sí **regenera absolutamente todo**, incluido lo que ya estaba
sincronizado. Gasta IA de más y no se puede deshacer — por eso el plugin
te pide confirmación explícita antes de lanzarlo, indicándote **cuántos
documentos va a borrar**. Además de regenerar, borra los documentos que ya no
corresponden: los de tipos de contenido que has sacado del alcance, los de
traducciones si no tienes marcado «Crear por idioma» y los documentos puente
antiguos. No toca las preguntas frecuentes, los documentos de tienda, el
de Negocio, los artículos exclusivos de Genix ni los documentos que has
fijado en modo manual. Úsalo solo si quieres
forzar una regeneración completa (por ejemplo, tras cambiar el largo del
texto en [Ajustes](tab-ajustes.md) y querer que todo el catálogo se
adapte al nuevo tamaño).

> Mientras hay una generación en curso, verás un aviso (que indica si se
> procesa con Action Scheduler o con WP-Cron) y un botón para **cancelarla**
> en cualquier momento. Al terminar el último lote, la generación deja de
> figurar «en curso» sola. Cancelar también retira los lotes pendientes de
> WP-Cron.

## Cómo funciona por dentro (para que no te asustes si tarda)

La generación no ocurre toda de golpe: se procesa **por lotes**, en
segundo plano, usando el sistema de tareas programadas de WordPress
(Action Scheduler si lo tienes, por ejemplo con WooCommerce; si no, WP-Cron).
WP-Cron solo avanza cuando alguien visita la web, así que en un sitio sin
visitas (o con `DISABLE_WP_CRON`, sobre lo que el plugin avisa) puede quedarse
«En cola»: el botón **«Procesar ahora»** procesa hasta 20 documentos por clic.
Puedes seguir el progreso en detalle desde
**Herramientas → Scheduled Actions** (grupo `woo-kb`), aunque para el uso
normal te basta con mirar la pestaña [Registro](tab-registro.md).

## Ajustes de la cola

Valores que controlan el ritmo de generación. Por defecto: 50 documentos por
lote, un lote cada 15 segundos y la carga masiva empieza sin espera (los
valores que ya tuvieras guardados no cambian):

- **Límite diario de generaciones** — el techo de documentos que se
  generan por día. Puedes marcar "Sin límite" para cargas manuales
  puntuales que estés supervisando en directo — pero acuérdate de
  desactivarlo al terminar, o seguirá sin límite indefinidamente.
- **Tamaño de lote** — cuántos documentos se procesan juntos en cada
  ejecución.
- **Retraso de debounce** — un margen de espera (300 s por defecto) que solo
  se aplica a las ediciones sueltas, para no saturar el servidor con guardados
  seguidos. La carga masiva no espera.

## Dónde se guardan los documentos

Los `.md` del **idioma principal** viven directamente en `wp-content/ai-knowledge/` (URL `/ai-knowledge-doc/{nombre}.md`); los de otros idiomas (solo con «Crear por idioma») van en `wp-content/ai-knowledge/{idioma}/`. La carpeta antes se llamaba `wp-content/llm/` y el idioma principal iba en su propia subcarpeta: al actualizar, el plugin lo migra solo y las URL antiguas redirigen (301). Si cambias el idioma principal, pulsa **Reiniciar todo** para recolocar los documentos (los de idiomas que dejan de corresponder se borran). Al
actualizar, el plugin renombra la carpeta antigua automáticamente; si el
servidor no lo permite, sigue usando la antigua sin romper nada. Las URL
antiguas `wp-content/llm/...` redirigen (301) a la nueva cuando el archivo ya
no existe en la ruta vieja.

## Preguntas frecuentes

**¿Puedo cerrar la pestaña del navegador mientras genera?**
Sí, la generación ocurre en segundo plano en el servidor, no depende de
que tengas la página abierta.

**¿Qué pasa si se corta la conexión o el servidor a mitad de una carga
grande?**
Puedes retomarla desde el botón "Reiniciar cola" en la pestaña
[Registro](tab-registro.md), que continúa donde se quedó.

**¿"Generar pendientes" vuelve a gastar IA en lo que ya generé?**
No, solo genera lo nuevo o lo que falló. Para regenerar también lo que ya
está sincronizado, usa "Reiniciar todo" (con su aviso correspondiente).

**¿Por qué tengo un límite diario y no puedo generar todo de una vez?**
Es una protección para que no gastes tu presupuesto de IA de golpe sin
darte cuenta. Puedes subirlo o desactivarlo temporalmente si necesitas
una carga grande puntual.

---
[Ver también: [Contenido](tab-contenido.md) · [Registro](tab-registro.md) · [Ajustes](tab-ajustes.md)]
