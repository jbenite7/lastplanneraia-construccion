#!/usr/bin/env node
// Genera las zonas <!-- generado --> de memoria/arquitectura/.
// Escribe SOLO entre marcadores: la prosa de fuera nunca se toca.
// Ver memoria/index.md y docs/superpowers/plans/2026-08-03-arquitectura-en-la-wiki.md.
import { readFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { MODULOS } from './wiki-arquitectura.modulos.mjs';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');

// Algunas declaraciones de ruta no usan un literal de string sino una
// constante de clase (p.ej. MaintenanceMode::SECRET_PATH). Esa ruta ya está
// versionada en texto plano en el propio código fuente (src/Core/MaintenanceMode.php),
// así que resolverla aquí no expone nada nuevo.
const CONSTANTES_RUTA = {
  'MaintenanceMode::SECRET_PATH': '/_aia/operacion/7f3c9b',
};

// --- Rutas -----------------------------------------------------------------

export function leerRutas() {
  const fuente = readFileSync(join(RAIZ, 'public/index.php'), 'utf8');
  const rutas = [];
  const re = /\$router->(get|post|put|delete|any)\(\s*(?:'([^']+)'|([A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*))\s*,/g;
  for (const m of fuente.matchAll(re)) {
    let path = m[2];
    if (!path) {
      const nombre = m[3];
      path = CONSTANTES_RUTA[nombre];
      if (!path) {
        throw new Error(`No sé resolver la constante de ruta '${nombre}'. `
          + 'Añádela a CONSTANTES_RUTA en scripts/wiki-arquitectura.mjs.');
      }
    }
    const cola = fuente.slice(m.index + m[0].length, m.index + m[0].length + 400);
    const ctrl = cola.match(/\\?([A-Za-z0-9_\\]+)::class\s*,\s*'([A-Za-z0-9_]+)'/);
    const legado = cola.match(/require_once\s+PROJECT_ROOT\s*\.\s*'([^']+)'/);
    rutas.push({
      verbo: m[1].toUpperCase(),
      path,
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
