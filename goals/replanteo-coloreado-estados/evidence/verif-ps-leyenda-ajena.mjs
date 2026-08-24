// Comprueba que el rojo de programacion-semanal-legend-honesty.mjs NO lo causan
// las dos reglas de la ola 4 en PS: se desactivan EN VIVO desde el CSSOM y se
// vuelven a leer los colores de la leyenda. Si no cambian, el defecto es ajeno.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
for (const n of ['Preconstrucción Da Porto','Da Porto']) {
  const c=p.locator('.project-item').filter({has:p.getByRole('heading',{name:n,exact:true})});
  if (await c.count()) { await c.locator('button[type="submit"], .btn-enter').first().click(); break; }
}
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programacion-semanal`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(4000);
const leer = () => p.evaluate(() => {
  const out={};
  for (const cls of ['ps-alert-critical-route','ps-alert-high','ps-alert-medium','ps-alert-control']) {
    const e=document.querySelector('.'+cls+' .pdc-legend-swatch, .'+cls);
    out[cls]= e? getComputedStyle(e).backgroundColor : '(sin elemento)';
  }
  return out;
});
console.log('CON las reglas de la ola 4 :', JSON.stringify(await leer()));
// Se neutralizan las dos reglas de la ola 4 con una hoja que las revierte al
// valor previo medido (21px y 0), en vez de borrarlas del CSSOM.
await p.addStyleTag({ content: `.ps-page #hot-container .htCore td { line-height: 21px !important; }
.ps-page main.hot-full-bleed { padding-block-start: 0 !important; }` });
await p.waitForTimeout(600);
console.log('SIN las reglas de la ola 4 :', JSON.stringify(await leer()));
await b.close();
