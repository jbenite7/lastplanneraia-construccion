import { chromium } from 'playwright';
const PROP = {
  'Ejecución con restricciones': ['Con restricciones','En ejecución RC','Ejecutando RC'],
  'Condiciones Pendientes': ['Condiciones','Por habilitar'],
  'Trabajo No Planificado': ['No planificado','TNP'],
  'RC con restricciones': ['RC restringida','RC pendiente'],
  'Lista para Confirmar': ['Por confirmar','Lista'],
  'Listo para Comprometer': ['Por comprometer','Comprometible'],
  'En Ejecución Pendiente': ['Ejecución pendiente','Ejecutando RC','En ejecución'],
  'Alistamiento Pendiente': ['Alistamiento','Pendiente'],
  'Alistamiento en Riesgo': ['En riesgo','Riesgo'],
  'Alistamiento Urgente': ['Urgente','Alistar ya'],
};
const b = await chromium.launch();
const p = await (await b.newContext()).newPage();
await p.goto('about:blank');
await p.addStyleTag({ content: `@font-face{font-family:"Inter";font-weight:100 900;src:url("file://${process.cwd()}/public/vendor/fonts/aia/inter-latin-v20.woff2") format("woff2");}` });
await p.evaluate(() => document.fonts.ready);
const w = async (ts) => p.evaluate((ts) => { const c = document.createElement('canvas').getContext('2d'); c.font='600 11.52px Inter'; return ts.map((t)=>Math.ceil(c.measureText(t).width)); }, ts);
for (const [largo, opciones] of Object.entries(PROP)) {
  const anchos = await w(opciones);
  console.log(`${largo}`);
  opciones.forEach((o,i)=>console.log(`   ${anchos[i]<=112?'ok':'NO'} ${String(anchos[i]).padStart(3)}px  ${o}`));
}
await b.close();
