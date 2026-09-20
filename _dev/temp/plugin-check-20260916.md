# Plugin Check — resumen y triage (2026-09-16)

Fuente: `ai-knowledge-ai-knowledge-php-20260916-233623.md` (escaneo de Plugin Check,
generado el mismo día que se cerró el rename `woo-kb-generator` → `ai-knowledge`).
Este documento clasifica cada hallazgo por riesgo real, verificado leyendo el
código citado — no solo el texto del escáner.

## Peligroso de verdad

Ninguno. Los 3 puntos que parecían serios en el escaneo inicial se verificaron
leyendo el código y son falsos positivos (detalle abajo). No hay inyección
SQL, XSS ni path traversal real en el plugin.

### 1. `readfile()` en descarga de backup — `admin/class-admin.php:1430` (verificado, sin riesgo)
Sirve un archivo con `readfile()` en vez de `WP_Filesystem`. `$path` viene de
`Htaccess_Guard::path()`, que devuelve siempre `ABSPATH . '.htaccess'`
(`class-htaccess-guard.php:24`) — ruta fija, sin ningún input de usuario de
por medio. No hay path traversal posible. Capability + nonce ya verificados
arriba (`self::verify('wookb_download_htaccess_backup')`). Falso positivo,
no requiere acción.

### 2. Escritura con `file_put_contents()` — 3 apariciones (verificado, falso positivo)
`ai-knowledge.php:65` (`wp-content/llm/index.php`), `class-genix-hooks-guard.php:234`
(`wp-content/db-backup/...`), `class-chatbot-prompt-builder.php:218`
(`wp-content/llm/info.md`). Las 3 rutas están confirmadas leyendo el código:
escriben en `wp-content/llm/` y `wp-content/db-backup/`, **hermanas** de la
carpeta del plugin, no dentro de ella. El aviso `PluginDirectoryWrite` salta
solo porque detecta el uso de la constante `WP_CONTENT_DIR`, sin distinguir
subcarpetas. No hay riesgo de pérdida de datos en updates. Ruido del escáner,
no requiere acción.

### 3. `$total` sin escapar — `admin/views/tab-carga-inicial.php:25`
`$total` es `count($ids) * count($langs)`, siempre un entero — no hay dato de
usuario ahí. Es un false positive del escáner (no detecta que ya pasa por
`printf` con `%3$d` en `esc_html__`), pero lo marca porque el `%d` del format
string en sí no pasa por `esc_html()` como string suelto. **No es una
vulnerabilidad XSS real** (es un int), pero es fácil de silenciar si molesta.

## Ruido / prioridad baja (no bloquea nada, no es seguridad)

- **Nonce "missing" en `class-admin.php:344`**: falso positivo confirmado. La
  función llama a `self::verify('wookb_save_settings')` en la línea 319, que
  internamente hace `current_user_can()` + `check_admin_referer()`. El escáner
  no reconoce el wrapper propio.
- **`$wpdb->get_row/get_var/query` con `{$table}` interpolado** (4 sitios:
  `class-registry.php:57,273`, `class-crawler-log.php:130`,
  `class-chatbot-relevance-guard.php:422`): en los 4 casos `$table` viene de
  `self::table()` o `$wpdb->prefix . 'algo_fijo'` — **nunca de input de
  usuario**. El valor variable real (`$source_id`, `$excess`, `$session_id`)
  sí pasa por `$wpdb->prepare()`. No es inyección SQL real, es el patrón
  habitual (y aceptado) de interpolar el nombre de tabla, que `prepare()` no
  soporta como placeholder.
- **`PrefixAllGlobals.NonPrefixedVariableFound`** (~120 avisos, la mayoría del
  informe): variables locales dentro de archivos de vista (`$total`, `$ids`,
  `$key`, etc.). El check de WPCS está pensado para variables realmente
  globales; aquí son locales a cada `include`. Cosmético, cero riesgo.
- **`NonPrefixedHooknameFound`** (hooks `wpml_*`): son hooks que **dispara**
  el plugin **hacia** WPML (`apply_filters('wpml_object_id', ...)`), no hooks
  propios — por definición no pueden llevar el prefijo del plugin. Ruido.
- **`plugin_header_no_license`**: falta la línea `License:` en la cabecera.
  Fácil de añadir (`License: GPLv2 or later`), cero riesgo, solo trámite si
  algún día se sube a WordPress.org.
- **`outdated_tested_upto_header`** (6.7 vs 7.1) y **`stable_tag_mismatch`**
  (1.0.7 vs 1.0.8.6): desincronización de metadatos en `readme.txt`, no de
  código. Cosmético pero real — vale la pena corregirlo la próxima vez que se
  toque `readme.txt`, para que el `Stable tag` coincida con la versión real.
- **`readme_*_non_official_language`**: el checker de WordPress.org exige
  `readme.txt` en inglés. Solo aplica si este plugin fuera a publicarse en el
  repositorio oficial de WordPress.org — no es el caso aquí (plugin privado
  22MW). Ignorar salvo que cambie ese plan.
- **`.DS_Store` / `.gitignore` como "hidden files"**: ruido del escáner de
  WordPress.org, que no distingue archivos de macOS/Git de código real.
  Irrelevante para un plugin que no se sube a wordpress.org.
- **`wp_supports_ai()` / `wp_ai_client_prompt()` requieren WP 7.0** pero el
  plugin declara mínimo WP 5.8: correcto tal como está — el código ya detecta
  en runtime si esas funciones existen antes de usarlas (ver
  `class-ai-client.php`, uso de Conectores nativos solo si WP 7.0+). El
  escáner no ve ese `function_exists()`, es advertencia esperable, no un bug.

## Qué se puede hacer

**Nada de esto bloquea el uso normal del plugin** ni tiene riesgo de
seguridad real confirmado. Único trabajo que vale la pena, cosmético:

1. Añadir `License: GPLv2 or later` a la cabecera de `ai-knowledge.php`.
2. Sincronizar `Stable tag` (1.0.7 → 1.0.8.6) y `Tested up to` (6.7 → versión
   real probada) en `readme.txt`.
3. El resto (variables sin prefijo, hooks `wpml_*`, `$table` interpolado,
   escritura en `wp-content/llm`, `.DS_Store`) no requiere acción — son
   patrones ya usados en el resto del plugin y aceptados en
   `_dev/decisiones.md`, o ruido confirmado del escáner.

No se ha tocado código en esta revisión — es solo triage, a la espera de que
se decida qué de los puntos 1-3 se implementa.
