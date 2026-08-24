import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
for (const [r,n] of [['/programa-general','PG'],['/programacion-intermedia','PI']]) {
  await p.goto(`${BASE}${r}`,{waitUntil:'domcontentloaded'}); await p.waitForTimeout(3000);
  console.log(n, JSON.stringify(await p.evaluate(()=>{
    const c=document.querySelector('#hot-container'); const r=c.getBoundingClientRect();
    const h=document.querySelector('#hot-container .ht_master .wtHolder');
    return {top:Math.round(r.top), alto:Math.round(r.height), bottom:Math.round(r.bottom),
      vh:window.innerHeight, holderAlto:h?h.clientHeight:null, holderScrollY:h?h.scrollHeight-h.clientHeight:null,
      cssAlto:getComputedStyle(c).height, overflowY:getComputedStyle(c).overflowY};
  })));
}
await b.close();
