# DS-F1a · La escala de estado — plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usá `superpowers:subagent-driven-development`
> (recomendada) o `superpowers:executing-plans` para ejecutar este plan tarea a tarea. Los pasos
> usan casillas (`- [ ]`) para seguimiento.

**Goal:** publicar el contrato de la escala de estado —vocabulario, tres niveles de gravedad y la
regla de los dos canales— en formato legible y consultable por máquina, con una prueba que lo
vigile.

**Architecture:** el contrato vive en dos archivos que se validan entre sí: un JSON que es la
fuente ejecutable y un Markdown que lo explica. Una prueba de `node:test` comprueba el JSON contra
sus propias reglas y contra el Markdown, de modo que separarlos rompe la suite. **No se toca
`state-semantics.json`**: el contrato nuevo convive con él hasta que el saneo sea una decisión
explícita del usuario.

**Tech Stack:** JSON, Markdown, `node:test` (sin dependencias nuevas). La suite ya recoge por glob
cualquier `tests/design-system/*.test.mjs`, así que la prueba entra sola en
`npm run test:design-system:static`.

**Spec:** `docs/superpowers/specs/2026-08-19-ds-f1a-estado-design.md`, aprobado por el usuario el
2026-08-19.

## Global Constraints

- **Cero cambios en código de producto.** Nada bajo `public/`, `src/`, `views/`, `admin/`.
- **No tocar `docs/design-system/state-semantics.json`** ni ningún baseline ni golden.
- **Sin dependencias nuevas**: solo builtins de Node.
- **Prefijo `ds-f1a` en todo `.md` nuevo**, por la colisión de wikilinks del vault-en-raíz
  (`memoria/trampas/vault-en-raiz-colisiona-nombres-de-archivo.md`).
- **Los tres niveles son `urgente`, `atencion`, `controlado`.** `fuera-de-ventana` y `sin-datos`
  **no son niveles**: son estados sin gravedad.
- **Las dos asignaciones marcadas `revocable: true` conservan esa marca.** Quitarla es decisión del
  usuario, no del implementador.
- **Las `etiqueta` son los literales EXACTOS de la columna `Estado`**, con tildes, verificados
  contra la base sobre `1af2e9ac`. Un contrato cuya etiqueta no case con el literal guardado no
  puede unirse con los datos. (Corregido durante la ejecución: el plan escribía «En Liberacion»
  sin tilde.)
- **Porcentajes medidos sobre 50 966 actividades reales** (65 549 filas de `programa_consolidado`
  menos 14 583 capítulos), base `1af2e9ac`. No se recalculan en este plan.

---

### Task 1: El contrato ejecutable y su prueba

**Files:**
- Create: `docs/design-system/ds-f1a-escala-estado.json`
- Test: `tests/design-system/ds-f1a-escala-estado.test.mjs`

**Interfaces:**
- Consumes: nada. Es la primera tarea.
- Produces: el archivo `docs/design-system/ds-f1a-escala-estado.json` con las claves de nivel
  superior `version`, `base`, `canales`, `niveles` y `estados`. La Task 2 lee `estados[].id`,
  `estados[].etiqueta`, `estados[].nivel` y `estados[].porcentaje`; la Task 3 lee `canales`.

- [ ] **Step 1: Escribir la prueba que falla**

Crear `tests/design-system/ds-f1a-escala-estado.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');
const contrato = async () => JSON.parse(await read('docs/design-system/ds-f1a-escala-estado.json'));

const NIVELES = ['urgente', 'atencion', 'controlado'];
const SIN_GRAVEDAD = ['fuera-de-ventana', 'sin-datos'];

test('los niveles de gravedad son exactamente tres', async () => {
  const c = await contrato();
  assert.deepEqual(Object.keys(c.niveles), NIVELES);
});

test('cada estado declara un nivel conocido o se declara sin gravedad', async () => {
  const c = await contrato();
  for (const e of c.estados) {
    if (e.nivel === null) {
      assert.ok(SIN_GRAVEDAD.includes(e.id), `${e.id} sin nivel y no esta en SIN_GRAVEDAD`);
    } else {
      assert.ok(NIVELES.includes(e.nivel), `${e.id} declara nivel desconocido: ${e.nivel}`);
    }
  }
});

test('solo urgente y atencion dibujan barra; controlado es la ausencia', async () => {
  const c = await contrato();
  assert.equal(c.niveles.urgente.barra, true);
  assert.equal(c.niveles.atencion.barra, true);
  assert.equal(c.niveles.controlado.barra, false);
});

test('ningun id de estado se repite', async () => {
  const c = await contrato();
  const ids = c.estados.map((e) => e.id);
  assert.equal(new Set(ids).size, ids.length);
});

// Trece porcentajes redondeados a una decima no suman 100 exacto: la medicion real
// da 99,9. La tolerancia es de medio punto para que el redondeo no haga fallar la
// prueba, y sigue siendo suficientemente estrecha para cazar un estado olvidado
// -el mas pequeno que no es cero vale 0,1- o uno contado dos veces.
test('los porcentajes suman 100 con medio punto de tolerancia', async () => {
  const c = await contrato();
  const suma = c.estados.reduce((a, e) => a + e.porcentaje, 0);
  assert.ok(Math.abs(suma - 100) <= 0.5, `suman ${suma.toFixed(1)}, no 100`);
});

test('las dos asignaciones propuestas conservan su marca de revocables', async () => {
  const c = await contrato();
  const revocables = c.estados.filter((e) => e.revocable === true).map((e) => e.id);
  assert.deepEqual(revocables.sort(), ['debe-iniciar-esta-semana', 'en-liberacion-de-restricciones']);
});

// El spec exige que cada estado declare su origen. No es adorno: siete de los trece
// los produce `pg_calculate_status` y seis no los produce nadie hoy, y esa diferencia
// decide si un estado sobrevive al proximo recalculo o se borra solo.
test('cada estado declara quien lo produce', async () => {
  const c = await contrato();
  const ORIGENES = ['pg_calculate_status', 'legacy-sin-productor'];
  for (const e of c.estados) {
    assert.ok(ORIGENES.includes(e.origen), `${e.id} declara origen desconocido: ${e.origen}`);
  }
});

test('cada canal declara que dato transporta', async () => {
  const c = await contrato();
  assert.equal(c.canales.fondo.transporta, 'identidad-y-horizonte');
  assert.equal(c.canales.barra.transporta, 'gravedad');
});
```

- [ ] **Step 2: Correr la prueba y comprobar que falla**

```bash
node --test tests/design-system/ds-f1a-escala-estado.test.mjs
```

Esperado: FALLA en los ocho casos con `ENOENT` — el JSON todavía no existe.

- [ ] **Step 3: Escribir el contrato**

Crear `docs/design-system/ds-f1a-escala-estado.json`:

```json
{
  "version": "1.0.0-f1a",
  "base": "1af2e9ac",
  "medicion": "50966 actividades reales de programa_consolidado (65549 filas menos 14583 capitulos), 16 proyectos",
  "canales": {
    "fondo": {
      "transporta": "identidad-y-horizonte",
      "porque": "fuera-de-ventana, actividad-futura y terminada tienen urgencia cero las tres; si el fondo codificara gravedad se pintarian igual y se perderia la distincion de horizonte"
    },
    "barra": {
      "transporta": "gravedad",
      "posicion": "borde de la fila",
      "porque": "solo aparece en el 21,3% que pide algo; la ausencia de barra es la senal de que no pide nada"
    }
  },
  "niveles": {
    "urgente": { "orden": 1, "barra": true, "accion": "Atender ahora" },
    "atencion": { "orden": 2, "barra": true, "accion": "Revisar antes del siguiente hito" },
    "controlado": { "orden": 3, "barra": false, "accion": "Continuar segun el ciclo normal" }
  },
  "estados": [
    { "id": "atrasada", "etiqueta": "Atrasada", "nivel": "urgente", "porcentaje": 8.0, "origen": "pg_calculate_status" },
    { "id": "debe-iniciar-esta-semana-y-restricciones-pendientes", "etiqueta": "Debe Iniciar esta Semana y Restricciones Pendientes", "nivel": "urgente", "porcentaje": 1.5, "origen": "legacy-sin-productor" },
    { "id": "en-liberacion-de-restricciones", "etiqueta": "En Liberación de Restricciones", "nivel": "atencion", "porcentaje": 10.7, "origen": "legacy-sin-productor", "revocable": true, "nota": "asignacion propuesta por la spec, no decidida por el usuario: parece el sustituto vivo de Con Alerta Restricciones, que el contrato declara como atencion y que no existe en ninguna fila" },
    { "id": "debe-iniciar", "etiqueta": "Debe Iniciar", "nivel": "atencion", "porcentaje": 0.9, "origen": "pg_calculate_status" },
    { "id": "debe-iniciar-esta-semana", "etiqueta": "Debe Iniciar esta Semana", "nivel": "atencion", "porcentaje": 0.2, "origen": "legacy-sin-productor", "revocable": true, "nota": "asignacion propuesta por la spec: lo separa de su hermano con restricciones pendientes el que algo lo bloquee; si en obra le toca esta semana ya es urgente, se mueve" },
    { "id": "actividad-futura", "etiqueta": "Actividad Futura", "nivel": "controlado", "porcentaje": 33.6, "origen": "pg_calculate_status" },
    { "id": "terminada", "etiqueta": "Terminada", "nivel": "controlado", "porcentaje": 19.0, "origen": "pg_calculate_status" },
    { "id": "en-curso", "etiqueta": "En Curso", "nivel": "controlado", "porcentaje": 0.7, "origen": "pg_calculate_status" },
    { "id": "terminada-antes", "etiqueta": "Terminada Antes", "nivel": "controlado", "porcentaje": 0.6, "origen": "legacy-sin-productor" },
    { "id": "a-tiempo", "etiqueta": "A Tiempo", "nivel": "controlado", "porcentaje": 0.4, "origen": "legacy-sin-productor" },
    { "id": "adelantada", "etiqueta": "Adelantada", "nivel": "controlado", "porcentaje": 0.0, "origen": "legacy-sin-productor" },
    { "id": "fuera-de-ventana", "etiqueta": "Fuera de Ventana", "nivel": null, "porcentaje": 24.2, "origen": "legacy-sin-productor", "definicion": "actividad que comienza en 7 semanas o mas respecto a la fecha de inicio de la semana actual, es decir fuera de PG_LOOKAHEAD_DAYS = 42", "sustituye": "No Requerida", "pendiente": "declarar si es etiqueta de pantalla o valor persistido" },
    { "id": "sin-datos", "etiqueta": "Sin Datos", "nivel": null, "porcentaje": 0.1, "origen": "pg_calculate_status" }
  ]
}
```

- [ ] **Step 4: Correr la prueba y comprobar que pasa**

```bash
node --test tests/design-system/ds-f1a-escala-estado.test.mjs
```

Esperado: `pass 8`, `fail 0`.

- [ ] **Step 5: Comprobar que entra sola en la suite**

```bash
npm run test:design-system:static
```

Esperado: RC=0 y el paso `node-tests` en verde. La prueba entra por el glob de
`scripts/design-system-static-suite.mjs:13`, sin registrarla en ningún sitio.

- [ ] **Step 6: Commit**

```bash
git add docs/design-system/ds-f1a-escala-estado.json tests/design-system/ds-f1a-escala-estado.test.mjs
git commit -m "feat(ds-f1a): el contrato ejecutable de la escala de estado, con la prueba que lo vigila"
```

---

### Task 2: El contrato legible, atado al ejecutable

**Files:**
- Create: `docs/design-system/ds-f1a-escala-estado.md`
- Modify: `tests/design-system/ds-f1a-escala-estado.test.mjs` (añadir un caso al final)

**Interfaces:**
- Consumes: de Task 1, `estados[].etiqueta` y `estados[].nivel` del JSON.
- Produces: `docs/design-system/ds-f1a-escala-estado.md`, que la Task 3 enlaza desde el índice.

- [ ] **Step 1: Escribir la prueba que falla**

Añadir al final de `tests/design-system/ds-f1a-escala-estado.test.mjs`:

```javascript
test('el markdown nombra cada estado del contrato con su etiqueta exacta', async () => {
  const c = await contrato();
  const md = await read('docs/design-system/ds-f1a-escala-estado.md');
  for (const e of c.estados) {
    assert.ok(md.includes(e.etiqueta), `el markdown no nombra "${e.etiqueta}"`);
  }
});
```

- [ ] **Step 2: Correr la prueba y comprobar que falla**

```bash
node --test tests/design-system/ds-f1a-escala-estado.test.mjs
```

Esperado: 8 pasan, 1 falla con `ENOENT` sobre `ds-f1a-escala-estado.md`.

- [ ] **Step 3: Escribir el contrato legible**

Crear `docs/design-system/ds-f1a-escala-estado.md`. Debe contener, y en este orden:

1. Un encabezado que diga que la fuente ejecutable es el JSON hermano y que **si divergen, manda
   el JSON** — la misma jerarquía que `DESIGN.md` usa con `tokens.css`.
2. La regla de los dos canales, con la tabla `fondo` / `barra` del spec §El diseño.
3. El párrafo «por qué el fondo no puede llevar la gravedad», copiado del spec: `Fuera de Ventana`
   (24,2%), `Actividad Futura` (33,6%) y `Terminada` (19,0%) tienen urgencia cero las tres.
4. La tabla de niveles con la columna «cómo se ve»: urgente → barra fuerte, atención → barra media,
   controlado → **sin barra**.
5. La tabla de los trece estados con su etiqueta exacta, su nivel y su porcentaje, tomados del JSON.
6. Una sección «Lo que este contrato no decide» con los tres límites del spec: no sanea
   `state-semantics.json`, no decide qué pasa con `Capítulo`, y no implementa CSS.
7. Una sección «Pendiente de decisión» con el punto abierto: si `Fuera de Ventana` es etiqueta de
   pantalla o valor persistido, y qué implica cada opción.

- [ ] **Step 4: Correr la prueba y comprobar que pasa**

```bash
node --test tests/design-system/ds-f1a-escala-estado.test.mjs
```

Esperado: `pass 9`, `fail 0`.

- [ ] **Step 5: Comprobar el lint de la wiki**

```bash
npm run test:wiki
```

Esperado: ningún hallazgo nuevo atribuible a este archivo. La alarma de veracidad (68 commits
desde el pase del 2026-08-18) **es preexistente y no la resuelve esta tarea**; si aparece sola, se
considera verde para este plan.

- [ ] **Step 6: Commit**

```bash
git add docs/design-system/ds-f1a-escala-estado.md tests/design-system/ds-f1a-escala-estado.test.mjs
git commit -m "docs(ds-f1a): el contrato legible de la escala de estado, atado al JSON por prueba"
```

---

### Task 3: Enlazar el contrato desde el índice del design system

**Files:**
- Modify: `docs/design-system/README.md` (sección «Archivos canonicos»)

**Interfaces:**
- Consumes: de Task 1 y 2, las rutas de los dos archivos del contrato.
- Produces: nada que consuma otra tarea. Es la última.

**Antes de empezar:** `docs/design-system/README.md` **está fuera de las rutas declaradas por este
frente** y dentro de las que declaró `wiki-t2` (`docs/**`). Ampliar el frente con
`cas-frente.sh` y avisar a la coordinadora **antes** de editarlo. Si la coordinadora lo deniega,
esta tarea se salta y el plan se da por completo con las dos primeras.

- [ ] **Step 1: Ampliar las rutas del frente**

```bash
bash <ruta-de-cas-frente.sh> --sin-plan --verificacion "npm run test:design-system:static" \
  ds-f1a-estado "docs/superpowers/specs/*-ds-f1a-*,docs/superpowers/plans/*-ds-f1a-*,goals/ds-f1a-estado/**,docs/design-system/ds-f1a-*,tests/design-system/ds-f1a-*,docs/design-system/README.md" <sesion>
```

- [ ] **Step 2: Añadir las dos entradas al índice**

En `docs/design-system/README.md`, dentro de la lista «Archivos canonicos», añadir:

```markdown
- `ds-f1a-escala-estado.json` y `ds-f1a-escala-estado.md`: contrato de la escala de estado
  (DS-F1a). El JSON es la fuente ejecutable y el Markdown lo explica; si divergen, manda el JSON, y
  `tests/design-system/ds-f1a-escala-estado.test.mjs` comprueba que no diverjan. Convive con
  `state-semantics.json`, que este contrato **no** sustituye todavía.
```

- [ ] **Step 3: Verificar la suite completa**

```bash
npm run test:design-system:static
```

Esperado: RC=0, 8/8 pasos en verde.

- [ ] **Step 4: Commit**

```bash
git add docs/design-system/README.md
git commit -m "docs(ds-f1a): el índice del design system nombra el contrato de la escala de estado"
```

---

## Cierre del frente

No es una tarea del plan: es el gate de `AGENTS.md` §Publicación, que se aplica igual.

- [ ] Verificar la condición de hecho con salida real: `npm run test:design-system:static` y
      `npm run test:wiki`.
- [ ] `git status` limpio.
- [ ] `git fetch origin` y mirar la divergencia.
- [ ] Integrar si la hay, resolviendo a la vista.
- [ ] **Re-verificar después de integrar, no antes.** Anotar el sha.
- [ ] Pedir el visto a la coordinadora con el sha medido.
- [ ] Publicar el sha exacto visado, en comando aparte.
- [ ] Confirmar que `origin/main` coincide con el sha anotado.
- [ ] Anotar el cierre en `goals/ds-f1a-estado/goal.md`.
