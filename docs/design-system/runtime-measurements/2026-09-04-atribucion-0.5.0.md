# Atribución de la generación 0.5.0 del presupuesto de runtime

**Fecha:** 2026-09-04 · **Baseline anterior:** 0.4.0 (aprobado 2026-08-24)
**Medición:** `docs/design-system/runtime-measurements/0.5.0-measurement.json`
**Entorno:** GitHub Actions `ubuntu-latest`, runtime aislado de CI (`docker-compose.ci.yml`), job
`design-system-runtime (dark)`. Tres corridas por `workflow_dispatch` sobre el mismo commit
(`70ae2922`), nueve muestras, árbol limpio.
**Decisión:** aprobación de Felipe del 2026-09-04 — «aprueba el tope de CSS y arranca la baseline 0.5.0».

## Por qué el gate estaba en rojo

Desde la corrida `33886885126` (2026-09-03) `test:runtime-budget:check` fallaba en una sola métrica
contra 0.4.0, idéntica en los dos temas de la matriz:

| Métrica | Baseline | Techo | Medido |
|---|---|---|---|
| `cssGzipBytes` | 128.266 | 130.314 | 131.477 |

Las otras cinco métricas estaban dentro del techo. `TASKS.md` ya atribuía el exceso al CSS de la
fase cero de temas y forma (2026-08-28), posterior al `sourceRef` de 0.4.0 (`13e692aa`); esta ronda
lo confirma archivo por archivo.

## `cssGzipBytes` — +3.211 B, atribuidos al byte

El inventario de activos que ambas mediciones conservan permite restar archivo por archivo. La suma
cuadra exacta, sin residuo:

| Delta gzip | Archivo | Origen |
|---:|---|---|
| +1.008 | `css/design-system/components/readiness-popover.css` *(nuevo)* | popover de disponibilidad, spec temas claro/oscuro 2026-08-28 |
| +554 | `css/tokens.css` | tokens de estado claro, forma, tabla y densidad (`--ds-color-surface-well-*`) |
| +553 | `css/design-system/components/gravity-flag.css` *(nuevo)* | bandera de gravedad |
| +448 | `runtime/css/aia-design-system.css` | entrada del tema claro y `theme-overrides` |
| +432 | `css/design-system/components/readiness-squares.css` *(nuevo)* | cuadros de disponibilidad |
| +146 | `css/design-system/core.css` | |
| +93 | `css/programa-general.css` | |
| −12 | `css/handsontable-header-global.css` | |
| −10 | `css/design-system/components/table-filter-trigger.css` | |
| −1 | `css/handsontable-module.css` | |
| **+3.211** | **total** — 81 → 84 activos servidos | |

Ningún archivo aparece con **sha256 idéntico y peso distinto**. En 0.4.0 esa fila valía 1.132 B y
era la firma del cambio de entorno (zlib local frente a zlib del runner). Aquí no existe porque
baseline y medición se tomaron donde el gate verifica — que es lo que 0.4.0 vino a instaurar.

Corroborado desde el otro lado con `git diff --stat 13e692aa..main -- public/css`: 846 líneas
añadidas en 15 hojas, todas de la fase cero de temas y forma. No hay CSS duplicado ni retirable que
evitara la aprobación: **es funcionalidad, no regresión.**

## `jsGzipBytes` — +761 B, dentro de tolerancia

No violaba el techo (4.096 B) y no requería aprobación; se documenta porque el inventario lo permite:
`js/modules/programa_general/hot.js` +328, `js/modules/aia_ui/theme-bootstrap.js` +265,
`js/modules/lps_drawer.js` +168. Los tres son del mismo frente de temas.

## Determinismo

`jsGzipBytes` = **644.593 B**, idéntico en las tres corridas válidas, en los dos temas de cada una y
en las nueve muestras crudas. `cssGzipBytes` = **131.477 B** en dos corridas y **131.476** en la
tercera (`33935387697`, en sus dos temas): un byte de gzip, que se anota en vez de llamarlo
idéntico. La versionada lleva 131.477, así que el techo (133.525) cubre las dos cifras con holgura.
La corrida del `pull_request` (`33932724239`) dio los mismos bytes y se descartó como muestra por
otra razón (abajo).
`duplicateRequestCount` y `themeFlashCount` en 0 en todas. `adapterAssets`: los ocho de 0.4.0, sin
altas.

## Tiempos: tres corridas, mismo commit

Las métricas de tiempo no son deterministas; se acota su dispersión sobre `70ae2922`. Cada valor es
la mediana de tres muestras de su corrida:

| Corrida | `initializationMs` (mediana de 3) | `handsontableInteractionMs` | `cssGzipBytes` |
|---|---|---|---|
| 33933866319 | 647,3 | 174,6 | 131.477 |
| **33934598207** *(la versionada)* | **632,1** | **185,4** | **131.477** |
| 33935387697 | 630,6 | 179,2 | 131.476 |

La medición que se versiona es la **mediana de las tres por `initializationMs`**, no la más
favorable. Las dos generaciones medidas en Actions quedan en la misma banda: 0.4.0 dio 581–639 ms
en medianas; esta ronda, 630,6–647,3. Consistente con «la referencia se toma en GitHub
Actions» de 0.4.0.

## Una alarma que se desmontó midiendo

La corrida del `pull_request` (`33932724239`, tema oscuro) dio `handsontableInteractionMs` =
**295,9 ms** contra un techo de 202,1 — una segunda violación que no formaba parte de lo aprobado.
No se archivó, por dos razones medidas:

1. **Su `sourceRef` no existe.** `pull_request` hace checkout del commit de fusión sintético
   (`ad3f4391`), que no está en la historia de ninguna rama: `git cat-file -t` no lo resuelve. Una
   baseline con ese origen sería irreproducible, el defecto exacto que 0.3.3 documenta. Las
   generaciones medidas en Actions se toman por `workflow_dispatch` sobre la rama, cuyo checkout es
   el sha real — 0.4.0 (`13e692aa`, alcanzable desde `main`) ya se hizo así.
2. **Era ruido de un runner, no código.** Las tres corridas sobre el sha real dieron
   174,6, 179,2 y 185,4 en el tema oscuro, dentro del techo; el tema claro de la misma corrida
   sintética había dado 166,2 sobre el mismo árbol y el mismo minuto.

Se deja escrito porque la próxima vez que una métrica de tiempo salte sola en una corrida, la
primera pregunta es de qué commit y de cuántas corridas, no qué cambió en el código.

## Tolerancias

Se conservan las de 0.4.0, y no por inercia:

- `cssGzipBytes` (2.048) y `jsGzipBytes` (4.096): la métrica es determinista y el margen ya estaba
  calibrado.
- `initializationMs` (110): techo 742,1 ms contra un máximo observado de
  647,3 en medianas.
- `handsontableInteractionMs` (60): techo 245,4 ms contra un máximo observado de
  185,4 en medianas. El gate compara medianas de tres, no muestras sueltas; la muestra
  cruda más alta de la ronda fue 229 y aun así queda por debajo del techo.
- `addedAdapterAssets`, `duplicateRequestCount`, `themeFlashCount`: 0.

## Lo que esta generación NO afirma

- **No afirma que el CSS nuevo pese lo que debe.** Afirma que pesa lo que mide y que ese peso lo
  aprobó el dueño del producto. Si se quiere bajar, es trabajo de rendimiento con frente propio.
- **No afirma nada sobre la máquina de un usuario.** Todo se mide en un runner de 2 vCPU con la
  fixture `sanitized-pilot-v1`.
- **No mide el tema claro.** El presupuesto se declara sobre `theme: dark` (`SUPPORTED_THEMES`), y
  el job del tema claro mide con ese mismo tema forzado. Que el claro tenga presupuesto propio es
  decisión pendiente, no un olvido de esta ronda.

## Trampas registradas

- **`pull_request` mide un commit que no existe.** Para una baseline, `workflow_dispatch` sobre la
  rama. El paso «Preserve the runtime-budget measurement» sube la medición en cualquier evento; la
  procedencia la decide el evento, no el paso.
- **El grupo de concurrencia cancela en cadena.** `cancel-in-progress: true` por rama: tres
  `workflow_dispatch` seguidos dejaron una sola corrida viva y dos canceladas (`33933567488`,
  `33933569175`, y `33933565960` cayó después). Las muestras se toman en serie, una corrida a la
  vez; el orquestador de esta ronda esperó a que el grupo quedara libre antes de disparar la siguiente.
- **`test-output/` se pisa.** Hasta este frente la medición no sobrevivía a ninguna corrida: la
  pisaba el paso de teclado antes de que «Preserve failure evidence» la subiera. Por eso 0.4.0 pudo
  bajarse el 2026-08-24 (el pisado se midió el 28) y esta generación no habría podido sin el
  artefacto propio.
