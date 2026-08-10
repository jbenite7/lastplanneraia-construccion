# F1 — Destrabar el viewport móvil: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que `390x844` vuelva a ser un viewport **permitido** en los esquemas, contratos y gates del design system, sin que ninguna familia lo exija todavía y sin generar evidencia nueva.

**Architecture:** Hoy «permitido» y «exigido» son el mismo conjunto en cinco sitios. F1 los separa en dos constantes (`SUPPORTED_VIEWPORTS` / `REQUIRED_VIEWPORTS`) y convierte las comparaciones de igualdad exacta en dos reglas: *contiene lo requerido* y *no contiene nada fuera de lo soportado*. El candado que prohibía el viewport se reescribe para exigir evidencia en vez de prohibir el ancho.

**Tech Stack:** Node 20+ (`node --test`), JSON Schema, scripts ES module en `scripts/`. Sin Docker: F1 no toca PHP.

**Spec:** [`docs/superpowers/specs/2026-08-07-reapertura-movil-y-tema-claro-design.md`](../specs/2026-08-07-reapertura-movil-y-tema-claro-design.md)

## Global Constraints

- Viewport móvil canónico: **`390x844`**. Viewports desktop requeridos: **`1180x820`** y **`1440x900`** (en ese orden).
- Tema único en F1: **`dark`**. F1 no toca temas — eso es F3.
- **Cero cambio visual.** No se crea, regenera ni borra ningún golden. No se toca `public/css`, `views/`, ni `pdc-app/`.
- **No se toca** `homologation.json` ni `family-approvals.json`: las familias siguen declarando los dos viewports desktop y las aprobaciones firmadas quedan intactas.
- Comando de cierre de cada tarea: `npm run test:design-system:static` (ocho puertas, todas en verde).
- Los gates deben seguir ejerciendo **exactamente 20 escenarios** desktop al terminar F1.
- **Commits:** `AGENTS.md` §Publicación prohíbe commitear sin petición explícita. Los pasos de commit de este plan solo se ejecutan si Felipe lo autoriza; si no, se dejan los cambios en el árbol y se informa.
- Las actas no se reescriben: DS-031 no se edita, se supersede con DS-032.

## File Structure

| Archivo | Responsabilidad tras F1 |
|---|---|
| `scripts/design-system-contracts.mjs` | Gate de contratos. Pasa a distinguir soportado/requerido y a validar los viewports declarados por familia (hoy no los mira). |
| `scripts/design-system-runtime-budget.mjs` | Validación de artefactos de presupuesto. Acepta el viewport móvil. |
| `scripts/design-system-runtime-budget-provenance.mjs` | Procedencia de las muestras. Acepta el viewport móvil. |
| `docs/design-system/runtime-budget.schema.json` | Enum de viewport permitido. |
| `docs/design-system/family-approvals.schema.json` | Enum de viewports permitidos en una aprobación. |
| `tests/design-system/mobile-viewport-scope.test.mjs` | **Nuevo** (renombra `mobile-viewport-removal.test.mjs`). Candado de alcance: exige evidencia, ya no prohíbe el ancho. |
| `tests/design-system/contracts.test.mjs` | Suma las fixtures negativas y positiva del viewport móvil. |
| `docs/design-system/contracts/module-migration.md` | Documenta la distinción permitido/exigido. |
| `docs/design-system/decisions.md` | DS-032. |

---

### Task 1: Separar «soportado» de «requerido» en el gate de contratos

Este es el corazón de F1. Incluye un hallazgo de la exploración: `homologation.families[].viewports` **no lo valida el gate en ningún punto** — solo lo miraban `contracts.test.mjs` y el candado. Al relajar el candado hay que cerrar ese hueco en el gate, o «permitido pero no exigido» quedaría sin control ejecutable.

**Files:**
- Modify: `scripts/design-system-contracts.mjs:165-170`, `scripts/design-system-contracts.mjs:332-353`
- Test: `tests/design-system/contracts.test.mjs`

**Interfaces:**
- Produces: dos constantes de módulo en `design-system-contracts.mjs` — `SUPPORTED_VIEWPORTS` (`Set<string>`, incluye `'390x844'`) y `REQUIRED_VIEWPORTS` (`string[]`, `['1180x820','1440x900']`). Las tareas 2 y 3 usan esos mismos nombres.
- Consumes: el harness `runFixture(mutate)` que ya existe en `tests/design-system/contracts.test.mjs:16-53`. Copia `docs/design-system` a un directorio temporal, aplica `mutate(fixtureRoot)`, corre el gate ahí y devuelve el `spawnSync` result.

- [ ] **Step 1: Escribir las tres pruebas que fallan**

Añadir al final de `tests/design-system/contracts.test.mjs`:

> **Corregido tras la ejecución (2026-08-07).** La primera versión de esta prueba
> asertaba `result.status === 0` y era **imposible de pasar**: `runFixture` corre en un
> directorio temporal sin `.git`, así que el gate falla ahí siempre con 36 errores de
> `sourceRef must resolve to a Git commit`, ajenos a viewports. Lo que sigue es la
> comparación diferencial que se implementó de verdad. Consecuencia más amplia, anotada
> como precondición de F2: **ninguna** prueba de `runFixture` puede comprobar un caso
> positivo hoy, y por eso todas las existentes solo asertan `notEqual(status, 0)`.

```javascript
// Comparacion diferencial en vez de `status 0`: el fixture corre en un directorio
// temporal sin `.git`, asi que el gate siempre falla ahi. Lo que importa es que
// declarar 390x844 no anada ni un fallo respecto de la linea base.
test('declarar el viewport movil no anade ningun fallo al gate', () => {
  const baseline = runFixture(() => {});
  const withMobile = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    const foundations = contract.families.find(({ id }) => id === 'foundations');
    foundations.viewports = ['1180x820', '1440x900', '390x844'];
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  const failures = (result) => (result.stderr || '')
    .split('\n')
    .filter((line) => line.startsWith('- '));

  assert.deepEqual(failures(withMobile), failures(baseline));
  assert.equal(failures(baseline).some((line) => line.includes('390x844')), false);
});

test('una familia no puede declarar un viewport no soportado', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    const foundations = contract.families.find(({ id }) => id === 'foundations');
    foundations.viewports = ['1180x820', '1440x900', '800x600'];
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /foundations: unsupported viewport 800x600/);
});

test('una familia no puede dejar de declarar un viewport requerido', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    const foundations = contract.families.find(({ id }) => id === 'foundations');
    foundations.viewports = ['1180x820'];
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /foundations: missing required viewport 1440x900/);
});
```

- [ ] **Step 2: Correr las pruebas y verificar que fallan**

```bash
node --test tests/design-system/contracts.test.mjs
```

Esperado: las tres fallan. La primera porque el enum de `family-approvals.schema.json` todavía no admite `390x844` **no** interviene aquí, así que si la primera pasa ya, anótalo y sigue: significa que `homologation` no estaba validado, que es justo el hueco que esta tarea cierra. Las dos negativas fallan seguro, porque hoy el gate no emite esos mensajes.

- [ ] **Step 3: Separar las constantes en el gate**

En `scripts/design-system-contracts.mjs`, sustituir el bloque de la línea 332-334:

```javascript
// Viewports soportados: el conjunto que el sistema acepta. Requeridos: el que
// toda familia debe cubrir con evidencia. Se separaron en F1 (DS-032) para
// reabrir el ancho movil sin exigir goldens que aun no existen; DS-031 los
// habia fundido en uno solo.
const SUPPORTED_VIEWPORTS = new Set(['1180x820', '1440x900', '390x844']);
const REQUIRED_VIEWPORTS = ['1180x820', '1440x900'];
```

Y cambiar los dos bucles de cobertura (líneas ~340 y ~348) para que iteren sobre `REQUIRED_VIEWPORTS` en vez de sobre el Set:

```javascript
  for (const viewport of REQUIRED_VIEWPORTS) {
    if (!keys.has(`dark/${viewport}`)) {
      failures.push(`laboratory: missing scenario ${familyId}/dark/${viewport}`);
    }
  }
```

```javascript
for (const theme of ['dark']) {
  for (const viewport of REQUIRED_VIEWPORTS) {
    if (!pilotScenarioKeys.has(`${theme}/${viewport}`)) {
      failures.push(`programa-general: missing scenario ${theme}/${viewport}`);
    }
  }
}
```

- [ ] **Step 4: Validar los viewports declarados por familia**

Añadir, dentro del bucle `for (const family of homologation?.families || [])` que ya existe en la línea 172:

```javascript
  for (const viewport of family.viewports || []) {
    if (!SUPPORTED_VIEWPORTS.has(viewport)) {
      failures.push(`${family.id}: unsupported viewport ${viewport}`);
    }
  }
  for (const viewport of REQUIRED_VIEWPORTS) {
    if (!(family.viewports || []).includes(viewport)) {
      failures.push(`${family.id}: missing required viewport ${viewport}`);
    }
  }
```

- [ ] **Step 5: Relajar la igualdad exacta de las aprobaciones**

Sustituir las líneas 168-170:

```javascript
  for (const viewport of approval.viewports || []) {
    if (!SUPPORTED_VIEWPORTS.has(viewport)) {
      failures.push(`${key}: approval declares unsupported viewport ${viewport}`);
    }
  }
  for (const viewport of REQUIRED_VIEWPORTS) {
    if (!(approval.viewports || []).includes(viewport)) {
      failures.push(`${key}: approval must cover ${viewport}`);
    }
  }
```

`SUPPORTED_VIEWPORTS` y `REQUIRED_VIEWPORTS` se declaran hoy en la línea ~334, después de este punto de uso. Como son `const` de módulo, hay que **subir las dos declaraciones** por encima del bucle de aprobaciones (antes de la línea 155) o el gate reventará con `Cannot access before initialization`. Muévelas, no las dupliques.

- [ ] **Step 6: Correr las pruebas y verificar que pasan**

```bash
node --test tests/design-system/contracts.test.mjs
```

Esperado: PASS, incluida `canonical design-system contracts pass the executable gate` — el repositorio real no cambió de forma, así que el gate debe seguir en verde sobre él.

- [ ] **Step 7: Verificar que el conteo de escenarios no se movió**

```bash
node scripts/design-system-contracts.mjs && echo "gate OK"
```

Esperado: `Design system contracts: PASS`. Ningún mensaje sobre `390x844`.

- [ ] **Step 8: Commit** *(solo con autorización explícita)*

```bash
git add scripts/design-system-contracts.mjs tests/design-system/contracts.test.mjs
git commit -m "feat(design-system): separar viewports soportados de requeridos en el gate"
```

---

### Task 2: Ensanchar los esquemas y los dos gates de presupuesto runtime

**Files:**
- Modify: `docs/design-system/runtime-budget.schema.json:326-331`
- Modify: `docs/design-system/family-approvals.schema.json:80-89`
- Modify: `scripts/design-system-runtime-budget.mjs:38`
- Modify: `scripts/design-system-runtime-budget-provenance.mjs:26`
- Test: `tests/design-system/runtime-budget.test.mjs`

**Interfaces:**
- Consumes: nada de la Task 1 (son archivos distintos; pueden hacerse en cualquier orden).
- Produces: `SUPPORTED_VIEWPORTS` en `design-system-runtime-budget.mjs` y `VIEWPORTS` en `design-system-runtime-budget-provenance.mjs` incluyen `'390x844'`. Son constantes internas de cada script, no exportadas.

- [ ] **Step 1: Escribir la prueba que falla**

Añadir a `tests/design-system/runtime-budget.test.mjs`:

```javascript
test('el contrato de presupuesto acepta el viewport movil', async () => {
  const source = await readFile(
    new URL('../../scripts/design-system-runtime-budget.mjs', import.meta.url), 'utf8',
  );
  assert.match(source, /SUPPORTED_VIEWPORTS = \[[^\]]*'390x844'/);
});

test('el esquema de presupuesto admite el viewport movil', async () => {
  const schema = JSON.parse(await readFile(
    new URL('../../docs/design-system/runtime-budget.schema.json', import.meta.url), 'utf8',
  ));
  const viewport = schema.allOf
    .flatMap((branch) => Object.values(branch.then?.properties ?? {}).concat(
      Object.values(branch.properties ?? {}),
    ))
    .find((property) => Array.isArray(property?.enum) && property.enum.includes('1180x820'));
  assert.ok(viewport, 'no se encontro el enum de viewport');
  assert.ok(viewport.enum.includes('390x844'));
});
```

Si `runtime-budget.test.mjs` aún no importa `readFile`, añade `import { readFile } from 'node:fs/promises';` arriba.

> Si la travesía por `schema.allOf` no encuentra el enum, **no adaptes la prueba al esquema**: abre `runtime-budget.schema.json:326`, mira dónde vive de verdad ese enum y corrige la ruta de acceso. La forma del esquema es el dato; la prueba se ajusta a él, nunca al revés.

- [ ] **Step 2: Correr y verificar que falla**

```bash
node --test tests/design-system/runtime-budget.test.mjs
```

Esperado: FAIL en las dos pruebas nuevas.

- [ ] **Step 3: Ensanchar los dos enums de esquema**

`docs/design-system/runtime-budget.schema.json`, línea ~327:

```json
        "viewport": {
          "enum": [
            "1180x820",
            "1440x900",
            "390x844"
          ]
        },
```

`docs/design-system/family-approvals.schema.json`, línea ~85:

```json
        "viewports": {
          "type": "array",
          "minItems": 1,
          "uniqueItems": true,
          "items": {
            "enum": [
              "1180x820",
              "1440x900",
              "390x844"
            ]
```

- [ ] **Step 4: Ensanchar las dos constantes de script**

`scripts/design-system-runtime-budget.mjs:38`:

```javascript
const SUPPORTED_VIEWPORTS = ['1180x820', '1440x900', '390x844'];
```

`scripts/design-system-runtime-budget-provenance.mjs:26`:

```javascript
const VIEWPORTS = ['1180x820', '1440x900', '390x844'];
```

**No toques** `design-system-runtime-budget.mjs:146` (`requireEqual(artifact.viewport, '1440x900', 'baseline viewport')`). Esa línea fija el viewport de la **línea base histórica**, que se midió en 1440x900 y no se vuelve a medir. Cambiarla invalidaría la comparación contra la base.

- [ ] **Step 5: Correr y verificar que pasan**

```bash
node --test tests/design-system/runtime-budget.test.mjs tests/design-system/runtime-budget-provenance.test.mjs tests/design-system/runtime-budget-aggregate.test.mjs
```

Esperado: PASS en las tres suites. Las dos últimas no deberían haber cambiado de resultado.

- [ ] **Step 6: Commit** *(solo con autorización explícita)*

```bash
git add docs/design-system/runtime-budget.schema.json docs/design-system/family-approvals.schema.json scripts/design-system-runtime-budget.mjs scripts/design-system-runtime-budget-provenance.mjs tests/design-system/runtime-budget.test.mjs
git commit -m "feat(design-system): admitir el viewport movil en esquemas y gates de presupuesto"
```

---

### Task 3: Reescribir el candado

El archivo se **renombra** con `git mv`. `scripts/design-system-static-suite.mjs:13` recoge los tests por glob sobre `tests/design-system`, así que el renombrado no lo saca de la suite; ningún workflow lo nombra explícitamente (verificado el 2026-08-07).

> **Corregido tras la ejecución (2026-08-07).** La versión original decía que `git mv`
> haría «que el historial siga al contenido». No es así: git detecta renombrados por
> similitud al mostrar, no por el comando usado. Como aquí el contenido se reescribe
> entero, con el umbral por defecto (50%) el commit aparece como borrado más creación;
> `git show -M20%` sí lo detecta, al 32% de similitud. Es inherente a reescribir el
> archivo, no algo que se pueda evitar.

**Files:**
- Rename: `tests/design-system/mobile-viewport-removal.test.mjs` → `tests/design-system/mobile-viewport-scope.test.mjs`
- Test: el propio archivo renombrado (es una suite de contrato, no tiene test aparte)

**Interfaces:**
- Consumes: las constantes `SUPPORTED_VIEWPORTS` / `REQUIRED_VIEWPORTS` de la Task 1, leídas **como texto fuente** (igual que hacía el candado original), no importadas.

- [ ] **Step 1: Renombrar el archivo conservando historial**

```bash
git mv tests/design-system/mobile-viewport-removal.test.mjs tests/design-system/mobile-viewport-scope.test.mjs
```

- [ ] **Step 2: Reescribir el contenido entero**

Sustituir todo el archivo por:

```javascript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

// Candado de alcance de viewports. Sustituye al candado de retirada de DS-031:
// aquella prohibia el ancho movil; esta exige que todo viewport declarado venga
// con evidencia que lo sostenga. La intencion protegida es la misma —que no
// exista viewport declarado sin golden— y sobrevive a que F2 empiece a declarar
// 390x844 familia por familia.
const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));

const REQUIRED_VIEWPORTS = ['1180x820', '1440x900'];
const SUPPORTED_VIEWPORTS = [...REQUIRED_VIEWPORTS, '390x844'];

const SCHEMAS = [
  'docs/design-system/runtime-budget.schema.json',
  'docs/design-system/family-approvals.schema.json',
];

for (const schema of SCHEMAS) {
  test(`${schema} admite el viewport movil`, async () => {
    assert.ok(
      (await read(schema)).includes('390x844'),
      `${schema} volvio a cerrar el viewport movil`,
    );
  });
}

test('toda familia homologada cubre los viewports requeridos y ninguno ajeno', async () => {
  const homologation = await readJson('docs/design-system/homologation.json');
  for (const family of homologation.families) {
    for (const viewport of REQUIRED_VIEWPORTS) {
      assert.ok(
        family.viewports.includes(viewport),
        `familia ${family.id} no cubre ${viewport}`,
      );
    }
    for (const viewport of family.viewports) {
      assert.ok(
        SUPPORTED_VIEWPORTS.includes(viewport),
        `familia ${family.id} declara el viewport no soportado ${viewport}`,
      );
    }
  }
});

test('toda aprobacion de familia cubre los viewports requeridos y ninguno ajeno', async () => {
  const { approvals } = await readJson('docs/design-system/family-approvals.json');
  for (const approval of approvals) {
    const label = `${approval.familyId}/${approval.candidateId}`;
    for (const viewport of REQUIRED_VIEWPORTS) {
      assert.ok(approval.viewports.includes(viewport), `${label} no cubre ${viewport}`);
    }
    for (const viewport of approval.viewports) {
      assert.ok(
        SUPPORTED_VIEWPORTS.includes(viewport),
        `${label} declara el viewport no soportado ${viewport}`,
      );
    }
  }
});

test('todo escenario declarado en un manifiesto trae evidencia', async () => {
  const inventory = await readJson('docs/design-system/manifests/inventory.json');
  assert.ok(inventory.manifests.length >= 1);
  for (const file of inventory.manifests) {
    const manifest = await readJson(`docs/design-system/manifests/${file}`);
    for (const scenario of manifest.scenarios ?? []) {
      const label = `${manifest.moduleId}/${scenario.id}`;
      assert.ok(scenario.golden, `${label} declara un escenario sin golden`);
      assert.match(scenario.sha256 ?? '', /^[a-f0-9]{64}$/, `${label} sin sha256`);
    }
  }
});

test('el gate de contratos distingue soportado de requerido', async () => {
  const source = await read('scripts/design-system-contracts.mjs');
  assert.match(source, /const SUPPORTED_VIEWPORTS = new Set\(\[/);
  assert.match(source, /const REQUIRED_VIEWPORTS = \['1180x820', '1440x900'\]/);
});
```

- [ ] **Step 3: Correr el candado nuevo**

```bash
node --test tests/design-system/mobile-viewport-scope.test.mjs
```

Esperado: PASS en las seis pruebas. Si la última falla, la Task 1 no está aplicada o cambiaron los nombres de las constantes — arregla la Task 1, no el candado.

- [ ] **Step 4: Comprobar que la suite lo sigue recogiendo**

> **Corregido tras la ejecución (2026-08-07).** El comando original filtraba la salida de
> la suite por el nombre del archivo, y devuelve `0` **siempre**: `node --test` no imprime
> nombres de archivo. Se comprueba por el título de una prueba, que sí aparece.

```bash
node scripts/design-system-static-suite.mjs 2>&1 | grep -c "el gate de contratos distingue soportado de requerido"
```

Esperado: al menos `1`. Si sale `0`, la suite no está cargando el archivo y hay que revisar el glob de `design-system-static-suite.mjs:13` antes de continuar.

- [ ] **Step 5: Commit** *(solo con autorización explícita)*

```bash
git add tests/design-system/mobile-viewport-scope.test.mjs
git commit -m "test(design-system): el candado movil pasa a exigir evidencia en vez de prohibir el ancho"
```

---

### Task 4: Registrar la decisión y actualizar el contrato de migración

**Files:**
- Modify: `docs/design-system/decisions.md` (añadir fila DS-032 al final de la tabla)
- Modify: `docs/design-system/contracts/module-migration.md:16`
- Test: `tests/design-system/governance-docs.test.mjs`

**Interfaces:**
- Consumes: el identificador `DS-032`, que no debe existir todavía en `decisions.md`.

- [ ] **Step 1: Comprobar que DS-032 está libre**

```bash
grep -c "DS-032" docs/design-system/decisions.md
```

Esperado: `0`. Si devuelve otra cosa, usa el siguiente número libre y ajústalo en todo este plan.

- [ ] **Step 2: Añadir DS-032**

Al final de la tabla de `docs/design-system/decisions.md`:

```markdown
| DS-032 | Reapertura del viewport `390x844` | 2026-08-07: el viewport móvil vuelve a ser un valor **soportado** en los esquemas, las aprobaciones y los tres gates, pero **no requerido**: la cobertura obligatoria sigue siendo `1180x820` y `1440x900`. Supersede a DS-031, que lo había retirado cuando `AGENTS.md` §Routing acotaba el alcance a desktop; esa prohibición se retiró de los `.md` normativos el 2026-08-07. El gate pasa a distinguir `SUPPORTED_VIEWPORTS` de `REQUIRED_VIEWPORTS` y, por primera vez, valida los viewports declarados en `homologation.json`. El candado de DS-031 se renombra a `tests/design-system/mobile-viewport-scope.test.mjs` y cambia de intención: ya no prohíbe el ancho, exige evidencia para todo escenario declarado. No se genera evidencia móvil: eso es F2 | approved |
```

- [ ] **Step 3: Actualizar el contrato de migración**

En `docs/design-system/contracts/module-migration.md`, sustituir la línea 16:

```markdown
- Dark en 1180x820 y 1440x900.
```

por:

```markdown
- Dark en 1180x820 y 1440x900. Son los viewports **requeridos**: todo módulo migrado
  los cubre con evidencia. `390x844` está **soportado pero no requerido** desde DS-032:
  se puede declarar, y en cuanto se declara exige golden y `sha256` como cualquier otro.
```

- [ ] **Step 4: Correr los gates de gobernanza y documentación**

```bash
node --test tests/design-system/governance-docs.test.mjs tests/design-system/design-doc-wiring.test.mjs
```

Esperado: PASS. Si `governance-docs` exige una forma concreta de fila en `decisions.md`, ajusta la fila a esa forma —no la prueba.

- [ ] **Step 5: Cierre completo de F1**

```bash
npm run test:design-system:static
```

Esperado: las ocho puertas en verde (`entrypoint-partition`, `unlayered-delivery`, `bi-utilities`, `table-contract`, `node-tests`, `contracts`, `consumer-contract`, `audit`).

- [ ] **Step 6: Verificar la condición de hecho nº2 — que nada se ensanchó de facto**

```bash
node -e "const h=require('./docs/design-system/homologation.json');console.log(h.families.length, [...new Set(h.families.flatMap(f=>f.viewports))].sort().join(','))"
```

Esperado exacto: `10 1180x820,1440x900`. Si aparece `390x844`, alguien declaró el viewport en F1 y eso pertenece a F2.

- [ ] **Step 7: Commit** *(solo con autorización explícita)*

```bash
git add docs/design-system/decisions.md docs/design-system/contracts/module-migration.md
git commit -m "docs(design-system): DS-032 reabre el viewport movil como soportado no requerido"
```

---

## Condición de hecho de F1

Las cuatro de la spec, verificables con los comandos de arriba:

1. `npm run test:design-system:static` pasa sus ocho puertas (Task 4, Step 5).
2. Los gates siguen ejerciendo los 20 escenarios desktop (Task 4, Step 6).
3. No se añadió, regeneró ni borró ningún golden — comprobable con `git status --short tests/browser/__screenshots__` vacío.
4. Un manifiesto con escenario `390x844` valida contra el esquema y sigue sin satisfacer el gate de cobertura (Task 1, Steps 1 y 6).

## Fuera de alcance

Cards, CSS, temas, goldens, axe, el conmutador, `pdc-app/`, y cualquier cambio en `homologation.json` o en las aprobaciones firmadas. El carril runtime (`npm run test:design-system:runtime`) no se corre: F1 no cambia nada observable en navegador.
