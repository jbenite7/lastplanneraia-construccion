---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-07
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-07-f2a-1-precondiciones-evidencia-movil.md
resumen: Que un escenario 390x844 declarado en un manifiesto produzca evidencia real o falle ruidosamente — hoy se declara, nadie lo captura y todos los gates dan verde.
---

# F2a-1 — Precondiciones de la evidencia móvil: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que un escenario `390x844` declarado en un manifiesto produzca evidencia real o falle ruidosamente — hoy se declara, nadie lo captura y todos los gates dan verde.

**Architecture:** Tres arreglos independientes en la infraestructura de pruebas, ninguno visual. El harness de fixtures se arregla primero porque es lo que permite escribir pruebas de caso positivo para los otros dos. Después el gate aprende a rechazar un golden que no corresponde a su viewport. Por último, los carriles visual y de accesibilidad dejan de descartar los escenarios móviles en silencio.

**Tech Stack:** Node 20+ (`node --test`), Playwright, scripts ES module en `scripts/`.

**Spec:** [`docs/superpowers/specs/2026-08-07-f2a-piloto-movil-programacion-design.md`](../specs/2026-08-07-f2a-piloto-movil-programacion-design.md) §Las tres precondiciones (P1, P2, P3).

## Global Constraints

- Viewport móvil canónico: **`390x844`**. Viewports desktop requeridos: **`1180x820`** y **`1440x900`**.
- Tema único: **`dark`**. Nada aquí toca temas.
- **Cero cambio visual y cero evidencia nueva.** No se crea, regenera ni borra ningún golden. No se toca `public/css`, `views/`, `public/js`, ni `pdc-app/`.
- **No se declara ningún escenario móvil en este plan.** Al terminar, `homologation.json` y los manifiestos siguen exactamente igual. Declarar escenarios es F2a-2.
- Al terminar, `npm run test:design-system:static` pasa sus ocho puertas.
- Nomenclatura ya vigente de los goldens, que este plan convierte en regla ejecutable: `<modulo>-<tema>-<ancho>x<alto>.png`.
- **Commits:** `AGENTS.md` §Publicación prohíbe commitear sin petición explícita. Los pasos de commit solo se ejecutan si Felipe lo autoriza.

## File Structure

| Archivo | Responsabilidad tras F2a-1 |
|---|---|
| `tests/design-system/contracts.test.mjs` | El harness `runFixture` deja de llevar una lista fija de archivos y la deriva de los contratos; enlaza `.git`. Con eso admite pruebas de caso positivo. |
| `scripts/design-system-contracts.mjs` | El gate ata cada golden a su escenario: nombre coherente con tema y viewport, y ningún golden compartido entre dos escenarios. |
| `tests/browser/design-system-lab.visual.mjs` | Deja de filtrar por `width >= 1180`. |
| `tests/browser/programa-general.visual.mjs` | Igual. |
| `tests/browser/design-system-lab.a11y.mjs` | Igual, y su conteo fijo de 20 escenarios pasa a derivarse. |

---

### Task 1: Arreglar el harness de fixtures (P3)

Hoy `runFixture` copia `docs/design-system` a un directorio temporal y corre el gate ahí. Falla **siempre**, por dos causas medidas el 2026-08-07: sin `.git` el gate suma 17 fallos de `sourceRef must resolve to a Git commit`, y aun enlazando `.git` quedan 22 fallos de `missing test <ruta>` porque el harness copia 9 archivos de test mientras los contratos referencian una veintena. Consecuencia: **ninguna prueba de fixture puede comprobar un caso positivo**, y por eso todas las existentes solo asertan `notEqual(status, 0)`.

**Files:**
- Modify: `tests/design-system/contracts.test.mjs:16-53` (la función `runFixture`)
- Test: el mismo archivo

**Interfaces:**
- Produces: `runFixture(mutate)` sigue con la misma firma y sigue devolviendo el resultado de `spawnSync`. Lo que cambia es que un fixture **sin mutar** ahora sale con `status 0`. Las tareas 2 y 3 dependen de eso.

- [ ] **Step 1: Escribir la prueba que falla**

Añadir a `tests/design-system/contracts.test.mjs`:

```javascript
test('un fixture sin mutar pasa el gate', () => {
  const result = runFixture(() => {});
  assert.equal(result.status, 0, result.stderr || result.stdout);
});
```

- [ ] **Step 2: Correr y verificar que falla**

```bash
node --test tests/design-system/contracts.test.mjs
```

Esperado: FAIL. La salida trae decenas de líneas `- ...`: unas de `sourceRef must resolve to a Git commit` y otras de `missing test <ruta>`. Ambas familias deben desaparecer al final de esta tarea.

- [ ] **Step 3: Derivar la lista de tests copiados de los contratos**

En `runFixture`, sustituir la lista fija de archivos por una derivada. Las dos fuentes son las mismas que el gate comprueba: `homologation.tests` (`scripts/design-system-contracts.mjs:148`) y `manifest.tests` de cada manifiesto (`scripts/design-system-contracts.mjs:315`).

```javascript
function contractTestFiles() {
  const dsRoot = path.join(root, 'docs/design-system');
  const homologation = JSON.parse(readFileSync(path.join(dsRoot, 'homologation.json'), 'utf8'));
  const inventory = JSON.parse(readFileSync(path.join(dsRoot, 'manifests/inventory.json'), 'utf8'));
  const files = new Set(homologation.tests || []);
  for (const name of inventory.manifests) {
    const manifest = JSON.parse(readFileSync(path.join(dsRoot, 'manifests', name), 'utf8'));
    for (const file of manifest.tests || []) files.add(file);
  }
  return [...files];
}
```

Y en el cuerpo de `runFixture`, reemplazar el `for (const file of [ ... ])` de la lista fija por:

```javascript
  for (const file of contractTestFiles()) {
    const source = path.join(root, file);
    if (!existsSync(source)) continue;
    mkdirSync(path.dirname(path.join(fixtureRoot, file)), { recursive: true });
    cpSync(source, path.join(fixtureRoot, file));
  }
```

El `continue` es deliberado: si un contrato referencia un archivo que no existe, ese es un fallo **real** del repositorio y lo debe reportar el gate, no ocultarlo el harness copiando lo que puede.

- [ ] **Step 4: Enlazar `.git` en el fixture**

Junto a los `symlinkSync` que ya existen para `public` y `views`, añadir:

```javascript
  symlinkSync(path.join(root, '.git'), path.join(fixtureRoot, '.git'), 'dir');
```

Funciona porque el gate solo hace `git rev-parse --verify <sha>^{commit}` y `git ls-tree -r --full-tree <commit>` (`scripts/design-system-evidence-receipt.mjs:34-46`): ambas leen la base de objetos, no el árbol de trabajo.

- [ ] **Step 5: Correr y verificar que pasa**

```bash
node --test tests/design-system/contracts.test.mjs
```

Esperado: PASS, incluidas todas las pruebas negativas que ya existían. **Si alguna negativa se vuelve verde por el motivo equivocado** —es decir, si ahora falla el gate por una causa distinta de la que esa prueba busca— arréglalo: cada negativa hace `assert.match(result.stderr, /mensaje concreto/)` además de comprobar el status, así que el mensaje debe seguir siendo el suyo.

- [ ] **Step 6: Commit** *(solo con autorización explícita)*

```bash
git add tests/design-system/contracts.test.mjs
git commit -m "test(design-system): el harness de fixtures deriva sus tests de los contratos y enlaza .git"
```

---

### Task 2: Atar cada golden a su escenario (P2)

Hoy el gate verifica que el golden exista y que su hash cuadre (`scripts/design-system-contracts.mjs:346-355`), pero no que **corresponda** al escenario. La revisión final de F1 lo reprodujo: un escenario `390x844` que reutilice el golden y el `sha256` de uno `1180x820` no produce ni un fallo. Sin esto, F2a-2 puede generar evidencia móvil falsa y quedar verde.

La convención de nombres ya existe en todos los manifiestos —`programacion-semanal-dark-1180x820.png`— así que la regla no inventa nada: convierte en ejecutable lo que ya se cumple.

**Files:**
- Modify: `scripts/design-system-contracts.mjs:346-355`
- Test: `tests/design-system/contracts.test.mjs`

**Interfaces:**
- Consumes: `runFixture(mutate)` de la Task 1, que ahora devuelve `status 0` sobre un fixture sin mutar.
- Produces: dos mensajes de fallo nuevos, que la Task 3 no usa pero el candado de F2a-2 sí comprobará: `golden does not match theme/viewport` y `golden reused by another scenario`.

- [ ] **Step 1: Escribir las dos pruebas que fallan**

```javascript
test('un golden que no corresponde al viewport del escenario falla', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programacion-semanal.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const target = manifest.scenarios.find((s) => s.viewport.width === 1180);
    const donor = manifest.scenarios.find((s) => s.viewport.width === 1440);
    target.golden = donor.golden;
    target.sha256 = donor.sha256;
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /golden does not match theme\/viewport/);
});

test('dos escenarios no pueden compartir el mismo golden', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programacion-semanal.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const [first, second] = manifest.scenarios;
    second.id = `${second.id}-copia`;
    second.viewport = { ...first.viewport };
    second.golden = first.golden;
    second.sha256 = first.sha256;
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /golden reused by another scenario/);
});
```

- [ ] **Step 2: Correr y verificar que fallan**

```bash
node --test tests/design-system/contracts.test.mjs
```

Esperado: FAIL en las dos nuevas — el gate hoy no emite ninguno de esos mensajes.

- [ ] **Step 3: Implementar las dos reglas en el gate**

En `scripts/design-system-contracts.mjs`, dentro del bucle de escenarios y **después** de la comprobación de hash que ya existe, añadir:

```javascript
    const expectedSuffix =
      `-${scenario.theme}-${scenario.viewport.width}x${scenario.viewport.height}.png`;
    if (!scenario.golden.endsWith(expectedSuffix)) {
      failures.push(
        `${manifest.moduleId}/${scenario.id}: golden does not match theme/viewport `
        + `(espera un nombre terminado en ${expectedSuffix})`,
      );
    }
    if (goldenOwners.has(scenario.golden)) {
      failures.push(
        `${manifest.moduleId}/${scenario.id}: golden reused by another scenario `
        + `(${goldenOwners.get(scenario.golden)})`,
      );
    }
    goldenOwners.set(scenario.golden, `${manifest.moduleId}/${scenario.id}`);
```

`goldenOwners` es un `Map` declarado **fuera** del bucle de manifiestos, para que la detección cruce también entre módulos distintos:

```javascript
const goldenOwners = new Map();
```

- [ ] **Step 4: Correr y verificar que pasan**

```bash
node --test tests/design-system/contracts.test.mjs && node scripts/design-system-contracts.mjs
```

Esperado: PASS en las pruebas, y `Design system contracts: PASS` sobre el repositorio real. Lo segundo importa: comprueba que **los goldens que ya existen cumplen la convención**. Si alguno no la cumple, párate y repórtalo — significa que la convención no era universal y hay que decidir qué hacer con la excepción, no relajar la regla.

- [ ] **Step 5: Commit** *(solo con autorización explícita)*

```bash
git add scripts/design-system-contracts.mjs tests/design-system/contracts.test.mjs
git commit -m "feat(design-system): el gate ata cada golden a su tema y viewport"
```

---

### Task 3: Que los carriles dejen de descartar el móvil (P1)

Tres specs de Playwright filtran los escenarios por `viewport.width >= 1180`. Un escenario móvil declarado no se renderiza, no se compara y no se audita: **desaparece sin un aviso**. Es la mitad silenciosa del hueco que cierra la Task 2.

Ojo con la trampa: `design-system-lab.a11y.mjs:20` tiene además un `expect(scenarios).toHaveLength(20)` fijo. Si se quita el filtro sin tocar el conteo, el carril se cae en cuanto exista el primer escenario móvil, y el mensaje no dirá nada sobre viewports.

**Files:**
- Modify: `tests/browser/design-system-lab.visual.mjs:22`
- Modify: `tests/browser/programa-general.visual.mjs:12`
- Modify: `tests/browser/programacion-intermedia.visual.mjs:12`
- Modify: `tests/browser/design-system-lab.a11y.mjs:19-20`
- Test: `tests/design-system/visual-ci-contract.test.mjs`

> **Son cuatro carriles, no tres.** La spec citaba tres porque es lo que había encontrado la
> revisión final de F1. El censo del 2026-08-07 (`grep -rn "width >= 1180" tests/`) encontró
> un cuarto: `programacion-intermedia.visual.mjs:12` — y es **uno de los dos módulos del
> piloto**, así que sin él F2a-2 no podría capturar la mitad de su evidencia.

**Interfaces:**
- Consumes: nada de las tareas anteriores; es independiente y puede hacerse en cualquier orden respecto de la 2.
- Produces: los tres carriles iteran sobre todos los escenarios `dark` declarados, sea cual sea su ancho.

- [ ] **Step 1: Escribir la prueba que falla**

Esta se comprueba sobre el texto fuente, porque ejecutar los tres carriles exige navegador, sesión y proyecto sembrado, y su entrada no cambia en este plan. Añadir a `tests/design-system/visual-ci-contract.test.mjs`:

```javascript
test('ningun carril descarta escenarios por ancho', async () => {
  for (const spec of [
    'tests/browser/design-system-lab.visual.mjs',
    'tests/browser/programa-general.visual.mjs',
    'tests/browser/programacion-intermedia.visual.mjs',
    'tests/browser/design-system-lab.a11y.mjs',
  ]) {
    const source = await readFile(new URL(`../../${spec}`, import.meta.url), 'utf8');
    assert.equal(
      /width\s*>=\s*1180/.test(source), false,
      `${spec} sigue descartando escenarios por ancho`,
    );
  }
});

test('el carril de accesibilidad no fija el numero de escenarios a mano', async () => {
  const source = await readFile(
    new URL('../../tests/browser/design-system-lab.a11y.mjs', import.meta.url), 'utf8',
  );
  assert.equal(/toHaveLength\(\d+\)/.test(source), false);
});
```

Si `visual-ci-contract.test.mjs` aún no importa `readFile`, añade `import { readFile } from 'node:fs/promises';` arriba.

- [ ] **Step 2: Correr y verificar que fallan**

```bash
node --test tests/design-system/visual-ci-contract.test.mjs
```

Esperado: FAIL en las dos nuevas.

- [ ] **Step 3: Quitar los tres filtros de los carriles visuales**

En `tests/browser/design-system-lab.visual.mjs:22` y en `tests/browser/programa-general.visual.mjs:12`, la misma sustitución en ambos:

```javascript
const VISUAL_SCENARIOS = MANIFEST.scenarios.filter(({ theme }) => theme === 'dark');
```

En `tests/browser/programacion-intermedia.visual.mjs:12` el filtro está partido en dos líneas; queda:

```javascript
const VISUAL_SCENARIOS = MANIFEST.scenarios.filter(
  ({ theme }) => theme === 'dark',
);
```

- [ ] **Step 4: Quitar el filtro y el conteo fijo del carril de accesibilidad**

En `tests/browser/design-system-lab.a11y.mjs`, sustituir el filtro y la aserción de longitud:

```javascript
  const scenarios = helper.approvedAccessibilityScenarios(homologation)
    .filter((scenario) => scenario.theme === 'dark');
  expect(scenarios.length).toBeGreaterThan(0);
```

El conteo deja de ser un número escrito a mano y pasa a comprobar lo único que de verdad importa aquí: que la matriz no se quedó vacía. El número exacto lo gobiernan `homologation.json` y las aprobaciones, que ya tienen sus propios gates.

- [ ] **Step 5: Correr y verificar que pasan**

```bash
node --test tests/design-system/visual-ci-contract.test.mjs
```

Esperado: PASS.

- [ ] **Step 6: Comprobar que los carriles siguen sanos en escritorio**

```bash
npx playwright test tests/browser/design-system-lab.visual.mjs --workers=1
```

Esperado: mismo resultado que antes del cambio. **No debe aparecer ningún escenario nuevo**: ninguna familia declara `390x844` todavía, así que quitar el filtro no cambia nada hoy. Si aparecen escenarios nuevos, alguien declaró móvil y eso pertenece a F2a-2.

Si esta suite ya venía en rojo antes de tocar nada, no la arregles aquí: anota el estado previo y sigue. Hay rojos preexistentes en el carril visual que no son de este plan.

- [ ] **Step 7: Commit** *(solo con autorización explícita)*

```bash
git add tests/browser/design-system-lab.visual.mjs tests/browser/programa-general.visual.mjs tests/browser/design-system-lab.a11y.mjs tests/design-system/visual-ci-contract.test.mjs
git commit -m "test(design-system): los carriles visual y a11y dejan de descartar el viewport movil"
```

---

### Task 4: Derivar la matriz esperada en vez de escribirla a mano

La revisión final de F1 encontró una aserción que prohibía por igualdad exacta lo que el candado nuevo permite, y la arreglamos. El censo del 2026-08-07 muestra que **no era un caso aislado**: quedan dos sitios más en la suite estática que fijan la matriz a mano y que se pondrán rojos en cuanto F2a-2 declare el primer escenario móvil, con mensajes que no mencionan viewports ni móvil.

La regla de esta tarea: **derivar de `homologation.json`, no escribir números**. Así la matriz se adapta sola cuando F2a-2 declare móvil, sin dejar de comprobar que la cobertura es la que los contratos dicen.

**Files:**
- Modify: `tests/design-system/accessibility.test.mjs:80,88-91`
- Modify: `tests/design-system/visual-ci-contract.test.mjs:13-32`
- Test: los mismos archivos

**Interfaces:**
- Consumes: nada de las tareas anteriores.
- Produces: nada que otras tareas usen. Es la última.

- [ ] **Step 1: Ver el rojo futuro antes de arreglarlo**

Comprueba con tus propios ojos que estas dos pruebas se caerían. Añade temporalmente `390x844` a una familia de `homologation.json` y corre la suite:

```bash
node --input-type=module -e 'import {readFileSync, writeFileSync} from "node:fs"; const p="docs/design-system/homologation.json"; const h=JSON.parse(readFileSync(p,"utf8")); h.families[0].viewports.push("390x844"); writeFileSync(p, JSON.stringify(h,null,2)+"\n")'
node --test tests/design-system/accessibility.test.mjs tests/design-system/visual-ci-contract.test.mjs
git checkout docs/design-system/homologation.json
```

Esperado: FAIL en ambas. **El `git checkout` de la tercera línea no es opcional** — deja `homologation.json` como estaba. Confirma con `git status --short docs/design-system/homologation.json` que no queda nada.

- [ ] **Step 2: Derivar el conteo en el carril de accesibilidad**

En `tests/design-system/accessibility.test.mjs`, sustituir el conteo fijo de la línea 80:

```javascript
  const expectedCount = homologation.families
    .reduce((total, family) => total + family.viewports.length, 0);
  assert.equal(scenarios.length, expectedCount);
```

Y la aserción de viewports por familia (líneas 88-91), que hoy fija los dos desktop, pasa a comparar contra lo que **esa** familia declara:

```javascript
  for (const family of [...new Set(scenarios.map(({ family }) => family))]) {
    const familyScenarios = scenarios.filter((scenario) => scenario.family === family);
    const declared = homologation.families.find(({ id }) => id === family).viewports;
    assert.deepEqual([...new Set(familyScenarios.map(({ theme }) => theme))], ['dark']);
    assert.deepEqual([...new Set(familyScenarios.map(({ viewport }) => viewport))], declared);
  }
```

La lista de las diez familias por nombre (líneas 81-84) **se conserva tal cual**: eso sí es una decisión de gobierno que debe romperse si alguien añade o quita una familia sin querer.

- [ ] **Step 3: Derivar la matriz del contrato visual**

En `tests/design-system/visual-ci-contract.test.mjs`, `assertDesktopDarkPilotMatrix` compara el conjunto de viewports contra `requiredViewports` por igualdad exacta y exige un conteo fijo por viewport. Cámbiala para que reciba los viewports esperados en vez de asumirlos:

```javascript
function assertDarkPilotMatrix(scenarios, expectedPerViewport, viewports) {
  assert.deepEqual([...new Set(scenarios.map(({ theme }) => theme))], ['dark']);
  assert.deepEqual(
    [...new Set(scenarios.map(({ viewport }) => viewportKey(viewport)))].sort(),
    [...viewports].sort(),
  );

  for (const viewport of viewports) {
    assert.equal(
      scenarios.filter((scenario) => viewportKey(scenario.viewport) === viewport).length,
      expectedPerViewport,
      `dark ${viewport}`,
    );
  }
}
```

Cada llamada pasa los viewports que el manifiesto o la homologación declaran para esa superficie, en vez de la constante. Renombra la función: ya no es «desktop».

- [ ] **Step 4: Repetir el Step 1 y comprobar que ahora aguanta**

```bash
node --input-type=module -e 'import {readFileSync, writeFileSync} from "node:fs"; const p="docs/design-system/homologation.json"; const h=JSON.parse(readFileSync(p,"utf8")); h.families[0].viewports.push("390x844"); writeFileSync(p, JSON.stringify(h,null,2)+"\n")'
node --test tests/design-system/accessibility.test.mjs tests/design-system/visual-ci-contract.test.mjs
git checkout docs/design-system/homologation.json
```

Esperado: ahora las dos pruebas **fallan por el motivo correcto** —falta el escenario `390x844` que la familia declara, que es exactamente lo que debe exigirse— y ya no por un número escrito a mano. Si pasan en verde, la derivación quedó demasiado laxa y hay que apretarla.

Vuelve a confirmar con `git status --short docs/design-system/homologation.json` que el archivo quedó limpio.

- [ ] **Step 5: Cierre**

```bash
npm run test:design-system:static
```

Esperado: las ocho puertas en verde con el repositorio real intacto.

- [ ] **Step 6: Commit** *(solo con autorización explícita)*

```bash
git add tests/design-system/accessibility.test.mjs tests/design-system/visual-ci-contract.test.mjs
git commit -m "test(design-system): la matriz esperada se deriva de homologation en vez de escribirse a mano"
```

---

## Condición de hecho de F2a-1

1. `npm run test:design-system:static` pasa sus ocho puertas.
2. Un fixture sin mutar sale con `status 0` (Task 1) — el harness admite pruebas de caso positivo.
3. Reutilizar el golden de otro escenario hace fallar el gate con un mensaje que lo dice (Task 2).
4. Ningún carril contiene ya `width >= 1180` ni un conteo de escenarios escrito a mano (Task 3).
5. Añadir `390x844` a una familia de `homologation.json` hace fallar la suite estática **por falta del escenario declarado**, no por un número fijo (Task 4, Step 4).
6. `homologation.json` y los manifiestos siguen byte-idénticos: `git diff --stat docs/design-system/homologation.json docs/design-system/manifests/` vacío.

## Fuera de alcance

Declarar escenarios móviles, generar goldens, la primitiva de cards, el umbral de 1180 en el navegador, y cualquier cambio en `public/js`, `views/` o `public/css`. Todo eso es F2a-2.
