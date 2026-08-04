#!/usr/bin/env node
// Genera memoria/registro-de-trabajo.md: el catálogo del trabajo fechado de docs/superpowers/.
// Empareja cada spec con su plan por slug y agrupa por mes. Escribe SOLO entre marcadores.
// Ver docs/wiki-operacion.md.
import { readFileSync, existsSync, readdirSync, writeFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');
const SALIDA = join(RAIZ, 'memoria/registro-de-trabajo.md');
const INICIO = '<!-- generado:inicio -->';
const FIN = '<!-- generado:fin -->';

const ZONAS = [
  { dir: 'docs/superpowers/specs', tipo: 'spec', archivada: false },
  { dir: 'docs/superpowers/plans', tipo: 'plan', archivada: false },
  { dir: 'docs/archive/superpowers/specs', tipo: 'spec', archivada: true },
  { dir: 'docs/archive/superpowers/plans', tipo: 'plan', archivada: true },
];

// El slug es el nombre sin fecha ni el sufijo `-design` de las specs: es lo que
// hermana una spec con el plan que la ejecutó.
const slugDe = (archivo) => archivo
  .replace(/\.md$/, '')
  .replace(/^\d{4}-\d{2}-\d{2}-/, '')
  .replace(/-design$/, '');

const fechaDe = (archivo) => (archivo.match(/^(\d{4}-\d{2}-\d{2})/) || [])[1] || null;

function tituloDe(ruta) {
  for (const linea of readFileSync(join(RAIZ, ruta), 'utf8').split('\n')) {
    const m = /^#\s+(.+)$/.exec(linea.trim());
    if (m) {
      return m[1]
        .replace(/\s+[—-]\s+(Implementation Plan|Plan de implementación).*$/i, '')
        .replace(/`/g, '')
        .trim();
    }
  }
  return slugDe(ruta.split('/').pop());
}

const trabajos = new Map(); // slug -> { fecha, titulo, spec, plan, archivada }

for (const zona of ZONAS) {
  const abs = join(RAIZ, zona.dir);
  if (!existsSync(abs)) continue;
  for (const archivo of readdirSync(abs)) {
    if (!archivo.endsWith('.md')) continue;
    const fecha = fechaDe(archivo);
    if (!fecha) continue;
    const slug = slugDe(archivo);
    const ruta = `${zona.dir}/${archivo}`;
    const t = trabajos.get(slug) || { fecha, titulo: null, spec: null, plan: null, archivada: true };
    t[zona.tipo] = ruta;
    // La fecha del trabajo es la más temprana: la spec suele preceder al plan.
    if (fecha < t.fecha) t.fecha = fecha;
    // Basta con que una de las dos mitades siga viva para que el trabajo no cuente como archivado.
    if (!zona.archivada) t.archivada = false;
    if (zona.tipo === 'spec' || !t.titulo) t.titulo = tituloDe(ruta);
    trabajos.set(slug, t);
  }
}

const porMes = new Map();
for (const [slug, t] of trabajos) {
  const mes = t.fecha.slice(0, 7);
  if (!porMes.has(mes)) porMes.set(mes, []);
  porMes.get(mes).push({ slug, ...t });
}

const MESES = {
  '01': 'enero', '02': 'febrero', '03': 'marzo', '04': 'abril', '05': 'mayo', '06': 'junio',
  '07': 'julio', '08': 'agosto', '09': 'septiembre', '10': 'octubre', '11': 'noviembre',
  '12': 'diciembre',
};
const nombreMes = (mes) => `${MESES[mes.slice(5, 7)]} de ${mes.slice(0, 4)}`;

const enlace = (ruta) => `[[${ruta.replace(/\.md$/, '')}|${ruta.includes('/specs/') ? 'spec' : 'plan'}]]`;

const lineas = [];
let conPareja = 0;
for (const mes of [...porMes.keys()].sort().reverse()) {
  const items = porMes.get(mes).sort((a, b) => (a.fecha === b.fecha ? a.slug.localeCompare(b.slug) : b.fecha.localeCompare(a.fecha)));
  lineas.push(`### ${nombreMes(mes)}`, '');
  lineas.push('| Trabajo | Documentos | Archivado |', '|---|---|---|');
  for (const t of items) {
    if (t.spec && t.plan) conPareja += 1;
    const docs = [t.spec, t.plan].filter(Boolean).map(enlace).join(' · ');
    lineas.push(`| ${t.titulo} | ${docs} | ${t.archivada ? 'sí' : '—'} |`);
  }
  lineas.push('');
}

const archivados = [...trabajos.values()].filter((t) => t.archivada).length;
const resumen = `${trabajos.size} trabajos · ${conPareja} con spec y plan emparejados · `
  + `${archivados} archivados en \`docs/archive/superpowers/\``;

const generado = [INICIO, '', `_${resumen}. Generado por \`scripts/wiki-registro.mjs\`._`, '', ...lineas, FIN].join('\n');

const previo = existsSync(SALIDA) ? readFileSync(SALIDA, 'utf8') : null;
if (!previo) {
  console.error(`Falta ${SALIDA} con su prosa y sus marcadores. Créalo primero.`);
  process.exit(1);
}
if (!previo.includes(INICIO) || !previo.includes(FIN)) {
  console.error(`${SALIDA} no tiene los marcadores ${INICIO} … ${FIN}.`);
  process.exit(1);
}
const nuevo = previo.replace(new RegExp(`${INICIO}[\\s\\S]*${FIN}`), generado);

if (process.argv.includes('--escribir')) {
  writeFileSync(SALIDA, nuevo);
  console.log(`Escrito ${resumen}.`);
} else {
  console.log(resumen);
  console.log(nuevo === previo ? 'La zona generada está al día.' : 'La zona generada cambiaría: corre con --escribir.');
}
