// Verificacion ola 4 en PS: se inyecta el delta exacto de programacion-semanal.css
// (el contenedor sirve el codigo de la raiz, no el del worktree) y se mide antes
// y despues, a 1180x820 dark.
import { chromium } from 'playwright';
import { readFileSync } from 'node:fs';
const BASE='http://localhost:8081';
const DELTA = readFileSync(new URL('../../../public/css/programacion-semanal.css', import.meta.url), 'utf8')
  .split('/* Ola 4 (2026-08-20)').slice(1).join('/* Ola 4 (2026-08-20)');
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
for (const n of ['Preconstrucción Da Porto','Optimización Aeropuerto JMC','Da Porto']) {
  const c=p.locator('.project-item').filter({has:p.getByRole('heading',{name:n,exact:true})});
  if (await c.count()) { await c.locator('button[type="submit"], .btn-enter').first().click(); break; }
}
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programacion-semanal`,{waitUntil:'domcontentloaded'});
await p.locator('#loading').waitFor({state:'hidden',timeout:45000});
const max=Number(await p.locator('#Max_Semana').inputValue());
let filas=0;
for (let s=max;s>=1&&filas===0;s-=1){
  await p.evaluate(async (semana)=>{ await fetch('/context/week',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({semana})}); window.location.href='/programacion-semanal'; }, s);
  await p.waitForTimeout(3500);
  filas=await p.evaluate(()=>document.querySelectorAll('#hot-container .ht_master .htCore tbody tr').length);
}
const medir = () => p.evaluate(() => {
  const trs=[...document.querySelectorAll('#hot-container .ht_master .htCore tbody tr')].slice(0,6);
  const td=trs[0].querySelectorAll('td')[1];
  const main=document.querySelector('main.hot-full-bleed');
  const tb=document.querySelector('.aia-toolbar');
  const est=document.querySelector('#hot-container td.ops-state-td');
  const chip=est&&est.querySelector('.ops-state-zoom');
  const doc=document.documentElement;
  return {
    altos: trs.map(t=>Math.round(t.getBoundingClientRect().height)),
    lh: getComputedStyle(td).lineHeight, fsCelda: getComputedStyle(td).fontSize,
    fsCab: getComputedStyle(document.querySelector('#hot-container .ht_clone_top th')).fontSize,
    textoEntero: (td.textContent||'').length,
    recorte: (() => { const cs=getComputedStyle(td);
      return { overflow: cs.overflow, textOverflow: cs.textOverflow, ws: cs.whiteSpace,
        desborda: td.scrollHeight > Math.ceil(td.getBoundingClientRect().height) }; })(),
    chipH: chip? Math.round(chip.getBoundingClientRect().height): null,
    mainPT: getComputedStyle(main).paddingTop,
    respiro: Math.round(tb.getBoundingClientRect().top - main.getBoundingClientRect().top),
    tablaAncho: Math.round(document.querySelector('#hot-container .ht_master .wtHider').getBoundingClientRect().width),
    contAncho: Math.round(document.querySelector('#hot-container').getBoundingClientRect().width),
    scrollX: doc.scrollWidth > doc.clientWidth,
  };
});
console.log('ANTES ', JSON.stringify(await medir()));
await p.addStyleTag({ content: DELTA });
await p.evaluate(()=>{ const i=document.querySelector('#hot-container')&&window; });
await p.waitForTimeout(1200);
await p.evaluate(()=>{ window.dispatchEvent(new Event('resize')); });
await p.waitForTimeout(1800);
console.log('DESPUES', JSON.stringify(await medir()));
await b.close();
