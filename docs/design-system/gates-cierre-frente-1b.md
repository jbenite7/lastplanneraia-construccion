# De 15 a 8 gates de cierre — Frente 1b, 2026-08-11

Este documento registra, con su motivo, por qué la lista de gates blocantes de
`docs/design-system/closeout-evidence.json` bajó de 15 a 8. Es la ejecución de tres
decisiones del usuario en `docs/decisiones-pendientes.md`: `D-F1b-1`, `D-F1b-2` y
`D-F1b-3`, medidas en `docs/design-system/medicion-gates-2026-08-11.md`.

No se tocaron los recibos de evidencia (`docs/design-system/evidence/`) ni se migró
el índice a la forma de recibo nueva — eso sigue pendiente y es un paso aparte
(`scripts/design-system/migrate-receipts.mjs`).

## Retirados (5), con su motivo

### `git-preservation` — D-F1b-1

Comparaba el árbol contra el snapshot del arranque del Sprint 00. A más de 1.300
commits de distancia, cada commit nuevo lo aleja más de poder pasar: es un candado
de un solo uso que ya se disparó, no un gate re-ejecutable. `npm run test:design-system:preservation`
da hoy `RC=1` con `unstaged changed`, `status changed`, `ignoredControlSurfaces changed`
y `classification does not cover the current status exactly once`. Un gate que ningún
cierre futuro puede pasar no vigila nada: entrena a ignorarlo, que es como los quince
gates acabaron declarando `passed` sin que nadie los ejecutara.

**Cobertura que se pierde:** ninguna verificación activa de que el árbol de trabajo
siga en el estado del Sprint 00 — pero esa verificación ya no podía dar una respuesta útil.
Candidato anotado para más adelante en `D-F1b-1`: rediseñarlo para comparar contra el
cierre **anterior** en vez del Sprint 00, si se decide que vale la pena.

### `accessibility-insights` — D-F1b-3

Su comando declarado, `accessibility-insights basic-automated-review`, no es un binario
del `PATH` ni un script del repositorio. Nunca pudo ejecutarse tal como estaba declarado.

**Cobertura que se pierde — y lo que sí la cubre:** este repo **sí** tiene un carril de
accesibilidad real, con Playwright + axe, que no es el mismo nombre pero sí mide lo mismo
que este gate prometía:

- `npm run test:a11y:lab` → `playwright test tests/browser/design-system-lab.a11y.mjs`
- `npm run test:a11y:pilot` → `playwright test tests/browser/programa-general-design-system.mjs`

Ambos ya corren dentro de `npm run test:design-system:runtime` (que a su vez es parte
del gate `runtime`, que sigue en la lista de 8). Es decir: **no es que nadie cubra
accesibilidad — es que la cubre otra cosa con otro nombre**, y ese otro carril sigue
activo. Lo que se pierde es la superficie `revealed-states`, que `accessibility-insights`
declaraba (`surfaces: ["laboratory", "pilot", "revealed-states"]`) y que ninguno de los
dos comandos de `test:a11y:*` cubre hoy explícitamente.

### `consolidated-lab`, `consolidated-pilot`, `review` — D-F1b-3

Los tres son de tipo `human` en el propio índice y su comando declarado,
`local-review <algo>`, tampoco es un binario ni un script del repositorio. Su problema
de fondo no era la herramienta que faltaba: es que un juicio humano no debería
declararse como si fuera la salida de un comando automático.

**Cobertura que se pierde:** la revisión visual/local humana de laboratorio, piloto y
diff de release **no tiene hoy un carril sustituto en este repo**. A diferencia de
`accessibility-insights`, aquí no hay "otra cosa con otro nombre" que lo cubra — es una
ausencia real, y queda visible como tal en este documento y en el recuento de gates
(8, no 11), no disimulada detrás de un gate que igual declaraba `passed`.

## Fundidos en uno — D-F1b-2

`pg-roles`, `pg-persistence` y `data-restoration` declaraban, literalmente, el mismo
comando: `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`. Tres
nombres sobre un solo examen no pueden dar tres veredictos distintos.

Se fusionan en **`full-app-flow`**, nombrado por lo que el spec (`tests/browser/full-app-flow.spec.mjs`)
de verdad ejerce: login y sesión por rol (`support/session.mjs`), navegación del shell
y flujos de módulo por proyecto (`support/moduleFlows.mjs`), y restauración de datos vía
snapshot de base de datos y de archivos (`support/restoration.mjs`, `E2ERestorationScope`)
en cada `afterEach`.

**Cobertura que se pierde:** con tres gates independientes, un cierre podía en teoría (si
se les diera objetivo propio, opción `(a)` de `D-F1b-2`, no ejecutada aquí) saber *cuál*
de las tres dimensiones —roles, persistencia, restauración— falló, con su propia fixture
aislada. Con uno solo, un fallo en cualquiera de las tres tira el mismo gate y hay que leer
la salida del spec para saber cuál aspecto fue. No se pierde ejecución (sigue siendo el
mismo spec, con el mismo alcance), se pierde **granularidad de diagnóstico** al leer el
recuento de gates sin abrir el log.

## Recuento final

15 − 5 retirados (`git-preservation`, `accessibility-insights`, `consolidated-lab`,
`consolidated-pilot`, `review`) − 2 por la fusión de tres en uno (`pg-roles` +
`pg-persistence` + `data-restoration` → `full-app-flow`) = **8**.

Orden final en `docs/design-system/closeout-evidence.json` y
`scripts/design-system-closeout-contract.mjs` (`closeoutGateIds`): `static`, `runtime`,
`runtime-budgets`, `phpstan-scoped`, `phpstan-global`, `global-table-safety`,
`full-app-flow`, `atomic-commit`.

## Dos aclaraciones más, decididas el 2026-08-11

### `runtime-budgets` no corre fuera de CI, y la razón no es la que se escribió primero

**Corregido el 2026-08-11, después de medirlo. La versión anterior de esta sección era falsa y la
dictó la coordinadora sin comprobarla.** Decía que `runtime-budgets` mide «los artefactos que produce
la corrida de `runtime`» y que por eso se declaraba **dependiente de `runtime`**. Nada de eso es
cierto.

**Lo medido, sobre `2f060464`:**

- El gate declara `npm run test:runtime-budget:check`, que compara un baseline contra
  `test-output/design-system-runtime-budget.json`.
- **La corrida de `runtime` no produce ese archivo.** Su etapa de rendimiento escribe
  `design-system-lab-performance.json`, que es **otro archivo**. Con `runtime` recién pasado en verde
  y sus cuatro etapas medidas, el `check` sigue dando `ENOENT`.
- Quien lo produce es **su propio paso**, `npm run test:runtime-budget:measure`, que el gate no
  declara.

**Y ese paso se niega a correr en local, a propósito.** Exige un contexto de procedencia completo —
`CI_RUN_ID`, `CI_GIT_SHA`, `CI_WORKTREE_FINGERPRINT`, `CI_FIXTURE_SHA256`— validado contra un árbol
limpio, y descarta muestras de más de 15 minutos
(`scripts/design-system-runtime-budget-provenance.mjs:133-148`). Con el árbol limpio y `CI_GIT_SHA`
correcto sigue negándose, porque faltan las otras dos.

**Esa negativa es la guarda haciendo su trabajo, no un defecto.** Un presupuesto de rendimiento
medido en una máquina de desarrollo, con otros contenedores compitiendo por CPU, no es comparable
con un baseline de CI. Fabricar esas variables a mano para que pase sería **inventar una procedencia
de CI**, que es exactamente el gesto que este programa desmonta.

**Estado correcto: `blocked`, no ejecutable fuera de CI.** No es dependiente de `runtime` —son
carriles distintos con artefactos distintos— y su rojo no dice nada sobre el rendimiento del
producto: dice que **nadie lo ha medido donde se puede medir**.

Lo que haría falta para sacarlo de ahí es que el CI lo corra con su contexto, o declarar el `measure`
como parte del gate. Las dos cosas son trabajo propio y ninguna se decide aquí.

### El recibo de `static` se mide en la corrida anterior, no en la propia

`static` es la suite que **valida el índice que lo referencia**, así que no puede a la vez ser el
recibo de sí misma: si se midiera en la propia corrida, cada actualización del índice invalidaría el
recibo que acaba de escribir, y el proceso no convergería nunca.

La salida adoptada es la más simple y no inventa nada: **su recibo describe la corrida anterior**.
Es lo mismo que hace cualquier CI —el recibo de una ejecución nunca puede incluir el efecto de
publicarlo—, y queda escrito aquí para que nadie lo lea como un descuido.

**Lo que esto NO significa:** que `static` esté exento de decir la verdad. Su recibo lleva comando,
código de salida, fecha y árbol medido como los demás; lo único que se acepta es que el árbol que
describe sea el del commit inmediatamente anterior al que fija su propio hash.
