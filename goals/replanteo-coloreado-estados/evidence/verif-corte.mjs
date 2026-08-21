// Verifica la linea roja del frente: ninguna celda puede esconder texto.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const c=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await c.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
let fallos=0;
for (const [ruta,nom] of [['/programa-general','PG'],['/programacion-intermedia','PI'],['/programacion-semanal','PS']]) {
  await p.goto(`${BASE}${ruta}`,{waitUntil:'domcontentloaded'});
  await p.waitForTimeout(2500);
  const r=await p.evaluate(()=>{
    const celdas=[...document.querySelectorAll('#hot-container .ht_master tbody td, #hot-container .ht_master thead th')];
    const cortadas=celdas.filter(td=>td.scrollHeight>td.clientHeight+1 || td.scrollWidth>td.clientWidth+1)
      .slice(0,5).map(td=>({txt:(td.textContent||'').trim().slice(0,28), sh:td.scrollHeight, ch:td.clientHeight, sw:td.scrollWidth, cw:td.clientWidth}));
    const filas=[...document.querySelectorAll('#hot-container .ht_master tbody tr')].slice(0,14).map(tr=>Math.round(tr.getBoundingClientRect().height));
    const holder=document.querySelector('#hot-container .wtHolder');
    return {total:celdas.length, cortadas, filas, suma:filas.reduce((a,x)=>a+x,0),
      desborda: holder? holder.scrollWidth>holder.clientWidth+2 : null};
  });
  fallos += r.cortadas.length;
  console.log(`\n${nom}: ${r.total} celdas | cortadas: ${r.cortadas.length} | desbordaX: ${r.desborda}`);
  console.log('  filas:', r.filas.join(','), '| suma:', r.suma);
  for (const x of r.cortadas) console.log('   CORTE:', JSON.stringify(x));
}
console.log('\n' + (fallos? 'ROJO: hay texto escondido' : 'VERDE: ninguna celda esconde texto'));
await b.close();
