# Goal: Reapertura de móvil/tablet y tema claro

**Objetivo:** Devolver al producto los alcances que el repositorio se había prohibido —móvil,
tablet y un tema claro— empezando por los contratos, siguiendo por los gates y terminando por la
interfaz. Cada fase deja evidencia real o falla ruidosamente; ninguna se declara cerrada sin
comprobarlo con una mutación.

**Condición de hecho del goal completo:** las cuatro fases cerradas (F1 destrabar, F2 móvil real,
F3 tema claro, F4 matriz diagonal), con `npm run test:design-system:static` en 8/8 y sin pendientes
abiertos sin dueño.

**Spec del programa:** `docs/superpowers/specs/2026-08-07-reapertura-movil-y-tema-claro-design.md`
(decisiones D1–D8, 2026-08-07).

## Estado por fase

| Fase | Estado | Evidencia |
|---|---|---|
| **F1 — Destrabar** | **CERRADA** (2026-08-07) | DS-032. `390x844` vuelve a ser soportado y no requerido; el gate distingue `SUPPORTED_VIEWPORTS` de `REQUIRED_VIEWPORTS` y valida por primera vez los viewports declarados en `homologation.json`. Commits `01564ff9..0de9b753` + tanda final `c776b429`. |
| **F2a-1 — Precondiciones** | **CERRADA** (2026-08-07) | El harness de fixtures admite caso positivo, el gate valida los 15 manifiestos (miraba 4), ata cada golden a su tema, viewport y contenido, y ningún carril descarta ya el móvil. Commits `1aea682c..dbc3536a`. Spec: `2026-08-07-f2a-piloto-movil-programacion-design.md`. |
| **F2a-2a — Deudas de arranque** | **CERRADA** (2026-08-07) | El golden debe medir exactamente su viewport salvo recorte declarado y autorizado por lista blanca; los 17 manifiestos declaran `1.1.0` y pasan por el chequeo de versión; los seis minors de F2a-1 saldados. DS-033. Commits `0fadef2c..e00a1772`. |
| **F2a-2b — Piloto móvil** | Pendiente | Las cards y la evidencia móvil de Programación Intermedia y Semanal. Spec ya escrita: `2026-08-07-f2a-piloto-movil-programacion-design.md`. |
| **F2b — Resto de módulos** | Pendiente | Los 13 módulos restantes, planificados con el coste medido en el piloto. |
| **F3 — Tema claro** | Pendiente | Paleta clara nueva, conmutador con preferencia guardada. |
| **F4 — Matriz diagonal** | Pendiente | Los gates adoptan la matriz de D6 y los candados se reinstalan en su forma nueva. |

## Pendientes

Se resuelven dentro de este goal, no se difieren fuera de él.

| # | Pendiente | Origen | Estado |
|---|---|---|---|
| P-A | El chequeo de dimensiones del golden usaba `<=` solo sobre el ancho, así que un golden móvil pasaba como evidencia de escritorio. | Re-revisión final de F2a-1, medido | **Cerrado** (`0fadef2c`). Campo `capture` en el manifiesto; regla estricta de ancho y alto salvo recorte declarado. |
| P-B | 11 de los 17 manifiestos declaraban `designSystemVersion: 1.0.0` con `version.json` en `1.1.0`. | Revisión final de F2a-1 | **Cerrado** (`5fa6f007`, `719bff02`). Los 17 declaran `1.1.0` y entran por derivación al chequeo. |
| P-C | Seis minors diferidos de F2a-1. | Revisiones por tarea de F2a-1 | **Cerrado** (`c61b25e3`). Cinco arreglados; el sexto resultó cerrado por construcción. |
| P-D | `capture: "element"` no acotaba el alto, así que un PNG móvil etiquetado como recorte pasaba bajo un viewport de escritorio. | Revisión de F2a-2a Task 1, medido | **Cerrado** (`9d0e7331`, `e00a1772`). Lista blanca con clave compuesta `moduleId/scenarioId`. |
| P-E | Un conteo fijo cambiado por otro conteo fijo en `foundation.test.mjs`. | Revisión de F2a-2a Task 3 | **Cerrado** (`e00a1772`). |
| P-F | La lista blanca se indexaba por `scenario.id`, que no es único entre módulos: otro manifiesto podía reclamar el id y heredar la excepción. Reproducido en verde. | Revisión final de F2a-2a, medido | **Cerrado** (`e00a1772`). Clave compuesta, dos pruebas que se ponen rojas si se borra la regla, y DS-033. |

### Deuda anotada, con dueño

| Deuda | Estado |
|---|---|
| `ELEMENT_CAPTURE_ALLOWLIST` vive como constante en `scripts/design-system-contracts.mjs`, no como contrato de datos en `docs/design-system/`. Con clave compuesta ya no es evadible, pero añadirse sigue siendo editar un `.mjs`; el obstáculo real es la revisión humana descrita en DS-033. | Abierta, aceptada. Se reevalúa si la lista pasa de dos entradas. |

## Archivos de este goal

Estado y relación con los demás goals: [[estado|Estado de los goals]].
