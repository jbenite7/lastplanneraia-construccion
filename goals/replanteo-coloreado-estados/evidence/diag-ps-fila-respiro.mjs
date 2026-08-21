// Diagnostico PS (ola 4): de donde sale el alto de fila y cuanto respiro hay
// entre la barra superior y el cajon de acciones, comparado contra PG.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
for (const [ruta,nom] of [['/programa-general','PG'],['/programacion-semanal','PS']]) {
  await p.goto(`${BASE}${ruta}`,{waitUntil:'domcontentloaded'});
  await p.waitForTimeout(3000);
  const r = await p.evaluate(() => {
    const out={};
    // 1) alto de fila real
    const tr = document.querySelector('#hot-container .ht_master tbody tr:nth-child(2)');
    const td = tr && tr.querySelector('td');
    if (td) {
      const cs=getComputedStyle(td);
      out.fila = { trH: tr.getBoundingClientRect().height, tdH: td.getBoundingClientRect().height,
        pad: cs.paddingTop+'/'+cs.paddingBottom, lh: cs.lineHeight, fs: cs.fontSize,
        trStyleH: tr.style.height||'(sin inline)', tdStyleH: td.style.height||'(sin inline)' };
    }
    out.tokenRowH = getComputedStyle(document.documentElement).getPropertyValue('--ds-table-row-h').trim();
    // 2) respiro: rect de la barra superior (topbar del shell) vs la toolbar
    const bar = document.querySelector('.aia-topbar, .aia-shell__topbar, header.aia-topbar, .aia-appbar');
    const tb  = document.querySelector('.aia-toolbar');
    const main= document.querySelector('main');
    const dir = document.querySelector('.pg-direction-row, .ps-direction-row');
    if (tb) { const cs=getComputedStyle(tb);
      out.toolbar={ top: tb.getBoundingClientRect().top, mt: cs.marginTop, cls: tb.className }; }
    if (bar) out.topbar={ bottom: bar.getBoundingClientRect().bottom, cls: bar.className };
    if (main){ const cs=getComputedStyle(main);
      out.main={ top: main.getBoundingClientRect().top, pt: cs.paddingTop, cls: main.className }; }
    if (dir) { const cs=getComputedStyle(dir); const r=dir.getBoundingClientRect();
      out.direction={ top:r.top, h:r.height, mb:cs.marginBottom, pb:cs.paddingBottom, cls:dir.className }; }
    return out;
  });
  console.log('\n=== '+nom+' ===');
  console.log(JSON.stringify(r,null,2));
}
await b.close();
