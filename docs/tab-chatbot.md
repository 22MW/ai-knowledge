# Pestaña Chatbot

[← Volver al índice](index.md)

> Esta pestaña solo aparece si tienes **Support Genix activo**. Sin él, no
> hay ningún chatbot al que enviar este prompt, así que la pestaña
> permanece oculta.

Aquí defines **cómo debe comportarse** el chatbot de tu web: su tono, qué
debe hacer cuando no sabe algo, y qué no debe hacer nunca. Los datos de tu
negocio en sí (dirección, contacto, horario...) no se rellenan aquí, sino
en [Negocio](tab-negocio.md) — este cuestionario los combina
automáticamente al generar el borrador.

No hace falta acertar a la primera: puedes responder lo que sepas, generar
un primer borrador, editarlo a mano, pulirlo con IA, o añadir más
respuestas al cuestionario y volver a generar para mejorarlo.

## 1. Ajustes del chat

Un único ajuste por ahora: cuántos "Documentos relacionados" muestra el
chatbot como máximo bajo cada respuesta (0 = sin límite).

## 2. Cuestionario

Responde a un pequeño cuestionario sobre cómo quieres que hable tu
chatbot. Opcionalmente puedes:

- **Añadir páginas de referencia** (hasta 3, por ID o URL): el plugin lee
  el contenido real de esas páginas para dar más contexto a la IA — útil
  si tienes una página "Sobre nosotros" con un tono muy concreto que
  quieres que el chatbot imite.
- **Añadir información extra**: cualquier instrucción o dato adicional
  que quieras pegar para esa generación en concreto.

[SCREENSHOT: cuestionario del chatbot con el campo de páginas de referencia]

Pulsa **"Generar borrador con IA"** para obtener un primer texto.

## 3. Borrador editable

El resultado aparece en un cuadro de texto totalmente editable. Cuando
termines de ajustarlo puedes:

- **Pulir la redacción con IA** (mejora la forma, sin tocar el fondo de lo
  que ya escribiste).
- **Guardar y sincronizar con Genix**, para que el chatbot empiece a
  usarlo de inmediato.

## Preguntas frecuentes

**¿Esto es lo mismo que las FAQs?**
No. Las [FAQs](tab-faqs.md) son contenido público, pensado para que lo
lea cualquiera. El prompt del chatbot son instrucciones internas de
comportamiento — nunca se muestran tal cual a tus visitantes.

**¿Qué pasa si no relleno el cuestionario y genero directamente?**
La IA generará un borrador más genérico, con menos detalle sobre tu
negocio concreto. Cuantas más respuestas le des (aquí y en Negocio),
mejor será el resultado.

**¿Puedo escribir el prompt yo mismo, sin pasar por el cuestionario?**
Sí, el cuadro del paso 3 es libre: puedes escribir o pegar directamente
tu propio texto y guardarlo, sin generar nada con IA primero.

**¿Cada vez que genero se pierde lo que ya tenía guardado?**
No, "Generar borrador con IA" reemplaza el borrador en pantalla, pero
hasta que no pulses "Guardar y sincronizar con Genix" no se sobrescribe
lo que el chatbot está usando en producción.

---
[Ver también: [Negocio](tab-negocio.md) · [FAQs](tab-faqs.md)]
