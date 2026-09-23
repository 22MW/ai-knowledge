# Instalación y puesta en marcha

[← Volver al índice](index.md)

Esta guía te lleva desde cero hasta tener tus primeros documentos de
conocimiento generados y publicados. No hace falta ningún conocimiento
técnico: cada paso se hace desde el panel de administración de WordPress.

## Antes de empezar

Necesitas:

- WordPress con PHP 7.4 o superior.
- Un origen de IA conectado: **Conectores nativos de WordPress** (si tu
  WordPress es 7.0 o superior, con Anthropic, OpenAI o Google configurado)
  o **Support Genix** instalado y activo.

Esto es todo. WooCommerce y WPML son opcionales: si los tienes, el plugin
los detecta solo y activa pantallas extra; si no los tienes, todo lo demás
funciona igual.

## Paso 1 — Instalar y activar

1. Sube la carpeta `ai-knowledge` a `/wp-content/plugins/` (o instala el
   `.zip` desde **Plugins → Añadir nuevo → Subir plugin**).
2. Actívalo desde **Plugins**.
3. Verás un nuevo menú en el panel lateral: **Base de conocimiento IA**.

[SCREENSHOT: menú "Plugins" con AI Knowledge & Visibility activado]

## Paso 2 — Conectar un origen de IA

Antes de generar nada, el plugin necesita saber de dónde saca la IA que
redacta los documentos. Ve a **[Ajustes](tab-ajustes.md)** y elige:

- **Conectores de WordPress** (recomendado si tu WordPress es 7.0+): usa
  la IA que ya tengas conectada en `Ajustes → Conectores`.
- **Support Genix**: si usas Support Genix como chatbot, el plugin puede
  usar la misma conexión de IA.

> Sin uno de los dos conectado, el plugin no puede generar documentos —
> el resto de pantallas seguirán funcionando para configurar datos, pero
> la generación con IA fallará hasta que conectes un origen.

## Paso 3 — Decidir qué contenido entra

Ve a **[Contenido](tab-contenido.md)** y elige qué tipos de contenido
(productos, páginas, entradas, o cualquier tipo de contenido personalizado
que tengas) quieres que forme parte de tu base de conocimiento. Puedes
afinar por categorías, etiquetas, IDs concretos, o dejarlo abierto a "todo
lo público".

Nada se genera para lo que no esté incluido aquí — es el primer paso y el
más importante, porque todo lo demás depende de este alcance.

## Paso 4 — Generar los primeros documentos

Ve a **[Generación masiva](tab-generacion-masiva.md)** y pulsa **"Generar
pendientes"**. El plugin empieza a crear los documentos en segundo plano,
por lotes, respetando el límite diario que tengas configurado (para no
disparar tu gasto de IA de golpe).

[SCREENSHOT: pantalla de Generación masiva con el contador de alcance y el botón "Generar pendientes"]

## Paso 5 — Revisar el resultado

Ve a **[Registro](tab-registro.md)** para ver cómo avanza: qué está en
cola, qué se generó bien, y si algo falló. Desde ahí puedes regenerar,
editar a mano o borrar cualquier documento individual.

## Y ahora, si tienes WooCommerce o Support Genix

- Con **WooCommerce** activo, rellena la pestaña
  **[WooCommerce](tab-woocommerce.md)** con tus datos de envío, impuestos
  y pagos (muchos se detectan solos).
- Con **Support Genix** activo, configura tu chatbot en la pestaña
  **[Genix](tab-chatbot.md)** para que responda con los datos reales de tu
  negocio.
- Revisa **[Visibilidad IA](tab-visibilidad-ia.md)** para comprobar que
  los buscadores de IA pueden acceder de verdad a lo que acabas de
  publicar.

## Preguntas frecuentes

**¿Cuánto tarda en generar todo el catálogo?**
Depende del límite diario configurado en Generación masiva (pensado
justamente para controlar el gasto de IA) y de cuánto contenido tengas.
Puedes subir el límite o desactivarlo temporalmente para cargas grandes
supervisadas.

**¿Puedo probar con solo unos pocos productos antes de generar todo el
catálogo?**
Sí. En Contenido puedes limitar el alcance a una categoría concreta o a
unos pocos IDs sueltos, generar esos, revisar el resultado, y luego abrir
el alcance al resto cuando estés conforme.

**¿Qué pasa si no tengo ni Conectores de WordPress ni Support Genix?**
El plugin se instala y todas las pantallas de configuración funcionan,
pero no podrá generar documentos con IA hasta que conectes uno de los
dos orígenes.

**¿Hace falta configurar algo en el servidor?**
No. El plugin crea automáticamente lo que necesita (tabla de base de
datos, carpeta pública para los documentos) al activarse.

---
[VIDEO: recorrido completo de instalación y primera generación, 2-3 min]
