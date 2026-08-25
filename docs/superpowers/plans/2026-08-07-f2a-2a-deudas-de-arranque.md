---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-07
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-07-f2a-2a-deudas-de-arranque.md
resumen: Cerrar los tres pendientes que dejó F2a-1 antes de que el piloto genere un solo golden móvil.
---

# F2a-2a — Deudas de arranque: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cerrar los tres pendientes que dejó F2a-1 antes de que el piloto genere un solo golden móvil.

**Architecture:** Tres bloques independientes. El primero ata cada golden a las dimensiones exactas de su viewport, distinguiendo por primera vez una captura de pantalla completa de un recorte a elemento. El segundo salda la deuda de versión de los 11 manifiestos que el gate nunca miró y restaura la derivación que F2a-1 dejó a medias. El tercero limpia los seis minors registrados.

**Tech Stack:** Node 20+ (`node --test`), JSON Schema, scripts ES module en `scripts/`.

**Goal:** `goals/reapertura-movil-y-tema-claro/goal.md` (pendientes P-A, P-B, P-C).

## Global Constraints

- Viewport móvil canónico: **`390x844`**. Viewports desktop requeridos: **`1180x820`** y **`1440x900`**.
- **Cero cambio visual.** No se crea, regenera, renombra ni borra ningún golden. No se toca `public/css`, `public/js`, `views/`, ni `pdc-app/`.
- **No se declara ningún escenario móvil.** Eso es el piloto, no este plan.
- Al terminar, `npm run test:design-system:static` pasa sus ocho puertas.
- **Toda regla de gate que se toque se entrega con una mutación que la pone roja, ejecutada, con la salida pegada en el informe.** Es la regla que salió del patrón de F2a-1: cuatro defectos de plan y dos Critical, todos por afirmar el estado sin medirlo.
- Cada mutación se revierte con `git checkout` y se confirma con `git status --short`.
- **Commits:** autorizados por Felipe para este plan, uno por tarea.

## Datos medidos (2026-08-07)

- De los **39 escenarios** con golden en disco, **37 tienen el ancho exacto** del viewport declarado y **2 son recortes**: `laboratory/states-feedback-dark-1180x820` (png 1102x1649) y `laboratory/states-feedback-dark-1440x900` (png 1362x1577).
- **11 de 17 manifiestos** declaran `designSystemVersion: 1.0.0`: `auth`, `bi-runtime`, `control-cambios`, `escalamientos`, `foundation-shell`, `indicadores`, `plan-compras-v2`, `profesionales`, `programa-general-actualizar`, `programacion-semanal`, `subcontratistas`. Los 6 en `1.1.0` son exactamente los que el gate miraba antes de F2a-1.

---

### Task 1: Atar el golden a las dimensiones exactas de su viewport (P-A)

Hoy `scripts/design-system-contracts.mjs` comprueba que el ancho del PNG sea **menor o igual** que el del viewport. Se eligió `<=` porque dos goldens legítimos están recortados a un elemento. La cota de un solo lado deja abierto el caso simétrico, medido por la re-revisión de F2a-1: un PNG real de `390x844` con contenido único pasa como evidencia de un escenario de escritorio.

La causa de fondo es que el manifiesto no dice si una captura es de pantalla completa o un recorte. Esta tarea se lo hace decir.

**Files:**
- Modify: `docs/design-system/module-manifest.schema.json`
- Modify: `docs/design-system/manifests/laboratory.json` (los dos escenarios de `states-feedback`)
- Modify: `scripts/design-system-contracts.mjs` (el bloque de dimensiones)
- Test: `tests/design-system/contracts.test.mjs`

**Interfaces:**
- Produces: un campo `capture` en el escenario de manifiesto, con valores `"viewport"` (por defecto si se omite) y `"element"`. El piloto lo usará al declarar sus escenarios móviles.

- [ ] **Step 1: Escribir las dos pruebas que fallan**

```javascript
test('un golden mas estrecho que su viewport falla si la captura es de pantalla completa', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const scenario = manifest.scenarios.find((s) => s.viewport.width === 1180);
    scenario.viewport = { width: 1440, height: 900 };
    scenario.golden = scenario.golden.replace('1180x820', '1440x900');
    scenario.id = scenario.id.replace('1180x820', '1440x900');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /golden mide 1180x820 px, no coincide con el viewport declarado 1440x900/);
});

test('un recorte a elemento declarado no exige coincidencia exacta', () => {
  const result = runFixture(() => {});
  assert.equal(result.status, 0, result.stderr || result.stdout);
  assert.equal(/states-feedback.*golden mide/.test(result.stderr || ''), false);
});
```

La segunda prueba vale porque los dos escenarios de `states-feedback` son recortes reales: si la regla nueva no los exceptuara, el fixture sin mutar se pondría rojo.

- [ ] **Step 2: Correr y verificar que la primera falla**

```bash
node --test tests/design-system/contracts.test.mjs
```

Esperado: FAIL en la primera (el gate hoy acepta un golden más estrecho). La segunda puede pasar ya; es una prueba de no-regresión sobre los recortes existentes.

- [ ] **Step 3: Añadir el campo `capture` al esquema**

En `docs/design-system/module-manifest.schema.json`, dentro de las propiedades del escenario:

```json
    "capture": {
      "enum": ["viewport", "element"],
      "description": "viewport: captura de pantalla completa, el PNG mide exactamente el viewport declarado. element: recorte a un elemento, el PNG puede ser menor. Si se omite, viewport."
    },
```

No lo añadas al array `required` del escenario: omitirlo debe significar `viewport`, para no tener que editar los 37 escenarios que ya cumplen.

- [ ] **Step 4: Declarar los dos recortes reales**

En `docs/design-system/manifests/laboratory.json`, añadir `"capture": "element"` a los escenarios `states-feedback-dark-1180x820` y `states-feedback-dark-1440x900`. Solo a esos dos.

- [ ] **Step 5: Endurecer la regla en el gate**

Sustituir la comprobación de ancho con `<=` por una que distinga los dos casos. Para `capture: "viewport"` (o ausente), el ancho **y el alto** del PNG deben coincidir exactamente con el viewport declarado. Para `capture: "element"`, ambos deben ser menores o iguales.

El mensaje de fallo del caso exacto debe ser `golden mide <w>x<h> px, no coincide con el viewport declarado <W>x<H>`, porque la prueba del Step 1 lo comprueba literalmente.

Ojo con el alto: hoy no se comprueba. Al exigirlo, los 37 escenarios de pantalla completa deben cumplirlo — si alguno no lo cumple, **párate y repórtalo con la lista**: significaría que hay más recortes sin declarar, y decidir si son legítimos es de Felipe, no tuyo.

- [ ] **Step 6: Correr y verificar que pasan**

```bash
node --test tests/design-system/contracts.test.mjs && node scripts/design-system-contracts.mjs
```

Esperado: PASS en ambos.

- [ ] **Step 7: La mutación adversarial, obligatoria**

Reproduce el caso que motivó el pendiente y pega la salida en el informe:

```bash
node --input-type=module -e 'import {readFileSync, writeFileSync} from "node:fs"; const b=Buffer.alloc(0); const {execSync}=await import("node:child_process"); execSync("sips -s format png --resampleHeightWidth 844 390 tests/browser/__screenshots__/programa-general.visual.mjs/programa-general-dark-1180x820.png --out /tmp/falso.png >/dev/null 2>&1")'
cp /tmp/falso.png tests/browser/__screenshots__/programa-general.visual.mjs/programa-general-dark-1180x820.png
node --input-type=module -e 'import {readFileSync, writeFileSync} from "node:fs"; import {createHash} from "node:crypto"; const p="docs/design-system/manifests/programa-general.json"; const m=JSON.parse(readFileSync(p,"utf8")); const s=m.scenarios.find((x)=>x.viewport.width===1180); s.sha256=createHash("sha256").update(readFileSync(s.golden)).digest("hex"); writeFileSync(p, JSON.stringify(m,null,2)+"\n")'
node scripts/design-system-contracts.mjs
git checkout docs/design-system/manifests/programa-general.json tests/browser/__screenshots__/programa-general.visual.mjs/programa-general-dark-1180x820.png
git status --short
```

Esperado: el gate **falla** con el mensaje de dimensiones, y `git status --short` queda vacío. Si el gate pasa, la regla no cierra el caso y hay que apretarla. Si `sips` no está disponible, genera el PNG de 390x844 como prefieras y dilo en el informe; lo que importa es que sea un PNG real de ese tamaño con contenido distinto de cualquier golden.

- [ ] **Step 8: Commit**

```bash
git add docs/design-system/module-manifest.schema.json docs/design-system/manifests/laboratory.json scripts/design-system-contracts.mjs tests/design-system/contracts.test.mjs
git commit -m "feat(design-system): el golden debe medir exactamente su viewport salvo recorte declarado"
```

---

### Task 2: Saldar la deuda de versión y restaurar la derivación (P-B)

11 manifiestos declaran `designSystemVersion: 1.0.0` con `version.json` en `1.1.0`. No es descuido: la campaña que publicó 1.1.0 subió exactamente los 6 manifiestos que el gate miraba entonces, y los otros 11 quedaron fuera porque nadie los revisaba. F2a-1 amplió el gate a los 15 y esos 11 pasan ahora todas sus comprobaciones —rutas, componentes, vendors, escenarios, goldens y hashes— salvo la de versión, que sigue apagada para ellos porque `required` se dejó sin derivar.

Subirlos es afirmar algo que el gate ya verifica, no un renombrado.

**Files:**
- Modify: los 11 manifiestos en `docs/design-system/manifests/`
- Modify: `scripts/design-system-contracts.mjs` (la lista `required` y su comentario)
- Test: `tests/design-system/contracts.test.mjs`

**Interfaces:**
- Consumes: nada de la Task 1.
- Produces: los 17 manifiestos declaran `1.1.0` y todos pasan por el chequeo de versión.

- [ ] **Step 1: Escribir la prueba que falla**

```javascript
test('todo manifiesto del inventario pasa por el chequeo de version', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/subcontratistas.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.designSystemVersion = '9.9.9';
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /subcontratistas\.json: designSystemVersion must equal/);
});
```

`subcontratistas.json` se elige a propósito: es uno de los 11 que hoy están fuera del chequeo.

- [ ] **Step 2: Correr y verificar que falla**

```bash
node --test tests/design-system/contracts.test.mjs
```

Esperado: FAIL — hoy mutar ese archivo no produce ningún fallo.

- [ ] **Step 3: Subir los 11 manifiestos a 1.1.0**

```bash
node --input-type=module -e '
import {readFileSync, writeFileSync} from "node:fs";
const version = JSON.parse(readFileSync("docs/design-system/version.json","utf8")).version;
const inv = JSON.parse(readFileSync("docs/design-system/manifests/inventory.json","utf8"));
for (const f of inv.manifests) {
  const p = "docs/design-system/manifests/" + f;
  const m = JSON.parse(readFileSync(p, "utf8"));
  if (m.designSystemVersion === version) continue;
  m.designSystemVersion = version;
  writeFileSync(p, JSON.stringify(m, null, 2) + "\n");
  console.log("subido:", f);
}'
```

Esperado: 11 líneas. **Comprueba con `git diff` que en cada archivo cambió una sola línea** — si el reserializado a JSON altera el formato de algún manifiesto (orden de claves, indentación, comillas), párate: reescribir 11 contratos por un cambio de formato es exactamente el ruido que hace irrevisable un diff. En ese caso edítalos a mano.

- [ ] **Step 4: Derivar `required` para los manifiestos**

En `scripts/design-system-contracts.mjs`, hacer que los manifiestos del inventario entren en `documents`, que es lo que alimenta el chequeo de versión. Y corregir el comentario que explica por qué estaban fuera: ya no lo están.

- [ ] **Step 5: Correr y verificar que pasan**

```bash
node --test tests/design-system/contracts.test.mjs && node scripts/design-system-contracts.mjs && npm run test:design-system:static
```

Esperado: todo en verde, ocho puertas.

- [ ] **Step 6: Medir qué cobertura se gana**

La segunda regla de esta fase: todo paso que cambie una lista mide qué cambia. Muta la versión de **dos** manifiestos que antes estaban dentro (`laboratory.json`) y fuera (`auth.json`) del chequeo, comprueba que ahora **ambos** fallan, y revierte. Pega las dos salidas en el informe.

- [ ] **Step 7: Commit**

```bash
git add docs/design-system/manifests/ scripts/design-system-contracts.mjs tests/design-system/contracts.test.mjs
git commit -m "fix(design-system): los 17 manifiestos declaran 1.1.0 y pasan por el chequeo de version"
```

---

### Task 3: Limpiar los seis minors (P-C)

Seis observaciones registradas en las revisiones de F2a-1. Ninguna rompe nada hoy; todas estorban a quien venga después. Van juntas porque comparten archivo y ninguna merece su propio ciclo de revisión.

**Files:**
- Modify: `scripts/design-system-contracts.mjs`
- Modify: `tests/design-system/contracts.test.mjs`
- Modify: `tests/design-system/manifest-sources.mjs`
- Modify: `tests/design-system/accessibility.test.mjs`
- Modify: `docs/design-system/manifests/inventory.json`

**Interfaces:**
- Consumes: las Tasks 1 y 2 ya aplicadas.

- [ ] **Step 1: El nullish que falta**

En `scripts/design-system-contracts.mjs`, el bucle de escenarios usa `scenario.viewport?.width` en un punto y `scenario.viewport.width` sin `?.` en otro. Un escenario sin `viewport` revienta con `TypeError` en vez de dar un fallo legible — y el piloto va a escribir escenarios a mano. Únificalo con `?.` y añade un fallo explícito si el escenario no declara viewport:

```javascript
    if (!scenario.viewport?.width || !scenario.viewport?.height) {
      failures.push(`${manifest.moduleId}/${scenario.id}: scenario must declare a viewport`);
      continue;
    }
```

Colócalo antes del primer uso de `scenario.viewport`.

- [ ] **Step 2: El comentario invertido**

En `tests/design-system/contracts.test.mjs`, el comentario del candado que vigila la cobertura de manifiestos dice que si la lista se encogiera «esta prueba pasaría en verde por la razón equivocada». Es al revés: la prueba **falla**, y así se verificó. Corrige el comentario para que describa el mecanismo real.

- [ ] **Step 3: El `existsSync` ausente**

En `tests/design-system/manifest-sources.mjs`, la función compartida copia archivos de test sin comprobar que existan, así que un manifiesto que referencie un test inexistente revienta con `ENOENT` en vez de producir el `missing test ...` del gate. Añade el `existsSync` con el mismo criterio que ya se documentó: si un contrato referencia un archivo que no existe, ese fallo lo debe reportar el gate, no ocultarlo ni hacer estallar el harness.

- [ ] **Step 4: Las dos vistas borradas del inventario**

En `docs/design-system/manifests/inventory.json`, `sharedHeadConsumers` sigue listando `views/contratos/contratos.view.php` y `views/listado-actividades/listadoActividades.view.php`, borradas con el PDC v1 el 2026-08-04. Quítalas. **Comprueba antes con `ls` que efectivamente no existen** — si alguna existe, no la toques y dilo.

- [ ] **Step 5: La doble candidata aprobada**

En `tests/design-system/accessibility.test.mjs`, el emparejamiento familia↔aprobación usa `.find(({ status }) => status === 'approved')`. La familia `shell-navigation` tiene **dos** candidatas aprobadas (`adaptive-shell` y `sidebar-shell`), así que el resultado depende del orden del JSON, y hoy contrasta la que **no** es `activeCandidate`. No falla porque ambas declaran los mismos viewports.

Hazlo determinista y explícito: si hay varias candidatas aprobadas, comprueba **todas** sus aprobaciones firmadas contra los viewports de la familia, en vez de elegir una por orden implícito. Así deja de depender del orden y además cubre más.

- [ ] **Step 6: La cobertura atada al array del inventario**

Queda anotado, no se arregla aquí: borrar una entrada de `inventory.manifests` saca ese manifiesto del gate sin producir fallo. Ya existe un candado que compara el inventario con los archivos reales del directorio (`scripts/design-system-contracts.mjs`, bloque de `actualManifests`). **Comprueba si ese candado ya cubre el caso**: muta el inventario quitando una entrada y mira si el gate falla. Si falla, este minor está cerrado por construcción y basta con anotarlo en el informe; si no falla, dilo y déjalo abierto — cerrarlo es una decisión de alcance, no tuya.

- [ ] **Step 7: Verificar y commitear**

```bash
npm run test:design-system:static
```

Esperado: ocho puertas en verde.

```bash
git add scripts/design-system-contracts.mjs tests/design-system/ docs/design-system/manifests/inventory.json
git commit -m "chore(design-system): saldar los seis minors registrados en F2a-1"
```

---

## Condición de hecho de F2a-2a

1. `npm run test:design-system:static` pasa sus ocho puertas.
2. Un PNG real de `390x844` declarado como evidencia de un escenario de escritorio hace fallar el gate (Task 1, Step 7).
3. Mutar la versión de cualquiera de los 17 manifiestos hace fallar el gate (Task 2, Step 6).
4. Los 17 manifiestos declaran la misma versión que `version.json`.
5. Los seis minors del ledger están cerrados o explicados por escrito.
6. Ningún golden fue creado, regenerado, renombrado ni borrado: `git diff --name-only <base>..HEAD -- '*.png'` vacío.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** p4-movil-y-tema-claro.md:23-25 «MO-F2a-2a (DS-033)»; DS-033 en docs/design-system/decisions.md

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
