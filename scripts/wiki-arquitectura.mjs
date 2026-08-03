#!/usr/bin/env node
// Genera las zonas <!-- generado --> de memoria/arquitectura/.
// Escribe SOLO entre marcadores: la prosa de fuera nunca se toca.
// Ver memoria/index.md y docs/superpowers/plans/2026-08-03-arquitectura-en-la-wiki.md.
import { readFileSync, existsSync, readdirSync, writeFileSync, mkdirSync } from 'node:fs';
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
//
// El generador no inventa: un nombre que "parece" tabla o servicio pero no
// se puede confirmar contra una fuente de verdad (el esquema real, el árbol
// de archivos de src/Services y src/Support) se descarta, no se publica.

const RUIDO_SQL = new Set(['select', 'from', 'where', 'join', 'inner', 'left', 'right', 'outer',
  'on', 'as', 'and', 'or', 'set', 'values', 'into', 'update', 'insert', 'delete', 'group',
  'order', 'by', 'limit', 'offset', 'union', 'null', 'not', 'is', 'in', 'exists', 'case',
  'when', 'then', 'else', 'end', 'distinct', 'having', 'dual', 'if']);

// Quita comentarios de bloque, de línea (// y #) antes de aplicar cualquier
// regex de extracción: si no, texto en prosa dentro de un comentario ("top 5
// from stage 1") se lee como si fuera SQL real.
function sinComentariosPhp(t) {
  return t
    .replace(/\/\*[\s\S]*?\*\//g, ' ')
    .replace(/(^|[^:])\/\/.*$/gm, '$1')
    .replace(/^\s*#.*$/gm, ' ');
}

// Índice real de archivos bajo src/Services y src/Support: única fuente de
// verdad para decidir si un nombre capturado por regex es un servicio real.
let _indiceServiciosCache = null;
function indiceServicios() {
  if (_indiceServiciosCache) return _indiceServiciosCache;
  const rutasRelativas = new Set();
  const basenames = new Map(); // nombre de clase (sin namespace) -> ruta relativa a src/
  for (const raiz of ['src/Services', 'src/Support']) {
    const base = join(RAIZ, raiz);
    if (!existsSync(base)) continue;
    const walk = (dir, prefijo) => {
      for (const entry of readdirSync(dir, { withFileTypes: true })) {
        if (entry.isDirectory()) {
          walk(join(dir, entry.name), `${prefijo}${entry.name}/`);
        } else if (entry.name.endsWith('.php')) {
          const rel = `${raiz}/${prefijo}${entry.name}`;
          rutasRelativas.add(rel);
          basenames.set(entry.name.slice(0, -4), rel);
        }
      }
    };
    walk(base, '');
  }
  _indiceServiciosCache = { rutasRelativas, basenames };
  return _indiceServiciosCache;
}

// Devuelve la ruta real (relativa a RAIZ) de un nombre de servicio ya
// validado por serviciosDe(), o null si por algún motivo no se encuentra.
export function archivoDeServicio(nombre) {
  const { rutasRelativas, basenames } = indiceServicios();
  const conNamespace = [...rutasRelativas].find((r) => r.endsWith(`/${nombre.replace(/\\/g, '/')}.php`));
  if (conNamespace) return join(RAIZ, conNamespace);
  const plano = basenames.get(nombre);
  return plano ? join(RAIZ, plano) : null;
}

export function serviciosDe(archivo) {
  if (!existsSync(archivo)) return [];
  const t = sinComentariosPhp(readFileSync(archivo, 'utf8'));
  const { rutasRelativas, basenames } = indiceServicios();
  const s = new Set();
  for (const m of t.matchAll(/App\\(Services|Support)\\([A-Za-z0-9_\\]+)/g)) {
    const raiz = `src/${m[1]}`;
    const clase = m[2];
    const rel = `${raiz}/${clase.replace(/\\/g, '/')}.php`;
    // Normaliza al nombre de clase (sin namespace) para que un uso calificado
    // (App\Services\Pdc\X) y un uso corto (new X(...)) en el mismo archivo
    // no aparezcan como dos servicios distintos.
    if (rutasRelativas.has(rel)) s.add(clase.split('\\').pop());
  }
  for (const m of t.matchAll(/new\s+\\?([A-Z][A-Za-z0-9_]*(?:Service|Processor|Matcher|Resolver|Gate|Policy))\s*\(/g)) {
    const nombre = m[1];
    if (basenames.has(nombre)) s.add(nombre);
  }
  return [...s].sort();
}

// Esquema real de la base de datos, vía docker compose (nunca hardcodeado).
// Si el contenedor no responde, devuelve null: el llamador debe tratarlo
// como "no se pudo confirmar", no como "no hay tablas". IMPORTANTE: cuando
// esto devuelve null, tablasDe() también devuelve null en vez de caer a un
// fallback de solo-regex — una lista sin contrastar contra el esquema
// parece verídica pero no lo es, y eso es justo lo que el generador no debe
// hacer. "Sin db, sin tablas" es intencional, no un defecto pendiente.
let _esquemaCache = undefined;
export function leerEsquema() {
  if (_esquemaCache !== undefined) return _esquemaCache;
  try {
    const env = readFileSync(join(RAIZ, '.env'), 'utf8');
    const leer = (clave) => {
      const m = env.match(new RegExp(`^${clave}=(.*)$`, 'm'));
      return m ? m[1].trim().replace(/^"(.*)"$/, '$1') : null;
    };
    const usuario = leer('DB_USER') || 'root';
    const clave = leer('DB_PASS') || '';
    const base = leer('DB_NAME');
    if (!base) { _esquemaCache = null; return null; }
    const salida = execFileSync('docker', [
      'compose', 'exec', '-T', 'db', 'mysql', `-u${usuario}`, `-p${clave}`, '-N', '-e', 'SHOW TABLES', base,
    ], { cwd: RAIZ, encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] });
    _esquemaCache = new Set(salida.split('\n').map((l) => l.trim()).filter(Boolean));
  } catch {
    _esquemaCache = null;
  }
  return _esquemaCache;
}

export function tablasDe(archivos) {
  const esquema = leerEsquema();
  // Sin esquema no hay forma honesta de distinguir una tabla real de un
  // nombre de variable o columna que también matchea la regex: en vez de
  // publicar una lista con apariencia de cierta, se declara indeterminada
  // igual que el carril legado.
  if (!esquema) return null;
  const s = new Set();
  for (const a of archivos) {
    if (!existsSync(a)) continue;
    const t = sinComentariosPhp(readFileSync(a, 'utf8'));
    for (const m of t.matchAll(/\b(?:FROM|JOIN|INTO|UPDATE)\s+`?([a-z][a-z0-9_]{2,})`?/gi)) {
      const nombre = m[1].toLowerCase();
      if (RUIDO_SQL.has(nombre)) continue;
      if (esquema.has(nombre)) s.add(nombre);
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

// Calcula los problemas de cobertura sin imprimir ni salir: lo reutilizan
// tanto `--cobertura` (modo informe) como `--escribir` (gate previo).
function erroresDeCobertura(rutas) {
  const porModulo = new Map(MODULOS.map((m) => [m.slug, []]));
  const huerfanas = [];
  for (const r of rutas) {
    const a = asignar(r.path);
    if (!a) huerfanas.push(r);
    else porModulo.get(a.mod.slug).push(r);
  }

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

  return { errores, porModulo };
}

function cobertura() {
  const rutas = leerRutas();
  const { errores, porModulo } = erroresDeCobertura(rutas);

  for (const m of MODULOS) {
    console.log(`${String(porModulo.get(m.slug).length).padStart(4)}  ${m.slug}`);
  }
  console.log(`${String(rutas.length).padStart(4)}  TOTAL`);

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
    .map((s) => archivoDeServicio(s))
    .filter((p) => p && existsSync(p));
  const soloLegado = mias.length > 0 && mias.every((r) => r.tipo === 'legado');

  const capacidades = mod.capacidades.map((cap) => {
    const roles = Object.entries(rbac)
      .filter(([, mapa]) => mapa[cap] === true)
      .map(([rol]) => rol);
    const existe = Object.values(rbac).some((mapa) => cap in mapa);
    if (!existe) {
      console.error(`CAPACIDAD FANTASMA ${mod.slug}: '${cap}' no existe en RbacManager — revisar el manifiesto.`);
    }
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

// --- Composición y escritura ------------------------------------------------

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
    ? '_indeterminado_ — o bien todas las rutas de este módulo son legadas (las consultas '
      + 'viven en scripts procedurales y la extracción textual no es fiable ahí), o bien no '
      + 'se pudo leer el esquema real de la base de datos al generar esta página.\n'
    : d.tablas.length ? d.tablas.map((t) => `- \`${t}\``).join('\n') + '\n' : '_indeterminado_\n');

  partes.push('\n### Quién puede\n');
  partes.push(mod.capacidades.length
    ? tabla(['Capacidad', 'Roles que la tienen'],
        d.capacidades.map((c) => [`\`${c.cap}\``,
          !c.existe ? '_capacidad desconocida — revisar el manifiesto_'
          : c.roles.length ? c.roles.join(', ') : '—']))
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

  // Gate 1: cobertura. Una ruta huérfana, un prefijo muerto o un destino
  // indeterminado nuevo pasan desapercibidos si no se comprueban aquí —
  // --cobertura no es obligatorio en ningún contrato del repo, así que el
  // único comando que la gente recuerda (--escribir) tiene que absorberlo.
  const { errores: erroresCobertura } = erroresDeCobertura(rutas);
  if (erroresCobertura.length) {
    console.error('No se escribe: hay problemas de cobertura sin resolver.\n');
    console.error(erroresCobertura.join('\n'));
    console.error(`\n${erroresCobertura.length} problemas de cobertura. Ejecuta `
      + '`node scripts/wiki-arquitectura.mjs --cobertura` para el detalle, asigna la ruta a un '
      + 'módulo de wiki-arquitectura.modulos.mjs (o resuelve el destino indeterminado) y reintenta.');
    process.exit(1);
  }

  // Gate 2: esquema de base de datos alcanzable. Sin él, tablasDe() no puede
  // distinguir una tabla real de ruido y el generador tendría que sustituir
  // 118 líneas de datos reales por "_indeterminado_" en silencio. Preferimos
  // abortar entero: dejar la wiki como estaba es mejor que dejarla peor.
  if (leerEsquema() === null) {
    console.error('No se escribe: no se pudo leer el esquema real de la base de datos '
      + '(revisa que el contenedor `db` esté arriba y que DB_NAME/.env apunten a una base '
      + 'existente). Levanta `db` con `docker compose up -d db` y reintenta.');
    process.exit(1);
  }

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

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  if (process.argv.includes('--cobertura')) cobertura();
  else if (process.argv.includes('--escribir')) escribir();
  else if (process.argv.includes('--datos')) {
    const slug = process.argv[process.argv.indexOf('--datos') + 1];
    const mod = MODULOS.find((m) => m.slug === slug);
    if (!mod) { console.error(`Módulo desconocido: ${slug}`); process.exit(1); }
    console.log(JSON.stringify(datosDe(mod, leerRutas(), leerRbac()), null, 2));
  }
  else console.log('Uso: node scripts/wiki-arquitectura.mjs [--cobertura | --escribir | --datos <slug>]');
}
