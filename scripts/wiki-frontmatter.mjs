#!/usr/bin/env node
// Backfill idempotente del frontmatter del esquema v2 en la capa de fuentes
// (`docs/`, `goals/`, los `.md` de la raíz). Ver docs/wiki-operacion.md.
//
//   node scripts/wiki-frontmatter.mjs                 # censo completo, no escribe nada
//   node scripts/wiki-frontmatter.mjs --dry-run       # lo mismo, explícito
//   node scripts/wiki-frontmatter.mjs --detalle       # además, el frontmatter que escribiría
//   node scripts/wiki-frontmatter.mjs --solo docs/superpowers/specs
//   node scripts/wiki-frontmatter.mjs --escribir      # aplica (por tandas, con --solo)
//
// **No escribe si no se lo piden.** El modo por defecto es el ensayo: un backfill que toca
// cientos de archivos tiene que poder mirarse entero antes de correr.
//
// Añade metadato, nunca contenido: el bloque va delante del cuerpo y el cuerpo no se toca. Si el
// archivo ya trae frontmatter (`DESIGN.md` lleva el suyo, que leen el linter Stitch y el panel
// live), se **fusiona**: solo se añaden las claves del esquema que falten, en el orden del
// esquema, y ninguna clave ajena se toca ni se reordena. Reescribir ese bloque rompería otra
// herramienta sin que nada se pusiera rojo aquí.
import { readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join, relative, extname, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { bloqueFrontmatter, deducirCapa } from './wiki-esquema.mjs';
import { ORDEN, aplicar, deducirAreas, deducirEstado, deducirResumen, deducirTags, deducirTipo,
  faltantes, fechaDelNombre, render } from './wiki-frontmatter.reglas.mjs';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');
const argv = process.argv.slice(2);
const ESCRIBIR = argv.includes('--escribir');
const DETALLE = argv.includes('--detalle');
const SOLO = argv.includes('--solo') ? argv[argv.indexOf('--solo') + 1] : null;

if (argv.includes('--solo') && !SOLO) {
  console.error('wiki-frontmatter: --solo necesita un prefijo de ruta.');
  process.exit(2);
}

// ── Qué archivos entran ──────────────────────────────────────────────────────────────────────
const filtros = JSON.parse(readFileSync(join(RAIZ, '.obsidian/app.json'), 'utf8')).userIgnoreFilters;
const ignorado = (rel) => filtros.some((f) => rel === f.replace(/\/$/, '') || rel.startsWith(f))
  || rel.startsWith('.git/');

const fuentes = [];
(function recorrer(dir) {
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    const rel = relative(RAIZ, p);
    if (ignorado(rel + (e.isDirectory() ? '/' : ''))) continue;
    if (e.isDirectory()) recorrer(p);
    else if (extname(e.name) === '.md' && deducirCapa(rel) !== 'wiki') fuentes.push(rel);
  }
})(RAIZ);
fuentes.sort();

const elegidos = SOLO ? fuentes.filter((f) => f === SOLO || f.startsWith(SOLO.replace(/\/?$/, '/'))) : fuentes;

// ── Fechas: un solo `git log` para todo el árbol, no uno por archivo ─────────────────────────
// La fecha del hecho es la del alta del archivo en git, salvo que el nombre lleve una mejor
// (`2026-08-18-loquesea.md`). Sacar 383 subprocesos costaría un minuto y daría el mismo dato.
function altasGit() {
  const alta = new Map();
  try {
    const salida = execFileSync('git', ['log', '--diff-filter=A', '--reverse', '--date=short',
      '--format=%ad', '--name-only'], { cwd: RAIZ, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 });
    let fecha = '';
    for (const linea of salida.split('\n')) {
      if (/^\d{4}-\d{2}-\d{2}$/.test(linea)) fecha = linea;
      else if (linea.trim() && !alta.has(linea)) alta.set(linea, fecha);
    }
  } catch {
    // Sin git utilizable, la fecha queda pendiente en vez de inventada.
  }
  return alta;
}
const ALTA = altasGit();

// ── Qué frontmatter le tocaría a cada archivo ────────────────────────────────────────────────
// El cómo se escribe (`ORDEN`, `faltantes`, `render`, `aplicar`) vive en el módulo de reglas,
// para que se pueda probar sin tocar el disco.

function propuesta(rel, texto) {
  const areas = deducirAreas(rel);
  const tags = deducirTags(rel, texto);
  const p = {
    capa: deducirCapa(rel),
    tipo: deducirTipo(rel),
    estado: deducirEstado(rel),
    fecha: fechaDelNombre(rel) || ALTA.get(rel) || '',
    areas: `[${areas.join(', ')}]`,
    tags: `[${tags.join(', ')}]`,
    fuente: rel,
    resumen: deducirResumen(texto),
  };
  if (!areas.length) delete p.areas;
  if (!tags.length) delete p.tags;
  return p;
}

// ── Censo ────────────────────────────────────────────────────────────────────────────────────
const porCarpeta = new Map();
const porTipo = new Map();
const cuenta = (m, k) => m.set(k, (m.get(k) ?? 0) + 1);

let alDia = 0, pendientes = 0, fusiones = 0, sinFecha = 0, sinResumen = 0, sinArea = 0;
const escritos = [];

for (const rel of elegidos) {
  const ruta = join(RAIZ, rel);
  const texto = readFileSync(ruta, 'utf8');
  const fm = bloqueFrontmatter(texto);
  const prop = propuesta(rel, texto);
  const claves = faltantes(fm, prop);

  const carpeta = rel.includes('/') ? rel.slice(0, rel.lastIndexOf('/')) : '(raíz)';
  cuenta(porCarpeta, carpeta);
  cuenta(porTipo, prop.tipo);

  if (!claves.length) { alDia++; continue; }
  pendientes++;
  if (fm !== null) fusiones++;
  if (!prop.fecha) sinFecha++;
  if (!prop.resumen) sinResumen++;
  if (!('areas' in prop)) sinArea++;

  if (DETALLE) {
    console.log(`\n── ${rel}${fm !== null ? '  (fusión: ya tiene frontmatter ajeno)' : ''}`);
    console.log(render(prop, claves).split('\n').map((l) => `   ${l}`).join('\n'));
  }
  if (ESCRIBIR) { writeFileSync(ruta, aplicar(texto, prop, claves), 'utf8'); escritos.push(rel); }
}

// ── Informe ──────────────────────────────────────────────────────────────────────────────────
const tabla = (m, titulo) => {
  console.log(`\n${titulo}`);
  for (const [k, v] of [...m].sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]))) {
    console.log(`  ${String(v).padStart(4)}  ${k}`);
  }
};

console.log(`Censo de la capa fuente${SOLO ? ` bajo '${SOLO}'` : ''}: ${elegidos.length} archivos`
  + `${SOLO ? ` de ${fuentes.length}` : ''}.`);
tabla(porCarpeta, 'Por carpeta:');
tabla(porTipo, 'Por tipo deducido:');

console.log(`\n${alDia} ya declarados · ${pendientes} pendientes`
  + `${fusiones ? ` (${fusiones} con frontmatter ajeno, se fusionaría)` : ''}.`);
if (sinFecha || sinResumen || sinArea) {
  console.log('Lo que las reglas no pueden deducir y hay que rellenar a mano:'
    + `${sinFecha ? ` ${sinFecha} sin fecha ·` : ''}`
    + `${sinResumen ? ` ${sinResumen} sin resumen ·` : ''}`
    + `${sinArea ? ` ${sinArea} sin área` : ''}`.replace(/ ·$/, ''));
}

if (ESCRIBIR) console.log(`\nEscritos ${escritos.length} archivos.`);
else console.log('\nEnsayo: no se escribió nada. Con --escribir se aplica; con --solo, por tandas.');
