import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programacion-intermedia`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(3000);
console.log(JSON.stringify(await p.evaluate(()=>{
  const h=window.PIHotModule.getHotInstance();
  const s=h.getSettings();
  const n=h.countCols();
  const jsW=[]; for(let i=0;i<n;i++) jsW.push(h.getColWidth(i));
  const c=document.querySelector('#hot-container');
  return {n, jsW, sumaJs:jsW.reduce((a,x)=>a+x,0), colWidthsTipo: typeof s.colWidths,
    colWidthsArr: Array.isArray(s.colWidths)? s.colWidths : null,
    stretchH:s.stretchH, contClientW:c.clientWidth, rowHeights:s.rowHeights,
    colsLen: Array.isArray(s.columns)? s.columns.length : null};
}),null,1));
await b.close();
