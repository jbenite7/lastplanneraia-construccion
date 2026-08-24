---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-24
areas: [design-system, bi, ci, a11y]
fuente: goals/pendientes-frente-tablas/goal.md
resumen: Cierre de cuatro pendientes que dejó el frente de tablas — fuga de tipografía, color de serie mal usado en BI, cuatro listas de SQL en CI y el gemelo callado del filtro de cabecera — más la anotación de lo que no se cerró.
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: pendientes-frente-tablas

## Fase del plan
Plan: `docs/superpowers/plans/2026-08-24-pendientes-frente-tablas.md`
Fase: Tasks 1 a 5 — el frente ejecutó el plan entero en una sesión
Sha verificado: `ee875efb` (ver `## Cierre`)

## Objetivo
Cerrar cuatro pendientes puntuales que quedó debiendo el frente de tablas del 2026-08-24: una fuga
de dos líneas fuera del sistema tipográfico, un color de estado usado como relleno de dato en dos
donas de BI, cuatro listas de SQL en CI que debían coincidir y no lo decían, y un defecto de
accesibilidad reportado en vivo (el «gemelo callado» del filtro de cabecera de Programa General).

## Condición de hecho
Las cuatro tareas de código quedan resueltas o investigadas con evidencia, cada una en su propio
commit; el cierre documental deja `TASKS.md` y `CHANGELOG.md` reflejando exactamente lo que pasó
—lo cerrado sale, lo no reproducido se reescribe con su hipótesis, lo nuevo que apareció entra—, y
el frente queda publicado en `main`.

## Leer primero
- `docs/superpowers/specs/2026-08-24-pendientes-frente-tablas-design.md`
- `docs/superpowers/plans/2026-08-24-pendientes-frente-tablas.md`
- `.superpowers/sdd/2026-08-24-pendientes-frente-tablas/progress.md` — ledger completo, incluye el
  incidente del contenedor compartido en Task 1 y las rulings de revisión.

## Archivos declarados
`public/css/**`, `public/js/modules/programa_general/hot.js`, `src/Services/Bi/ControlTowerService.php`,
`public/js/bi-spa.js`, `database/fixtures/design-system-ci.Dockerfile`,
`scripts/design-system-ci-preflight.mjs`, `tests/design-system/ci-preflight.test.mjs`,
`tests/design-system/visual-ci-contract.test.mjs`, `TASKS.md`, `CHANGELOG.md`,
`goals/pendientes-frente-tablas/**`

## Resultado

| Tarea | Commit | Qué pasó |
|---|---|---|
| T1 — tipografía | `3ce994fc` | `handsontable-module.css` deja `monospace` por `--ds-font-mono`; token nuevo `--ds-font-icon` reemplaza «Font Awesome 5 Free» a mano en los dos sitios que lo usaban. Gate estático 8/8. |
| T2 — color de BI | `880e9d4a` | Las dos donas de progreso dejan de pintar con `status-*` (tinta de estado) y pintan con color de dato: `critical` / `brand-construction` / `brand-primary`. PHPStan sin errores; 51 tests PHPUnit OK. |
| T3 — listas de CI | `a9366f3b` | De cuatro listas de SQL quedan tres con roles distintos y explícitos: guardarraíl, su derivada y un segundo testigo deliberadamente duplicado. Verificado ensuciando el Dockerfile: los dos tests que deben fallar, fallan. |
| T4 — gemelo callado | `ee875efb` | **No se reprodujo.** 24/24 botones marcados en las dos mitades, sostenido en 12 muestras tras seis operaciones distintas (render, updateSettings, loadData, resize, scroll, recarga). Solo se actualizó el comentario del archivo; sin arreglo de código. |
| T5 — cierre documental | este commit | `TASKS.md` y `CHANGELOG.md` actualizados; ver abajo. |

**Lo que no cerró y por qué:**
- El pendiente del gemelo callado no desapareció: se reescribió con la hipótesis que queda —la
  medición original del 2026-08-24 pudo tomarse contra un contenedor que montaba otro árbol,
  exactamente el fallo que `LPS_CODE_ROOT` existe para prevenir y que mordió dos veces esa misma
  jornada en la sesión que lo midió.
- T2 dejó un hallazgo nuevo: los tres tokens de dato (`critical`, `brand-construction`,
  `brand-primary`) ya pintan área en otras piezas del mismo tablero, pero el design system sigue
  sin un token de estado pensado para relleno — solo la mitad `-text`. Va a `TASKS.md`, dirigido a
  DS-F1.
- T2 no pudo verse a tamaño real: el único proyecto accesible del sandbox tiene 0 % de avance en
  ambas métricas y el arco de dona queda invisible. Riesgo bajo (evidencia sustituta: los mismos
  tres tokens ya pintan área en el mismo tablero) pero sin ver. Va a `TASKS.md`.
- DataTables (el pendiente de retirar el tercer motor de tablas) se deja intacto a propósito: es
  decisión de rumbo sin fecha que Felipe mantuvo explícitamente para este frente.

## Cierre

Verificación reutilizada de la evidencia ya medida por cada tarea de este mismo frente (sesión
2026-08-24), sin repetir suites que no cambiaron de entrada:

- T1: gate estático de design system → **8/8**.
- T2: `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` →
  **[OK] No errors (145/145)**; `run-php-tests.php --nivel=puro` → **24/24 scripts + PHPUnit OK
  (51 tests, 74 assertions)**.
- T3: Dockerfile ensuciado a propósito → `ci-preflight.test.mjs` y `visual-ci-contract.test.mjs`
  **fallan** como se esperaba (guardarraíl vivo).
- T4: 24/24 botones con `aria-hidden` en 12 muestras, sin reproducir el defecto reportado.
- T5 (este cierre): `git status` limpio tras staging selectivo de `TASKS.md`, `CHANGELOG.md` y
  `goals/pendientes-frente-tablas/` (sin `evidence/`, que está en `.gitignore`).

SHA verificado y publicado: **`ee875efb`** más el commit de este cierre documental — ver
`git log` para el SHA final publicado en `main`.

## Archivos de este goal
- [[docs/superpowers/specs/2026-08-24-pendientes-frente-tablas-design]]
- [[docs/superpowers/plans/2026-08-24-pendientes-frente-tablas]]
- [[TASKS]] · [[CHANGELOG]]
- [[memoria/goals/estado]]
