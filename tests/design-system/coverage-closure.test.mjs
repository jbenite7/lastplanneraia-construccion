import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { join } from 'node:path';
import test from 'node:test';

// El hueco que este guard cierra no es «hay cosas sin cubrir»: es que **lo
// descubierto no podia ponerse rojo**. Los guards de manifiesto comprueban que
// cada manifiesto sea valido, que sus escenarios existan y que sus hashes
// cuadren — todo sobre lo que YA esta declarado. Una pantalla que nadie declaro
// no incumple ninguno: no aparece como descubierta, simplemente no aparece.
//
// Medido el 2026-08-19: 32 pantallas reales, 3 sin manifiesto, 5 rutas
// declaradas que ya no responden a GET, y un manifiesto -`foundation-shell`-
// que declara 20 rutas, el 37% del total, con CERO escenarios.
//
// La brecha de hoy va congelada en `coverage-debt.json`: visible y cerrada por
// arriba. Quitar entradas de ahi es bueno; anadirlas significa que la aplicacion
// crecio sin que el sistema de diseno se enterara, y eso tiene que costar una
// decision.

const REPO = fileURLToPath(new URL('../../', import.meta.url));
const MANIFESTS = join(REPO, 'docs/design-system/manifests');
const leer = (p) => JSON.parse(readFileSync(p, 'utf8'));
const deuda = leer(join(REPO, 'docs/design-system/coverage-debt.json'));

const NO_MANIFIESTO = new Set(['inventory.json', 'goal-provenance.json']);

// Una ruta GET es «pantalla» si no es API, ni asset servido por PHP, ni una
// accion sin interfaz. El criterio se declara aqui y no se deduce: un filtro
// vago haria pasar por pantalla a cuarenta endpoints de PDC.
function esPantalla(ruta) {
  if (/(^|\/)api(\/|$)/.test(ruta)) return false;
  if (ruta.startsWith('/runtime/') || ruta.startsWith('/legacy/') || ruta.startsWith('/dev/')) return false;
  if (ruta === '/logout' || ruta === '/login/cancelar') return false;
  return !/\.(css|js|csv)$/.test(ruta);
}

function pantallasReales() {
  const idx = readFileSync(join(REPO, 'public/index.php'), 'utf8');
  const encontradas = new Set();
  for (const m of idx.matchAll(/\$router->get\(\s*'([^']+)'/g)) {
    if (esPantalla(m[1])) encontradas.add(m[1]);
  }
  return [...encontradas].sort();
}

function manifiestos() {
  return readdirSync(MANIFESTS)
    .filter((f) => f.endsWith('.json') && !NO_MANIFIESTO.has(f))
    .map((f) => ({ nombre: f, datos: leer(join(MANIFESTS, f)) }));
}

function rutasDe(datos) {
  const brutas = datos.routes || datos.rutas || [];
  return brutas
    .map((r) => (typeof r === 'string' ? r : r.path || r.route || r.ruta || ''))
    .filter(Boolean);
}

test('toda pantalla real esta declarada en algun manifiesto', () => {
  const declaradas = new Set(manifiestos().flatMap((m) => rutasDe(m.datos)));
  const toleradas = new Set(deuda.pantallas_sin_manifiesto);
  const nuevas = pantallasReales().filter((r) => !declaradas.has(r) && !toleradas.has(r));
  assert.deepEqual(
    nuevas,
    [],
    'Pantallas que sirve el router y que ningun manifiesto declara. No incumplen ningun otro guard '
      + 'porque para el sistema de diseno no existen:\n  ' + nuevas.join('\n  '),
  );
});

test('todo manifiesto con rutas declara al menos un escenario', () => {
  const toleradas = new Set(deuda.manifiestos_sin_escenario);
  const vacios = manifiestos()
    .filter((m) => rutasDe(m.datos).length > 0)
    .filter((m) => ((m.datos.scenarios || m.datos.escenarios || []).length === 0))
    .map((m) => `${m.nombre} (${rutasDe(m.datos).length} rutas)`)
    .filter((n) => !toleradas.has(n.split(' ')[0]));
  assert.deepEqual(
    vacios,
    [],
    'Manifiestos que declaran rutas y no las prueban con ningun escenario. Declarar sin escenario '
      + 'hace parecer cubierto lo que no se mide:\n  ' + vacios.join('\n  '),
  );
});

test('la deuda de cobertura no crece y sus entradas siguen siendo ciertas', () => {
  const reales = new Set(pantallasReales());
  const declaradas = new Set(manifiestos().flatMap((m) => rutasDe(m.datos)));

  // Una tolerancia que sobrevive al problema que toleraba miente por omision:
  // dice que algo sigue pendiente cuando ya se arreglo.
  const yaCubiertas = deuda.pantallas_sin_manifiesto.filter((r) => declaradas.has(r));
  assert.deepEqual(yaCubiertas, [], 'Estas pantallas YA tienen manifiesto: quitalas de coverage-debt.json.\n  ' + yaCubiertas.join('\n  '));

  const yaExisten = deuda.rutas_declaradas_sin_get.filter((r) => reales.has(r));
  assert.deepEqual(yaExisten, [], 'Estas rutas declaradas SI responden a GET: quitalas de coverage-debt.json.\n  ' + yaExisten.join('\n  '));

  assert.ok(deuda.pantallas_sin_manifiesto.length <= 3,
    `La cifra de pantallas sin manifiesto subio a ${deuda.pantallas_sin_manifiesto.length}; el maximo medido y congelado es 3.`);
});
