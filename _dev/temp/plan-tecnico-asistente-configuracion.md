# Diseño técnico — Asistente de configuración

## 1. Áreas afectadas

- Admin principal existente en admin/class-admin.php.
- Vistas nuevas del asistente en admin/views/.
- JavaScript del admin y estilos del tema existente.
- Activación del plugin en ai-knowledge.php.
- Handlers admin-post y AJAX ya registrados.
- Internacionalización POT, PO y JSON existentes.

No se crea una configuración paralela para productos, documentos, IA,
WooCommerce o crawlers.

## 2. Entrada y apertura

### 2.1 Menú

Añadir una página propia subordinada a la pantalla principal de AI Knowledge,
con el título «Asistente de configuración».

### 2.2 Fila de plugins

Registrar un enlace contextual en los enlaces de acción del plugin:

«Abrir asistente» → página del asistente.

Debe aparecer solo para usuarios con la capability actual del plugin.

### 2.3 Primera activación

La activación marca un estado inicial sin ejecutar generaciones ni llamadas IA.
La redirección automática se hará una sola vez, después de la activación, y
solo si el usuario tiene permisos. Si no puede redirigir, quedará un aviso y
el enlace manual seguirá disponible.

## 3. Estado persistente

Usar una opción propia y pequeña, por ejemplo un estado de asistente con:

- versión del flujo;
- iniciado;
- paso actual;
- pasos completados;
- pasos saltados;
- terminado;
- última apertura.

No duplicar ajustes existentes. Cada paso lee y guarda mediante los métodos
actuales.

## 4. Navegación y guardado

- Cada paso tendrá un identificador estable.
- Atrás solo cambia la vista y conserva el formulario en memoria.
- Continuar valida, guarda el paso y actualiza el estado.
- Saltar actualiza solo el estado y no inventa valores.
- Salir guarda el estado actual y vuelve al panel.
- Reabrir busca el primer paso pendiente.
- Terminar guarda el estado final sin obligar a generar documentos.

Se conservará un fallback POST tradicional si JavaScript no está disponible.
AJAX se usará solo para comprobaciones de conexión, guardados parciales y
acciones que ya dispongan de handler AJAX.

## 5. Reutilización de handlers

El asistente debe llamar o adaptar los handlers actuales de:

- ajustes de IA;
- Contenido;
- Negocio;
- WooCommerce;
- Chatbot;
- crawlers;
- robots.txt y .htaccess;
- generación inicial.

No duplicar validaciones, nonces, sanitización ni reglas de cola.

## 6. Pantallas y dependencias

Cada pantalla será una vista corta. Las secciones 2.3, 3.2, 3.5 y 3.7
mostrarán bloques condicionales dentro de la misma pantalla cuando proceda.

WooCommerce avanzado y la configuración completa de conectores se abrirán en
ventana nueva con target seguro y explicación de que pueden completarse
después.

## 7. Seguridad

- Capability check antes de renderizar y guardar.
- Nonce propio por cada guardado o acción.
- Sanitización equivalente a las pantallas actuales.
- Escaping de todo texto y URL.
- Respuestas AJAX sin claves ni datos sensibles.
- Enlaces externos con rel="noopener noreferrer".
- No ejecutar generación, robots.txt o .htaccess al abrir el asistente.
- Confirmación explícita antes de acciones de escritura o generación.

## 8. Visual

- Reutilizar wookb-theme.css, Tabler y las clases de botón existentes.
- Sin iconos dentro de textos.
- Sin colores nuevos.
- Modo claro/oscuro heredado de wookb-wrap y de la preferencia actual.
- Línea de progreso con contraste probado en ambos modos.
- Diseño usable en un admin estrecho sin depender solo del color.

## 9. Validación prevista

### 9.1 Técnica

- php -l en cada PHP nuevo o modificado.
- git diff --check.
- POT actualizado con todos los textos.
- JSON de cada locale regenerado.

### 9.2 Funcional

- Primera activación y redirección única.
- Abrir desde menú, Ajustes y fila del plugin.
- Avanzar, volver, saltar, salir y reanudar.
- Configuración existente conservada.
- WooCommerce y Support Genix condicionales.
- Conexiones comprobadas de nuevo.
- Enlaces externos en ventana nueva.
- Generación opcional y cola existente.
- Prompt GEO descargable.

### 9.3 Regresión

- Panel normal sin asistente.
- Ajustes y formularios existentes.
- Usuarios sin permisos.
- JavaScript desactivado.
- Nonce caducado.
- Modo claro y oscuro.
- Español, catalán e inglés.

## 10. Orden de implementación

1. Estado, menú, fila del plugin y apertura inicial.
2. Shell visual, progreso y navegación.
3. Bienvenida, IA y Contenido.
4. Negocio, WooCommerce y Chatbot condicional.
5. Visibilidad IA y enlaces avanzados.
6. Ajustes, generación y final.
7. Prompt GEO descargable.
8. i18n, QA y documentación.

## 11. Bloqueo antes de programar

El diseño funcional está validado. Antes de implementar se debe aprobar este
diseño técnico y concretar los nombres definitivos de la opción de estado y de
la capability reutilizada.
