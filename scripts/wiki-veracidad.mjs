#!/usr/bin/env node
// Alarma de la operación `veracidad` de la wiki `memoria/`.
// Funciones puras: no imprimen ni salen con código. Las consume scripts/wiki-lint.mjs.
// Ver docs/wiki-operacion.md.
import { execFileSync } from 'node:child_process';

// Más de este número de commits de código desde el último pase → hallazgo.
// Elegido contra el ritmo real del repo (101-181 commits/día en sprint): salta 2-4 veces
// en un día intenso y ninguna en días quietos. Ajustable en una línea; deja constancia en el log.
export const UMBRAL_COMMITS = 40;

// Código y contratos. `memoria/` queda fuera a propósito: la wiki no dispara su propia alarma.
export const RUTAS_CONTADAS = ['src/', 'admin/', 'public/', 'tests/', 'scripts/', 'docs/', 'AGENTS.md'];

const LINEA_VERACIDAD = /^-\s+(\d{4}-\d{2}-\d{2})\s+·\s+veracidad\s+·/;

export function ultimoPase(logTexto) {
  let ultima = null;
  for (const linea of logTexto.split('\n')) {
    const m = LINEA_VERACIDAD.exec(linea.trim());
    if (m) ultima = m[1];
  }
  return ultima;
}

function gitPorDefecto(args) {
  try {
    return execFileSync('git', args, { encoding: 'utf8' });
  } catch {
    return '';
  }
}

// Las claves del esquema v2. Una línea de diff que empiece por una de ellas es metadato, no
// contenido. Se listan aquí y no se importan de `wiki-esquema.mjs` a propósito: este módulo es la
// alarma y tiene que poder correr aunque el esquema cambie debajo. Si divergen, esta lista peca de
// corta y la alarma cuenta de más, que es el lado seguro.
const CLAVES_ESQUEMA = ['capa', 'tipo', 'estado', 'fecha', 'areas', 'tags', 'fuente', 'resumen', 'origen'];

const LINEA_METADATO = new RegExp(
  `^[+-](?:---[ \t]*$|[ \t]*$|(?:${CLAVES_ESQUEMA.join('|')}):|[ \t]+-[ \t])`,
);
const CABECERA_HUNK = /^@@ -(\d+)(?:,\d+)? \+(\d+)(?:,\d+)? @@/;

/**
 * ¿El diff de un commit toca **solo** el bloque de frontmatter?
 *
 * Exige las dos cosas a la vez, y por eso es difícil que se equivoque:
 *   1. **Todos** sus hunks empiezan en la línea 1. El frontmatter vive en la cabecera del archivo;
 *      un cambio de cuerpo abre su hunk más abajo.
 *   2. **Todas** las líneas añadidas o quitadas son metadato: una clave del esquema, un `---`, un
 *      elemento de lista indentado, o una línea en blanco.
 *
 * Con una sola de las dos condiciones se colaría un falso: un `- punto` de una lista de Markdown
 * en las primeras líneas cumple la 2, y una reescritura de la cabecera cumple la 1.
 *
 * Ante la duda **devuelve `false`**, es decir, el commit cuenta. Una alarma que se calla de más es
 * peor que una que suena de más: la primera falla en silencio.
 */
export function esSoloFrontmatter(diff) {
  if (!diff.trim()) return false;
  let vioHunk = false;
  for (const linea of diff.split('\n')) {
    const hunk = CABECERA_HUNK.exec(linea);
    if (hunk) {
      vioHunk = true;
      if (Number(hunk[2]) > 1) return false;   // empieza más abajo de la cabecera
      continue;
    }
    if (/^(\+\+\+|---) /.test(linea)) continue;   // cabeceras de archivo del propio diff
    if (!/^[+-]/.test(linea)) continue;             // contexto
    if (!LINEA_METADATO.test(linea)) return false;
  }
  return vioHunk;
}

/** Parte la salida de `git log --pretty=%H --name-only` en `{sha, archivos}`. */
export function partirCommits(salida) {
  const commits = [];
  for (const linea of salida.split('\n')) {
    const t = linea.trim();
    if (!t) continue;
    if (/^[0-9a-f]{7,40}$/.test(t)) commits.push({ sha: t, archivos: [] });
    else if (commits.length) commits[commits.length - 1].archivos.push(t);
  }
  return commits;
}

/**
 * Commits de código desde `desde`, **sin contar los que solo añaden frontmatter**.
 *
 * Esa exclusión existe por una medida concreta: el 2026-08-19 la alarma saltó con 69 commits, y al
 * desglosarlos el código de producto tocado eran **8 archivos**. El resto era el backfill de la
 * wiki v2, que puso frontmatter en 413 archivos de `docs/` **sin cambiar una línea de contenido**.
 * La regla documentada de la alarma siempre fue «commits que tocan código o contratos», y un
 * commit de solo-metadato no toca ninguno de los dos: contarlo hacía que la wiki disparase su
 * propia alarma por escribirse, que es justo lo que la exclusión de `memoria/` ya evitaba.
 *
 * **Es conservadora a propósito.** Un commit solo se descuenta si se puede demostrar que es
 * metadato: si no lista archivos (un merge), si toca algo que no sea `.md`, o si el diff no se
 * puede leer, cuenta. Se prefiere que suene de más a que se calle de menos.
 */
export function contarCommits(desde, ejecutor = gitPorDefecto) {
  const args = ['log', `--since=${desde}`, '--pretty=%H', '--name-only', '--', ...RUTAS_CONTADAS];
  const commits = partirCommits(ejecutor(args));

  return commits.filter((c) => {
    if (c.archivos.length === 0) return true;                       // merge o sin datos: cuenta
    if (!c.archivos.every((f) => f.endsWith('.md'))) return true;   // toca algo que no es prosa
    const diff = ejecutor(['show', '--unified=0', '--format=', c.sha, '--', ...RUTAS_CONTADAS]);
    return !esSoloFrontmatter(diff);
  }).length;
}

export function estadoVeracidad(logTexto, ejecutor = gitPorDefecto) {
  const desde = ultimoPase(logTexto);
  if (!desde) return { sembrado: false, desde: null, commits: 0, excedido: false };
  const commits = contarCommits(desde, ejecutor);
  return { sembrado: true, desde, commits, excedido: commits > UMBRAL_COMMITS };
}

export function mensajeVeracidad(estado) {
  if (!estado.sembrado) {
    return {
      hallazgo: null,
      aviso: 'Veracidad: sin pase registrado todavía. El primer pase siembra la línea '
        + '`veracidad` en memoria/log.md; hasta entonces esta comprobación no falla.',
    };
  }
  if (!estado.excedido) {
    return {
      hallazgo: null,
      aviso: `Veracidad: ${estado.commits} commits de código desde el pase del ${estado.desde} `
        + `(umbral ${UMBRAL_COMMITS}).`,
    };
  }
  return {
    hallazgo: `${estado.commits} commits de código desde el último pase del ${estado.desde}, `
      + `por encima del umbral de ${UMBRAL_COMMITS}. Toca un pase de veracidad: `
      + 'verifica contra el repositorio las páginas de las áreas que cambiaron '
      + '(ver docs/wiki-operacion.md).',
    aviso: null,
  };
}
