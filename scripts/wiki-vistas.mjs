#!/usr/bin/env node
// Censo de qué páginas lista cada vista de `memoria/paginas.base`.
//
// Existe para una comprobación que el lint no puede hacer: un frontmatter puede quedar **bien
// formado y mal clasificado a la vez**, y entonces el lint pasa en verde mientras una página se
// cae del catálogo sin que nadie lo note. Se corre antes y después de un retag y se comparan las
// dos salidas; `--comparar <antes>` lo hace y sale en rojo si alguna página cambió de vista.
import { readdirSync, readFileSync, existsSync } from 'node:fs';
import { join, relative, extname, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { bloqueFrontmatter, campo } from './wiki-esquema.mjs';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');
const WIKI = join(RAIZ, 'memoria');

const paginas = [];
(function rec(dir) {
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) rec(p);
    else if (extname(e.name) === '.md') paginas.push(relative(RAIZ, p));
  }
})(WIKI);
paginas.sort();

// Las vistas se leen del propio `paginas.base`: si mañana se añade una, este censo la cubre sin
// que nadie venga a añadirla aquí.
const base = existsSync(join(WIKI, 'paginas.base'))
  ? readFileSync(join(WIKI, 'paginas.base'), 'utf8') : '';
const vistas = [];
for (const m of base.matchAll(/- type: \w+\n\s+name: ([^\n]+)\n\s+filters:\n\s+and:\n\s+- note\.(\w+) == "([^"]+)"/g)) {
  vistas.push({ nombre: m[1].trim(), campo: m[2], valor: m[3] });
}

const censo = {};
for (const v of vistas) {
  censo[v.nombre] = paginas.filter((rel) => {
    const fm = bloqueFrontmatter(readFileSync(join(RAIZ, rel), 'utf8'));
    return fm !== null && campo(fm, v.campo) === v.valor;
  });
}

const previo = process.argv.includes('--comparar')
  ? JSON.parse(readFileSync(process.argv[process.argv.indexOf('--comparar') + 1], 'utf8')) : null;

if (!previo) {
  console.log(JSON.stringify(censo, null, 2));
  process.exit(0);
}

let roto = 0;
for (const nombre of new Set([...Object.keys(previo), ...Object.keys(censo)])) {
  const antes = new Set(previo[nombre] ?? []);
  const ahora = new Set(censo[nombre] ?? []);
  const caidas = [...antes].filter((p) => !ahora.has(p));
  const nuevas = [...ahora].filter((p) => !antes.has(p));
  if (caidas.length || nuevas.length) {
    roto++;
    console.log(`VISTA «${nombre}»: ${antes.size} → ${ahora.size}`);
    for (const p of caidas) console.log(`  − se cayó: ${p}`);
    for (const p of nuevas) console.log(`  + entró:   ${p}`);
  }
}
if (roto) { console.log(`\n${roto} vista(s) cambiaron de contenido.`); process.exitCode = 1; }
else console.log(`Ninguna vista cambió. ${vistas.length} vistas, ${paginas.length} páginas.`);
