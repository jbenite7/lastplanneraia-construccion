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
  await p.waitForTimeout(2200);
  const r = await p.evaluate(() => {
    // Sube desde un chip de filtro hasta encontrar quien dibuja marco.
    const chip = document.querySelector('.pg-filter-chip, .pdc-legend-item, .ps-filter-chip, [class*="filter-chip"], [data-filter]');
    if (!chip) return {error:'sin chip de filtro'};
    const cadena=[]; let n=chip;
    for (let i=0;i<7 && n && n!==document.body;i++){
      const cs=getComputedStyle(n);
      const pintaMarco = cs.borderTopWidth!=='0px' || cs.borderTopLeftRadius!=='0px' || (cs.backgroundColor!=='rgba(0, 0, 0, 0)' && cs.backgroundColor!=='transparent');
      cadena.push({ tag:n.tagName.toLowerCase(), cls:String(n.className).slice(0,46),
        borde:cs.borderTopWidth+' '+cs.borderTopStyle, radio:cs.borderTopLeftRadius,
        bg:cs.backgroundColor, marco:pintaMarco });
      n=n.parentElement;
    }
    return {cadena};
  });
  console.log('\n=== '+nom+' ===');
  if (r.error) { console.log('  ', r.error); continue; }
  for (const x of r.cadena) console.log('  ', (x.marco?'MARCO ':'      '), x.cls.padEnd(46), 'borde:'+x.borde.padEnd(12), 'radio:'+x.radio.padEnd(7), x.bg);
}
await b.close();
