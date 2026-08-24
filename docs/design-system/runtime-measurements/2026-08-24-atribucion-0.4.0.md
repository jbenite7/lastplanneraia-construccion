# Atribución de la generación 0.4.0 del presupuesto de runtime

**Fecha:** 2026-08-24 · **Baseline anterior:** 0.3.5 (aprobado 2026-08-18)
**Medición:** `docs/design-system/runtime-measurements/0.4.0-measurement.json`
**Entorno:** GitHub Actions `ubuntu-latest`, runtime aislado de CI (`docker-compose.ci.yml`).
Cuatro corridas, doce muestras, árbol limpio.

## Por qué el gate estaba en rojo

El gate no corrió entre el 2026-08-21 y el 2026-08-24: estaba tapado por un fallo anterior del
paso de la suite PHP (`general_flags` sin sembrar en la imagen de CI), destrabado en `9a1c8639`.
Al destaparse, `test:runtime-budget:check` falló en dos métricas contra el baseline 0.3.5:

| Métrica | Baseline | Techo | Medido |
|---|---|---|---|
| `jsGzipBytes` | 634.284 | 638.380 | 643.832 |
| `initializationMs` | 191,4 | 301,9 | 596,5 |

## `initializationMs` — el baseline y el gate no se medían en la misma máquina

Este es el hallazgo de la ronda, y cambia la lectura del rojo entero.

El manifiesto de recuperación de 0.3.5 declara `ciRunId: "run-local-01428901"`: **se midió en la
máquina local**, no en un runner. La única generación anterior medida en GitHub Actions es 0.3.4
(`run-31616614996-1`). Ordenada por entorno en vez de por fecha, la serie deja de parecer ruido:

| Generación | Entorno | `initializationMs` |
|---|---|---|
| 0.3.3 (retrospectiva) | local | 227,9 |
| 0.3.6 (medición suelta) | local | 268,2 |
| 1.0.0 (medición suelta) | local | 265,7 |
| **0.3.5 (baseline vigente)** | **local** | **191,4** |
| 0.3.4 | **GitHub Actions** | 1.070,6 |
| **hoy (0.4.0)** | **GitHub Actions** | **581 – 638,9** |

El agrupamiento es perfecto por máquina: local 191–268 ms, Actions 581–1.071 ms. Los 596,5 del
rojo no son una regresión — son **la mitad** del único precedente medido en el mismo entorno. El
defecto era estructural: un baseline tomado en la máquina rápida y verificado en la lenta produce
rojos que no corresponden a ningún cambio de código.

De paso corrige una afirmación del informe de 0.3.5, que descartó la hipótesis de máquina diciendo
que el 1.070,6 y el ~230 se habían medido «en el mismo equipo». No era el mismo equipo: 1.070,6 es
de Actions y ~230 de local. La conclusión de aquel informe sobre `handsontableInteractionMs` se
sostiene igual; el razonamiento que la apoyaba, no.

**La grilla no es la que cuesta.** El `themeProbe` de las doce muestras descompone el tiempo:
`DOMContentLoaded` cae en 480–540 ms y la grilla queda visible 55–95 ms después. El trabajo propio
de Handsontable —lo que tocaron las cuatro olas del frente `replanteo-coloreado-estados`— es esa
cola de ~70 ms. El resto es carga y parseo de la página en un runner de 2 vCPU.

Dos medidas independientes apuntan igual: `handsontableInteractionMs` **bajó** de 259 a 141,6–175
sobre esa misma grilla, y el total servido **bajó** 59 KB gzip.

## `jsGzipBytes` — +9.548 B, atribuidos al byte

El inventario de activos que ambas mediciones conservan permite restar archivo por archivo. La
suma cuadra exacta, sin residuo:

| Delta gzip | Archivo | Origen |
|---:|---|---|
| +4.990 | `js/modules/programa_general/hot.js` | frente `replanteo-coloreado-estados`: olas 1 y 4, cifras tabulares y cabecera navegable (`a70293ce`, `e5c348ec`, `56a53b49`, `463397ac`, `8418449a`) |
| +2.304 | `js/design-system/state-tooltip.js` *(nuevo)* | la primitiva de tooltip en top-layer (`6dfdee59`, `a70293ce`, `28118802`) |
| +1.132 | 42 archivos con **sha256 idéntico** | ruido de compresión entre entornos, no código |
| +727 | `js/tablet-viewport-scale.js` | `651cc9f2`, umbral medido contra la pantalla física |
| +395 | `js/modules/aia_ui/view-switch.js` *(nuevo en la página)* | el archivo ya existía desde `c6d99fc8`; lo nuevo es que `/programa-general` lo carga |
| **+9.548** | **total** | |

La fila de los 1.132 B merece su renglón: son 42 archivos cuyo contenido no cambió —`jquery.min.js`
pesa 30.875 B gzip en la medición local y 30.894 B en el runner, con el mismo sha256— comprimidos
por versiones distintas de zlib. Es exactamente el mismo defecto de entorno que infló
`initializationMs`, medido en otra unidad. Cerca de un 12 % del exceso de JS no era código.

Funcionalidad nueva y deliberada en el resto. Ninguna regresión.

## Lo que bajó, y por qué no se celebra

`cssGzipBytes` cayó de 196.733 a 128.266 B (**−68.467 B, −35 %**), con `styles.css` pasando de
132.101 a 87.445 B en crudo, `tokens.css` de 35.265 a 17.823 y `legacy-bridge.css` de 26.260 a
6.706. No es minificación: es CSS retirado. Sumado al JS, el neto servido baja 58.919 B gzip.

No se celebra porque una parte de esa caída es del mismo cambio de entorno que las otras dos
métricas, y esta ronda no separó cuánto. Lo que sí queda establecido es que la página no sirve más
bytes que antes.

## Determinismo

`jsGzipBytes` = **643.832 B** y `cssGzipBytes` = **128.266 B**, idénticos en las cuatro corridas y
las doce muestras. `duplicateRequestCount` y `themeFlashCount` en 0 en todas.

Las métricas de tiempo no son deterministas y no se pretende que lo sean; se acota su dispersión
sobre un mismo commit (`13e692aa`):

| Corrida | `initializationMs` (mediana de 3) | `handsontableInteractionMs` |
|---|---|---|
| 32756319305 | 638,9 | 146,7 |
| **32756835750** *(la versionada)* | **598,9** | **141,6** |
| 32756847947 | 581,0 | 159,0 |
| 32752663363 *(commit `9a1c8639`)* | 596,5 | 175,0 |

La medición que se versiona es la **mediana de las tres del mismo commit**, no la más favorable.

## Tolerancias

`cssGzipBytes` (2.048) y `jsGzipBytes` (4.096) se conservan: la métrica es determinista y el margen
ya estaba calibrado.

`initializationMs` conserva su valor absoluto de **110 ms**, ahora sobre una base medida donde el
gate verifica. Techo 708,9 ms contra un máximo observado de 638,9 en medianas.

`handsontableInteractionMs` **sube de 45 a 60 ms**. Con 45 el techo quedaba en 186,6 y el máximo ya
observado del mismo código es 175: once milisegundos de margen sobre una métrica cuyas muestras
individuales llegaron a 178,6. Fijar un techo dentro del ruido ya medido garantiza un rojo falso, que
es el defecto que esta ronda entera está corrigiendo.

## Lo que esta generación NO afirma

- **No afirma que no exista regresión de rendimiento**, solo que ninguna es atribuible a un cambio
  de código con la evidencia disponible. La prueba directa —medir el árbol de `01428901` en un
  runner de Actions— no se hizo; se descartó por costo frente a la evidencia convergente de tres
  medidas independientes (agrupamiento por entorno, descomposición del `themeProbe`, e interacción
  y bytes a la baja).
- **No afirma que ~600 ms sea un buen arranque.** Si se quiere bajar, es trabajo de rendimiento con
  frente propio, y el primer sitio donde mirar son los 81 activos de la página, no la grilla.
- **No afirma nada sobre el rendimiento en la máquina de un usuario.** Todo lo de arriba se mide en
  un runner de 2 vCPU con la fixture `sanitized-pilot-v1`.
- **No reconcilia** el reparto de la caída de CSS entre código retirado y cambio de entorno.

## Trampa registrada

El baseline y el gate deben medirse en el mismo entorno, y hasta hoy nada lo obligaba: el artefacto
registra `ciRunId`, así que la prueba estaba a la vista desde el 2026-08-18 y aun así el informe de
aquella ronda razonó como si fuera la misma máquina. Desde 0.4.0 la referencia se toma en GitHub
Actions. Una generación futura medida en local vuelve a abrir este agujero, y el síntoma será otra
vez un `initializationMs` inexplicablemente bueno.
