# Plan funcional — Asistente de configuración de AI Knowledge

## 1. Objetivo

Guiar la primera configuración sin mostrar todas las opciones a la vez. El
asistente será opcional, reanudable y siempre accesible después.

## 2. Reglas generales

### 2.0 Criterios visuales

- No incluir iconos dentro de los textos, títulos, botones o mensajes del
  asistente.
- Usar una interfaz limpia, con colores sencillos y jerarquía clara.
- Reutilizar exclusivamente los colores globales y las clases visuales ya
  definidas por AI Knowledge y Tabler.
- No crear colores nuevos ni variantes visuales aisladas para el asistente.
- Mantener el modo claro y el modo oscuro existentes del plugin.
- El cambio de modo debe funcionar durante todo el asistente y conservar la
  preferencia actual del usuario.
- Revisar contraste de texto, botones, estados, enlaces y línea de progreso en
  ambos modos.

### 2.1 Primera apertura

- Se abre automáticamente una sola vez después de activar el plugin.
- No se vuelve a abrir automáticamente si ya se inició, saltó o terminó.
- Solo se muestra a usuarios con permisos suficientes.

### 2.2 Reapertura

Se podrá abrir desde un nuevo menú, desde «Abrir asistente» bajo el nombre del
plugin en la pantalla de plugins y desde un enlace visible en Ajustes.

### 2.3 Conservación

Cada pantalla lee la configuración actual. Al relanzar el asistente no se
empieza desde cero ni se sustituyen valores guardados por valores iniciales.

### 2.4 Navegación

Arriba habrá una línea de círculos numerados. Los pasos completados serán
enlaces para volver. «Atrás» conserva datos, «Saltar este paso» lo deja
pendiente, «Salir» conserva el progreso, «Continuar» guarda y avanza, y
«Terminar» marca el asistente como completado.

### 2.5 Ayuda e internacionalización

Cada pantalla explica qué configura y cómo ayuda al plugin. Todos los títulos,
ayudas, botones, errores y avisos entran en el POT y usan gettext o
JavaScript i18n.

## 3. Pantallas

### 3.1 Bienvenida

Explica qué es AI Knowledge, qué hará el asistente y que puede relanzarse
desde el menú y Ajustes. Acciones: Empezar, Saltar asistente y Salir.

### 3.2 Origen de IA

Una única pantalla adapta su contenido según las conexiones detectadas.

#### 3.2.1 Orígenes disponibles

Mostrar solo Conectores de WordPress y Support Genix cuando esté activo.

#### 3.2.2 Selección y prioridad

Un origen configurado queda seleccionado. Si hay dos, se priorizan los
Conectores de WordPress. Se mantiene el modelo guardado y se usa Automático
cuando corresponda.

#### 3.2.3 Conexiones pendientes

Si no hay conexión, mostrar en ventana nueva los enlaces de Conectores de
WordPress y Support Genix si existe. Añadir «Volver a comprobar conexiones»,
«Continuar sin IA» y acceso a Ajustes completos. Nunca guardar claves en AI
Knowledge.

### 3.3 Contenido y alcance

#### 3.3.1 Sin configuración previa

Mostrar los CPT públicos incluidos por defecto y permitir elegir inclusiones y
exclusiones principales.

#### 3.3.2 Con configuración previa

Cargar valores guardados y no restaurar predeterminados. Mostrar un resumen y
enlazar a Contenido.

#### 3.3.3 Opciones avanzadas

Taxonomías, términos, IDs y campos personalizados se configuran en la pantalla
completa de Contenido.

### 3.4 Negocio

Mostrar el formulario completo de Datos del negocio y el resumen existente,
pero sin generación IA: nombre, dirección, país, idioma, público objetivo,
contacto, horario, enfoque y demás campos actuales.

Acciones: Guardar y continuar, Saltar y Abrir Negocio completo.

### 3.5 WooCommerce

Solo aparece si WooCommerce está activo.

#### 3.5.1 Datos generales

Nombre de tienda, moneda, país base, pedido mínimo o envío gratis, recogida y
plazo de entrega.

#### 3.5.2 Contacto y horario

Mostrar el formulario con el aviso original: esos datos pueden proceder de
Negocio.

#### 3.5.3 Configuración avanzada

Mostrar una explicación breve: esta configuración es avanzada y puede hacerse
después desde la pantalla completa de WooCommerce. El enlace debe abrir esa
pantalla en una ventana nueva para no perder el progreso del asistente.

El enlace llevará a la configuración completa de envíos, impuestos, pagos,
categorías y catálogo.

### 3.6 Chatbot

Solo aparece si Support Genix está activo. Incluye ajustes del chat, límite de
documentos relacionados, páginas de referencia, información adicional,
comportamiento, borrador editable, pulido y sincronización.

### 3.7 Visibilidad IA

#### 3.7.1 Explicación

Explicar llms.txt, Markdown, JSON y JSON-LD.

#### 3.7.2 Crawlers

Dividir la configuración por tipo: búsqueda y citas IA, asistentes bajo
demanda, entrenamiento, SEO y scraping, archivado y datasets, y scanners de
seguridad. Cada tipo explica para qué sirve permitirlo o bloquearlo y cuándo
usar solo llms.txt.

#### 3.7.3 robots.txt

Mostrar comparación actual/propuesta. Para aplicar, exigir descarga previa,
conservar reglas externas en conflicto como comentarios y sustituir solo el
bloque propio.

#### 3.7.4 .htaccess

Mostrar el bloque generado y explicar dónde está, cómo descargarlo, cómo
copiarlo manualmente y por qué la escritura exige copia previa.

### 3.8 Ajustes y primera generación

#### 3.8.1 Ajustes

Mostrar largo del texto, tokens de salida y aviso IndexNow.

#### 3.8.2 Decisión

Explicar que se crean los documentos principales y el resto queda en cola,
respetando el límite diario. Acciones: Guardar configuración, Generar
documentos iniciales y Terminar sin generar. Debe reutilizarse la cola actual.

### 3.9 Final

Mostrar configuración guardada, pasos saltados, documentos creados, cola
pendiente y enlace a llms.txt. Añadir accesos a Registro, Contenido, Negocio,
WooCommerce, Visibilidad IA y Ajustes.

#### 3.9.1 Prompt GEO

Crear un prompt descargable en Markdown para otra IA. Debe pedir comprobar
visibilidad, llms.txt, Markdown, enlaces, JSON, JSON-LD, esquemas, respuestas
HTTP, estructura GEO y cola pendiente. Debe avisar que el análisis es fiable
cuando todos los documentos estén creados y la cola vacía.

## 4. Estado

Guardar solo iniciado, versión del flujo, paso actual, pasos completados o
saltados, finalizado y última apertura. No duplicar productos, documentos ni
credenciales.

## 5. Casos límite

- Sin permisos: no mostrar.
- WooCommerce inactivo: ocultar su pantalla.
- Genix inactivo: ocultar Chatbot.
- Sin IA: permitir continuar sin generación IA.
- Configuración existente: cargarla, nunca reiniciarla.
- Error AJAX o cierre: reanudar desde el último guardado.
- Actualización: no reabrir si ya terminó.
- Nuevo paso futuro: pedir solo la nueva decisión.

## 6. Criterios de aceptación

- Apertura automática única.
- Acceso desde menú, Ajustes y fila del plugin.
- Atrás, saltar, salir y reanudar funcionando.
- Pasos largos divididos en subpasos.
- Configuración existente conservada.
- WooCommerce y Genix condicionales.
- Primera generación usa la cola actual.
- Prompt GEO descargable.
- Todos los textos incluidos en POT.
- Español, catalán e inglés disponibles.

## 7. Siguiente paso

El resto del plan queda validado. Solo quedan por aplicar estas decisiones de
presentación en la fase técnica:

- Origen de IA en una pantalla con bloques condicionales.
- WooCommerce avanzado en ventana nueva, como configuración posterior.

Después se preparará el diseño técnico de hooks, estado, permisos, AJAX,
activación inicial y reutilización de handlers.
