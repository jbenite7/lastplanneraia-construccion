import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
for (const [ruta,nom] of [['/programa-general','PG'],['/programacion-intermedia','PI'],['/programacion-semanal','PS']]) {
  await p.goto(`${BASE}${ruta}`,{waitUntil:'domcontentloaded'});
  await p.waitForTimeout(2500);
  const d = await p.evaluate(() => {
    const q=(s)=>document.querySelector(s);
    const cs=(el,pr)=>el?getComputedStyle(el)[pr]:null;
    const tb=q('.aia-toolbar');
    const nav=q('.aia-navbar, .navbar, header, .aia-page-header');
    // 1) overflow real
    const cont=q('#hot-container'), holder=q('#hot-container .wtHolder');
    const ov = holder ? {scrollW:holder.scrollWidth, clientW:holder.clientWidth, desbordaX:holder.scrollWidth>holder.clientWidth+2} : null;
    // 2) matiz por columna: fondo de las celdas de la primera fila
    const celdas=[...document.querySelectorAll('#hot-container .ht_master tbody tr:first-child td')].slice(0,10)
      .map(td=>getComputedStyle(td).backgroundColor);
    const distintos=[...new Set(celdas)];
    // 3) separacion navbar -> toolbar
    const rt=tb?tb.getBoundingClientRect():null, rn=nav?nav.getBoundingClientRect():null;
    const hueco = (rt&&rn)? Math.round(rt.top-rn.bottom) : null;
    // 4) tipografia y alto de fila
    const th=q('#hot-container .ht_master thead th'), td=q('#hot-container .ht_master tbody td');
    return { ov, celdasFondos:celdas.length, maticesDistintos:distintos.length, muestras:distintos.slice(0,5),
      hueco, marginTopToolbar:cs(tb,'marginTop'), navSel: nav?nav.className.slice(0,30):null,
      thFont:cs(th,'fontSize'), tdFont:cs(td,'fontSize'),
      thAlto: th?Math.round(th.getBoundingClientRect().height):null,
      tdAlto: td?Math.round(td.getBoundingClientRect().height):null };
  });
  console.log('\n=== '+nom+' ===', JSON.stringify(d,null,1));
}
await b.close();
