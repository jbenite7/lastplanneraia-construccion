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
  await p.waitForTimeout(3500);
  const d=await p.evaluate(()=>{
    const tb=document.querySelector('.aia-toolbar');
    const r=(e)=>{const x=e.getBoundingClientRect();return {t:+x.top.toFixed(1),b:+x.bottom.toFixed(1),h:+x.height.toFixed(1)};};
    let prev=null, hueco=null, padreInfo=null;
    if(tb){
      let s=tb.previousElementSibling;
      while(s && s.getBoundingClientRect().height===0) s=s.previousElementSibling;
      if(s){prev={tag:s.tagName,cls:s.className.toString().slice(0,50),...r(s)}; hueco=+(tb.getBoundingClientRect().top - s.getBoundingClientRect().bottom).toFixed(1);}
      const pa=tb.parentElement;
      padreInfo={cls:pa.className.toString().slice(0,50), padTop:getComputedStyle(pa).paddingTop, ...r(pa)};
    }
    const cont=[...document.querySelectorAll('.handsontable')].map(h=>({id:h.id||h.parentElement.id||'', cls:h.className.slice(0,40)}));
    const tds=document.querySelectorAll('.handsontable .ht_master tbody td').length;
    return {toolbar: tb?{...r(tb), mt:getComputedStyle(tb).marginTop, cls:tb.className.toString().slice(0,50)}:null,
      prevHermano:prev, huecoPrev:hueco, padre:padreInfo, tablasHot:cont.slice(0,4), tdsHot:tds};
  });
  console.log('\n== '+nom+' ==\n'+JSON.stringify(d,null,1));
}
await b.close();
