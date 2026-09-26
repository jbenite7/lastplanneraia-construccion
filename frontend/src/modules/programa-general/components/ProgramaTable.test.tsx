import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { ProgramaTable } from './ProgramaTable';
import { ActividadUI } from '../domain/modelo';

describe('ProgramaTable', () => {
  const mockActividades: ActividadUI[] = [
    {
      unique_id: 1,
      Actividad: '1. Cimentación',
      Titulo: 1,
      esCapitulo: true,
      capituloNombre: '1. Cimentación',
      avanceRealPct: 45,
      avanceTeoricoPct: 50,
      deltaPct: -5,
      deltaTexto: '-5%',
      esRutaCritica: false,
      plazoVencido: false,
      diasVencimiento: 0,
      alerta_crisis: 0,
    },
    {
      unique_id: 101,
      Consecutivo_en_Programa: 'EST-01',
      codigo_actividad: 'EST-01',
      Actividad: 'Excavación mecánica de zapatas eje A-C',
      Titulo: 0,
      Fecha_Inicio: '2026-08-10',
      Fecha_Fin: '2026-08-20',
      Semanas_Inicio: 33,
      Ruta_Critica: 1,
      Ejecutado: 0.25,
      Ejecutado_Teorico: 0.5,
      Estado: 'Atrasada',
      Estado_Restricciones: '0.66',
      cantidad_ppto: 450.0,
      unidad: 'm³',
      esCapitulo: false,
      capituloNombre: '1. Cimentación',
      avanceRealPct: 25.0,
      avanceTeoricoPct: 50.0,
      deltaPct: -25.0,
      deltaTexto: '-25%',
      esRutaCritica: true,
      plazoVencido: true,
      diasVencimiento: 3,
      alerta_crisis: 0,
    },
    {
      unique_id: 102,
      Consecutivo_en_Programa: 'EST-02',
      codigo_actividad: 'EST-02',
      Actividad: 'Armado de acero zapatas',
      Titulo: 0,
      Fecha_Inicio: '2026-08-18',
      Fecha_Fin: '2026-08-28',
      Semanas_Inicio: 34,
      Ruta_Critica: 0,
      Ejecutado: 0.4,
      Estado: 'En Curso',
      Estado_Restricciones: '1',
      cantidad_ppto: 12.5,
      unidad: 'ton',
      esCapitulo: false,
      capituloNombre: '1. Cimentación',
      avanceRealPct: 40.0,
      avanceTeoricoPct: 40.0,
      deltaPct: 0.0,
      deltaTexto: '0%',
      esRutaCritica: false,
      plazoVencido: false,
      diasVencimiento: 0,
      alerta_crisis: 0,
    },
  ];

  it('declara ocho columnas en colgroup sin anchos inline', () => {
    const { container } = render(
      <ProgramaTable actividades={[]} actividadSeleccionadaId={null} onSelectActividad={vi.fn()} />,
    );
    expect([...container.querySelectorAll('colgroup col')].map((col) => col.className)).toEqual([
      'pg-col-id', 'pg-col-codigo', 'pg-col-actividad', 'pg-col-inicio',
      'pg-col-fin', 'pg-col-ppto', 'pg-col-avance', 'pg-col-estado',
    ]);
    container.querySelectorAll<HTMLTableCellElement>('thead th').forEach((th) => expect(th.style.width).toBe(''));
  });

  it('declara trece columnas en colgroup en modo completo', () => {
    const { container } = render(
      <ProgramaTable actividades={[]} actividadSeleccionadaId={null} onSelectActividad={vi.fn()} modo13Cols />,
    );
    expect(container.querySelectorAll('colgroup col')).toHaveLength(13);
    expect(container.querySelectorAll('thead th')).toHaveLength(13);
  });

  it('renderiza las 8 columnas requeridas por defecto y celdas combinadas', () => {
    render(
      <ProgramaTable
        actividades={mockActividades}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
      />
    );

    // Encabezados de 8 columnas
    expect(screen.getByText('ID')).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'CÓDIGO' })).toHaveTextContent('CÓD.');
    expect(screen.getByText('ACTIVIDAD')).toBeInTheDocument();
    expect(screen.getByText('F. INICIO')).toBeInTheDocument();
    expect(screen.getByText('F. FIN')).toBeInTheDocument();
    expect(screen.getByText('PPTO TOTAL')).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'AVANCE (REAL / TEÓR)' })).toHaveTextContent('AV. REAL / TEÓR');
    expect(screen.getByText('ESTADO')).toBeInTheDocument();

    // Datos combinados en 8 columnas
    expect(screen.getByText('EST-01')).toBeInTheDocument();
    expect(screen.getByText('450.0 m³')).toBeInTheDocument();
    expect(screen.getByText('25.0%')).toBeInTheDocument();
    expect(screen.getByText('/ 50.0%')).toBeInTheDocument();
    expect(screen.getByText('-25%')).toBeInTheDocument();
    expect(screen.getByText('RC')).toBeInTheDocument();
    expect(screen.getByText('Atrasada')).toBeInTheDocument();
    expect(screen.getByText('En Curso')).toBeInTheDocument();

    // Accesibilidad de región
    const region = screen.getByRole('region', { name: /cronograma de actividades/i });
    expect(region).toBeInTheDocument();
  });

  it('dispara onSelectActividad al hacer clic en una fila de actividad', () => {
    const onSelect = vi.fn();
    render(
      <ProgramaTable
        actividades={mockActividades}
        actividadSeleccionadaId={null}
        onSelectActividad={onSelect}
      />
    );

    fireEvent.click(screen.getByText('Excavación mecánica de zapatas eje A-C'));
    expect(onSelect).toHaveBeenCalledWith(101);
  });

  it('soporta navegación y selección mediante teclado con Enter y Espacio', () => {
    const onSelect = vi.fn();
    render(
      <ProgramaTable
        actividades={mockActividades}
        actividadSeleccionadaId={null}
        onSelectActividad={onSelect}
      />
    );

    const rows = screen.getAllByRole('row');
    // rows[0] is thead, rows[1] is chapter, rows[2] is activity 101, rows[3] is activity 102
    const row101 = rows.find((r) => r.textContent?.includes('EST-01'))!;
    expect(row101).toHaveAttribute('tabIndex', '0');

    fireEvent.keyDown(row101, { key: 'Enter' });
    expect(onSelect).toHaveBeenCalledWith(101);

    fireEvent.keyDown(row101, { key: ' ' });
    expect(onSelect).toHaveBeenCalledWith(101);
  });

  it('aplica la clase active-editing a la fila seleccionada', () => {
    const { rerender } = render(
      <ProgramaTable
        actividades={mockActividades}
        actividadSeleccionadaId={101}
        onSelectActividad={vi.fn()}
      />
    );

    const rows = screen.getAllByRole('row');
    const row101 = rows.find((r) => r.textContent?.includes('EST-01'))!;
    const row102 = rows.find((r) => r.textContent?.includes('EST-02'))!;

    expect(row101.className).toContain('active-editing');
    expect(row101).toHaveAttribute('aria-selected', 'true');
    expect(row102.className).not.toContain('active-editing');
    expect(row102).toHaveAttribute('aria-selected', 'false');

    rerender(
      <ProgramaTable
        actividades={mockActividades}
        actividadSeleccionadaId={102}
        onSelectActividad={vi.fn()}
      />
    );
    expect(row101.className).not.toContain('active-editing');
    expect(row102.className).toContain('active-editing');
  });

  it('renderiza fila de capítulo ocupando colSpan={8} por defecto', () => {
    render(
      <ProgramaTable
        actividades={mockActividades}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
      />
    );

    expect(screen.getByText('1. Cimentación')).toBeInTheDocument();
    expect(screen.getByText('Capítulo')).toBeInTheDocument();

    const chapterCell = screen.getByText('1. Cimentación').closest('td');
    expect(chapterCell).toHaveAttribute('colSpan', '8');
  });

  it('renderiza las 13 columnas completas cuando modo13Cols es true', () => {
    render(
      <ProgramaTable
        actividades={mockActividades}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
        modo13Cols={true}
      />
    );

    // 13 columnas individuales
    expect(screen.getByText('ID')).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'CÓDIGO' })).toHaveTextContent('CÓD.');
    expect(screen.getByText('ACTIVIDAD')).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'RC' })).toBeInTheDocument();
    expect(screen.getByText('F. INICIO')).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'SEM. INICIO' })).toHaveTextContent('SEM. INI.');
    expect(screen.getByText('F. FIN')).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'CANTIDAD PPTO' })).toHaveTextContent('CANT.');
    expect(screen.getByRole('columnheader', { name: 'UNIDAD' })).toHaveTextContent('UND.');
    expect(screen.getByRole('columnheader', { name: 'AVANCE REAL' })).toHaveTextContent('AV. REAL');
    expect(screen.getByRole('columnheader', { name: 'AVANCE TEÓR' })).toHaveTextContent('AV. TEÓR');
    expect(screen.getByRole('columnheader', { name: 'LIB. RESTRICCIONES' })).toHaveTextContent('RESTR. LIB.');
    expect(screen.getByText('ESTADO')).toBeInTheDocument();

    // Capítulo con colSpan={13}
    const chapterCell = screen.getByText('1. Cimentación').closest('td');
    expect(chapterCell).toHaveAttribute('colSpan', '13');

    // Celdas independientes en 13 cols
    expect(screen.getByText('Sem 33')).toBeInTheDocument();
    expect(screen.getByText('450.0')).toBeInTheDocument();
    expect(screen.getByText('m³')).toBeInTheDocument();
    expect(screen.getByText('25.0%')).toBeInTheDocument();
    expect(screen.getByText('50.0%')).toBeInTheDocument();
    expect(screen.getByText('66%')).toBeInTheDocument();
    expect(screen.getByText('100%')).toBeInTheDocument();
  });

  it('muestra icono de alerta cuando el plazo está vencido', () => {
    render(
      <ProgramaTable
        actividades={mockActividades}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
      />
    );

    const alertIcon = screen.getByTitle('Plazo vencido hace 3 días');
    expect(alertIcon).toBeInTheDocument();
    expect(alertIcon.textContent).toContain('⚠️');
  });

  it('renderiza actividades con tags HTML sanitizados mostrando titulo y subtitulo', () => {
    const acts = [
      {
        unique_id: 101,
        id_proyecto: 1,
        Titulo: 0,
        Actividad: '<b>LOCALIZACIÓN Y REPLANTEO, </b> <small>[Capítulo: PRELIMINARES, DAPORTO TORRE 3]</small>',
        codigo_actividad: 'ACT-101',
        esCapitulo: false,
        capituloNombre: 'PRELIMINARES',
        Fecha_Inicio: '2026-08-10',
        Fecha_Fin: '2026-08-20',
        cantidad_ppto: 100,
        unidad: 'ml',
        avanceRealPct: 50,
        avanceTeoricoPct: 60,
        deltaPct: -10,
        deltaTexto: '-10.0%',
        esRutaCritica: true,
        plazoVencido: true,
        diasVencimiento: 4,
        Estado: 'Atrasada',
      } as any,
    ];

    render(
      <ProgramaTable
        actividades={acts}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
        modo13Cols={false}
      />
    );

    expect(screen.queryByText(/<b>/)).toBeNull();
    expect(screen.queryByText(/<small>/)).toBeNull();
    expect(screen.getByText('LOCALIZACIÓN Y REPLANTEO')).toBeInTheDocument();
    expect(screen.getByText('PRELIMINARES, DAPORTO TORRE 3')).toBeInTheDocument();
    expect(screen.getByText('10/08/2026')).toBeInTheDocument();
    expect(screen.getByText('20/08/2026')).toBeInTheDocument();
  });

  it('renderiza capitulos con titulo limpio sin tags <b>', () => {
    const acts = [
      {
        unique_id: 1,
        id_proyecto: 1,
        Titulo: 1,
        Actividad: '<b>DAPORTO TORRE 3</b>',
        esCapitulo: true,
        capituloNombre: 'DAPORTO TORRE 3',
        avanceRealPct: 0,
        avanceTeoricoPct: 0,
        deltaPct: 0,
        deltaTexto: '-',
        esRutaCritica: false,
        plazoVencido: false,
        diasVencimiento: 0,
        Estado: 'En Curso',
      } as any,
    ];

    render(
      <ProgramaTable
        actividades={acts}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
      />
    );

    expect(screen.queryByText(/<b>/)).toBeNull();
    expect(screen.getByText('DAPORTO TORRE 3')).toBeInTheDocument();
  });

  it('renderiza capitulos con mini barra de avance si avanceRealPct > 0', () => {
    const acts = [
      {
        unique_id: 2,
        id_proyecto: 1,
        Titulo: 1,
        Actividad: 'ESTRUCTURA',
        esCapitulo: true,
        capituloNombre: 'ESTRUCTURA',
        avanceRealPct: 45,
        avanceTeoricoPct: 50,
        deltaPct: -5,
        deltaTexto: '-5%',
        esRutaCritica: false,
        plazoVencido: false,
        diasVencimiento: 0,
        Estado: 'En Curso',
      } as any,
    ];

    render(
      <ProgramaTable
        actividades={acts}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
      />
    );

    expect(screen.getByText('Avance 45%')).toBeInTheDocument();
  });

  it('renderiza celdas duales con clases delta-neg y delta-ok', () => {
    const acts = [
      {
        unique_id: 201,
        codigo_actividad: 'ACT-NEG',
        Actividad: 'Actividad Atrasada',
        esCapitulo: false,
        Fecha_Inicio: '2026-08-01',
        Fecha_Fin: '2026-08-10',
        avanceRealPct: 20,
        avanceTeoricoPct: 40,
        deltaPct: -20,
        deltaTexto: '-20%',
        Estado: 'Atrasada',
      } as any,
      {
        unique_id: 202,
        codigo_actividad: 'ACT-OK',
        Actividad: 'Actividad Al Dia',
        esCapitulo: false,
        Fecha_Inicio: '2026-08-01',
        Fecha_Fin: '2026-08-10',
        avanceRealPct: 40,
        avanceTeoricoPct: 40,
        deltaPct: 0,
        deltaTexto: '0%',
        Estado: 'En Curso',
      } as any,
    ];

    render(
      <ProgramaTable
        actividades={acts}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
        modo13Cols={false}
      />
    );

    const deltaNeg = screen.getByText('-20%');
    expect(deltaNeg.className).toContain('delta-neg');

    const deltaOk = screen.getByText('0%');
    expect(deltaOk.className).toContain('delta-ok');
  });

  it('renderiza fechas formateadas y subtitulo en modo 13 columnas', () => {
    const acts = [
      {
        unique_id: 301,
        codigo_actividad: 'ACT-301',
        Actividad: 'CONCRETO DE LIMPIEZA <small>[Cap: ESTRUCTURAS]</small>',
        esCapitulo: false,
        Fecha_Inicio: '2026-09-01',
        Fecha_Fin: '2026-09-05',
        Semanas_Inicio: 35,
        cantidad_ppto: 50,
        unidad: 'm²',
        avanceRealPct: 100,
        avanceTeoricoPct: 100,
        deltaPct: 0,
        deltaTexto: '0%',
        esRutaCritica: true,
        plazoVencido: false,
        diasVencimiento: 0,
        Estado: 'Terminada',
        Estado_Restricciones: '1',
      } as any,
    ];

    render(
      <ProgramaTable
        actividades={acts}
        actividadSeleccionadaId={null}
        onSelectActividad={vi.fn()}
        modo13Cols={true}
      />
    );

    expect(screen.getByText('CONCRETO DE LIMPIEZA')).toBeInTheDocument();
    expect(screen.getByText(/ESTRUCTURAS/)).toBeInTheDocument();
    expect(screen.getByText('01/09/2026')).toBeInTheDocument();
    expect(screen.getByText('05/09/2026')).toBeInTheDocument();
    expect(screen.getByText('Sem 35')).toBeInTheDocument();
  });
});
