# Pasada de lint sobre la wiki `memoria/` — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Corregir las tres afirmaciones de la wiki que el repositorio ya desmiente, repartir la nota que empaqueta diez hechos, cerrar el vocabulario de `areas` y sustituir el catálogo manual del índice por vistas generadas.

**Architecture:** Se empieza construyendo el verificador (`scripts/wiki-lint.mjs`), que falla contra el estado actual. Cada tarea posterior lo pone en verde en un aspecto. El verificador queda en el repositorio como la operación *lint* del patrón LLM Wiki, ejecutable a mano.

**Tech Stack:** Node.js (sin dependencias, módulos ESM), Markdown con frontmatter YAML, Obsidian Bases.

## Global Constraints

- **Nunca se edita el contenido de `docs/`.** La única excepción viva es la sección «Archivos de este goal» al pie de cada `goals/*/goal.md`, ya aplicada; no se amplía.
- **La ruta del repositorio contiene un espacio** (`/Volumes/Crucial X6/…`). En scripts ESM, `file://${process.argv[1]}` es no-op: usar `pathToFileURL`. Ver `memoria/trampas/path-with-space-esm-guard-noop.md`.
- **No commitear sin petición explícita** salvo los commits que este plan indica, que ya están autorizados por el usuario al aprobarlo.
- **Frontmatter obligatorio** en toda página de `memoria/`: `tipo`, `estado`, `fecha`, `areas`, `fuente`, `resumen`. Las migradas llevan además `origen`.
- **Lista cerrada de `areas`** (doce, exactamente estos valores): `design-system`, `qa`, `docker`, `worktrees`, `pdc`, `lps`, `datos`, `rbac`, `deploy`, `bi`, `admin`, `proceso`.
- **Valores de `tipo`:** `decision`, `trampa`, `mapa`, `goal`, `concepto`, `referencia`, `log`.
- **Verificación en clon fresco** obligatoria antes del commit final: ningún wikilink puede depender de archivos no versionados.

---

## Estructura de archivos

**Se crea:**
- `scripts/wiki-lint.mjs` — verificador de la wiki. Responsabilidad única: comprobar y reportar, nunca corregir.
- `memoria/paginas.base` — definición de las vistas de catálogo.
- `memoria/trampas/sesion-cae-en-el-panel.md`
- `memoria/trampas/semanal-auto-dispara-mutaciones.md`
- `memoria/trampas/bitacora-drawer-sin-profesional.md`
- `memoria/trampas/reset-legacy-pisa-adaptadores.md`
- `memoria/trampas/gate-visual-tolerancia-enganosa.md`
- `memoria/trampas/captura-playwright-miente.md`
- `memoria/trampas/pdc-legend-item-clase-compartida.md`

**Se modifica:**
- `memoria/trampas/suite-php-rojos-preexistentes.md` — fechar la cifra.
- `memoria/decisiones/compras-migrado-shell-sidebar.md` — reescribir el párrafo del goal.
- `memoria/trampas/path-with-space-esm-guard-noop.md` — anexar el caso de Playwright.
- `memoria/trampas/servir-worktree-stack-efimero.md` y `aislar-stack-docker-por-worktree.md` — absorber lo fusionado.
- Las 31 páginas migradas — normalizar `areas`.
- `memoria/index.md` — vistas en lugar de tablas, y la lista de áreas.
- `memoria/log.md` — una línea por operación.

**Se retira:**
- `memoria/trampas/browser-qa-pitfalls.md` — repartida entre las páginas anteriores.

---

### Task 1: El verificador de la wiki

**Files:**
- Create: `scripts/wiki-lint.mjs`

**Interfaces:**
- Consumes: nada.
- Produces: ejecutable `node scripts/wiki-lint.mjs`; sale con código 1 si hay hallazgos, 0 si no. Imprime una línea por hallazgo con el formato `<CATEGORÍA> <archivo>: <detalle>`.

- [ ] **Step 1: Escribir el verificador**

Crear `scripts/wiki-lint.mjs`:

```javascript
#!/usr/bin/env node
// Operación `lint` de la wiki `memoria/` (patrón LLM Wiki).
// Comprueba y reporta; nunca corrige. Ver memoria/index.md.
import { readdirSync, readFileSync, existsSync } from 'node:fs';
import { join, relative, basename, extname, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');
const WIKI = join(RAIZ, 'memoria');

const AREAS = new Set(['design-system', 'qa', 'docker', 'worktrees', 'pdc',
  'lps', 'datos', 'rbac', 'deploy', 'bi', 'admin', 'proceso']);
const TIPOS = new Set(['decision', 'trampa', 'mapa', 'goal', 'concepto', 'referencia', 'log']);
const ESTADOS = new Set(['vigente', 'derogada', 'abierto', 'cerrado']);

function listarMd(dir) {
  const salida = [];
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) salida.push(...listarMd(p));
    else if (extname(e.name) === '.md') salida.push(p);
  }
  return salida;
}

// Índice del vault entero (la raíz del repo), aplicando los filtros de Obsidian.
const filtros = JSON.parse(readFileSync(join(RAIZ, '.obsidian/app.json'), 'utf8')).userIgnoreFilters;
const ignorado = (rel) => filtros.some((f) => rel === f.replace(/\/$/, '') || rel.startsWith(f))
  || rel.startsWith('.git/');

const vault = [];
(function recorrer(dir) {
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    const rel = relative(RAIZ, p);
    if (ignorado(rel + (e.isDirectory() ? '/' : ''))) continue;
    if (e.isDirectory()) recorrer(p);
    else if (extname(e.name) === '.md') vault.push(rel);
  }
})(RAIZ);

const porRuta = new Set(vault.map((f) => f.replace(/\.md$/, '')));
const porNombre = new Map();
for (const f of vault) {
  const corto = basename(f, '.md');
  if (!porNombre.has(corto)) porNombre.set(corto, []);
  porNombre.get(corto).push(f);
}

const hallazgos = [];
const anota = (cat, archivo, detalle) => hallazgos.push(`${cat} ${archivo}: ${detalle}`);

const paginas = listarMd(WIKI);
const indice = readFileSync(join(WIKI, 'index.md'), 'utf8');

for (const p of paginas) {
  const rel = relative(RAIZ, p);
  const texto = readFileSync(p, 'utf8');
  const fm = texto.match(/^---\n([\s\S]*?)\n---/)?.[1];

  if (!fm) { anota('FRONTMATTER', rel, 'sin bloque de frontmatter'); continue; }

  const campo = (k) => fm.match(new RegExp(`^${k}:\\s*(.*)$`, 'm'))?.[1]?.trim();
  for (const k of ['tipo', 'estado', 'fecha', 'resumen']) {
    if (!campo(k)) anota('FRONTMATTER', rel, `falta o está vacío: ${k}`);
  }
  if (campo('tipo') && !TIPOS.has(campo('tipo'))) anota('FRONTMATTER', rel, `tipo desconocido: ${campo('tipo')}`);
  if (campo('estado') && !ESTADOS.has(campo('estado'))) anota('FRONTMATTER', rel, `estado desconocido: ${campo('estado')}`);
  if (campo('fecha') && !/^\d{4}-\d{2}-\d{2}$/.test(campo('fecha'))) anota('FRONTMATTER', rel, `fecha no ISO: ${campo('fecha')}`);

  const areas = (fm.match(/^areas:\s*\[(.*)\]$/m)?.[1] ?? '')
    .split(',').map((s) => s.trim()).filter(Boolean);
  for (const a of areas) if (!AREAS.has(a)) anota('AREA', rel, `fuera de la lista cerrada: ${a}`);

  // Una nota, un hecho: más de tres hechos numerados delata una nota que debería partirse.
  const numerados = (texto.match(/^(?:\d+\.|\*\*\d+\.)\s/gm) ?? []).length;
  if (numerados > 3) anota('MULTIHECHO', rel, `${numerados} hechos numerados; parte la nota`);

  // Enlaces
  const limpio = texto.replace(/```[\s\S]*?```/g, '').replace(/`[^`\n]*`/g, '');
  for (const m of limpio.matchAll(/\[\[([^\]|#]+)(?:[|#][^\]]*)?\]\]/g)) {
    const destino = m[1].trim();
    if (porRuta.has(destino)) continue;
    const cand = porNombre.get(basename(destino));
    if (!cand) anota('ENLACE', rel, `roto: [[${destino}]]`);
    else if (cand.length > 1) anota('ENLACE', rel, `ambiguo: [[${destino}]] → ${cand.join(', ')}`);
  }

  // Toda página debe ser alcanzable desde el índice o desde una vista de la base.
  const nombre = basename(p, '.md');
  if (!['index', 'log'].includes(nombre)
      && !indice.includes(`[[${nombre}`)
      && !existsSync(join(WIKI, 'paginas.base'))) {
    anota('INDICE', rel, 'no aparece en index.md y no hay base que la liste');
  }
}

if (hallazgos.length) {
  console.log(hallazgos.join('\n'));
  console.log(`\n${hallazgos.length} hallazgos en ${paginas.length} páginas.`);
  process.exit(1);
}
console.log(`Sin hallazgos. ${paginas.length} páginas revisadas.`);
```

- [ ] **Step 2: Ejecutarlo para ver que falla**

Run: `node scripts/wiki-lint.mjs`

Expected: FALLA con código 1. Debe reportar al menos:
- `MULTIHECHO memoria/trampas/browser-qa-pitfalls.md: 10 hechos numerados; parte la nota`
- varias líneas `AREA … fuera de la lista cerrada: ui` / `shell` / `rutas` / `entorno` / `sesiones` / `git` / `tooling` / `goals` / `arquitectura`

Si NO falla, el verificador está mal escrito: revísalo antes de continuar.

- [ ] **Step 3: Commit**

```bash
git add scripts/wiki-lint.mjs
git commit -m "feat(wiki): añade el verificador de la wiki memoria/

Implementa la operación lint del patrón LLM Wiki como script ejecutable:
enlaces rotos y ambiguos, frontmatter incompleto, areas fuera de la lista
cerrada, notas que empaquetan más de tres hechos y páginas que no aparecen
en el índice.

Comprueba y reporta; no corrige. Falla con código 1 si hay hallazgos, para
poder encadenarlo.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: Las tres correcciones verificadas

**Files:**
- Modify: `memoria/trampas/browser-qa-pitfalls.md` (punto 8)
- Modify: `memoria/decisiones/compras-migrado-shell-sidebar.md`
- Modify: `memoria/trampas/suite-php-rojos-preexistentes.md`

**Interfaces:**
- Consumes: nada.
- Produces: nada que otras tareas usen. El punto 8 corregido se moverá en la Task 3 a `pdc-legend-item-clase-compartida.md`.

- [ ] **Step 1: Re-medir los tres hechos antes de escribir nada**

```bash
wc -l public/css/styles.css
grep -n "205px" public/css/styles.css || echo "205px NO aparece"
grep -n "pdc-legend-item" public/css/styles.css | head -3
sed -n '/## Cierre formal/,/^## /p' goals/sidebar-todos-modulos/goal.md | grep -A3 -i compras
ls tests/test_*.php | wc -l
```

Expected: 4380 líneas · `205px` ausente · reglas en 532–536 con tokens · el cierre formal describe Compras como excepción deliberada · 126 archivos de test.

Si alguna cifra difiere de la del spec, **usa la que acabas de medir** y anótalo en el log: el spec se escribió el 2026-08-03 y el repositorio se mueve.

- [ ] **Step 2: Corregir el punto 8 de `browser-qa-pitfalls.md`**

Sustituir el punto 8 completo por:

```markdown
8. **`pdc-legend-item` es una clase compartida trampa** (revisado el 2026-08-03): la regla
   `html body … {width: 205px !important}` que citaba la línea 6476 de `styles.css` **ya no
   existe** — el archivo tiene hoy 4380 líneas y `205px` no aparece en él. Tras la tokenización,
   `.pdc-legend-item` se define en `styles.css:532-536` con tokens de estado del design system y
   sin `!important` de ancho. Lo que sigue vigente es el fondo del asunto: la clase la comparten
   PG, PI y PS, y `buttons.css` la llena de `!important` en capa `components`, invencibles desde
   CSS de módulo. Para adoptar el design system en una leyenda, desacopla con una clase propia del
   módulo (patrón `pg-filter-chip`) en vez de pelear la cascada.
```

- [ ] **Step 3: Reescribir el párrafo del goal en `compras-migrado-shell-sidebar.md`**

Localizar el párrafo que dice que el `goal.md` de `sidebar-todos-modulos` «sigue diciendo» o «quedó obsoleto», y sustituirlo por:

```markdown
**Sobre el goal `sidebar-todos-modulos`** (revisado el 2026-08-03): su `goal.md` excluye a Compras,
y eso **no es un texto olvidado**. El goal cerró el 2026-07-31 con una sección «Cierre formal» que
documenta la omisión como excepción deliberada: «Compras… omitidas — PDC v2 tiene su propia
navegación; las rutas viejas ya están retiradas». Las dos cosas son ciertas a la vez: aquel goal no
migró Compras, y Compras llegó al shell sidebar por otra vía. No hay nada que corregir en el goal;
lo que había que corregir era esta nota, que lo acusaba de estar desactualizado.
```

- [ ] **Step 4: Fechar la cifra en `suite-php-rojos-preexistentes.md`**

Añadir inmediatamente después de la primera aparición de la cifra «4 de 108»:

```markdown
> **Universo medido el 2026-08-03:** `ls tests/test_*.php | wc -l` da **126** archivos, no 108. La
> cifra de fallos de arriba es de `main@1a75b19` (2026-07-29) y **no se ha vuelto a medir**. Cítala
> siempre con su fecha, o vuelve a correr la suite.
```

- [ ] **Step 5: Verificar que no se rompió nada**

Run: `node scripts/wiki-lint.mjs`

Expected: sigue fallando (las áreas y el multihecho no se han tocado todavía), pero **sin ninguna línea nueva de `ENLACE`**. Compara con la salida de la Task 1.

- [ ] **Step 6: Commit**

```bash
git add memoria/trampas/browser-qa-pitfalls.md memoria/decisiones/compras-migrado-shell-sidebar.md memoria/trampas/suite-php-rojos-preexistentes.md
git commit -m "fix(wiki): corrige tres afirmaciones que el repositorio desmiente

Medido el 2026-08-03:

- La trampa de pdc-legend-item apuntaba a styles.css:6476 con un ancho de
  205px !important. El archivo tiene 4380 líneas y 205px no aparece; las
  reglas actuales (532-536) usan tokens sin !important de ancho. Se
  conserva la recomendación de fondo, que sigue siendo válida.
- La decisión sobre Compras acusaba al goal sidebar-todos-modulos de estar
  desactualizado. Ese goal cerró el 2026-07-31 documentando la omisión como
  excepción deliberada; lo desactualizado era la nota.
- La cifra de la suite PHP (4 de 108) queda fechada: hoy el universo es de
  126 archivos y los fallos no se han vuelto a medir.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: Repartir la nota de diez hechos

**Files:**
- Create: las siete páginas listadas en «Estructura de archivos»
- Modify: `memoria/trampas/path-with-space-esm-guard-noop.md`
- Modify: `memoria/trampas/servir-worktree-stack-efimero.md`
- Delete: `memoria/trampas/browser-qa-pitfalls.md`
- Modify: `memoria/index.md` (entradas del catálogo)

**Interfaces:**
- Consumes: el punto 8 ya corregido en la Task 2.
- Produces: siete nombres de página nuevos que la Task 5 listará en la base y que otras notas enlazarán: `sesion-cae-en-el-panel`, `semanal-auto-dispara-mutaciones`, `bitacora-drawer-sin-profesional`, `reset-legacy-pisa-adaptadores`, `gate-visual-tolerancia-enganosa`, `captura-playwright-miente`, `pdc-legend-item-clase-compartida`.

- [ ] **Step 1: Leer la nota completa antes de repartirla**

Run: `cat memoria/trampas/browser-qa-pitfalls.md`

No repartas de memoria: el texto tiene detalles operativos (comandos, identificadores, rutas) que deben viajar **verbatim** a la página destino. Perder uno de esos detalles convierte una trampa útil en una anécdota.

- [ ] **Step 2: Crear las seis páginas de un hecho**

Cada una con este frontmatter, cambiando lo que corresponda:

```yaml
---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "<una línea, sin comillas internas>"
---
```

Reparto, con el `areas` de cada una y el cuerpo que hay que trasladar:

| Página | `areas` | Qué punto de la nota vieja lleva |
|---|---|---|
| `sesion-cae-en-el-panel.md` | `[qa]` | Punto 1 completo, incluido el diagnóstico que exonera al servidor y la mitigación |
| `semanal-auto-dispara-mutaciones.md` | `[qa, lps]` | Punto 2, con la alternativa `/dashboard/escalamientos` |
| `bitacora-drawer-sin-profesional.md` | `[qa, lps]` | Punto 4, con el id 366 y la fixture de `page.route()` |
| `reset-legacy-pisa-adaptadores.md` | `[design-system]` | Punto 6, con la capa `legacy-overrides` y los dos ejemplos |
| `gate-visual-tolerancia-enganosa.md` | `[qa, design-system]` | Punto 7, con el 3 % y `--update-snapshots=all` |
| `captura-playwright-miente.md` | `[qa, pdc]` | Punto 9, con el `try/finally` y el consejo de mirar el log del contenedor |

Cada página termina con una línea de enlaces a sus vecinas, por ejemplo en `sesion-cae-en-el-panel.md`:

```markdown
Relacionado: [[semanal-auto-dispara-mutaciones]], [[captura-playwright-miente]].
```

- [ ] **Step 3: Crear `pdc-legend-item-clase-compartida.md`**

Con el texto **ya corregido** en la Task 2, frontmatter `areas: [design-system, lps]`, `fecha: 2026-08-03`, y cerrando con:

```markdown
Relacionado: [[css-layer-cascade]], [[reset-legacy-pisa-adaptadores]].
```

- [ ] **Step 4: Anexar el caso de Playwright a `path-with-space-esm-guard-noop.md`**

Añadir al final, antes de cualquier línea de «Relacionado»:

```markdown
**El mismo espacio muerde al importar Playwright desde un worktree:** Playwright vive solo en el
`node_modules` del checkout principal, así que hay que importarlo con URL absoluta —
`file:///Volumes/Crucial%20X6/Developer/lps-aia/node_modules/playwright/index.mjs` — con el espacio
codificado como `%20`.
```

- [ ] **Step 5: Absorber lo fusionado y dejar constancia**

En `memoria/trampas/servir-worktree-stack-efimero.md`, añadir al final:

```markdown
> Esta página deroga lo que `browser-qa-pitfalls` recomendaba antes: servir un worktree con un
> `docker run` suelto. El toolchain asume compose, así que aquel atajo apunta al stack de la sesión
> vecina. Ver también [[aislar-stack-docker-por-worktree]].
```

- [ ] **Step 6: Retirar la nota vieja y actualizar el índice**

```bash
git rm memoria/trampas/browser-qa-pitfalls.md
```

En `memoria/index.md`, eliminar la fila de `browser-qa-pitfalls` de la tabla de trampas y añadir las siete nuevas, en orden alfabético, con su resumen de una línea.

Después, buscar referencias colgando:

```bash
grep -rn "browser-qa-pitfalls" memoria/ docs/ CLAUDE.md AGENTS.md
```

Cada aparición en `memoria/` debe repuntarse a la página nueva que corresponda a ese contexto — en `mapas/qa-y-gates.md` son varias y no todas van al mismo sitio: la de caídas de sesión va a `sesion-cae-en-el-panel`, la de mutaciones automáticas a `semanal-auto-dispara-mutaciones`, y así.

- [ ] **Step 7: Verificar**

Run: `node scripts/wiki-lint.mjs`

Expected: **ninguna línea `MULTIHECHO`** y **ninguna línea `ENLACE`**. Siguen las de `AREA`.

Comprobar además que no se perdió ningún hecho:

```bash
git show HEAD:memoria/trampas/browser-qa-pitfalls.md | grep -cE "^[0-9]+\. |^\*\*[0-9]+\."
```

Expected: 10. Los diez deben estar localizables: seis en páginas nuevas, uno en `pdc-legend-item-clase-compartida`, uno anexado a `path-with-space-esm-guard-noop`, y dos absorbidos por las páginas de Docker.

- [ ] **Step 8: Commit**

```bash
git add -A memoria/
git commit -m "refactor(wiki): reparte la nota que empaquetaba diez hechos

browser-qa-pitfalls.md violaba la regla «una nota, un hecho» que la propia
wiki fija, y dos de sus puntos ya estaban derogados por páginas más
recientes sobre aislamiento de stacks Docker.

Seis hechos pasan a página propia; pdc-legend-item va a la suya ya con el
estado medido hoy; el caso de Playwright desde un worktree se anexa a la
nota de la ruta con espacio, que es el mismo hecho; y los dos puntos sobre
servir un worktree quedan absorbidos por servir-worktree-stack-efimero, que
deja constancia de que los deroga.

Todas heredan origen: lps-aia-browser-qa-pitfalls. Ningún hecho se pierde.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: Cerrar el vocabulario de `areas`

**Files:**
- Modify: todas las páginas de `memoria/` cuyo `areas` use valores fuera de la lista
- Modify: `memoria/index.md` (documentar la lista)

**Interfaces:**
- Consumes: la lista cerrada declarada en «Global Constraints» y ya codificada en `scripts/wiki-lint.mjs`.
- Produces: `areas` normalizado, que la Task 5 usará para agrupar en la base.

- [ ] **Step 1: Ver qué hay que cambiar**

Run: `node scripts/wiki-lint.mjs | grep '^AREA'`

Anota la lista. Debe contener `ui`, `shell`, `rutas`, `entorno`, `sesiones`, `git`, `tooling`, `goals`, `arquitectura`.

- [ ] **Step 2: Aplicar el mapeo**

```bash
node -e '
const {readdirSync,readFileSync,writeFileSync}=require("fs");const {join,extname}=require("path");
const MAPA={ui:"design-system",shell:"design-system",rutas:"rbac",entorno:"docker",
  sesiones:"proceso",git:"proceso",tooling:"proceso",goals:"proceso",arquitectura:"proceso"};
const notas=[];(function r(d){for(const e of readdirSync(d,{withFileTypes:true})){const p=join(d,e.name);
if(e.isDirectory())r(p);else if(extname(e.name)===".md")notas.push(p);}})("memoria");
let n=0;
for(const p of notas){let t=readFileSync(p,"utf8");const antes=t;
t=t.replace(/^areas:\s*\[(.*)\]$/m,(m,lista)=>{
  const v=[...new Set(lista.split(",").map(s=>s.trim()).filter(Boolean).map(a=>MAPA[a]??a))];
  return "areas: ["+v.join(", ")+"]";});
if(t!==antes){writeFileSync(p,t);n++;}}
console.log("páginas normalizadas:",n);
'
```

Revisa a mano dos casos que el mapeo automático deja a medias:
- `entorno-y-despliegue.md` debe quedar `areas: [docker, deploy]`, no solo `docker`.
- `siteground-sin-tunel-ssh.md` y `produccion-deploy.md` deben ser `[deploy]`.

- [ ] **Step 3: Documentar la lista en `index.md`**

Añadir en la sección de reglas de escritura:

```markdown
**Áreas válidas** (lista cerrada; `scripts/wiki-lint.mjs` la comprueba): `design-system` · `qa` ·
`docker` · `worktrees` · `pdc` · `lps` · `datos` · `rbac` · `deploy` · `bi` · `admin` · `proceso`.
Si necesitas una nueva, añádela primero al script y explica aquí qué cubre; una lista que crece
sin control deja de servir para filtrar.
```

- [ ] **Step 4: Verificar**

Run: `node scripts/wiki-lint.mjs`

Expected: **ninguna línea `AREA`**. Si el script ya no reporta nada en absoluto, sale con código 0.

- [ ] **Step 5: Commit**

```bash
git add memoria/
git commit -m "refactor(wiki): cierra el vocabulario de areas en doce valores

Convivían ui, shell y design-system para lo mismo, y entorno se solapaba
con docker y deploy. Con 42 páginas no molestaba; con cien, filtrar por
área dejaría fuera lo que buscas.

docker y worktrees se mantienen separados a propósito: son las dos trampas
que más veces han mordido y conviene filtrarlas por separado.

La lista queda documentada en index.md y comprobada por wiki-lint.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: Catálogo generado con Bases

**Files:**
- Create: `memoria/paginas.base`
- Modify: `memoria/index.md`

**Interfaces:**
- Consumes: `tipo`, `resumen`, `areas` y `fecha` del frontmatter, ya normalizados.
- Produces: tres vistas embebibles. Si Bases no renderiza, se revierte solo `index.md`.

- [ ] **Step 1: Crear `memoria/paginas.base`**

```yaml
filters:
  and:
    - file.inFolder("memoria")

properties:
  note.resumen:
    displayName: De qué va
  note.areas:
    displayName: Áreas
  note.fecha:
    displayName: Fecha
  note.estado:
    displayName: Estado

views:
  - type: table
    name: Decisiones
    filters:
      and:
        - 'note.tipo == "decision"'
    order:
      - file.name
      - note.resumen
      - note.areas
      - note.fecha
  - type: table
    name: Trampas
    filters:
      and:
        - 'note.tipo == "trampa"'
    order:
      - file.name
      - note.resumen
      - note.areas
      - note.fecha
  - type: table
    name: Referencias
    filters:
      and:
        - 'note.tipo == "referencia"'
    order:
      - file.name
      - note.resumen
      - note.fecha
```

- [ ] **Step 2: Comprobar que Obsidian la renderiza ANTES de tocar el índice**

Abrir `memoria/paginas.base` en Obsidian (vault = raíz del repositorio). Las tres vistas deben listar páginas: decisiones 6, trampas 22 tras el reparto de la Task 3, referencias 2.

Si no renderiza o las vistas salen vacías, **para aquí**: no sustituyas las tablas del índice. Deja el `.base` creado, anótalo en el log como intento fallido y salta a la Task 6. El catálogo manual sigue funcionando.

Contar en disco para comparar:

```bash
grep -l "^tipo: decision" memoria/**/*.md | wc -l
grep -l "^tipo: trampa" memoria/**/*.md | wc -l
grep -l "^tipo: referencia" memoria/**/*.md | wc -l
```

- [ ] **Step 3: Sustituir las tres tablas del índice**

En `memoria/index.md`, reemplazar las tablas de las secciones «Decisiones», «Trampas» y
«Referencias» por:

```markdown
![[paginas.base]]
```

**No toques** la tabla de mapas, la de las tres capas, ni las secciones de las tres operaciones y
las reglas de escritura: eso es prosa con criterio, no catálogo.

Si el embebido con `![[…]]` no renderiza, prueba con un bloque de código de tipo `base` que
referencie el archivo; si ninguna de las dos formas funciona, revierte esta tarea y quédate con
las tablas manuales.

- [ ] **Step 4: Verificar**

Run: `node scripts/wiki-lint.mjs`

Expected: sin hallazgos, código 0. El verificador acepta que una página no esté citada en
`index.md` cuando existe `memoria/paginas.base`.

Comprobar en Obsidian que `index.md` sigue siendo legible y que las vistas aparecen embebidas.

- [ ] **Step 5: Commit**

```bash
git add memoria/paginas.base memoria/index.md
git commit -m "feat(wiki): genera el catálogo del índice con Obsidian Bases

index.md tenía 110 líneas, 31 de ellas filas de catálogo escritas a mano
que había que tocar en cada ingesta. Las tres tablas (decisiones, trampas,
referencias) pasan a vistas generadas desde el frontmatter.

La prosa se queda: las tres capas, las tres operaciones, las reglas de
escritura y la tabla de mapas son criterio, no catálogo.

Bases es nativo, así que no añade dependencias. Si no renderizara, basta
revertir index.md: ninguna página depende de la base.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: Cierre y verificación completa

**Files:**
- Modify: `memoria/log.md`

**Interfaces:**
- Consumes: el resultado de todas las tareas anteriores.
- Produces: nada.

- [ ] **Step 1: Verificación completa**

```bash
node scripts/wiki-lint.mjs
```

Expected: `Sin hallazgos.` y código 0.

- [ ] **Step 2: Verificar en un clon fresco**

```bash
T=$(mktemp -d) && git clone -q --local --no-hardlinks . "$T/c" \
  && (cd "$T/c" && node scripts/wiki-lint.mjs) ; rm -rf "$T"
```

Expected: `Sin hallazgos.` también ahí. Si aquí falla y en local no, hay un enlace que apunta a algo sin versionar.

- [ ] **Step 3: Anotar en el log**

Añadir al final de `memoria/log.md`, una línea por operación realizada, con este formato:

```markdown
- 2026-08-03 · lint · Primera pasada con `scripts/wiki-lint.mjs`: tres afirmaciones corregidas contra medición del repositorio, una nota de diez hechos repartida en siete páginas, vocabulario de `areas` cerrado en doce valores y catálogo del índice generado con Bases · [[index]], 7 páginas nuevas
```

Si la Task 5 se abandonó porque Bases no renderizaba, dilo en la línea en vez de omitirlo.

- [ ] **Step 4: Commit**

```bash
git add memoria/log.md
git commit -m "docs(wiki): anota la pasada de lint en la bitácora

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Autorrevisión del plan

**Cobertura del spec:** las tres correcciones → Task 2 · el reparto de la nota → Task 3 · el vocabulario → Task 4 · Bases → Task 5 · la verificación (enlaces, cobertura del reparto, clon fresco, áreas, índice) → Tasks 1 y 6. Ningún apartado del spec queda sin tarea.

**Placeholders:** ninguno. Todo texto que hay que escribir está literal; el único «rellena esto» es el `resumen` de cada página nueva, que depende del contenido que se traslade y por eso lleva la instrucción de leer la nota verbatim antes.

**Consistencia:** los siete nombres de página declarados en la Task 3 son los mismos que se usan en la tabla de reparto y en las líneas de «Relacionado». La lista de doce áreas es idéntica en «Global Constraints», en el script de la Task 1 y en el texto de la Task 4.

**Riesgo asumido:** la Task 5 depende de una función reciente de Obsidian y de una sintaxis de embebido que la documentación oficial no fija con claridad. Por eso su Step 2 es un gate explícito con salida limpia: si no renderiza, se abandona sin dejar la wiki peor de como estaba.
