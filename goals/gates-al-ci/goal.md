<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: gates-al-ci

## Fase del plan
Plan: docs/superpowers/plans/2026-08-11-cierre-hasta-produccion.md
Fase: Fase F-AB
Sha verificado: ef4780b0 (static RC=0; efecto medido contra 0e45ba1d)

## Objetivo
Enchufar `full-app-flow` y `runtime-budgets` al job `design-system-runtime` de
`.github/workflows/design-system.yml` y pasarlos a `passed` en
`docs/design-system/closeout-evidence.json` con procedencia de una corrida real de CI (8/8).

## Condición de hecho
`docs/design-system/closeout-evidence.json` con los ocho gates en `passed`, la procedencia
tomada de una corrida real de GitHub Actions, y `npm run test:design-system:static` en `RC=0`.

## Tarea 1 — el workflow NO corre verde hoy (medido, no recordado)

Medido el 2026-08-12 sobre `4dc4631a` (worktree `elegant-jones-d4126a`).

`gh run list --workflow=design-system.yml --limit 5`:

```
in_progress		docs(plans): plan de cierre hasta produccion, cuatro fases tarea por …	Design System	main	push	31553920501	1m3s	2026-08-12T01:32:17Z
completed	failure	docs(decisiones): cierra D-VOC-1..4, que el usuario decidio y yo no a…	Design System	main	push	31552502320	3m42s	2026-08-12T01:06:22Z
completed	cancelled	docs(coordinacion): la etiqueta [COORDINA] describe el papel, no iden…	Design System	main	push	31552387291	2m17s	2026-08-12T01:04:23Z
completed	cancelled	docs(coordinacion): la pagina mandaba desempatar y preguntar a la vez	Design System	main	push	31552329341	1m20s	2026-08-12T01:03:21Z
completed	failure	docs(coordinacion): adopta la gramatica de titulos por fase del plugin	Design System	main	push	31552001285	3m48s	2026-08-12T00:57:36Z
```

Reparto de las 30 últimas corridas (`gh run list --limit 30`): **22 `failure`, 7 `cancelled`, 1 en
curso, 0 `success`**. La última corrida verde de este workflow es del **2026-07-17**
(`gh run list --status=success --limit 5` → id `29577390968`, `2026-07-17T11:36:15Z`).

Paso que cae, en las dos fallidas inspeccionadas (`gh run view <id> --json jobs`):

```
design-system-static => failure
design-system-runtime => skipped
```

`design-system-runtime` lleva `needs: design-system-static` (`.github/workflows/design-system.yml:51`),
así que **el job donde había que enchufar los dos gates no llega a ejecutarse nunca**.

Causa del rojo (`gh run view 31552502320 --log-failed`), reproducida en local sobre `4dc4631a`
con `node tests/test_programa_general_sprint_contract.mjs`:

```
AssertionError [ERR_ASSERTION]: PG no debe usar important local
    at tests/test_programa_general_sprint_contract.mjs:41:8
  expected: /!important/, operator: 'doesNotMatch'
```

Origen: `public/css/programa-general.css:621-623` declara tres `!important` dentro de
`@layer components` para `html.aia-theme-dark body.pg-page .pdc-legend-item.is-zero`. Los
introdujo `20f08dd2` (2026-08-07, «fix(pg,cc): los chips en cero de PG se atenuan…»), y su propio
comentario dice que es «copia literal de la receta ya validada en `programacion-intermedia.css`».
El contrato `tests/test_programa_general_sprint_contract.mjs:41` prohíbe cualquier `!important` en
`public/css/programa-general.css`, sin excepción por capa.

**Decisión aplicada (plan, Tarea 1, Paso 2): rojo → escalar a la coordinadora y no arreglar el CI
dentro de esta fase.** Escalada enviada el 2026-08-12.

## Estado: PAUSADO (no descartado)

Respuesta de la coordinadora, 2026-08-12: escalada confirmada punto por punto y elevada al usuario.
Decisión del usuario **`D-GAC-1`**: la aserción de
`tests/test_programa_general_sprint_contract.mjs:41` pasará a permitir `!important` **dentro de
`@layer`** y a seguirlo prohibiendo **fuera**; el CSS **no** se toca. Esa reparación es la nueva
**fase F-0** del plan (commit `6d82f723` del `main` local) y **sale como chip aparte**: este frente
no la toma.

- **Dónde me quedé:** Tarea 1 completa (medida y anotada arriba). Tareas 2–5 intactas, sin empezar.
- **Qué desbloquea:** que F-0 ponga `design-system-static` en verde. Sin eso,
  `design-system-runtime` sigue en `skipped` y las Tareas 2–5 no se pueden verificar en CI.
- **Estado del árbol:** worktree `elegant-jones-d4126a` limpio, **cero commits** de este frente.
- **Decisiones encoladas:** `decisiones/gates-al-ci-ejecutor.md` (D-1 escalada resuelta, D-2
  `.gitignore` — asumida por la coordinadora).

## Archivos declarados
.github/workflows/design-system.yml,docs/design-system/closeout-evidence.json

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Re-medición al retomar — 2026-08-12, sobre `58f7263c`

Worktree avanzado con `git merge --ff-only origin/main` (sin commits propios que integrar).

- `npm run test:design-system:static` → **`RC=0`**, los ocho gates en verde.
- `node tests/test_programa_general_sprint_contract.mjs` → **`RC=1`**,
  `AssertionError: PG no debe usar important local`.
- `gh run view 31557588758 --json jobs` → `design-system-static => failure`,
  `design-system-runtime => skipped`.

**Corrección a lo escrito arriba:** decir «el static del CI está en rojo» confundía el **job** con la
**suite**. La suite `test:design-system:static` está **verde**; lo que tumba el job es un **paso
aparte**, `Enforce Programa General pilot contract`
(`.github/workflows/design-system.yml:46-47`), que invoca el contrato piloto directamente. La
conclusión no cambia —el job falla y el `needs` corta la cadena— pero la atribución sí. Importa para
F-AB: la suite que este frente va a tocar ya está verde, así que encontrarla roja sería un hallazgo
nuevo y no éste.

**F-0 no ha aterrizado:** `origin/main:tests/test_programa_general_sprint_contract.mjs:41` conserva
`assert.doesNotMatch(css, /!important/)`. `6d82f723` y `615395aa` son documentación (plan y fichas
`D-GAC-1`/`D-GAC-2`), no la reparación. **El frente sigue pausado.**

## Revisión del 2026-08-12 sobre `c014874c` — el frente lo lleva otra sesión

- **F-0 aterrizó:** `design-system-static => success` (corrida `31566518358`). La aserción de
  `tests/test_programa_general_sprint_contract.mjs:84-85` es ahora «PG solo puede usar !important
  dentro de una @layer, nunca fuera de toda capa» (`D-GAC-1`).
- **Duplicidad:** `.claude/sesiones.md` da el frente `gates-al-ci` a **37818e4a**
  (worktree `cranky-dhawan-aa8725`), con mis mismos archivos declarados. Sus commits ya están en
  `origin/main`: `0b2cb1f8`, `09a6e71c`, `9307824c`. Las Tareas 2 y 3 están hechas por esa sesión.
- **`full-app-flow`:** `Enforce full-app-flow gate => success` en CI real; el recibo cita la corrida
  `31564632414`.
- **`runtime-budgets`:** rojo **por presupuesto**, no por procedencia —
  `cssGzipBytes actual 194553` contra `maximum 138981`; `adapterAssets` suma
  `shell-sidebar.css` y `/public/css/design-system/adapters/datatables.css`, y pierde
  `semi-auto-review.css` (PDC v1, eliminado el 2026-08-04).
- **`closeout-evidence.json` en `origin/main`:** 7 `passed`, 1 `blocked` (`runtime-budgets`).

**Estado de este frente: pendiente de que la coordinadora lo declare descartado por duplicidad.**
Sin commits, worktree limpio. Mover el baseline para cerrar el 8/8 es decisión del usuario y archivo
declarado por 37818e4a: no se toca desde aquí.

---

## Tarea 4 (sesión 37818e4a, worktree `cranky-dhawan-aa8725`) — la mutación de `full-app-flow`, ejecutada

**Alcance de esta medición, dicho antes que el resultado:** todo lo de abajo es **runtime aislado en
local** (`docker-compose.yml` + `docker-compose.ci.yml`, puerto 18081), **no** una corrida de
GitHub Actions. La procedencia de CI para la mitad roja sigue **bloqueada** (ver D-3 abajo).

**Punto de partida, y no hizo falta gastar una corrida para tenerlo.** La mitad **verde** de esta
mutación ya existe con procedencia real de CI sobre el mismo sha del que parto:

```
gh run view 31566518358 --json headSha  -> c014874c5db7e182879251843e41670d0565bb0e
  13 success  Enforce full-app-flow gate
  17 failure  Check runtime budgets against the baseline
```

**La mutación, con su predicción escrita ANTES de correr.** Commit `b17bfc95` sobre `c014874c`:
`ProjectDbSnapshot.restore()` convertido en no-op (3 líneas). `DatabaseSnapshot extends
ProjectDbSnapshot` y **no** sobreescribe `restore()` — comprobado leyendo `dbSnapshot.mjs:248-252`,
no supuesto. Aplicada con `assert patron in s` en `python3`, no con un `sed` optimista;
`git diff --stat` = `1 file changed, 3 insertions(+)` antes de correr nada.

Predicción anotada: *cae `E2E database restoration mismatch: before=… after=…`
(`restoration.mjs:164-169`), envuelta en `Full app E2E cleanup failed.`*

**Rojo (mutación puesta), `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`:**

```
GATE_RC=1
  13 failed
    at E2ERestorationScope.restore (tests/browser/support/restoration.mjs:165:15)
    at tests/browser/full-app-flow.spec.mjs:38:39
  > 165 |         throw new Error(
        |               ^
    166 |           'E2E database restoration mismatch: '

recuento de tipos de error en el log completo:
  13 Error: Full app E2E cleanup failed
  13 Error: E2E restoration failed
  13 Error: E2E database restoration mismatch
```

**Cayó exactamente la aserción prevista, y ninguna otra.** No hubo un segundo tipo de fallo que
tapara el primero.

**Verde (mutación revertida, `acd6a754`), sobre un runtime recreado desde cero (`down -v` + `up
--build`) para que el rojo no pudiera venir de una base sucia:**

```
GATE_RC=0
  13 passed (54.6s)
```

**Un tercer dato que no buscaba y que vale la pena dejar: el árbol restaurado es byte a byte el que
midió el CI.** El preflight local sobre `acd6a754` imprime
`CI_WORKTREE_FINGERPRINT=b6be40e97f86911d27cb11a34d43f8093507cc86660581352c4cadda4cac1211`, y ése es
**el mismo** que la corrida `31566518358` registró sobre `c014874c`. La reversión no dejó residuo.

**Y el eje de restauración no era una aserción vacía.** El recibo del último test lo demuestra: la
suite **sí** ensucia el sistema de archivos y la restauración **sí** tiene trabajo que hacer.

```
E2E_RESTORATION_RECEIPT {"files":{...,"removed":[
  ".../storage/compromisosSemana/20260812063056da_porto_Compromisos_S1.xlsx",
  ".../storage/cortesProgramacion/202608125213054_da_porto_semana_1.xlsx",
  ".../storage/cortesRestricciones/...xlsx", ".../storage/ordenes/...ConsolidadoODC.xlsx",
  ".../storage/compromisosSemana", ".../storage/cortesProgramacion", ".../storage/ordenes"]}}
```

Sin ese dato, un verde en el eje de restauración sería indistinguible de un verde por ausencia.

## Tarea 4 — la mutación de `runtime-budgets` NO se ejecuta, y el motivo es que sobra la mitad y falta la otra

**La mitad roja ya la da el mundo real, sin mutar nada.** Segunda medición, independiente de la de
la coordinadora, sobre `c014874c` / corrida `31566518358`:

```
> node scripts/design-system-runtime-budget.mjs check docs/design-system/runtime-baseline-0.3.3.json ...
{ "pass": false, "violations": [
  { "metric": "cssGzipBytes",  "baseline": 136933, "tolerance": 2048, "maximum": 138981, "actual": 194553 },
  { "metric": "adapterAssets", "tolerance": 0, ... } ] }

grep -nE "initializationMs" sobre el log completo de la corrida -> 0 apariciones
```

Es decir: el gate **falla por presupuesto y no por procedencia**, que es exactamente lo que la
mutación del plan tenía que demostrar. Bajar un umbral para provocar un rojo que ya existe no
añadiría información.

Confirma además la corrección de la coordinadora en `D-GAC-5`: **dos** violaciones, no tres, y
`initializationMs` **no** está violado. `cssGzipBytes` varía en 1 byte entre corridas
(194554 → 194553): medición estable.

**Lo que falta es la mitad verde, y no es mía.** Para verla haría falta re-aprobar
`runtime-baseline-0.3.3.json`, y tocar un baseline **escala siempre**. Es `D-GAC-5`, abierta y del
usuario. **No se toca el baseline ni el recibo.** El gate se queda **rojo y honesto**.

## Estado del frente

- **Árbol:** `acd6a754`, limpio; contenido **idéntico** a `c014874c` (`git diff c014874c HEAD` vacío).
  Los dos commits son la mutación y su reversión; no aportan cambio neto.
- **Bloqueado en:** D-3 (gate de push, ver `decisiones/gates-al-ci-ejecutor.md`) y D-GAC-5.
- **Sin visto pedido todavía**, porque no hay nada neto que publicar.

## Retomado el 2026-08-12 sobre `0e45ba1d` — qué había cambiado y qué hice

**Lo primero, medido: la coordinadora ejecutó la mutación de `full-app-flow` en CI mientras yo la
ejecutaba en local.** Corrida `31591828197`, rama `mutacion/full-app-flow-restauracion` vía
`workflow_dispatch` (`5a337f3e`). **Eso resuelve D-3**: la procedencia de CI que me faltaba existe.

**Y las dos mediciones son independientes y coinciden**, que es justo la defensa que este repo
persigue — «que lo mire otro, por un segundo camino»:

| | Coordinadora (CI) | Yo (runtime aislado local) |
|---|---|---|
| Dónde mutó | `restoration.mjs`, anulando `databaseSnapshot.restore()` | `dbSnapshot.mjs`, `ProjectDbSnapshot.restore()` como no-op |
| Resultado | `failure`, 13 de 13 | `GATE_RC=1`, 13 de 13 |
| Aserción caída | `E2E database restoration mismatch: before=008eb690… after=e1a11b76…` | la misma, 13 veces, sin ningún otro tipo de error |

Dos puntos de entrada distintos al mismo eje, el mismo veredicto. **Y mi medición local contradice
de frente el error #1 que ella misma dejó anotado** («concluí que la restauración se ejecuta pero no
se asevera — falso»): mi recibo verde trae `removed` con 7 rutas reales de `storage/`, así que el
eje ni es vacío ni deja de aseverarse.

**Tarea 4: cerrada.** `full-app-flow` mordió y se le vio morder por el motivo correcto.
`runtime-budgets` no se muta a propósito, y las dos sesiones llegamos a lo mismo por separado: ya se
le vio fallar **por presupuesto y no por procedencia** en corridas reales.

**Lo que hice al retomar, y es lo único que quedaba hacer sin decidir nada ajeno.** `D-GAC-5` dejó
escrita una sub-pregunta con su método —«(3) se verifica leyendo cómo compone esa ruta el medidor,
sin tocar el baseline»—. La resolví. **Refuta la hipótesis:** el medidor guarda `url.pathname`
verbatim y filtra por subcadena, así que no compone nada raro; la ruta `/public/css/…` está escrita
en el código, en `vendor-datatables-legacy.css:6`, **1 de 18 imports** (29-a-1 por la segunda vía de
recuento) y la única sin `?v=`. Con eso, **las tres diferencias de `adapterAssets` son reales** y
ninguna es artefacto de medición. Detalle completo, con las tres muestras del artefacto de CI, en
`decisiones/gates-al-ci-ejecutor.md` · D-5.

**El arreglo es de una línea y NO lo hago:** el archivo no está en mis declarados y tocarlo mueve
`adapterAssets`, que es la entrada de una decisión abierta del usuario. Medir no es decidir.

**Estado del frente:** `runtime-budgets` sigue `blocked` y honesto; 7/8. La condición de hecho
original (8/8) **no se puede cumplir desde aquí** sin resolver `D-GAC-5`. Árbol limpio en
`gates-al-ci/medicion-adapterassets` = `origin/main` = `0e45ba1d`, **sin commits propios**: todo lo
medido es lectura y salida de comando, nada que publicar.

## El arreglo de la línea — autorizado por el usuario, 2026-08-12

`ef4780b0` sobre `0e45ba1d`. Un archivo, una línea, **solo el prefijo**:
`vendor-datatables-legacy.css:6` pasa de `/public/css/design-system/adapters/datatables.css` a
`/css/…`. No se añadió el `?v=1.1.0` de los otros diecisiete: eso es cache-busting y excede el
encargo.

**Verificado, con imagen reconstruida por árbol en el mismo runtime aislado:**

```
sin arreglo (0e45ba1d):  /public/css/design-system/adapters/datatables.css   cssGzipBytes 195401
con arreglo (ef4780b0):  /css/design-system/adapters/datatables.css          cssGzipBytes 195402
npm run test:design-system:static  ->  RC=0  (ocho suites)
npm run test:runtime-budget:check  ->  RC=1  (cssGzipBytes y adapterAssets; correcto)
"added": ["/css/…/datatables.css", "/css/…/shell-sidebar.css"]
```

**Δ = +1 byte de gzip: el arreglo hace una cosa y nada más.** Baseline y recibos intactos.

**Lo que NO verifiqué, y lo digo:** no volví a correr `full-app-flow` ni la suite visual sobre este
sha. El cambio es una URL de CSS y ninguno de los dos asevera sobre eso, pero es un razonamiento,
no una medición.

**Pendiente de visto para publicar.** El usuario autorizó **el arreglo**; la publicación es de la
coordinadora y no la doy por concedida.

**Aviso que vale para cualquier frente que mida en el runtime aislado:** `COMPOSE_FILE` no incluye
`docker-compose.override.yml`, así que el contenedor lleva el código **horneado en la imagen**.
`git checkout` en el host **no** cambia lo que se mide. Sin `up --build`, se mide la imagen
anterior — me pasó, y los dos números iguales encajaban con la conclusión cómoda. Detalle en
`decisiones/gates-al-ci-ejecutor.md` · D-5.

## Retomado el 2026-08-12 por bbd231db (worktree `beautiful-blackwell-414f09`) — encargo D-GAC-5 ejecutado, y dos escaladas nuevas

Sha medido: **`0f968d2f`** (= `0e45ba1d` + dos commits propios, sin publicar).

- **`365c486e`** — la línea, corregida como pidió el encargo: `vendor-datatables-legacy.css:6` →
  `/css/design-system/adapters/datatables.css?v=1.1.0` (esta vez CON `?v=1.1.0`, a diferencia del
  `ef4780b0` de la sesión muerta, porque la coordinadora lo pidió explícito). Contención medida
  antes: 0 commits hoy sobre el archivo.
- **Re-medición limpia** en runtime aislado local (imagen reconstruida con `up --build`, arreglo
  verificado dentro del contenedor — trampa de D-5 evitada):
  `adapterAssets` = 8 rutas canónicas; `added` = `datatables.css`, `shell-sidebar.css`.
- **`0f968d2f`** — baseline `runtime-baseline-0.3.3.json` actualizado SOLO en `adapterAssets`
  (autorización D-GAC-5). Re-check sobre ese sha: `CHECK_RC=1` con **una** violación,
  `cssGzipBytes` 195383 vs máx 138981 — la deuda D-GAC-5(b), intacta.
- **Rotura descubierta:** `npm run test:design-system:static` → **RC=1** sobre `0f968d2f`:
  `visual-ci-contract.test.mjs:354` exige baseline ≡ retrospectiva 0.3.3 (fijada por sha256).
  La actualización autorizada rompe esa identidad. **Escalada como D-6** (contrato: bloquea siempre).
- **Gate PG (encargo punto 4):** en el runtime aislado canónico, tal cual el repo →
  `PG_GATE_RC=1` («no autenticó a test.C»: `docker-compose.ci.yml:21` no lo habilita). Con `test.C`
  añadido en local (revertido, sin commit) → **`PG_GATE_RC=0`, 3 passed / 1 skipped** sobre
  `0f968d2f`. El RBAC está sano; lo que falla en CI sería configuración. **Escalada como D-7.**

**Condición de hecho (8/8): NO alcanzada y NO alcanzable desde aquí.** Faltan: D-6 (identidad
baseline/contrato), D-7 (test.C en CI), y `cssGzipBytes` (D-GAC-5(b), del usuario). El árbol queda
con los dos commits sin publicar, a la espera de la coordinadora.

## Publicaciones

- 2026-08-12 · `365c486e` (la línea de `vendor-datatables-legacy.css`, autorizada por el usuario)
  publicado por la coordinadora (01a82dae) vía merge `b3d65ddd`, tras re-verificar sobre el árbol
  integrado: `visual-ci-contract` 12/12 y contrato PG `RC=0`. Efecto vivo en el publicado:
  `git show origin/main:public/css/design-system/vendor-datatables-legacy.css` línea 6 =
  `/css/design-system/adapters/datatables.css?v=1.1.0`. **No** se publicó `0f968d2f` (baseline):
  descartado por D-GAC-6. El frente sigue ABIERTO: quedan D-GAC-7 (test.C en CI, encargo del
  frente), la deuda `cssGzipBytes` y el baseline 0.3.4 (D-GAC-5(b), del usuario).

## Encargo del baseline 0.3.4 — autorizado por el usuario, 2026-08-12 (vía coordinadora)

El usuario aprobó crear el **baseline 0.3.4** según la recomendación del informe de atribución
(`docs/design-system/runtime-measurements/2026-08-12-atribucion-css-gzip-0.3.3.md`, publicado en
`df4bf7b3`). Esta autorización cubre lo que D-GAC-6 dejó bloqueado, por la vía (a):

1. **Medición fresca con procedencia real** sobre un sha ≥ `ef4780b0` con la línea corregida
   (cualquier sha ≥ `b3d65ddd` la contiene). Fuente preferida: los `sample-N.json` del artefacto de
   una corrida de CI sobre ese sha — traen `provenance.assets` completo (hallazgo del informe); si
   se mide en local, imagen reconstruida (`up --build`, trampa D-5).
2. **`runtime-baseline-0.3.4.json` nuevo** con su measurement y manifest propios (no editar nada
   de 0.3.3: queda como histórico anclado por sha256). `adapterAssets` con las 8 rutas canónicas;
   `cssGzipBytes` con la cifra medida (~195,4 KB esperados).
3. **Actualizar los punteros**: `package.json` (`test:runtime-budget:check`) y
   `tests/design-system/visual-ci-contract.test.mjs` pasan a 0.3.4. El cambio de contrato está
   autorizado por esta decisión, pero **entréguese con sus mutaciones ejecutadas** (baseline
   editado a mano → rojo; puntero viejo → rojo) y la suite estática en RC=0 sobre el sha entregado.
4. **Adjuntar la atribución** como justificación en el propio baseline (`approval` o campo
   equivalente que el schema permita).
5. Meta: `runtime-budgets` pasa a verde con procedencia de CI y `closeout-evidence.json` llega a
   8/8, que es la condición de hecho original de este frente.

Sigue vigente: entrega de 6 campos, visto por sha antes de publicar.
