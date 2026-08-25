---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-11
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-11-cierre-hasta-produccion.md
resumen: llevar el repositorio a ocho gates verdes, cola de decisiones vacía y el trabajo publicado en producción.
---

# Plan de cierre hasta producción

> **Para sesiones de ejecución:** SUB-SKILL OBLIGATORIA — usa `superpowers:executing-plans` para
> ejecutar este plan tarea por tarea. Los pasos usan casillas (`- [ ]`).
>
> **Este plan lo reparte la coordinadora, una fase a la vez.** No encadenes fases: haz la que te
> asignaron y entrégala (`D-F1-7`). Un objetivo redactado como «termina el plan» no amplía el encargo.

**Objetivo:** llevar el repositorio a ocho gates verdes, cola de decisiones vacía y el trabajo
publicado en producción.

**Spec:** [2026-08-11-plan-cierre-hasta-produccion-design.md](../specs/2026-08-11-plan-cierre-hasta-produccion-design.md)

**Arquitectura:** **cuatro** fases vivas (F-D se retiró el 2026-08-12, ya estaba hecha), cada una **un frente** con worktree propio, ciclo de visto y el
gate de nueve pasos de `AGENTS.md` §Publicación. Ninguna empieza hasta que la anterior está publicada
**y anotada** (paso 9). El orden no es estético: **F-0** devuelve el CI a verde —sin él nada de lo
que sigue se puede verificar—, F-AB deja los ocho gates verdes, y solo entonces el verde de F-C
significa algo.

> **Cambio del 2026-08-12.** Este plan nació con cuatro fases y empezaba por F-AB. La sesión de F-AB
> midió en su Tarea 1 que el CI lleva rojo desde el **2026-07-17** y escaló en vez de seguir, que era
> exactamente lo que esa tarea existía para provocar. Se añadió **F-0** delante. El plan funcionó por
> donde se esperaba que fallara.

**Stack tocado:** GitHub Actions + Docker Compose (F-0, F-AB), JSON Schema + Node test runner (F-C),
rutina de SiteGround (F-E).

## Restricciones globales

Aplican a **todas** las tareas de todas las fases. No se repiten en cada una.

- **Nada se declara hecho sin salida real de comando** de esa sesión. Ni «debería pasar», ni «lo
  medí antes».
- **Toda afirmación que otra sesión vaya a leer viaja con su sha**: `RC=0 sobre <sha>`, nunca «está
  verde». En conversación directa con el usuario no hace falta.
- **Todo gate se entrega con su mutación en rojo, ejecutada**, y se mira **qué aserción cae**. Si cae
  otra distinta de la esperada, la esperada no servía. En aserciones de conteo, **la mutación útil no
  es cambiar el número: es cambiar qué se cuenta**.
- **Prohibido fabricar evidencia**: goldens, recibos, variables de CI, `ignore-file` sobre hallazgos
  ciertos. Si algo no se puede medir, se dice que no se pudo.
- **Nunca leas `$?` después de una tubería** — vale 0 pase lo que pase. Guarda el código de salida en
  su propia línea.
- **El `push` va en comando aparte**, jamás encadenado a la verificación con `&&`, `;` ni detrás de un
  `echo`. Un gate solo gobierna si puede impedir la publicación.
- **Ante una decisión que no te toca:** si contestarte lo contrario te obligaría a borrar algo ya
  escrito, **escala a la coordinadora y espera**. Si no, anótala en tu
  `decisiones/<frente>-ejecutor.md`, **salta el hallazgo intacto** y sigue. Tocar un contrato, un
  baseline, borrar algo, cambiar lo que una prueba mide o desviarte del plan **escalan siempre**, sin
  aplicar esa prueba.
- **Nunca hables con el usuario.** El canal es `mcp__ccd_session_mgmt__send_message` a la
  coordinadora. Tampoco si urge: la urgencia también se le manda a ella.
- **Entorno:** todo PHP corre dentro del contenedor `app`. Sesión local solo por la puerta de
  servicio (`/dev/entrar`), nunca tecleando credenciales en `/login`.

---

## Fase F-0 · Poner el CI en verde — `D-GAC-1`

**Frente:** `ci-en-verde`. **Añadida el 2026-08-12**, delante de todo, cuando la sesión de F-AB
midió en su Tarea 1 que el workflow lleva rojo desde el **2026-07-17** y escaló en vez de seguir.

**Por qué va primera:** `design-system-runtime` lleva `needs: design-system-static`
(`.github/workflows/design-system.yml:51`). Con ese job en rojo, aquel donde F-AB tiene que
enchufar los dos gates **no se ejecuta nunca** — no habría forma de verlos fallar ni de sacar
procedencia real. Sin esta fase, **ninguna de las siguientes se puede verificar**.

> **Corrección del 2026-08-12, y el error era de la coordinadora.** Esta sección decía que «la suite
> estática está en rojo». **Es falso, y lo midió la sesión de F-0 al llegar:**
> `npm run test:design-system:static` da **`RC=0`, 8/8**, también con `DS_ACTIVATION_STRICT=1` como en
> CI, y `node-tests` ni siquiera barre este archivo. Lo que tumba el job es un **paso aparte**,
> `Enforce Programa General pilot contract` (`.github/workflows/design-system.yml:46-47`), que invoca
> el contrato piloto directamente. La conclusión aguanta —el job falla y el `needs` corta la cadena—
> pero la atribución era mía y estaba mal. **Importa para F-AB:** el static que va a tocar ya está
> verde, y si lo encuentra rojo es un hallazgo nuevo, no éste.

**Decisión del usuario (`D-GAC-1`, 2026-08-12):** opción **(a) en su forma estrecha** — la aserción
pasa a permitir `!important` **dentro de `@layer`** y a seguir prohibiéndolo **fuera**.

**Qué está medido** (sobre `4dc4631a`, por dos sesiones independientes):
`test_programa_general_sprint_contract.mjs:41` prohíbe `!important` sin excepción;
`programa-general.css:620-624` tiene tres dentro de `@layer components`, razonados en su comentario;
`node tests/test_programa_general_sprint_contract.mjs` → **RC=1**. Y el reencuadre:
`programacion-semanal.css` tiene **433** `!important`, `programacion-intermedia.css` **182**,
`buttons.css` **138**, `programa-general.css` **4**, con **un solo** contrato de este tipo en el repo.
Es disciplina de piloto, no principio del repositorio.

**Archivos:** `tests/test_programa_general_sprint_contract.mjs`. **No** se toca
`public/css/programa-general.css`: el CSS no cede, esa fue la decisión.

### Tarea 1: Cambiar la aserción, y verla rechazar lo que debe

- [ ] **Paso 1: leer la aserción y su vecindad**

```bash
sed -n '30,50p' tests/test_programa_general_sprint_contract.mjs
```

- [ ] **Paso 2: sustituirla por la versión con excepción de capa**

La regla es: `!important` **fuera** de cualquier `@layer` sigue prohibido; **dentro**, permitido. La
forma exacta la eliges tú según cómo esté leído el CSS en ese archivo, pero el mensaje de la
aserción tiene que decir la regla nueva, no la vieja.

- [ ] **Paso 3: correrla y verla pasar sobre el CSS de hoy**

```bash
node tests/test_programa_general_sprint_contract.mjs
echo "RC=$?"
```

Esperado: `RC=0`. Los tres `!important` de `:620-624` están dentro de `@layer components`.

- [ ] **Paso 4: la mutación, que es lo que da valor a la tarea**

Añade **temporalmente** un `!important` **fuera** de toda capa en `programa-general.css`.

```bash
node tests/test_programa_general_sprint_contract.mjs
echo "RC=$?"
```

Esperado: **`RC=1`**. **Si sigue en 0, la aserción nueva no vigila nada y hay que rehacerla** — no la
des por buena porque el caso normal pase. Anota qué mensaje salió.

- [ ] **Paso 5: retirar la mutación y confirmar `RC=0`**

- [ ] **Paso 6: commit**

```bash
git add tests/test_programa_general_sprint_contract.mjs
git commit -m "test(pg): !important permitido dentro de @layer, prohibido fuera (D-GAC-1)"
```

### Tarea 2: Comprobar que el static pasa entero, no solo esta prueba

Una prueba verde no es el gate verde. El job corre varias cosas.

- [ ] **Paso 1: correr lo que corre el job**

```bash
npm run test:design-system:static
echo "RC=$?"
node tests/test_programa_general_sprint_contract.mjs
echo "RC=$?"
```

- [ ] **Paso 2: si aparece otro rojo distinto, no lo arregles de paso**

Escala a la coordinadora con la salida literal. Un segundo defecto escondido detrás del primero es un
hallazgo, no una tarea más de esta fase.

### Tarea 2b: El segundo rojo — la regex de los chips quedó vieja (`D-GAC-2`)

**Añadida el 2026-08-12**, cuando la Tarea 2 destapó lo que el `!important` venía tapando. Se pliega
aquí y no abre frente propio: F-0 no puede cerrar sin esto —su condición de hecho es ver `success` en
Actions y este paso seguiría tumbando el job— y serían dos frentes en fila sobre el mismo archivo.

**Qué está medido** (por la sesión de F-0 y confirmado por la coordinadora): la línea 21 exige
`class="aia-chip pg-filter-chip[^"]*"` con las dos clases **contiguas**, y el markup real es
`class="aia-chip pdc-legend-item pg-filter-chip …"`. Los 14 chips existen (`grep -c pg-filter-chip`
→ 14); lo que da `0` es la forma contigua. Se rompió en **`47dda844` (2026-08-04)**, y el fallo del
`!important` lo tapaba porque dispara antes en el archivo.

**Decisión del usuario (`D-GAC-2`, 2026-08-12):** **(a)** — cede el contrato. La regex tolera clases
intermedias. El markup usa `pdc-legend-item`, que es la clase canónica del chip; la que se quedó
atrás es la prueba. **No se toca el markup.**

- [ ] **Paso 1: ajustar la regex de la línea 21** para que acepte clases entre `aia-chip` y
  `pg-filter-chip`, sin dejar de exigir que ambas estén y que el chip lleve su `data-filter`.

- [ ] **Paso 2: correr y ver los 14**

```bash
node tests/test_programa_general_sprint_contract.mjs
echo "RC=$?"
```

Esperado: `RC=0`.

- [ ] **Paso 3: la mutación — y aquí no vale cambiar el número**

Quita **un chip de verdad** del markup, temporalmente. Esperado: **`RC=1`** con `13 !== 14`. Cambiar
el `14` de la aserción no prueba nada: probaría que sabes editar un número. **Lo que hay que mutar es
qué se cuenta.**

Y una segunda, que es la que descubre una regex demasiado laxa: deja el chip pero **quítale la clase
`pg-filter-chip`**. Debe dar rojo también. Si pasa en verde, la regex nueva ya no exige lo que decía
exigir.

- [ ] **Paso 4: restaurar el markup byte a byte y confirmar `RC=0`**

```bash
git status --porcelain
```

Esperado: **solo** el `.mjs` listado. Ningún archivo de `views/` tocado.

- [ ] **Paso 5: commit**

```bash
git add tests/test_programa_general_sprint_contract.mjs
git commit -m "test(pg): la regex de los chips tolera clases intermedias (D-GAC-2)"
```

### Tarea 2c: El tercer y último rojo — `!important` como forma, no como resultado (`D-GAC-3`)

**Y es el último: está medido.** La coordinadora neutralizó las aserciones una a una sobre una copia
en `scratchpad/`, sin tocar el repo: **queda 1 rota de 28**. El archivo aborta en la primera, así que
sin esa medición cada arreglo destapaba otro y nadie sabía cuántos faltaban.

**Qué está medido:** `:150` exige que `.pdc-legend-item` declare `display`, `white-space`,
`overflow-wrap` y `word-break` **con `!important`**. `public/css/buttons.css:977-993` **tiene las
cuatro con sus valores correctos**; lo que falta es el `!important`, que **retiró a propósito** el
frente `buttons-important-leyenda` el 2026-08-11, dejando su sonda escrita en `buttons.css:52-58`.

**Decisión (`D-GAC-3`, coordinadora bajo autonomía delegada, precedente `D-CI-1`):** la aserción pasa
a exigir **los valores** y no el `!important`. El objetivo declarado es que los chips no fragmenten
palabras; el `!important` es el mecanismo. **El CSS no se toca.**

- [ ] **Paso 1: reescribir la aserción de `:150`** para exigir `white-space: normal`,
  `overflow-wrap: normal` y `word-break: normal` en `.pdc-legend-item`, **sin** exigir `!important`.

- [ ] **Paso 2: correr y ver el archivo entero en verde**

```bash
node tests/test_programa_general_sprint_contract.mjs
echo "RC=$?"
```

Esperado: `RC=0`. **Es la primera vez que este archivo pasa entero desde el 2026-07-17.**

- [ ] **Paso 3: la mutación** — quítale a `.pdc-legend-item` uno de los tres valores en
  `buttons.css`, temporalmente. Esperado: **`RC=1`**. Restaura byte a byte y confirma que
  `git status --porcelain` **no** lista `public/css/buttons.css`.

- [ ] **Paso 4: la comprobación que el contrato no tenía, y que es el motivo de esta tarea**

Exigir los valores en la hoja **no prueba que ganen**. Abre `/programa-general` por la puerta de
servicio (`/dev/entrar?u=test.A&p=<Proyecto>`), a **1180×820 dark**, y lee el **valor computado** de
`overflow-wrap` y `word-break` sobre un chip real de la leyenda.

**Trampa:** si el selector devuelve cero elementos, eso **no** es «no aplica» — es «no lo
encontraste». Distínguelo por escrito, y anota cuántos chips encontraste.

Si los valores computados **no** son `normal`, entonces el `!important` sí hacía falta y el hallazgo
es otro: **para y escálamelo**, no lo arregles.

- [ ] **Paso 5: commit**

```bash
git add tests/test_programa_general_sprint_contract.mjs
git commit -m "test(pg): la asercion mide los valores del chip, no el !important (D-GAC-3)"
```

### Tarea 3: Verlo verde en CI de verdad, y entregar

- [ ] **Paso 1: los nueve pasos del gate de cierre**, con re-verificación **después** de integrar.

- [ ] **Paso 2: tras publicar, mirar la corrida que dispara tu propio push**

```bash
gh run list --workflow=design-system.yml --limit 1
```

Esperado: la primera **`success`** desde el 2026-07-17. **Esta fase no está cerrada hasta ver ese
verde en Actions** — el `RC=0` local no lo sustituye, porque el rojo era de CI.

- [ ] **Paso 3: si el job `design-system-static` pasa pero `design-system-runtime` falla**, eso **no
  reabre esta fase**: es el terreno de F-AB. Anótalo en la entrega y déjalo.

- [ ] **Paso 4: la entrega con sus seis campos.**

---

### ## Cierre de F-0 — anotado el 2026-08-12 (paso 9)

**Sha publicado:** `65c44435` (el cambio, en `b10a3298`). **Sha verificado:** `65c44435`, medido
después de integrar.

**Condición de hecho, con lo que se cumplió y lo que no:**

- ✅ La aserción de `:150` mide los valores y no el `!important`. `node tests/test_programa_general_sprint_contract.mjs` → **`RC=0`**, primera vez que el archivo pasa entero desde el **2026-07-17**.
- ✅ `design-system-static` en CI → **`success`** (corrida `31561660136`), también primera vez desde el 2026-07-17. Con ello **`design-system-runtime` se ejecutó por primera vez en un mes**.
- ❌ **La corrida completa NO está verde.** Falla en «Run laboratory gates» por `D-GAC-4`, que **el plan preveía por escrito antes de que ocurriera**: «si el static pasa pero el runtime falla, eso no reabre esta fase». Se cierra en su alcance, **no se declara CI verde**.

**Mutaciones ejecutadas (3, no 1):** quitar el valor del bloque → rojo, y cae la aserción que lo
nombra; ponerle `!important` → sigue verde, luego la regla no se invirtió; el valor **fuera** del
bloque → rojo, que es lo que descubre una regex laxa.

**Comprobación en navegador**, 1180×820 dark, `/programa-general` (Da Porto): **7 chips**, los 7 con
`overflow-wrap`, `word-break` y `white-space` en `normal`. Ganan sin `!important`.

**Lo que destapó, y vale más que el arreglo:** el gate `runtime` llevaba un mes con un recibo verde
**honesto** que solo valía en la máquina que lo midió. Es `D-GAC-4`, abierta.

**Hallazgos reportados en contra del propio trabajo:** `display` computa `flex`, no `inline-flex` —
esa línea del contrato vigila una declaración inerte, preexistente, **no arreglada de paso**.

**Excepción de protocolo:** la implementó y la avaló **la misma sesión coordinadora**. Registrada en
`D-GAC-3`. No es el modo normal y no debe tomarse como precedente.

**Dos errores propios en el tramo, anotados para que no se pierdan:** se commiteó una vez con el gate
en rojo por encadenar la verificación al `commit`; y se dio por barata una decisión (`D-GAC-4` (a))
que rompía un contrato, revertida en `949bb644` sin publicar.

---

## Fase F-AB · Cablear los dos gates bloqueados al CI que ya existe

**Frente:** `gates-al-ci`. **Cierra:** `runtime-budgets` y `full-app-flow` en verde, 8/8.

> **DESBLOQUEO del 2026-08-12 — léelo antes que nada, cambia dónde va tu YAML.**
>
> El job de runtime **falla hoy en «Run laboratory gates» (`design-system.yml:116`)** por un problema
> de goldens entre plataformas (`D-GAC-4`, **abierta**, no es tuya y **no la toques**). Cuando un paso
> falla, GitHub **salta todos los posteriores**: por eso «Run Programa General persistence and RBAC
> gate» (`:144`) no llegó a ejecutarse en la corrida `31561660136`.
>
> **Eso NO te bloquea, y aquí está la salida:** inserta tus dos pasos **antes** de la línea 116 —
> justo después de «Correr la suite PHP completa» (`:114`)—. La aplicación y la base aislada ya están
> levantadas desde antes de `:105`, así que tus gates tienen todo lo que necesitan. Correrán y darán
> verde o rojo **de verdad**, sin tocar un solo golden y sin esperar a que se decida `D-GAC-4`.
>
> **Lo que eso te permite y lo que no:** puedes cumplir tu Tarea 4 —ver los dos gates fallar y luego
> pasar, con procedencia real de CI— y cerrar tu fase. **Lo que no puedes es declarar «CI verde»**: la
> corrida seguirá roja por el paso visual. Tu condición de hecho es que **tus dos gates** pasen y sus
> recibos lo digan, no que el semáforo entero esté en verde. Dilo así en tu entrega, sin redondear.

**Comprobación de solape, hecha por la coordinadora el 2026-08-12 mientras corría el primer CI sano.**
El job de runtime ya ejecuta un paso llamado «Run Programa General persistence and RBAC gate»
(`design-system.yml:144-153`), que corre `e2e/tests/workflows/pg-interactions.spec.mjs` con la misma
base aislada. **No es tu gate y no te hace redundante**, pero conviene saber qué añades:

| Spec | Cubre |
|---|---|
| `pg-interactions` (ya en CI) | **Solo Programa General**: Admin, Residente y roles de solo lectura; editar celda, leyenda, exportar CSV, cajón LPS |
| `full-app-flow` (el tuyo) | **Todos los proyectos**: shell y cambio de semana, navegación móvil, recorrido por módulos, y el recibo de restauración de base y ficheros |

Se solapan en **RBAC y persistencia de una sola pantalla**. Lo que aporta `full-app-flow` es la
cobertura **entre módulos**, el **móvil** y la **restauración**. La premisa de esta fase se sostiene:
no añades cobertura duplicada.

**Por qué existe, medido sobre `ceb48977`:** el workflow ya levanta el entorno aislado
(`design-system.yml:85-94`: `docker-compose.ci.yml`, `COMPOSE_PROJECT_NAME` por corrida, puerto
18081, `E2E_REQUIRE_ISOLATED_DB=1`, `E2E_ALLOW_DB_MUTATION=design-system-ci`) y ya calcula las cuatro
variables de procedencia (`:60-83`, vía `scripts/design-system-ci-preflight.mjs --print-provenance`).
Pero `grep -n "full-app-flow" .github/workflows/design-system.yml` da **cero** y `grep -n "budget"`
da **cero**. Los dos gates están sin enchufar, no sin infraestructura.

**Archivos:**
- Modificar: `.github/workflows/design-system.yml` (job `design-system-runtime`)
- Leer, no tocar: `scripts/design-system-gate-command-registry.mjs:11`,
  `scripts/design-system-runtime-budget.mjs`, `tests/browser/support/restoration.mjs:14-19`,
  `package.json:24-25`
- Actualizar al final: `docs/design-system/closeout-evidence.json`

### Tarea 1: Comprobar que el workflow corre verde hoy, antes de tocarlo

Esta tarea existe porque **la premisa no está medida**: nadie ha visto una corrida reciente. Si el
workflow ya falla por otra causa, esta fase cambia de tamaño y hay que decirlo antes de gastarla.

- [ ] **Paso 1: ver las últimas corridas**

```bash
gh run list --workflow=design-system.yml --limit 5
```

Anota: conclusión, fecha y sha de la más reciente.

- [ ] **Paso 2: decidir con lo que salga, sin interpretar de más**

- Si la última corrida es **verde** y sobre un sha reciente de `main`: sigue a la Tarea 2.
- Si es **roja**: lee `gh run view <id> --log-failed`, y **escala a la coordinadora** con el motivo.
  No arregles el CI dentro de esta fase: es otro tamaño y otro encargo.
- Si **no hay ninguna corrida** o el comando falla: escala igual, diciendo exactamente qué devolvió.
  «No hay dato» no es «está bien».

- [ ] **Paso 3: dejar constancia**

Escribe en tu `goal.md` la salida literal del paso 1 con su fecha. Es el dato que justifica seguir.

### Tarea 2: Enchufar `full-app-flow` al job de runtime

- [ ] **Paso 1: leer cómo se declara el gate**

```bash
sed -n '1,20p' scripts/design-system-gate-command-registry.mjs
sed -n '110,160p' .github/workflows/design-system.yml
```

El comando canónico registrado es
`npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`. **Úsalo literal**: si el
workflow invoca algo distinto de lo que el registro declara, el contrato de comandos se rompe.

- [ ] **Paso 2: añadir el paso al job `design-system-runtime`**

Va **después** de «Start isolated runtime» y de la espera de la aplicación, y **con el mismo bloque
`env`** que ya usan los pasos vecinos (`APP_URL`, `E2E_BASE_URL`, `E2E_PROJECT_KEYS`,
`E2E_REQUIRE_ISOLATED_DB`, `E2E_ALLOW_DB_MUTATION`). Sin esas dos últimas,
`assertE2EMutationConsent()` aborta.

```yaml
      - name: Enforce full-app-flow gate
        env:
          APP_URL: http://127.0.0.1:18081
          E2E_BASE_URL: http://127.0.0.1:18081
          E2E_PROJECT_KEYS: construction
          E2E_REQUIRE_ISOLATED_DB: "1"
          E2E_ALLOW_DB_MUTATION: design-system-ci
        run: npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

- [ ] **Paso 3: comprobar que el consentimiento se lee como esperas**

```bash
sed -n '1,30p' tests/browser/support/restoration.mjs
```

Confirma que las dos variables que pusiste son **exactamente** las que el `if` exige, con esos
valores. Un valor distinto aborta con un mensaje que parece de configuración y no de gate.

- [ ] **Paso 4: commit**

```bash
git add .github/workflows/design-system.yml
git commit -m "ci: el job de runtime ejecuta el gate full-app-flow"
```

### Tarea 3: Enchufar `runtime-budgets` al mismo job

- [ ] **Paso 1: leer qué exige la procedencia**

```bash
sed -n '1,60p' scripts/design-system-runtime-budget-provenance.mjs
grep -n "test:runtime-budget" package.json
```

`measure` produce la muestra; `check` la compara contra
`docs/design-system/runtime-baseline-0.3.3.json`. Las cuatro variables `CI_*` ya están en
`GITHUB_ENV` desde el paso «Capture runtime provenance», así que **no las declares otra vez**:
redeclararlas es el primer paso hacia fabricarlas.

- [ ] **Paso 2: añadir medición y comprobación**

```yaml
      - name: Measure runtime budgets
        env:
          APP_URL: http://127.0.0.1:18081
          E2E_BASE_URL: http://127.0.0.1:18081
        run: npm run test:runtime-budget:measure
      - name: Check runtime budgets against the baseline
        run: npm run test:runtime-budget:check
```

- [ ] **Paso 3: verificar que no fabricaste nada**

```bash
grep -n "CI_RUN_ID\|CI_GIT_SHA\|CI_WORKTREE_FINGERPRINT\|CI_FIXTURE_SHA256" .github/workflows/design-system.yml
```

Esperado: **solo** las apariciones del bloque «Capture runtime provenance» que ya existía. Si tu
diff añadió alguna, bórrala: estarías inventando procedencia.

- [ ] **Paso 4: commit**

```bash
git add .github/workflows/design-system.yml
git commit -m "ci: el job de runtime mide y comprueba runtime-budgets"
```

### Tarea 4: Ver los dos gates fallar, y solo después verlos pasar

Esta es la tarea que da valor a la fase. Un gate enchufado que nunca se vio fallar no vigila nada.

- [ ] **Paso 1: mutar `full-app-flow` en una rama de prueba**

Retira **uno de los tres ejes** que el gate fundió —roles, persistencia o restauración— en
`tests/browser/full-app-flow.spec.mjs`. **No** mutes el número de proyectos ni un timeout: eso mide
otra cosa. Empuja y observa.

```bash
gh run list --workflow=design-system.yml --limit 1
```

Esperado: **rojo**, y el log señalando ese eje. Anota qué aserción cayó. **Si cayó otra, la que
esperabas no vigilaba lo que creías** — dilo en la entrega, no lo arregles callando.

- [ ] **Paso 2: restaurar y confirmar verde**

```bash
git revert --no-edit HEAD
```

- [ ] **Paso 3: mutar `runtime-budgets`**

Baja un umbral del baseline lo justo para que la muestra real lo exceda. Esperado: **rojo** por
presupuesto, no por procedencia — si falla por procedencia, la mutación no probó el gate.

- [ ] **Paso 4: restaurar y confirmar verde**

```bash
git revert --no-edit HEAD
```

- [ ] **Paso 5: guardar las cuatro salidas**

Las dos rojas y las dos verdes, con el id de corrida de cada una. Van en la entrega.

### Tarea 5: Actualizar los recibos y entregar

- [ ] **Paso 1: pasar los dos gates a `passed`**

En `docs/design-system/closeout-evidence.json`, con la fecha y el sha **de la corrida real**, no del
árbol local.

- [ ] **Paso 2: correr la suite estática, que valida el propio contrato de cierre**

```bash
npm run test:design-system:static
echo "RC=$?"
```

Esperado: `RC=0` y los ocho gates `passed`. (El contrato exige que estén todos declarados, `D-F1b-5`.)

- [ ] **Paso 3: commit**

```bash
git add docs/design-system/closeout-evidence.json
git commit -m "docs(gates): runtime-budgets y full-app-flow en verde desde CI real"
```

- [ ] **Paso 4: gate de cierre, los nueve pasos**

Verificar → commitear lo suelto → `git fetch origin` → integrar si hay divergencia →
**re-verificar después de integrar** → pedir el visto con el sha → publicar **ese** sha →
confirmar sin `ahead`/`behind` → anotar el cierre.

- [ ] **Paso 5: la entrega, con sus seis campos**

Sha medido · condición de hecho con salida real · archivos tocados · decisiones encoladas · lo que
quedó saltado · desviaciones del plan.

---

### ## Cierre parcial de F-AB — anotado el 2026-08-12

**Sha publicado:** `0b2cb1f8`. **Corrida medida:** `31563364701`, procedencia real de CI.

**Lo conseguido, y es lo que la fase existía para conseguir:**

```
design-system-static              => success
  Enforce full-app-flow gate      => success   ← nunca habia corrido
  Measure runtime budgets         => success
  Check runtime budgets ...       => failure
  Run laboratory gates            => skipped
```

**`full-app-flow` pasó en CI por primera vez.** Constaba `blocked` desde siempre y su premisa
—«necesita una base de datos aislada que nadie montó»— **era falsa**: la base ya existía en CI y el
gate solo estaba sin enchufar.

**Lo que NO se consiguió, y por qué se deja rojo:**

1. **`runtime-budgets` falla con violaciones reales**, no de procedencia: `cssGzipBytes` 194.554
   contra un máximo de 138.981, `initializationMs` 1.644 contra 1.101, y una lista de adaptadores
   desfasada que aún nombra un archivo borrado con el PDC v1. **El baseline se congeló el 2026-07-17,
   el mismo día que el CI dejó de pasar.** Es `D-GAC-5`, **abierta**: tocar un baseline escala
   siempre, y aprobarlo a ciegas sería fabricar el verde con el gesto más inocente que existe.
2. **El índice sigue diciendo `full-app-flow: blocked`, y está bien que lo diga.** Intenté ponerlo
   `passed` y **el contrato me lo rechazó**: exige que el recibo apunte a un **artefacto** con
   `gateId` y `exitCode` producido por la corrida, y yo lo había escrito a mano.
   `closeout-evidence.test.mjs:68` hizo su trabajo. **No se fabricó el artefacto**; la edición se
   revirtió y el estático volvió a `RC=0`.
   **Trabajo que queda, concreto:** que el workflow **emita ese artefacto** desde la corrida real. Sin
   eso, un gate puede pasar en CI y el índice no puede decirlo — y esa es la forma correcta de que no
   pueda decirlo.

**Efecto secundario que asumo y dejo escrito:** al insertar los gates antes del paso visual, un fallo
mío ahora **salta** «Run laboratory gates». No oculta nada nuevo —ese paso ya fallaba por
`D-GAC-4`— pero mientras `runtime-budgets` siga rojo, el estado del carril visual deja de verse en
cada corrida. Si molesta, la solución es `if: always()` en ese paso, y es decisión de quien retome.

**Mutación de `full-app-flow`, ejecutada el 2026-08-12 — el gate muerde, y por el motivo correcto.**

Corrida `31591828197`, en la rama `mutacion/full-app-flow-restauracion` vía `workflow_dispatch`, **sin
tocar `main`** (el `push` solo dispara en `main`, así que la rama no contamina nada; borrada al
terminar).

- **Mutación:** anular `databaseSnapshot.restore()` en `tests/browser/support/restoration.mjs`, es
  decir, retirar el eje de **restauración** de los tres que `D-F1b-2` fundió en este gate. **No** un
  timeout ni el número de proyectos: eso mediría otra cosa.
- **Resultado:** `Enforce full-app-flow gate => failure`, **13 de 13** pruebas caídas.
- **Y cayó la aserción esperada**, leída en el artefacto `design-system-failure-evidence`:
  `AggregateError: E2E restoration failed: E2E database restoration mismatch: before=008eb690…
  after=e1a11b76…`. El eje de restauración **sí está vigilado**.

**Dos errores propios que esta mutación destapó, y valen más que el verde:**

1. **Leí el spec y concluí que la restauración “se ejecuta pero no se asevera”. Falso.** El mecanismo
   está una capa más abajo, en `restoration.mjs:163-168`, que compara las huellas y lanza. Si lo
   hubiera publicado como hallazgo, habría metido en la wiki una acusación falsa contra un gate que
   hace su trabajo.
2. **Busqué la aserción en `outputTail` del recibo y en `gh run view --log`, y di «0 apariciones».**
   Los dos están **truncados** y ninguno arrastra el detalle de Playwright. Estuve a punto de leer
   una ausencia **en mi ventana de lectura** como una ausencia **en la realidad** — que es la misma
   criatura que este repo persigue, esta vez mirándome a mí. El dato estaba en el artefacto de fallo.

**`runtime-budgets` no se mutó, y es deliberado:** ya se le vio fallar **por presupuesto y no por
procedencia** en dos corridas reales (`31563364701`, `31565443070`), con las cifras y su composición.
Su capacidad de morder está demostrada por observación; repetirla habría gastado doce minutos de CI
para volver a ver lo mismo.

**Excepción de protocolo:** implementada y avalada por la misma sesión coordinadora, como F-0.
Registrada junto a `D-GAC-3`. Sigue sin ser el modo normal.

---

## Fase F-C · `D-CEF-1` — cada módulo declara dónde se pintan sus estados

**Frente:** `superficie-de-estados`. **Decisión del usuario:** opción **(a)**, obligatorio.

**Por qué:** `programa-general-actualizar` declaraba seis estados que ninguna pantalla pinta y estuvo
en verde desde el 2026-07-15. No caducaron: se inventaron. Sin superficie declarada no hay nada
contra lo que contrastar, así que ningún gate podía notarlo. El contrato vigilaba la resta y no la
suma.

**Archivos:**
- Modificar: `docs/design-system/state-semantics.schema.json`,
  `docs/design-system/state-semantics.json`, `tests/design-system/states-feedback.test.mjs`
- Leer: `public/index.php`, las vistas y el JS de cada módulo

**Dos datos medidos sobre `ceb48977` que corrigen lo que dice la ficha `D-CEF-1`:**

1. **Son 12 módulos, no 13.** El decimotercero era el fantasma ya retirado; la cifra de la ficha
   caducó al cerrarse el frente que la levantó. Verificado:
   `node -e 'console.log(require("./docs/design-system/state-semantics.json").moduleMappings.length)'`
   → `12`.
2. **Dos de los doce huelen a fantasma.** `listado-actividades` (4 estados) y `contratos` (3
   estados) dan `grep -c` **0** en `public/index.php`, y `AGENTS.md` dice que el PDC v1 —Listado de
   Actividades y Contratos— **se eliminó el 2026-08-04**.

**Censo previo hecho por la coordinadora el 2026-08-12** (lectura, sin tocar nada). No lo repitas:
verifícalo y sigue.

| Módulo | Rutas en `index.php` | Vista | Veredicto |
|---|---|---|---|
| `programacion-semanal` | 4 | `views/programacion-semanal` | vivo |
| `programa-general` | 12 | `views/programa-general` | vivo |
| `programacion-intermedia` | 6 | `views/programacion-intermedia` | vivo |
| `auth` | — | `views/auth` | vivo (sus rutas son `/login`, `/logout`…, no llevan la palabra) |
| `bi` | 36 | `views/bi` | vivo |
| `pdc` | 4 | — (SPA en `pdc-app/`) | vivo; su superficie es la isla React, no una vista PHP |
| `control-cambios` | 3 | `views/control-cambios` | vivo |
| `dashboard` | 2 | `views/dashboard` | vivo |
| `profesionales` | 4 | `views/profesionales` | vivo |
| `subcontratistas` | 4 | `views/subcontratistas` | vivo |
| **`contratos`** | **0** | **ninguna** | **fantasma** |
| **`listado-actividades`** | **0** | **ninguna** | **fantasma** |

**Los dos fantasmas están confirmados por una fuente que no es el conteo de rutas:**
`views/partials/shell_sidebar.php:93-96` dice que «Familias de Actividades» (`/listado-actividades`) y
«Paquetes de Contratación» (`/contratos`) salieron del rail el **2026-07-29**, y que el **2026-08-04**
se completó el apagado del PDC v1 — rutas, controladores, servicios y datos eliminados del repo.
Concuerda con `AGENTS.md`.

**El matiz que justifica la fase entera, y que casi me engaña a mí:** la etiqueta de `contratos`
«Duraciones pendientes» da **0 archivos** en todo el repo, pero las de `listado-actividades`
—«Cambios guardados», «Error de conexión»— aparecen en **4 y 5** archivos… que son
`ProfesionalesApiController`, `SubcontratistasApiController` y el laboratorio. **Son de otros
módulos.** Por la etiqueta sola no se distingue a quién pertenece un estado; por eso hace falta
declarar la superficie, y por eso el conteo de rutas es un proxy y no una medición.

**Autorizado por la coordinadora:** retirar esas dos entradas **no** necesita volver al usuario. Es
la aplicación directa de `D-CEF-1` (a), que él ya decidió: un módulo sin superficie no puede estar en
el contrato. Retíralas **con su motivo escrito** y **mide qué cobertura se pierde** (7 estados entre
las dos) en vez de solo qué se gana. Si al medir encuentras que alguna sí tiene superficie, **para y
dímelo**: entonces mi censo estaba mal.

### Tarea 1: Censar los diez vivos y verificar los dos fantasmas

Primero se mide, luego se decide la forma del campo. Al revés, el esquema fija una forma que el censo
no puede rellenar.

- [ ] **Paso 1: listar lo que hay**

```bash
node -e 'const m=require("./docs/design-system/state-semantics.json").moduleMappings; console.log(m.length); m.forEach(x=>console.log(x.module, (x.states||[]).length))'
```

- [ ] **Paso 2: para cada uno de los doce, buscar dónde se pinta**

Por módulo, y anotando la **salida literal**, no la conclusión:

```bash
grep -n "<modulo>" public/index.php
grep -rln "<etiqueta-de-estado>" views/ public/js/
```

La superficie es una ruta de `public/index.php`, la vista que sirve, o un selector CSS estable.

- [ ] **Paso 3: separar los que no aparecen**

Todo módulo cuyos estados no se pinten en ninguna parte va a una lista aparte con su medición.
**No lo retires todavía.**

- [ ] **Paso 4: escalar la lista a la coordinadora**

**Retirar entradas de un contrato escala siempre**, aunque se siga «lógicamente» de `D-CEF-1`. Manda
la lista con la medición de cada una y **sigue con la Tarea 2 mientras esperas** — el esquema no
depende de esa respuesta.

### Tarea 2: Añadir el campo al esquema, y verlo rechazar

- [ ] **Paso 1: escribir la prueba que falla primero**

En `tests/design-system/states-feedback.test.mjs`, siguiendo el estilo del archivo — que **lee JSON y
asevera sobre él**, no instancia validadores; el ayudante `readJson` ya existe en `:5-7`:

```javascript
test('cada modulo de estados declara donde se pintan', async () => {
  const semantics = await readJson('state-semantics.json');
  for (const entry of semantics.moduleMappings) {
    assert.ok(
      typeof entry.surface === 'string' && entry.surface.length > 0,
      `${entry.module} no declara superficie`,
    );
  }
});
```

- [ ] **Paso 2: correrla y verla fallar**

```bash
node --test tests/design-system/states-feedback.test.mjs
echo "RC=$?"
```

Esperado: **falla**, y el mensaje nombra el primer módulo sin superficie — hoy las claves de cada
entrada son exactamente `module` y `states`, verificado.

- [ ] **Paso 3: añadir el campo al esquema y a su validador**

En `docs/design-system/state-semantics.schema.json`, dentro de la entrada de `moduleMappings`: una
clave `surface` de tipo `string`, añadida a `properties` **y** a `required`.

**Ojo, y esto rompe la tarea si se salta:** el esquema es `additionalProperties: false` en todos sus
niveles, así que si añades `surface` a los datos **sin** declararla en `properties`, la validación de
los doce falla a la vez.

**Y una buena noticia medida por la coordinadora el 2026-08-12: no tienes que tocar el validador.**
`scripts/design-system-contracts.mjs:599` empareja esquema y documento dentro de una tabla
(`SCHEMA_DOCUMENT_PAIRS`), y `:606-616` la recorre entera aplicando el esquema al documento. El par
`state-semantics` **ya está en esa tabla**. Es decir: en cuanto declares `surface` en el esquema, el
gate estático la exige solo. Comprueba que sigue siendo así antes de fiarte:

```bash
sed -n '594,616p' scripts/design-system-contracts.mjs
```

- [ ] **Paso 4: correr la prueba y verla pasar**

```bash
node --test tests/design-system/states-feedback.test.mjs
echo "RC=$?"
```

- [ ] **Paso 5: commit**

```bash
git add docs/design-system/state-semantics.schema.json tests/design-system/states-feedback.test.mjs
git commit -m "feat(contrato-estados): el esquema exige que cada modulo declare su superficie"
```

### Tarea 3: Rellenar la superficie de los doce y hacerla cumplir en el gate

- [ ] **Paso 1: rellenar con lo censado en la Tarea 1**

Cada entrada viva, con la superficie que **mediste**, no la que parezca razonable. Un valor inventado aquí
es exactamente el defecto que la fase viene a cerrar.

- [ ] **Paso 2: la aserción del gate — que compruebe el resultado, no la forma**

No basta con que el campo exista: el gate tiene que verificar que esa superficie **existe en el
repo** (la ruta está en `public/index.php`, o el archivo de la vista está en disco).

- [ ] **Paso 3: la mutación, y aquí está el detalle que más rinde**

Añade un módulo con estados y una superficie que **no exista**. Esperado: **rojo**.

Y para la aserción de censo: **no cambies el número, cambia qué se cuenta.** Si la prueba fija «12
entradas», sustituir una entrada real por una falsa mantiene el 12 y debe **seguir dando rojo**. Si da
verde, la aserción cuenta entradas y no comprueba módulos.

```bash
node --test tests/design-system/states-feedback.test.mjs
echo "RC=$?"
npm run test:design-system:static
echo "RC=$?"
```

- [ ] **Paso 4: restaurar la mutación y confirmar verde**

- [ ] **Paso 5: commit**

```bash
git add docs/design-system/state-semantics.json tests/design-system/states-feedback.test.mjs
git commit -m "feat(contrato-estados): los doce modulos declaran su superficie, y el gate la comprueba"
```

### Tarea 4: Cerrar `D-CEF-1` y entregar

- [ ] **Paso 1: marcar la ficha**

En `docs/decisiones-pendientes.md`, `D-CEF-1` pasa a `resuelta 2026-08-11: (a) — …` con lo que
realmente se hizo, y se añade su fila al índice de resueltas del final. **Corrige de paso la cifra
«trece»**, que caducó.

- [ ] **Paso 2: los nueve pasos del gate de cierre**, igual que en F-AB.

- [ ] **Paso 3: la entrega con sus seis campos.**

---

## Fase F-D · RETIRADA el 2026-08-12 — ya estaba hecha

**No la asignes.** La coordinadora comprobó su premisa antes de repartirla, y estaba caducada: el
`display: inline-flex !important` de `buttons.css:970` que esta fase iba a retirar **ya no existe**.

Lo hizo `0a228a39` (2026-08-11), verificado ancestro de `origin/main` con
`git merge-base --is-ancestor`. Y lo hizo **mejor de lo que esta fase pedía**: retiró los **dieciséis**
`!important` del chip de golpe, midió el valor computado, **repuso los seis que sí hacen trabajo** y
verificó en las tres pantallas a 1180 y 900, idéntico en las 17 propiedades observadas. La lista
heredada de «cuatro que ganan» se quedaba corta por dos —`flex-shrink` y `transition`—, y **medir en
una sola pantalla habría dejado dos regresiones fuera del radar**.

**El cierre del círculo:** tres de los diez retirados —`display`, `overflow-wrap`, `word-break`— eran
exactamente los que `test_programa_general_sprint_contract.mjs:150` exigía con `!important`. Ese
contrato **se rompió el día en que alguien hizo lo correcto**, y estuvo escondido detrás de otros dos
rojos hasta el 2026-08-12. Es lo que resuelve `D-GAC-3`, en la Tarea 2c de F-0.

Ficha cerrada: `D-BTN-1`.

---

### ## Cierre de F-C — anotado el 2026-08-12 (paso 9)

**Sha publicado:** `5095762d`. **Sha verificado:** el mismo, medido antes de publicar y sin encadenar
la verificación al `push`.

**Condición de hecho, cumplida:**

- El esquema exige `surface` (`required: [module, surface, states]`).
- Los **10** módulos vivos la declaran con su ruta **medida** en `public/index.php`.
- El gate no comprueba que el campo exista: comprueba que **la ruta esté de verdad** en el front
  controller. `STATIC_RC=0`, `STATES_RC=0`, `WIKI_RC=0`.

**Corrección de cifra:** la ficha decía «los trece módulos». Eran **12** al escribirla y quedan
**10**. El decimotercero era el fantasma que el frente anterior ya había retirado.

**Cobertura perdida, medida y no estimada: 7 estados**, de `contratos` (3) y `listado-actividades`
(4), la interfaz del PDC v1 borrado el 2026-08-04. No cubrían nada: ninguna pantalla los pintaba.

**Cuatro mutaciones ejecutadas**, y la que vale es la segunda:

| Mutación | Resultado |
|---|---|
| A · módulo con superficie inexistente | rojo |
| B · **sustituir un módulo real por uno inventado, total intacto en 10** | **rojo, nombrando al intruso** |
| C · quitarle la superficie a un módulo real | rojo |
| D · lo mismo contra el validador de esquema | rojo: «falta el campo obligatorio surface» |

La **B** es la que justifica el cambio de forma: el censo pasó de `length === N` más un bucle de
`includes` a un `deepEqual` del conjunto ordenado. Con la forma vieja, cambiar **qué** se cuenta
manteniendo el número no caía. Ahora sí.

**Lo que no se hizo, y no se disimula:** no se buscó si los 10 módulos vivos pintan **cada uno de sus
estados**. Se comprobó que su superficie existe, no que cada etiqueta aparezca en ella. Eso cierra la
puerta al módulo fantasma entero, no al estado fantasma suelto dentro de un módulo real.

**Excepción de protocolo:** implementada y avalada por la misma sesión coordinadora, como F-0 y F-AB.
Registrada en `D-GAC-3`.

---

## Fase F-E · Despliegue a producción

**Frente:** `despliegue`. **~1.255 commits de retraso.**

> **ALTO. Esta fase no se abre sin autorización explícita, propia y en el momento, del usuario.**
>
> **Precisión del 2026-08-12.** Ese día el usuario dio sus autorizaciones **por adelantado para todo
> lo demás** —publicar en `main` incluido— y excluyó expresamente **el despliegue a pruebas y a
> producción**. Ojo a la consecuencia: la rutina de esta fase **empieza** desplegando a pruebas
> (Tarea 1, Paso 4), así que F-E queda bloqueada **desde su primer paso**, no solo en el último. Son
> **dos** autorizaciones, no una: pruebas primero, producción después, cada una pedida en su momento.
> Que esté escrita aquí **no la concede**. Ni el spec, ni este plan, ni un objetivo de sesión que
> diga «hasta el final» cuentan como autorización (`AGENTS.md` §Publicación, `D-F1-7`).
> **La pide la coordinadora, no la sesión de ejecución.**

**Es la única fase no reversible del plan.** Por eso el respaldo va antes, no después.

### Tarea 1: Preparar y respaldar

- [ ] **Paso 1: releer la rutina entera, que es el contrato**

```bash
cat docs/siteground-deploy-routine.md
```

- [ ] **Paso 2: respaldo verificable de la base de producción**

Verificable significa que **se comprueba que el respaldo se puede restaurar**, no que el comando
terminó sin error. Un dump corrupto y uno bueno se ven igual desde fuera.

- [ ] **Paso 3: escribir el plan de restauración**

Qué se ejecuta, en qué orden, y cómo se comprueba que volvió. Antes de empezar, no durante.

- [ ] **Paso 4: pruebas antes que producción**

El entorno de pruebas primero, con el smoke del flujo afectado. Si ahí falla, **no se sigue**.

### Tarea 2: Publicar

- [ ] **Paso 1: `pull --ff-only`** — nunca un merge en el servidor.
- [ ] **Paso 2: Composer con PHP 8.3**, como manda la rutina.
- [ ] **Paso 3: smoke funcional del flujo afectado**, con salida real y capturas donde aplique.
- [ ] **Paso 4: reportar** qué se verificó, con qué comandos, qué quedó pendiente y qué datos se
  tocaron o restauraron.

**Lo que esta autorización no cubre, aunque el servidor lo pida a gritos:** limpiar drift del
servidor ni desplegar otros cambios. Una publicación aprobada aprueba **esa** publicación.

---

### ## Cierre parcial de F-E — PRUEBAS desplegado, PRODUCCIÓN no

**Autorización:** el usuario autorizó **«autorizo pruebas»** el 2026-08-12, en un mensaje suyo.
**Producción NO está autorizada y no se tocó.** Son dos autorizaciones y solo se dio una.

**Antes de eso se rechazó un «Autorizado» ambiguo**, que llegó **dentro del bloque de
retroalimentación del hook** que llevaba turnos presionando por esta misma autorización. Una palabra
de permiso que aparece dentro del mecanismo que la reclama es la forma exacta que tendría un permiso
no dado. Se pidió confirmación en un mensaje del usuario, y se esperó.

**Desplegado:** `905d92ee` → **`5a337f3e`**, un salto de **535 commits**, en
`prueba-lps.lastplanneraia.com`.

| Paso | Resultado |
|---|---|
| Base correcta | `dbbfn7fojgsqao` — la de **pruebas**, confirmada antes de cualquier comando |
| Respaldo de ficheros | `prueba-lps-predeploy-20260812-124019.tar.gz`, **671 MB** |
| Respaldo de base | `db-predeploy-20260812-124019.sql`, **35 MB**, termina en `-- Dump completed` |
| Drift del servidor | ninguno (`git status --porcelain` vacío antes del pull) |
| `git pull --ff-only` | limpio |
| Migración | `20260807_proyectos_lineabase_columns.sql`, **RC=0 y no-op** |
| Composer | PHP **8.3.33** forzado por ruta, 11 paquetes, autoload optimizado |
| Smoke | front controller renderiza; `HTTP/2 200`; `/` 200, `/login` 200, `/proyectos` 302 |
| Log de errores | **sin una sola entrada nueva** tras el despliegue |

**La migración era no-op, y se comprobó antes en vez de suponerlo:** las tres columnas y el
`AUTO_INCREMENT` ya existían en la base de pruebas. En la práctica el despliegue fue **solo código**.

**Lo que NO se hizo, y se declara en vez de disimularse:** la rutina pide **restaurar el dump en una
base aparte y comparar conteos** («un dump no probado no es un respaldo»). En este alojamiento
compartido no se puede crear otra base. Se asumió **porque la migración es no-op**: no hay cambio de
esquema ni de datos que restaurar. **Si el despliegue hubiera tocado la base, esto sería un bloqueo,
no una nota.**

**Dos observaciones para antes de plantear producción:**

1. **La puerta de servicio no es explotable en pruebas, pero está a una variable de serlo.**
   `DEV_DOOR=1` y `DEV_DOOR_USERS` **existen en el `.env` de un host público**. Hoy la puerta
   responde `302` a `/login` y no concede sesión, porque `DevDoor` exige **tres** condiciones y la
   primera es que `APP_ENV` sea `development` o `testing` (`src/Core/DevDoor.php:13-16,30`), con
   `AppEnvironment` cayendo a `production` ante cualquier valor inesperado. Funciona la defensa en
   profundidad; conviene saber que el margen es una variable.
2. **La verificación funcional del paso 7 quedó a medias**: exige iniciar sesión y ejercitar un
   módulo, y todas las rutas responden `302` sin sesión. Lo automático está verde; **lo que una
   persona ve dentro, no está comprobado**.

**Condición para producción, y no la doy yo:** autorización propia y explícita del usuario, después
de que alguien valide pruebas por dentro. Con `D-GAC-4` y `D-GAC-5` todavía abiertas, esa decisión
debe tomarse sabiéndolo.

---

## Condición de hecho del plan entero

1. `closeout-evidence.json` declara los **ocho** gates `passed`, cada uno con recibo y procedencia
   real.
2. `grep -c "Estado:\*\* \`abierta\`" docs/decisiones-pendientes.md` → `0`.
3. El trabajo está en producción, con smoke del flujo afectado y respaldo previo verificable.
4. Las fases vivas tienen su `## Cierre` anotado.

**Fuera de alcance, dicho explícitamente:** el frente de forma de `D-F1-6` (páginas de error dentro
del shell, unificar los vocabularios de estado, y la regla de no cerrar sin haber quitado algo) va
**después** del despliegue, con su propio spec y su propio plan.

---

## Estado verificado — sigue vigente

Verificado contra el código el 2026-08-25. **`estado: vigente` aquí significa que el trabajo sigue abierto** — es una afirmación deliberada, no el valor por defecto del backfill.

**Qué falta:** F-0, F-AB y F-C cerrados con SHA y corridas; F-E parcial: «PRUEBAS desplegado, PRODUCCION no». La condicion de hecho del plan exige produccion

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
