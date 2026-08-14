<!-- cas:cita-textual — informe de cierre: cita comandos y salidas tal como se midieron -->
# F2a-2b-1 — Red de pruebas sobre las reglas de habilitación: informe de cierre

- Fecha: 2026-08-13
- Plan: [`2026-08-08-f2a-2b-1-red-de-pruebas-habilitacion.md`](../plans/2026-08-08-f2a-2b-1-red-de-pruebas-habilitacion.md)
- Spec: [`2026-08-07-f2a-piloto-movil-programacion-design.md`](../specs/2026-08-07-f2a-piloto-movil-programacion-design.md), decisiones E7 y E8
- Commits: `0e909227` (arnés), `dc17ee69` (Semanal), `0f3882ce` (Intermedia), más el de manifiestos

## Qué se construyó

Tres archivos nuevos, ninguna regla modificada:

| Archivo | Qué hace |
|---|---|
| `tests/browser/support/enablement-probe.mjs` | El arnés: inyecta rol/fase/semana en los inputs que las reglas ya leen, fuerza la re-decisión por la vía de cada módulo, y lee la decisión de cada celda desde `getCellMeta` (el `cells()` real, no el CSS). |
| `tests/browser/programacion-semanal-enablement.mjs` | 8 pruebas: matriz de 24 combinaciones, 6 anclas literales, S11, S13 y los dos hallazgos. |
| `tests/browser/programacion-intermedia-enablement.mjs` | 6 pruebas: I1, I2 (24 combinaciones), I3 (como hecho inalcanzable), I4, I5, I7. |

Dos decisiones del arnés que conviene no perder:

- **La re-decisión usa la vía de cada módulo, no `location.reload()`**, que habría perdido el contexto inyectado: `hot.render()` en Semanal, el hook `afterFilter` en Intermedia —que es donde el propio código invalida sus caches de fila y recalcula `_canEditGlobal`— y `PSHotModule.reload()` para repintar las cards móviles.
- **El lector se ata al editor real** (`expectDecisionMatchesEditor`): una celda que el lector declara editable debe aceptar el editor, y una readOnly rechazarlo. Sin eso, la red caracterizaría el CSS en vez de la regla.

## Tabla de cobertura de las 22 reglas

### Programación Semanal

| # | Regla | Estado | Detalle |
|---|---|---|---|
| S1 | Whitelist `editableProps` | **Cubierta** | Matriz + ancla. Ver hallazgo 2: declara nueve props, la grilla monta ocho. |
| S2 | Semana histórica solo A/D | **Cubierta** | Las 24 combinaciones, más dos anclas literales. |
| S3 | `Ejecutado_Real` solo en calificación | **Cubierta** | Matriz + ancla. Ver hallazgo 1. |
| S4 | `Compromiso`/`Sub_Contratista`/`Responsable_AIA` bloqueados si confirmada | **Cubierta** | Matriz + ancla + S11. |
| S5 | Columna de acciones siempre readOnly | **Parcial** | La decisión se toma por `columnMeta.renderer === 'psActionsRenderer'`, no por `prop`, así que no entra por `propToCol`. La cubre indirectamente el control `Actividad` (readOnly fija con cualquier rol y fase); una prueba propia exigiría leer la columna por índice de renderer. |
| S6 | ~15 columnas readOnly fijas | **Cubierta** | Una de ellas (`Actividad`) se recorre en las 24 combinaciones como control. Las otras catorce comparten exactamente la misma cláusula (`!editableProps[prop]`), así que no se enumeran una a una. |
| S11 | El dropdown solo auto-abre si no es readOnly | **Cubierta** | Por el editor real, en los dos sentidos. |
| S12 | `canManageToolbarActions` = S2 más veto al rol `C` | **No cubierta** | Gobierna botones de barra, no celdas: no se lee de `cells()` ni del editor. Cubrirla exige otro tipo de prueba (estado de la barra), fuera del alcance de esta red. |
| S13 | La card móvil edita solo si la grilla edita | **Cubierta** | Tres combinaciones, comparando card y grilla en la misma corrida. **Es la prueba que sostiene la extracción que viene**: si card y grilla se desincronizan, se pone roja. |

### Programación Intermedia

| # | Regla | Estado | Detalle |
|---|---|---|---|
| I1 | `editableProps` dinámico desde `/api/general/restriction-config` | **Parcial** | Se comprueba que las restricciones que la config declara salen editables. **El complemento no es medible**: la grilla monta exactamente las columnas que la config declara, así que no existe una restricción no declarada con celda que mirar. Es un límite de la red, no una regla sin cubrir. |
| I2 | `isUserAllowedToEdit` (confirmada bloquea todo; histórica solo A/D) | **Cubierta** | Las 24 combinaciones. |
| I3 | Filas cabecera no editables | **No ejercitable, y por diseño** | Ver hallazgo 3: el listado del servidor filtra `Titulo = 0`, así que la grilla nunca recibe una cabecera. Se fija ese hecho con una prueba que se pondrá roja si deja de ser cierto. |
| I4 | Candado por Responsable AIA | **Cubierta** | Fila con y sin responsable, incluyendo la clase `pi-cell-locked-resp`. |
| I5 | `__shared_selected` ignora rol y fase | **Cubierta** | Con rol `V` y fase confirmada, justo donde todo lo demás está bloqueado. |
| I6 | 6 columnas readOnly fijas | **Cubierta por construcción** | Misma cláusula que I1 (`!editableProps[prop]`); las columnas readOnly no aparecen en `editableProps`. |
| I7 | La apertura del dropdown re-verifica | **Cubierta** | Por el editor real, en los dos sentidos, sobre la misma fila y columna. |

### Fuera de alcance declarado (no omitidas en silencio)

`S7`, `S8`, `S9`, `S10`, `I8` e `I9` no son reglas de habilitación sino **guards de valor** que corren en `beforeChange`/`afterChange` y tienen efectos: revertir la celda, **borrar la actividad** (Compromiso entre 0 y 0.001 en Semanal) o encolar un modal CNC. Caracterizarlos exige provocar cambios reales de datos, que es otra clase de prueba y otro riesgo. El plan los dejó fuera y así siguen.

## Comportamientos a revisar

No se corrigieron —el plan lo prohíbe expresamente—. Quedan escritos con su combinación exacta para decidir después.

| # | Qué | Dónde | Por qué importa |
|---|---|---|---|
| 1 | **`Ejecutado_Real` ignora la restricción de semana histórica.** Su cláusula tiene un `return` propio que se evalúa **antes** de `isUserAllowedToEdit()`, así que en fase de calificación un rol `R` edita el avance de una semana histórica que no puede tocar en ninguna otra columna. | `public/js/modules/programacion_semanal/hot.js:416-418` | Puede ser deliberado (calificar lo ya ejecutado) o un olvido de orden. Caracterizado en una prueba con nombre explícito. |
| 2 | **`editableProps` declara nueve props y la grilla monta ocho.** `Descripcion` no está en el array `columns`, así que esa entrada no gobierna ninguna celda. | `hot.js:37-47` frente a `:2641-2667` | Al extraer las reglas, una lista de nueve haría pensar que hay nueve columnas que atender. Son ocho. |
| 3 | **La rama `meta.isHeader` de Intermedia es código inalcanzable desde esta vista**, porque el listado filtra `Titulo = 0` en el servidor. | `ProgramacionIntermediaController.php:182` frente a `programacion_intermedia/hot.js:956-961` | Se descubrió al intentar cubrir I3. Es la diferencia entre «no hay datos sembrados» y «no puede haberlos», y solo la segunda justifica no cubrir la regla. |

**El censo del plan también se corrigió en dos puntos:** los números de línea de S1–S13 estaban corridos respecto al archivo actual, y S5 no se lee por `prop` sino por `renderer`, así que no encaja en el mismo lector que las demás.

## Verificación

Ejecutado en esta sesión, con el stack Docker levantado (`app` y `db` arriba):

```
npx playwright test tests/browser/programacion-semanal-enablement.mjs --workers=1
  → 8 passed (18.5s / 17.9s / 17.1s en tres corridas seguidas)

npx playwright test tests/browser/programacion-intermedia-enablement.mjs --workers=1
  → 6 passed (12.4s / 11.8s / 11.7s en tres corridas seguidas)

npm run test:design-system:static
  → 8/8: entrypoint-partition, unlayered-delivery, bi-utilities, table-contract,
    node-tests, contracts, consumer-contract, audit

git diff public/js/modules/programacion_semanal/hot.js
git diff public/js/modules/programacion_intermedia/hot.js
  → vacío en ambos: ninguna regla de habilitación fue modificada
```

**Ninguna prueba quedó como `skip`.** El único caso que iba a serlo (I3) se convirtió en una prueba del hecho que lo hace inalcanzable.

## Límites de esta red, dichos sin adornos

- **Cubre la decisión del cliente**, no la autorización del servidor. Esa otra mitad la tienen `tests/test_semanal_rbac_solo_lectura.php` y `tests/test_weekly_governance.php`. Que una regla del cliente coincida con la del servidor no lo afirma esta red.
- **La matriz de roles se recorre inyectando `#permiso_canonico`**, no abriendo seis sesiones, porque `test.C` y `test.D` no están habilitadas y `DCV` no tiene cuenta. Caracteriza lo que el cliente decide con ese rol en el DOM, que es exactamente lo que la extracción va a mover de sitio.
- **El proyecto se elige por disponibilidad** (`Preconstrucción Da Porto` en local; JMC en el fixture de CI): en la base local, «Da Porto» tiene tres filas que no llegan a la grilla. Si un día ningún candidato rinde filas, las pruebas fallan con un mensaje que lo dice, en vez de pasar en vacío.

## Condición de hecho

| # | Criterio | Estado |
|---|---|---|
| 1 | Las dos suites pasan tres veces seguidas sin intermitencias | **Cumplido** |
| 2 | Cada una de las 22 reglas clasificada, con motivo escrito para parciales y fuera de alcance | **Cumplido** |
| 3 | La prueba de S13 ata la card móvil a la misma regla que la grilla | **Cumplido** |
| 4 | `npm run test:design-system:static` en verde con los manifiestos declarando las pruebas | **Cumplido** |
| 5 | Ninguna regla modificada: `git diff` sobre los dos `hot.js` vacío | **Cumplido** |

## Qué sigue

Con la red puesta, lo que el plan dejó fuera a propósito: extraer las 22 reglas (E7), unificar el umbral en 1180 (E3), dejar de montar Handsontable bajo el umbral (E4), que las cards de Intermedia editen, y la evidencia móvil `390x844` de los dos módulos.
