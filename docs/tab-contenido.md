# Pestaña Contenido

[← Volver al índice](index.md)

Aquí decides **qué parte de tu web** entra en la base de conocimiento.
Es la pantalla más importante del plugin: nada se genera para lo que no
esté incluido aquí, y un contenido excluido nunca genera documento aunque
su tipo o categoría esté incluida.

> Si vienes de una versión anterior del plugin, esta pantalla fusiona lo
> que antes eran dos pestañas separadas ("Alcance" y "Exclusiones") en
> una sola, más simple.

## Tipos de contenido a incluir

Eliges entre dos modos:

- **Solo los tipos marcados abajo**: seleccionas a mano qué tipos de
  contenido (productos, páginas, entradas, tipos de contenido
  personalizado...) quieres incluir.
- **Todos los tipos públicos**: se incluye automáticamente todo lo
  público de tu web, presente y futuro (si mañana instalas un plugin que
  añade un nuevo tipo de contenido, entra solo). En este modo puedes
  marcar excepciones concretas a excluir.

[SCREENSHOT: selector de modo "Solo los tipos marcados" vs "Todos los tipos públicos"]

> Si usas Support Genix, no verás los artículos internos de Genix
> ("Docs") en esta lista: son copias que el propio chatbot usa para
> responder, no contenido tuyo que la IA tenga que redactar. Si quieres
> publicar alguno de esos artículos hacia fuera (en `/llms.txt`), se hace
> desde la pestaña [Genix](tab-chatbot.md), sección "Artículos exclusivos
> de Genix".

## Categorías y etiquetas

Para cada tipo de contenido incluido, puedes afinar por sus categorías o
etiquetas: si no marcas ninguna, entran todos los términos; si marcas
alguna, solo entra el contenido que tenga ese término.

## IDs sueltos

Dos campos independientes de todo lo anterior:

- **IDs a incluir**: fuerza la entrada de contenido concreto, sin
  importar su tipo o categoría.
- **IDs a excluir**: bloquea contenido concreto para siempre, aunque su
  tipo o categoría esté incluida. Es el mismo mecanismo que usa
  automáticamente la pestaña [Registro](tab-registro.md) cuando borras un
  documento.

## Campos personalizados por tipo de contenido

Si usas campos personalizados (por ejemplo, de constructores de página o
plugins de campos avanzados), aquí puedes elegir cuáles se envían a la IA
como contexto adicional al redactar cada documento — útil si tienes datos
relevantes que no están en el contenido principal del producto o página.
Además, los campos que elijas se añaden tal cual, sin pasar por la IA, en el
bloque «Datos de compra» del final del documento (también en tipos de
contenido que no son productos).

## Preguntas frecuentes

**Si marco "Todos los tipos públicos", ¿se incluye también contenido
futuro?**
Sí, ese es justamente el sentido de ese modo: cualquier tipo de contenido
que añadas después entra automáticamente, sin que tengas que volver aquí
a marcarlo.

**¿Un ID excluido puede volver a incluirse?**
Sí, solo tienes que quitarlo de la lista de "IDs a excluir" y volverá a
entrar en el alcance la próxima vez que se genere.

**¿Cambiar esta configuración borra los documentos que ya generé?**
No, cambiar el alcance no borra nada automáticamente. Afecta a lo que se
genere de aquí en adelante. Si quieres eliminar documentos de algo que ya
excluiste, hazlo desde [Registro](tab-registro.md).

**No veo mis campos personalizados en la lista, ¿por qué?**
El plugin muestra los campos detectados en una muestra de contenido
reciente de ese tipo. Si el campo solo existe en contenido muy antiguo o
aún no se ha rellenado en ningún elemento, puede no aparecer todavía.

---
[Ver también: [Registro](tab-registro.md) · [Generación masiva](tab-generacion-masiva.md)]
