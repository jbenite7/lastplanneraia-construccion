// Prueba de que el alto del contrato es SUELO y no TECHO en PI: se inyecta texto
// largo en una celda SOLO en el DOM (sin tocar el dato ni disparar guardado) y se
// mide si la fila crece en vez de recortar. Se revierte al terminar.
import { chromium } from 'playwright';
const BASE='http://localhost:8081';
const b=await chromium.launch();
const p=await(await b.newContext({viewport:{width:1180,height:820}})).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card=p.locator('.project-item').filter({has:p.getByRole('heading',{name:'Da Porto',exact:true})});
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u=>!u.toString().includes('/proyectos'),{timeout:45000});
await p.goto(`${BASE}/programacion-intermedia`,{waitUntil:'domcontentloaded'}); await p.waitForTimeout(3000);
console.log(JSON.stringify(await p.evaluate(()=>{
  const tr=document.querySelector('#hot-container .ht_master tbody tr');
  const td=tr.querySelectorAll('td')[16]; // Observaciones, la que envuelve
  const antes={fila:Math.round(tr.getBoundingClientRect().height), celda:Math.round(td.getBoundingClientRect().height)};
  const original=td.innerHTML;
  td.textContent='Texto largo de prueba para comprobar que la fila crece cuando el contenido lo pide y no se recorta en silencio, tal como exige la regla del frente.';
  const desp={fila:Math.round(tr.getBoundingClientRect().height), celda:Math.round(td.getBoundingClientRect().height),
    cortada: td.scrollHeight>td.clientHeight+1};
  td.innerHTML=original;
  return {antes, desp, revertido:Math.round(tr.getBoundingClientRect().height)};
}),null,1));
await b.close();
