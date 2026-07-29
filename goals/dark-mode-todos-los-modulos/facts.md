# Facts aceptados — dark-mode-todos-los-modulos

Decisiones del usuario, recogidas en el grilleo de Plannotator del 2026-07-25.
Fuente cruda: `interview.json` (bundle) e `interview-result.json` (respuestas).
Estas decisiones son vinculantes para los siete specs; cambiarlas exige nueva ronda.

## Transversales

| # | Decisión | Respuesta |
|---|---|---|
| 1 | Default de `:root` | **Invertir**: `:root` = dark; linen como override explícito |
| 2 | Destino del tema `linen` | **Retirar del producto**. Un solo tema: dark |
| 17 | Orden de ejecución | F0 → (F1 ∥ F4) → (F2 ∥ F3) → F6; F5 al desbloquearse |
| 16 | Gate de deuda global | **Monotónico** sobre el total del audit, con override documentado en `exceptions.json` |

## F0 · Fundación de tema

| # | Decisión | Respuesta |
|---|---|---|
| 3 | Tema muerto (`NavbarComponent` + `dark-mode.css` + `navbar.css`) | **Borrar** en F0, commit propio y reversible, tras verificar por grep que nada los referencia |
| 4 | `admin/` en `scanRoots` del audit | Entrar **congelado al baseline** medido en F0; el número sólo puede bajar |
| 8 | Rojo vivo de `programacion-semanal` | Cerrarlo **primero**, como tarea inicial de F0 |

## F1 · `styles.css`

| # | Decisión | Respuesta |
|---|---|---|
| 5 | Estrategia | **Desmantelar por secciones** hasta borrar el archivo |
| 6 | Tolerancia a regresión | **Tolerar regresión en todo**, con bitácora y cierre al final de F1 |

**Excepción acordada en chat (2026-07-25):** `/programa-general` mantiene evidencia visual
obligatoria por tramo pese a la tolerancia general. Es el piloto del DS, hoy con cero
hallazgos, y `DESIGN.md` lo declara protegido. El usuario aceptó la excepción.

## F2 · Nueve superficies del agregador

| # | Decisión | Respuesta |
|---|---|---|
| 7 | Granularidad de manifiestos | **Uno por superficie** (9 nuevos), siguiendo el patrón de los 7 existentes |

## F3 · BI

| # | Decisión | Respuesta |
|---|---|---|
| 9 | Tailwind CDN | **Eliminar** y reescribir las utilidades usadas a primitivas `aia-*` |
| 10 | Colores de series de Chart.js | **`getComputedStyle` en runtime** sobre `--ds-active-data-*`, con fallback al token |

## F4 · Panel admin

| # | Decisión | Respuesta |
|---|---|---|
| 11 | AdminLTE | **«Mejor no lo toquemos en este goal»** (respuesta libre del usuario) |
| 12 | Tokens de admin | **Unificar** en `public/css/tokens.css`; borrar `admin/public/css/tokens.css` |
| 13 | CDN externas de admin | **Vendorizar** todo a `/public/vendor`, reutilizando lo ya servido |

**Lectura acordada de la decisión 11, confirmada por el usuario en chat (2026-07-25):**
AdminLTE permanece como framework de `admin/`. **No** se reescriben las 14 vistas sobre el
shell canónico ni se migran a primitivas `aia-*`. Sí se sirve local en vez de por CDN, sí se
unifica a los tokens canónicos, y sí recibe un adaptador dark.

Consecuencia asumida y explícita: al cerrar el goal, `admin/` quedará **en dark y tokenizado
pero no migrado al DS**. Es la única desviación respecto al criterio «migración completa al DS»
elegido para el resto del alcance.

## F5 · plan-compras

| # | Decisión | Respuesta |
|---|---|---|
| 14 | Autoridad sobre el repo externo | **Tenemos acceso y autoridad** sobre `plan-de-compras` |

## F6 · Vendors

| # | Decisión | Respuesta |
|---|---|---|
| 15 | Vendors sin adaptador | **Tokenizar** `change-monitor` y `handsontable-module`; **consolidar** Tom Select y Select2 en un solo select enriquecido |

**Dirección de la consolidación, decidida en chat el 2026-07-25 (posterior al grilleo):**
**consolidar en Tom Select y eliminar Select2.**

Invierte lo que la primera redacción de F6 proponía. Justificación: Select2 depende de jQuery
y Tom Select no, así que retirarlo elimina una de las razones por las que jQuery sigue
cargándose. Coste asumido: Select2 está en 9 vistas de producto frente a 4 de Tom Select, y es
el único de los dos que hoy tiene adaptador del design system, entrypoint de adjunto y registro
en `VENDOR_ATTACHMENTS`.

Efecto secundario favorable: obliga a registrar `tom-select` en `VENDOR_ATTACHMENTS`, lo que
cierra un fallo latente — `programacion-intermedia.json` y `laboratory.json` ya declaran ese
vendor, y hoy `renderForModule()` lo rechazaría degradando en silencio al agregador.

La retirada de Select2 tiene **condición de parada** explícita en F6 si la integración con
Handsontable de `programacion-semanal` no puede resolverse con `HandsontableTomSelectEditor.js`.
