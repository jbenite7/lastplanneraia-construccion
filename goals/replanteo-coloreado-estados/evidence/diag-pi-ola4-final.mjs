import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const ctx=await b.newContext({viewport:{width:1180,height:820}});
const p=await ctx.newPage();
const errores=[]; p.on('console',m=>{if(m.type()==='error') errores.push(m.text().slice(0,120));});
p.on('pageerror',e=>errores.push('PAGEERROR '+String(e).slice(0,120)));
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programacion-intermedia`,{waitUntil:'domcontentloaded'}); await p.waitForTimeout(3500);
// scrollear la rejilla para revisar filas mas abajo, donde el texto envuelve
const d1 = await p.evaluate(()=>{
  const h=document.querySelector('#hot-container .ht_master .wtHolder'); h.scrollTop=1200; return h.scrollTop;
});
await p.waitForTimeout(900);
const d = await p.evaluate(()=>{
  const tds=[...document.querySelectorAll('#hot-container .ht_master tbody tr td')];
  const cortadas=tds.filter(td=>td.scrollHeight>td.clientHeight+1);
  const altos=[...new Set(tds.map(td=>Math.round(td.getBoundingClientRect().height)))].sort((a,b)=>a-b);
  const ths=[...document.querySelectorAll('#hot-container .ht_master thead th')];
  return {altosTrasScroll:altos, celdasCortadas:cortadas.length,
    muestra:cortadas.slice(0,3).map(t=>t.textContent.trim().slice(0,50)),
    cabecerasCortadas:ths.filter(t=>t.scrollWidth>t.clientWidth+1).length, celdas:tds.length};
});
console.log('scrollTop', d1, JSON.stringify(d,null,1));
console.log('errores consola:', JSON.stringify(errores));
await p.screenshot({path:'goals/replanteo-coloreado-estados/evidence/pi-ola4-scroll-1180x820-dark.png'});
await b.close();
