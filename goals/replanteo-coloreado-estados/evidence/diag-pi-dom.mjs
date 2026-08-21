import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
for (const [r,n] of [['/programa-general','PG'],['/programacion-intermedia','PI']]) {
await p.goto(`${BASE}${r}`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(2500);
console.log('\n=== '+n+' ===', JSON.stringify(await p.evaluate(()=>{
  const tb=document.querySelector('.aia-toolbar');
  const cad=[]; let e=tb;
  while(e && e!==document.documentElement){ const cs=getComputedStyle(e);
    cad.push({tag:e.tagName.toLowerCase(), id:e.id||null, cls:e.className||null, pt:cs.paddingTop, mt:cs.marginTop, top:Math.round(e.getBoundingClientRect().top)}); e=e.parentElement; }
  const enc=document.querySelector('#encabezado');
  // cabeceras cortadas
  const ths=[...document.querySelectorAll('#hot-container .ht_master thead th')];
  const cortadas=ths.filter(t=>{const s=t.querySelector('span,div')||t; return t.scrollWidth>t.clientWidth+1;}).map(t=>t.textContent.trim());
  return {cadena:cad, encBottom: enc?Math.round(enc.getBoundingClientRect().bottom):null,
    toolbarTop: tb?Math.round(tb.getBoundingClientRect().top):null, cabecerasCortadas:cortadas};
}),null,1));
}
await b.close();
