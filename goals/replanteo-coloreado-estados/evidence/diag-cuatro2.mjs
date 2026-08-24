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
    const cs=(el,pr)=>el?getComputedStyle(el)[pr]:null;
    // Matiz por COLUMNA: recorre la 3a fila y agrupa por clase de celda
    const fila=[...document.querySelectorAll('#hot-container .ht_master tbody tr')][2];
    const porCol = fila ? [...fila.querySelectorAll('td')].slice(0,12).map((td,i)=>({
      i, bg:getComputedStyle(td).backgroundColor,
      cls:(td.className.match(/pg-cell-\w+|pi-cell-\w+|ps-cell-\w+/)||[''])[0] })) : [];
    const distintos=[...new Set(porCol.map(c=>c.bg))];
    // Hueco real: la barra superior de la pagina (breadcrumb / topbar)
    const top=document.querySelector('.aia-topbar, .aia-breadcrumb, .aia-page-header, .breadcrumb, main > header, .aia-context-bar');
    const tb=document.querySelector('.aia-toolbar');
    const hueco = (top&&tb) ? Math.round(tb.getBoundingClientRect().top - top.getBoundingClientRect().bottom) : null;
    // Alto de fila declarado por Handsontable
    const td=document.querySelector('#hot-container .ht_master tbody td');
    return { porCol, coloresDistintos:distintos.length, distintos:distintos.slice(0,4),
      topSel: top?(top.className||top.tagName).slice(0,32):null, hueco,
      tdPadding:cs(td,'padding'), tdLineHeight:cs(td,'lineHeight'),
      tdAlto: td?Math.round(td.getBoundingClientRect().height):null };
  });
  console.log('\n=== '+nom+' ===');
  console.log('  columnas:', d.coloresDistintos, 'colores distintos ->', JSON.stringify(d.distintos));
  console.log('  clases por col:', d.porCol.map(c=>c.cls||'-').join(','));
  console.log('  topbar:', d.topSel, '| hueco:', d.hueco);
  console.log('  celda: alto', d.tdAlto, 'padding', d.tdPadding, 'lh', d.tdLineHeight);
}
await b.close();
