import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

/**
 * Escaneo de fuente persistido de T01-Tarea 3 (spec 2026-08-30-t01-shell-runtime-react-design
 * §10.2): la navegación React es un renderizador puro de `sesion.navigation.groups`, que ya
 * llega ordenado y filtrado por `ShellNavigationService` (PHP). Si alguno de estos patrones
 * reaparece en `frontend/src`, es que el cliente volvió a tomar una decisión de autorización
 * que le corresponde al servidor — justo lo que esta tarea retiró.
 *
 * No es una prueba de comportamiento (esa vive en `NavegacionLateral.test.tsx`): es una
 * regresión contra que alguien reintroduzca la matriz `ocultasPorRol` u otra equivalente.
 */

const raizFrontendSrc = fileURLToPath(new URL('../../frontend/src', import.meta.url));

async function archivosFuente(dir) {
  const entradas = await readdir(dir, { withFileTypes: true });
  const archivos = await Promise.all(entradas.map(async (entrada) => {
    const rutaCompleta = path.join(dir, entrada.name);
    if (entrada.isDirectory()) {
      return archivosFuente(rutaCompleta);
    }
    if (/\.(ts|tsx)$/.test(entrada.name) && !entrada.name.endsWith('.test.ts') && !entrada.name.endsWith('.test.tsx')) {
      return [rutaCompleta];
    }
    return [];
  }));

  return archivos.flat();
}

async function leerFuenteCompleta() {
  const rutas = await archivosFuente(raizFrontendSrc);
  const contenidos = await Promise.all(rutas.map(async (ruta) => ({
    ruta: path.relative(raizFrontendSrc, ruta),
    texto: await readFile(ruta, 'utf8'),
  })));

  return contenidos;
}

test('ninguna fuente de frontend/src reintroduce una tabla de ocultamiento por rol', async () => {
  const archivos = await leerFuenteCompleta();

  for (const { ruta, texto } of archivos) {
    assert.doesNotMatch(texto, /ocultasPorRol/, `${ruta}: reintrodujo una tabla de ocultamiento por rol`);
    assert.doesNotMatch(texto, /esVisible\s*\(/, `${ruta}: reintrodujo un filtro de visibilidad calculado en cliente`);
  }
});

test('ninguna fuente de frontend/src ramifica sobre el código crudo de rol', async () => {
  const archivos = await leerFuenteCompleta();
  // `role: z.string()` (definición de esquema) y `role: 'A'`/`role: rol` (fixtures/paso de
  // datos) no son ramificación — lo prohibido es *decidir* algo comparando el código de rol.
  const patronesDeAutorizacionPorRol = [
    /\.role\s*===\s*['"]/, // sesion.user?.role === 'X'
    /\brole\s*===\s*['"][A-Z]{1,3}['"]/, // role === 'G' / rol === 'V'
    /\[['"](?:A|D|R|DCV|OT|G|S|SG|C|V)['"]\]\s*:/, // mapa Record<rolCrudo, ...> tipo ocultasPorRol
  ];

  for (const { ruta, texto } of archivos) {
    for (const patron of patronesDeAutorizacionPorRol) {
      assert.doesNotMatch(texto, patron, `${ruta}: ramifica sobre el código de rol (${patron})`);
    }
  }
});

test('el shell de navegación no construye rutas ni query strings de módulos protegidos', async () => {
  const rutaComponente = path.join(raizFrontendSrc, 'shell', 'NavegacionLateral.tsx');
  const texto = await readFile(rutaComponente, 'utf8');

  // Ningún literal de ruta de módulo LPS/BI/PDC hardcodeado: todo `href` debe venir del
  // manifiesto del servidor (`item.href`), nunca de una cadena escrita en este archivo.
  const catalogoDeRutasProhibido = [
    '/programa-general',
    '/programacion-intermedia',
    '/programacion-semanal',
    '/plan-compras',
    '/profesionales',
    '/subcontratistas',
    '/control-cambios',
    '/programa-general-actualizar',
    '/bi/',
  ];

  for (const ruta of catalogoDeRutasProhibido) {
    assert.ok(!texto.includes(ruta), `NavegacionLateral.tsx contiene la ruta hardcodeada '${ruta}'`);
  }

  // Tampoco construcción de query strings (síntoma de armar un destino "autorizado" en cliente).
  assert.doesNotMatch(texto, /[?&][a-zA-Z_]+=/, 'NavegacionLateral.tsx construye una query string propia');
});
