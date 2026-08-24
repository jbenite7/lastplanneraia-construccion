import { chromium } from 'playwright';
import { readFileSync } from 'node:fs';
const contrato = JSON.parse(readFileSync('docs/design-system/state-semantics.json','utf8'));
const b = await chromium.launch();
const p = await (await b.newContext({ viewport:{width:800,height:600} })).newPage();
await p.goto('about:blank');
await p.addStyleTag({ content: `@font-face{font-family:"Inter";font-weight:100 900;font-display:swap;src:url("file://${process.cwd()}/public/vendor/fonts/aia/inter-latin-v20.woff2") format("woff2");}` });
await p.evaluate(() => document.fonts.ready);
const medir = async (textos) => p.evaluate((ts) => {
  const c = document.createElement('canvas').getContext('2d');
  c.font = '600 11.52px Inter';                    // 0.72rem, peso del chip
  return ts.map((t) => ({ t, w: Math.ceil(c.measureText(t).width) }));
}, textos);
// Presupuesto: contenedor 128px (columna 164 - 36) menos padding del chip (8+8) = 112
const UTIL = 112;
for (const m of contrato.moduleMappings) {
  if (!['programacion-semanal','programacion-intermedia','programa-general'].includes(m.module)) continue;
  const labels = m.states.map((s) => s.label);
  const medidos = await medir(labels);
  console.log(`\n[${m.module}]  util ${UTIL}px a 0.72rem/600`);
  for (const { t, w } of medidos.sort((a,b)=>b.w-a.w)) {
    const palabras = t.split(' ');
    const larga = (await medir([palabras.reduce((a,x)=>x.length>a.length?x:a,'')]))[0];
    console.log(`  ${w>UTIL?'NO':'ok'} ${String(w).padStart(3)}px  ${t.padEnd(28)} palabra mas larga ${larga.t} = ${larga.w}px`);
  }
}
await b.close();
