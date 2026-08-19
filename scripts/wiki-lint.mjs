#!/usr/bin/env node
// Operación `lint` del vault (patrón LLM Wiki), esquema v2.
// Comprueba y reporta; nunca corrige. Ver docs/wiki-operacion.md.
//
// v2 lintea las tres capas, pero no con las mismas reglas:
//   · `wiki`    (memoria/)            — frontmatter, enlaces, un-hecho-por-nota, alcanzabilidad.
//   · `fuente`  (docs/, goals/, raíz) — SOLO el frontmatter. El cuerpo no se mira ni se toca.
//   · `esquema` (docs/wiki-operacion.md) — como fuente.
//
// Retrocompatible a propósito: `capa` y `tags` se validan si están, y una fuente entra al lint
// SOLO si su frontmatter declara `capa:`. El backfill de las fuentes es otra tanda; hasta que
// corra, exigirlo pondría en rojo doscientos archivos que nadie ha tocado todavía. Con
// `--estricto` se exige a toda fuente declararse, que es como quedará el lint cuando el backfill
// termine.
//
// La activación es por `capa:` y no por «tiene un bloque ---» por un caso real: `DESIGN.md` ya
// lleva frontmatter, pero es de otra herramienta (el linter Stitch y el panel live leen ahí sus
// tokens). Medirlo con la vara de la wiki lo ponía en rojo por cuatro campos que no le tocan.
// Un bloque de metadatos no es una declaración de pertenecer a este esquema; `capa:` sí lo es.
import { readdirSync, readFileSync, existsSync } from 'node:fs';
import { join, relative, basename, extname, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { estadoVeracidad, mensajeVeracidad } from './wiki-veracidad.mjs';
import { bloqueFrontmatter, campo, deducirCapa, lista, revisarFrontmatter } from './wiki-esquema.mjs';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');
const WIKI = join(RAIZ, 'memoria');
const ESTRICTO = process.argv.includes('--estricto');
// `--sin-alarma` calla la alarma de veracidad y deja solo la comprobación de FORMA.
//
// Existe para poder separar dos cosas que no son iguales y que estaban en el mismo semáforo: un
// enlace roto o una fuente sin declarar son **defectos de lo que vas a publicar**; la alarma de
// veracidad es un **contador de commits** que pide trabajo pero no dice que lo tuyo esté mal.
// Mezcladas, o bloqueas por un contador —y se aprende a saltarse el gate— o no bloqueas por un
// defecto real. Separadas, cada una puede tener la severidad que le toca.
const SIN_ALARMA = process.argv.includes('--sin-alarma');

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
    // `.canvas` cuenta como destino enlazable: un canvas es una página del vault como otra
    // cualquiera. Faltaba, y por eso un enlace correcto a un canvas se reportaba como roto.
    else if (['.md', '.base', '.canvas'].includes(extname(e.name))) vault.push(rel);
  }
})(RAIZ);

const porRuta = new Set(vault.map((f) => f.replace(/\.(md|base|canvas)$/, '')));
const porNombre = new Map();
for (const f of vault) {
  const corto = basename(f, extname(f));
  if (!porNombre.has(corto)) porNombre.set(corto, []);
  porNombre.get(corto).push(f);
}

const hallazgos = [];
const anota = (cat, archivo, detalle) => hallazgos.push(`${cat} ${archivo}: ${detalle}`);

// Archivos que un contrato congela por sha256: se leen, no se les exige nada. Ver
// `wiki-frontmatter.mjs`, que los excluye por la misma razón y desde el mismo manifiesto.
const CONGELADOS = (() => {
  try {
    const m = JSON.parse(readFileSync(join(RAIZ, 'docs/design-system/manifests/goal-provenance.json'), 'utf8'));
    return new Set([...(m.canonicalSources ?? []), ...(m.historicalSources ?? [])].map((s) => s.path));
  } catch { return new Set(); }
})();

const md = vault.filter((f) => extname(f) === '.md');
const paginas = md.filter((f) => deducirCapa(f) === 'wiki');
const fuentes = md.filter((f) => deducirCapa(f) !== 'wiki');
const indice = readFileSync(join(WIKI, 'index.md'), 'utf8');

// Tipos cubiertos por alguna vista de paginas.base: esas páginas no necesitan enlace desde index.md.
const tiposCubiertos = new Set();
const rutaBase = join(WIKI, 'paginas.base');
if (existsSync(rutaBase)) {
  const base = readFileSync(rutaBase, 'utf8');
  for (const m of base.matchAll(/note\.tipo\s*==\s*"([^"]+)"/g)) tiposCubiertos.add(m[1]);
}

// ── Capa wiki: frontmatter + cuerpo ──────────────────────────────────────────────────────────
for (const rel of paginas) {
  const texto = readFileSync(join(RAIZ, rel), 'utf8');
  const fm = bloqueFrontmatter(texto);

  if (fm === null) { anota('FRONTMATTER', rel, 'sin bloque de frontmatter'); continue; }

  // Una plantilla no es una página de la wiki: es el molde del que salen páginas. Se le comprueba
  // el vocabulario y nada más — ni sus huecos, ni su cuerpo, ni que esté enlazada desde el índice,
  // porque un molde no es contenido al que haya que llegar navegando. La exención se apoya en el
  // tag `plantilla`, que ya está en el vocabulario cerrado que fija la spec de v2, y no en una
  // regla ad-hoc por carpeta: mover la carpeta no debe cambiar cómo se mide.
  const molde = lista(fm, 'tags').includes('plantilla');

  for (const f of revisarFrontmatter(fm, { rel, molde, obligatorios: ['tipo', 'estado', 'fecha', 'resumen'] })) {
    anota(f.campo === 'areas' ? 'AREA' : 'FRONTMATTER', rel, f.detalle);
  }
  if (molde) continue;

  // Una nota, un hecho: más de tres hechos numerados delata una nota que debería partirse.
  const numerados = (texto.match(/^(?:\d+\.|\*\*\d+\.)\s/gm) ?? []).length;
  if (numerados > 3) anota('MULTIHECHO', rel, `${numerados} hechos numerados; parte la nota`);

  // Enlaces
  const limpio = texto.replace(/```[\s\S]*?```/g, '').replace(/`[^`\n]*`/g, '');
  // El separador de alias puede venir escapado (`\|`): dentro de una tabla de Markdown, una
  // barra sin escapar cortaría la celda, así que `[[destino\|Alias]]` es la forma CORRECTA y no
  // una errata. Sin contemplarlo, el `\` se colaba en el destino y el enlace salía roto —
  // medido el 2026-08-19 sobre los tres canvas enlazados desde `index.md`.
  for (const m of limpio.matchAll(/\[\[([^\]|#\\]+)(?:\\?[|#][^\]]*)?\]\]/g)) {
    const destino = m[1].trim().replace(/\.(md|base|canvas)$/, '');
    if (porRuta.has(destino)) continue;
    const cand = porNombre.get(basename(destino));
    if (!cand) anota('ENLACE', rel, `roto: [[${destino}]]`);
    else if (cand.length > 1) anota('ENLACE', rel, `ambiguo: [[${destino}]] → ${cand.join(', ')}`);
  }

  // Toda página debe ser alcanzable desde el índice o desde una vista de la base.
  const nombre = basename(rel, '.md');
  const enlazadaEnIndice = new RegExp(`\\[\\[${nombre.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?:\\]\\]|[|#])`).test(indice);
  if (!['index', 'log'].includes(nombre)
      && !enlazadaEnIndice
      && !tiposCubiertos.has(campo(fm, 'tipo'))) {
    anota('INDICE', rel, 'no aparece en index.md y ninguna vista de paginas.base la lista');
  }
}

// ── Capas fuente y esquema: solo el frontmatter ──────────────────────────────────────────────
let fuentesConFm = 0;
for (const rel of fuentes) {
  const fm = bloqueFrontmatter(readFileSync(join(RAIZ, rel), 'utf8'));
  if (fm === null || !campo(fm, 'capa')) {
    // Un archivo que un contrato congela por hash no puede declararse, y exigírselo en estricto
    // pondría en rojo a quien cumple el contrato. Ver CONGELADOS en `wiki-frontmatter.mjs`.
    if (ESTRICTO && !CONGELADOS.has(rel)) {
      // El mensaje lleva el remedio dentro a propósito: quien se lo encuentra suele ser alguien
      // de otro frente que acaba de crear un documento y no tiene por qué conocer este esquema.
      anota('FUENTE', rel, 'sin frontmatter del esquema v2. Se lo pone el backfill, que solo '
        + `añade metadato y no toca el cuerpo:\n    node scripts/wiki-frontmatter.mjs --solo ${rel} --detalle   # ensayo`
        + `\n    node scripts/wiki-frontmatter.mjs --solo ${rel} --escribir`
        + '\n  Si el archivo aún no está en git, la `fecha` queda vacía —no hay alta de la que '
        + 'deducirla— y se pone a mano. Es lo único que el backfill no puede saber.');
    }
    continue;
  }
  fuentesConFm++;
  // A una fuente se le exige `resumen` igual que a una página de wiki: es la columna «De qué va»
  // del catálogo, y un catálogo de 391 filas con esa columna vacía no sirve para filtrar nada.
  //
  // Se pudo exigir porque el coste dejó de ser el que parecía. Con una sola regla de deducción
  // quedaban 222 fuentes sin resumen, y eso hacía ver la Tanda 2 como 222 textos escritos a mano.
  // Medido el 2026-08-19, esos 222 eran un fallo de la deducción y no del repositorio: los planes
  // abren con una cita (`> **For agentic workers:**`) y la regla se paraba justo antes del
  // `**Goal:**` que era el resumen buscado. Con la cascada de cuatro respaldos de
  // `wiki-frontmatter.reglas.mjs` quedan 17. Ese es el coste real de esta línea.
  for (const f of revisarFrontmatter(fm, { rel, obligatorios: ['tipo', 'estado', 'fecha', 'resumen'] })) {
    anota('FUENTE', rel, f.detalle);
  }
}

// Edad del último pase de veracidad, medida en commits de código (no en días).
if (!SIN_ALARMA) {
  const veracidad = mensajeVeracidad(estadoVeracidad(readFileSync(join(WIKI, 'log.md'), 'utf8')));
  if (veracidad.hallazgo) anota('VERACIDAD', 'memoria/log.md', veracidad.hallazgo);
  if (veracidad.aviso) console.log(`${veracidad.aviso}\n`);
}

const censo = `${paginas.length} páginas de wiki y ${fuentesConFm} de ${fuentes.length} fuentes declaradas`
  + `${ESTRICTO ? ' (modo estricto)' : ''}`;

if (hallazgos.length) {
  console.log(hallazgos.join('\n'));
  console.log(`\n${hallazgos.length} hallazgos en ${censo}.`);
  process.exitCode = 1;
} else {
  console.log(`Sin hallazgos. ${censo}.`);
}
