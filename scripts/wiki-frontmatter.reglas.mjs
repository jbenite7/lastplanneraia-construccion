// Reglas por ruta del backfill de frontmatter (`wiki-frontmatter.mjs`).
// Funciones puras: entra una ruta relativa a la raíz del repo, sale el metadato que le toca.
// Viven aparte del script para poder probarlas sin tocar el disco, igual que
// `wiki-arquitectura.modulos.mjs` separa la declaración de módulos de su generador.

import { AREAS, bloqueFrontmatter, campo } from './wiki-esquema.mjs';

// Los `.md` de la raíz, uno por uno. Son pocos y son los que más importa no adivinar:
// un contrato mal tipado como guía deja de salir en los filtros de «leer antes de tocar».
const RAIZ = {
  'AGENTS.md': { tipo: 'contrato', tags: ['leer-antes-de-tocar'] },
  'CLAUDE.md': { tipo: 'contrato', tags: ['leer-antes-de-tocar'] },
  'GEMINI.md': { tipo: 'contrato', tags: ['leer-antes-de-tocar'] },
  'DESIGN.md': { tipo: 'contrato', tags: ['leer-antes-de-tocar'], areas: ['design-system'] },
  'GLOSARIO.md': { tipo: 'biblia', areas: ['lps'] },
  'PRODUCT.md': { tipo: 'biblia' },
  'ROADMAP.md': { tipo: 'biblia', areas: ['proceso'] },
  'README.md': { tipo: 'guia' },
  'CHANGELOG.md': { tipo: 'reporte', tags: ['generado'] },
};

// Carpeta → área, para lo que el nombre de la ruta ya dice sin ambigüedad.
const AREA_POR_CARPETA = [
  ['docs/design-system/', 'design-system'],
  ['docs/brand/', 'design-system'],
  ['docs/flujos/', 'lps'],
  ['docs/bi/', 'bi'],
  ['docs/reportes/', 'proceso'],
  ['docs/superpowers/', 'proceso'],
  ['docs/archive/', 'proceso'],
  ['goals/', 'proceso'],
  ['decisiones/', 'proceso'],
];

// Palabra en el nombre del archivo → área. Solo términos que en este repo no son ambiguos.
const AREA_POR_PALABRA = [
  [/\bpdc\b|plan-compras/, 'pdc'],
  [/design-system|\bds-\d|token|glass/, 'design-system'],
  [/rbac|permis|rol(es)?\b/, 'rbac'],
  [/docker|compose/, 'docker'],
  [/worktree/, 'worktrees'],
  [/deploy|siteground|publicac/, 'deploy'],
  [/\bbi\b|control-tower|torre/, 'bi'],
  [/global-tables|migrac|backfill|schema|base-de-datos/, 'datos'],
  [/\badmin\b/, 'admin'],
  [/playwright|phpunit|\bqa\b|\btest/, 'qa'],
  [/\blps\b|last-planner|semanal|restriccion|compromiso/, 'lps'],
  [/rutas|routing|arquitectura|router/, 'arquitectura'],
];

/** Tipo que le corresponde a una ruta de la capa fuente. */
export function deducirTipo(rel) {
  const r = rel.replaceAll('\\', '/');
  if (RAIZ[r]) return RAIZ[r].tipo;
  if (/(^|\/)specs\//.test(r)) return 'spec';
  if (/(^|\/)plans\//.test(r)) return 'plan';
  if (/(^|\/)(reports|reportes)\//.test(r)) return 'reporte';
  if (/(^|\/)(evidencia|evidence|runtime-measurements|manifests)\//.test(r)) return 'evidencia';
  if (/^docs\/design-system\/contracts\//.test(r)) return 'contrato';
  // Dentro de un goal el tipo lo dice el nombre del archivo, no la carpeta: un `goals/x/plan.md`
  // es un plan igual que uno de `docs/superpowers/plans/`, y tratarlo como `goal-doc` lo dejaría
  // fuera del filtro por el que se buscan los planes.
  if (/^goals\//.test(r)) {
    if (/\/plan\.md$/.test(r)) return 'plan';
    if (/\/(facts|validation-log)[^/]*\.md$/.test(r)) return 'evidencia';
    return 'goal-doc';
  }
  // Las colas de decisiones del protocolo entre sesiones: son bitácora, no guía.
  if (/^decisiones\//.test(r)) return 'reporte';
  if (/^docs\//.test(r)) return 'guia';
  return 'guia';
}

/** Áreas que le corresponde a una ruta. Puede devolver `[]`: mejor vacío que inventado. */
export function deducirAreas(rel) {
  const r = rel.replaceAll('\\', '/');
  if (RAIZ[r]?.areas) return [...RAIZ[r].areas];

  const encontradas = new Set();
  for (const [prefijo, area] of AREA_POR_CARPETA) if (r.startsWith(prefijo)) encontradas.add(area);
  for (const [patron, area] of AREA_POR_PALABRA) if (patron.test(r.toLowerCase())) encontradas.add(area);

  // El área de carpeta es un cajón de sastre: si el nombre dice algo más preciso, manda el nombre.
  if (encontradas.size > 1) encontradas.delete('proceso');
  return [...encontradas].filter((a) => AREAS.has(a));
}

/** Tags que le corresponde a una ruta, con lo que el propio texto delate. */
export function deducirTags(rel, texto = '') {
  const r = rel.replaceAll('\\', '/');
  const tags = new Set(RAIZ[r]?.tags ?? []);
  if (r.startsWith('docs/archive/')) tags.add('archivo');
  if (r.startsWith('decisiones/')) tags.add('pendiente');
  if (/<!--\s*generado:inicio\s*-->/.test(texto)) tags.add('generado');
  return [...tags];
}

/**
 * Estado inicial. `docs/archive/` es trabajo cerrado por definición; el resto nace `vigente`.
 * Nunca `derogada`: derogar es una afirmación sobre la verdad, y esto solo mira la ruta.
 */
export function deducirEstado(rel) {
  return rel.replaceAll('\\', '/').startsWith('docs/archive/') ? 'cerrado' : 'vigente';
}

/**
 * Resumen de una línea: el primer párrafo de prosa que sigue al `# título`, recortado.
 *
 * Se para en cuanto aparece otro encabezado. Es deliberado y cuesta cobertura: sin ese corte,
 * un archivo cuyo H1 va seguido de un `##` y una lista devolvía un párrafo de mitad del
 * documento como si fuera su resumen —medido en `AGENTS.md`, que así resumía el gate de
 * publicación—. Devuelve `''` cuando no hay párrafo que valga, y el censo lo cuenta como
 * pendiente de mano humana: un resumen equivocado circula por el catálogo como si fuera cierto,
 * y uno vacío se ve.
 */
export function deducirResumen(texto, limite = 160) {
  const cuerpo = texto
    .replace(/^---\r?\n[\s\S]*?\r?\n---\r?\n?/, '')
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/^```[\s\S]*?^```/gm, '');
  const lineas = cuerpo.split('\n');

  let i = 0;
  while (i < lineas.length && !/^#\s/.test(lineas[i])) i++;
  if (i === lineas.length) return '';   // sin H1 no hay de dónde colgar el resumen
  i++;

  const parrafo = [];
  for (; i < lineas.length; i++) {
    const l = lineas[i].trim();
    if (!parrafo.length) {
      if (l === '') continue;
      if (/^#{1,6}\s/.test(l)) return '';            // otro encabezado antes de la prosa
      if (/^([-*+]\s|\d+\.\s|>|\||!\[|\[!)/.test(l)) return '';  // lista, cita, tabla, callout
      parrafo.push(l);
    } else {
      if (l === '') break;
      parrafo.push(l);
    }
  }

  const plano = parrafo.join(' ')
    .replace(/\[\[([^\]|#]+)(?:[|#][^\]]*)?\]\]/g, '$1')
    .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1')
    .replace(/[*_`]/g, '')
    .replace(/\s+/g, ' ')
    .trim();
  if (!plano) return '';
  if (plano.length <= limite) return plano;
  const corte = plano.slice(0, limite);
  return `${corte.slice(0, corte.lastIndexOf(' ')).replace(/[,;:.]$/, '')}…`;
}

/** Fecha del nombre del archivo (`2026-08-18-loquesea.md`), o `''` si no la lleva. */
export function fechaDelNombre(rel) {
  return rel.match(/(?:^|\/)(\d{4}-\d{2}-\d{2})-/)?.[1] ?? '';
}

// ── Cómo se escribe el bloque ────────────────────────────────────────────────────────────────

/** Orden canónico de las claves del esquema dentro del bloque. */
export const ORDEN = ['capa', 'tipo', 'estado', 'fecha', 'areas', 'tags', 'fuente', 'resumen'];

/**
 * Claves del esquema que le faltan a un archivo. `[]` significa que ya está al día.
 * Una clave presente pero vacía cuenta como puesta: rellenarla es trabajo de mano humana, y
 * volver a escribirla en cada pasada haría que el backfill dejara de ser idempotente.
 */
export function faltantes(fm, propuesta) {
  if (fm === null) return ORDEN.filter((k) => k in propuesta);
  return ORDEN.filter((k) => k in propuesta && !campo(fm, k)
    && !new RegExp(`^${k}:\\s*$`, 'm').test(fm));
}

export function render(propuesta, claves) {
  return claves.map((k) => `${k}: ${propuesta[k]}`).join('\n');
}

/**
 * Texto nuevo del archivo: bloque nuevo delante, o claves fusionadas dentro del que ya hay.
 * En la fusión las claves del esquema van **primero** y las ajenas quedan intactas y en su orden.
 */
export function aplicar(texto, propuesta, claves) {
  if (!claves.length) return texto;
  const fm = bloqueFrontmatter(texto);
  if (fm === null) return `---\n${render(propuesta, claves)}\n---\n\n${texto.replace(/^\n+/, '')}`;
  return texto.replace(/^---\r?\n[\s\S]*?\r?\n---/,
    `---\n${render(propuesta, claves)}\n${fm}\n---`);
}
