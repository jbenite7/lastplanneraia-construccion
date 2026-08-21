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
await p.waitForTimeout(2600);
const d=await p.evaluate(()=>{
  const hex=(s)=>{const m=(s||'').match(/\d+/g); return m? '#'+m.slice(0,3).map(n=>(+n).toString(16).padStart(2,'0')).join('') : s;};
  // Chips de filtro de la leyenda
  const filtros=[...document.querySelectorAll('#pgLegend .pg-filter-chip, .pg-filter-chip')].map(ch=>{
    const punto=ch.querySelector('span:first-child, .pg-filter-dot, [class*="dot"]');
    const cont=ch.querySelector('.pg-filter-count, [class*="count"], b, strong, span:last-child');
    const cs=getComputedStyle(ch);
    return { txt:(ch.textContent||'').trim().replace(/\s+/g,' ').slice(0,24),
      chipBg:hex(cs.backgroundColor), chipColor:hex(cs.color),
      puntoBg: punto? hex(getComputedStyle(punto).backgroundColor):null,
      contBg: cont? hex(getComputedStyle(cont).backgroundColor):null,
      contColor: cont? hex(getComputedStyle(cont).color):null };
  });
  // Chips de estado en la tabla
  const estados={};
  for (const ch of document.querySelectorAll('#hot-container .ops-state-chip')) {
    const t=(ch.textContent||'').trim().split('\n')[0].slice(0,24);
    if (!estados[t]) estados[t]={bg:hex(getComputedStyle(ch).backgroundColor), color:hex(getComputedStyle(ch).color)};
  }
  return {filtros, estados};
});
console.log('FILTRO (leyenda)                       chip      punto     contador');
for (const f of d.filtros) console.log(f.txt.padEnd(26), (f.chipBg||'-').padEnd(9), (f.puntoBg||'-').padEnd(9), (f.contBg||'-'));
console.log('\nESTADO (tabla)              chip solido');
for (const [k,v] of Object.entries(d.estados)) console.log(k.padEnd(26), v.bg);
await b.close();
