import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const c=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await c.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
await p.goto(`${BASE}/programa-general`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(2500);
const d=await p.evaluate(()=>{
  const td=document.querySelector('#hot-container .ht_master tbody td');
  const cs=getComputedStyle(td);
  // quien gana el line-height: recorre las hojas buscando reglas que casen
  const reglas=[];
  for (const hoja of document.styleSheets) {
    let rs; try { rs=hoja.cssRules } catch { continue }
    for (const r of rs||[]) {
      if (!r.selectorText || !r.style || !r.style.lineHeight) continue;
      try { if (td.matches(r.selectorText)) reglas.push({sel:r.selectorText.slice(0,70), lh:r.style.lineHeight, hoja:(hoja.href||'').split('/').pop()}); } catch {}
    }
  }
  const filas=[...document.querySelectorAll('#hot-container .ht_master tbody tr')].slice(0,14)
    .map(tr=>Math.round(tr.getBoundingClientRect().height));
  return {lhComputado:cs.lineHeight, fontSize:cs.fontSize, reglas:reglas.slice(-4), altosFila:filas,
    sumaAltos:filas.reduce((a,x)=>a+x,0)};
});
console.log(JSON.stringify(d,null,1));
await b.close();
