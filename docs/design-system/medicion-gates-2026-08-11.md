---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-11
areas: [design-system]
fuente: docs/design-system/medicion-gates-2026-08-11.md
resumen: Medido sobre b313de3f, en el worktree elegant-jones-d4126a, con COMPOSEFILE=docker-compose.wt.yml (sin eso, docker compose exec app resuelve al contenedor del…
---

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
| `runtime` | `npm run test:design-system:runtime` | **RC=1** — **2 fallos de 31** (medido el 2026-08-11 sobre `9138386a`) |
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
- **`runtime` medido el 2026-08-11 y también rojo**: RC=1, **2 fallos de 31** sobre `9138386a`. Falla
  `design-system-lab.mjs:252` («severity and urgency blocks keep distinct semantic backgrounds») y
  `:375` («sidebar shell keeps desktop width, context and theme-visible brand mark»). **No son del
  Frente 1 ni del 1b:** cero commits suyos en `views/design-system/families/states-feedback.php` y
  en `public/css/design-system/lab.css`; los últimos que los tocaron son `a3fee1fb`, `cf75c04b` y
  `ed8f90b4`. Es decir, **el carril de runtime lleva rojo desde antes y declaraba `passed` desde
  julio**, que es el mismo patrón de los otros tres.
- **Recuento corregido: 4 rojos con causa medida** (`phpstan-global`, `runtime-budgets`,
  `git-preservation`, `runtime`), no 3.

Coincide en lo esencial con lo que el spec del programa había estimado —«2 pasan, 4 fallaban, 8 no
son ejecutables y 1 apunta a una herramienta que no existe»— y **añade la causa concreta de cada
fallo**, que es lo que el Frente 1b necesita para decidir, gate a gate, si se reconstruye o se
retira con su motivo escrito.

## Lo que esta medición NO hace

No arregla ningún gate, no retira ninguno, no toca `closeout-evidence.json` ni los recibos, y no
decide nada. Esas cuatro cosas son el Frente 1b, y **ese frente lo abre la coordinadora**
(`docs/coordinacion-sesiones.md`): una sola sesión de ejecución activa a la vez.

---

## Lo que se construyó el 2026-08-11 (Frente 1b, primera entrega)

**`scripts/design-system/gate-receipt.mjs`** — genera el recibo de un gate **ejecutándolo**. No
acepta el resultado como parámetro: lo **deriva** del código de salida, que es la única forma de que
un recibo no pueda afirmar algo distinto de lo que ocurrió. Lee el comando del propio
`closeout-evidence.json`, para que no pueda medir algo distinto de lo que el índice declara. Anota
comando, código de salida, fecha, **el árbol medido y si estaba sucio**, y la cola de la salida real.

**`tests/design-system/gate-receipt-content.test.mjs`** — el candado que faltaba. El gate de
gobernanza que ya existía comprueba que haya quince gates, que sus ids no se repitan y que estén en
orden: **cuenta y comprueba nombres**. Nunca abría el recibo. Este valida **contenido**, y falla
cerrado ante las cuatro mentiras posibles: declararse `passed` con el comando en rojo (que es la
forma exacta que tenían los quince recibos de julio), medir un comando distinto del declarado, no
decir sobre qué árbol se midió, y la forma vieja de dos claves.

**Entregado con su mutación en rojo, ejecutada:** el test comprueba explícitamente que un recibo con
`exitCode: 1` y `result: 'passed'` es rechazado. Sin esa comprobación, el candado no prueba que muerda.

**Las exclusiones van por nombre y con motivo**, y con un test propio que falla si una exclusión
nombra un gate que ya no existe. Los ocho excluidos son los que tienen decisión encolada
(`D-F1b-1`, `D-F1b-2`, `D-F1b-3`): una exclusión sin dueño es como empezó este problema.

### Lo que falta para migrar los recibos, medido al intentarlo

Regenerar los recibos **no basta**, y conviene que quede escrito porque no es evidente. El contrato
de `scripts/design-system-closeout-contract.mjs` es **más estricto de lo que esta medición supuso al
principio**, y eso es una buena noticia: sí verifica contenido y procedencia.

Al sustituir los cuatro recibos ejecutables por recibos reales, el gate `contracts` cae con:

```
- global-table-safety: artifactSha256 does not match the evidence artifact
- global-table-safety: evidence artifact is stale relative to sourceRef
- static: unresolved gate must have null verifiedAt
- atomic-commit: passed requires fresh verifiedAt and structured evidence
```

De donde salen tres requisitos que la migración tiene que cumplir:

1. **`verifiedAt` con formato exacto** `YYYY-MM-DDTHH:MM:SSZ` —sin milisegundos— y **posterior** a
   `generatedAt`. Un gate que no esté `passed` debe tener `verifiedAt: null`.
2. **El artefacto tiene que estar commiteado**, y `sourceRef` resolver al commit donde ese archivo
   coincide (`design-system-evidence-receipt.mjs:120-130`). Es decir, la migración es de **dos
   tiempos**: commitear los recibos y solo entonces apuntarles el `sourceRef`.
3. **`artifactSha256` se recalcula** con cada recibo nuevo.

**Por eso esta entrega no migra el índice todavía:** ocho de los quince gates están esperando las
decisiones `D-F1b-1`, `D-F1b-2` y `D-F1b-3`, y migrar los siete restantes por separado dejaría el
índice en un estado mixto durante días. El mecanismo ya está y probado; la migración se hace de una
vez cuando las tres decisiones aterricen.

**Lo que sí se pagó ya:** el gate `phpstan-global` baja de **8 errores a 6** (`96d194b9`). Uno de los
dos era una **entrada de baseline caducada** —esperaba una aparición y había dos, así que el propio
baseline avisaba de que ya no describía el código— y se **retiró** en vez de subirle el contador.
Antes se comprobó la hipótesis grave, que era que la puntuación por ubicación del emparejador
estuviera inerte: **es falsa**, las cuatro ramas se ejecutan (verificado invocando el método:
1.000 / 0.300 / 0.700 / 0.500). Lo que sobraba era media condición ya garantizada por la anterior.


## La causa del rojo de `runtime`, medida el 2026-08-11

El fallo de `design-system-lab.mjs:252` («severity and urgency blocks keep distinct semantic
backgrounds») tiene causa concreta:

```
Expected: "rgb(67, 20, 20)"   // #431414 — el ancla crítica de la escala de estado
Received: "rgb(69, 42, 13)"   // #452a0d — --ds-state-tint-orange
```

Es decir: **el bloque que debería pintarse con el crítico se está pintando con el tinte naranja.**
El último commit que tocó esa escala es `fff71ad9` (**2026-08-03**), «la escala de celda deriva de la
de estado y el contrato se mide en runtime» — **anterior a todo este programa**: el Frente 1 y el 1b
tienen cero commits en `views/design-system/families/states-feedback.php` y `public/css/design-system/lab.css`.

**Y ese mismo commit dejó escrita la trampa que lo explica**, en
`memoria/trampas/gate-estatico-no-ve-tokens-rotos.md`: un gate que lee archivos da verde con un token
que apunta a una variable inexistente, porque la declaración es sintácticamente impecable y el fallo
solo ocurre **al resolver la cascada**. Su conclusión —«todo contrato sobre valores resueltos
necesita superficie de runtime»— es exactamente por lo que existe el carril `runtime`… **que lleva
rojo desde entonces declarando `passed`.**

O sea: alguien vio el problema, escribió el carril correcto para cazarlo, dejó la lección apuntada, y
el carril nunca se ejecutó. **Es el mismo patrón que la regresión de las cabeceras**, en la otra
punta del programa: el instrumento existe y nadie lo mira.

Arreglar el mapeo de severidad no es trabajo de esta medición —toca la escala semántica del design
system, que es capa contractual—, pero la causa queda localizada para quien lo tome.


## `full-app-flow` no está roto: está negándose a correr, y hace bien

Medido el 2026-08-11 tras fundir los tres gates de datos en uno:

```
Missing isolated E2E database mutation consent: set
E2E_REQUIRE_ISOLATED_DB=1 and E2E_ALLOW_DB_MUTATION=design-system-ci.
  at assertE2EMutationConsent (tests/browser/support/restoration.mjs:17)
```

**No es un fallo del gate: es su guardia funcionando.** La suite se niega a mutar datos sin que
alguien declare explícitamente que la base es aislada y que consiente la mutación. El spec del
programa ya lo anticipaba —«los tres gates de datos necesitan una fixture aislada y el consentimiento
explícito de mutación que hoy los bloquea en seguro»—, y la medición lo confirma con su mensaje.

**Y aquí no se le da ese consentimiento.** Este worktree corre contra el contenedor de base de datos
**compartido** con el árbol principal y con las demás sesiones. Poner `E2E_ALLOW_DB_MUTATION` sobre
una base compartida sería exactamente lo que esa guardia existe para impedir, y por un motivo que no
es teórico: la variable se llama `design-system-ci` porque está pensada para una base efímera de CI,
no para la de trabajo.

**Reclasificación:** `full-app-flow` **no cuenta como rojo por defecto propio**. Cuenta como **no
ejecutable en este entorno**, que es distinto y se arregla de otra forma: dándole una base aislada
—una instancia efímera o un esquema propio sembrado y destruido por la propia suite—. Eso es
infraestructura, no reparación de un gate, y merece decidirse antes de hacerse.

**Recuento final de los ocho, medido:**

| Gate | Estado real |
|---|---|
| `static` | **verde**, con recibo pendiente por circularidad (mide la suite que valida el índice que lo referencia) |
| `phpstan-scoped` | **verde con recibo real** |
| `phpstan-global` | **verde con recibo real** (de 8 errores a 0 el 2026-08-11) |
| `global-table-safety` | **verde con recibo real** |
| `atomic-commit` | **verde con recibo real** |
| `runtime` | **rojo**, causa localizada: el bloque crítico se pinta con `--ds-state-tint-orange` |
| `runtime-budgets` | **no ejecutable solo**: `ENOENT` sobre `test-output/`, necesita artefactos de una corrida previa |
| `full-app-flow` | **no ejecutable aquí**: exige base aislada y consentimiento de mutación, y hace bien |

**Cinco verdes de ocho**, cuatro de ellos con recibo real. Frente a los **tres verdes comprobables de
quince** con que empezó el frente, y con la diferencia de que ahora los números describen algo.
