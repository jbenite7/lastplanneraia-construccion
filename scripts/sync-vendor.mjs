#!/usr/bin/env node
/**
 * Sincroniza `public/vendor/` desde `node_modules/`.
 *
 * POR QUE EXISTE (hallazgo del barrido del 2026-08-07): las 22 librerias de `public/vendor/`
 * estaban copiadas a mano. Ningun gestor las veia: sin `package.json` que las declarara, sin
 * lockfile, sin `npm audit`, sin `npm outdated`. Envejecian en silencio —jquery-ui era de 2013— y
 * la unica forma de saber su version era abrir el archivo y leer el banner.
 *
 * Ahora se declaran en `devDependencies` con version EXACTA (sin `^`), y este script las copia.
 * Actualizar pasa a ser: subir la version en `package.json`, `npm install`, `npm run sync:vendor`,
 * y verificar. Auditable y reversible.
 *
 * MODOS
 *   --check  (por defecto)  Compara y REPORTA. No escribe. Sale con 1 si algo difiere.
 *   --write                 Copia. Usalo solo tras revisar la salida de --check.
 *
 * El modo por defecto es `--check` a proposito: sobrescribir 22 librerias a ciegas puede mover
 * goldens sin que nadie lo note, que es justo la clase de fallo silencioso que este barrido
 * encontro (jQuery 1.12.4 -> 3.6.0 hacia desaparecer una tabla con CERO errores de consola).
 */
import { createHash } from 'node:crypto';
import { copyFileSync, existsSync, mkdirSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const raiz = fileURLToPath(new URL('..', import.meta.url));
const escribir = process.argv.includes('--write');

/**
 * Copias cuyo archivo en `public/vendor/` NO coincide byte a byte con el tarball de npm de la
 * version declarada. Medido el 2026-08-08: difieren en unos cientos de bytes, lo que apunta a que
 * se bajaron del CDN del proyecto y no de npm (los builds de CDN concatenan distinto).
 *
 * NO se sincronizan: sobrescribirlas cambiaria bytes sin ninguna necesidad, y este barrido ya
 * midio lo que cuesta un cambio de librería que nadie pidio. Quedan DECLARADAS —para que
 * `npm outdated` y `npm audit` las vean— pero su archivo se conserva. Se sincronizaran cuando
 * toque subirlas de version, que es cuando el cambio de bytes esta justificado.
 */
const SIN_SINCRONIZAR = new Map([
  ['chart.js/chart.umd.min.js', 'vendor 205399 B vs npm 205125 B'],
  ['popper.min.js', 'vendor 21004 B vs npm 21233 B'],
  ['select2/select2.min.css', 'vendor 15275 B vs npm 14966 B'],
  ['pdfmake/vfs_fonts.js', 'vendor 926228 B vs npm 926233 B'],
  ['datatables/js/jquery.dataTables.min.js', 'vendor 84647 B vs npm 82867 B'],
]);

/** [origen en node_modules, destino en public/vendor] */
const MAPA = [
  ['jquery/dist/jquery.min.js', 'jquery.min.js'],
  ['jquery-ui/dist/jquery-ui.min.js', 'jquery-ui.min.js'],
  // El CSS va con su JS: quedaron desparejados un momento (JS 1.14.2 con CSS 1.10.1) y el
  // datepicker toma clases de ambos.
  ['jquery-ui/dist/themes/base/jquery-ui.min.css', 'jquery-ui.css'],
  ['bootstrap/dist/js/bootstrap.min.js', 'bootstrap/bootstrap.min.js'],
  ['bootstrap/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'],
  ['popper.js/dist/umd/popper.min.js', 'popper.min.js'],
  ['select2/dist/js/select2.min.js', 'select2/select2.min.js'],
  ['select2/dist/css/select2.min.css', 'select2/select2.min.css'],
  ['handsontable/dist/handsontable.full.min.js', 'handsontable/handsontable.full.min.js'],
  ['handsontable/dist/handsontable.full.min.css', 'handsontable/handsontable.full.min.css'],
  ['chart.js/dist/chart.umd.js', 'chart.js/chart.umd.min.js'],
  ['jszip/dist/jszip.min.js', 'jszip/jszip.min.js'],
  ['pdfmake/build/pdfmake.min.js', 'pdfmake/pdfmake.min.js'],
  ['pdfmake/build/vfs_fonts.js', 'pdfmake/vfs_fonts.js'],
  ['sweetalert2/dist/sweetalert2.all.min.js', 'sweetalert2.all.min.js'],
  ['sweetalert2/dist/sweetalert2.min.css', 'sweetalert2.min.css'],
  ['toastr/build/toastr.min.js', 'toastr.min.js'],
  ['toastr/build/toastr.min.css', 'toastr.min.css'],
  ['tom-select/dist/js/tom-select.complete.min.js', 'tom-select/tom-select.complete.min.js'],
  ['tom-select/dist/css/tom-select.bootstrap4.min.css', 'tom-select/tom-select.bootstrap4.min.css'],
  ['icheck-bootstrap/icheck-bootstrap.min.css', 'icheck-bootstrap/icheck-bootstrap.min.css'],
  ['datatables.net/js/jquery.dataTables.min.js', 'datatables/js/jquery.dataTables.min.js'],
];

const sha = (ruta) => createHash('sha256').update(readFileSync(ruta)).digest('hex').slice(0, 12);

const iguales = [];
const distintos = [];
const omitidos = [];
const ausentes = [];

for (const [origen, destino] of MAPA) {
  const rutaOrigen = join(raiz, 'node_modules', origen);
  const rutaDestino = join(raiz, 'public/vendor', destino);
  if (!existsSync(rutaOrigen)) { ausentes.push({ origen, motivo: 'no esta en node_modules' }); continue; }
  if (!existsSync(rutaDestino)) { ausentes.push({ origen: destino, motivo: 'no esta en public/vendor' }); continue; }
  const a = sha(rutaOrigen);
  const b = sha(rutaDestino);
  if (a === b) { iguales.push(destino); continue; }
  if (SIN_SINCRONIZAR.has(destino)) {
    omitidos.push({ destino, motivo: SIN_SINCRONIZAR.get(destino) });
    continue;
  }
  distintos.push({ destino, npm: a, vendor: b });
  if (escribir) {
    mkdirSync(dirname(rutaDestino), { recursive: true });
    copyFileSync(rutaOrigen, rutaDestino);
  }
}

console.log(`identicos  : ${iguales.length}  (el vendor coincide byte a byte con npm)`);
console.log(`a copiar   : ${distintos.length}${escribir ? ' (COPIADOS)' : ''}`);
for (const d of distintos) console.log(`   ${d.destino.padEnd(48)} npm ${d.npm} != vendor ${d.vendor}`);
console.log(`omitidos   : ${omitidos.length}  (declarados, no sincronizados a proposito)`);
for (const o of omitidos) console.log(`   ${o.destino.padEnd(48)} ${o.motivo}`);
console.log(`sin pareja : ${ausentes.length}`);
for (const a of ausentes) console.log(`   ${a.origen.padEnd(48)} ${a.motivo}`);

if (!escribir && (distintos.length || ausentes.length)) process.exit(1);
