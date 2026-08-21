// Cuanto ancho sobra en cada columna de Intermedia: compara el ancho asignado
// con el que de verdad ocupa el contenido mas ancho de esa columna.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const c=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await c.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
await p.goto(`${BASE}/programacion-intermedia`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(2600);
const d=await p.evaluate(()=>{
  const med=document.createElement('span');
  med.style.cssText='position:absolute;visibility:hidden;white-space:nowrap;font:600 12px Inter';
  document.body.appendChild(med);
  const anchoTexto=(t)=>{med.textContent=t; return Math.ceil(med.getBoundingClientRect().width);};
  const ths=[...document.querySelectorAll('#hot-container .ht_master thead th')];
  const filas=[...document.querySelectorAll('#hot-container .ht_master tbody tr')];
  return ths.map((th,i)=>{
    const cab=(th.textContent||'').trim().replace(/\s+/g,' ');
    const palabraCab=Math.max(0,...cab.split(/\s+/).map(anchoTexto));
    let maxDato=0;
    for (const tr of filas) {
      const td=tr.children[i]; if(!td) continue;
      const t=(td.textContent||'').trim().replace(/\s+/g,' ');
      if(!t) continue;
      // el dato puede envolver entre palabras: lo que NO puede partirse es la palabra mas larga
      maxDato=Math.max(maxDato, ...t.split(/\s+/).map(anchoTexto));
    }
    const w=Math.round(th.getBoundingClientRect().width);
    const necesita=Math.max(palabraCab, maxDato)+22; // + relleno de celda
    return {i, cab:cab.slice(0,24), w, necesita, holgura:w-necesita};
  });
});
const sobra=d.reduce((a,x)=>a+Math.max(0,x.holgura),0);
console.log('col  asignado  necesita  holgura   cabecera');
for (const x of d.sort((a,b)=>b.holgura-a.holgura))
  console.log(String(x.i).padStart(3), String(x.w).padStart(8), String(x.necesita).padStart(9), String(x.holgura).padStart(8), '  ', x.cab);
console.log('\nHOLGURA TOTAL RECUPERABLE:', sobra, 'px  (hay que recortar 390)');
await b.close();
