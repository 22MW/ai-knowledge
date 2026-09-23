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
preguntas y respuestas.

Si ya tenías una FAQ guardada (en ese idioma), la IA la **amplía o
mejora** en lugar de partir de cero. Puedes repetir el proceso todas las
veces que haga falta: amplía primero los datos de Negocio y guárdalos, o
edita la FAQ a mano abajo y guárdala, y vuelve a generar para pulir el
resultado.

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

Verás un selector de idioma arriba: cada idioma tiene su propia FAQ y su
propio documento publicado, igual que el resto del contenido de tu web.

## Preguntas frecuentes

**¿Puedo escribir la FAQ yo mismo, sin usar la IA?**
Sí, el cuadro de texto es totalmente editable a mano. La generación con
IA es una ayuda, no un paso obligatorio.

**Si pido "ampliar" la FAQ, ¿se borra lo que ya tenía?**
No, la IA parte de lo que ya hay guardado y lo mejora o añade sobre ello,
no empieza de cero salvo que el campo esté vacío.

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
