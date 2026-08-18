# Atribución de la generación 0.3.5 del presupuesto de runtime

**Fecha:** 2026-08-18 · **Baseline anterior:** 0.3.4 (aprobado 2026-08-12)
**Medición:** `docs/design-system/runtime-measurements/0.3.5-measurement.json`
**Entorno:** stack aislado de CI (`docker-compose.ci.yml`, puertos desplazados para no chocar con
otra sesión que tenía el suyo levantado), siete corridas, worktree limpio.

## Por qué el gate estaba en rojo

No por el código. El recibo de `runtime-budgets` en `closeout-evidence.json` se midió el
**2026-08-11** contra el baseline `0.3.3`, y el baseline `0.3.4` se aprobó el **2026-08-12**. El
recibo nunca se regeneró, así que el gate llevaba una semana bloqueado por una medición anterior a
la referencia que debía cumplir. Antes de tocar nada había que volver a medir, y eso es lo que hizo
esta ronda.

## Las dos métricas que superaban el techo de 0.3.4

### `cssGzipBytes` — 196.733 contra un máximo de 196.612

**121 bytes, 0,06 %.** Idéntico en las siete corridas: determinista, no ruido.

Atribución completa: entre `73df32dc` (donde se midió 0.3.4) y `083b5b65` el CSS del design system
creció en 105 líneas, 81 de ellas en `adapters/shell-sidebar.css`, aportadas por tres commits del
menú flotante bajo 1180px — `c48ab522`, `11f2c415` y `7b8c7a67`. Las 24 restantes están en
`adapters/select2.css` (`cc8146a2`, tinta heredada del modal TNP). Funcionalidad nueva y deliberada;
ninguna regresión.

Se evaluó recortar el CSS para volver bajo el techo y se descartó: la mayor parte de esas 81 líneas
son comentarios que documentan dos trampas ya medidas —por qué el disparador vive fuera de
`.context-bar` (contexto de apilamiento) y por qué el selector va duplicado en vez de usar
`!important` (especificidad de `:has()`)—. Cambiar documentación medida por un 0,06 % es mal
negocio.

### `handsontableInteractionMs` — 271–277 contra un techo de 180,1

Aquí el que estaba mal era el instrumento. La serie histórica de esta misma métrica:

| Generación | Valor |
|---|---|
| 0.3.3 | 280,7 |
| **0.3.4** | **134,6** |
| 0.3.6 (medición suelta) | 180,4 |
| 1.0.0 (medición suelta) | 234,2 |
| **hoy (0.3.5)** | **271–277** |

El 134,6 que 0.3.4 congeló es **el mínimo de toda la serie**, y lo medido hoy coincide con lo que
medía 0.3.3. Congelar un valor atípico como referencia es lo que produce esta falsa alarma.

**La hipótesis de «máquina lenta» quedó descartada con una medida, no con una suposición:**
`initializationMs` bajó de 1.070,6 a ~230 en el mismo equipo donde se midió lo anterior. Un arranque
cinco veces más rápido no convive con una interacción inflada por falta de CPU. La primera lectura
de esta ronda sí atribuyó el número a la carga de la máquina, y era falsa: al repetir con el equipo
descargado, las cuatro corridas siguieron entre 271 y 277.

## Lo que esta generación NO afirma

No afirma que 271 ms sea un buen número, solo que no es nuevo ni atribuible a un cambio reciente.
Si se quiere bajar, es trabajo de rendimiento con frente propio, no una corrección de baseline.

## Trampa registrada de paso

Las claves de `BASELINE_GENERATIONS` son la **generación del presupuesto**, no la versión de
producto —que ya iba por 1.1.0 cuando nació 0.3.4—. El primer intento de esta re-aprobación las
confundió y nombró el baseline `1.1.0`; el propio manifiesto de 0.3.4 ya advertía la diferencia.
Queda un comentario en `scripts/design-system-runtime-budget.mjs` junto a la entrada nueva.
