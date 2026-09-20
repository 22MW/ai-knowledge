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
| [Chatbot](tab-chatbot.md) | El comportamiento de tu chatbot de Support Genix *(solo si lo tienes instalado)* |
| [Visibilidad IA](tab-visibilidad-ia.md) | Comprobar y controlar qué ven de tu web los buscadores de IA |
| [Generación masiva](tab-generacion-masiva.md) | Generar todos los documentos pendientes de golpe |
| [Ajustes](tab-ajustes.md) | Configuración general: largo del texto, origen de la IA, etc. |

## Preguntas frecuentes

**¿Necesito WooCommerce para usar este plugin?**
No. WooCommerce añade una pantalla extra (envíos, impuestos, pagos), pero
el plugin funciona igual de bien documentando páginas, artículos o
cualquier tipo de contenido de tu web.

**¿Necesito Support Genix?**
No para generar y publicar tu base de conocimiento. Solo lo necesitas si
quieres que un chatbot en tu web use esos datos para responder a tus
visitantes — en ese caso, la pestaña Chatbot se activa sola.

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
