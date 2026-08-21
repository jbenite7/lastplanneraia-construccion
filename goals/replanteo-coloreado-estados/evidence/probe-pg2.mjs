import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programa-general`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(3500);
console.log(JSON.stringify(await p.evaluate(()=>{
  const trs=[...document.querySelectorAll('#hot-container .ht_master tbody tr')];
  let recortadas=[];
  trs.forEach((tr,i)=>{ [...tr.children].forEach(td=>{ if(td.scrollHeight>td.clientHeight+1) recortadas.push({i,txt:(td.textContent||'').slice(0,25),sh:td.scrollHeight,ch:td.clientHeight}); }); });
  const inst=window.PGHotModule&&window.PGHotModule.getHotInstance&&window.PGHotModule.getHotInstance();
  return {n:trs.length, altos:trs.map(tr=>Math.round(tr.getBoundingClientRect().height)),
    setting:inst?String(inst.getSettings().rowHeights):null, recortadas:recortadas.slice(0,8), nRecortadas:recortadas.length};
}),null,1));
await b.close();
