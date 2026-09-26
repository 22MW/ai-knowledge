# Pestaña Ajustes

[← Volver al índice](index.md)

La configuración general del generador de documentos: cuánto texto
escribe, de dónde saca la IA, y en qué contenido aparece el botón rápido
para añadir algo a la base de conocimiento.

> El límite diario, el tamaño de lote y el debounce de la cola **no
> viven aquí** — se editan en [Generación masiva](tab-generacion-masiva.md).

## Largo del texto generado

Cuántos caracteres tiene el cuerpo de cada documento (sin contar el
título). Puedes sobrescribir este valor para un documento individual
concreto desde [Registro](tab-registro.md), si algún producto necesita
más o menos detalle que el resto.

## Tokens de salida por documento

Este es un dato más técnico: es el techo que se le pone a la petición de
IA para que la respuesta no se corte a mitad de frase. No es lo que
controla el largo real del texto — eso lo hace el campo anterior. Solo
tócalo si ves que los documentos se están cortando antes de tiempo.

## Origen de IA

De dónde saca el plugin la inteligencia artificial para redactar:

- **Conectores de WordPress** (si tu WordPress es 7.0 o superior y tienes
  alguno configurado en `Ajustes → Conectores`): Anthropic, OpenAI o
  Google.
- **Support Genix**: usa la conexión de IA de tu chatbot, si lo tienes
  instalado. Sirve la clave de **OpenAI** o la de **Claude** que tengas en
  Genix (se respeta el proveedor elegido en el chatbot de Genix; si ese no
  tiene clave, se usa el otro). El uso de Claude no está probado contra la API
  real.

Debajo del selector verás el **estado de la conexión**: si funciona, con el
origen y el modelo; si no, el motivo (WordPress inferior a 7.0, sin modelos en
Conectores, Genix sin clave de OpenAI o el modelo elegido ya no disponible). Se
recalcula al guardar y no hace ninguna llamada a la IA.

Si eliges Conectores de WordPress, puedes elegir el modelo concreto o
dejarlo en **"Automático"** (recomendado), que elige entre los modelos de
texto que tengas conectados. Con Support Genix, el modelo se muestra en solo
lectura: para cambiarlo, hazlo en los ajustes de Genix.

El botón **«Probar conexión»** hace una llamada mínima a la IA (consume unos
pocos tokens), solo cuando lo pulsas, y muestra el resultado real.

[SCREENSHOT: selector de origen de IA con el modelo activo mostrado abajo]

## Botón "Añadir a la base de conocimiento" en el editor

Cuando editas un producto o una página, el plugin añade un pequeño
recuadro (meta box) en el editor con un botón para generar su documento
sin salir de ahí. Aquí eliges en qué tipos de contenido aparece ese
recuadro — por defecto, en todos.

Al pulsarlo se incluye ese contenido en tu alcance (solo ese, no el tipo de
contenido entero) y se encola su documento. En webs con varios idiomas, el
documento es el del contenido original, o el de la traducción si tienes marcado
«Crear por idioma».

## Aviso a buscadores (IndexNow)

Si lo activas, el plugin avisa automáticamente a los buscadores
compatibles con **IndexNow** (Bing y otros — Google no lo soporta
todavía) cada vez que se crea, actualiza o borra contenido dentro de tu
alcance. Así se enteran del cambio casi al instante, en lugar de esperar
a su próximo rastreo habitual.

## Al eliminar el plugin

Dos casillas **desmarcadas por defecto** que deciden qué se borra si eliminas
el plugin desde la lista de plugins. Son irreversibles: haz una copia antes.

- **Borrar datos de la base de datos** — las tablas de documentos y de
  registro de crawlers, todos los ajustes y opciones del plugin (respuestas de
  Negocio y chatbot, resumen público, idiomas, estado del asistente…), los
  datos temporales, las acciones pendientes de la cola y los eventos
  programados, y las copias «sgkb-docs» de Genix que tengan una fila en el
  Registro. No borra los artículos exclusivos de Genix ni el ajuste
  `chatbot_custom_instructions` de Genix.
- **Borrar archivos generados** — la carpeta `wp-content/ai-knowledge/` (y la antigua `wp-content/llm/` si aún existe) (documentos
  `.md`, FAQ, `info.md` y el prompt del chatbot) y el `llms.txt` de la raíz,
  solo si lo creó este plugin (uno propio tuyo se respeta). **No toca
  `robots.txt` ni `.htaccess`:** las reglas de bloqueo de AI Knowledge siguen
  activas tras desinstalar hasta que las quites a mano.

Sin ninguna casilla marcada, al eliminar el plugin solo se borran la tabla de
documentos y los ajustes principales; el resto se conserva. En multisitio, los
datos se borran sitio a sitio y los archivos solo si todos los sitios tienen
marcada la casilla de archivos.

## Preguntas frecuentes

**¿Qué diferencia hay entre "Largo del texto" y "Tokens de salida"?**
El primero es el resultado final que ves (cuántos caracteres tiene el
documento). El segundo es un límite técnico interno para que la IA no se
quede a medias al escribir. En el 99% de los casos solo necesitas tocar
el primero.

**Si cambio el origen de IA, ¿se regeneran mis documentos actuales
solos?**
No, el cambio afecta a las próximas generaciones. Si quieres que todo tu
catálogo use el nuevo origen, tendrías que regenerarlo desde
[Generación masiva](tab-generacion-masiva.md) o
[Registro](tab-registro.md).

**No veo la opción "Conectores de WordPress", ¿por qué?**
Esa opción solo aparece si tu WordPress es la versión 7.0 o superior y
tiene la función de Conectores disponible. Si no la ves, usa Support
Genix como origen mientras tanto.

**¿Puedo tener el botón del editor en unos tipos de contenido sí y en
otros no?**
Sí, marca solo los que quieras en el selector de esta pantalla.

---
[Ver también: [Generación masiva](tab-generacion-masiva.md) · [Genix](tab-chatbot.md) · [WooCommerce](tab-woocommerce.md)]
