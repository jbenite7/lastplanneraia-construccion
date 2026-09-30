import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { ProgramaCards } from './ProgramaCards';
import { ActividadUI } from '../domain/modelo';

describe('ProgramaCards', () => {
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
      Ruta_Critica: 1,
      Ejecutado: 0.25,
      Estado: 'Atrasada',
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
  ];

  it('renderiza cabeceras de capítulo y tarjetas móviles con métricas', () => {
    render(
      <ProgramaCards
        actividades={mockActividades}
        onSelectActividad={vi.fn()}
      />
    );

    expect(screen.getByText('1. Cimentación')).toBeInTheDocument();
    expect(screen.getByText('EST-01')).toBeInTheDocument();
    expect(screen.getByText('Excavación mecánica de zapatas eje A-C')).toBeInTheDocument();
    expect(screen.getByText('Atrasada')).toBeInTheDocument();
    expect(screen.getByText('RC')).toBeInTheDocument();
    expect(screen.getByText('450.0 m³')).toBeInTheDocument();
    expect(screen.getByText('25.0%')).toBeInTheDocument();
    expect(screen.getByText('(-25%)')).toBeInTheDocument();
    expect(screen.getByTitle('Plazo vencido hace 3 días')).toBeInTheDocument();
  });

  it('limpia el HTML heredado de los títulos y de los capítulos (nunca lo muestra como texto)', () => {
    // Texto exacto que llega de la base y que se veía crudo en móvil (captura de Felipe, 2026-09-29).
    const sucias: ActividadUI[] = [
      {
        ...mockActividades[0],
        unique_id: 2,
        Actividad: '<b>PRELIMINARES, </b> <small>[Capítulo: DAPORTO TORRE 3]</small>',
      },
      {
        ...mockActividades[1],
        unique_id: 102,
        Actividad:
          '<b>LOCALIZACIÓN Y REPLANTEO, </b> <small>[Capítulo: PRELIMINARES, DAPORTO TORRE 3]</small>',
      },
    ];
    const { container } = render(<ProgramaCards actividades={sucias} onSelectActividad={vi.fn()} />);

    expect(container.textContent).not.toMatch(/<\/?(b|small)>/i);
    expect(screen.getByText('PRELIMINARES')).toBeInTheDocument();
    expect(screen.getByText('LOCALIZACIÓN Y REPLANTEO')).toBeInTheDocument();
    expect(screen.getByText('PRELIMINARES, DAPORTO TORRE 3')).toBeInTheDocument();
    // El nombre accesible tampoco arrastra etiquetas.
    expect(screen.getByRole('button', { name: /LOCALIZACIÓN Y REPLANTEO/ }).getAttribute('aria-label')).not.toMatch(/</);
  });

  it('sin código guardado, la tarjeta muestra la numeración WBS como calculada', () => {
    const sinCodigo: ActividadUI = {
      ...(mockActividades.find((a) => !a.esCapitulo) as ActividadUI),
      unique_id: 556,
      codigo_actividad: '',
      Id: '3.4',
    };
    render(<ProgramaCards actividades={[sinCodigo]} actividadSeleccionadaId={null} onSelectActividad={vi.fn()} />);

    expect(screen.getByText('3.4')).toHaveClass('cell-code', 'cell-code--calculado');
  });

  it('dispara onSelectActividad al hacer clic en una tarjeta', () => {
    const onSelect = vi.fn();
    render(
      <ProgramaCards
        actividades={mockActividades}
        onSelectActividad={onSelect}
      />
    );

    fireEvent.click(screen.getByText('Excavación mecánica de zapatas eje A-C'));
    expect(onSelect).toHaveBeenCalledWith(101);
  });

  it('soporta activación por teclado con Enter y Espacio', () => {
    const onSelect = vi.fn();
    render(
      <ProgramaCards
        actividades={mockActividades}
        onSelectActividad={onSelect}
      />
    );

    const card = screen.getByRole('button', { name: /Excavación mecánica/i });
    expect(card).toHaveAttribute('tabIndex', '0');

    fireEvent.keyDown(card, { key: 'Enter' });
    expect(onSelect).toHaveBeenCalledWith(101);

    fireEvent.keyDown(card, { key: ' ' });
    expect(onSelect).toHaveBeenCalledWith(101);
  });

  it('resalta la tarjeta seleccionada con active-editing', () => {
    const { rerender } = render(
      <ProgramaCards
        actividades={mockActividades}
        actividadSeleccionadaId={101}
        onSelectActividad={vi.fn()}
      />
    );

    const card = screen.getByRole('button', { name: /Excavación mecánica/i });
    expect(card.className).toContain('active-editing');
    expect(card).toHaveAttribute('aria-selected', 'true');

    rerender(
      <ProgramaCards
        actividades={mockActividades}
        actividadSeleccionadaId={999}
        onSelectActividad={vi.fn()}
      />
    );
    expect(card.className).not.toContain('active-editing');
    expect(card).toHaveAttribute('aria-selected', 'false');
  });
});
