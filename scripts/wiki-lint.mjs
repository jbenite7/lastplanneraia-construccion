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
import { bloqueFrontmatter, campo, deducirCapa, revisarFrontmatter } from './wiki-esquema.mjs';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');
const WIKI = join(RAIZ, 'memoria');
const ESTRICTO = process.argv.includes('--estricto');

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
    else if (extname(e.name) === '.md' || extname(e.name) === '.base') vault.push(rel);
  }
})(RAIZ);

const porRuta = new Set(vault.map((f) => f.replace(/\.(md|base)$/, '')));
const porNombre = new Map();
for (const f of vault) {
  const corto = basename(f, extname(f));
  if (!porNombre.has(corto)) porNombre.set(corto, []);
  porNombre.get(corto).push(f);
}

const hallazgos = [];
const anota = (cat, archivo, detalle) => hallazgos.push(`${cat} ${archivo}: ${detalle}`);

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

  for (const f of revisarFrontmatter(fm, { rel, obligatorios: ['tipo', 'estado', 'fecha', 'resumen'] })) {
    anota(f.campo === 'areas' ? 'AREA' : 'FRONTMATTER', rel, f.detalle);
  }

  // Una nota, un hecho: más de tres hechos numerados delata una nota que debería partirse.
  const numerados = (texto.match(/^(?:\d+\.|\*\*\d+\.)\s/gm) ?? []).length;
  if (numerados > 3) anota('MULTIHECHO', rel, `${numerados} hechos numerados; parte la nota`);

  // Enlaces
  const limpio = texto.replace(/```[\s\S]*?```/g, '').replace(/`[^`\n]*`/g, '');
  for (const m of limpio.matchAll(/\[\[([^\]|#]+)(?:[|#][^\]]*)?\]\]/g)) {
    const destino = m[1].trim().replace(/\.(md|base)$/, '');
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
    if (ESTRICTO) anota('FUENTE', rel, 'no declara `capa:`; el backfill no ha pasado por aquí');
    continue;
  }
  fuentesConFm++;
  // A una fuente se le exige `resumen` — la columna del catálogo — solo si ya lo trae: el censo
  // del backfill mide 217 fuentes cuyo H1 va seguido de otro encabezado, sin párrafo del que
  // deducirlo. Exigirlo de entrada convertiría la Tanda 2 en 217 resúmenes escritos a mano, y un
  // resumen escrito por compromiso miente igual que uno vacío. Lo obligatorio de una fuente es lo
  // que la hace filtrable: `tipo`, `estado` y `fecha`.
  for (const f of revisarFrontmatter(fm, { rel, obligatorios: ['tipo', 'estado', 'fecha'] })) {
    anota('FUENTE', rel, f.detalle);
  }
}

// Edad del último pase de veracidad, medida en commits de código (no en días).
const veracidad = mensajeVeracidad(estadoVeracidad(readFileSync(join(WIKI, 'log.md'), 'utf8')));
if (veracidad.hallazgo) anota('VERACIDAD', 'memoria/log.md', veracidad.hallazgo);
if (veracidad.aviso) console.log(`${veracidad.aviso}\n`);

const censo = `${paginas.length} páginas de wiki y ${fuentesConFm} de ${fuentes.length} fuentes declaradas`
  + `${ESTRICTO ? ' (modo estricto)' : ''}`;

if (hallazgos.length) {
  console.log(hallazgos.join('\n'));
  console.log(`\n${hallazgos.length} hallazgos en ${censo}.`);
  process.exitCode = 1;
} else {
  console.log(`Sin hallazgos. ${censo}.`);
}
