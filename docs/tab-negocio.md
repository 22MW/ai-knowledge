# Pestaña Negocio

[← Volver al índice](index.md)

Aquí guardas los datos de tu negocio en sí — lo que no depende de
WooCommerce y por tanto tiene sentido tengas o no tienda online: quién
eres, a qué te dedicas, cómo contactarte. Estos datos alimentan el
chatbot de la pestaña [Genix](tab-chatbot.md) (si tienes Support Genix) y el resumen público
que aparece en `/llms.txt`.

> Lo específico de tu tienda WooCommerce (envíos, impuestos, pagos) vive
> en su propia pestaña: [WooCommerce](tab-woocommerce.md).

## 1. Datos

Un formulario sencillo con la información base de tu negocio. Cada campo
te explica con un ejemplo qué se espera en él. Guárdalo antes de pasar al
siguiente paso — el resumen de IA de abajo se genera a partir de estos
datos, así que cuanto más completos estén, mejor saldrá.

[SCREENSHOT: formulario de datos de negocio con varios campos rellenados]

## 2. Resumen para llms.txt (con IA)

Un resumen corto y público que se usa como carta de presentación de tu
negocio en `/llms.txt`, el archivo que leen los buscadores de IA para
entender rápidamente de qué va tu web.

Puedes:

- Escribir instrucciones simples ("hazlo más breve", "menciona que
  hacemos envíos internacionales") o pegar un prompt completo con el
  formato exacto que quieras.
- Generar o pulir el resumen con IA cuantas veces quieras.
- Editarlo a mano libremente antes de guardarlo.

> Guardar o borrar este resumen **no modifica** los datos de negocio del
> paso 1 — son dos cosas independientes. Si el resumen se queda corto,
> amplía primero los datos de arriba, guárdalos, y genera de nuevo.

## Preguntas frecuentes

**¿Qué pasa si no relleno ningún dato aquí?**
El chatbot y el resumen de `/llms.txt` tendrán menos contexto real sobre
tu negocio. No falla nada, pero el resultado será más genérico.

**¿El resumen de llms.txt es visible para mis visitantes?**
Es un archivo público (como `robots.txt`), pensado para que lo lean
buscadores y asistentes de IA, no para mostrarse como una página normal
de tu web.

**¿Puedo pedir que el resumen tenga un tono concreto (formal, cercano,
con emojis)?**
Sí, escríbelo en el campo de instrucciones antes de generar. Esas
instrucciones prevalecen sobre el formato por defecto.

**¿Se puede tener el resumen en varios idiomas?**
El resumen de negocio se genera en el idioma principal del sitio. Si tu web es
multiidioma, el documento añade al final una nota con los demás idiomas en los
que está disponible.

---
[Ver también: [Genix](tab-chatbot.md) · [FAQs](tab-faqs.md) · [Visibilidad IA](tab-visibilidad-ia.md)]
