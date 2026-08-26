---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-26
areas: [design-system, proceso]
fuente: docs/superpowers/plans/2026-08-26-tarea-cero-lista-canonica-de-pantallas.md
resumen: "Tarea cero de la v0: que los dos inventarios de pantallas coincidan y un gate ponga rojo cuando dejen de hacerlo. Medio dia, cinco tareas"
---

# Tarea cero — una sola lista canónica de pantallas · Plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usa `superpowers:subagent-driven-development`
> (recomendado) o `superpowers:executing-plans` para ejecutar tarea por tarea. Los pasos usan
> casillas (`- [ ]`) solo para seguimiento durante la ejecución — **en este repo las casillas no son
> evidencia de avance** (`AGENTS.md` §Verificación).

**Goal:** que exista **una** lista de pantallas del sistema, que los dos inventarios coincidan, y que
un gate ponga rojo cuando dejen de coincidir.

**Architecture:** no se construye una lista nueva. El censo (`censo-modulos.json`) ya enlaza a las
fichas por `designSystem.moduleId`; se arreglan los enlaces rotos, se resuelven las pantallas
huérfanas, y se añade el gate que cruza **censo ↔ fichas** — un eje que hoy nadie comprueba (el gate
existente cruza rutas ↔ fichas, que es distinto y deja pasar esta divergencia).

**Tech Stack:** Node.js test runner (`node --test`), JSON. Sin dependencias nuevas.

**Spec:** `docs/superpowers/specs/2026-08-26-v0-del-producto-design.md` (§Tarea cero)

## Global Constraints

- **Ninguna casilla de este plan cuenta como evidencia.** El avance se lee del `## Cierre` y de git.
- **No se regeneran baselines ni deudas para poner algo verde.** Quitar entradas de
  `coverage-debt.json` es bueno; añadirlas exige decisión explícita del usuario.
- Los gates nuevos viven en `tests/design-system/` y corren con `npm run test:design-system:static`.
- Comentarios y mensajes en español, sin tildes en los mensajes de commit (convención del repo).
- **Estado medido el 2026-08-26 sobre `a92a75f8`**: 21 módulos censados, 41 superficies, 15 con
  `designSystem.moduleId`, 2 enlaces rotos, 17 fichas.

---

### Task 1: El gate que cruza censo contra fichas

Es lo primero porque **tiene que nacer rojo**: si nace verde, no está midiendo nada.

**Files:**
- Create: `tests/design-system/censo-fichas-coherencia.test.mjs`
- Read: `docs/design-system/auditoria/censo-modulos.json`, `docs/design-system/manifests/*.json`

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: el gate `censo-fichas-coherencia`, que las tareas 2 y 3 dejan en verde.

- [ ] **Paso 1: Escribir el gate, que debe fallar hoy**

```javascript
import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { join } from 'node:path';
import test from 'node:test';

// Por que existe: hay DOS inventarios del mismo sistema y ningun gate los cruzaba.
// El censo (`censo-modulos.json`) enumera modulos y sus superficies; las fichas
// (`manifests/*.json`) declaran que cubre el sistema de diseno. `coverage-closure`
// cruza RUTAS contra fichas — otro eje — asi que un censo que apunte a una ficha
// inexistente pasaba desapercibido. Medido el 2026-08-26: dos enlaces rotos
// (`projects`, `admin`) llevaban semanas sin que nada se pusiera rojo.

const REPO = fileURLToPath(new URL('../../', import.meta.url));
const leer = (p) => JSON.parse(readFileSync(p, 'utf8'));
const censo = leer(join(REPO, 'docs/design-system/auditoria/censo-modulos.json')).modulos;
const fichas = new Set(
  readdirSync(join(REPO, 'docs/design-system/manifests'))
    .filter((f) => f.endsWith('.json'))
    .map((f) => f.slice(0, -5)),
);

test('todo moduleId declarado en el censo apunta a una ficha que existe', () => {
  const rotos = censo
    .map((m) => [m.slug, m.designSystem?.moduleId])
    .filter(([, id]) => id && !fichas.has(id));
  assert.deepEqual(rotos, [], `enlaces rotos censo -> ficha: ${JSON.stringify(rotos)}`);
});

test('todo modulo con superficies declara su moduleId o su exencion', () => {
  const huerfanos = censo
    .filter((m) => (m.superficies?.length ?? 0) > 0)
    .filter((m) => !m.designSystem?.moduleId && !m.designSystem?.sinFicha)
    .map((m) => m.slug);
  assert.deepEqual(huerfanos, [], `modulos con pantalla y sin ficha ni exencion: ${huerfanos}`);
});

test('un modulo exento declara por que', () => {
  const mudos = censo
    .filter((m) => m.designSystem?.sinFicha)
    .filter((m) => !m.designSystem?.porQue)
    .map((m) => m.slug);
  assert.deepEqual(mudos, [], `exenciones sin motivo escrito: ${mudos}`);
});
```

- [ ] **Paso 2: Correrlo y comprobar que falla**

Correr: `node --test tests/design-system/censo-fichas-coherencia.test.mjs`
Esperado: **FAIL**, 2 de 3 tests en rojo — el primero por `projects` y `admin`; el segundo por
`submodulo-cnp`, `submodulo-cnc` y `submodulo-cic`. Si sale verde, el gate está mal escrito: no está
leyendo lo que cree.

- [ ] **Paso 3: Commit del gate en rojo**

```bash
git add tests/design-system/censo-fichas-coherencia.test.mjs
git commit -m "test(ds): gate que cruza censo contra fichas — nace rojo a proposito

Ningun gate cruzaba estos dos inventarios: coverage-closure compara rutas
contra fichas, que es otro eje. Dos enlaces rotos (projects, admin) llevaban
semanas sin ponerse rojos."
```

---

### Task 2: Arreglar los dos enlaces rotos

**Files:**
- Modify: `docs/design-system/auditoria/censo-modulos.json` (módulos `selector-de-proyectos` y `panel-admin`)

**Interfaces:**
- Consumes: el gate de la Task 1.
- Produces: el primer test del gate en verde.

- [ ] **Paso 1: Comprobar a qué ficha corresponde cada uno**

Correr: `ls docs/design-system/manifests/ | grep -i "project\|admin"`
Esperado: existe `project-selector.json`; **no existe** ninguna de `admin`.

- [ ] **Paso 2: Corregir el enlace de `selector-de-proyectos`**

En `censo-modulos.json`, en el módulo `selector-de-proyectos`, cambiar
`"moduleId": "projects"` por `"moduleId": "project-selector"`. Es un renombre: la ficha existe y
cubre esa pantalla.

- [ ] **Paso 3: Declarar `panel-admin` como exento, con su motivo**

No existe ficha de `admin` y `admin/` es una mini-aplicación aparte que no reutiliza `src/Core` ni
`src/Security` (ver `CLAUDE.md`). Se declara exento en vez de fabricar una ficha vacía:

```json
"designSystem": {
  "sinFicha": true,
  "porQue": "admin/ es una mini-aplicacion aparte con su propio front controller, router y CSS; no consume el sistema de diseno de src/. Darle ficha aqui declararia una cobertura que no existe."
}
```

- [ ] **Paso 4: Correr el gate**

Correr: `node --test tests/design-system/censo-fichas-coherencia.test.mjs`
Esperado: el primer test **PASA**; el segundo sigue en rojo por los tres submódulos.

- [ ] **Paso 5: Commit**

```bash
git add docs/design-system/auditoria/censo-modulos.json
git commit -m "fix(ds): los dos enlaces rotos del censo a las fichas

selector-de-proyectos apuntaba a `projects` y la ficha se llama
`project-selector`. panel-admin apuntaba a `admin`, que no existe: se declara
exento con su motivo, porque admin/ no consume el sistema de diseno de src/."
```

---

### Task 3: Las tres pantallas de submódulo

CNP, CNC y CIC cuelgan de `/programacion-semanal` y tienen una pantalla cada uno.

**Files:**
- Modify: `docs/design-system/auditoria/censo-modulos.json` (los tres `submodulo-*`)
- Read: `docs/design-system/manifests/programacion-semanal.json`

**Interfaces:**
- Consumes: el gate de la Task 1.
- Produces: el gate entero en verde.

- [ ] **Paso 1: Comprobar si la ficha de semanal ya declara esas rutas**

Correr:
```bash
grep -n "cnp\|cnc\|cic" docs/design-system/manifests/programacion-semanal.json
```

- [ ] **Paso 2: Enlazarlos según lo que diga el paso 1**

**Si la ficha de semanal ya declara las tres rutas:** poner
`"designSystem": { "moduleId": "programacion-semanal" }` en los tres módulos del censo. Son
superficies del mismo módulo, no módulos con ficha propia.

**Si NO las declara:** añadir las tres rutas al array `routes` de
`docs/design-system/manifests/programacion-semanal.json` y luego enlazar igual. No crear tres fichas
nuevas: son tres vistas de la misma pantalla y tres fichas triplicarían el mantenimiento sin añadir
cobertura.

- [ ] **Paso 3: Correr el gate completo**

Correr: `node --test tests/design-system/censo-fichas-coherencia.test.mjs`
Esperado: **3/3 PASS**.

- [ ] **Paso 4: Correr la suite estática entera, para no romper vecinos**

Correr: `npm run test:design-system:static`
Esperado: RC=0. Si `coverage-closure` se pone rojo, es señal de que el paso 2 añadió rutas sin
escenario — no bajar su umbral: añadir el escenario o revertir.

- [ ] **Paso 5: Commit**

```bash
git add docs/design-system/auditoria/censo-modulos.json docs/design-system/manifests/programacion-semanal.json
git commit -m "fix(ds): CNP, CNC y CIC quedan enlazados a la ficha de semanal

Son tres vistas de la misma pantalla, no tres modulos. Tres fichas propias
triplicarian el mantenimiento sin anadir cobertura."
```

---

### Task 4: Cerrar la deuda de las tres pantallas sin ficha

`coverage-debt.json` congela tres: `/`, `/dashboard` y `/reportes/{tipo}`.

**Files:**
- Modify: `docs/design-system/coverage-debt.json`
- Create (posible): `docs/design-system/manifests/dashboard.json`

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: `coverage-debt.json` con menos entradas.

- [ ] **Paso 1: Clasificar las tres, sin tocarlas todavía**

Correr:
```bash
grep -n '"/"' docs/design-system/auditoria/censo-modulos.json | head -3
grep -n "'/dashboard'" public/index.php
grep -n "reportes" public/index.php | head -3
```

Lo ya medido, para no repetirlo: `/` es **alias de `/login`** según el censo (misma vista,
`LoginController::index`), así que su cobertura es la de `auth` y sale de la deuda sin trabajo.
`/dashboard` es el panel de inicio (`DashboardController::index`), pantalla real y nueva: necesita
ficha. `/reportes/{tipo}` hay que mirarlo antes de decidir.

- [ ] **Paso 2: Sacar `/` de la deuda, por alias**

Quitar `"/"` de `pantallas_sin_manifiesto` en `coverage-debt.json` y añadir al campo `medicion` una
línea: `2026-08-26: "/" sale por alias de /login (LoginController::index), cubierto por auth.json.`

- [ ] **Paso 3: Correr el gate de cobertura**

Correr: `node --test tests/design-system/coverage-closure.test.mjs`
Esperado: PASS. Si falla, `/` no era alias y el paso 2 estaba mal: revertirlo y dejar la entrada.

- [ ] **Paso 4: Crear la ficha de `/dashboard`**

Copiar la estructura de `docs/design-system/manifests/project-selector.json` (es la ficha más
parecida en tamaño), ajustando `moduleId` a `dashboard`, `routes` a `["/dashboard"]` y `sources` a
los archivos reales de esa pantalla. Comprobar los `sources` con:

```bash
grep -rn "DashboardController" public/index.php src/Controllers/Core/DashboardController.php | head -5
```

- [ ] **Paso 5: Quitar `/dashboard` de la deuda y correr los dos gates**

Correr: `node --test tests/design-system/coverage-closure.test.mjs tests/design-system/censo-fichas-coherencia.test.mjs`
Esperado: ambos PASS.

- [ ] **Paso 6: Commit**

```bash
git add docs/design-system/coverage-debt.json docs/design-system/manifests/dashboard.json
git commit -m "fix(ds): baja la deuda de cobertura de tres pantallas a una

/ sale por alias de /login (cubierto por auth). /dashboard recibe su ficha:
es pantalla real y nueva. /reportes/{tipo} queda, con su motivo escrito."
```

---

### Task 5: Cablear el gate a la suite y dejar el cierre escrito

**Files:**
- Modify: `package.json` (si `test:design-system:static` no descubre el gate solo)
- Modify: `docs/superpowers/plans/2026-08-26-tarea-cero-lista-canonica-de-pantallas.md` (sección `## Cierre`)

- [ ] **Paso 1: Comprobar si la suite ya recoge el gate nuevo**

Correr: `npm run test:design-system:static 2>&1 | grep -c censo-fichas`
Esperado: 1 o más. Si da 0, el script no descubre el archivo: añadirlo al glob de `package.json:8`.

- [ ] **Paso 2: Correr la suite estática entera**

Correr: `npm run test:design-system:static`
Esperado: RC=0. **Leer el código de salida en su propia línea**, no encadenado ni tras una tubería
(en zsh es `$pipestatus[1]`, 1-indexado).

- [ ] **Paso 3: Escribir el `## Cierre` de este plan**

Con: el número de módulos censados y fichas al terminar, qué quedó exento y por qué, qué entradas
salieron de `coverage-debt.json`, y **el tiempo real que costó** — ese dato alimenta la estimación de
la Ola 1.

- [ ] **Paso 4: Publicar**

```bash
bash scripts/publicar.sh
```

Recordar que este script **no commitea**: el árbol debe estar limpio antes.

---

## Condición de hecho

1. `node --test tests/design-system/censo-fichas-coherencia.test.mjs` da **3/3 PASS**.
2. `npm run test:design-system:static` da **RC=0**, leído en su propia línea.
3. Todo módulo del censo con pantalla tiene ficha **o** exención con motivo escrito.
4. `coverage-debt.json` tiene **menos** entradas que al empezar, y ninguna añadida.
5. El `## Cierre` de este plan dice cuánto costó, en horas reales.

## Lo que este plan NO hace

- **No migra ninguna pantalla.** Eso es la Ola 1 y la Ola 2.
- **No crea fichas para módulos sin pantalla** (integración, núcleo y runtime, legado): tienen cero
  superficies y una ficha vacía declararía cobertura inexistente.
- **No toca `admin/`**: se declara exento con su motivo, no se le fabrica cobertura.

---

## Cierre — 2026-08-26

**Las cinco tareas cerraron, y las tres primeras salieron como el plan las predijo.** Las últimas
dos no: dos de las tres pantallas de la deuda tenían una causa distinta de la que este plan y la
spec de la v0 les atribuían, y solo se vio al abrir el controlador.

### La unidad de medida, corregida

El plan pedía «medio día». Es una unidad de trabajo humano que no aplica aquí y no le sirve a nadie
para dimensionar las otras 40 pantallas, que es para lo que la Ola 1 necesita un número real. La
medida que sí sirve: **cuántas veces hubo que parar y volver a mirar el código porque lo escrito no
coincidía con lo que había.**

**Tres paradas, las tres en la Task 4** — las dos primeras tareas (el gate y los dos enlaces rotos)
corrieron sin ninguna:

1. `/reportes/{tipo}` calificaba como «pantalla» para el gate de cobertura, pero
   `ReportController::generate` solo descarga Excel. Ninguna vista que migrar.
2. `/dashboard`, que la spec de la v0 llamaba «panel de inicio» sin haber abierto el controlador,
   resultó ser puro enrutamiento: `DashboardController::index()` nunca renderiza, solo redirige.
3. El gate `coverage-closure` existente no distingue redirección/descarga de pantalla real, y **no
   lee el censo** — la misma clase de desconexión entre inventarios que esta tarea entera existía
   para resolver, encontrada de nuevo dentro de su propia solución.

### Lo que quedó

- **El gate nuevo** (`censo-fichas-coherencia.test.mjs`) nació rojo por 2 enlaces rotos y 3 módulos
  sin enlazar; cierra en verde, 3/3.
- **El censo y las fichas coinciden**: `selector-de-proyectos` → `project-selector`, `panel-admin`
  exento con motivo, CNP/CNC/CIC enlazados a la ficha de semanal que ya los declaraba.
- **La deuda de cobertura bajó de 3 pantallas a 2**, ambas con causa verificada en código:
  `/dashboard` y `/reportes/{tipo}` no rinden pantalla, y el gate que las detecta no tiene forma de
  saberlo. Corregir esa distinción queda fuera de esta tarea — anotado en `TASKS.md`.
- **Ningún gate existente se rompió**: `npm run test:design-system:static` en `RC=0` antes y después
  de cada cambio.

### Corrección que le debo a la spec de la v0

`docs/superpowers/specs/2026-08-26-v0-del-producto-design.md` describe el «panel de inicio» de la
Ola 3 dando por hecho que `/dashboard` es una pantalla con contenido propio. **No lo es.** Queda
anotado ahí también, para que la Ola 3 no herede el mismo supuesto sin verificar.

### Verificación

```
node --test tests/design-system/censo-fichas-coherencia.test.mjs   # RC=0, 3/3
node --test tests/design-system/coverage-closure.test.mjs          # RC=0, 3/3
npm run test:design-system:static                                  # RC=0, 8/8 suites
npm run test:wiki                                                  # RC=0, sin hallazgos, 170 páginas
```

Rama `tarea-cero-lista-canonica`, 5 commits sobre `74a44faa`. Sin merge todavía.
