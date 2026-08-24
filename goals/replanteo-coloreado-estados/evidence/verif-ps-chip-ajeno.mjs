// Mide el contrato de "sin recorte" del chip de Estado Operativo (dark-density,
// linea 44) CON y SIN las dos reglas de la ola 4, neutralizadas en vivo a sus
// valores previos medidos. Si el veredicto no cambia, el rojo es ajeno.
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
  await p.waitForTimeout(4500);
  filas=await p.evaluate(()=>document.querySelectorAll('#hot-container .ht_master .htCore tbody tr').length);
}
console.log('filas en la semana medida:', filas);
const medir = () => p.locator('#hot-container .ops-state-zoom:visible').evaluateAll((nodes)=>nodes.map(n=>{
  const chip=n.querySelector('.ops-state-chip'); const count=n.querySelector('.ops-state-count');
  const cs=chip?getComputedStyle(chip):null; const vis=Boolean(cs&&cs.display!=='none');
  return { texto:(n.getAttribute('aria-label')||n.textContent.trim()).slice(0,40), chipVisible:vis,
    recorteAlto: vis? chip.scrollHeight - chip.clientHeight : null,
    recorteAncho: vis? chip.scrollWidth - chip.clientWidth : null,
    countVisible: Boolean(count && count.getClientRects().length), hayCount: Boolean(count) };
}));
const resumen = (m) => ({ total:m.length, sinChip:m.filter(x=>!x.chipVisible).length,
  sinChipNiCount:m.filter(x=>!x.chipVisible&&!x.countVisible).map(x=>x.texto),
  recortados:m.filter(x=>x.chipVisible&&(x.recorteAlto>1||x.recorteAncho>1)).map(x=>x.texto) });
console.log('CON ola 4 :', JSON.stringify(resumen(await medir())));
await p.addStyleTag({ content: `.ps-page #hot-container .htCore td { line-height: 21px !important; }
.ps-page main.hot-full-bleed { padding-block-start: 0 !important; }` });
await p.waitForTimeout(1200);
console.log('SIN ola 4 :', JSON.stringify(resumen(await medir())));
await b.close();
