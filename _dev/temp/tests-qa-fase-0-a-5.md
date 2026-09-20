# QA manual — Fases 0 a 5

Nada de lo implementado ha pasado prueba manual real todavía (solo `php -l` /
`git diff --check` y lectura de código por el subagente). Este documento es
la lista de pruebas a ejecutar en el sitio real, paso a paso.

Cómo usarlo: cada prueba tiene pasos, resultado esperado, y quién puede
ejecutarla (**tú en el navegador**, o **yo desde terminal/código**). Marca
cada una como hecha cuando la pruebes; si falla, anota el resultado real
debajo con la severidad (crítico/alto/medio/bajo).

---

## Fase 0 — Identidad

1.  **Nombre visible.** wp-admin → Plugins → confirma que aparece
   "AI Knowledge & Visibility" (no "WOO Knowledge Base Generator"). — **Tú - OK**.
2. **Textos traducidos siguen funcionando. ** Abre cualquier pestaña del
   plugin y confirma que los textos se ven igual que antes (el cambio de
   Text Domain no debería romper ninguna cadena, solo cambia bajo qué
   dominio se busca la traducción). — ** Tú - QUAL SON LOS TEXTOS TRADUC **.
3. **Rutas del menú siguen igual. -  ** Confirma que la URL del panel sigue
   siendo `admin.php?page=woo-kb-generator` (el slug de menú NO se tocó a
   propósito). — **Tú - SI, no se puede cambiar =???**.

---

## Fase 1 — Control manual de documentos 

4. **Migración de tabla.** Con el plugin activo, comprobar que la tabla
   `wp_wookb_documents` tiene las columnas nuevas. — **Yo, por terminal - DIME**:
   `wp db query "DESCRIBE wp_wookb_documents"` (o phpMyAdmin) y confirmar
   que aparecen `override_mode`, `override_text`, `char_limit`, `stale`.
5. **Pasar un documento a manual.** Registro → elige una fila sincronizada →
   editar texto → "Pasar a manual". Resultado esperado: la fila queda en
   modo Manual; el `.md` público (`wp-content/llm/{lang}/{slug}.md`) contiene
   exactamente el texto que pegaste, no el generado antes. — **Tú - si.. functiona para UX es fatal... tenemos q hablar de esto**.
6. **No se regenera solo.** Edita y guarda el post de origen de ese
   documento (cambia el título, por ejemplo). Resultado esperado: el `.md`
   NO cambia, la fila sigue en Manual, pero aparece marcada como
   "desactualizada" (`stale`). — **Tú - creo qeu si,.. rtarda un poco pero es normal a**.
7. **Aviso de desactualización.** Tras el paso 5, comprobar que aparece un
   aviso en la página del plugin (`admin_notices`) y un contador en la barra
   de admin con enlace al Registro filtrado. — **Tú -  si*.
8. **Volver a Auto.** Desde la fila marcada `stale`, pulsar "Volver a Auto".
   Resultado esperado: se regenera con IA de inmediato, el aviso desaparece.
   — **Sí** (consume una llamada real a OpenAI, confírmalo si tienes límite
   de coste).
9. **Límite de caracteres persistido.** Fija un `char_limit` bajo (ej. 300)
   en una fila en modo Auto, fuerza una regeneración. Resultado esperado: el
   cuerpo generado respeta ese límite, y si vuelves a regenerar más tarde
   sigue aplicándose sin tener que volver a indicarlo. — **Tú - giarda pero no aparce en acciones... alli sigo viendo 10000**.
10. **Bulk "Regenerar seleccionados" respeta el modo manual.** Selecciona
    varias filas (alguna Auto, alguna Manual) → "Regenerar seleccionados".
    Resultado esperado: las filas Manual no cambian, y el aviso final indica
    cuántas se saltaron. — **Tú - si**.
11. **Botón en el editor.** Abre un post de un CPT marcado en Ajustes →
    confirma que aparece el meta box "Base de conocimiento IA" → pulsa
    "Añadir a la base de conocimiento". Resultado esperado: el ID aparece en
    Alcance y una fila nueva en el Registro (encolada o generada). — **Tú -aparce boton pero no se ve nada dando al boton y no aparece en alcance, si esra creado deberia aparcer boton quitar de la ista,,,y que aparezca en exclusines **.
12. **Selector de CPTs en Ajustes.** Desmarca un CPT en el ajuste nuevo de
    Ajustes → confirma que el meta box deja de aparecer en el editor de ese
    CPT. — **Tú -si**.
13. **Salto de tema.** Recarga varias veces cualquier pestaña del plugin con
    el tema oscuro activado. Resultado esperado: nunca se ve un parpadeo de
    claro antes de oscuro. — **Tú -sim iunico hay bordes por todos los lados zona negra.. seria genoal 	quita** (prueba visual, recarga fuerte con
    Cmd+Shift+R varias veces).
    **hay que hacer algo con la los regutros quiza hay micho botones y pioco UX, qyuza todo contorl manual Ver y editar debe abrirse abajo del resato.. en misma fila ..peor en todo ancho campo de texto de markdown deberia tener con 2/s de ancho y unos minimo 15lineas de altura.. para ver bien,.. a laldo botones.. y resto de opcines... todos los bortomes un poco mnas grandes quedan pequelos...  Carga inicial queda raro..por que no es solo incial.. ahora he anadodo posr y todo m epone 
    Alcance actual: 38 elementos × 1 idiomas = 38 documentos posibles.

Documentos ya sincronizados: 13.

Límite diario actual: 100 generaciones/día.

Carga inicial en curso (procesando por lotes vía Action Scheduler) y boton cancelar carga incicial y no se entoendo pro uq eno se hacargado resto.. o reniciar... o anador.. es muy liosa esta parte **

---

## Fase 2 — Pestaña WooCommerce

14. **La pestaña aparece.** Con WooCommerce activo, confirma que existe la
    pestaña "WooCommerce" en el menú del plugin. — **Tú - OK **.
15. **Datos detectados correctos.** Compara lo que muestra la sección
    "Detectado automáticamente" contra los ajustes reales de WooCommerce
    (Ajustes de WooCommerce → Envío, Impuestos, Pagos, Cuentas y privacidad).
    Presta atención especial a **impuestos/IVA** (es la parte nueva, nunca
    probada): si tienes tipos de IVA configurados, deben aparecer con su
    porcentaje y clase fiscal correctos. — **Tú - todos estos datos deberian aparecer como los post en plan seleccionar o no.. seleccioandos para epues inclueir o no en promt y .md No en columna si no uno al alod del otro , copiar maquetacin y estilos de los posts**.
16. **Sin impuestos configurados.todos los dartos deberian poder serr editablesmu tener un campo extra para anadir un promt o un texto para usar luegii , si es la agina poder incluir o biscar otra..Esto deberian ser para todo los campos ** Si el sitio no tiene impuestos activos
    (Ajustes de WooCommerce → General → "Activar impuestos" desmarcado),
    confirma que la sección de IVA muestra el mensaje de "no configurado" en
    vez de un error o una sección vacía rara. — **Tú - ok**.
17. **Campos manuales se guardan.** Rellena "Plazo de entrega" y "Notas
    legales adicionales" → guarda → recarga la pestaña. Resultado esperado:
    los valores persisten. — **Tú -OK **.
18. **Vista previa refleja los datos.** Pulsa "Generar/actualizar ahora" →
    confirma que la vista previa del documento `store-info` incluye el plazo
    de entrega y las notas legales que acabas de rellenar, y la nueva
    sección de IVA. — **Tú**.
19. **Botón movido de Ajustes.** Confirma que en Ajustes ya no está el botón
    de generar documentos de tienda (debe quedar solo un aviso con enlace a
    la pestaña WooCommerce). — **Tú NO ENTIENDO DEBERI TENR BOTON  genrar los textos y despues poder visualizar..**.

---

## Fase 3 — API REST JSON

Estas se pueden probar sin salir de terminal. 
20. **Endpoint de un contenido incluido.**
    `curl -s "https://TU-DOMINIO/wp-json/ai-knowledge/v1/content/ID" | jq`
    (usa el ID de un producto/post que SÍ esté en Alcance). Resultado
    esperado: JSON con título, contenido, taxonomías, etc. — **Yo o tú en nabvegador OK**.
* 21. **Endpoint de un contenido excluido.** Repite con un ID que esté en
    Exclusiones (o que exista pero no sea del alcance). Resultado esperado:
    HTTP 404, sin dar pistas de que el contenido existe. — **Yo o tú* -OK*:
    añade `-i` a curl para ver el código de estado.
22. ** Endpoint de un ID inexistente.** Prueba con un ID absurdo (ej.
    `999999999`). Resultado esperado: también 404, con el MISMO formato de
    error que el caso 21 (no debe distinguirse "existe pero excluido" de "no
    existe"). — **Yo o tú**.
23. **Listado paginado.**
    `curl -si "https://TU-DOMINIO/wp-json/ai-knowledge/v1/product?per_page=5"`
    Resultado esperado: 5 items, cabeceras `X-WP-Total` y `X-WP-TotalPages`
    presentes y coherentes con el número real de productos en Alcance.
    — **Yo o tú**.
24. **post_type no público/inexistente.**
    `curl -si ".../wp-json/ai-knowledge/v1/no-existe-este-tipo"` → 404.
    — **Yo o tú**.
25. **Cabecera de caché.** Confirma con `curl -si` que la respuesta trae
    `Cache-Control: public, max-age=300`. — **Yo o tú**.

---

## Fase 4 — Markdown discovery (`<link rel="alternate">`)

26. **Post sincronizado.** Abre en el navegador un producto/post que ya
    tenga documento `synced` en el Registro → "Ver código fuente" → busca
    `rel="alternate" type="text/markdown"` en el `<head>`. Resultado
    esperado: presente, con URL válida que al abrirla devuelve el `.md`
    real. — **Tú OK---> por qeu no se abre y sdecarga...esto puede cambiar. tanto aquio como en admin ?**.
27. **Post sin sincronizar.** Repite en un post que exista pero no tenga
    fila `synced` en el Registro (o esté fuera de Alcance). Resultado
    esperado: el `<link>` NO aparece. — **T -okú**.
28. **Portada/archivo/categoría.** Confirma que el `<link>` no aparece en la
    home, en un archivo de categoría, ni en la página de tienda. — **Tú - estopor que ?? no deberian ??? explicame y si quero???**.

---

## Fase 5 — JSON-LD

29. **Con RankMath Schema activo.** Si el módulo Schema de RankMath está
    activo, abre un producto → "Ver código fuente" → confirma que NO hay un
    segundo `<script type="application/ld+json">` de este plugin duplicando
    el de RankMath (debería verse solo el de RankMath). — **Tú - dublicado - existe <script type="application/ld+json">{"@context":"https://schema.org","@type":"Product","name":"IVA &#8211; 21","description":"Lorem ipsum dolor sit amet, te has solet postea. Voluptua quaestio dissentias has ex, no eum aliquid tibique petentium, agam mucius liberavisse eos idt.","url":"https://plugins.local/producto/product-name/","sku":"211852","image":"https://plugins.local/wp-content/uploads/2022/01/Wierframe-Woocommerce_product.jpg","offers":{"@type":"Offer","url":"https://plugins.local/producto/product-name/","priceCurrency":"EUR","availability":"https://schema.org/InStock","price":"100"}}</script>
    **.
30. **Con RankMath Schema desactivado (prueba temporal).** Desactiva
    solo el módulo Schema de RankMath (Ajustes de RankMath → Módulos → Schema
    OFF) → recarga el producto → confirma que SÍ aparece el JSON-LD de este
    plugin (`Product`/`Offer` con precio numérico correcto, moneda, stock).
    Vuelve a activar el módulo de RankMath al terminar. — **Tú** (cambia un
    ajuste real de otro plugin, hazlo solo si te parece bien probarlo así).
31. **Contenido no-WooCommerce.** Repite en un post/página normal en
    Alcance sin RankMath Schema activo para ese tipo. Resultado esperado:
    `Article` con `headline`, fechas ISO 8601 válidas. — **Tú - duplicado**.
32. **JSON válido.** Copia el contenido del `<script type="application/ld+json">`
    y valida en https://validator.schema.org/ (pega el JSON, no la URL, si
    prefieres no compartir la URL del sitio). — **Tú - OK**.

---

## Seguridad (transversal, todas las fases)

33. Confirma que ninguna de las acciones nuevas de `admin_post` funciona sin
    sesión de admin (prueba en una ventana de incógnito sin login: debe
    redirigir a login o dar error de permisos, nunca ejecutar la acción).
    — **Tú- ok**.
34. Confirma que el endpoint REST no devuelve `custom_fields` que no estén
    explícitamente seleccionados en Alcance para ese post_type. — **Tú-noentido**.

---

## Cómo registrar resultados

Por cada número: `OK`, `FALLO (severidad): descripción y pasos exactos`, o
`NO PROBADO (motivo)`. Cuando tengas resultados, los paso a
`_dev/contexto-activo.md` (pendientes) y `_dev/roadmap.md` (si algo obliga a
rehacer una fase).
__