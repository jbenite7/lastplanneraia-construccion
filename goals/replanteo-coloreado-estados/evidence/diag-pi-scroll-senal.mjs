import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programacion-intermedia`,{waitUntil:'domcontentloaded'}); await p.waitForTimeout(3000);
console.log(JSON.stringify(await p.evaluate(()=>{
  const h=document.querySelector('#hot-container .ht_master .wtHolder');
  const cs=getComputedStyle(h);
  const antes=h.scrollLeft; h.scrollLeft=200; const desp=h.scrollLeft; h.scrollLeft=antes;
  // barra horizontal visible?
  const barra = h.offsetHeight - h.clientHeight;
  return {overflowX:cs.overflowX, scrollableX: desp>0, altoBarraHoriz: barra,
    scrollbarWidthCss: cs.scrollbarWidth, contMask:getComputedStyle(document.querySelector('#hot-container')).maskImage};
}),null,1));
await b.close();
