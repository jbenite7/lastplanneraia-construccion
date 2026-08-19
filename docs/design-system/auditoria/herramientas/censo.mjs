import { readFileSync, writeFileSync } from 'node:fs';
import { MODULOS } from '/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/bold-neumann-485f23/scripts/wiki-arquitectura.modulos.mjs';
import { leerRutas, asignar } from '/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/bold-neumann-485f23/scripts/wiki-arquitectura.mjs';

const RAIZ = '/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/bold-neumann-485f23';
const inv = JSON.parse(readFileSync(`${RAIZ}/docs/design-system/manifests/inventory.json`, 'utf8'));
const estadoDs = new Map(inv.modules.map(m => [m.moduleId, m.status]));

// slug del censo de rutas → moduleId del inventario del design system (verificado a mano)
const PUENTE = {
  autenticacion: 'auth',
  'selector-de-proyectos': 'projects',
  'programa-general': 'programa-general',
  cronograma: 'programa-general-actualizar',
  'programacion-intermedia': 'programacion-intermedia',
  'programacion-semanal': 'programacion-semanal',
  'plan-de-compras': 'plan-compras-v2',
  profesionales: 'profesionales',
  subcontratistas: 'subcontratistas',
  'control-de-cambios': 'control-cambios',
  indicadores: 'indicadores',
  'torre-de-control-bi': 'bi-runtime',
  'escalamientos-y-crisis': 'escalamientos',
  'panel-admin': 'admin',
  'laboratorio-design-system': 'laboratory',
};

// Verificado leyendo cada controlador: estas rutas GET no rinden pantalla.
const NO_RINDEN = new Map([
  ['/logout', 'LoginController::logout redirige a /login'],
  ['/login/cancelar', 'LoginController::cancelPasswordChange redirige a /login'],
  ['/dev/entrar', 'DevDoor: abre sesion y redirige'],
  ['/programa-general/set-filtro', 'ProgramaGeneralController::setFilter muta $_SESSION y redirige'],
  ['/programacion-intermedia/set-filtro', 'ProgramacionIntermediaController::setFilter muta $_SESSION y redirige'],
  ['/programacion-intermedia/set-view-all', 'ProgramacionIntermediaController::setViewAll muta $_SESSION; responde JSON si es ajax'],
  ['/reportes/{tipo}', 'ReportController::generate responde json_encode'],
  ['/legacy/cambiar_pagina.php', 'src/Legacy/Endpoints/cambiar_pagina.php solo hace header(Location)'],
]);
// Rutas distintas que rinden una pantalla ya contada. Se listan para no perderlas del censo.
const ALIAS_DE = new Map([
  ['/', '/login (LoginController::index -> views/auth/login.view.php)'],
  ['/_aia/operacion/7f3c9b', '/login en modo mantenimiento (misma vista, otro formAction)'],
  ['/admin/dashboard', '/admin (DashboardController@index)'],
]);

const rutas = leerRutas();
const porModulo = new Map(MODULOS.map(m => [m.slug, []]));
for (const r of rutas) {
  const a = asignar(r.path); const slug = a && a.mod.slug;
  if (slug && porModulo.has(slug)) porModulo.get(slug).push(r);
}

const filas = MODULOS.map(m => {
  const rs = porModulo.get(m.slug) || [];
  const moduleId = PUENTE[m.slug] || null;
  return {
    slug: m.slug,
    titulo: m.titulo,
    prefijos: m.rutas,
    rutas: rs.length,
    rutasGet: rs.filter(r => r.verbo === 'GET').length,
    // Superficie = ruta GET que rinde una pantalla HTML propia. Se excluyen, con su motivo
    // verificado en el controlador: las APIs (JSON/CSV), los assets de /runtime/, las rutas que
    // solo mutan sesion y redirigen, y los alias que rinden una pantalla ya contada.
    superficies: rs.filter(r => r.verbo === 'GET'
      && !r.path.startsWith('/api/') && !r.path.includes('/api/')
      && !r.path.startsWith('/runtime/')
      && !NO_RINDEN.has(r.path) && !ALIAS_DE.has(r.path)).map(r => r.path),
    alias: rs.filter(r => r.verbo === 'GET' && ALIAS_DE.has(r.path)).map(r => ({ ruta: r.path, de: ALIAS_DE.get(r.path) })),
    rutasQueNoRindenPantalla: rs.filter(r => r.verbo === 'GET' && NO_RINDEN.has(r.path)).map(r => ({ ruta: r.path, porQue: NO_RINDEN.get(r.path) })),
    destinosLegado: rs.filter(r => r.tipo === 'legado').length,
    designSystem: { moduleId, estado: moduleId ? (estadoDs.get(moduleId) || 'ausente-del-inventario') : 'ausente-del-inventario' },
  };
});

// admin/ tiene su propio front controller y no aparece en public/index.php.
// Se cuenta aparte, con la misma definición de superficie.
const adminSrc = readFileSync(`${RAIZ}/admin/public/index.php`, 'utf8');
const adminRutas = [...adminSrc.matchAll(/\$router->add\('(GET|POST)',\s*'([^']+)'/g)].map(m => ({ verbo: m[1], path: m[2] }));
const ADMIN_NO_VISTA = new Set(['/logout', '/dev/entrar', '/dashboard/report-progress', '/usuarios/cargos',
  '/usuarios/sugerir-usuario', '/proyectos/sugerir-rol', '/proyectos/respaldar',
  '/matching/family-catalog/export', '/pdc/limpieza/conteos']);
const adminFila = filas.find(f => f.slug === 'panel-admin');
adminFila.fuente = 'admin/public/index.php';
adminFila.rutas = adminRutas.length;
adminFila.rutasGet = adminRutas.filter(r => r.verbo === 'GET').length;
const adminGet = adminRutas.filter(r => r.verbo === 'GET' && !ADMIN_NO_VISTA.has(r.path))
  .map(r => '/admin' + (r.path === '/' ? '' : r.path));
adminFila.superficies = adminGet.filter(p => !ALIAS_DE.has(p));
adminFila.alias = adminGet.filter(p => ALIAS_DE.has(p)).map(p => ({ ruta: p, de: ALIAS_DE.get(p) }));
adminFila.rutasQueNoRindenPantalla = [...ADMIN_NO_VISTA].map(p => ({ ruta: '/admin' + p, porQue: 'endpoint de datos o redireccion' }));

writeFileSync(process.argv[2], JSON.stringify({
  generado: 'scratchpad/censo.mjs (DS-F0, tanda 1)',
  fuente: ['public/index.php via scripts/wiki-arquitectura.mjs', 'docs/design-system/manifests/inventory.json'],
  totalRutas: rutas.length + adminRutas.length,
  totalRutasIndexPhp: rutas.length,
  totalRutasAdmin: adminRutas.length,
  modulos: filas,
}, null, 2) + '\n');
console.log('rutas:', rutas.length, '| modulos:', filas.length);
for (const f of filas) console.log(String(f.rutas).padStart(4), String(f.superficies.length).padStart(3), f.designSystem.estado.padEnd(24), f.slug);
