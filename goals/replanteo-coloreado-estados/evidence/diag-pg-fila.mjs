import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
await p.goto(`${BASE}/programa-general`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(3000);
const d = await p.evaluate(() => {
  const q=(s)=>document.querySelector(s);
  const enc=q('#encabezado'), main=q('#contenido'), dir=q('.pg-direction-row'), tb=q('.aia-toolbar.pg-toolbar');
  const r=(el)=>el?el.getBoundingClientRect():null;
  const cs=(el)=>el?getComputedStyle(el):null;
  const filas=[...document.querySelectorAll('#hot-container .ht_master tbody tr')].slice(0,40);
  const altos=filas.map(tr=>Math.round(tr.getBoundingClientRect().height));
  // recorte: comparo alto de scroll del contenido interno con el visible
  let recortadas=0, ejemplos=[];
  for (const tr of filas) for (const td of tr.children) {
    const inner=td.firstElementChild||td;
    if (inner.scrollHeight > inner.clientHeight+1 || td.scrollHeight > td.clientHeight+1) {
      recortadas++; if(ejemplos.length<5) ejemplos.push({txt:(td.textContent||'').slice(0,30), sh:td.scrollHeight, ch:td.clientHeight});
    }
  }
  const td0=q('#hot-container .ht_master tbody td');
  return {
    tokenRowH: getComputedStyle(document.documentElement).getPropertyValue('--ds-table-row-h').trim(),
    altos: [...new Set(altos)], nFilas: filas.length, recortadas, ejemplos,
    tdAlto: td0?Math.round(td0.getBoundingClientRect().height):null,
    thAlto: (()=>{const th=q('#hot-container .ht_master thead th');return th?Math.round(th.getBoundingClientRect().height):null;})(),
    respiro: {
      encabezadoBottom: r(enc)?Math.round(r(enc).bottom):null,
      mainTop: r(main)?Math.round(r(main).top):null,
      mainPaddingTop: cs(main)?cs(main).paddingTop:null,
      dsPagePadding: getComputedStyle(document.documentElement).getPropertyValue('--ds-page-padding').trim(),
      dirTop: r(dir)?Math.round(r(dir).top):null,
      dirHeight: r(dir)?Math.round(r(dir).height):null,
      dirMarginBottomHijo: (()=>{const c=dir&&dir.querySelector('.mb-1');return c?getComputedStyle(c).marginBottom:null;})(),
      toolbarTop: r(tb)?Math.round(r(tb).top):null,
      toolbarMarginTop: cs(tb)?cs(tb).marginTop:null,
      huecoEncabezadoToolbar: (r(enc)&&r(tb))?Math.round(r(tb).top-r(enc).bottom):null
    }
  };
});
console.log(JSON.stringify(d,null,1));
await b.close();
