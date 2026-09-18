# Pestaña WooCommerce

[← Volver al índice](index.md)

> Esta pestaña solo aparece si tienes **WooCommerce activo**. Sin
> WooCommerce, no hay envíos, impuestos ni pagos reales que detectar, así
> que simplemente no la verás en el menú.

Aquí vive toda la información de tu tienda que se convierte en documentos
propios (cómo comprar, condiciones, envío y pago, catálogo) para que el
chatbot y los buscadores de IA la conozcan de verdad.

WooCommerce te lo **detecta todo solo** la primera vez que abres esta
pantalla, pero cada campo es editable: una vez que guardas algo, ese
valor se queda fijo aunque después cambies o borres algo en WooCommerce.

[SCREENSHOT: pestaña WooCommerce con los datos generales precargados]

## Datos generales

Nombre de la tienda, moneda, país base, condiciones de venta y política
de devoluciones. Si tienes páginas reales de condiciones/devoluciones
configuradas en WooCommerce, aparece un enlace directo para editarlas ahí
mismo.

## Métodos de pago, envíos, impuestos y catálogo

Cuatro bloques con selección por checkbox: eliges qué pasarelas de pago,
zonas de envío, tipos de impuesto y categorías de catálogo quieres que
aparezcan documentados. Por defecto salen todos marcados si nunca has
guardado esta pestaña; si guardas sin marcar nada en un bloque, ese bloque
queda vacío a propósito (no es un error).

## Datos que WooCommerce no expone

Algunos datos no existen como tal en WooCommerce y hay que escribirlos a
mano:

- **Pedido mínimo / envío gratis** — solo hace falta si no tienes ya un
  método de envío gratuito con importe mínimo configurado (si lo tienes,
  se detecta solo).
- **Recogida en tienda** — igual: solo si no usas ya el método de
  recogida local de WooCommerce.
- **Plazo de entrega** — WooCommerce no tiene un campo real para esto,
  así que se escribe en texto libre (ej. "2-4 días laborables en
  Península").
- **Contacto y horario de la tienda online** — solo si es distinto del
  contacto general que ya pusiste en [Negocio](tab-negocio.md). Si lo
  dejas vacío, se usa automáticamente el de Negocio.
- **Notas legales adicionales** — cualquier aviso legal extra que quieras
  incluir.

## Generar los documentos de tienda

A diferencia de tus productos y páginas (que se generan solos según su
propio ciclo), los documentos de "información de tienda" y "catálogo" son
compuestos: no dependen de un contenido concreto, sino de toda esta
configuración junta. Por eso **no se generan solos** al guardar cambios
aquí — tienes que pulsar **"Generar/actualizar ahora"** cuando quieras
que reflejen los datos más recientes.

Cada documento generado se puede pulir con IA (solo redacción y formato,
sin cambiar ni inventar datos) directamente desde esta pantalla, para
cada idioma activo.

## Preguntas frecuentes

**Si cambio mis impuestos en WooCommerce, ¿se actualiza el documento
solo?**
No automáticamente. Cambia el dato en WooCommerce y luego pulsa
"Generar/actualizar ahora" en esta pestaña para que el documento refleje
el cambio.

**¿Por qué mis métodos de pago desactivados en WooCommerce aparecen
marcados igual?**
No aparecen premarcados: si un método está desactivado en WooCommerce,
sale sin marcar por defecto y con la etiqueta "(desactivado)" para que lo
identifiques fácilmente.

**Guardé la pestaña sin marcar ninguna categoría de catálogo, ¿es un
error?**
No, es intencional: guardar sin marcar nada en un bloque significa "no
incluir nada de ese bloque", no "usar todo por defecto".

**¿Puedo pulir el texto del documento de tienda con instrucciones
propias?**
Sí, pero solo puede reordenar o mejorar la redacción de los datos que ya
tiene — no puede añadir ni inventar datos nuevos que no le hayas dado.

---
[Ver también: [Negocio](tab-negocio.md) · [Registro](tab-registro.md)]
