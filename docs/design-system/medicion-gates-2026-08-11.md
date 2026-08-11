# Medición de los quince gates de cierre — 2026-08-11

**Medido sobre `b313de3f`**, en el worktree `elegant-jones-d4126a`, con
`COMPOSE_FILE=docker-compose.wt.yml` (sin eso, `docker compose exec app` resuelve al contenedor del
árbol principal y se mide el árbol vecino).

**Quién y por qué:** lo levantó la sesión de ejecución del **Frente 1** al cerrarlo. **No es el
Frente 1b** —ese frente lo abre la coordinadora y aún no está asignado—: es la medición de partida
que ese frente necesita, hecha mientras estaba a mano y sin tocar nada. Todo lo de aquí es lectura y
ejecución; **no se modificó ningún gate, ningún recibo y ningún inventario.**

## Lo que dice `closeout-evidence.json` y lo que ocurre al ejecutarlo

Los **quince** gates declaran `"status": "passed"`, con `verifiedAt` de **2026-07-15**. Ejecutados
contra el HEAD de hoy:

| Gate | Comando declarado | Resultado real |
|---|---|---|
| `static` | `npm run test:design-system:static` | **RC=0**, 8/8 |
| `runtime` | `npm run test:design-system:runtime` | script existe; exige navegador y contenedor, no medido aquí |
| `runtime-budgets` | `npm run test:runtime-budget:check` | **RC=1** — `ENOENT … test-output/`: no es ejecutable por sí solo, necesita los artefactos de una corrida previa |
| `phpstan-scoped` | `docker compose exec app vendor/bin/phpstan analyse src` | ver `phpstan-global` |
| `phpstan-global` | `npm run test:design-system:phpstan` | **RC=1** — «New PHPStan findings: **8**» |
| `global-table-safety` | `docker compose exec app php tests/test_global_table_safety.php` | **RC=0** |
| `pg-roles` | `npx playwright test tests/browser/full-app-flow.spec.mjs` | los tres declaran **el mismo** comando |
| `pg-persistence` | idem | idem |
| `data-restoration` | idem | idem |
| `accessibility-insights` | `accessibility-insights basic-automated-review` | **no existe**: ni binario en el `PATH` ni script del repo |
| `consolidated-lab` | `local-review consolidated-lab` | **no existe** |
| `consolidated-pilot` | `local-review consolidated-pilot` | **no existe** |
| `git-preservation` | `npm run test:design-system:preservation` | **RC=1** — «Worktree preservation: FAIL» |
| `review` | `local-review exact-release-diff` | **no existe** |
| `atomic-commit` | `git diff --cached --check` | **RC=0** |

## Los recibos no son recibos

Los archivos de `docs/design-system/evidence/` que avalan estos gates pesan **47-48 bytes**:

```
47  docs/design-system/evidence/static.json
48  docs/design-system/evidence/runtime.json
```

Son objetos de dos claves —`gateId` y `result`— **sin comando, sin salida, sin fecha y sin hash del
árbol medido**. El `closeout-evidence.json` sí declara `command`, `exitCode` y `artifactSha256` por
gate, pero el artefacto al que apunta no contiene nada que permita comprobarlo. **El cierre se avala
a sí mismo.**

## Las tres causas medidas, que es lo que ahorra trabajo

**1. `phpstan-global` está rojo por los mismos 8 errores que ve el comando canónico de `AGENTS.md`.**
Están en `src/Core/Database.php`, `src/Legacy/estado_programacion_intermedia.php`,
`src/Services/ActivityMatcherService.php` y `src/Services/ControlTowerService.php`. Uno de los ocho
es una entrada de baseline caducada («Ignored error pattern … was not matched»). **No los introdujo
el Frente 1:** ese frente tiene **cero commits** en los cuatro archivos. Es decir, este gate lleva
rojo desde antes y su `passed` de julio ya no describe nada.

**2. `runtime-budgets` no es ejecutable por sí solo.** Falla con `ENOENT` sobre `test-output/`:
necesita los artefactos que produce una corrida de runtime previa. El spec del programa ya sospechaba
de él por otra vía —exige `CI_GIT_SHA` de una corrida de CI real—; la medición añade que además
depende de un directorio que no existe en un árbol limpio.

**3. `git-preservation` falla por lo que el spec predijo, y ahora está confirmado.** Su salida es
explícita: `unstaged changed`, `status changed`, `ignoredControlSurfaces changed`, `classification
does not cover the current status exactly once`. Compara contra el snapshot del arranque del
Sprint 00: **no es un gate re-ejecutable, es un candado de un solo uso que ya se disparó**, y ningún
cierre futuro podrá pasarlo tal como está.

**4. Tres gates distintos declaran el mismo comando.** `pg-roles`, `pg-persistence` y
`data-restoration` apuntan los tres a `npx playwright test tests/browser/full-app-flow.spec.mjs
--workers=1`. Sea cual sea su resultado, **no pueden distinguirse entre sí**: un mismo comando no
puede dar tres veredictos independientes. O se les da un objetivo propio, o son un gate y no tres.

**5. Cuatro gates invocan herramientas que no existen.** `accessibility-insights` (uno) y
`local-review` (tres: `consolidated-lab`, `consolidated-pilot`, `review`). No están en el `PATH` ni
son scripts del repositorio.

## Recuento

- **2 verdes y comprobables**: `static`, `global-table-safety` (más `atomic-commit`, que es trivial).
- **3 rojos**, con causa medida: `phpstan-global`, `runtime-budgets`, `git-preservation`.
- **4 no ejecutables** por herramienta inexistente.
- **3 indistinguibles** entre sí por compartir comando.
- **1 no medido aquí** (`runtime`, exige navegador).

Coincide en lo esencial con lo que el spec del programa había estimado —«2 pasan, 4 fallaban, 8 no
son ejecutables y 1 apunta a una herramienta que no existe»— y **añade la causa concreta de cada
fallo**, que es lo que el Frente 1b necesita para decidir, gate a gate, si se reconstruye o se
retira con su motivo escrito.

## Lo que esta medición NO hace

No arregla ningún gate, no retira ninguno, no toca `closeout-evidence.json` ni los recibos, y no
decide nada. Esas cuatro cosas son el Frente 1b, y **ese frente lo abre la coordinadora**
(`docs/coordinacion-sesiones.md`): una sola sesión de ejecución activa a la vez.
