# AI Knowledge & Visibility — Documentación

¿Alguna vez le has preguntado algo a ChatGPT sobre una tienda y te ha dado
una respuesta a medias, o directamente inventada? Eso pasa porque la
inmensa mayoría de tiendas online no están pensadas para que una IA las
lea: están pensadas para que las lea una persona, con menús, banners e
imágenes que un motor de IA no entiende bien.

**AI Knowledge & Visibility** hace justo lo contrario: convierte tu
catálogo, tus páginas y la información de tu negocio en documentos claros,
estructurados y fáciles de entender tanto para tu propio chatbot como para
los buscadores y asistentes de IA (ChatGPT, Claude, Gemini, Perplexity...)
que cada vez más gente usa para comprar.

En una frase: **tu tienda deja de ser invisible para la IA.**

## ¿Para quién es esto?

Para cualquier tienda WooCommerce (o sitio con contenido de cualquier
tipo — no hace falta que vendas nada) que quiera:

- Que su catálogo se entienda de verdad cuando alguien le pregunta a una
  IA por sus productos, precios, envíos o condiciones.
- Tener un chatbot propio en la web que responda con datos reales de tu
  negocio, no con respuestas genéricas.
- Aparecer bien posicionada en los nuevos motores de búsqueda basados en
  IA, no solo en Google clásico.

## ¿Qué hace exactamente?

1. **Genera documentos de conocimiento** a partir de tus productos,
   páginas y el contenido que tú decidas, usando IA para redactarlos de
   forma clara y ordenada.
2. **Los publica** de varias formas a la vez: como archivos Markdown
   públicos, como `/llms.txt` (el estándar que empiezan a usar los
   buscadores de IA para saber de qué va tu web), como datos
   estructurados (JSON-LD) dentro de tus páginas, y como una API que
   cualquier IA puede consultar.
3. **Alimenta tu chatbot** (si usas [Support Genix](https://22mw.online/)),
   con el prompt y los datos reales de tu negocio, no con texto inventado.
4. **Te dice si los buscadores de IA pueden ver tu web** de verdad, y te
   deja decidir qué crawlers de IA quieres permitir o bloquear.

Todo esto sin tocar tu WooCommerce: **no se recrean productos, pedidos ni
pagos**. El plugin lee lo que ya tienes y genera una capa de contenido
adicional pensada para que la IA la entienda.

> No hace falta que sepas nada de IA ni de SEO técnico para usar este
> plugin. Cada pantalla te explica qué hace y qué pasa si activas algo.

## Webs con varios idiomas

El plugin funciona con **WPML** (probado en una web real), **Polylang** y
**TranslatePress** (compatibles, todavía sin probar en una instalación real), y
también sin ningún plugin de idiomas. Lo que debes saber:

- **Un solo documento por contenido.** Por defecto, cada página o producto
  tiene un único documento, en el **idioma principal** de tu web, y todas sus
  traducciones lo enlazan. Cada documento acaba con «Disponible en», con el
  enlace a cada versión que existe.
- **Idioma principal e idiomas de la web** se configuran en
  [Negocio](tab-negocio.md) (y en el paso Negocio del asistente). Si tienes un
  plugin de idiomas se detectan solos; puedes añadir otros idiomas a mano.
- **«Crear por idioma»** (solo con WPML y Polylang): si lo marcas, además se crea
  un documento por cada traducción que exista. Se activa en
  [Generación masiva](tab-generacion-masiva.md). Al cambiarlo, pulsa **Reiniciar
  todo** para que los documentos se ajusten.
- **`/llms.txt` es único**, en el idioma principal. No hay un `llms.txt` distinto
  por idioma: cualquier `/idioma/llms.txt` muestra el mismo archivo.
- Las preguntas frecuentes, los documentos de tienda y el resumen de Negocio
  se generan solo en el idioma principal.

## Idiomas de la interfaz

La interfaz del plugin está preparada para estos idiomas:

- Español
- Catalán
- Alemán
- Inglés
- Francés

El idioma se toma de la configuración de idioma de WordPress. Si falta un
idioma, encuentras una cadena sin traducir o detectas una traducción incorrecta,
ponte en contacto con nosotros para poder corregirla y añadirla al catálogo.

## Por dónde empezar

Si es la primera vez que lo instalas, sigue este orden:

1. **[Instalación y puesta en marcha](instalacion.md)** — sube el plugin,
   actívalo y da los primeros pasos.
2. **[Contenido](tab-contenido.md)** — decide qué parte de tu web entra en
   la base de conocimiento.
3. **[Generación masiva](tab-generacion-masiva.md)** — genera de golpe los
   primeros documentos.

Y a partir de ahí, cada pantalla tiene su propia guía:

| Pantalla | Para qué sirve |
|---|---|
| [Registro](tab-registro.md) | Ver, buscar y gestionar todos los documentos generados |
| [Contenido](tab-contenido.md) | Elegir qué entra y qué no en la base de conocimiento |
| [Negocio](tab-negocio.md) | Los datos de tu negocio que usan el chatbot y `/llms.txt` |
| [FAQs](tab-faqs.md) | Preguntas frecuentes públicas, generadas con IA |
| [WooCommerce](tab-woocommerce.md) | Envíos, impuestos, pagos y condiciones de tu tienda *(solo si tienes WooCommerce)* |
| [Genix](tab-chatbot.md) | El comportamiento de tu chatbot de Support Genix y los artículos exclusivos de Genix que quieras publicar *(solo si lo tienes instalado)* |
| [Visibilidad IA](tab-visibilidad-ia.md) | Comprobar y controlar qué ven de tu web los buscadores de IA |
| [Generación masiva](tab-generacion-masiva.md) | Generar todos los documentos pendientes de golpe |
| [Ajustes](tab-ajustes.md) | Configuración general: largo del texto, origen de la IA, etc. |

## El asistente de configuración

Si prefieres que te guíen, el plugin incluye un **asistente** paso a paso. Lo
encuentras en el menú *Base de conocimiento IA → Asistente* y en la lista de
plugins («Abrir asistente»); además, se abre solo la primera vez que activas el
plugin.

Recorre estos pasos, en este orden:

1. **Bienvenida** — qué se va a revisar, y un prompt de auditoría para
   comprobar tu sitio en un agente de IA externo *antes* de configurar nada.
2. **Origen de IA** — qué conexión redactará los documentos (Conectores de
   WordPress o Support Genix). El paso comprueba la conexión (sin hacer
   llamadas a la IA) y te dice el motivo si algo falla; si funciona, puedes
   elegir el modelo (con Conectores de WordPress) o ver el que usa Genix, y
   pulsar **«Probar conexión»** para una prueba real bajo demanda. Si todavía
   no tienes ninguna conexión, puedes seguir sin IA: cada paso posterior que
   necesite generar te avisará, sin bloquearte.
3. **Límites de generación** — largo máximo del texto y límites diarios.
   «Sin límite diario» viene marcado la primera vez (con un aviso: el gasto
   de IA no tiene tope); desmárcalo si quieres controlar el gasto.
4. **Contenido y alcance** — qué tipos de contenido entran. Al guardar, se
   encola la generación de lo que entre en el alcance.
5. **Negocio** — los datos estables de tu negocio. Al guardar, si hay
   conexión de IA y todavía no existe un resumen público propio, se genera con
   IA (si ya existe no se toca; si falla o no hay IA, el paso te lo dice y
   puedes hacerlo después en [Negocio](tab-negocio.md)).
6. **WooCommerce** — datos de la tienda *(solo con WooCommerce)*, precargados
   con lo que ya tiene WooCommerce (incluida la dirección, que se copia al
   campo «Dirección» de Negocio si está vacío). Al guardar, se generan los
   documentos de información de tienda (no usan IA).
7. **FAQs** — preguntas frecuentes públicas, generadas con IA y publicadas en
   `/llms.txt`. Solo aparece si hay una conexión de IA disponible.
8. **Chatbot** — cómo usa Support Genix tu base de conocimiento *(solo con
   Genix)*. Al guardar, si ya tienes un prompt del chatbot se sincroniza con
   Genix; si no, se crea uno predeterminado con tus respuestas (con IA si hay
   conexión; si no, con una plantilla) y se sincroniza. El paso muestra el
   resultado real.
9. **Visibilidad IA** — qué crawlers pueden entrar, y si los bloqueados
   pueden leer `/llms.txt`.
10. **Archivos del servidor** — comprobar `robots.txt` y `.htaccess` y, si
    quieres, aplicarlos. Ves el `robots.txt` actual (archivo físico o el que
    genera WordPress); sin archivo físico puedes crearlo tras aceptar los
    riesgos (ver [Visibilidad IA](tab-visibilidad-ia.md)).
11. **Resumen** — el estado real de cada paso: qué está generado y qué falta,
    con el desglose de documentos por estado (En cola, Generando, Listo,
    Error), un enlace al Registro y el botón **«Procesar ahora»**. Desde aquí
    puedes generar lo pendiente o dejarlo para más tarde en
    [Generación masiva](tab-generacion-masiva.md).
12. **Resumen final** — el estado real de los documentos (mismo desglose por
    estado), el prompt de auditoría otra vez (para comparar con el resultado
    del principio) y accesos directos a las pantallas principales. La
    generación continúa en segundo plano y puede tardar. Los accesos
    directos son botones y hay un botón **Salir** que lleva al Registro.

Puedes saltar cualquier paso y salir cuando quieras con el botón **Salir** de la
cabecera; al volver, continúas por donde lo dejaste. El asistente no borra
ninguna configuración existente.

## Cabecera de las pantallas

Arriba a la derecha encuentras **Ver llms.txt** (abre tu `/llms.txt` en una
pestaña nueva) y el interruptor de **modo oscuro**.

## Preguntas frecuentes

**¿Necesito WooCommerce para usar este plugin?**
No. WooCommerce añade una pantalla extra (envíos, impuestos, pagos), pero
el plugin funciona igual de bien documentando páginas, artículos o
cualquier tipo de contenido de tu web.

**¿Necesito Support Genix?**
No para generar y publicar tu base de conocimiento. Solo lo necesitas si
quieres que un chatbot en tu web use esos datos para responder a tus
visitantes — en ese caso, la pestaña Genix se activa sola.

**¿La IA se inventa datos sobre mi negocio?**
No debería: el plugin está diseñado para que la IA redacte solo con la
información real que tú le das (tu catálogo, tus datos de negocio). Nunca
inventa precios, condiciones ni políticas.

**¿Esto sustituye a mi SEO habitual?**
No, lo complementa. Tu SEO sigue funcionando igual para Google. Esto es la
capa pensada específicamente para que te encuentren los asistentes de IA,
que funcionan de forma distinta a un buscador clásico.

**¿Qué pasa si desactivo el plugin?**
Tus datos (documentos generados, ajustes) se quedan guardados. Si lo
desinstalas del todo, sí se eliminan — verás un aviso antes de que eso
pase.

---
[SCREENSHOT: pantalla principal del plugin con el menú "Base de conocimiento IA" en el admin de WordPress]
