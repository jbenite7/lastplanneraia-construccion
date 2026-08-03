#!/usr/bin/env node
// Operación `lint` de la wiki `memoria/` (patrón LLM Wiki).
// Comprueba y reporta; nunca corrige. Ver memoria/index.md.
import { readdirSync, readFileSync, existsSync } from 'node:fs';
import { join, relative, basename, extname, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');
const WIKI = join(RAIZ, 'memoria');

const AREAS = new Set(['design-system', 'qa', 'docker', 'worktrees', 'pdc',
  'lps', 'datos', 'rbac', 'deploy', 'bi', 'admin', 'proceso', 'arquitectura']);
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
    else if (extname(e.name) === '.md' || extname(e.name) === '.base') vault.push(rel);
  }
})(RAIZ);

const porRuta = new Set(vault.map((f) => f.replace(/\.(md|base)$/, '')));
const porNombre = new Map();
for (const f of vault) {
  const ext = extname(f);
  const corto = basename(f, ext);
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

  let areas = [];
  const areasInline = fm.match(/^areas:\s*\[(.*)\]$/m)?.[1];
  if (areasInline !== undefined) {
    areas = areasInline.split(',').map((s) => s.trim()).filter(Boolean);
  } else {
    const areasBloque = fm.match(/^areas:\s*\n((?:^\s*-\s*.+\n?)+)/m)?.[1];
    if (areasBloque) {
      areas = [...areasBloque.matchAll(/^\s*-\s*(.+)$/gm)].map((m) => m[1].trim()).filter(Boolean);
    }
  }
  for (const a of areas) if (!AREAS.has(a)) anota('AREA', rel, `fuera de la lista cerrada: ${a}`);

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
  const nombre = basename(p, '.md');
  const enlazadaEnIndice = new RegExp(`\\[\\[${nombre.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?:\\]\\]|[|#])`).test(indice);
  if (!['index', 'log'].includes(nombre)
      && !enlazadaEnIndice
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
