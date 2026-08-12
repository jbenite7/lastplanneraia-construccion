# Plan de cierre hasta producción

> **Para sesiones de ejecución:** SUB-SKILL OBLIGATORIA — usa `superpowers:executing-plans` para
> ejecutar este plan tarea por tarea. Los pasos usan casillas (`- [ ]`).
>
> **Este plan lo reparte la coordinadora, una fase a la vez.** No encadenes fases: haz la que te
> asignaron y entrégala (`D-F1-7`). Un objetivo redactado como «termina el plan» no amplía el encargo.

**Objetivo:** llevar el repositorio a ocho gates verdes, cola de decisiones vacía y el trabajo
publicado en producción.

**Spec:** [2026-08-11-plan-cierre-hasta-produccion-design.md](../specs/2026-08-11-plan-cierre-hasta-produccion-design.md)

**Arquitectura:** cuatro fases, cada una **un frente** con worktree propio, ciclo de visto y el gate
de nueve pasos de `AGENTS.md` §Publicación. Ninguna empieza hasta que la anterior está publicada **y
anotada** (paso 9). El orden no es estético: F-AB deja los ocho gates verdes, y solo entonces el
verde de F-C y F-D significa algo.

**Stack tocado:** GitHub Actions + Docker Compose (F-AB), JSON Schema + Node test runner (F-C), CSS
en capas (F-D), rutina de SiteGround (F-E).

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

## Fase F-AB · Cablear los dos gates bloqueados al CI que ya existe

**Frente:** `gates-al-ci`. **Cierra:** `runtime-budgets` y `full-app-flow` en verde, 8/8.

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

### Tarea 1: Censar los doce antes de tocar el esquema

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
los doce falla a la vez. Quien valida el esquema es `scripts/design-system-contracts.mjs` — léelo
antes de tocar nada:

```bash
grep -n "state-semantics" scripts/design-system-contracts.mjs
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

Cada entrada, con la superficie que **mediste**, no la que parezca razonable. Un valor inventado aquí
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

## Fase F-D · `D-BTN-1` — la resta del `!important`

**Frente:** `important-que-no-gana`. **Decisión del usuario:** opción **(2)**, investigar y retirar
los dos si procede.

**Qué está medido** (sobre `f1f5bd87`, 1180×820 dark): `public/css/buttons.css:970` declara
`display: inline-flex !important` para `.pdc-legend-item`, y el valor **computado** en Programa
General, Intermedia y Semanal es `flex`. Ese `!important` no le gana a nadie.

**Riesgo declarado:** entra en la cascada de capas (`memoria/trampas/css-layer-cascade.md`), donde
para `!important` el orden de capas **se invierte**. El frente que lo levantó excluyó esa zona a
propósito. Se mide, no se supone.

**Archivos:** `public/css/buttons.css` y la hoja de quien lo pise.

### Tarea 1: Medir el estado de partida antes de tocar nada

- [ ] **Paso 1: abrir sesión por la puerta de servicio**

```
http://localhost:8081/dev/entrar?u=test.A&p=<Proyecto_Proceso>
```

Nunca por `/login`.

- [ ] **Paso 2: leer el valor computado en las tres pantallas**

A 1180×820, tema dark, en `/programa-general`, `/programacion-intermedia` y
`/programacion-semanal`, sobre un `.pdc-legend-item` real. Anota el valor y **cuántos elementos
encontraste**.

**Trampa medida:** si el selector devuelve cero elementos, eso **no** es «no se aplica» — es «no lo
encontraste». Distínguelo por escrito. Igual que `elementFromPoint` fuera del viewport devuelve
`null`, que no significa «está tapado».

- [ ] **Paso 3: averiguar qué regla gana**

Usa la lista de reglas coincidentes del navegador, no la deducción. Anota el archivo, la línea y la
capa de la ganadora.

- [ ] **Paso 4: dejarlo escrito antes de cambiar nada**

Es la línea base contra la que se compara después.

### Tarea 2: Retirar lo que sobre, con la salida honesta ya prevista

- [ ] **Paso 1: decidir con lo medido, no con lo esperado**

- Si la regla ganadora **existe solo para vencer a este `!important`**: se retiran **las dos**.
- Si la ganadora **hace falta por sí misma**: se retira **solo la declaración muerta** de
  `buttons.css:970` y **se escribe por qué** junto a la regla. Eso **no** incumple la decisión del
  usuario: es lo que la medición permite. Dilo en la entrega.
- Si la medición no distingue los dos casos: **escala a la coordinadora.** No elijas el conservador y
  lo anotes como duda — anotar no es consultar.

- [ ] **Paso 2: aplicar el cambio**

- [ ] **Paso 3: volver a medir las tres pantallas**

Mismo viewport, mismo tema, mismo selector. Esperado: **el mismo valor computado que en la Tarea 1**.
Cualquier diferencia es una regresión visual, y esas necesitan aprobación explícita.

- [ ] **Paso 4: la suite que cubre esta zona**

```bash
npm run test:design-system:static
echo "RC=$?"
```

- [ ] **Paso 5: commit**

```bash
git add public/css/buttons.css
git commit -m "refactor(css): retira el !important de .pdc-legend-item, que no ganaba a nadie"
```

### Tarea 3: Cerrar `D-BTN-1` y entregar

- [ ] **Paso 1: marcar la ficha** en `docs/decisiones-pendientes.md` con lo que realmente se hizo, y
  añadir su fila al índice de resueltas.

- [ ] **Paso 2: comprobar que la cola queda vacía**

```bash
grep -c "Estado:\*\* \`abierta\`" docs/decisiones-pendientes.md
```

Esperado: `0`.

- [ ] **Paso 3: los nueve pasos del gate de cierre, y la entrega con sus seis campos.**

---

## Fase F-E · Despliegue a producción

**Frente:** `despliegue`. **~1.255 commits de retraso.**

> **ALTO. Esta fase no se abre sin autorización explícita, propia y en el momento, del usuario.**
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

## Condición de hecho del plan entero

1. `closeout-evidence.json` declara los **ocho** gates `passed`, cada uno con recibo y procedencia
   real.
2. `grep -c "Estado:\*\* \`abierta\`" docs/decisiones-pendientes.md` → `0`.
3. El trabajo está en producción, con smoke del flujo afectado y respaldo previo verificable.
4. Las cuatro fases tienen su `## Cierre` anotado.

**Fuera de alcance, dicho explícitamente:** el frente de forma de `D-F1-6` (páginas de error dentro
del shell, unificar los vocabularios de estado, y la regla de no cerrar sin haber quitado algo) va
**después** del despliegue, con su propio spec y su propio plan.
