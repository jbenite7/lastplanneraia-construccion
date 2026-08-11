#!/usr/bin/env node
/**
 * Gate de paridad RBAC: compara la matriz de capacidades del servidor
 * (App\Security\RbacManager::getCapabilities) con la del cliente
 * (public/js/rbac_capabilities.js), rol por rol y capacidad por capacidad.
 *
 * Nace en rojo a propósito: hoy las capacidades se declaran dos veces y nada
 * las compara. Este script destapa las divergencias; no las corrige.
 *
 * Clasificación (decisión del coordinador, 2026-08-10): no toda discrepancia
 * de superficie es peligrosa. Solo engaña a alguien la que existe en los dos
 * lados y responde cosas distintas:
 *
 *   - "valores distintos": la capacidad existe en servidor y cliente pero
 *     no coincide. FALLO DURO, sin excepción posible — es el corazón del
 *     gate y hoy son las únicas dos divergencias reales encontradas
 *     (canManageContracts/canManagePdC para el rol R; los dos nombres eran
 *     alias exactos y se colapsaron en canManagePdC el 2026-08-10).
 *   - "solo existe en cliente": la interfaz se inventa una capacidad que el
 *     servidor no conoce. FALLA salvo excepción declarada con motivo —
 *     puede ser un ayudante de UI legítimo (ver nota sobre canEditLps más
 *     abajo), pero eso se decide explícitamente, no por omisión.
 *   - "solo existe en servidor": el servidor conoce una capacidad que la
 *     interfaz no ofrece. NO es un fallo — la interfaz simplemente no la
 *     usa, no hay riesgo de que ofrezca algo no autorizado — pero se
 *     imprime igual, contada y agrupada, porque es el dato que alguien
 *     necesitará el día que se pregunte por qué una acción no aparece.
 */

import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

export const ROLES_CANONICOS = ['A', 'D', 'R', 'DCV', 'OT', 'G', 'S', 'SG', 'C', 'V'];

const SCRIPT_PHP = `
require "/var/www/html/vendor/autoload.php";
$roles = ["A","D","R","DCV","OT","G","S","SG","C","V"];
$out = [];
foreach ($roles as $r) { $out[$r] = \\App\\Security\\RbacManager::getCapabilities($r); }
echo json_encode($out);
`;

/**
 * Vuelca la matriz de capacidades del servidor ejecutando PHP dentro del
 * contenedor `app`. Requiere que el runtime de Docker Compose esté arriba.
 */
export function cargarMatrizServidor() {
  const salida = execFileSync(
    'docker',
    ['compose', 'exec', '-T', 'app', 'php', '-r', SCRIPT_PHP],
    { cwd: root, encoding: 'utf8' },
  );
  return JSON.parse(salida);
}

/**
 * `public/js/rbac_capabilities.js` no es un módulo ES: termina asignando a
 * `window`. Para leerlo desde Node hay que darle un `window` postizo. No se
 * modifica el archivo: el gate debe leer exactamente lo que el navegador
 * carga, no una copia adaptada.
 *
 * Solo se comparan funciones que devuelven booleano. `getRoleName` devuelve
 * un string (siempre "truthy"): no es una capacidad de autorización, es un
 * texto de UI, así que se salta y se declara en `omitidas` en vez de
 * compararse como si fuera un permiso.
 *
 * Trampa conocida — `canEditLps(role, currentWeek, maxWeek)`: recibe más
 * argumentos que el rol, pero a diferencia de otras funciones del archivo
 * NO lanza excepción si se la llama solo con el rol. Para 'A'/'D' devuelve
 * `true` de inmediato; para el resto calcula con `currentWeek`/`maxWeek`
 * en `undefined` (→ `NaN` en la resta), y el resultado es un booleano
 * "plausible" pero sin sentido real. El try/catch de abajo no la atrapa
 * porque no lanza. El gate la reporta igual como "solo existe en cliente"
 * (no hay ninguna capacidad con ese nombre exacto en el servidor), así que
 * la falta de excepción no pasa colada — pero quien la lea no debe confiar
 * en el valor booleano mostrado como si reflejara la lógica real de semana.
 * Queda exceptuada explícitamente en docs/rbac-parity-exceptions.json.
 */
export function cargarMatrizCliente(roles) {
  const fuente = readFileSync(path.join(root, 'public/js/rbac_capabilities.js'), 'utf8');
  const ventana = {};
  const ejecutar = new Function('window', `${fuente}\nreturn window;`);
  ejecutar(ventana);
  const caps = ventana.RbacCapabilities;
  if (!caps) throw new Error('rbac_capabilities.js no expuso window.RbacCapabilities');

  const matriz = {};
  const omitidasPorTipo = new Map(); // nombre -> typeof del valor crudo devuelto
  for (const rol of roles) {
    matriz[rol] = {};
    for (const [nombre, valor] of Object.entries(caps)) {
      if (typeof valor !== 'function') continue;
      let crudo;
      try {
        crudo = valor.call(caps, rol);
      } catch {
        // Una capacidad que exige más argumentos que el rol no es comparable con la
        // matriz del servidor, que solo recibe el rol. Se declara y se salta.
        matriz[rol][nombre] = null;
        continue;
      }
      if (typeof crudo !== 'boolean') {
        // No es una capacidad de autorización: se salta y se informa por nombre.
        if (!omitidasPorTipo.has(nombre)) omitidasPorTipo.set(nombre, typeof crudo);
        continue;
      }
      matriz[rol][nombre] = crudo;
    }
  }
  return { matriz, omitidas: [...omitidasPorTipo.entries()].map(([nombre, tipo]) => ({ nombre, tipo })) };
}

/**
 * Compara capacidad a capacidad y separa las tres categorías: valores
 * distintos (fallo duro), solo en cliente (fallo salvo excepción) y solo en
 * servidor (informativo, nunca falla).
 */
export function comparar(servidor, cliente) {
  const valoresDistintos = [];
  const soloCliente = [];
  const soloServidor = [];

  for (const rol of Object.keys(servidor)) {
    const capsServidor = servidor[rol];
    const capsCliente = cliente[rol] || {};
    for (const [cap, valorServidor] of Object.entries(capsServidor)) {
      const valorCliente = capsCliente[cap];
      if (valorCliente === undefined) {
        soloServidor.push({ rol, cap, servidor: valorServidor });
        continue;
      }
      if (valorCliente === null) continue; // no comparable, declarado en cargarMatrizCliente
      if (valorCliente !== valorServidor) {
        valoresDistintos.push({ rol, cap, servidor: valorServidor, cliente: valorCliente });
      }
    }
    for (const cap of Object.keys(capsCliente)) {
      if (!(cap in capsServidor) && capsCliente[cap] !== null) {
        soloCliente.push({ rol, cap, cliente: capsCliente[cap] });
      }
    }
  }

  return { valoresDistintos, soloCliente, soloServidor };
}

export function cargarExcepciones() {
  const ruta = path.join(root, 'docs/rbac-parity-exceptions.json');
  const contenido = JSON.parse(readFileSync(ruta, 'utf8'));
  return contenido.exceptions ?? [];
}

/**
 * `rol: "*"` en una excepción cubre los diez roles canónicos con una sola
 * entrada — útil para ayudantes de UI (como canEditLps) cuya divergencia es
 * la misma en todos los roles, no una particularidad de uno solo.
 */
function esExcepcion(fallo, excepcion) {
  return excepcion.capacidad === fallo.cap && (excepcion.rol === '*' || excepcion.rol === fallo.rol);
}

/**
 * Filtra los fallos "solo existe en cliente" usando las excepciones
 * declaradas y devuelve además el conjunto de excepciones que no se
 * usaron: una excepción declarada y no usada es en sí misma un fallo del
 * gate (evita que la lista se pudra). Las excepciones NO aplican a
 * "valores distintos": esa categoría es el corazón del gate y no admite
 * excepción — se arregla, no se declara.
 */
export function aplicarExcepciones(soloCliente, excepciones) {
  const usadas = new Set();
  const soloClienteRestante = soloCliente.filter((fallo) => {
    const excepcion = excepciones.find((e) => esExcepcion(fallo, e));
    if (excepcion) {
      usadas.add(excepcion);
      return false;
    }
    return true;
  });
  const noUsadas = excepciones.filter((e) => !usadas.has(e));
  return { soloClienteRestante, noUsadas };
}

export function formatearValorDistinto(fallo) {
  return `[${fallo.rol}] ${fallo.cap}: valores distintos (servidor=${JSON.stringify(fallo.servidor)}, cliente=${JSON.stringify(fallo.cliente)})`;
}

export function formatearSoloCliente(fallo) {
  return `[${fallo.rol}] ${fallo.cap}: solo existe en cliente (cliente=${JSON.stringify(fallo.cliente)})`;
}

export function formatearExcepcionNoUsada(excepcion) {
  return `[${excepcion.rol}] ${excepcion.capacidad}: excepción declarada pero no encontrada como divergencia (motivo: ${excepcion.motivo})`;
}

/**
 * Agrupa "solo existe en servidor" por capacidad: como la ausencia es a
 * nivel de función (no de rol), una capacidad ausente en el cliente lo está
 * para los diez roles por igual. Agrupar evita imprimir 120 líneas casi
 * idénticas y deja el dato accionable: qué capacidades del servidor no
 * tienen reflejo alguno en la interfaz.
 */
export function agruparSoloServidor(soloServidor) {
  const porCapacidad = new Map();
  for (const item of soloServidor) {
    if (!porCapacidad.has(item.cap)) porCapacidad.set(item.cap, []);
    porCapacidad.get(item.cap).push(item.rol);
  }
  return [...porCapacidad.entries()].map(([cap, roles]) => ({ cap, roles }));
}

/**
 * Ejecuta el gate completo. `ok` depende solo de "valores distintos" (sin
 * excepción posible) y de "solo en cliente" no exceptuado / excepciones no
 * usadas. "solo en servidor" y las funciones no booleanas se reportan pero
 * nunca hacen fallar el gate.
 */
export function ejecutarGate() {
  const servidor = cargarMatrizServidor();
  const { matriz: cliente, omitidas } = cargarMatrizCliente(ROLES_CANONICOS);
  const { valoresDistintos, soloCliente, soloServidor } = comparar(servidor, cliente);
  const excepciones = cargarExcepciones();
  const { soloClienteRestante, noUsadas } = aplicarExcepciones(soloCliente, excepciones);

  return {
    ok: valoresDistintos.length === 0 && soloClienteRestante.length === 0 && noUsadas.length === 0,
    valoresDistintos,
    soloCliente: soloClienteRestante,
    excepcionesNoUsadas: noUsadas,
    soloServidorAgrupado: agruparSoloServidor(soloServidor),
    funcionesNoBooleanas: omitidas,
  };
}

// Permite ejecutarlo directamente: `node scripts/rbac-parity.mjs`
const esEjecucionDirecta = process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1]);
if (esEjecucionDirecta) {
  const resultado = ejecutarGate();

  if (resultado.valoresDistintos.length > 0) {
    console.log(`Valores distintos — fallo duro (${resultado.valoresDistintos.length}):`);
    for (const fallo of resultado.valoresDistintos) console.log('  ' + formatearValorDistinto(fallo));
  }
  if (resultado.soloCliente.length > 0) {
    console.log(`Solo existen en cliente — fallo salvo excepción (${resultado.soloCliente.length}):`);
    for (const fallo of resultado.soloCliente) console.log('  ' + formatearSoloCliente(fallo));
  }
  if (resultado.excepcionesNoUsadas.length > 0) {
    console.log(`Excepciones declaradas y no usadas (${resultado.excepcionesNoUsadas.length}):`);
    for (const excepcion of resultado.excepcionesNoUsadas) console.log('  ' + formatearExcepcionNoUsada(excepcion));
  }
  if (resultado.funcionesNoBooleanas.length > 0) {
    console.log(`Funciones del cliente que no son capacidades — devuelven algo distinto de booleano (${resultado.funcionesNoBooleanas.length}):`);
    for (const { nombre, tipo } of resultado.funcionesNoBooleanas) {
      console.log(`  ${nombre}: no es una capacidad, devuelve ${tipo}`);
    }
  }
  console.log(
    `Solo existen en servidor — informativo, no falla (${resultado.soloServidorAgrupado.length} capacidades × ${ROLES_CANONICOS.length} roles):`,
  );
  for (const { cap, roles } of resultado.soloServidorAgrupado) {
    console.log(`  ${cap}: sin reflejo en cliente para ${roles.length} roles (${roles.join(', ')})`);
  }

  if (resultado.ok) {
    console.log('OK: sin fallos duros ni divergencias de cliente sin exceptuar.');
  }
  process.exit(resultado.ok ? 0 : 1);
}
