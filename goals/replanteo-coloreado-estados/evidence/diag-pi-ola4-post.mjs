// Verificacion posterior de la ola 4 en PI: alto de fila, celdas cortadas,
// respiro y desbordamiento vertical de la pagina.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programacion-intermedia`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(3500);
const d = await p.evaluate(()=>{
  const q=s=>document.querySelector(s);
  const tds=[...document.querySelectorAll('#hot-container .ht_master tbody tr td')];
  const cortadas=tds.filter(td=>td.scrollHeight>td.clientHeight+1);
  const ths=[...document.querySelectorAll('#hot-container .ht_master thead th')];
  const thCortadas=ths.filter(t=>t.scrollWidth>t.clientWidth+1).map(t=>t.textContent.trim());
  const altos=[...new Set(tds.map(td=>Math.round(td.getBoundingClientRect().height)))].sort((a,b)=>a-b);
  const holder=q('#hot-container .ht_master .wtHolder');
  const enc=q('#encabezado'), tb=q('.aia-toolbar'), cont=q('#hot-container');
  return {
    altosFila:altos, totalCeldas:tds.length, celdasCortadas:cortadas.length,
    muestraCortada: cortadas.slice(0,3).map(td=>td.textContent.trim().slice(0,40)),
    cabecerasCortadas:thCortadas,
    thAlto: ths[0]?Math.round(ths[0].getBoundingClientRect().height):null,
    respiro: (enc&&tb)?Math.round(tb.getBoundingClientRect().top-enc.getBoundingClientRect().bottom):null,
    desbordeX: holder? holder.scrollWidth-holder.clientWidth : null,
    scrollXPagina: document.documentElement.scrollWidth-document.documentElement.clientWidth,
    scrollYPagina: document.documentElement.scrollHeight-document.documentElement.clientHeight,
    contBottom: cont?Math.round(cont.getBoundingClientRect().bottom):null,
    viewportH: window.innerHeight,
    filasVisibles: document.querySelectorAll('#hot-container .ht_master tbody tr').length,
  };
});
console.log(JSON.stringify(d,null,1));
await p.screenshot({path:'goals/replanteo-coloreado-estados/evidence/pi-ola4-1180x820-dark.png'});
await b.close();
