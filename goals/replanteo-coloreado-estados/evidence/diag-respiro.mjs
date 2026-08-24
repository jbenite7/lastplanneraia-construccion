import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const c=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await c.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
for (const [r,n] of [['/programa-general','PG'],['/programacion-intermedia','PI'],['/programacion-semanal','PS']]) {
  await p.goto(`${BASE}${r}`,{waitUntil:'domcontentloaded'}); await p.waitForTimeout(2200);
  const d=await p.evaluate(()=>{
    const enc=document.querySelector('#encabezado, .aia-page-header, header');
    const tb=document.querySelector('.aia-toolbar');
    if(!tb) return {err:'sin toolbar'};
    const rt=tb.getBoundingClientRect();
    return { toolbarTop:Math.round(rt.top), encBottom:enc?Math.round(enc.getBoundingClientRect().bottom):null,
      hueco: enc?Math.round(rt.top-enc.getBoundingClientRect().bottom):null,
      padPagina:(()=>{const m=document.querySelector('main.aia-page, .aia-page');return m?getComputedStyle(m).paddingTop:null})() };
  });
  console.log(n, JSON.stringify(d));
}
await b.close();
