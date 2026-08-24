// Que hay dentro de la celda Actividad de PS: texto completo, saltos duros y
// cuantas lineas ocupa. Sin esto no se puede decidir si la fila alta es honesta.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
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
console.log(JSON.stringify(await p.evaluate(()=>{
  const trs=[...document.querySelectorAll('#hot-container .ht_master .htCore tbody tr')].slice(0,6);
  const alturas={};
  for (const tr of trs){ const h=Math.round(tr.getBoundingClientRect().height); alturas[h]=(alturas[h]||0)+1; }
  const muestras = trs.map(tr=>{
    const td=tr.querySelectorAll('td')[1];
    const t=td.textContent||'';
    return { alto: Math.round(tr.getBoundingClientRect().height), saltos:(t.match(/\n/g)||[]).length,
      largo: t.length, texto: t };
  });
  const td=trs[0].querySelectorAll('td')[1];
  const cs=getComputedStyle(td);
  return { alturas, cell:{ ws: cs.whiteSpace, lh: cs.lineHeight, wordBreak: cs.wordBreak,
    overflowWrap: cs.overflowWrap, w: Math.round(td.getBoundingClientRect().width) }, muestras };
}),null,2));
await b.close();
