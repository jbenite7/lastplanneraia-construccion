// Comprueba que el CSS minificado es EQUIVALENTE al original para el navegador,
// no que se parezca en el texto.
//
// El primer intento comparó el número de llaves y punto y coma del archivo, y dio
// descuadres en 30 hojas. Eran falsos: los comentarios llevan ejemplos de código
// en su prosa, con sus llaves dentro, así que al quitarlos el texto cambia sin
// que cambie ni una regla. **Contar texto no mide lo que el navegador aplica** —
// la misma lección que ya costó cara aquí (memoria/trampas/guard-de-texto-no-ve-el-parseo.md).
//
// Así que el juez es el parser real: se inyecta cada hoja, se leen sus
// `cssRules` y se comparan una a una.
import { chromium } from 'playwright';
import { readdirSync, readFileSync } from 'node:fs';
import { join, extname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { destinoDe } from '../../scripts/css-minify.mjs';

const RAIZ = fileURLToPath(new URL('../../', import.meta.url));
const CSS_DIR = join(RAIZ, 'public/css');

function* hojas(dir) {
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) { yield* hojas(p); continue; }
    if (extname(e.name) !== '.css') continue;
    yield p;
  }
}

const navegador = await chromium.launch();
const pagina = await (await navegador.newContext()).newPage();
await pagina.goto('about:blank');

const reglasDe = (css) => pagina.evaluate((texto) => {
  const hoja = new CSSStyleSheet();
  try { hoja.replaceSync(texto); } catch (e) { return { error: String(e) }; }
  const aplanar = (reglas) => [...reglas].flatMap((r) => (
    r.cssRules ? [r.cssText.split('{')[0].trim(), ...aplanar(r.cssRules)] : [r.cssText]
  ));
  return { reglas: aplanar(hoja.cssRules) };
}, css);

let fallos = 0;
let comprobadas = 0;
for (const fuente of hojas(CSS_DIR)) {
  const destino = destinoDe(fuente);
  let original;
  let minificado;
  try {
    original = readFileSync(fuente, 'utf8');
    minificado = readFileSync(destino, 'utf8');
  } catch { continue; }

  const a = await reglasDe(original);
  const b = await reglasDe(minificado);
  comprobadas += 1;
  const nombre = relative(RAIZ, fuente);

  if (a.error || b.error) {
    console.log(`✘ ${nombre}: el parser rechaza (${a.error || b.error})`);
    fallos += 1;
    continue;
  }
  if (a.reglas.length !== b.reglas.length) {
    console.log(`✘ ${nombre}: ${a.reglas.length} reglas -> ${b.reglas.length}`);
    fallos += 1;
    continue;
  }
  const distinta = a.reglas.findIndex((r, i) => r !== b.reglas[i]);
  if (distinta !== -1) {
    console.log(`✘ ${nombre}: la regla ${distinta} difiere`);
    console.log(`    original : ${a.reglas[distinta].slice(0, 120)}`);
    console.log(`    minificado: ${b.reglas[distinta].slice(0, 120)}`);
    fallos += 1;
  }
}
await navegador.close();

console.log(`\n${comprobadas} hojas comparadas regla a regla contra el parser del navegador.`);
if (fallos) { console.log(`FALLOS: ${fallos}`); process.exitCode = 1; }
else console.log('Ninguna diferencia: el minificado es equivalente para el navegador.');
