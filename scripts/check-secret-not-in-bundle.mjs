#!/usr/bin/env node
// Candado automático de la Tarea 12 (S01): falla si el valor de
// `MaintenanceMode::SECRET_PATH` aparece en el bundle construido de la SPA o en cualquier
// archivo versionado de `frontend/src`. El bundle solo debe conocer la FORMA del runtime
// inyectado (`frontend/src/lib/runtime/configuracion.ts`), nunca el VALOR de la ruta oculta:
// el servidor la resuelve en cada request y la entrega ya calculada, en HTML, jamás en
// JavaScript versionado.
//
// El valor nunca se escribe aquí ni en ningún otro archivo de este repo: se lee del código
// fuente de `MaintenanceMode.php` en tiempo de ejecución, la única fuente de verdad.
//
// Lee cada archivo COMPLETO como una sola cadena (nunca línea por línea): el bundle de Vite
// va minificado, sin saltos de línea, así que una comprobación por línea no vería nada —
// exactamente el fallo que ya tuvo este frente con un gate equivalente.

import { readFileSync, readdirSync, existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

export function leerRutaSecreta(raiz = ROOT) {
  const fuente = readFileSync(path.join(raiz, 'src/Core/MaintenanceMode.php'), 'utf8');
  const coincidencia = fuente.match(/SECRET_PATH\s*=\s*'([^']+)'/);

  if (!coincidencia) {
    throw new Error('No se pudo leer MaintenanceMode::SECRET_PATH desde el código fuente.');
  }

  return coincidencia[1];
}

function listarArchivos(dir, extensiones) {
  if (!existsSync(dir)) {
    return [];
  }

  const resultado = [];
  for (const entrada of readdirSync(dir, { withFileTypes: true })) {
    const ruta = path.join(dir, entrada.name);
    if (entrada.isDirectory()) {
      resultado.push(...listarArchivos(ruta, extensiones));
    } else if (extensiones.some((ext) => entrada.name.endsWith(ext))) {
      resultado.push(ruta);
    }
  }

  return resultado;
}

export function objetivosDeEscaneo(raiz = ROOT) {
  return [
    ...listarArchivos(path.join(raiz, 'public/app/assets'), ['.js', '.css']),
    ...listarArchivos(path.join(raiz, 'frontend/src'), ['.ts', '.tsx', '.js', '.jsx', '.css']),
  ];
}

/** @returns {{archivo: string, secreto: string}[]} */
export function buscarFugas(raiz = ROOT) {
  const secreto = leerRutaSecreta(raiz);
  const objetivos = objetivosDeEscaneo(raiz);

  const hallazgos = [];
  for (const archivo of objetivos) {
    const contenido = readFileSync(archivo, 'utf8');
    if (contenido.includes(secreto)) {
      hallazgos.push({ archivo, secreto });
    }
  }

  return { hallazgos, totalEscaneado: objetivos.length };
}

function main() {
  const { hallazgos, totalEscaneado } = buscarFugas(ROOT);

  if (hallazgos.length > 0) {
    console.error('FALLA: la ruta oculta de mantenimiento aparece en archivos publicados/versionados:');
    for (const { archivo } of hallazgos) {
      console.error(`  - ${path.relative(ROOT, archivo)}`);
    }
    process.exit(1);
  }

  console.log(
    `OK: la ruta oculta de mantenimiento no aparece en ${totalEscaneado} archivo(s) escaneados ` +
      '(public/app/assets + frontend/src).',
  );
  process.exit(0);
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  main();
}
