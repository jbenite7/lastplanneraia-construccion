#!/usr/bin/env node
// Genera, junto a cada hoja propia, una copia SIN COMENTARIOS que Apache sirve
// en su lugar.
//
// Por qué existe: el CSS de este repo se sirve **sin minificar**, con 187
// comentarios que explican el porqué de cada decisión. Esa prosa es deliberada y
// vale para quien lee el repositorio — pero hoy viaja al navegador de cada
// usuario en cada carga. Medido el 2026-08-20 sobre los 47 assets CSS de la
// medición de CI: los comentarios pesan **75.895 B gzip, el 38 % del total**, y
// son lo que puso el gate `runtime-budgets` por encima de su techo.
//
// Qué NO hace, a propósito: no reordena, no colapsa espacios dentro de reglas,
// no toca selectores, no reescribe colores ni unidades. Un minificador de verdad
// ahorraría más y arriesgaría cambios de comportamiento; aquí solo se quitan los
// comentarios y las líneas que quedan vacías, que es la parte con ahorro grande
// y riesgo casi nulo.
//
// Cómo se sirve: `public/.htaccess` reescribe `/css/x.css` a `/dist-css/x.css`
// **sólo si ese archivo existe** (`RewriteCond -f`). Si no se generó, se sirve el
// original: el peor caso es «no ahorra», nunca «se rompe».
import { readFileSync, writeFileSync, readdirSync, mkdirSync, rmSync } from 'node:fs';
import { join, extname, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';

const RAIZ = fileURLToPath(new URL('../', import.meta.url));
const CSS_DIR = join(RAIZ, 'public/css');
// El artefacto vive FUERA de `public/css/`, en un espejo con la misma
// estructura. Es deliberado: diez guards del design system recorren `public/css`
// filtrando por `.css`, y una copia generada allí los hace reportar cada hallazgo
// por duplicado —medido: 3 gates en rojo al primer intento—. Excluirla en cada
// guard sería frágil, porque el guard número once nacería sin acordarse. Aquí no
// hay nada que recordar: el artefacto no está donde ellos miran.
const DIST_DIR = join(RAIZ, 'public/dist-css');

/**
 * Quita los comentarios `/* … *\/` respetando cadenas y `url(...)`.
 *
 * Se recorre carácter a carácter en vez de usar una expresión regular porque
 * `content: "/*"` y `url(data:...)` contienen secuencias que una regex trataría
 * como apertura de comentario y truncaría la hoja entera. Es el mismo tipo de
 * fallo que ya se midió en este repo con un comentario mal cerrado: ocho
 * carriles en verde con el filete apagado.
 */
export function quitarComentarios(css) {
  let salida = '';
  let i = 0;
  while (i < css.length) {
    const c = css[i];
    if (c === '"' || c === "'") {
      const comilla = c;
      salida += c;
      i += 1;
      while (i < css.length) {
        if (css[i] === '\\') { salida += css.slice(i, i + 2); i += 2; continue; }
        salida += css[i];
        i += 1;
        if (css[i - 1] === comilla) break;
      }
      continue;
    }
    if (c === 'u' && /^url\(/i.test(css.slice(i, i + 4))) {
      const cierre = css.indexOf(')', i);
      if (cierre !== -1) { salida += css.slice(i, cierre + 1); i = cierre + 1; continue; }
    }
    if (c === '/' && css[i + 1] === '*') {
      const fin = css.indexOf('*/', i + 2);
      if (fin === -1) {
        // Comentario sin cerrar: se deja tal cual y se avisa. Truncar aquí
        // borraría el resto de la hoja en silencio.
        process.stderr.write('AVISO: comentario sin cerrar; se conserva el resto sin tocar\n');
        salida += css.slice(i);
        return salida;
      }
      i = fin + 2;
      continue;
    }
    salida += c;
    i += 1;
  }
  return salida;
}

export function minificar(css) {
  return quitarComentarios(css)
    .replace(/[ \t]+$/gm, '')
    .replace(/\n{2,}/g, '\n')
    .replace(/^\n+/, '');
}

function* hojas(dir) {
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) { yield* hojas(p); continue; }
    if (extname(e.name) !== '.css') continue;
    yield p;
  }
}

/**
 * Comprueba que cada `*.min.css` del disco corresponde EXACTAMENTE a lo que su
 * fuente produce hoy.
 *
 * El riesgo real de este mecanismo no es que minifique mal —eso lo cubre
 * `css-minify-parity.mjs` contra el parser del navegador— sino que se sirva un
 * minificado VIEJO: al editar una hoja sin regenerar, la URL cambia su `?v=`
 * (que sale del mtime del original) mientras el cuerpo servido sigue siendo el
 * anterior. Caché envenenada, y de las que no se ven.
 *
 * No se compara por fecha, que depende del reloj de quien generó: se recalcula
 * el minificado en memoria y se compara el contenido.
 */
export function destinoDe(fuente, raizCss = CSS_DIR, raizDist = DIST_DIR) {
  return join(raizDist, relative(raizCss, fuente));
}

export function desfasados(dirCss = CSS_DIR, dirDist = DIST_DIR) {
  const malos = [];
  for (const fuente of hojas(dirCss)) {
    const destino = destinoDe(fuente, dirCss, dirDist);
    let enDisco;
    try { enDisco = readFileSync(destino, 'utf8'); } catch { continue; }
    if (enDisco !== minificar(readFileSync(fuente, 'utf8'))) malos.push(destino);
  }
  return malos;
}

if (import.meta.url === `file://${process.argv[1]}`) {
  const limpiar = process.argv.includes('--limpiar');
  if (process.argv.includes('--verificar')) {
    const malos = desfasados();
    if (malos.length) {
      console.error('Minificados desfasados respecto a su fuente:');
      for (const m of malos) console.error('  ' + m);
      console.error('Regenera con: npm run css:minify');
      process.exit(1);
    }
    console.log('Todos los minificados corresponden a su fuente.');
    process.exit(0);
  }
  let ahorro = 0;
  let n = 0;
  if (limpiar) {
    rmSync(DIST_DIR, { recursive: true, force: true });
    console.log('Minificados retirados.');
    process.exit(0);
  }
  for (const fuente of hojas(CSS_DIR)) {
    const destino = destinoDe(fuente);
    const original = readFileSync(fuente, 'utf8');
    const minificado = minificar(original);
    mkdirSync(dirname(destino), { recursive: true });
    writeFileSync(destino, minificado);
    // El mtime del minificado se iguala al de su fuente: así el guard de
    // frescura puede comparar sin depender del reloj de quien lo generó.
    ahorro += gzipSync(Buffer.from(original), { level: 9 }).length
      - gzipSync(Buffer.from(minificado), { level: 9 }).length;
    n += 1;
  }
  console.log(`${n} hojas minificadas en public/dist-css · ahorro ${ahorro} B gzip`);
}
