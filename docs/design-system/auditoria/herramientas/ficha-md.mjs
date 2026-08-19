import { readFileSync } from 'node:fs';
const [, , cssJson, vistasJson, mapaJson, censoJson, impJson, slug] = process.argv;
const css = JSON.parse(readFileSync(cssJson, 'utf8'));
const vistas = JSON.parse(readFileSync(vistasJson, 'utf8'));
const mapa = JSON.parse(readFileSync(mapaJson, 'utf8'));
const censo = JSON.parse(readFileSync(censoJson, 'utf8'));
const imp = JSON.parse(readFileSync(impJson, 'utf8'));

const m = mapa[slug];
const mod = censo.modulos.find(x => x.slug === slug);
const misCss = css.filter(f => m.css.includes(f.archivo));
const misVistas = vistas.filter(f => m.vistas.some(p => f.archivo.startsWith(p)));

const L = [];
L.push(`## Hojas de estilo`, '');
L.push('| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |');
L.push('|---|---:|---:|---:|---:|---:|---:|');
for (const f of misCss) {
  L.push(`| \`${f.archivo}\` | ${f.lineas} | ${f.important.length} | ${f.hexCodigo.length} | ${f.rgbHsl.length} | ${f.layer ? 'sí' : '**no**'} | ${f.varUso.length} |`);
}
const tImp = misCss.reduce((a, f) => a + f.important.length, 0);
if (tImp) {
  const fam = {};
  for (const f of misCss) { const v = imp[f.archivo]; if (v) for (const [k, c] of Object.entries(v.porFamilia)) fam[k] = (fam[k] || 0) + c; }
  L.push('', `### A qué apunta cada uno de los ${tImp} \`!important\``, '');
  L.push('| Familia del selector | Cuántos | % |', '|---|---:|---:|');
  for (const [k, v] of Object.entries(fam).sort((a, b) => b[1] - a[1])) L.push(`| ${k} | ${v} | ${(100 * v / tImp).toFixed(0)}% |`);
}
if (misVistas.length) {
  L.push('', '## Vistas', '');
  L.push('| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |');
  L.push('|---|---:|---:|---:|:-:|:-:|---:|---:|');
  for (const f of misVistas) {
    L.push(`| \`${f.archivo}\` | ${f.lineas} | ${f.styleAttr.length} | ${f.styleBloque.length} | ${f.main ? '✓' : '—'} | ${f.h1 ? '✓' : '—'} | ${f.idsDuplicados.length} | ${f.aia.length} |`);
  }
}
const vend = {};
for (const f of misCss) for (const [k, v] of Object.entries(f.vendor)) vend[k] = (vend[k] || 0) + v;
if (Object.keys(vend).length) {
  L.push('', '## Selectores de vendor que este módulo toca', '');
  L.push(Object.entries(vend).sort((a, b) => b[1] - a[1]).map(([k, v]) => `- \`${k}\` — ${v} selectores`).join('\n'));
}
console.log(L.join('\n'));
