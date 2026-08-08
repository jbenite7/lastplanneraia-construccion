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
| **F2a-2 — Deudas + piloto** | En curso | Dos deudas de arranque y el piloto móvil de Programación Intermedia y Semanal. |
| **F2b — Resto de módulos** | Pendiente | Los 13 módulos restantes, planificados con el coste medido en el piloto. |
| **F3 — Tema claro** | Pendiente | Paleta clara nueva, conmutador con preferencia guardada. |
| **F4 — Matriz diagonal** | Pendiente | Los gates adoptan la matriz de D6 y los candados se reinstalan en su forma nueva. |

## Pendientes abiertos

Se resuelven dentro de este goal, no se difieren fuera de él.

| # | Pendiente | Origen | Estado |
|---|---|---|---|
| P-A | El chequeo de dimensiones del golden usa `<=` solo sobre el ancho, así que un golden móvil pasa como evidencia de un escenario de escritorio. Cerrarlo exige que el manifiesto declare si la captura es recorte a elemento. | Re-revisión final de F2a-1, medido | Abierto |
| P-B | 11 de los 15 manifiestos declaran `designSystemVersion: 1.0.0` con `version.json` en `1.1.0`. Residuo del bump: se subieron solo los 6 que el gate miraba. | Revisión final de F2a-1 | Abierto |
| P-C | Seis minors diferidos de F2a-1 (nullish en `contracts.mjs`, comentario invertido del candado, `existsSync` ausente, `sharedHeadConsumers` con vistas borradas, doble candidata `approved` en `shell-navigation`, cobertura atada al array del inventario). | Revisiones por tarea de F2a-1 | Abierto |

## Archivos de este goal

Estado y relación con los demás goals: [[estado|Estado de los goals]].
