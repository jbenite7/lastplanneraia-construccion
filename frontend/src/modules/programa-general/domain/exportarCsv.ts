import { ActividadUI } from './modelo';

export const CABECERAS_CSV_13_COLS = [
  'ID',
  'Código',
  'Actividad',
  'Crítica',
  'Fecha inicio',
  'Sem. inicio',
  'Fecha fin',
  'Cantidad PPTO',
  'Unidad',
  'Avance Real',
  'Avance Teórico',
  'Lib. restricciones',
  'Estado',
];

function escaparCampoCsv(valor: string | number | boolean | null | undefined): string {
  if (valor === null || valor === undefined) return '';
  const str = String(valor);
  if (str.includes(',') || str.includes('"') || str.includes('\n') || str.includes('\r')) {
    return `"${str.replace(/"/g, '""')}"`;
  }
  return str;
}

export function generarContenidoCsv13Cols(actividades: ActividadUI[]): string {
  const lineas: string[] = [];

  // Fila de encabezados
  lineas.push(CABECERAS_CSV_13_COLS.map(escaparCampoCsv).join(','));

  // Filas de datos
  for (const act of actividades) {
    if (act.esCapitulo) {
      // Los capítulos se registran preservando jerarquía en el CSV
      const filaCap = [
        act.unique_id,
        '',
        act.Actividad,
        'No',
        '',
        '',
        '',
        '',
        '',
        `${act.avanceRealPct}%`,
        `${act.avanceTeoricoPct}%`,
        '',
        'Capítulo',
      ];
      lineas.push(filaCap.map(escaparCampoCsv).join(','));
      continue;
    }

    const semInicioStr = act.Semanas_Inicio ? `Sem ${act.Semanas_Inicio}` : '';
    const restriccionesStr = act.Estado_Restricciones ? `${act.Estado_Restricciones}` : '0%';

    const fila = [
      act.unique_id,
      act.codigo_actividad || act.Consecutivo_en_Programa || '',
      act.Actividad,
      act.esRutaCritica ? 'Sí' : 'No',
      act.Fecha_Inicio || '',
      semInicioStr,
      act.Fecha_Fin || '',
      act.cantidad_ppto !== null && act.cantidad_ppto !== undefined ? act.cantidad_ppto : '',
      act.unidad || '',
      `${act.avanceRealPct}%`,
      `${act.avanceTeoricoPct}%`,
      restriccionesStr,
      act.Estado,
    ];

    lineas.push(fila.map(escaparCampoCsv).join(','));
  }

  // BOM UTF-8 (\uFEFF) para compatibilidad con Excel
  return '\uFEFF' + lineas.join('\r\n');
}

export function dispararDescargaCsv(contenido: string, nombreArchivo: string): void {
  const blob = new Blob([contenido], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', nombreArchivo);
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}
