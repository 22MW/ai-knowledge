# Pestaña FAQs

[← Volver al índice](index.md)

Las preguntas frecuentes de tu negocio, escritas en Markdown y publicadas
como un documento más de tu base de conocimiento (aparece enlazado desde
`/llms.txt`, igual que el resto de documentos, y también en
[Registro](tab-registro.md)).

> No lo confundas con el prompt del chatbot en [Genix](tab-chatbot.md): esto es
> contenido **público**, pensado para que lo lea cualquiera (persona o
> IA); el prompt del chatbot son instrucciones internas de comportamiento.

## Generar con IA

Escribe instrucciones opcionales (tono, número de preguntas, temas que
quieres cubrir) y pulsa **"Generar/ampliar con IA"**. El plugin usa los
datos que guardaste en [Negocio](tab-negocio.md) para redactar las
preguntas y respuestas. El resultado aparece como **borrador** en el cuadro de
texto: no se publica hasta que lo guardas.

Si ya tenías una FAQ guardada, la IA la toma como referencia para ampliarla o
mejorarla en lugar de partir de cero, pero el borrador puede cambiar lo que ya
había. Puedes repetir el proceso todas las veces que haga falta: amplía primero
los datos de Negocio y guárdalos, o edita la FAQ a mano abajo y guárdala, y vuelve
a generar para pulir el resultado.

> En el **asistente de configuración** el paso de preguntas frecuentes es
> distinto: al guardar el paso se genera con IA y se **publica al momento**. Si
> ya había una FAQ, el asistente te avisa de cuándo se generó y de que volver a
> guardar la redacta otra vez y sustituye a la publicada.

[SCREENSHOT: cuestionario de generación de FAQ con el campo de instrucciones]

## Editar a mano

El cuadro de texto de abajo es la FAQ real, en Markdown:

```markdown
### ¿Puedo devolver un producto?
Sí, dispones de 30 días desde la recepción...
```

Puedes escribirla o corregirla directamente aquí y guardarla, sin pasar
por la IA.

## Si tu web es multiidioma

La FAQ se escribe y se publica solo en el **idioma principal** del sitio (no hay
selector de idioma). El documento acaba con «Disponible en», con la portada de
cada idioma de tu web.

## Preguntas frecuentes

**¿Puedo escribir la FAQ yo mismo, sin usar la IA?**
Sí, el cuadro de texto es totalmente editable a mano. La generación con
IA es una ayuda, no un paso obligatorio.

**Si pido "ampliar" la FAQ, ¿se borra lo que ya tenía?**
Depende de lo que hagas. La IA toma lo que ya hay guardado como referencia y
genera un **borrador**; hasta que no lo guardas, tu FAQ actual no cambia. Si lo
guardas, el borrador sustituye al anterior, así que copia antes lo que quieras
conservar.

**¿En qué formato se publica?**
En Markdown, el mismo formato ligero que usa el resto de documentos del
plugin — fácil de leer tanto para personas como para sistemas de IA.

**¿Aparece esta FAQ en alguna página visible de mi web?**
Se publica como documento propio (Markdown + entrada en Registro) y se
enlaza desde `/llms.txt`. Si quieres mostrarla también como una página
normal para tus visitantes, tendrías que añadirla tú aparte con el
contenido de aquí.

---
[Ver también: [Negocio](tab-negocio.md) · [Registro](tab-registro.md)]
