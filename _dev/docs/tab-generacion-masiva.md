# Pestaña Generación masiva

[← Volver al índice](index.md)

Genera de golpe todos los documentos pendientes de tu alcance configurado
en [Contenido](tab-contenido.md), y controla la velocidad a la que se
generan para no disparar tu gasto de IA.

## El resumen de arriba

Antes de generar nada, esta pantalla te dice:

- Cuántos elementos y en cuántos idiomas entran en tu alcance actual
  (elementos × idiomas = documentos posibles).
- Cuántos ya están sincronizados.
- Tu límite diario actual (o un aviso bien visible si lo tienes
  desactivado).

[SCREENSHOT: resumen de alcance con el contador de elementos x idiomas]

## Generar pendientes

Es el botón que usarás casi siempre. Es **seguro repetirlo**: no vuelve a
generar lo que ya está sincronizado y sin cambios, solo lo nuevo o lo que
falló anteriormente.

## Reiniciar todo

Este botón sí **regenera absolutamente todo**, incluido lo que ya estaba
sincronizado. Gasta IA de más y no se puede deshacer — por eso el plugin
te pide confirmación explícita antes de lanzarlo. Úsalo solo si quieres
forzar una regeneración completa (por ejemplo, tras cambiar el largo del
texto en [Ajustes](tab-ajustes.md) y querer que todo el catálogo se
adapte al nuevo tamaño).

> Mientras hay una generación en curso, verás un aviso y un botón para
> **cancelarla** en cualquier momento.

## Cómo funciona por dentro (para que no te asustes si tarda)

La generación no ocurre toda de golpe: se procesa **por lotes**, en
segundo plano, usando el sistema de tareas programadas de WordPress
(Action Scheduler). Puedes seguir el progreso en detalle desde
**Herramientas → Scheduled Actions** (grupo `woo-kb`), aunque para el uso
normal te basta con mirar la pestaña [Registro](tab-registro.md).

## Ajustes de la cola

Tres valores que controlan el ritmo de generación:

- **Límite diario de generaciones** — el techo de documentos que se
  generan por día. Puedes marcar "Sin límite" para cargas manuales
  puntuales que estés supervisando en directo — pero acuérdate de
  desactivarlo al terminar, o seguirá sin límite indefinidamente.
- **Tamaño de lote** — cuántos documentos se procesan juntos en cada
  ejecución.
- **Retraso de debounce** — un pequeño margen de espera para no saturar
  el servidor con peticiones seguidas.

## Preguntas frecuentes

**¿Puedo cerrar la pestaña del navegador mientras genera?**
Sí, la generación ocurre en segundo plano en el servidor, no depende de
que tengas la página abierta.

**¿Qué pasa si se corta la conexión o el servidor a mitad de una carga
grande?**
Puedes retomarla desde el botón "Reiniciar cola" en la pestaña
[Registro](tab-registro.md), que continúa donde se quedó.

**¿"Generar pendientes" vuelve a gastar IA en lo que ya generé?**
No, solo genera lo nuevo o lo que falló. Para regenerar también lo que ya
está sincronizado, usa "Reiniciar todo" (con su aviso correspondiente).

**¿Por qué tengo un límite diario y no puedo generar todo de una vez?**
Es una protección para que no gastes tu presupuesto de IA de golpe sin
darte cuenta. Puedes subirlo o desactivarlo temporalmente si necesitas
una carga grande puntual.

---
[Ver también: [Contenido](tab-contenido.md) · [Registro](tab-registro.md) · [Ajustes](tab-ajustes.md)]
