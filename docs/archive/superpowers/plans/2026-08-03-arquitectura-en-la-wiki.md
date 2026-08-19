# Arquitectura del proyecto en la wiki — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que la wiki `memoria/` responda «qué módulos hay, qué expone cada uno, quién puede usarlo y dónde encaja en el flujo», con el inventario extraído del código por un script y la prosa escrita a mano protegida por marcadores, y retirar `docs/ROUTES.md` sin perder nada suyo.

**Architecture:** Un manifiesto declarativo (`scripts/wiki-arquitectura.modulos.mjs`) asigna cada ruta de `public/index.php` a exactamente un módulo; el generador (`scripts/wiki-arquitectura.mjs`) falla si alguna queda huérfana. El generador escribe **solo** entre `<!-- generado:inicio -->` y `<!-- generado:fin -->` de cada página de `memoria/arquitectura/`; todo lo demás es prosa humana que sobrevive a la regeneración. Las capacidades RBAC no se adivinan por regex: se piden a `RbacManager` ejecutándolo de verdad dentro del contenedor.

**Tech Stack:** Node.js (ESM, sin dependencias), PHP 8.3 dentro de Docker Compose (servicio `app`), Markdown con frontmatter YAML, Obsidian Bases.

## Global Constraints

- **Rama:** se trabaja **directo en `main`**, autorizado por el usuario el 2026-08-03. Los commits que este plan indica están autorizados; cualquier otro, no.
- **`docs/` no se edita.** Excepciones vivas y únicas: la sección «Archivos de este goal» al pie de cada `goals/*/goal.md` (ya aplicada, no se amplía) y el **retiro de `docs/ROUTES.md`**, autorizado explícitamente por el spec y con gate propio en la Task 7.
- **`docs/ROUTES.md` no está en git**: está ignorado en `.gitignore:193`. `git rm` no sirve; se borra con `rm` y se quita la línea del `.gitignore`.
- **La ruta del repositorio contiene un espacio** (`/Volumes/Crucial X6/…`). En scripts ESM, `file://${process.argv[1]}` es no-op: usar `pathToFileURL`. Ver `memoria/trampas/path-with-space-esm-guard-noop.md`.
- **Frontmatter obligatorio** en toda página de `memoria/`: `tipo`, `estado`, `fecha`, `areas`, `fuente`, `resumen`. El `resumen` es la columna del catálogo: si cambia el cuerpo, cambia el resumen.
- **Lista cerrada de `areas`** (trece, exactamente estos valores): `design-system`, `qa`, `docker`, `worktrees`, `pdc`, `lps`, `datos`, `rbac`, `deploy`, `bi`, `admin`, `proceso`, `arquitectura`. **No se añade ninguna** en este plan.
- **Tipos nuevos:** `modulo` y `flujo` se añaden a `TIPOS` en `scripts/wiki-lint.mjs` (Task 6) **y** como vistas en `memoria/paginas.base`, porque el verificador exige que cada página esté enlazada desde `index.md` o cubierta por una vista.
- **Colisiones de nombre prohibidas.** El verificador marca `ENLACE ambiguo` cuando dos archivos comparten basename en todo el vault. Ya existen `arquitectura.md`, `design-system.md`, `pdc.md`, `estado.md`, `index.md`, `log.md`, `lps-dominio.md`. Por eso el módulo de compras se llama `plan-de-compras`, el del laboratorio `laboratorio-design-system`, y los flujos `flujo-lps` y `flujo-pdc` — **nunca** `pdc.md`.
- **El generador no inventa.** Lo que no pueda determinar se escribe literalmente como `_indeterminado_`. Esto es esperable y frecuente en el carril legado (`src/Legacy/`), donde las rutas hacen `require_once` de scripts procedurales: ahí los servicios y las tablas se marcan indeterminados y se rellenan a mano en la prosa, o se dejan vacíos. Preferimos un hueco visible a un dato falso.
- **`node scripts/wiki-lint.mjs` debe terminar en verde**, en local y en un clon fresco, antes del commit final.

---

## Estructura de archivos

**Se crea:**

- `scripts/wiki-arquitectura.modulos.mjs` — manifiesto. Responsabilidad única: decir qué módulos existen y qué prefijos de ruta y capacidades RBAC le tocan a cada uno. Es el único archivo que se edita cuando aparece un módulo nuevo.
- `scripts/wiki-arquitectura.mjs` — generador. Responsabilidad única: leer el código, componer las zonas generadas y escribirlas entre marcadores. Nunca toca prosa.
- `scripts/wiki-arquitectura-rbac.php` — vuelca a JSON el mapa real de capacidades por rol invocando `RbacManager`. Se ejecuta dentro del contenedor `app`.
- `memoria/arquitectura/<slug>.md` × 23 — una página por módulo.
- `memoria/flujos/flujo-lps.md` y `memoria/flujos/flujo-pdc.md`.

**Se modifica:**

- `scripts/wiki-lint.mjs` — añadir `modulo` y `flujo` a `TIPOS`.
- `memoria/paginas.base` — dos vistas nuevas.
- `memoria/index.md` — sección de arquitectura y flujos, y mención al generador.
- `AGENTS.md` y `CLAUDE.md` — las referencias a `docs/ROUTES.md` pasan a la wiki.
- `.gitignore` — se retira la línea 193.
- `memoria/log.md` — una línea por operación.

**Se retira:**

- `docs/ROUTES.md`, solo tras el gate de la Task 7.

**Reparto de las 222 rutas.** Medido el 2026-08-03 con
`grep -cE "\$router->(get|post|put|delete|any)\(" public/index.php`. Si al ejecutar el plan la
cifra difiere, **usa la que midas** y anótalo en el log: el manifiesto está hecho para acusar
cualquier ruta nueva sin módulo, que es exactamente lo que debe pasar.

---

### Task 1: Manifiesto y cobertura total de rutas

**Files:**
- Create: `scripts/wiki-arquitectura.modulos.mjs`
- Create: `scripts/wiki-arquitectura.mjs`

**Interfaces:**
- Consumes: `public/index.php`.
- Produces:
  - `MODULOS` — array exportado por el manifiesto. Cada entrada: `{ slug, titulo, areas, flujo, rutas, capacidades, nota }`, donde `rutas` es un array de prefijos y `flujo` es `'lps'`, `'pdc'`, `'ambos'` o `null`.
  - `node scripts/wiki-arquitectura.mjs --cobertura` — imprime el reparto y sale con código 1 si alguna ruta queda sin módulo o si un módulo declara un prefijo que no casa con nada.
  - Función interna `leerRutas()` que devuelve `[{ verbo, path, destino, tipo }]`, con `tipo` ∈ `'controlador' | 'legado'`.

- [ ] **Step 1: Medir el terreno antes de escribir el manifiesto**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
grep -cE "\$router->(get|post|put|delete|any)\(" public/index.php
grep -oE "\$router->(get|post|put|delete|any)\('[^']+'" public/index.php | sed "s/.*('//;s/'$//" | sort -u | wc -l
git status --porcelain
```

Expected: 222 rutas declaradas, 207 paths únicos (hay paths con dos verbos), árbol limpio.

- [ ] **Step 2: Escribir el manifiesto**

Crear `scripts/wiki-arquitectura.modulos.mjs`:

```javascript
// Manifiesto de módulos de la aplicación. Lo lee scripts/wiki-arquitectura.mjs.
//
// `rutas` son PREFIJOS: una ruta casa si es igual al prefijo o empieza por
// `<prefijo>/`. Gana el prefijo más largo, así que '/api/pdc/auto' se lleva lo
// suyo aunque '/api/pdc' también case. Toda ruta de public/index.php debe casar
// con exactamente un módulo: el generador falla si alguna queda huérfana.
//
// `capacidades` son claves del mapa que devuelve App\Security\RbacManager::getCapabilities().
// `flujo`: 'lps' | 'pdc' | 'ambos' | null.

export const MODULOS = [
  {
    slug: 'autenticacion',
    titulo: 'Autenticación',
    areas: ['rbac', 'arquitectura'],
    flujo: null,
    rutas: ['/', '/login', '/logout', '/password', '/dev/entrar'],
    capacidades: [],
    nota: 'La puerta de servicio /dev/entrar solo se registra en desarrollo.',
  },
  {
    slug: 'selector-de-proyectos',
    titulo: 'Selector de proyectos',
    areas: ['rbac', 'arquitectura'],
    flujo: null,
    rutas: ['/proyectos', '/proyecto'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'programa-general',
    titulo: 'Programa General',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programa-general', '/api/general', '/api/pg'],
    capacidades: ['canEditGeneralProgram', 'canManageGeneralProgram',
      'canEditPastGeneralProgram', 'canDeleteRows'],
    nota: '',
  },
  {
    slug: 'cronograma',
    titulo: 'Actualizar cronograma',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programa-general-actualizar'],
    capacidades: ['canEditGeneralProgram'],
    nota: '',
  },
  {
    slug: 'programacion-intermedia',
    titulo: 'Programación Intermedia',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-intermedia', '/api/pi'],
    capacidades: ['canEditMediumTerm', 'canManageMediumTermProgram', 'canEditConstraints'],
    nota: '',
  },
  {
    slug: 'programacion-semanal',
    titulo: 'Programación Semanal',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-semanal', '/api/semanal'],
    capacidades: ['canEditWeeklyProgram', 'canManageWeeklyProgram', 'canManageWeeks'],
    nota: '',
  },
  {
    slug: 'submodulo-cnp',
    titulo: 'CNP — Causas de No Programación',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-semanal/cnp', '/api/cnp'],
    capacidades: ['canEditWeeklyProgram'],
    nota: '',
  },
  {
    slug: 'submodulo-cnc',
    titulo: 'CNC — Causas de No Cumplimiento',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-semanal/cnc', '/api/cnc'],
    capacidades: ['canEditWeeklyProgram'],
    nota: '',
  },
  {
    slug: 'submodulo-cic',
    titulo: 'CIC — Cumplimiento de Actividades',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-semanal/cic', '/api/cic'],
    capacidades: ['canEditWeeklyProgram'],
    nota: '',
  },
  {
    slug: 'plan-de-compras',
    titulo: 'Plan de Compras v2',
    areas: ['pdc', 'arquitectura'],
    flujo: 'pdc',
    rutas: ['/plan-compras'],
    capacidades: ['canManagePdC', 'canManageContracts'],
    nota: 'SPA React en pdc-app/, bundle en public/pdc-app/. Sub-router por hash.',
  },
  {
    slug: 'listado-de-actividades',
    titulo: 'Listado de Actividades (PDC v1)',
    areas: ['pdc', 'arquitectura'],
    flujo: 'pdc',
    rutas: ['/pdc', '/api/pdc'],
    capacidades: ['canManagePdC'],
    nota: '',
  },
  {
    slug: 'contratos',
    titulo: 'Contratos y definición semiautomática',
    areas: ['pdc', 'arquitectura'],
    flujo: 'pdc',
    rutas: ['/api/pdc/auto'],
    capacidades: ['canManageContracts', 'canAutoDefineContracts'],
    nota: 'Reparto de criterio: /api/pdc/auto/* se atribuye a Contratos por ser el '
      + 'contrato auto/preview·apply·undo·feedback·metrics que define contratos; el resto de '
      + '/api/pdc/* queda en Listado de Actividades. Verificable leyendo '
      + 'src/Controllers/Api/PdcAutoGenerateController.php y src/Services/SemiAutoService.php.',
  },
  {
    slug: 'profesionales',
    titulo: 'Profesionales',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/profesionales', '/api/profesionales'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'subcontratistas',
    titulo: 'Subcontratistas',
    areas: ['lps', 'arquitectura'],
    flujo: 'ambos',
    rutas: ['/subcontratistas', '/api/subcontratistas'],
    capacidades: ['canManageContracts'],
    nota: '',
  },
  {
    slug: 'control-de-cambios',
    titulo: 'Control de Cambios',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/control-cambios', '/api/control-cambios'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'indicadores',
    titulo: 'Indicadores LPS',
    areas: ['lps', 'bi', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/indicadores', '/api/indicadores'],
    capacidades: ['canSeeReports'],
    nota: '',
  },
  {
    slug: 'torre-de-control-bi',
    titulo: 'Torre de Control BI',
    areas: ['bi', 'arquitectura'],
    flujo: 'ambos',
    rutas: ['/bi', '/api/bi'],
    capacidades: ['canSeeReports'],
    nota: '',
  },
  {
    slug: 'integracion',
    titulo: 'Integración de reportes',
    areas: ['datos', 'arquitectura'],
    flujo: null,
    rutas: ['/reportes'],
    capacidades: ['canSeeReports'],
    nota: '',
  },
  {
    slug: 'escalamientos-y-crisis',
    titulo: 'Escalamientos, crisis y avisos',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/dashboard', '/api/lps', '/api/notifications'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'nucleo-y-runtime',
    titulo: 'Núcleo, sesión y runtime',
    areas: ['arquitectura', 'design-system'],
    flujo: null,
    rutas: ['/session', '/context', '/runtime'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'legado',
    titulo: 'Carril legado',
    areas: ['arquitectura'],
    flujo: null,
    rutas: ['/legacy'],
    capacidades: [],
    nota: 'Rutas que hacen require_once de scripts procedurales: servicios y tablas '
      + 'saldrán indeterminados por diseño.',
  },
  {
    slug: 'panel-admin',
    titulo: 'Panel de administración',
    areas: ['admin', 'arquitectura'],
    flujo: null,
    rutas: [],
    capacidades: [],
    nota: 'Mini-app aislada con su propio front controller (admin/index.php) y su propio '
      + 'router. Ninguna de sus rutas pasa por public/index.php, por eso la zona generada '
      + 'de rutas queda vacía a propósito.',
  },
  {
    slug: 'laboratorio-design-system',
    titulo: 'Laboratorio del design system',
    areas: ['design-system', 'arquitectura'],
    flujo: null,
    rutas: ['/internal'],
    capacidades: ['internal.design_system.view'],
    nota: 'La capacidad real es la constante RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW; '
      + 'si el valor de la constante cambia, hay que actualizar esta clave.',
  },
];
```

**Sobre `'internal.design_system.view'`:** ese es el valor que se espera de
`RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW`. Compruébalo antes de dar la tarea por buena:

```bash
grep -n "PERM_INTERNAL_DESIGN_SYSTEM_VIEW" src/Security/RbacCatalog.php
```

Si el literal es otro, **usa el que veas**. El generador avisará igualmente (Task 2, Step 4:
una capacidad declarada que no existe en el mapa se reporta como error, no se ignora).

- [ ] **Step 3: Escribir el generador, primera versión (solo rutas y cobertura)**

Crear `scripts/wiki-arquitectura.mjs`:

```javascript
#!/usr/bin/env node
// Genera las zonas <!-- generado --> de memoria/arquitectura/.
// Escribe SOLO entre marcadores: la prosa de fuera nunca se toca.
// Ver memoria/index.md y docs/superpowers/plans/2026-08-03-arquitectura-en-la-wiki.md.
import { readFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { MODULOS } from './wiki-arquitectura.modulos.mjs';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');

// --- Rutas -----------------------------------------------------------------

export function leerRutas() {
  const fuente = readFileSync(join(RAIZ, 'public/index.php'), 'utf8');
  const rutas = [];
  const re = /\$router->(get|post|put|delete|any)\(\s*'([^']+)'\s*,/g;
  for (const m of fuente.matchAll(re)) {
    const cola = fuente.slice(m.index + m[0].length, m.index + m[0].length + 400);
    const ctrl = cola.match(/\\?([A-Za-z0-9_\\]+)::class\s*,\s*'([A-Za-z0-9_]+)'/);
    const legado = cola.match(/require_once\s+PROJECT_ROOT\s*\.\s*'([^']+)'/);
    rutas.push({
      verbo: m[1].toUpperCase(),
      path: m[2],
      destino: ctrl ? `${ctrl[1].replace(/^\\/, '')}::${ctrl[2]}`
        : legado ? legado[1].replace(/^\//, '')
        : '_indeterminado_',
      tipo: ctrl ? 'controlador' : legado ? 'legado' : 'indeterminado',
    });
  }
  return rutas.sort((a, b) => (a.path + a.verbo).localeCompare(b.path + b.verbo));
}

// Gana el prefijo más largo. '/' solo casa consigo mismo.
export function asignar(path) {
  let mejor = null;
  for (const mod of MODULOS) {
    for (const p of mod.rutas) {
      const casa = p === '/' ? path === '/' : (path === p || path.startsWith(p + '/'));
      if (casa && (!mejor || p.length > mejor.prefijo.length)) mejor = { mod, prefijo: p };
    }
  }
  return mejor;
}

// --- Cobertura -------------------------------------------------------------

function cobertura() {
  const rutas = leerRutas();
  const porModulo = new Map(MODULOS.map((m) => [m.slug, []]));
  const huerfanas = [];
  for (const r of rutas) {
    const a = asignar(r.path);
    if (!a) huerfanas.push(r);
    else porModulo.get(a.mod.slug).push(r);
  }

  for (const m of MODULOS) {
    console.log(`${String(porModulo.get(m.slug).length).padStart(4)}  ${m.slug}`);
  }
  console.log(`${String(rutas.length).padStart(4)}  TOTAL`);

  const errores = [];
  for (const r of huerfanas) errores.push(`HUERFANA ${r.verbo} ${r.path}`);
  for (const m of MODULOS) {
    for (const p of m.rutas) {
      const usado = rutas.some((r) => (p === '/' ? r.path === '/' : r.path === p || r.path.startsWith(p + '/')));
      if (!usado) errores.push(`PREFIJO MUERTO ${m.slug}: ${p}`);
    }
  }
  const sinDestino = rutas.filter((r) => r.tipo === 'indeterminado');
  for (const r of sinDestino) errores.push(`DESTINO INDETERMINADO ${r.verbo} ${r.path}`);

  if (errores.length) {
    console.log('\n' + errores.join('\n'));
    console.log(`\n${errores.length} problemas de cobertura.`);
    process.exit(1);
  }
  console.log('\nCobertura completa: ninguna ruta queda sin módulo.');
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  if (process.argv.includes('--cobertura')) cobertura();
  else console.log('Uso: node scripts/wiki-arquitectura.mjs [--cobertura | --escribir]');
}
```

- [ ] **Step 4: Ejecutar la cobertura**

Run: `node scripts/wiki-arquitectura.mjs --cobertura`

Expected: una línea por módulo, `222 TOTAL`, y `Cobertura completa`.

Si aparece `HUERFANA`, **el manifiesto está incompleto**: añade el prefijo al módulo que
corresponda por dominio, no un módulo cajón de sastre. Si aparece `PREFIJO MUERTO`, sobra un
prefijo. Si aparece `DESTINO INDETERMINADO`, la regex no supo leer esa declaración: arregla la
regex, no borres el chequeo.

Comprobación cruzada de que no se pierde ni se duplica ninguna ruta:

```bash
node scripts/wiki-arquitectura.mjs --cobertura | awk '$2!="TOTAL"{s+=$1} END{print "suma módulos:", s}'
grep -cE "\$router->(get|post|put|delete|any)\(" public/index.php
```

Expected: las dos cifras iguales.

- [ ] **Step 5: Commit**

```bash
git add scripts/wiki-arquitectura.mjs scripts/wiki-arquitectura.modulos.mjs
git commit -m "feat(wiki): manifiesto de módulos con cobertura total de rutas

El manifiesto declara los 23 módulos reales de la aplicación y los prefijos
de ruta de cada uno. El generador reparte las 222 rutas de public/index.php
por prefijo más largo y falla si alguna queda huérfana, si un prefijo no
casa con nada o si no supo leer el destino de una declaración.

Esa es la garantía de que el inventario no envejece en silencio: una ruta
nueva sin módulo rompe el script en vez de desaparecer del mapa.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: Controladores, servicios, tablas y capacidades RBAC

**Files:**
- Create: `scripts/wiki-arquitectura-rbac.php`
- Modify: `scripts/wiki-arquitectura.mjs`

**Interfaces:**
- Consumes: `leerRutas()` y `asignar()` de la Task 1.
- Produces:
  - `scripts/wiki-arquitectura-rbac.php` — imprime por stdout un JSON `{ "<ROL>": { "<capacidad>": true|false } }`.
  - `leerRbac()` — devuelve ese objeto ya parseado, o lanza si Docker no responde.
  - `serviciosDe(rutaArchivoControlador)` → `string[]` ordenado.
  - `tablasDe(rutasArchivo[])` → `string[]` ordenado.
  - `node scripts/wiki-arquitectura.mjs --datos <slug>` — vuelca a stdout lo extraído de un módulo, para inspección manual.

- [ ] **Step 1: Escribir el volcador de RBAC**

Las capacidades no se leen con regex: se le preguntan a la clase que manda. Crear
`scripts/wiki-arquitectura-rbac.php`:

```php
<?php
// Vuelca a JSON el mapa real de capacidades por rol.
// Se ejecuta dentro del contenedor: docker compose exec -T app php scripts/wiki-arquitectura-rbac.php
require __DIR__ . '/../vendor/autoload.php';

$roles = ['A', 'D', 'R', 'DCV', 'OT', 'G', 'S', 'SG', 'C', 'V'];
$salida = [];
foreach ($roles as $rol) {
    $salida[$rol] = \App\Security\RbacManager::getCapabilities($rol);
}
echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
```

- [ ] **Step 2: Comprobar que el contenedor lo ejecuta**

```bash
docker compose ps --status running --services
docker compose exec -T app php scripts/wiki-arquitectura-rbac.php | head -20
```

Expected: `app`, `db` y `adminer` corriendo, y un JSON cuyo primer bloque es el rol `A` con
`"isSystemAdmin": true`.

Si el stack no está levantado: `docker compose up -d db app adminer`. Si la lista de roles no
coincide con la de `RbacCatalog`, corrige la lista de `$roles` con la del catálogo:

```bash
grep -nE "'(A|D|R|DCV|OT|G|S|SG|C|V)'" src/Security/RbacCatalog.php | head -20
```

- [ ] **Step 3: Añadir los extractores al generador**

En `scripts/wiki-arquitectura.mjs`, añadir tras `asignar()`:

```javascript
import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';

// --- RBAC ------------------------------------------------------------------

export function leerRbac() {
  const salida = execFileSync('docker', [
    'compose', 'exec', '-T', 'app', 'php', 'scripts/wiki-arquitectura-rbac.php',
  ], { cwd: RAIZ, encoding: 'utf8' });
  return JSON.parse(salida.slice(salida.indexOf('{')));
}

// --- Servicios y tablas ----------------------------------------------------

const RUIDO_SQL = new Set(['select', 'from', 'where', 'join', 'inner', 'left', 'right', 'outer',
  'on', 'as', 'and', 'or', 'set', 'values', 'into', 'update', 'insert', 'delete', 'group',
  'order', 'by', 'limit', 'offset', 'union', 'null', 'not', 'is', 'in', 'exists', 'case',
  'when', 'then', 'else', 'end', 'distinct', 'having', 'dual', 'if']);

export function serviciosDe(archivo) {
  if (!existsSync(archivo)) return [];
  const t = readFileSync(archivo, 'utf8');
  const s = new Set();
  for (const m of t.matchAll(/App\\(?:Services|Support)\\([A-Za-z0-9_\\]+)/g)) {
    s.add(m[1].replace(/\\/g, '\\'));
  }
  for (const m of t.matchAll(/new\s+([A-Z][A-Za-z0-9_]*(?:Service|Processor|Matcher|Resolver|Gate|Policy))\s*\(/g)) {
    s.add(m[1]);
  }
  return [...s].sort();
}

export function tablasDe(archivos) {
  const s = new Set();
  for (const a of archivos) {
    if (!existsSync(a)) continue;
    const t = readFileSync(a, 'utf8');
    for (const m of t.matchAll(/\b(?:FROM|JOIN|INTO|UPDATE)\s+`?([a-z][a-z0-9_]{2,})`?/gi)) {
      const nombre = m[1].toLowerCase();
      if (!RUIDO_SQL.has(nombre)) s.add(nombre);
    }
  }
  return [...s].sort();
}

// Traduce 'App\Controllers\Api\GeneralApiController::list' a la ruta del archivo.
export function archivoDeDestino(destino) {
  if (!destino.includes('::')) return null;
  const clase = destino.split('::')[0];
  if (!clase.startsWith('App\\')) return null;
  return join(RAIZ, 'src', clase.slice('App\\'.length).replace(/\\/g, '/') + '.php');
}
```

**Ojo con el escapado:** en JavaScript, la barra invertida de los namespaces PHP se escribe
doble. La regex `/App\\(?:Services|Support)\\([A-Za-z0-9_\\]+)/g` busca literalmente
`App\Services\…`. Si al probar no encuentra nada, es casi seguro un problema de escapado, no de
que el controlador no use servicios.

- [ ] **Step 4: Añadir el modo `--datos` y validar las capacidades declaradas**

Añadir al final, antes del bloque `if (process.argv[1] …)`:

```javascript
export function datosDe(mod, rutas, rbac) {
  const mias = rutas.filter((r) => asignar(r.path)?.mod.slug === mod.slug);
  const archivos = [...new Set(mias.map((r) => archivoDeDestino(r.destino)).filter(Boolean))];
  const servicios = [...new Set(archivos.flatMap((a) => serviciosDe(a)))].sort();
  const archivosServicio = servicios
    .map((s) => join(RAIZ, 'src/Services', s.replace(/\\/g, '/') + '.php'))
    .filter((p) => existsSync(p));
  const soloLegado = mias.length > 0 && mias.every((r) => r.tipo === 'legado');

  const capacidades = mod.capacidades.map((cap) => {
    const roles = Object.entries(rbac)
      .filter(([, mapa]) => mapa[cap] === true)
      .map(([rol]) => rol);
    const existe = Object.values(rbac).some((mapa) => cap in mapa);
    return { cap, roles, existe };
  });

  return {
    rutas: mias,
    controladores: [...new Set(mias.filter((r) => r.tipo === 'controlador')
      .map((r) => r.destino.split('::')[0]))].sort(),
    legados: [...new Set(mias.filter((r) => r.tipo === 'legado').map((r) => r.destino))].sort(),
    servicios,
    tablas: soloLegado ? null : tablasDe([...archivos, ...archivosServicio]),
    capacidades,
  };
}
```

Y en el despacho de argumentos:

```javascript
  else if (process.argv.includes('--datos')) {
    const slug = process.argv[process.argv.indexOf('--datos') + 1];
    const mod = MODULOS.find((m) => m.slug === slug);
    if (!mod) { console.error(`Módulo desconocido: ${slug}`); process.exit(1); }
    console.log(JSON.stringify(datosDe(mod, leerRutas(), leerRbac()), null, 2));
  }
```

- [ ] **Step 5: Probar la extracción en un módulo moderno y en uno legado**

```bash
node scripts/wiki-arquitectura.mjs --datos programa-general
node scripts/wiki-arquitectura.mjs --datos legado
```

Expected en `programa-general`: controladores `App\Controllers\Programacion\ProgramaGeneralController`
y `App\Controllers\Api\GeneralApiController`, una lista de servicios no vacía, tablas no vacías, y
`capacidades` con `canEditGeneralProgram` → roles `A, D, R, DCV` (contrástalo contra
`src/Security/RbacManager.php:26`, que es la lista `in_array($role, ['A','D','R','DCV'])`).

Expected en `legado`: `controladores` vacío, `legados` con los 6 scripts de `src/Legacy/`, y
`tablas: null` — que es como el generador dice «indeterminado». **Eso es correcto, no un fallo.**

Comprobar además que ninguna capacidad declarada es fantasma:

```bash
for s in $(node -e 'import("./scripts/wiki-arquitectura.modulos.mjs").then(m=>console.log(m.MODULOS.map(x=>x.slug).join(" ")))'); do
  node scripts/wiki-arquitectura.mjs --datos "$s" | grep -B2 '"existe": false' && echo "^^ en $s"
done
echo "revisión terminada"
```

Expected: ninguna salida antes de `revisión terminada`. Si alguna sale `"existe": false`, la clave
del manifiesto está mal escrita: corrígela contra `src/Security/RbacManager.php`.

- [ ] **Step 6: Commit**

```bash
git add scripts/wiki-arquitectura.mjs scripts/wiki-arquitectura-rbac.php
git commit -m "feat(wiki): extrae servicios, tablas y capacidades RBAC del código

Las capacidades no se adivinan por regex: se le preguntan a RbacManager
ejecutándolo de verdad dentro del contenedor, así que el mapa por rol es el
que la aplicación aplica, no una lectura aproximada del archivo.

Servicios y tablas sí son extracción textual, fiable en src/ y no en el
carril legado: cuando todas las rutas de un módulo hacen require_once de un
script procedural, las tablas quedan en null y la página lo dirá como
indeterminado en vez de inventarlas.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: Escritura entre marcadores e idempotencia

**Files:**
- Modify: `scripts/wiki-arquitectura.mjs`
- Create: `memoria/arquitectura/<slug>.md` × 23

**Interfaces:**
- Consumes: `datosDe()` de la Task 2.
- Produces:
  - `node scripts/wiki-arquitectura.mjs --escribir` — crea la página que falte y sustituye el contenido entre `<!-- generado:inicio -->` y `<!-- generado:fin -->` en las que ya existan.
  - Las 23 páginas con frontmatter válido, prosa esqueleto y zona generada.

- [ ] **Step 1: Añadir el compositor y el escritor**

En `scripts/wiki-arquitectura.mjs`, añadir `writeFileSync` y `mkdirSync` a los imports de
`node:fs`, y luego:

```javascript
const INICIO = '<!-- generado:inicio -->';
const FIN = '<!-- generado:fin -->';

function tabla(cabeceras, filas) {
  if (!filas.length) return '_Ninguno._\n';
  return `| ${cabeceras.join(' | ')} |\n| ${cabeceras.map(() => '---').join(' | ')} |\n`
    + filas.map((f) => `| ${f.join(' | ')} |`).join('\n') + '\n';
}

export function componer(mod, d) {
  const partes = [];

  partes.push('### Rutas\n');
  partes.push(mod.rutas.length === 0
    ? '_Este módulo no declara rutas en `public/index.php`._\n'
    : tabla(['Verbo', 'Ruta', 'Destino'],
        d.rutas.map((r) => [r.verbo, `\`${r.path}\``,
          r.tipo === 'legado' ? `\`${r.destino}\` (legado)` : `\`${r.destino}\``])));

  partes.push('\n### Controladores\n');
  partes.push(d.controladores.length
    ? d.controladores.map((c) => `- \`${c}\``).join('\n') + '\n'
    : '_Ninguno: ' + (d.legados.length ? 'carril legado.' : 'sin rutas propias.') + '_\n');

  if (d.legados.length) {
    partes.push('\n### Scripts legados\n');
    partes.push(d.legados.map((l) => `- \`${l}\``).join('\n') + '\n');
  }

  partes.push('\n### Servicios\n');
  partes.push(d.servicios.length
    ? d.servicios.map((s) => `- \`${s}\``).join('\n') + '\n'
    : '_indeterminado_\n');

  partes.push('\n### Tablas\n');
  partes.push(d.tablas === null
    ? '_indeterminado_ — todas las rutas de este módulo son legadas; las consultas viven en '
      + 'scripts procedurales y la extracción textual no es fiable ahí.\n'
    : d.tablas.length ? d.tablas.map((t) => `- \`${t}\``).join('\n') + '\n' : '_indeterminado_\n');

  partes.push('\n### Quién puede\n');
  partes.push(mod.capacidades.length
    ? tabla(['Capacidad', 'Roles que la tienen'],
        d.capacidades.map((c) => [`\`${c.cap}\``, c.roles.length ? c.roles.join(', ') : '—']))
    : '_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._\n');

  return partes.join('');
}

function paginaNueva(mod, flujoTexto) {
  return `---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [${mod.areas.join(', ')}]
fuente: public/index.php
resumen: "Módulo ${mod.titulo}: rutas, controladores, servicios y quién puede usarlo"
---
# ${mod.titulo}

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** ${flujoTexto}

${mod.nota ? `**Nota del manifiesto.** ${mod.nota}\n\n` : ''}## Inventario

Lo de abajo lo genera \`scripts/wiki-arquitectura.mjs\` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

${INICIO}
${FIN}
`;
}

const TEXTO_FLUJO = {
  lps: 'En el flujo LPS. Ver [[flujo-lps]].',
  pdc: 'En el flujo del Plan de Compras. Ver [[flujo-pdc]].',
  ambos: 'En los dos flujos. Ver [[flujo-lps]] y [[flujo-pdc]].',
  null: 'Fuera de los dos flujos de negocio: es infraestructura de la aplicación.',
};

function escribir() {
  const dir = join(RAIZ, 'memoria/arquitectura');
  mkdirSync(dir, { recursive: true });
  const rutas = leerRutas();
  const rbac = leerRbac();
  let creadas = 0, actualizadas = 0;

  for (const mod of MODULOS) {
    const archivo = join(dir, `${mod.slug}.md`);
    if (!existsSync(archivo)) {
      writeFileSync(archivo, paginaNueva(mod, TEXTO_FLUJO[mod.flujo ?? 'null']));
      creadas++;
    }
    const texto = readFileSync(archivo, 'utf8');
    const i = texto.indexOf(INICIO), f = texto.indexOf(FIN);
    if (i === -1 || f === -1 || f < i) {
      console.error(`SIN MARCADORES ${mod.slug}.md — no se toca. Añádelos a mano.`);
      process.exitCode = 1;
      continue;
    }
    const nuevo = texto.slice(0, i + INICIO.length) + '\n'
      + componer(mod, datosDe(mod, rutas, rbac)) + texto.slice(f);
    if (nuevo !== texto) { writeFileSync(archivo, nuevo); actualizadas++; }
  }
  console.log(`${creadas} páginas creadas, ${actualizadas} zonas generadas actualizadas.`);
}
```

Y en el despacho: `else if (process.argv.includes('--escribir')) escribir();`

- [ ] **Step 2: Generar por primera vez**

Run: `node scripts/wiki-arquitectura.mjs --escribir`

Expected: `23 páginas creadas, 23 zonas generadas actualizadas.`

```bash
ls memoria/arquitectura/ | wc -l
```

Expected: 23.

- [ ] **Step 3: Probar la idempotencia — el chequeo que impide el churn**

```bash
git add -A memoria/arquitectura/
node scripts/wiki-arquitectura.mjs --escribir
node scripts/wiki-arquitectura.mjs --escribir
git diff --stat -- memoria/arquitectura/
```

Expected: la segunda y la tercera pasada dicen `0 páginas creadas, 0 zonas generadas
actualizadas.` y `git diff --stat` **no imprime nada**.

Si hay diferencias, algo en la composición no es determinista: revisa que todas las listas se
ordenen (`.sort()`) y que no se cuele ninguna fecha ni ruta absoluta en la zona generada.

- [ ] **Step 4: Probar que los marcadores protegen la prosa**

```bash
printf '\n**Trampa de prueba.** Esta línea debe sobrevivir.\n' >> memoria/arquitectura/programa-general.md
node scripts/wiki-arquitectura.mjs --escribir
grep -c "Esta línea debe sobrevivir" memoria/arquitectura/programa-general.md
```

Expected: `1`.

Y la prueba complementaria, que la prosa **anterior** a los marcadores también sobrevive:

```bash
grep -n "Qué resuelve" memoria/arquitectura/programa-general.md
```

Expected: sigue ahí.

Limpiar la línea de prueba antes de seguir:

```bash
sed -i '' '/Esta línea debe sobrevivir/d' memoria/arquitectura/programa-general.md
sed -i '' -e :a -e '/^\n*$/{$d;N;};/\n$/ba' memoria/arquitectura/programa-general.md
```

- [ ] **Step 5: Contrastar la cobertura de rutas en las páginas escritas**

```bash
grep -h "^| \(GET\|POST\|PUT\|DELETE\|ANY\) " memoria/arquitectura/*.md | wc -l
grep -cE "\$router->(get|post|put|delete|any)\(" public/index.php
```

Expected: las dos cifras iguales (222). Esta es la comprobación de «el número de rutas
documentadas cuadra con las declaradas» del criterio de hecho, medida sobre el archivo final y no
sobre la salida del script.

- [ ] **Step 6: Commit**

```bash
git add scripts/wiki-arquitectura.mjs memoria/arquitectura/
git commit -m "feat(wiki): 23 páginas de módulo con zona generada desde el código

Cada página tiene dos zonas. Dentro de los marcadores manda el script:
rutas con verbo y destino, controladores, scripts legados, servicios,
tablas y la tabla de qué rol tiene cada capacidad. Fuera manda la persona.

Regenerar dos veces seguidas no produce diferencias en git, y la prosa
escrita fuera de los marcadores sobrevive a la regeneración: las dos cosas
están comprobadas.

Las 222 rutas de public/index.php aparecen repartidas y ninguna queda sin
módulo.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: La prosa de cada módulo, con el rescate de `docs/ROUTES.md`

**Files:**
- Modify: `memoria/arquitectura/<slug>.md` × 23 (solo fuera de los marcadores)

**Interfaces:**
- Consumes: las páginas creadas en la Task 3 y el contenido de `docs/ROUTES.md`.
- Produces: cada página con «Qué resuelve» escrito y, donde corresponda, el criterio rescatado de `ROUTES.md`. El `resumen` del frontmatter queda ajustado a lo que diga el cuerpo.

- [ ] **Step 1: Leer `docs/ROUTES.md` entero y separar lo derivable de lo que no**

Run: `cat docs/ROUTES.md`

Sus 146 líneas tienen tres clases de contenido:

1. **Inventario de rutas y controladores** (sección 1, tablas A–H) — derivable del código y ya
   generado. No hay que rescatar nada.
2. **Matriz de navegación cruzada** (sección 2) — **no derivable**: dice a qué conduce cada ítem
   del sidebar, qué flyouts abre, y el sub-router por hash del PDC. Esto es lo que hay que
   rescatar como prosa.
3. **Reglas de oro para asistentes** (las tres del principio: sesión por `DevDoor` con `p=`,
   comprobar redirecciones en Playwright, viewport 1180×820 dark) — **ya están en `AGENTS.md`**
   y en `memoria/trampas/dev-door-acceso-local.md`. Compruébalo antes de darlo por cubierto:

```bash
grep -n "dev/entrar" AGENTS.md CLAUDE.md memoria/trampas/dev-door-acceso-local.md | head
grep -n "1180" AGENTS.md
```

Expected: la puerta de servicio y el viewport canónico aparecen en `AGENTS.md`. Si alguna de las
tres reglas **no** estuviera cubierta, rescátala como prosa en
`memoria/arquitectura/autenticacion.md` antes de continuar.

- [ ] **Step 2: Rescatar la matriz de navegación**

La sección 2 se reparte así, en la prosa (fuera de los marcadores) de cada página:

| De `ROUTES.md` | Va a |
|---|---|
| 2.A — el sidebar canónico completo, con los tres grupos y el menú de usuario | `memoria/arquitectura/nucleo-y-runtime.md`, en una sección `## Navegación desde el shell` |
| 2.B.1 — las tres píldoras CNP/CNC/CIC | `memoria/arquitectura/programacion-semanal.md` |
| 2.B.2 — el sub-router por hash del PDC (`#/ensamble/importar`, `#/ensamble/maestro`, `#/ensamble/paquetes`, `#/plan/fechas`, `#/seguimiento`, `#/torre-control`) | `memoria/arquitectura/plan-de-compras.md` |
| 2.B.3 — la barra de navegación BI (`views/bi/_nav.php`) con sus ocho destinos | `memoria/arquitectura/torre-de-control-bi.md` |
| 2.B.4 — el menú del panel admin, incluidas `/admin/pdc-maintenance` y `/admin/config`, que no aparecen en ninguna otra parte de la wiki | `memoria/arquitectura/panel-admin.md` |

Además, dos detalles de 2.A que son criterio y no catálogo, y que se pierden si solo se copia la
lista: el flyout de semanas de PG/PI/PS **cambia la semana activa y redirige al propio módulo**, y
`Semanas del Proyecto` abre un flyout con `+ Nueva semana` y `Eliminar semana` — que es lo que
justifica las rutas legadas `nueva_semana.php` y `eliminar_semana.php`. Ese vínculo va en
`memoria/arquitectura/legado.md`.

Escribe cada bloque **con tus palabras y en presente**, no como copia literal: es prosa de wiki,
no un volcado. Pero **no pierdas ningún destino**: la comprobación de la Task 7 los cuenta.

- [ ] **Step 3: Escribir «Qué resuelve» en las 23 páginas**

Dos o tres frases por módulo, en simple, respondiendo: qué problema del usuario resuelve, y qué
hay que saber antes de tocarlo. Fuentes para no inventar:

```bash
sed -n '1,120p' docs/VISTAS-MODULOS.md
sed -n '1,80p' docs/pdc-v2.md
cat GLOSARIO.md
cat memoria/mapas/lps-dominio.md memoria/mapas/pdc.md memoria/mapas/arquitectura.md
```

Enlaza desde cada página lo que ya existe en la wiki en vez de repetirlo. Enlaces obligatorios,
que además tejen el grafo:

- Todas las de programación → `[[lps-dominio]]`.
- `plan-de-compras` y `listado-de-actividades` → `[[pdc]]` y `[[pdc-v2|docs/pdc-v2.md]]`.
- `panel-admin` → `[[admin-adminlte-adaptador]]`.
- `laboratorio-design-system` → `[[design-system]]` y `[[lab-desktop-layout-suite]]`.
- Todas → `[[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]]` cuando el módulo tenga vista propia.
- `autenticacion` → `[[dev-door-acceso-local]]`.
- `nucleo-y-runtime` → `[[arquitectura]]` (el mapa) y `[[navbar-css-consumidor-vivo]]`.

**Antes de escribir un enlace, comprueba que el destino existe y es único:**

```bash
find . -name 'VISTAS-MODULOS.md' -o -name 'pdc-v2.md' | grep -v node_modules
```

Un `[[pdc-v2]]` roto o ambiguo lo caza el lint en la Task 8, pero es más barato no escribirlo mal.

- [ ] **Step 4: Ajustar el `resumen` de cada página al cuerpo**

El `resumen` que puso el generador es genérico. Sustitúyelo por una línea que diga de qué va **ese**
módulo — es la columna que se ve en el catálogo del índice, y un resumen genérico multiplicado por
23 deja el catálogo inservible. Ejemplo del tono:

```yaml
resumen: "Programa General: la línea base del proyecto; edita la del pasado solo A y D"
```

- [ ] **Step 5: Verificar que la regeneración no se comió nada**

```bash
node scripts/wiki-arquitectura.mjs --escribir
git diff --stat -- memoria/arquitectura/
```

Expected: `0 páginas creadas, 0 zonas generadas actualizadas.` y **ninguna diferencia**. Si la
regeneración borrase prosa, aquí saldría: el diff sería contra lo que acabas de escribir.

- [ ] **Step 6: Commit**

```bash
git add memoria/arquitectura/
git commit -m "docs(wiki): prosa de los 23 módulos y rescate de ROUTES.md

Cada página explica qué resuelve el módulo y enlaza a la fuente que manda
en vez de repetirla.

La matriz de navegación cruzada de docs/ROUTES.md —lo único suyo que no es
derivable del código— queda rescatada: el sidebar canónico en núcleo, las
píldoras CNP/CNC/CIC en semanal, el sub-router por hash en plan de compras,
la barra BI en la torre de control y el menú del panel en admin.

Escrita fuera de los marcadores, así que sobrevive a cada regeneración;
comprobado regenerando después de escribirla.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: Las dos páginas de flujo

**Files:**
- Create: `memoria/flujos/flujo-lps.md`
- Create: `memoria/flujos/flujo-pdc.md`
- Modify: `memoria/arquitectura/<slug>.md` de los módulos que participan en un flujo

**Interfaces:**
- Consumes: los 23 módulos y sus slugs.
- Produces: dos páginas `tipo: flujo` enlazadas en los dos sentidos con los módulos.

- [ ] **Step 1: Crear `memoria/flujos/flujo-lps.md`**

```markdown
---
tipo: flujo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: GLOSARIO.md
resumen: "El ciclo LPS de punta a punta: del programa general a los indicadores, y qué módulo cubre cada paso"
---
# Flujo LPS

El ciclo Last Planner tal como lo implementa esta aplicación. Cada paso enlaza al módulo que lo
cubre; el vocabulario lo fija [[GLOSARIO]] y el dominio, [[lps-dominio]].

1. **[[programa-general]]** — la línea base del proyecto: qué actividades hay y cuándo deberían
   pasar. Todo lo demás se mide contra esto.
2. **[[cronograma]]** — la actualización del programa general cuando la obra se mueve.
3. **[[programacion-intermedia]]** — ventana de medio plazo: se bajan las actividades del programa
   general y se les levantan restricciones antes de que lleguen a la semana.
4. **[[programacion-semanal]]** — el compromiso de la semana: qué se va a hacer de verdad.
5. Los tres submódulos que cierran el ciclo de aprendizaje:
   - **[[submodulo-cnp]]** — lo que no llegó a programarse, y por qué.
   - **[[submodulo-cnc]]** — lo que se programó y no se cumplió, y por qué.
   - **[[submodulo-cic]]** — el cumplimiento medido de lo comprometido.
6. **[[indicadores]]** — el PPC y las demás medidas salen de ahí, y **[[torre-de-control-bi]]** las
   presenta a nivel de portafolio.

En paralelo, **[[escalamientos-y-crisis]]** recoge lo que se atasca, y **[[profesionales]]**,
**[[subcontratistas]]** y **[[control-de-cambios]]** aportan quién responde de cada compromiso.

El flujo de compras corre al lado: ver [[flujo-pdc]].
```

- [ ] **Step 2: Crear `memoria/flujos/flujo-pdc.md`**

```markdown
---
tipo: flujo
estado: vigente
fecha: 2026-08-03
areas: [pdc, arquitectura]
fuente: docs/pdc-v2.md
resumen: "El flujo del Plan de Compras v2: del presupuesto al seguimiento, y qué módulo cubre cada paso"
---
# Flujo del Plan de Compras

El recorrido del PDC v2 según [[pdc-v2|docs/pdc-v2.md]], que es la fuente que manda. El mapa del
área es [[pdc]].

1. **Presupuesto** — se carga y se versiona. Activar una versión es la decisión que fija contra qué
   se compra.
2. **Maestro de insumos** — de las partidas del presupuesto salen los insumos, que se depuran,
   vinculan y clasifican.
3. **Paquetes de contratación** — los insumos se agrupan en paquetes; parte se asigna sola y parte
   la decide una persona.
4. **Plan con fechas** — cada paquete recibe los pasos del proceso de contratación con sus
   duraciones, amarrados a las actividades de obra.
5. **Seguimiento** — vencimientos, desfases y flujo de caja del plan ya en operación.

Todo eso vive en **[[plan-de-compras]]** (SPA React, sub-router por hash).
**[[listado-de-actividades]]** es la superficie anterior, PDC v1, y **[[contratos]]** es el motor
semiautomático que propone qué contratar. **[[subcontratistas]]** aporta a quién se contrata y
**[[torre-de-control-bi]]** lo mira desde arriba.

El flujo de programación corre al lado: ver [[flujo-lps]].
```

- [ ] **Step 3: Comprobar que los slugs enlazados existen**

```bash
for n in programa-general cronograma programacion-intermedia programacion-semanal \
  submodulo-cnp submodulo-cnc submodulo-cic indicadores torre-de-control-bi \
  escalamientos-y-crisis profesionales subcontratistas control-de-cambios \
  plan-de-compras listado-de-actividades contratos; do
  test -f "memoria/arquitectura/$n.md" || echo "FALTA $n"
done
echo "comprobación terminada"
```

Expected: ninguna línea `FALTA`.

- [ ] **Step 4: Cerrar el enlace en el otro sentido**

El generador ya puso en cada página `**Dónde encaja.**` con el enlace al flujo que le toca según
`flujo` en el manifiesto. Confírmalo:

```bash
grep -L "flujo-lps\|flujo-pdc\|infraestructura de la aplicación" memoria/arquitectura/*.md
```

Expected: ninguna salida. Si alguna página aparece, el `flujo` de su entrada del manifiesto no se
aplicó: corrígelo en `scripts/wiki-arquitectura.modulos.mjs` y vuelve a generar.

- [ ] **Step 5: Commit**

```bash
git add memoria/flujos/ memoria/arquitectura/
git commit -m "docs(wiki): dos páginas de flujo, LPS y plan de compras

Los módulos por separado dicen qué hace cada uno; estas dos dicen en qué
orden pasan las cosas y por qué un módulo depende del anterior. Cada flujo
enlaza a sus módulos y cada módulo a su flujo, así que el grafo queda tejido
por dependencia real y no por catálogo.

Se llaman flujo-lps y flujo-pdc, no lps y pdc: memoria/mapas/pdc.md ya
existe y dos archivos con el mismo basename dejarían los wikilinks ambiguos
para el verificador.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: Índice, catálogo y verificador

**Files:**
- Modify: `scripts/wiki-lint.mjs`
- Modify: `memoria/paginas.base`
- Modify: `memoria/index.md`

**Interfaces:**
- Consumes: las 25 páginas nuevas y sus `tipo: modulo` / `tipo: flujo`.
- Produces: el verificador acepta los dos tipos nuevos y el catálogo los lista.

- [ ] **Step 1: Ver el estado actual del verificador**

Run: `node scripts/wiki-lint.mjs`

Expected: **falla**, con 25 líneas `FRONTMATTER … tipo desconocido: modulo` / `: flujo`. Si no
falla, el frontmatter de las páginas nuevas está mal y hay que arreglarlo antes de tocar el
verificador.

- [ ] **Step 2: Añadir los dos tipos**

En `scripts/wiki-lint.mjs`, sustituir la línea de `TIPOS` por:

```javascript
const TIPOS = new Set(['decision', 'trampa', 'mapa', 'goal', 'concepto', 'referencia', 'log',
  'modulo', 'flujo']);
```

- [ ] **Step 3: Añadir las dos vistas al catálogo**

En `memoria/paginas.base`, añadir al final de `views:`, respetando la indentación exacta de las
vistas que ya hay (dos espacios para el guion, cuatro para las claves):

```yaml
  - type: table
    name: Módulos
    filters:
      and:
        - note.tipo == "modulo"
    order:
      - file.name
      - resumen
      - areas
  - type: table
    name: Flujos
    filters:
      and:
        - note.tipo == "flujo"
    order:
      - file.name
      - resumen
```

**Por qué esto y no enlazar 25 líneas en `index.md`:** el verificador da por indexada una página
si su `tipo` aparece en algún filtro `note.tipo == "…"` de la base (`scripts/wiki-lint.mjs:57-63`).
Con las vistas, añadir un módulo nuevo no obliga a tocar el índice; con enlaces manuales, sí — y
esa es exactamente la clase de mantenimiento que este plan viene a quitar.

- [ ] **Step 4: Añadir la sección de arquitectura al índice**

En `memoria/index.md`, entre «Mapas por área» y «Catálogo», insertar:

```markdown
## Arquitectura por módulo

Una página por módulo real de la aplicación en `memoria/arquitectura/`, y dos de flujo en
`memoria/flujos/`: [[flujo-lps]] y [[flujo-pdc]].

Cada página de módulo tiene dos zonas. Entre `<!-- generado:inicio -->` y `<!-- generado:fin -->`
manda `scripts/wiki-arquitectura.mjs`, que extrae del código las rutas con su verbo y destino, los
controladores, los servicios, las tablas y qué rol tiene cada capacidad. **Fuera de los marcadores
manda la persona**, y regenerar no lo toca. Cuando cambien rutas, controladores o permisos:

```bash
node scripts/wiki-arquitectura.mjs --cobertura   # ninguna ruta sin módulo
node scripts/wiki-arquitectura.mjs --escribir    # actualiza las zonas generadas
```

Si aparece un módulo nuevo, se declara en `scripts/wiki-arquitectura.modulos.mjs`; si una ruta
nueva no casa con ningún módulo, `--cobertura` falla en vez de dejarla fuera del mapa en silencio.

El inventario de rutas y la matriz de navegación vivían antes en `docs/ROUTES.md`, que no viajaba
en git. Se retiró el 2026-08-03: ahora están aquí y versionados.
```

Y en la lista de tipos del catálogo, si la hubiera, mencionar los dos nuevos.

- [ ] **Step 5: Verificar**

Run: `node scripts/wiki-lint.mjs`

Expected: `Sin hallazgos. 73 páginas revisadas.` y código 0 (48 de antes + 23 módulos + 2 flujos).

Si aparecen líneas `ENLACE roto` o `ENLACE ambiguo`, arréglalas ahora: casi siempre es un
`[[destino]]` que no existe o un basename duplicado. Si aparece `INDICE`, la base no está cubriendo
el tipo: revisa que la sintaxis del filtro sea idéntica a la de las vistas que ya funcionaban.

Comprobar además en Obsidian (vault = raíz del repo) que las dos vistas nuevas listan 23 y 2
páginas. Si Bases no renderizara, revierte solo `paginas.base` y enlaza las 25 páginas a mano en
`index.md`: el verificador acepta las dos formas, pero **no puede quedarse sin ninguna**.

- [ ] **Step 6: Commit**

```bash
git add scripts/wiki-lint.mjs memoria/paginas.base memoria/index.md
git commit -m "feat(wiki): indexa los módulos y flujos en el catálogo

El verificador acepta los tipos modulo y flujo, y paginas.base gana dos
vistas para listarlos. Se eligen vistas en vez de 25 enlaces manuales en
index.md porque así añadir un módulo no obliga a tocar el índice.

El índice explica de paso cómo se regenera lo generado y qué pasa si una
ruta nueva no casa con ningún módulo.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 7: Retirar `docs/ROUTES.md`

**Files:**
- Delete: `docs/ROUTES.md`
- Modify: `.gitignore`
- Modify: `AGENTS.md`
- Modify: `CLAUDE.md`

**Interfaces:**
- Consumes: las páginas de la Task 4, que ya deben contener lo rescatado.
- Produces: nada. Es la única tarea que borra algo, y va después de todo lo demás a propósito.

- [ ] **Step 1: Gate — comprobar que no se pierde nada, ANTES de borrar**

Este paso es un gate: si falla, **no se borra**.

```bash
# 1. Toda ruta que ROUTES.md citaba debe aparecer en alguna página de arquitectura.
grep -oE '`/[a-z0-9/_.{}-]+`' docs/ROUTES.md | tr -d '`' | sort -u > /tmp/rutas-routes.txt
wc -l < /tmp/rutas-routes.txt
while read -r r; do
  grep -qF "\`$r\`" memoria/arquitectura/*.md memoria/flujos/*.md || echo "NO CUBIERTA: $r"
done < /tmp/rutas-routes.txt
echo "--- fin del chequeo de rutas ---"

# 2. Todo destino de la matriz de navegación (incluidos los que no son rutas del router,
#    como /admin/* y los hashes del PDC) debe estar en la wiki.
for d in /admin/users /admin/projects /admin/families /admin/pdc-maintenance /admin/config \
  /admin/logout /admin/login '#/ensamble/importar' '#/ensamble/maestro' '#/ensamble/paquetes' \
  '#/plan/fechas' '#/seguimiento' '#/torre-control'; do
  grep -qrF "$d" memoria/arquitectura/ memoria/flujos/ || echo "NO RESCATADO: $d"
done
echo "--- fin del chequeo de navegación ---"

# 3. Las tres reglas de oro deben estar cubiertas por AGENTS.md o la wiki.
grep -qn "dev/entrar" AGENTS.md && echo "ok: puerta de servicio"
grep -qn "1180" AGENTS.md && echo "ok: viewport canónico"
grep -qrn "proyectos\|/login" memoria/arquitectura/autenticacion.md && echo "ok: redirecciones"
```

Expected: ninguna línea `NO CUBIERTA` ni `NO RESCATADO`, y los tres `ok:`.

Sobre `NO CUBIERTA`: hay dos casos legítimos de ruta citada en `ROUTES.md` que no está en
`public/index.php` — las de `/admin/*`, que las sirve `admin/index.php`. Esas las cubre el chequeo
2, no el 1. Cualquier otra `NO CUBIERTA` es una pérdida real: **rescátala en la página del módulo
que corresponda y vuelve a correr el gate**.

Si algo no se puede rescatar, **para aquí**, deja `docs/ROUTES.md` en su sitio, anótalo en el log y
salta a la Task 8. El spec es explícito: no se borra nada que no esté cubierto.

- [ ] **Step 2: Borrar el archivo y desactivar la línea del `.gitignore`**

`docs/ROUTES.md` **no está en git** (`.gitignore:193`), así que `git rm` no sirve. Guarda una copia
fuera del repositorio por si el gate se te escapó algo, y luego borra:

```bash
cp docs/ROUTES.md /tmp/ROUTES.md.retirado
grep -n "ROUTES" .gitignore
rm docs/ROUTES.md
```

Editar `.gitignore`: eliminar la línea `docs/ROUTES.md`. Verificar:

```bash
grep -c "ROUTES" .gitignore
```

Expected: `0`.

- [ ] **Step 3: Repuntar las referencias**

```bash
grep -rn "ROUTES.md" AGENTS.md CLAUDE.md GEMINI.md README.md docs/ memoria/ 2>/dev/null
```

Cada aparición se sustituye. En `AGENTS.md`, la sección «Routing por tipo de cambio» dice
«Consulta el inventario canónico y matriz de navegación completa en `docs/ROUTES.md`». Pasa a:

```markdown
Consulta el inventario de rutas y la matriz de navegación en `memoria/arquitectura/` (una página
por módulo, con las rutas generadas desde `public/index.php`) y en `memoria/flujos/`. Se regeneran
con `node scripts/wiki-arquitectura.mjs --escribir`. `docs/ROUTES.md` se retiró el 2026-08-03: no
viajaba en git y duplicaba un dato que ahora se genera.
```

En `CLAUDE.md`, la línea de «Reference docs» que describe `docs/ROUTES.md` pasa a:

```markdown
- `memoria/arquitectura/` y `memoria/flujos/` — inventario de rutas por módulo, matriz de
  navegación y los dos flujos de negocio. Generado desde el código con
  `scripts/wiki-arquitectura.mjs`; sustituye al retirado `docs/ROUTES.md`.
```

Si aparece en `GEMINI.md` o `README.md`, aplícales el mismo cambio: `AGENTS.md` manda, pero los
tres deben decir lo mismo.

- [ ] **Step 4: Verificar que no quedan referencias colgando**

```bash
grep -rn "ROUTES.md" --exclude-dir=node_modules --exclude-dir=.git . | grep -v "docs/superpowers/"
```

Expected: ninguna salida. Las menciones dentro de `docs/superpowers/specs/` y `plans/` son
histórico y se dejan como están.

- [ ] **Step 5: Commit**

```bash
git add AGENTS.md CLAUDE.md .gitignore
git add GEMINI.md README.md 2>/dev/null || true
git commit -m "docs: retira docs/ROUTES.md; el inventario vive en la wiki

ROUTES.md era el inventario canónico de rutas y la matriz de navegación,
pero estaba en .gitignore: desaparecía en cualquier clon fresco. Es el mismo
patrón que dejó cuatro goals fuera del repositorio hasta el 2026-08-02.

Su inventario ahora se genera desde public/index.php en
memoria/arquitectura/, y su matriz de navegación —lo único suyo que no era
derivable— quedó rescatada como prosa en las páginas de módulo. Antes de
borrarlo se comprobó ruta por ruta y destino por destino que nada suyo se
perdía.

AGENTS.md y CLAUDE.md apuntan ya a la wiki.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 8: Verificación completa y cierre

**Files:**
- Modify: `memoria/log.md`

**Interfaces:**
- Consumes: el resultado de las siete tareas anteriores.
- Produces: nada.

- [ ] **Step 1: Fidelidad del RBAC — leyendo el código**

Contrastar la tabla «Quién puede» de **tres** módulos contra `src/Security/RbacManager.php`:

```bash
sed -n '1,60p' src/Security/RbacManager.php
sed -n '/### Quién puede/,/^<!-- generado:fin/p' memoria/arquitectura/programa-general.md
sed -n '/### Quién puede/,/^<!-- generado:fin/p' memoria/arquitectura/programacion-semanal.md
sed -n '/### Quién puede/,/^<!-- generado:fin/p' memoria/arquitectura/contratos.md
```

Expected, según las listas `in_array` del propio archivo:
- `canEditGeneralProgram` → `A, D, R, DCV`
- `canEditWeeklyProgram` → `A, D, R, S, G, SG`
- `canManageContracts` y `canAutoDefineContracts` → `A, D, OT, R`

Si una tabla no coincide, el fallo está en el volcador o en el compositor, **no** en el código de
la aplicación: no toques `RbacManager`.

- [ ] **Step 2: Fidelidad del RBAC — en el navegador, un rol permitido y uno denegado**

Lo exige `AGENTS.md` para cualquier trabajo que afirme algo sobre rutas y capacidades. Con el stack
levantado, por la puerta de servicio y **nunca** por `/login`:

```bash
docker compose ps --status running --services
```

Con el navegador integrado (`mcp__Claude_Browser__preview_start` apuntando a `http://localhost:8081`),
en viewport 1180×820 y dark mode:

1. `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E` y navegar a
   `/programa-general`. Expected: 200, la URL no redirige a `/proyectos` ni a `/login`, y la
   superficie permite editar — coherente con `canEditGeneralProgram` incluyendo a `R`.
2. `http://localhost:8081/dev/entrar?u=test.V&p=PDC%20Sandbox%20E2E` y la misma ruta. Expected:
   carga en solo lectura — coherente con `isReadOnly` para `V` y con que `V` no aparece en
   `canEditGeneralProgram`.

Revisa la consola del navegador en las dos. Si la puerta redirige a `/login`, está cerrada:
comprueba `DEV_DOOR=1` y `DEV_DOOR_USERS` en `.env` (editar `APP_ENV` **no** la cierra bajo
Docker). Anota en el log qué se comprobó y con qué proyecto.

- [ ] **Step 3: Cobertura, idempotencia y lint, todo junto**

```bash
node scripts/wiki-arquitectura.mjs --cobertura
node scripts/wiki-arquitectura.mjs --escribir
node scripts/wiki-arquitectura.mjs --escribir
git status --porcelain -- memoria/
node scripts/wiki-lint.mjs
```

Expected: `222 TOTAL` y cobertura completa · las dos escrituras con `0 páginas creadas, 0 zonas
generadas actualizadas.` · `git status` sin salida · `Sin hallazgos. 73 páginas revisadas.` con
código 0.

- [ ] **Step 4: Clon fresco**

```bash
T=$(mktemp -d) && git clone -q --local --no-hardlinks . "$T/c" \
  && (cd "$T/c" && node scripts/wiki-lint.mjs && ls memoria/arquitectura | wc -l) ; rm -rf "$T"
```

Expected: `Sin hallazgos. 73 páginas revisadas.` y `23`.

Este es el chequeo que `docs/ROUTES.md` nunca habría pasado, y la razón de todo el plan. Si aquí
falla y en local no, hay un enlace apuntando a algo sin versionar.

- [ ] **Step 5: Anotar en la bitácora**

Añadir al final de `memoria/log.md`:

```markdown
- 2026-08-03 · ingest · Arquitectura por módulo generada desde el código: 23 páginas en `memoria/arquitectura/` con zona generada entre marcadores (222 rutas repartidas, cero huérfanas), dos páginas de flujo, y `docs/ROUTES.md` retirado tras comprobar ruta por ruta que su matriz de navegación quedaba rescatada como prosa · [[index]], [[flujo-lps]], [[flujo-pdc]]
```

Si la Task 7 se detuvo en su gate y `docs/ROUTES.md` sigue en su sitio, **dilo en la línea** en vez
de omitirlo, y añade qué faltaba por rescatar.

- [ ] **Step 6: Commit**

```bash
git add memoria/log.md
git commit -m "docs(wiki): anota la ingesta de arquitectura en la bitácora

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Autorrevisión del plan

**Cobertura del spec:** las dos zonas con marcadores → Task 3 · el generador y su tabla de qué
extrae de dónde → Tasks 1 y 2 · las ~20 páginas de módulo → Tasks 1 (manifiesto, 23 módulos) y 3 ·
la tabla «Quién» y «Dónde encaja» → Task 3 (compositor) y Task 5 (flujos) · las dos páginas de
flujo → Task 5 · el retiro de `ROUTES.md` con su gate → Task 7 · verificación de cobertura,
fidelidad RBAC leyendo código y en navegador, marcadores que protegen, nada perdido, enlaces en
clon fresco e idempotencia → Tasks 3, 7 y 8. Los cuatro puntos de «fuera de alcance» se respetan:
no se documenta clase por clase, no se toca `docs/VISTAS-MODULOS.md` (solo se enlaza), no hay hook
ni CI, y `admin/` tiene una sola página que lo explica como mini-app aislada.

**Placeholders:** ninguno en el mecanismo. Lo único que queda por redactar con criterio es la prosa
de la Task 4 y los `resumen`, que por definición dependen de leer las fuentes — y por eso ese paso
lleva los comandos exactos para leerlas y la lista de enlaces obligatorios, en vez de un «escribe
una descripción».

**Consistencia de tipos:** `leerRutas()` devuelve `{verbo, path, destino, tipo}` y así se consume
en `asignar()`, `datosDe()` y `componer()`. `asignar()` devuelve `{mod, prefijo}` y siempre se lee
con `?.mod.slug`. `datosDe()` devuelve `{rutas, controladores, legados, servicios, tablas,
capacidades}` y `componer()` lee exactamente esas seis claves; `tablas` es `null` —no `[]`— cuando
es indeterminado, y `componer()` distingue los dos casos. Los 23 slugs del manifiesto son los
mismos que usan los wikilinks de la Task 5 y el chequeo de su Step 3.

**Riesgo asumido, dicho en claro:** la extracción de servicios y tablas es fiable en `src/` y no lo
es en el carril legado, donde las rutas hacen `require_once` de scripts procedurales. El generador
marca `_indeterminado_` en vez de adivinar, y esas casillas se rellenan a mano o se quedan vacías.
Un hueco visible envejece bien; un dato inventado, no.

**Riesgo secundario:** el volcado de RBAC exige el contenedor `app` levantado. Si Docker no está,
`--escribir` falla en seco en vez de generar tablas a medias. Es deliberado: una tabla de permisos
a medias es peor que ninguna.
