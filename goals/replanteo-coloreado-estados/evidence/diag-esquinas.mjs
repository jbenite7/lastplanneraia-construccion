import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820},deviceScaleFactor:3})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.evaluate(()=>localStorage.setItem('aia-theme','dark'));
await p.goto(`${BASE}/programa-general`,{waitUntil:'domcontentloaded'});
await p.waitForTimeout(2500);
const caja = await p.locator('#hot-container').first().boundingBox();
if (!caja) { console.log('sin contenedor'); await b.close(); process.exit(1); }
const L=70;
const esquinas = {
  'sup-izq': {x:caja.x, y:caja.y},
  'sup-der': {x:caja.x+caja.width-L, y:caja.y},
  'inf-izq': {x:caja.x, y:caja.y+caja.height-L},
  'inf-der': {x:caja.x+caja.width-L, y:caja.y+caja.height-L},
};
for (const [nombre,pos] of Object.entries(esquinas)) {
  await p.screenshot({path:`esquina-${nombre}.png`, clip:{x:Math.max(0,pos.x), y:Math.max(0,pos.y), width:L, height:L}});
}
console.log('caja tabla:', JSON.stringify(caja));
await b.close();
