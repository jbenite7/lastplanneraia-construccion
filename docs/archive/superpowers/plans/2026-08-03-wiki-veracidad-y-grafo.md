# Cierre de los tres pendientes de la wiki — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que la wiki `memoria/` avise sola cuando lleva demasiados commits sin verificarse contra el código, que sus nodos sueltos estén clasificados y declarados, y que su manual de operación viva donde un humano nuevo lo encuentre.

**Architecture:** La lógica de la alarma se extrae a un módulo propio (`scripts/wiki-veracidad.mjs`) con funciones puras que reciben texto y una función ejecutora de git, para poder probarlas sin tocar el repositorio real; `scripts/wiki-lint.mjs` solo la consume y anota el hallazgo con el mismo mecanismo que ya usa. El tejido del grafo y el manual son trabajo de contenido, sin código.

**Tech Stack:** Node 20+ ESM (sin dependencias), runner nativo `node --test`, `git log` vía `child_process.execFileSync`.

## Global Constraints

- **Umbral:** más de **40** commits desde el último pase → hallazgo. Constante nombrada al principio de `scripts/wiki-veracidad.mjs`.
- **Rutas que cuentan para el conteo:** `src/`, `admin/`, `public/`, `tests/`, `scripts/`, `docs/`, `AGENTS.md`. Nada más.
- **Rutas que NO cuentan:** un commit que toque exclusivamente `memoria/` no cuenta (la wiki no dispara su propia alarma).
- **Sin línea `veracidad` en el log → informativo, nunca rojo.** Nacer fallando entrena a ignorar el rojo.
- **El lint comprueba y reporta; nunca corrige.** Regla existente de `scripts/wiki-lint.mjs:2-3`, se mantiene.
- **Las cuatro operaciones se llaman:** `ingest`, `query`, `lint`, `veracidad`.
- **Formato de línea de bitácora:** `- YYYY-MM-DD · operación · asunto · páginas tocadas`, la más reciente abajo (`memoria/log.md:12`).
- **Áreas válidas:** lista cerrada de trece, ya declarada en `scripts/wiki-lint.mjs:11-12`. No se amplía en este plan.
- **No se hace commit sin petición explícita del usuario** (`AGENTS.md` §Publicación). Los pasos «Commit» de este plan se ejecutan solo si el usuario lo autoriza; si no, se dejan los cambios en el worktree y se dice así en el resumen.

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `scripts/wiki-veracidad.mjs` (nuevo) | Funciones puras de la alarma: leer la última línea `veracidad` del log y contar commits relevantes. No imprime ni sale con código. |
| `tests/wiki/veracidad.test.mjs` (nuevo) | Pruebas de esas funciones con `node --test`, sin tocar git ni el repo real. |
| `scripts/wiki-lint.mjs` (modificar) | Importa el módulo y anota el hallazgo. Nada de lógica de fechas o git aquí. |
| `package.json` (modificar) | Script `test:wiki` que corre el runner nativo sobre `tests/wiki/`. |
| `docs/wiki-operacion.md` (nuevo) | Manual de operación en la capa de fuentes. |
| `CLAUDE.md` (modificar) | Su sección «Memoria del proyecto» adelgaza a resumen + puntero. |
| `memoria/index.md` (modificar) | Cuarta operación, enlace al manual, párrafo de nodos sueltos declarados. |
| `memoria/log.md` (modificar) | Primera línea `veracidad` real, escrita al final del pase. |
| `memoria/mapas/*.md` (modificar) | Wikilinks a los nodos clasificados como vigentes. |

---

### Task 1: Módulo de la alarma de veracidad

**Files:**
- Create: `scripts/wiki-veracidad.mjs`
- Create: `tests/wiki/veracidad.test.mjs`
- Modify: `package.json` (sección `scripts`)

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces:
  - `UMBRAL_COMMITS` — `number`, vale `40`.
  - `RUTAS_CONTADAS` — `string[]`, las siete rutas de las Global Constraints.
  - `ultimoPase(logTexto: string): string | null` — devuelve la fecha `YYYY-MM-DD` de la última línea `veracidad` del log, o `null` si no hay ninguna.
  - `contarCommits(desde: string, ejecutor: (args: string[]) => string): number` — cuenta commits posteriores a `desde` que tocan `RUTAS_CONTADAS`. `ejecutor` recibe los argumentos de git y devuelve stdout; se inyecta para poder probar sin git.
  - `estadoVeracidad(logTexto: string, ejecutor): { sembrado: boolean, desde: string | null, commits: number, excedido: boolean }`.

- [ ] **Step 1: Escribe el test que falla**

Crea `tests/wiki/veracidad.test.mjs`:

```javascript
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { ultimoPase, contarCommits, estadoVeracidad, UMBRAL_COMMITS }
  from '../../scripts/wiki-veracidad.mjs';

const LOG_SIN_PASE = [
  '- 2026-08-02 · ingest · Se funda la wiki · [[index]]',
  '- 2026-08-03 · lint · Primera pasada · [[index]]',
].join('\n');

const LOG_CON_PASES = [
  '- 2026-08-01 · veracidad · áreas revisadas: pdc · 3 páginas · [[pdc]]',
  '- 2026-08-02 · ingest · Algo · [[index]]',
  '- 2026-08-03 · veracidad · áreas revisadas: rbac · 5 páginas · [[rbac-y-rutas]]',
].join('\n');

test('sin línea veracidad devuelve null', () => {
  assert.equal(ultimoPase(LOG_SIN_PASE), null);
});

test('toma la última línea veracidad, no la primera', () => {
  assert.equal(ultimoPase(LOG_CON_PASES), '2026-08-03');
});

test('no confunde la palabra veracidad dentro del asunto de otra operación', () => {
  const log = '- 2026-08-03 · ingest · Nota sobre veracidad de los tokens · [[index]]';
  assert.equal(ultimoPase(log), null);
});

test('contarCommits cuenta las líneas que devuelve git', () => {
  const ejecutor = () => 'abc\ndef\nghi\n';
  assert.equal(contarCommits('2026-08-01', ejecutor), 3);
});

test('contarCommits devuelve 0 con salida vacía', () => {
  assert.equal(contarCommits('2026-08-01', () => '\n'), 0);
});

test('contarCommits pasa la fecha y las rutas a git, y excluye memoria/', () => {
  let recibido = null;
  contarCommits('2026-08-01', (args) => { recibido = args; return ''; });
  assert.ok(recibido.includes('--since=2026-08-01'));
  assert.ok(recibido.includes('--'), 'debe separar rutas con --');
  assert.ok(recibido.includes('src/'));
  assert.ok(recibido.includes('AGENTS.md'));
  assert.ok(!recibido.some((a) => a.includes('memoria')),
    'memoria/ no debe aparecer entre las rutas contadas');
});

test('estado no sembrado: informativo, nunca excedido', () => {
  const e = estadoVeracidad(LOG_SIN_PASE, () => 'a\n'.repeat(500));
  assert.equal(e.sembrado, false);
  assert.equal(e.excedido, false);
});

test('estado sembrado por debajo del umbral no está excedido', () => {
  const e = estadoVeracidad(LOG_CON_PASES, () => 'a\n'.repeat(UMBRAL_COMMITS));
  assert.equal(e.sembrado, true);
  assert.equal(e.desde, '2026-08-03');
  assert.equal(e.commits, UMBRAL_COMMITS);
  assert.equal(e.excedido, false, 'el umbral es «más de», no «igual o más»');
});

test('estado sembrado por encima del umbral está excedido', () => {
  const e = estadoVeracidad(LOG_CON_PASES, () => 'a\n'.repeat(UMBRAL_COMMITS + 1));
  assert.equal(e.excedido, true);
});
```

- [ ] **Step 2: Corre el test y comprueba que falla**

```bash
node --test tests/wiki/
```

Esperado: FALLA con `Cannot find module .../scripts/wiki-veracidad.mjs`.

- [ ] **Step 3: Escribe la implementación mínima**

Crea `scripts/wiki-veracidad.mjs`:

```javascript
#!/usr/bin/env node
// Alarma de la operación `veracidad` de la wiki `memoria/`.
// Funciones puras: no imprimen ni salen con código. Las consume scripts/wiki-lint.mjs.
// Ver docs/wiki-operacion.md.
import { execFileSync } from 'node:child_process';

// Más de este número de commits de código desde el último pase → hallazgo.
// Elegido contra el ritmo real del repo (101-181 commits/día en sprint): salta 2-4 veces
// en un día intenso y ninguna en días quietos. Ajustable en una línea; deja constancia en el log.
export const UMBRAL_COMMITS = 40;

// Código y contratos. `memoria/` queda fuera a propósito: la wiki no dispara su propia alarma.
export const RUTAS_CONTADAS = ['src/', 'admin/', 'public/', 'tests/', 'scripts/', 'docs/', 'AGENTS.md'];

const LINEA_VERACIDAD = /^-\s+(\d{4}-\d{2}-\d{2})\s+·\s+veracidad\s+·/;

export function ultimoPase(logTexto) {
  let ultima = null;
  for (const linea of logTexto.split('\n')) {
    const m = LINEA_VERACIDAD.exec(linea.trim());
    if (m) ultima = m[1];
  }
  return ultima;
}

export function contarCommits(desde, ejecutor = gitPorDefecto) {
  const args = ['log', `--since=${desde}`, '--pretty=%H', '--', ...RUTAS_CONTADAS];
  return ejecutor(args).split('\n').filter((l) => l.trim()).length;
}

function gitPorDefecto(args) {
  try {
    return execFileSync('git', args, { encoding: 'utf8' });
  } catch {
    return '';
  }
}

export function estadoVeracidad(logTexto, ejecutor = gitPorDefecto) {
  const desde = ultimoPase(logTexto);
  if (!desde) return { sembrado: false, desde: null, commits: 0, excedido: false };
  const commits = contarCommits(desde, ejecutor);
  return { sembrado: true, desde, commits, excedido: commits > UMBRAL_COMMITS };
}
```

- [ ] **Step 4: Corre el test y comprueba que pasa**

```bash
node --test tests/wiki/
```

Esperado: los 9 tests en verde.

- [ ] **Step 5: Añade el script a `package.json`**

En la sección `scripts`, junto a los demás `test:*`:

```json
"test:wiki": "node --test tests/wiki/ && node scripts/wiki-lint.mjs",
```

- [ ] **Step 6: Comprueba que el script nuevo corre**

```bash
npm run test:wiki
```

Esperado: 9 tests en verde y `Sin hallazgos. 78 páginas revisadas.`

- [ ] **Step 7: Commit** *(solo si el usuario autorizó publicar; si no, saltar y decirlo en el resumen)*

```bash
git add scripts/wiki-veracidad.mjs tests/wiki/veracidad.test.mjs package.json
git commit -m "feat(wiki): alarma de veracidad medida en commits de codigo"
```

---

### Task 2: Cablear la alarma en `wiki-lint.mjs`

**Files:**
- Modify: `scripts/wiki-lint.mjs` (import arriba; bloque nuevo antes del `if (hallazgos.length)` de la línea 118)
- Modify: `tests/wiki/veracidad.test.mjs` (dos casos nuevos al final)

**Interfaces:**
- Consumes: `estadoVeracidad`, `UMBRAL_COMMITS` de `scripts/wiki-veracidad.mjs` (Task 1).
- Produces: `mensajeVeracidad(estado): { hallazgo: string | null, aviso: string | null }` exportada desde `scripts/wiki-veracidad.mjs` — el lint solo la imprime.

- [ ] **Step 1: Escribe el test que falla**

Añade al final de `tests/wiki/veracidad.test.mjs`:

```javascript
import { mensajeVeracidad } from '../../scripts/wiki-veracidad.mjs';

test('sin sembrar: aviso informativo, ningún hallazgo', () => {
  const m = mensajeVeracidad({ sembrado: false, desde: null, commits: 0, excedido: false });
  assert.equal(m.hallazgo, null);
  assert.match(m.aviso, /veracidad/i);
});

test('excedido: hallazgo con el conteo y la fecha dentro', () => {
  const m = mensajeVeracidad({ sembrado: true, desde: '2026-08-03', commits: 57, excedido: true });
  assert.ok(m.hallazgo.includes('57'));
  assert.ok(m.hallazgo.includes('2026-08-03'));
  assert.ok(m.hallazgo.includes(String(UMBRAL_COMMITS)));
});

test('sembrado y por debajo: ni hallazgo ni aviso de alarma', () => {
  const m = mensajeVeracidad({ sembrado: true, desde: '2026-08-03', commits: 5, excedido: false });
  assert.equal(m.hallazgo, null);
});
```

- [ ] **Step 2: Corre el test y comprueba que falla**

```bash
node --test tests/wiki/
```

Esperado: FALLA con `mensajeVeracidad is not a function`.

- [ ] **Step 3: Implementa `mensajeVeracidad`**

Añade al final de `scripts/wiki-veracidad.mjs`:

```javascript
export function mensajeVeracidad(estado) {
  if (!estado.sembrado) {
    return {
      hallazgo: null,
      aviso: 'Veracidad: sin pase registrado todavía. El primer pase siembra la línea '
        + '`veracidad` en memoria/log.md; hasta entonces esta comprobación no falla.',
    };
  }
  if (!estado.excedido) {
    return {
      hallazgo: null,
      aviso: `Veracidad: ${estado.commits} commits de código desde el pase del ${estado.desde} `
        + `(umbral ${UMBRAL_COMMITS}).`,
    };
  }
  return {
    hallazgo: `${estado.commits} commits de código desde el último pase del ${estado.desde}, `
      + `por encima del umbral de ${UMBRAL_COMMITS}. Toca un pase de veracidad: `
      + 'verifica contra el repositorio las páginas de las áreas que cambiaron '
      + '(ver docs/wiki-operacion.md).',
    aviso: null,
  };
}
```

- [ ] **Step 4: Corre el test y comprueba que pasa**

```bash
node --test tests/wiki/
```

Esperado: los 12 tests en verde.

- [ ] **Step 5: Cablea el módulo en el lint**

En `scripts/wiki-lint.mjs`, tras los imports existentes (líneas 4-6), añade:

```javascript
import { estadoVeracidad, mensajeVeracidad } from './wiki-veracidad.mjs';
```

Y justo antes del bloque `if (hallazgos.length) {` de la línea 118, añade:

```javascript
// Edad del último pase de veracidad, medida en commits de código (no en días).
const veracidad = mensajeVeracidad(estadoVeracidad(readFileSync(join(WIKI, 'log.md'), 'utf8')));
if (veracidad.hallazgo) anota('VERACIDAD', 'memoria/log.md', veracidad.hallazgo);
if (veracidad.aviso) console.log(`${veracidad.aviso}\n`);
```

- [ ] **Step 6: Corre el lint contra el repo real**

```bash
node scripts/wiki-lint.mjs
```

Esperado: imprime el aviso «sin pase registrado todavía», **sale en verde** (`Sin hallazgos. 78 páginas revisadas.`) y con código 0. Confírmalo:

```bash
node scripts/wiki-lint.mjs > /dev/null; echo "código de salida: $?"
```

Esperado: `código de salida: 0`.

- [ ] **Step 7: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add scripts/wiki-lint.mjs scripts/wiki-veracidad.mjs tests/wiki/veracidad.test.mjs
git commit -m "feat(wiki): el lint avisa cuando toca un pase de veracidad"
```

---

### Task 3: Manual de operación `docs/wiki-operacion.md`

**Files:**
- Create: `docs/wiki-operacion.md`
- Modify: `CLAUDE.md` (sección «Memoria del proyecto (wiki en `memoria/`)»)
- Modify: `memoria/index.md` (sección «Las tres operaciones» → cuatro; enlace al manual)

**Interfaces:**
- Consumes: el umbral y las rutas contadas de Task 1; el nombre `VERACIDAD` del hallazgo de Task 2.
- Produces: la ruta `docs/wiki-operacion.md`, referenciada desde `CLAUDE.md`, `memoria/index.md` y el mensaje de hallazgo de Task 2.

- [ ] **Step 1: Escribe el manual**

Crea `docs/wiki-operacion.md` con estas secciones, en este orden:

1. **Qué es la wiki y qué no es** — las tres capas (fuentes / wiki / esquema), la precedencia `código > AGENTS.md > memoria/`, y que nada de la wiki es contrato. Que una nota desmentida se corrige y se marca `estado: derogada`, nunca se borra.
2. **Las cuatro operaciones**, una subsección cada una, con: cuándo se dispara, qué hace, y su línea de bitácora de ejemplo.
   - `ingest` — al cerrar tarea o aparecer fuente nueva.
   - `query` — pregunta contra la wiki, respondida citando páginas; si la respuesta era valiosa y no estaba escrita, se promueve a página.
   - `lint` — `node scripts/wiki-lint.mjs`. Comprueba **forma**: enlaces rotos o ambiguos, frontmatter, `areas` fuera de la lista, notas de más de tres hechos, páginas no alcanzables desde `index.md` ni desde una vista de `paginas.base`. **No comprueba verdad.**
   - `veracidad` — verificar contra el código que lo escrito sigue siendo cierto. Alcance por rotación: las áreas cuyo código cambió desde el pase anterior, más las páginas más antiguas sin revisar. Exige **verificar cada afirmación leyendo el repositorio, no sospecharla**. Delegable a un subagente de bajo coste.
3. **La alarma de veracidad** — el lint cuenta los commits posteriores a la última línea `veracidad` que tocan `src/`, `admin/`, `public/`, `tests/`, `scripts/`, `docs/` y `AGENTS.md`; más de **40** → hallazgo `VERACIDAD`. `memoria/` no cuenta, para que la wiki no dispare su propia alarma. Sin línea sembrada el aviso es informativo y no falla. El umbral vive en `scripts/wiki-veracidad.mjs` y se ajusta dejando constancia en el log.
   Incluye la limitación conocida: **el conteo se hace sobre la rama actual**, así que con varias sesiones en worktrees distintos es aproximado.
4. **Escribir una página** — `una nota, un hecho`; si no cabe en una pantalla probablemente son dos. Frontmatter obligatorio (`tipo`, `estado`, `fecha`, `areas`, `fuente`, `resumen`, más `origen` si viene de la memoria privada previa) con qué significa cada campo. La regla de que **si corriges el cuerpo, corriges el `resumen`**, porque es lo que se ve en el catálogo.
5. **Las trece áreas** — lista literal, y cómo se amplía: primero `scripts/wiki-lint.mjs`, después explicar en `memoria/index.md` qué cubre.
6. **Los scripts** — `wiki-lint.mjs` y `wiki-arquitectura.mjs` (`--cobertura`, `--escribir`), con la regla de las zonas generadas entre marcadores: dentro manda el script, fuera manda la persona.
7. **El vault** — la raíz del repo, no `memoria/`; configuración compartida en `.obsidian/`; sin plugins de comunidad.

- [ ] **Step 2: Adelgaza `CLAUDE.md`**

Sustituye el cuerpo de la sección «Memoria del proyecto (wiki en `memoria/`)» por: el párrafo de arranque («Empieza por `memoria/index.md`»), la tabla de las tres capas, la excepción de `goals/`, la precedencia, y **un párrafo** que nombre las cuatro operaciones y remita a `docs/wiki-operacion.md` para el detalle. Quita de `CLAUDE.md` el detalle que ya vive en el manual: qué comprueba el lint punto por punto, la lista literal de trece áreas, y las reglas de escritura de frontmatter.

Comprueba que la sección quedó más corta:

```bash
git diff --stat CLAUDE.md
```

Esperado: más líneas borradas que añadidas.

- [ ] **Step 3: Actualiza `memoria/index.md`**

- Renombra la sección «Las tres operaciones» a «Las cuatro operaciones» y añade la viñeta de `veracidad` con una frase: qué verifica y que el lint avisa cuando toca.
- Añade al final de esa sección: `El procedimiento completo está en [[wiki-operacion]].`
- Actualiza la tercera fila de la tabla de capas: el esquema pasa a vivir en `docs/wiki-operacion.md`, con `CLAUDE.md` como resumen.

- [ ] **Step 4: Corre el lint**

```bash
npm run test:wiki
```

Esperado: 12 tests en verde y `Sin hallazgos.` — en particular, el wikilink `[[wiki-operacion]]` debe resolver, porque el vault es la raíz del repo y `docs/` está indexado. **Si sale `ENLACE roto`, comprueba primero que `docs/` no esté en `userIgnoreFilters` de `.obsidian/app.json`**; si lo estuviera, enlaza por ruta (`[[docs/wiki-operacion]]`) en vez de tocar la configuración del vault.

- [ ] **Step 5: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add docs/wiki-operacion.md CLAUDE.md memoria/index.md
git commit -m "docs(wiki): manual de operacion en la capa de fuentes"
```

---

### Task 4: Auditoría y tejido del grafo

**Files:**
- Modify: `memoria/mapas/*.md` (solo los mapas de las áreas que reciban enlaces vigentes)
- Modify: `memoria/index.md` (párrafo de nodos sueltos declarados)
- Create: `docs/superpowers/specs/evidencia/2026-08-03-nodos-sueltos.md` (clasificación completa, como evidencia)

**Interfaces:**
- Consumes: la lista de áreas válidas de `scripts/wiki-lint.mjs:11-12`.
- Produces: nada que consuma código.

- [ ] **Step 1: Mide la lista real de nodos sueltos**

Escribe un script de un solo uso en el scratchpad (no en `scripts/`, que es del repo) que reutilice el recorrido del vault de `scripts/wiki-lint.mjs:27-50`: lista todos los `.md` del vault, extrae los `[[wikilinks]]` de cada uno, y reporta los archivos que **no tienen ningún enlace entrante ni saliente**. Guarda la salida.

Esperado: una lista de rutas del orden de 100 entradas. Anota el número exacto — el «~109» del diagnóstico es de memoria, no medido en esta sesión.

- [ ] **Step 2: Clasifica delegando a un subagente barato**

Despacha un subagente `buscador` (solo lectura, modelo `haiku`) con la lista y este criterio literal:

- **vigente** — describe algo que hoy manda o que se consultaría antes de tocar código: contratos, especificaciones de módulos vivos, rutinas en uso.
- **histórico** — cerrado y superado: specs de goals ya cerrados, evidencia de sprints pasados, diseños que otro documento reemplazó.
- **dudoso** — no se puede decidir sin criterio del usuario.

Exígele que **abra cada archivo** y justifique el cubo en una línea con lo que leyó, no por el nombre del archivo. Que devuelva las tres listas y nada más.

- [ ] **Step 3: Escribe la clasificación como evidencia**

Vuelca las tres listas en `docs/superpowers/specs/evidencia/2026-08-03-nodos-sueltos.md`: el total medido, las tres listas con su justificación de una línea, y la fecha.

- [ ] **Step 4: Teje solo los vigentes**

Para cada archivo del cubo **vigente**, añade un wikilink desde el mapa de su área en `memoria/mapas/`, en la sección donde encaje por tema. El enlace debe aportar navegación real: si no sabes en qué frase ponerlo, el archivo probablemente no era vigente — muévelo a *dudoso*.

No edites el contenido de `docs/`. Los enlaces se añaden **en los mapas**, no en las fuentes.

- [ ] **Step 5: Declara los históricos en el índice**

Añade a `memoria/index.md`, al final de la sección «Catálogo», un párrafo corto: cuántos nodos quedan deliberadamente sueltos, de qué son (specs y evidencia de goals cerrados), y por qué no se tejen — un grafo honesto vale más que uno lleno de enlaces de relleno. Cita la evidencia por ruta.

- [ ] **Step 6: Vuelve a medir y comprueba la mejora**

Corre otra vez el script del Step 1.

Esperado: el número de nodos sueltos baja exactamente en la cantidad de archivos del cubo *vigente*. Si no cuadra, un enlace quedó mal escrito — arréglalo antes de seguir.

- [ ] **Step 7: Corre el lint**

```bash
npm run test:wiki
```

Esperado: `Sin hallazgos.` Los enlaces nuevos no deben salir rotos ni ambiguos.

- [ ] **Step 8: Lista los dudosos al usuario**

Preséntale el cubo *dudoso* con la justificación de cada uno y pregunta cuáles tejer. No los tejas de oficio.

- [ ] **Step 9: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add memoria/mapas memoria/index.md docs/superpowers/specs/evidencia/2026-08-03-nodos-sueltos.md
git commit -m "docs(wiki): nodos vigentes tejidos, historicos declarados sueltos"
```

---

### Task 5: Primer pase de veracidad real

**Files:**
- Modify: páginas de `memoria/` que resulten desmentidas por el código
- Modify: `memoria/log.md` (línea `veracidad` nueva, al final)

**Interfaces:**
- Consumes: el procedimiento de `docs/wiki-operacion.md` (Task 3) y la alarma de Task 2.
- Produces: la primera línea `veracidad` del log, que arma la alarma para lo sucesivo.

- [ ] **Step 1: Elige el alcance por rotación**

Como no hay pase anterior, toma las áreas con más movimiento reciente:

```bash
git log --since=2026-07-26 --name-only --pretty=format: -- src/ admin/ public/ tests/ scripts/ docs/ | sort | uniq -c | sort -rn | head -30
```

De ahí salen las áreas con más cambio. Elige **dos o tres** áreas y anota cuáles y por qué.

- [ ] **Step 2: Verifica las páginas de esas áreas**

Para cada página de las áreas elegidas, **abre el código que la nota afirma** y comprueba la afirmación. Reglas:

- Verificar, no sospechar: una afirmación se confirma citando archivo y línea, o se corrige.
- Una nota desmentida se corrige **y** se marca `estado: derogada` si ya no aplica; si solo estaba imprecisa, se corrige el cuerpo **y el `resumen`**.
- No inventes correcciones que no puedas respaldar con salida real de esta sesión.

- [ ] **Step 3: Anota la línea en el log**

Añade al final de `memoria/log.md`, respetando el formato:

```
- 2026-08-03 · veracidad · áreas revisadas: <áreas> · <N> páginas · <N> corregidas, <N> derogadas · <páginas tocadas>
```

- [ ] **Step 4: Comprueba que la alarma quedó armada**

```bash
node scripts/wiki-lint.mjs
```

Esperado: **ya no** aparece el aviso «sin pase registrado todavía», sino el conteo real de commits desde hoy (que será 0 o muy bajo, porque la fecha es de hoy), y sigue en verde.

- [ ] **Step 5: Comprueba que la alarma sabe ponerse roja**

No basta con verla callada. Prueba el camino rojo sin ensuciar el repo:

```bash
node --input-type=module -e "
import { mensajeVeracidad } from './scripts/wiki-veracidad.mjs';
console.log(mensajeVeracidad({ sembrado: true, desde: '2026-07-01', commits: 200, excedido: true }).hallazgo);
"
```

Esperado: imprime el texto del hallazgo con `200`, `2026-07-01` y `40` dentro.

- [ ] **Step 6: Corre la suite completa de la wiki**

```bash
npm run test:wiki
```

Esperado: 12 tests en verde y `Sin hallazgos.`

- [ ] **Step 7: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add memoria/
git commit -m "docs(wiki): primer pase de veracidad, alarma armada"
```

---

## Verificación final

Antes de decir «hecho», corre y pega la salida real de:

```bash
npm run test:wiki
```

Y comprueba una a una las cinco condiciones de hecho del spec (`docs/superpowers/specs/2026-08-03-wiki-veracidad-y-grafo-design.md` §Condición de hecho). La quinta —que el lint sigue sin corregir nada— se comprueba releyendo el diff de `scripts/wiki-lint.mjs`: solo debe leer, contar y anotar.

**Sin UI web:** este trabajo no toca ninguna superficie de la aplicación, así que no hay validación en navegador. Dilo explícitamente en el resumen de cierre.
