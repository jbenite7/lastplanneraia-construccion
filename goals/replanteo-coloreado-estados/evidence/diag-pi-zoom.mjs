import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programacion-intermedia`,{waitUntil:'domcontentloaded'}); await p.waitForTimeout(3000);
console.log(JSON.stringify(await p.evaluate(()=>({
  zoomStyle: document.documentElement.style.zoom, zoomComputed: getComputedStyle(document.documentElement).zoom,
  vvH: window.visualViewport && window.visualViewport.height, innerH: window.innerHeight,
  docClientH: document.documentElement.clientHeight,
  bodyScrollH: document.body.scrollHeight,
  contCss: document.querySelector('#hot-container').style.height,
  ultimaFilaBottom: (()=>{const rs=document.querySelectorAll('#hot-container .ht_master tbody tr'); const l=rs[rs.length-1]; return l?Math.round(l.getBoundingClientRect().bottom):null;})(),
}))));
await b.close();
