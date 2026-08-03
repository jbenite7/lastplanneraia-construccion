#!/usr/bin/env node
// Genera las zonas <!-- generado --> de memoria/arquitectura/.
// Escribe SOLO entre marcadores: la prosa de fuera nunca se toca.
// Ver memoria/index.md y docs/superpowers/plans/2026-08-03-arquitectura-en-la-wiki.md.
import { readFileSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { execFileSync } from 'node:child_process';
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
    // La ventana no puede pasarse de largo del siguiente $router->…: si no, un
    // closure corto (p.ej. legacy que solo hace require_once) se traga la
    // definición de la ruta siguiente y le roba su destino.
    const inicioCola = m.index + m[0].length;
    const siguiente = fuente.indexOf('$router->', inicioCola);
    const finCola = siguiente === -1 ? fuente.length : siguiente;
    const cola = fuente.slice(inicioCola, Math.min(finCola, inicioCola + 400));
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

// --- Datos por módulo --------------------------------------------------------

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

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  if (process.argv.includes('--cobertura')) cobertura();
  else if (process.argv.includes('--datos')) {
    const slug = process.argv[process.argv.indexOf('--datos') + 1];
    const mod = MODULOS.find((m) => m.slug === slug);
    if (!mod) { console.error(`Módulo desconocido: ${slug}`); process.exit(1); }
    console.log(JSON.stringify(datosDe(mod, leerRutas(), leerRbac()), null, 2));
  }
  else console.log('Uso: node scripts/wiki-arquitectura.mjs [--cobertura | --escribir | --datos <slug>]');
}
