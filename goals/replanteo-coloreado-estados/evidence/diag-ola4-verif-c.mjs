// Ola 4 — distribucion real de altos de fila y desborde. Solo mide.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
for (const [ruta,nom] of [['/programa-general','PG'],['/programacion-intermedia','PI'],['/programacion-semanal','PS']]) {
  await p.goto(`${BASE}${ruta}`,{waitUntil:'domcontentloaded'});
  await p.waitForTimeout(3500);
  console.log('\n== '+nom+' ==\n'+JSON.stringify(await p.evaluate(()=>{
    const trs=[...document.querySelectorAll('#hot-container .ht_master tbody tr')];
    const altos=trs.map(t=>Math.round(t.getBoundingClientRect().height));
    const conteo={}; altos.forEach(a=>conteo[a]=(conteo[a]||0)+1);
    const tds=[...document.querySelectorAll('#hot-container .ht_master tbody td')];
    const cortY=tds.filter(c=>c.scrollHeight>c.clientHeight+1);
    const cortX=tds.filter(c=>c.scrollWidth>c.clientWidth+1);
    const h=document.querySelector('#hot-container .wtHolder');
    const pa=document.querySelector('.aia-toolbar')?.parentElement;
    const tb=document.querySelector('.aia-toolbar');
    return {filas:altos.length, alto27:altos.slice(0,27), conteoAltos:conteo,
      cortadasY:cortY.length, muestraY:cortY.slice(0,3).map(c=>({sh:c.scrollHeight,ch:c.clientHeight,t:c.innerText.slice(0,30)})),
      cortadasX:cortX.length, muestraX:cortX.slice(0,3).map(c=>({sw:c.scrollWidth,cw:c.clientWidth,t:c.innerText.slice(0,30)})),
      desbordeX:h?{sw:h.scrollWidth,cw:h.clientWidth,exceso:h.scrollWidth-h.clientWidth}:null,
      respiro: (tb&&pa)?+(tb.getBoundingClientRect().top-pa.getBoundingClientRect().top).toFixed(1):null};
  }),null,1));
}
await b.close();
