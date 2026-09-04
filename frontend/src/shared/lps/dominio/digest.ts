/**
 * Digest semanal consolidado de bloqueos críticos: agrupa por subcontratista las actividades de
 * ruta crítica (P1) con algún indicio de bloqueo (ITR incompleto, atraso, causa de no
 * cumplimiento, compromiso vencido o alerta de crisis activa). Puerto exacto de
 * `compileWeeklyDigest` (lps_drawer.js:1248-1301).
 *
 * El original toma la fecha con `new Date().toLocaleDateString()`; aquí, por la restricción "sin
 * fechas del reloj de pared" del brief, la fecha la entrega quien llama (`fecha`). Se formatea con
 * locale `es-CO` explícito (el resto del dominio ya usa ese locale en `filaLps.ts::avanceVisible`)
 * en vez del locale de navegador implícito del original — es la única decisión de este archivo que
 * no es un puerto 1:1; queda documentada en el reporte de la tarea.
 */

import { type FilaLps } from './campos';
import { calcularItr, resumenRestricciones, type ConfiguracionRestricciones } from './restricciones';
import { consecutivoCanonico, tituloActividad, textoPlano, subcontratista, esRutaCritica } from './filaLps';

export interface ResultadoDigestSemanal {
  /** `true` si no se encontró ningún bloqueo crítico: caso "sin datos" del digest. */
  sinBloqueos: boolean;
  /** Bloqueos agrupados por subcontratista, en el mismo orden de aparición del original. */
  bloqueosPorSubcontratista: Record<string, string[]>;
  /** Texto final tal como lo mostraba `lps_digest_text_preview` / copiaba al portapapeles. */
  texto: string;
}

function esVerdadero(valor: unknown): boolean {
  return Boolean(valor);
}

/**
 * `true` si la fila tiene algún indicio de bloqueo operativo: ITR incompleto, atraso positivo,
 * causa de no cumplimiento registrada, compromiso vencido o alerta de crisis activa (`alerta_crisis
 * === 1`). Puerto exacto de la condición `hasBottleneck` (lps_drawer.js:1263) — incluida su
 * comparación directa `row.atraso > 0` sobre el valor crudo (equivalente a `Number(row.atraso) > 0`
 * por la coerción numérica de `>` en JS) y las comprobaciones de verdad laxa (JS truthiness, no
 * `esValorEnBlanco`) sobre `Restriccion`/`causa_no_cumplimiento`/`compromiso_vencido`.
 */
function tieneBloqueo(fila: FilaLps, itrCompleto: boolean): boolean {
  return (
    !itrCompleto
    || Number(fila.atraso) > 0
    || esVerdadero(fila.Restriccion)
    || esVerdadero(fila.causa_no_cumplimiento)
    || esVerdadero(fila.compromiso_vencido)
    || parseInt(String(fila.alerta_crisis), 10) === 1
  );
}

/**
 * Compila el digest semanal de bloqueos críticos sobre las filas de un módulo (Programa General,
 * Programación Intermedia o Programación Semanal). Puerto exacto de `compileWeeklyDigest`
 * (lps_drawer.js:1248-1301): agrupa por subcontratista solo las filas de ruta crítica (P1) con
 * bloqueo, y arma el texto consolidado — o el mensaje "sin bloqueos" cuando no hay ninguna.
 */
export function compilarDigestSemanal(filas: readonly FilaLps[], config: ConfiguracionRestricciones, fecha: Date): ResultadoDigestSemanal {
  const bloqueosPorSubcontratista: Record<string, string[]> = {};

  filas.forEach((fila, indice) => {
    const filaSegura = fila || {};
    const itr = calcularItr(filaSegura, config);
    const esCritica = esRutaCritica(filaSegura);
    const sub = String(subcontratista(filaSegura));
    const consecutivo = consecutivoCanonico(filaSegura) || indice + 1;
    const actividad = textoPlano(tituloActividad(filaSegura)) || 'Tarea';
    const restriccion = resumenRestricciones(filaSegura, itr);

    if (esCritica && tieneBloqueo(filaSegura, itr.isComplete)) {
      if (!bloqueosPorSubcontratista[sub]) bloqueosPorSubcontratista[sub] = [];
      bloqueosPorSubcontratista[sub].push(`Actividad #${String(consecutivo)} (${actividad}) - Restricción: ${restriccion}`);
    }
  });

  const subcontratistas = Object.keys(bloqueosPorSubcontratista);

  if (subcontratistas.length === 0) {
    return {
      sinBloqueos: true,
      bloqueosPorSubcontratista,
      texto: 'Excelente. No se encontraron bloqueos críticos en actividades P1 (Ruta Crítica) para esta semana.',
    };
  }

  let texto = '📋 REPORTE CONSOLIDADO DE BLOQUEOS LPS - OBRA AIA\n';
  texto += `Semana de Control: ${fecha.toLocaleDateString('es-CO')}\n`;
  texto += '==============================================\n\n';

  subcontratistas.forEach((sub) => {
    texto += `▶️ RESPONSABLE: ${sub}\n`;
    bloqueosPorSubcontratista[sub].forEach((tarea) => {
      texto += `  • ${tarea}\n`;
    });
    texto += '\n';
  });

  texto += '----------------------------------------------\n';
  texto += 'Solicitamos a los líderes de frente asegurar recursos y coordinar la liberación de frentes para evitar atrasos en la línea base teórica.';

  return { sinBloqueos: false, bloqueosPorSubcontratista, texto };
}
