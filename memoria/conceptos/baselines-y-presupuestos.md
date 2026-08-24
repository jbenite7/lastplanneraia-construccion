---
capa: wiki
tipo: concepto
estado: vigente
verificado: 2026-08-18
fecha: 2026-08-04
areas: [design-system, qa]
tags: [trampa]
fuente: scripts/design-system-audit.mjs, tests/design-system/runtime-budget.test.mjs, scripts/design-system-phpstan-baseline.mjs
resumen: "Una baseline congela el desorden existente para que solo lo nuevo bloquee; un presupuesto fija un techo absoluto; cada uno tiene su gate y su archivo"
---
# Baselines y presupuestos: congelar lo viejo, acotar lo nuevo

El design system usa dos herramientas parecidas que conviene no confundir:

- **Una baseline congela el desorden que ya existía.** Registra cuántas violaciones hay hoy para
  que el gate solo falle si aparecen **más**. No absuelve lo viejo: lo deja contado y vigilado.
- **Un presupuesto fija un techo absoluto.** No importa la historia: si la medición supera el
  techo, falla.

Quién es quién, con su consumidor verificado:

| Archivo | Tipo | Qué bloquea |
|---|---|---|
| `audit-baseline.json` | baseline | Más hallazgos de estilo (hex, inline, fuera de capa) que los contados por regla — `scripts/design-system-audit.mjs:11,338-352` |
| `phpstan-baseline.json` | baseline | Cualquier fingerprint de PHPStan nuevo; hoy tolera **cero** (`"fingerprints": []`, verificado el 2026-08-10) — `scripts/design-system-phpstan-baseline.mjs:79-91`. Las cinco legacy que tenía (de `ListadoActividadesApiController` y `SemiAutoService`) se retiraron en `9011c99c` al borrarse esos archivos con el PDC v1 |
| `a11y-baseline.json` | baseline | Hoy exige `fingerprints` **vacío**: tolerancia cero, todo pasa por excepción — `tests/design-system/accessibility.test.mjs:225` (verificado el 2026-08-07: el archivo sigue con `"fingerprints": []`) |
| `lab-performance-budget.json` | presupuesto | Cada métrica del laboratorio (FCP, CLS, long tasks, peso CSS) contra su techo — `tests/browser/design-system-lab.performance.mjs:11,168` |
| `runtime-baseline-0.3.5.json` | baseline con tolerancias | El peso runtime de Programa General (CSS/JS gzip, adapters) contra lo aprobado más su margen. **Fue el vigente entre el 2026-08-18 y el 2026-08-24; hoy manda la `0.4.0`** (`package.json:26`), nacida porque la `0.3.5` se midió en local mientras el gate la verifica en GitHub Actions, e `initializationMs` agrupa por máquina antes que por código (local 191-268 ms, Actions 596-1071): parecía regresión y no lo era. Las generaciones anteriores (`0.3.3`, `0.3.4`, `0.3.5`) siguen en disco como histórico y **no se editan en sitio** — la `0.3.3` está anclada por hash, y el intento de actualizarla a mano es D-GAC-6, que ya falló el 2026-08-12; la `0.3.3` es la que ejercitan los fixtures de `tests/design-system/runtime-budget.test.mjs:67-92`. **Ojo con el nombre:** estas cifras son la generación del presupuesto, no la versión de producto, que ya iba por 1.1.0 cuando nació la `0.3.4` |

**Las baselines llevan candado.** `audit-baseline.json` está protegida por hash con aprobación en
`baseline-approvals/`, y los `runtime-baseline-0.3.x.json` registran `approval`, `sourceTreeHash` y
manifiestos `sha256` de recuperación: regenerarlas para forzar un verde no es un atajo disponible,
es una violación de contrato (AGENTS.md lo prohíbe explícitamente).

**Hallazgo del 2026-08-04, deuda conocida:** `lab-performance-baseline.json` existe en disco
(10 KB, del 19 de julio) pero **ningún script ni test lo lee** — el único consumidor de performance
del laboratorio es `lab-performance-budget.json`. Solo lo nombra `docs/design-system/README.md:62`.
Mismo patrón que [[comentario-de-token-afirma-uso-inexistente]]: documentado no implica cableado.

## Dónde se rompe esto en la práctica

- [[gate-estatico-no-ve-tokens-rotos]] — las baselines de lectura de archivos no ven valores
  resueltos; el rojo real solo aparece en navegador.
- [[visual-baselines-estado-real]] — las baselines visuales del laboratorio están rojas de fondo:
  mide el delta antes de culparte.
- [[gate-visual-tolerancia-enganosa]] — la tolerancia de píxeles absorbe más de lo que parece.
- [[branch-preexisting-red-gates]] — rojos preexistentes que no son tuyos.

Mapa del área: [[design-system]] · vecino: [[qa-y-gates]].


## Pase de veracidad del 2026-08-12: `runtime-baseline-0.3.3.json` se congeló con el CI

Medido en las corridas `31563364701` y `31565443070`, las **primeras** que ejecutaron este
presupuesto en CI:

- **Su último commit es `1cfc6e2c`, del 2026-07-17 — el mismo día de la última corrida verde del
  workflow.** El gate perdió la capacidad de correr y su referencia dejó de actualizarse a la vez.
- **Dos violaciones**, medidas sobre árbol limpio: `cssGzipBytes` **194.553** contra un máximo de
  138.981, y `adapterAssets`, cuya lista **aún nombra `semi-auto-review.css`**, borrado con el PDC v1
  el 2026-08-04, y **no** incluye `shell-sidebar.css` ni `datatables.css`, que sí existen.
- **`initializationMs` NO está violado.** Una primera medición dio 1.644 ms y era del **árbol sucio**;
  repetida en limpio, no aparece. Se corrigió en `D-GAC-5` — la cifra viajó a un informe antes de
  reverificarse.
- **`cssGzipBytes` varía un byte entre corridas** (194.554 → 194.553): eso es lo que la hace creíble
  como medición, frente a un número que cambia según quién pregunta.

**Lo que este pase NO establece:** cuánto de esos 57 KB es crecimiento real y cuánto es baseline
obsoleto. **No está medido**, y aprobar el baseline sin medirlo convertiría una posible regresión en
la nueva normalidad. **Resuelto el 2026-08-12 por `D-GAC-5(b)`**, aprobado por el usuario: en vez de
aprobar a ciegas el 0.3.3, se midió en CI y se versionó un baseline nuevo, `0.3.4` (`3bc3a662`), que
es el que corrió hasta el 2026-08-18. La forma de cerrarlo importa tanto como el cierre: `D-GAC-6`
había intentado antes **actualizar el 0.3.3 en sitio** y chocó con el contrato que lo fija por hash,
así que se revirtió y la re-aprobación pasó a ser un archivo con versión propia.

**Segunda re-aprobación, 2026-08-18: la generación `0.3.5`.** El gate llevaba una semana en rojo y no
por el código: su recibo se midió el 11 de agosto contra el `0.3.3`, y el `0.3.4` se aprobó el 12 sin
regenerarlo nunca. Al remedir, las dos violaciones tenían causa y ninguna era regresión —`cssGzipBytes`
+121 B (0,06 %) por las 81 líneas que el menú flotante bajo 1180px añadió a `adapters/shell-sidebar.css`,
y `handsontableInteractionMs` alto solo porque el `0.3.4` había congelado **el mínimo de toda la serie**
de esa métrica—. Registrar la generación nueva chocó con **tres** ataduras al literal `0.3.4`, todas
deuda del tiempo en que solo existía una generación: la lista blanca `BASELINE_GENERATIONS`
(`scripts/design-system-runtime-budget.mjs`), las rutas del validador de procedencia
(`scripts/design-system-runtime-budget-provenance.mjs`) y cinco aserciones de
`tests/design-system/visual-ci-contract.test.mjs`. Las tres quedaron parametrizadas por generación, así
que la próxima re-aprobación ya no las encuentra. Ver [[baseline-de-una-muestra-congela-el-atipico]],
que es la razón por la que las métricas de tiempo de este baseline solo puede fijarlas CI.
