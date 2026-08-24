import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820},deviceScaleFactor:2})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
await p.goto(`${BASE}/programa-general`,{waitUntil:'domcontentloaded'});
await p.waitForSelector('#hot-container .ops-state-chip',{timeout:45000});
const chip=p.locator('#hot-container .ops-state-chip').first();
await chip.hover();
await p.waitForTimeout(700);
const diag=await p.evaluate(()=>{
  const tip=document.querySelector('.aia-state-tip');
  const panel=document.querySelector('.aia-state-tip-panel');
  if(!tip) return {error:'sin tooltip en DOM'};
  const cs=getComputedStyle(tip), cp=panel?getComputedStyle(panel):null;
  return {
    tipDisplay:cs.display, tipVisibility:cs.visibility, tipOpacity:cs.opacity,
    tipPosition:cs.position, tipBg:cs.backgroundColor, tipZ:cs.zIndex,
    tipRect:tip.getBoundingClientRect().toJSON(),
    abierto: tip.matches(':popover-open'),
    popoverAttr: tip.getAttribute('popover'),
    panelBg: cp?cp.backgroundColor:null, panelColor: cp?cp.color:null,
    panelBorder: cp?cp.border:null, panelShadow: cp?cp.boxShadow.slice(0,40):null,
    panelRect: panel?panel.getBoundingClientRect().toJSON():null,
  };
});
console.log(JSON.stringify(diag,null,1));
const caja=await chip.boundingBox();
if(caja) await p.screenshot({path:'tooltip-opaco-2x.png',clip:{x:Math.max(0,caja.x-320),y:Math.max(0,caja.y-30),width:700,height:300}});
await b.close();
