// Mide los cuatro radios de cada contenedor de tabla en los tres modulos.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
for (const ruta of ['/programa-general','/programacion-intermedia','/programacion-semanal']) {
  await p.goto(`${BASE}${ruta}`,{waitUntil:'domcontentloaded'});
  await p.waitForTimeout(2500);
  const r = await p.evaluate(() => {
    const sel = ['#hot-container','.aia-grid-shell','.ht_master','.wtHolder','#cuadroTabla','.ps-table-wrap','.pi-grid-shell','.pg-grid-shell','.aia-card'];
    const out=[];
    for (const s of sel) {
      for (const el of [...document.querySelectorAll(s)].slice(0,2)) {
        const cs=getComputedStyle(el);
        const cuatro=[cs.borderTopLeftRadius,cs.borderTopRightRadius,cs.borderBottomRightRadius,cs.borderBottomLeftRadius];
        if (cuatro.every(v=>v==='0px')) continue;
        out.push({ sel:s, clases:el.className.slice(0,42), radios:cuatro.join(' | '),
          homogeneo: new Set(cuatro).size===1, overflow:cs.overflow });
      }
    }
    return out;
  });
  console.log('\n=== ' + ruta + ' ===');
  for (const x of r) console.log((x.homogeneo?'ok ':'NO '), x.sel.padEnd(16), x.radios.padEnd(34), 'overflow:'+x.overflow);
}
await b.close();
