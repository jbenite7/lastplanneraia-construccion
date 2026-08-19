// Escanea vistas PHP: estilo en linea, bloques <style>, ids, clases aia-*, clases de vendor.
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
const RAIZ = process.argv[2];
const dirs = process.argv.slice(3);
const archivos = execFileSync('bash', ['-c',
  `cd ${JSON.stringify(RAIZ)} && find ${dirs.join(' ')} -name '*.php' -o -name '*.tsx' -o -name '*.jsx' 2>/dev/null | sort`],
  { encoding: 'utf8' }).split('\n').filter(Boolean);

const linea = (s, i) => s.slice(0, i).split('\n').length;
const caza = (src, re) => { const o = []; for (const m of src.matchAll(re)) o.push({ linea: linea(src, m.index), texto: m[0].slice(0, 120), captura: m[1] }); return o; };

const out = [];
for (const rel of archivos) {
  const src = readFileSync(`${RAIZ}/${rel}`, 'utf8');
  const f = { archivo: rel, lineas: src.split('\n').length };
  f.styleAttr = caza(src, /style\s*=\s*"([^"]*)"/g).filter(x => x.captura.trim() && !/^<\?php/.test(x.captura));
  f.styleBloque = caza(src, /<style\b[^>]*>/g);
  f.hex = caza(src, /#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/g);
  f.important = caza(src, /!\s*important/g);
  const ids = caza(src, /\bid\s*=\s*"([A-Za-z_][A-Za-z0-9_-]*)"/g);
  const cuenta = new Map();
  for (const i of ids) cuenta.set(i.captura, (cuenta.get(i.captura) || 0) + 1);
  f.idsDuplicados = [...cuenta.entries()].filter(([, n]) => n > 1)
    .map(([id, n]) => ({ id, veces: n, lineas: ids.filter(x => x.captura === id).map(x => x.linea) }));
  f.aia = [...new Set(caza(src, /\b(aia-[a-z0-9-]+)/g).map(x => x.captura))];
  f.main = /<main\b/.test(src);
  f.h1 = /<h1\b/.test(src);
  out.push(f);
}
process.stdout.write(JSON.stringify(out));
