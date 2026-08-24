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
  const ths=[...document.querySelectorAll('#hot-container .ht_master thead th')];
  const cols=ths.map((th,i)=>({i, txt:(th.textContent||'').trim().replace(/\s+/g,' ').slice(0,26),
    w:Math.round(th.getBoundingClientRect().width)}));
  const holder=document.querySelector('#hot-container .wtHolder');
  const tabla=document.querySelector('#hot-container .ht_master table');
  return {cols, suma:cols.reduce((a,x)=>a+x.w,0),
    holder:Math.round(holder.clientWidth), tabla:Math.round(tabla.getBoundingClientRect().width),
    sobra: Math.round(tabla.getBoundingClientRect().width - holder.clientWidth)};
});
console.log('holder', d.holder, '| tabla', d.tabla, '| SOBRA', d.sobra, '| columnas', d.cols.length);
for (const c of d.cols.sort((a,b)=>b.w-a.w)) console.log(String(c.w).padStart(5)+'px  ['+String(c.i).padStart(2)+']', c.txt);
await b.close();
