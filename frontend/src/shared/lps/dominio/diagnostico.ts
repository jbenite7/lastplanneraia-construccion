/**
 * Texto de diagnóstico mostrado en la tarjeta principal del cajón: una frase por estado/severidad,
 * distinta para Programación Semanal (PS, estados `prog-*`/`cal-*`) y para Programa General /
 * Programación Intermedia (PG/PI, por severidad). Puerto exacto de `renderWeeklyDiagnosis` /
 * `renderStandardDiagnosis` / `renderDiagnosis` / `actionSentence` / `getWeeklyCommitmentGaps` /
 * `escapeHtml` (lps_drawer.js:311-317,656-663,1311-1429).
 *
 * El original escribe el resultado con `descEl.innerHTML = ...`; aquí no hay DOM, así que se
 * devuelve el mismo string (con el mismo marcado `<strong>` y los mismos emojis) para que la capa
 * de presentación lo inyecte como venía haciéndolo — el contenido HTML es dato, no manipulación
 * del DOM, y `escapeHtml` ya era una función de texto puro en el original (sin `createElement`).
 */

import { type FilaLps, esValorEnBlanco, analizarNumero, primerValor } from './campos';
import { resumenRestricciones } from './restricciones';
import type { ContextoLps } from './contexto';

/** Puerto exacto de `escapeHtml` (lps_drawer.js:1311-1319) — reemplazo de texto, no usa el DOM. */
export function escaparHtml(texto: unknown): string {
  if (!texto) return '';
  return String(texto)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Frase de "acción sugerida" con hasta 3 acciones del estado operativo, o cadena vacía si no hay
 * ninguna. Puerto exacto de `actionSentence` (lps_drawer.js:1321-1324).
 */
function fraseAccion(contexto: ContextoLps): string {
  const acciones = (contexto.stateActions || []).slice(0, 3).map(escaparHtml);
  return acciones.length ? ` Acción sugerida: ${acciones.join('; ')}.` : '';
}

/**
 * Pendientes operativos para poder comprometer la actividad en la semana: compromiso mayor a cero,
 * responsable AIA asignado, subcontratista asignado. Puerto exacto de `getWeeklyCommitmentGaps`
 * (lps_drawer.js:656-663).
 */
export function brechasCompromisoSemanal(fila: FilaLps): string[] {
  const brechas: string[] = [];
  const compromiso = analizarNumero(primerValor(fila, ['Compromiso']), null);
  if (compromiso === null || compromiso <= 0) brechas.push('definir compromiso mayor a cero');
  if (esValorEnBlanco(primerValor(fila, ['Responsable_AIA', 'Responsable']))) brechas.push('asignar Responsable AIA');
  if (esValorEnBlanco(primerValor(fila, ['Sub_Contratista', 'Subcontratista']))) brechas.push('asignar Sub-Contratista');
  return brechas;
}

/**
 * Diagnóstico de Programación Semanal (PS): una frase por cada estado `prog-*`/`cal-*` de la
 * matriz — T02-AC "PS program/qualification states". Puerto exacto de `renderWeeklyDiagnosis`
 * (lps_drawer.js:1326-1376).
 */
function diagnosticoSemanal(contexto: ContextoLps): string {
  const { stateKey } = contexto;
  const etiqueta = escaparHtml(contexto.stateLabel || 'Control semanal');
  const itrTexto = `${contexto.itr.porcentaje}%`;
  const textoAccion = fraseAccion(contexto);
  const compromiso = primerValor(contexto.rowData, ['Compromiso']);
  const real = primerValor(contexto.rowData, ['Ejecutado_Real']);
  const etiquetaFase = contexto.phase === 'calificacion' ? 'Calificación semanal' : 'Programación semanal';

  if (stateKey === 'prog-bloqueo-critico-sin-compromiso') {
    return `🚨 <strong>${etiqueta}.</strong> ${etiquetaFase}: actividad de ruta crítica con condiciones habilitantes pendientes (ITR: ${itrTexto}) y sin compromiso confiable. Escalar liberación antes de confirmar producción.${textoAccion}`;
  }
  if (stateKey === 'prog-ejecucion-con-restricciones') {
    const textoEscalamiento = contexto.severity === 'critical'
      ? ' Escalar continuidad del frente por impacto sobre ruta crítica.'
      : ' Gestionar cierre operativo sin escalamiento directivo por defecto.';
    return `⚠️ <strong>${etiqueta}.</strong> ${etiquetaFase}: existe avance acumulado (${escaparHtml(contexto.progressDisplay)}), pero aún hay restricciones habilitantes pendientes (ITR: ${itrTexto}). No comprometer más producción sin cerrar condiciones.${textoEscalamiento}${textoAccion}`;
  }
  if (stateKey === 'prog-condiciones-pendientes') {
    return `🟠 <strong>${etiqueta}.</strong> ${etiquetaFase}: la actividad requiere cerrar condiciones de habilitación antes de comprometerse (ITR: ${itrTexto}).${textoAccion}`;
  }
  if (stateKey === 'prog-sin-compromiso') {
    const brechas = brechasCompromisoSemanal(contexto.rowData);
    const textoBrecha = brechas.length ? brechas.join('; ') : 'validar compromiso semanal';
    const notaCritica = contexto.isCritical ? ' Es ruta crítica, por lo que conviene comprometerla con prioridad, pero no requiere escalamiento mientras esté habilitada.' : '';
    return `🟡 <strong>${etiqueta}.</strong> ${etiquetaFase}: actividad habilitada para plan semanal (ITR: ${itrTexto}). Pendiente operativo: ${escaparHtml(textoBrecha)}.${notaCritica}${textoAccion}`;
  }
  if (stateKey === 'prog-lista-para-confirmar') {
    return `🟢 <strong>${etiqueta}.</strong> ${etiquetaFase}: compromiso, responsable y subcontratista listos. Mantener verificación final antes del cierre semanal.`;
  }
  if (stateKey === 'cal-incumplida-critica' || stateKey === 'cal-incumplida') {
    const verbo = contexto.severity === 'critical' ? 'Registrar CNC y activar recuperación hoy.' : 'Registrar CNC y plan correctivo.';
    return `🔴 <strong>${etiqueta}.</strong> ${etiquetaFase}: el ejecutado real (${escaparHtml(real || 'sin dato')}) está por debajo del compromiso (${escaparHtml(compromiso || 'sin dato')}). ${verbo}${textoAccion}`;
  }
  if (stateKey === 'cal-sin-calificar') {
    return `🟡 <strong>${etiqueta}.</strong> ${etiquetaFase}: falta registrar ejecutado real para evaluar PAC y CNC. Completar calificación antes del cierre.`;
  }
  if (stateKey === 'cal-cumplida-control') {
    return `🟢 <strong>${etiqueta}.</strong> ${etiquetaFase}: compromiso cumplido o superado. Documentar aprendizaje y sostener ritmo.`;
  }

  return `🟢 <strong>${etiqueta}.</strong> ${etiquetaFase}: estado operativo semanal sin alertas críticas activas. ITR actual: ${itrTexto}.${textoAccion}`;
}

/**
 * Diagnóstico de Programa General / Programación Intermedia (PG/PI): una frase por severidad,
 * con casos especiales para SOS activo y crisis reactiva por desviación de avance. Puerto exacto
 * de `renderStandardDiagnosis` (lps_drawer.js:1378-1420).
 */
function diagnosticoEstandar(contexto: ContextoLps): string {
  const etiquetaEstado = escaparHtml(contexto.stateLabel || 'Control');
  const resumen = escaparHtml(resumenRestricciones(contexto.rowData, contexto.itr));
  const itrTexto = `${contexto.itr.porcentaje}%`;
  const semanas = contexto.semanasInicio;
  const textoAccion = fraseAccion(contexto);

  if (contexto.isSOS) {
    return `🔥 <strong>CRISIS ACTIVA POR ESCALAMIENTO SOS.</strong> El frente está escalado para intervención jerárquica. Bloqueo reportado: [${resumen}]. Se requiere acción directiva inmediata.`;
  }

  if (contexto.severity === 'critical') {
    if (contexto.isReactiveCrisis) {
      return `⚡ <strong>CRISIS REACTIVA: DESVIACIÓN DE AVANCE.</strong> Actividad P1 con desviación crítica. Avance actual: ${escaparHtml(contexto.progressDisplay)}. Revisar rendimientos, cuadrillas y reprogramación de frentes.`;
    }
    const horizonte = semanas === null
      ? 'sin fecha confiable de inicio'
      : (semanas < 0 ? `debió iniciar hace ${Math.abs(semanas)} semana(s)` : (semanas === 0 ? 'debe iniciar hoy' : `inicia en ${semanas} semana(s)`));
    return `🚨 <strong>${etiquetaEstado}: BLOQUEO CRÍTICO.</strong> Actividad P1 ${horizonte} con restricciones habilitantes pendientes (ITR: ${itrTexto}). Pendientes: ${resumen}. Escalar recuperación y destrabe inmediato.${textoAccion}`;
  }

  if (contexto.severity === 'attention') {
    const horizonte = contexto.isStartOverdue ? ` Debió iniciar hace ${Math.abs(semanas ?? 0)} semana(s).` : '';
    const notaRuta = contexto.isCritical ? ' Es P1, pero no cumple condición de crisis directiva según la matriz temporal.' : '';
    return `🟡 <strong>${etiquetaEstado}.</strong> Atención operativa prioritaria.${horizonte}${notaRuta} ITR actual: ${itrTexto}. Pendientes: ${resumen}.${textoAccion}`;
  }

  if (contexto.severity === 'info') {
    const horizonte = semanas === null ? '' : ` Inicia en ${semanas} semana(s).`;
    return `🔵 <strong>${etiquetaEstado}.</strong> Preparación temprana sin escalamiento.${horizonte} ITR actual: ${itrTexto}. Mantener seguimiento lookahead y restricciones blandas como información.${textoAccion}`;
  }

  if (contexto.isCritical && contexto.isLiberada) {
    const horizonte = semanas !== null && semanas > 0 ? ` Inicia en ${semanas} semana(s).` : '';
    return `🟢 <strong>P1 EN CONTROL.</strong>${horizonte} Actividad crítica liberada de restricciones habilitantes. Mantener control de productividad y verificación de arranque.`;
  }

  return `🟢 <strong>SEGUIMIENTO RUTINARIO.</strong> Actividad sin bloqueos habilitantes críticos. ITR actual: ${itrTexto}. Mantener control diario de obra.`;
}


/**
 * Selecciona la matriz de diagnóstico (semanal o estándar) según el módulo. Puerto exacto de
 * `renderDiagnosis` (lps_drawer.js:1422-1429).
 */
export function diagnosticoContexto(contexto: ContextoLps): string {
  if (contexto.moduleKey === 'programacion-semanal') return diagnosticoSemanal(contexto);
  return diagnosticoEstandar(contexto);
}
