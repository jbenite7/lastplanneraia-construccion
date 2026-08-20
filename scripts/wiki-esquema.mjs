// Esquema v2 del vault: vocabularios cerrados, deducción por ruta y lectura de frontmatter.
// Funciones puras, sin E/S ni impresión: las consumen `wiki-lint.mjs` y `wiki-frontmatter.mjs`,
// y las prueba `tests/wiki/esquema.test.mjs`. Un solo sitio para el vocabulario evita que el
// lint y el backfill discrepen sobre qué es válido.

// Las trece áreas. No cambian en v2.
export const AREAS = new Set(['design-system', 'qa', 'docker', 'worktrees', 'pdc',
  'lps', 'datos', 'rbac', 'deploy', 'bi', 'admin', 'proceso', 'arquitectura']);

// Los nueve tipos de la wiki, tal como estaban.
export const TIPOS_WIKI = new Set(['decision', 'trampa', 'mapa', 'goal', 'concepto', 'referencia',
  'log', 'modulo', 'flujo']);

// Los ocho tipos que v2 añade para la capa de fuentes.
export const TIPOS_FUENTE = new Set(['contrato', 'spec', 'plan', 'reporte', 'evidencia', 'biblia',
  'guia', 'goal-doc']);

export const TIPOS = new Set([...TIPOS_WIKI, ...TIPOS_FUENTE]);
export const ESTADOS = new Set(['vigente', 'derogada', 'abierto', 'cerrado']);

// Vocabulario cerrado de `tags`: transversales, no duplican `tipo` ni `areas`.
//
// `moc` salió el 2026-08-19. La spec de v2 lo traía para marcar los MOCs de área, y sobra desde
// que `tipo: mapa` SIGNIFICA MOC: un mapa de área tiene estructura propia y fija («Qué manda»,
// «Trampas», «Vecinos»), así que es una clase de página, y las clases viven en `tipo`. El tag
// habría existido solo para parchear que una página estaba mal tipada.
//
// `proyecto` entró el 2026-08-19 con la wiki LLM del proyecto (los cinco archivos de la raíz).
// La skill `llm-wiki` pide `tags: [proyecto, <dominio>]`, y esa clave ya existía aquí con otro
// vocabulario: dos esquemas pidiendo mandar sobre la misma clave. **No se abrió la lista para
// meter dominios**: el dominio lo lleva `areas`, que es el eje cerrado que v2 ya tenía para eso.
// Solo entra la marca transversal, que es lo único que `areas` no puede decir — «este archivo es
// uno de los cinco de la wiki del proyecto».
export const TAGS = new Set(['dashboard', 'plantilla', 'pendiente', 'trampa',
  'leer-antes-de-tocar', 'generado', 'archivo', 'proyecto']);

export const CAPAS = new Set(['fuente', 'wiki', 'esquema']);

// El único archivo de la capa esquema: el manual que describe a las otras dos.
export const RUTA_ESQUEMA = 'docs/wiki-operacion.md';

/** Capa que le corresponde a una ruta relativa a la raíz del repo. */
export function deducirCapa(rel) {
  const r = rel.replaceAll('\\', '/');
  if (r === RUTA_ESQUEMA) return 'esquema';
  if (r === 'memoria' || r.startsWith('memoria/')) return 'wiki';
  return 'fuente';
}

/**
 * Lee el bloque de frontmatter de un texto markdown.
 * Devuelve `null` si no hay bloque; si lo hay, el texto crudo entre los `---`.
 * Solo cuenta el bloque que abre en la primera línea: un `---` a media página es una regla
 * horizontal, no metadato.
 */
export function bloqueFrontmatter(texto) {
  const m = texto.match(/^---\r?\n([\s\S]*?)\r?\n---(?:\r?\n|$)/);
  return m ? m[1] : null;
}

/** Valor escalar de un campo del frontmatter, o `undefined` si no está o está vacío. */
export function campo(fm, clave) {
  // `[^\\S\\n]*` y no `\\s*`: `\\s` incluye el salto de línea, así que con `\\s*(.*)$` un campo
  // vacío se comía su propio salto y devolvía como valor **la línea siguiente**. Medido el
  // 2026-08-19 sobre trece archivos con `fecha:` vacío, que el lint reportó como
  // «fecha no ISO: areas: [design-system]». El defecto venía heredado del lint v1.
  const v = fm.match(new RegExp(`^${clave}:[^\\S\\n]*(.*)$`, 'm'))?.[1]?.trim();
  return v ? v : undefined;
}

/**
 * Lista de un campo del frontmatter, en cualquiera de las dos formas de YAML:
 * `areas: [a, b]` o el bloque con guiones. Devuelve `[]` si el campo no está.
 * Quita comillas de los elementos: `tags: ["moc"]` y `tags: [moc]` son el mismo dato.
 */
export function lista(fm, clave) {
  const inline = fm.match(new RegExp(`^${clave}:\\s*\\[(.*)\\]\\s*$`, 'm'))?.[1];
  const crudos = inline !== undefined
    ? inline.split(',')
    : [...(fm.match(new RegExp(`^${clave}:\\s*\\n((?:^[ \\t]*-[ \\t]*.+\\n?)+)`, 'm'))?.[1] ?? '')
        .matchAll(/^[ \t]*-[ \t]*(.+)$/gm)].map((m) => m[1]);
  return crudos.map((s) => s.trim().replace(/^["']|["']$/g, '')).filter(Boolean);
}

/**
 * Comprueba el frontmatter de un archivo y devuelve los hallazgos como `{campo, detalle}`.
 * Vale para las tres capas: la diferencia entre ellas no está aquí, sino en qué campos exige
 * quien llama (`obligatorios`). El cuerpo del archivo no se mira nunca.
 */
export function revisarFrontmatter(fm, { rel, obligatorios = [], molde = false } = {}) {
  const fallos = [];
  const anota = (c, d) => fallos.push({ campo: c, detalle: d });

  // Un molde (una plantilla) se mide distinto: sus campos son huecos por definición, así que no
  // se le exige ninguno, ni que `fecha` sea una fecha —lleva el marcador `{{date:…}}` que
  // rellena Obsidian—, ni que su `capa` case con dónde vive: la plantilla de una spec declara
  // `capa: fuente` porque eso es lo que valdrá el documento que salga de ella, y la plantilla
  // misma vive en `memoria/`. Lo que sí se le comprueba es el vocabulario: un `tipo` inventado
  // en un molde se copiaría a cada página que salga de él.
  if (!molde) {
    for (const k of obligatorios) {
      if (!campo(fm, k) && lista(fm, k).length === 0) anota(k, `falta o está vacío: ${k}`);
    }
  }

  const tipo = campo(fm, 'tipo');
  if (tipo && !TIPOS.has(tipo)) anota('tipo', `tipo desconocido: ${tipo}`);

  const estado = campo(fm, 'estado');
  if (estado && !ESTADOS.has(estado)) anota('estado', `estado desconocido: ${estado}`);

  const fecha = campo(fm, 'fecha');
  if (!molde && fecha && !/^\d{4}-\d{2}-\d{2}$/.test(fecha)) anota('fecha', `fecha no ISO: ${fecha}`);

  // `capa` es opcional mientras el backfill no haya pasado, pero si está tiene que ser una de las
  // tres y tiene que coincidir con la que la ruta implica: una wiki declarándose `fuente` haría
  // que el lint dejara de mirarle el cuerpo, que es justo lo que no debe pasar.
  const capa = campo(fm, 'capa');
  if (capa) {
    if (!CAPAS.has(capa)) anota('capa', `capa desconocida: ${capa}`);
    else if (!molde && rel && capa !== deducirCapa(rel)) {
      anota('capa', `dice '${capa}' y su ruta implica '${deducirCapa(rel)}'`);
    }
  }

  for (const a of lista(fm, 'areas')) {
    if (!AREAS.has(a)) anota('areas', `área fuera de la lista cerrada: ${a}`);
  }
  for (const t of lista(fm, 'tags')) {
    if (!TAGS.has(t)) anota('tags', `tag fuera del vocabulario cerrado: ${t}`);
  }

  return fallos;
}
