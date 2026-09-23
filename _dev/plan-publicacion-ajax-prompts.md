# Plan de cambio: publicación pública, generación AJAX y prompt por documento

Estado: aprobado funcionalmente por el usuario el 2026-09-23. Pendiente de
implementación y validación. Este documento conserva el plan detallado; no
describe funcionalidad ya realizada.

## Objetivo

1. Permitir marcar un documento del Registro como público desde “Ajustes
   avanzados”.
2. Los documentos públicos se publican como `.md` y aparecen en `/llms.txt`.
3. La generación individual se realiza desde Ajustes avanzados mediante AJAX,
   sin recargar la página.
4. Los documentos de documentación para Genix se guardan como copia íntegra
   del contenido original de la página, sin resumen, reescritura ni IA.
5. Para esa copia íntegra no se usa API de IA ni límite de caracteres.
6. Los documentos que sí se generen con IA pueden tener un prompt propio
   guardado por documento e idioma.
7. Ese prompt se envía a la IA junto con el contenido real de la página.
8. Si el prompt está vacío, se utiliza el prompt genérico actual.
9. El límite de caracteres se aplica a los documentos generados con IA, no a
   la copia íntegra de documentación.
10. AI Knowledge no crea ni actualiza posts `sgkb-docs` de Genix; conserva el
   Markdown propio que Genix pueda consumir.
11. Se pueden elegir tipos de contenido públicos, pero no se debe leer ni
   mostrar `sgkb-docs` como contenido fuente.
12. Solo se procesan contenidos publicados.

## Evaluación de partida

Es una funcionalidad de tamaño medio/grande porque afecta a Registro, modelo
de datos, generador IA, publicación Markdown, `/llms.txt`, AJAX, seguridad,
alcance de post types e integración con Genix.

Riesgo medio-alto. La separación más importante es:

- documento público `.md` y `/llms.txt`;
- documento propio usado internamente por AI Knowledge;
- post generado `sgkb-docs` de Genix.

## MVP incluido

### 1. Visibilidad pública por documento

En Ajustes avanzados de cada fila se añadirá un control para indicar si el
documento es público.

Un documento marcado como público:

- tiene un `.md` accesible públicamente;
- aparece en `/llms.txt`;
- invalida la caché de `/llms.txt` al cambiar su estado.

El documento destinado al chatbot no crea ni actualiza `sgkb-docs`. Su
Markdown propio se conserva y solo se expone públicamente si el usuario lo
marca como público.

No se borrarán automáticamente los `sgkb-docs` antiguos en esta fase.

### 2. Botón de generación AJAX

En Ajustes avanzados aparecerá un botón “Generar contenido”.

El flujo deberá:

- comprobar capability y nonce;
- regenerar únicamente la fila seleccionada;
- respetar idioma, límite específico y límite general;
- utilizar el prompt personalizado o el genérico;
- respetar el estado público y el alcance;
- actualizar el textarea, estado, fecha y mensaje de error sin recargar;
- bloquear el doble clic mientras procesa;
- mantener fallback al flujo tradicional si JavaScript está desactivado.

La generación actual es síncrona. AJAX evita la recarga, pero no convierte por
sí mismo el proceso en una cola en segundo plano.

### 3. Documentación íntegra para Genix

La documentación destinada a Genix no pasará por el generador IA. El plugin
debe tomar el contenido original del post publicado y guardarlo en Markdown,
conservando toda la información disponible.

Reglas:

- no resumir;
- no reescribir;
- no llamar a ningún proveedor de IA;
- no aplicar `char_limit`;
- no truncar el contenido por tokens;
- mantener título, cuerpo y datos relevantes que el extractor pueda obtener;
- guardar el resultado en el `.md` propio de AI Knowledge;
- publicar en `/llms.txt` solo si el documento está marcado como público.

La conversión a Markdown debe ser determinista. Si el origen contiene HTML,
se convertirá a Markdown sin pedir a una IA que lo redacte.

### 4. Prompt por documento

Se añadirá una sección “Prompt” dentro de Ajustes avanzados de cada documento,
no una pestaña global independiente.

Debe incluir:

- textarea “Prompt para este documento”;
- botón “Guardar prompt”;
- botón “Generar contenido”;
- explicación de que el contenido real del post siempre se envía delimitado;
- aviso de que la IA no puede inventar información ausente.

El prompt se guarda por documento y por idioma, ya que un origen puede tener
documentos diferentes por idioma WPML.

Si el prompt está vacío:

- se usa el prompt genérico actual;
- se usan los datos reales extraídos del post;
- se aplica el límite configurado.

Si el prompt tiene contenido:

- se envía como instrucción adicional o prompt principal;
- tiene prioridad sobre estilo, formato y estructura;
- no puede modificar ni inventar los datos fuente;
- no puede anular las reglas de seguridad del generador;
- no debe poder ordenar a la IA que revele instrucciones internas.

### 5. Límite de caracteres

Se reutiliza la prioridad existente:

1. límite guardado en la fila;
2. si no existe, límite general de Ajustes;
3. si tampoco existe, valor predeterminado del generador.

Debe aplicarse únicamente a documentos generados con IA:

- prompt genérico;
- prompt personalizado;
- generación AJAX;
- regeneración tradicional;
- resultado final Markdown generado con IA.

No debe aplicarse a la documentación íntegra copiada directamente desde el
contenido original.

No se debe confundir `output_tokens`, que limita la salida de la IA, con
`char_limit`, que limita la longitud del cuerpo Markdown.

### 6. Separación de Genix

El pipeline actual llama a `Genix_Bridge::upsert_document()` y marca siempre
`only_for_chatbot = 1`. El nuevo flujo debe dejar de crear o actualizar
`sgkb-docs` para estos documentos.

Flujo previsto para la documentación íntegra:

```text
post publicado
   ↓
extraer contenido completo
   ↓
convertir determinísticamente a Markdown
   ↓
guardar .md propio
   ↓
si es público → incluir en /llms.txt
   ↓
no crear ni actualizar sgkb-docs
```

Los `sgkb-docs` antiguos se mantienen intactos y no se actualizan desde AI
Knowledge. Si en el futuro se desea limpiarlos, será una tarea separada.

### 7. Solo contenido publicado

La implementación actual ya comprueba `post_status = publish` en varios
caminos, pero se debe auditar todo el flujo:

- generación masiva;
- generación por ID o URL;
- regeneración desde Registro;
- botón del editor;
- inclusión manual por ID;
- REST;
- feeds;
- traducciones WPML;
- documentos WooCommerce;
- documentos compuestos de tienda;
- cambios posteriores de estado.

Regla final:

- solo se generan documentos para posts publicados;
- borradores, privados, pendientes y papelera no se procesan;
- si un post publicado pasa a borrador, deja de publicarse su `.md` y se
  retira de `/llms.txt`;
- una URL no publicada no entra en el feed público.

## Exclusiones

Queda fuera de este cambio:

- rediseñar todo el sistema de colas;
- crear generación IA en segundo plano con polling;
- borrar automáticamente todos los `sgkb-docs` antiguos;
- cambiar crawlers, `robots.txt` o `.htaccess`;
- crear una pestaña global de prompts para todos los documentos;
- modificar el contenido del chatbot de Support Genix;
- editar archivos internos del plugin Genix.

## Protección de `sgkb-docs`

Aunque el usuario pueda elegir tipos públicos, `sgkb-docs` no debe entrar como
fuente.

### Investigación del slug de Genix

La instalación de Support Genix Lite dentro de este workspace está en
`app/public/wp-content/plugins/support-genix-lite`. La investigación confirma
una distinción importante:

- El identificador interno del post type sigue siendo literalmente
  `sgkb-docs`, en `modules/Apbd_wps_knowledge_base.php` dentro de
  `register_post_type()`.
- El ajuste “Slug individual de documento” se guarda como `docs_single_slug` y
  solo modifica `rewrite['slug']` al registrar el CPT.
- La prueba realizada por el usuario con `new-genix-slug` cambia la URL visible
  del documento, pero no cambia el `post_type` interno `sgkb-docs`.

Por tanto, AI Knowledge debe seguir identificando el CPT interno mediante la
API/registro de WordPress y no intentar usar el rewrite slug (`new-genix-slug`)
como `post_type`. Cualquier enlace o URL de Genix debe obtenerse desde el
objeto real del post type (`get_post_type_object()`/permalink), nunca
construirse suponiendo que ambos slugs son iguales.

La exclusión del contenido fuente debe proteger el identificador interno
`sgkb-docs`; el cambio de `docs_single_slug` no requiere cambiar esa exclusión.

La exclusión debe aplicarse en:

- selector visual de tipos de contenido;
- modo “Todos los tipos públicos”;
- resolución de alcance;
- inclusión manual por ID;
- generación por ID o URL;
- meta box del editor;
- REST y feeds;
- cola automática;
- regeneración manual.

La comprobación debe hacerse por tipo de contenido y no solo por la etiqueta
que se muestra en la interfaz.

## Archivos y áreas probables

- `includes/class-registry.php`: estado público, consultas de documentos y
  filas que entran en `/llms.txt`.
- `includes/class-document-pipeline.php`: separar Markdown público/propio de
  la integración Genix y aplicar estado público.
- `includes/class-generator.php`: aceptar prompt por documento, combinarlo con
  datos delimitados y aplicar límite.
- `includes/class-scope.php`: excluir `sgkb-docs` y garantizar posts publicados.
- `includes/class-llms-txt.php`: incluir solo filas públicas y sincronizadas e
  invalidar caché.
- `admin/class-registry-table.php`: controles, prompt y botón AJAX.
- `admin/class-admin.php`: acciones AJAX, guardado, generación y permisos.
- `assets/admin.js`: peticiones AJAX, carga, avisos y actualización de UI.
- `admin/views/tab-contenido.php`: ocultar `sgkb-docs`.
- `includes/class-editor-metabox.php`: impedir meta box para `sgkb-docs`.
- migración de esquema o persistencia compatible para visibilidad y prompt.

## Seguridad

Cada acción AJAX debe comprobar:

- capability del plugin;
- nonce específico;
- ID de fila válido;
- existencia de la fila;
- existencia y publicación del post origen;
- sanitización y longitud máxima del prompt;
- que el usuario pueda modificar esa fila;
- que no se acepte arbitrariamente un `post_type` desde `POST`.

El prompt debe escaparse al mostrarlo y enviarse delimitado a la IA. El
contenido de la página no puede convertirse en instrucciones internas.

## Casos límite

- prompt vacío;
- prompt demasiado largo;
- doble clic en “Generar”;
- error de la IA;
- respuesta vacía;
- post borrado;
- post que pasa de publicado a borrador;
- traducción WPML inexistente;
- Genix desactivado;
- documento antiguo asociado a `sgkb-docs`;
- `.md` existente;
- `/llms.txt` en caché;
- usuario sin permisos;
- petición AJAX interrumpida;
- documento en modo manual.

## Criterios de aceptación

1. Un documento público tiene enlace `.md` accesible.
2. Aparece en `/llms.txt`.
3. Al quitarlo de público desaparece de `/llms.txt`.
4. El cambio invalida la caché.
5. “Generar contenido” funciona sin recargar.
6. El resultado aparece en el editor de la fila.
7. Se conserva el estado de éxito o error.
8. El prompt se guarda por documento e idioma.
9. El prompt guardado se utiliza en la siguiente generación.
10. Con prompt vacío se usa el prompt genérico.
11. El límite se respeta en ambos casos.
12. No se crean ni actualizan posts `sgkb-docs`.
13. `sgkb-docs` no aparece en configuración.
14. No entra por “Todos los tipos públicos”.
15. Los posts no publicados no generan `.md`.
16. Los posts no publicados no aparecen en `/llms.txt`.
17. Al pasar a borrador dejan de estar publicados.
18. El flujo tradicional sigue funcionando sin JavaScript.
19. Permisos, nonces y sanitización pasan revisión.
20. Los `sgkb-docs` antiguos no se eliminan automáticamente.

## Validación prevista

### Validación estática

- `php -l` en todos los PHP modificados;
- revisión de hooks AJAX;
- revisión de nonces y capabilities;
- búsqueda de todas las llamadas a `Genix_Bridge`;
- búsqueda de todos los usos de `get_post_types()`;
- revisión de consultas que acepten posts no publicados.

### QA funcional en WordPress

- crear documento público;
- crear documento de uso interno/chatbot;
- guardar prompt vacío;
- guardar prompt personalizado;
- regenerar por AJAX;
- regenerar sin JavaScript;
- comprobar `.md`;
- comprobar `/llms.txt`;
- cambiar publicado a borrador;
- verificar ausencia de `sgkb-docs`;
- probar WPML si está activo;
- probar Genix activo y desactivado;
- probar usuario sin capability.

## Plan operativo

| Código | Nombre | Prioridad |
|---|---|---:|
| P1 | Separar publicación Markdown/llms de integración Genix | Alta |
| P2 | Añadir estado público por documento | Alta |
| P3 | Añadir prompt por documento e idioma | Alta |
| P4 | Integrar prompt personalizado/genérico y límite | Alta |
| P5 | Añadir acciones AJAX de guardar y generar | Alta |
| P6 | Excluir `sgkb-docs` de todo el alcance | Alta |
| P7 | Confirmar solo posts publicados en todos los flujos | Alta |
| P8 | Ejecutar validación técnica y QA real | Alta |

## Siguiente paso

Realizar el precheck técnico final y diseñar la persistencia exacta antes de
editar código. El diseño debe decidir si se añaden columnas a
`wookb_documents` o se utiliza una alternativa compatible, sin borrar datos
existentes.
