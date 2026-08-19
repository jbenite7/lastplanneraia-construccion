// Analizador estático del design system — DS-F0. Solo lee; no escribe en el repo.
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const RAIZ = process.argv[2];
const patrones = process.argv.slice(3);

function listar(glob) {
  return execFileSync('bash', ['-c', `cd ${JSON.stringify(RAIZ)} && ls ${glob} 2>/dev/null`], { encoding: 'utf8' })
    .split('\n').filter(Boolean);
}

// Quita comentarios /* */ pero conserva las posiciones (los sustituye por espacios),
// para poder distinguir "hex en código" de "hex en comentario" sin perder el número de línea.
function despuntar(src) {
  let out = '', i = 0;
  while (i < src.length) {
    if (src[i] === '/' && src[i + 1] === '*') {
      const fin = src.indexOf('*/', i + 2);
      const hasta = fin === -1 ? src.length : fin + 2;
      for (let k = i; k < hasta; k++) out += src[k] === '\n' ? '\n' : ' ';
      i = hasta;
    } else { out += src[i]; i++; }
  }
  return out;
}

const RE = {
  hex: /#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})\b/g,
  rgbHsl: /\b(?:rgba?|hsla?)\(\s*[0-9]/g,
  important: /!\s*important/g,
  varUso: /var\(\s*(--[A-Za-z0-9_-]+)/g,
  varDef: /(?:[{;]|^)\s*(--[A-Za-z0-9_-]+)\s*:/gm,
  layerAt: /@layer\b/g,
  fontFamily: /font-family\s*:\s*([^;}]+)/g,
  radius: /border-radius\s*:\s*([^;}]+)/g,
  zIndex: /z-index\s*:\s*(-?\d+)/g,
  transition: /transition\s*:\s*([^;}]+)/g,
};

const VENDOR = [
  ['handsontable', /(^|[\s,>+~])(\.ht[A-Za-z_-]*|\.handsontable\b|\.htCore\b|\.ht_[A-Za-z_-]+|\.wtHolder\b|\.wtSpreader\b|\.rowHeader\b|\.colHeader\b)/],
  ['datatables', /(^|[\s,>+~])(\.dataTable[s]?\b|\.dt-[A-Za-z-]+|\.dataTables_[A-Za-z]+|table\.dataTable)/],
  ['select2', /\.select2[A-Za-z-]*/],
  ['sweetalert2', /\.swal2-[A-Za-z-]+/],
  ['jquery-ui', /\.ui-[a-z]+[A-Za-z-]*/],
  ['bootstrap-adminlte', /(^|[\s,>+~])(\.btn\b|\.card\b|\.form-control\b|\.nav-link\b|\.main-sidebar\b|\.content-wrapper\b|\.navbar\b|\.modal-\w+)/],
  ['anychart', /\.anychart[A-Za-z-]*|\.acredits/],
  ['tom-select', /\.ts-[a-z]+[A-Za-z-]*|\.tom-select/],
];

function lineaDe(src, idx) { return src.slice(0, idx).split('\n').length; }

const res = [];
for (const glob of patrones) {
  for (const rel of listar(glob)) {
    let src;
    try { src = readFileSync(`${RAIZ}/${rel}`, 'utf8'); } catch { continue; }
    const codigo = despuntar(src);
    const lineas = src.split('\n');
    const f = { archivo: rel, lineas: lineas.length, bytes: Buffer.byteLength(src) };

    const cazar = (re, texto) => {
      const out = [];
      re.lastIndex = 0;
      for (const m of texto.matchAll(re)) out.push({ linea: lineaDe(texto, m.index), texto: m[0].trim(), captura: m[1] });
      return out;
    };

    f.hexCodigo = cazar(RE.hex, codigo);
    f.hexComentario = cazar(RE.hex, src).filter(h => !f.hexCodigo.some(c => c.linea === h.linea && c.texto === h.texto));
    f.rgbHsl = cazar(RE.rgbHsl, codigo);
    f.important = cazar(RE.important, codigo);
    f.varUso = [...new Set(cazar(RE.varUso, codigo).map(x => x.captura))];
    f.varUsoDetalle = cazar(RE.varUso, codigo);
    f.varDef = [...new Set(cazar(RE.varDef, codigo).map(x => x.captura))];
    f.layer = cazar(RE.layerAt, codigo).length;
    f.fontFamily = cazar(RE.fontFamily, codigo);
    f.radius = cazar(RE.radius, codigo).filter(r => !/var\(/.test(r.captura || ''));
    f.zIndex = cazar(RE.zIndex, codigo);
    f.vendor = {};
    for (const [nombre, re] of VENDOR) {
      const g = new RegExp(re.source, 'g');
      const hits = cazar(g, codigo);
      if (hits.length) f.vendor[nombre] = hits.length;
    }
    res.push(f);
  }
}
process.stdout.write(JSON.stringify(res));
