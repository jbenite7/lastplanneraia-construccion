/**
 * Extractores puros de campos de negocio sobre una fila LPS: consecutivo, título, subcontratista,
 * responsable, ruta crítica y avance. Puerto de `public/js/modules/lps_drawer.js:444-486,665-680`.
 * Los usan tanto `contexto.ts` (para construir el contexto del cajón) como `digest.ts` (que
 * recorre filas sin pasar por un contexto completo), de ahí que vivan separados.
 */

import { type FilaLps, esValorEnBlanco, primerValor, analizarNumero, analizarRatio, formatearPorcentajeDesdeRatio, normalizarBandera } from './campos';

/**
 * `true` si la fila es de ruta crítica (P1): prioridad literal `"P1"` o cualquiera de las banderas
 * `Ruta_Critica`/`Critica`/`p1` en formato laxo. Puerto exacto de `isCriticalRoute`
 * (lps_drawer.js:444-449).
 */
export function esRutaCritica(fila: FilaLps | null | undefined): boolean {
  if (!fila) return false;
  const prioridad = String(primerValor(fila, ['prioridad', 'Prioridad']) || '').trim().toUpperCase();
  const clavesBandera = ['Ruta_Critica', 'ruta_critica', 'Critica', 'critica', 'p1', 'P1'];
  return prioridad === 'P1' || clavesBandera.some((clave) => normalizarBandera(fila[clave]));
}

/**
 * Consecutivo canónico de la actividad: primer alias no-blanco entre las variantes conocidas del
 * campo, o `"N/A"` si ninguna trae dato. Puerto exacto de `getCanonicalConsecutivo`
 * (lps_drawer.js:458-468) — incluida su rareza: el `||` del original (no `??`) hace que un
 * consecutivo numérico `0` también caiga a `"N/A"` (0 es falsy en JS aunque `esValorEnBlanco(0)`
 * sea `false`). No se corrige aquí; en la práctica los consecutivos observados siempre son >= 1.
 */
export function consecutivoCanonico(fila: FilaLps): unknown {
  return primerValor(fila, [
    'unique_id',
    'Consecutivo_en_Programa',
    'Consecutivo_En_Programa',
    'consecutivo_en_programa',
    'Consecutivo',
    'Id',
    'id',
  ]) || 'N/A';
}

/** Puerto exacto de `getActivityTitle` (lps_drawer.js:470-472). */
export function tituloActividad(fila: FilaLps): unknown {
  return primerValor(fila, ['Actividad', 'nombre', 'Nombre']) || 'Tarea sin nombre';
}

/**
 * Versión en texto plano de un valor que puede traer marcado HTML (p. ej. el título de actividad
 * con `<mark>` de resaltado de búsqueda). El original delega en el DOM
 * (`container.innerHTML = valor; container.textContent`, lps_drawer.js:474-478); aquí, sin DOM
 * permitido, se reimplementa por regex: quita etiquetas y decodifica las entidades que de hecho
 * aparecen en este código base (`&amp; &lt; &gt; &quot; &#039; &nbsp;` y entidades numéricas).
 *
 * Divergencia documentada (no es un bug del original, es la reimplementación exigida por la
 * restricción "sin DOM" del brief): para HTML mal formado o entidades poco comunes el navegador y
 * este puerto pueden no coincidir carácter a carácter. No se observó ningún caso así en las
 * llamadas reales (títulos de actividad, siempre texto simple u ocasional `<mark>`).
 */
export function textoPlano(valor: unknown): string {
  const crudo = String(valor === null || valor === undefined ? '' : valor);
  const sinEtiquetas = crudo.replace(/<[^>]*>/g, '');
  const decodificado = sinEtiquetas
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/&#0?39;/gi, "'")
    .replace(/&#x27;/gi, "'")
    .replace(/&#(\d+);/g, (_coincidencia, codigo: string) => String.fromCharCode(Number(codigo)));
  return decodificado.trim();
}

/** Puerto exacto de `getSubcontractor` (lps_drawer.js:480-482). */
export function subcontratista(fila: FilaLps): unknown {
  return primerValor(fila, ['Sub_Contratista', 'Subcontratista', 'subcontratista', 'responsable']) || 'Sin Asignar';
}

/** Puerto exacto de `getResponsible` (lps_drawer.js:484-486). */
export function responsable(fila: FilaLps): unknown {
  return primerValor(fila, ['Responsable_AIA', 'Responsable', 'responsable_aia', 'responsable']) || 'Sin Asignar';
}

/**
 * `true` si la fila representa un capítulo/encabezado en vez de una actividad: `Titulo` numérico
 * distinto de 0, o el estado operativo resuelto es `'header'`. Puerto exacto de `isHeaderRow`
 * (lps_drawer.js:451-456); `claveEstado` es el `stateKey` ya resuelto por quien llama (en el
 * original, `getStateKey(stateView)`).
 */
export function esFilaCabecera(fila: FilaLps | null | undefined, claveEstado: string): boolean {
  if (!fila) return false;
  if (Number(fila.Titulo) !== 0 && fila.Titulo !== undefined && fila.Titulo !== null && fila.Titulo !== '') return true;
  return claveEstado === 'header';
}

/** Ratio 0-1 de avance ejecutado. Puerto exacto de `getProgressRatio` (lps_drawer.js:665-667). */
export function ratioAvance(fila: FilaLps): number {
  return analizarRatio(primerValor(fila, ['Ejecutado', 'ejecutado'])) ?? 0;
}

/**
 * Texto de avance para mostrar: cantidad + unidad + porcentaje entre paréntesis cuando la unidad
 * no es `%` y hay una cantidad numérica en `EjecutadoDisplay`; si no, el porcentaje a secas.
 * Puerto exacto de `getProgressDisplay` (lps_drawer.js:669-680).
 */
export function avanceVisible(fila: FilaLps): string {
  const ratio = ratioAvance(fila);
  const unidad = String(primerValor(fila, ['unidad', 'Unidad']) || '%').trim() || '%';
  const display = primerValor(fila, ['EjecutadoDisplay']);
  const cantidad = analizarNumero(display, null);

  if (cantidad !== null && unidad !== '%') {
    return `${cantidad.toLocaleString('es-CO', { maximumFractionDigits: 1 })} ${unidad} (${formatearPorcentajeDesdeRatio(ratio)})`;
  }

  return formatearPorcentajeDesdeRatio(ratio);
}

/** Reexportado para quienes solo necesitan comprobar blancos sobre un campo ya extraído. */
export { esValorEnBlanco };
