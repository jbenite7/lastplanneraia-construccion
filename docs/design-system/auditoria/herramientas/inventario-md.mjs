// Genera inventario.md desde hallazgos.json. La cascada nunca se escribe a mano:
// una tabla copiada del JSON se desincroniza en cuanto se anade un hallazgo.
import { readFileSync } from 'node:fs';
const d = JSON.parse(readFileSync(new URL('../hallazgos.json', import.meta.url), 'utf8'));
const ORDEN = ['critico', 'mayor', 'menor', 'cosmetico', 'sin-problema'];
const TITULO = {
  critico: 'Crítico', mayor: 'Mayor', menor: 'Menor',
  cosmetico: 'Cosmético', 'sin-problema': 'Sin problema',
};
const GLOSA = {
  critico: 'Rompe una regla innegociable en superficie `pilot`, deja al sistema sin poder detectarlo, o rompe accesibilidad nivel A.',
  mayor: 'Rompe una regla innegociable fuera de `pilot`, o es deuda estructural que impide migrar.',
  menor: 'Desviación local con equivalente ya existente, o accesibilidad AA de geometría.',
  cosmetico: 'Inconsistencia sin efecto funcional ni de contraste.',
  'sin-problema': 'Medido y conforme. Se registra a propósito: el inventario también dice dónde NO hay deuda.',
};
const L = [];
L.push('# Inventario por severidad', '');
L.push('**Generado por `herramientas/inventario-md.mjs` desde `hallazgos.json`. No se edita a mano.**');
L.push('La regla con la que se clasifica está en `escala-severidad.md`; cada entrada lleva su `porQue`');
L.push('en el JSON, que es lo que permite reinterpretarla si DS-F1 fija otra escala.', '');
const cuenta = Object.fromEntries(ORDEN.map((s) => [s, d.hallazgos.filter((h) => h.severidad === s).length]));
L.push('| Severidad | Hallazgos |', '|---|---:|');
for (const s of ORDEN) L.push(`| ${TITULO[s]} | ${cuenta[s]} |`);
L.push(`| **Total** | **${d.hallazgos.length}** |`, '');
const bloq = d.hallazgos.filter((h) => h.bloqueadoPor);
const est = d.hallazgos.filter((h) => h.estimado);
const semilla = d.hallazgos.filter((h) => h.origen && h.origen !== 'DS-F0');
L.push(`**${bloq.length}** esperan al frente \`runtime-budgets-al-ci\` para poder medirse; **${est.length}** llevan severidad estimada`);
L.push(`y lo dicen. **${semilla.length}** vienen de la semilla, con su identificador original intacto.`, '');
for (const s of ORDEN) {
  const hs = d.hallazgos.filter((h) => h.severidad === s);
  if (!hs.length) continue;
  L.push(`## ${TITULO[s]} — ${hs.length}`, '', `*${GLOSA[s]}*`, '');
  L.push('| id | Módulo | Hallazgo | Dónde | Origen |', '|---|---|---|---|---|');
  for (const h of hs) {
    const u = h.ubicaciones[0];
    const donde = `\`${u.archivo}${u.linea ? ':' + u.linea : ''}\`` + (h.ubicaciones.length > 1 ? ` +${h.ubicaciones.length - 1}` : '');
    const marca = (h.bloqueadoPor ? ' ⏳' : '') + (h.estimado ? ' ≈' : '');
    L.push(`| \`${h.id}\` | ${h.modulo} | ${h.titulo}${marca} | ${donde} | ${h.origen || '—'} |`);
  }
  L.push('');
}
L.push('⏳ = no medible hasta que `runtime-budgets-al-ci` deje un carril de referencia sano.');
L.push('≈ = severidad estimada, no medida.');
console.log(L.join('\n'));
