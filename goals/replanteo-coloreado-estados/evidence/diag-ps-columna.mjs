// Que celda impone el alto de la fila en PS: por columna, ancho, texto y alto propio.
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
  const cabs=[...document.querySelectorAll('#hot-container .ht_clone_top th .colHeader')].map(e=>e.textContent.trim());
  const trs=[...document.querySelectorAll('#hot-container .ht_master .htCore tbody tr')];
  let peor=trs[0], h=0; for (const tr of trs){ const x=tr.getBoundingClientRect().height; if (x>h){h=x;peor=tr;} }
  const celdas=[...peor.querySelectorAll('td')].map((td,i)=>{
    const cs=getComputedStyle(td); const r=td.getBoundingClientRect();
    const inner=td.firstElementChild;
    return { col: cabs[i]||('#'+i), w: Math.round(r.width), h: Math.round(r.height),
      contenido: Math.round(td.scrollHeight), ws: cs.whiteSpace, minH: cs.minHeight,
      pad: cs.padding, texto: (td.textContent||'').trim().slice(0,50),
      hijo: inner? inner.className.slice(0,40)+' h='+Math.round(inner.getBoundingClientRect().height):'-' };
  });
  return { filaAlto: Math.round(h), celdas };
}),null,2));
await b.close();
