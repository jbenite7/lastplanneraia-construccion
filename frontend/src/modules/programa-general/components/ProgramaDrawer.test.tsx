import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { ProgramaDrawer } from './ProgramaDrawer';
import { ActividadUI } from '../domain/modelo';

describe('ProgramaDrawer Contextual LPS', () => {
  const mockAct: ActividadUI = {
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
    Responsable_AIA: 'Ing. Carlos Restrepo',
    Sub_Contratista: 'Excavaciones del Norte S.A.S.',
    Observaciones: 'Lluvia suspendió labores el 18/08.',
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
  };

  const catalogos = {
    unidades: ['m³', 'ton', 'm²', '%'],
    codigos: ['EST-01', 'EST-02'],
    profesionales: [
      { id: 1, nombre: 'Ing. Carlos Restrepo' },
      { id: 2, nombre: 'Ing. María Gómez' },
    ],
    subcontratistas: [
      { id: 1, nombre: 'Excavaciones del Norte S.A.S.' },
      { id: 2, nombre: 'Aceros de Colombia' },
    ],
  };

  it('muestra secciones de plazos, asignaciones opcionales, presupuesto y avance con delta', () => {
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    expect(screen.getByText('Plazos y Cronograma')).toBeInTheDocument();
    expect(screen.getByText('Responsables & Asignaciones')).toBeInTheDocument();
    expect(screen.getByText(/Opcional en S05/i)).toBeInTheDocument();
    expect(screen.getByText(/Desviación Física/i)).toBeInTheDocument();
    expect(screen.getByText('Excavaciones del Norte S.A.S.')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Excavaciones del Norte S.A.S.')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Ing. Carlos Restrepo')).toBeInTheDocument();
    expect(screen.getByText(/Plazo vencido hace 3 días/i)).toBeInTheDocument();
  });

  it('permite navegación secuencial con botones', () => {
    const onNav = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={onNav}
      />
    );

    fireEvent.click(screen.getByTitle(/Actividad Siguiente/i));
    expect(onNav).toHaveBeenCalledWith(1);

    fireEvent.click(screen.getByTitle(/Actividad Anterior/i));
    expect(onNav).toHaveBeenCalledWith(-1);
  });

  it('muestra la Matriz de los 7 Recursos Lean con estados de liberación', () => {
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    expect(screen.getByText(/7 Recursos Lean/i)).toBeInTheDocument();
    expect(screen.getByText('Mano de Obra')).toBeInTheDocument();
    expect(screen.getByText('Maquinaria')).toBeInTheDocument();
    expect(screen.getByText('Materiales')).toBeInTheDocument();
    expect(screen.getByText('Información')).toBeInTheDocument();
    expect(screen.getByText('Condiciones Previas')).toBeInTheDocument();
    expect(screen.getByText('Seguridad')).toBeInTheDocument();
    expect(screen.getByText('Externos')).toBeInTheDocument();
  });

  it('muestra la sección de Bitácora SOS y permite escribir notas técnicas', () => {
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    expect(screen.getByText(/Bitácora SOS/i)).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/Escribir una nueva observación técnica/i)).toBeInTheDocument();
    expect(screen.getByText(/Declarar Crisis SOS LPS/i)).toBeInTheDocument();
  });

  it('deshabilita Cantidad PPTO cuando la unidad seleccionada es %', () => {
    render(
      <ProgramaDrawer
        actividad={{ ...mockAct, unidad: '%' }}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    const inputPpto = screen.getByLabelText(/Cantidad PPTO/i);
    expect(inputPpto).toBeDisabled();
  });

  it('reacciona a atajos de teclado [, ] para navegación secuencial fuera de inputs', () => {
    const onNav = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={2}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={onNav}
      />
    );

    fireEvent.keyDown(window, { key: ']' });
    expect(onNav).toHaveBeenCalledWith(1);

    fireEvent.keyDown(window, { key: '[' });
    expect(onNav).toHaveBeenCalledWith(-1);
  });

  it('ignora atajos [ y ] cuando el foco está dentro de un campo de texto', () => {
    const onNav = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={2}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={onNav}
      />
    );

    const textarea = screen.getByPlaceholderText(/Escribir una nueva observación técnica/i);
    fireEvent.keyDown(textarea, { key: ']' });
    expect(onNav).not.toHaveBeenCalled();
  });

  it('cierra el drawer al presionar Escape o botón Descartar', () => {
    const onCerrar = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={onCerrar}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    fireEvent.keyDown(window, { key: 'Escape' });
    expect(onCerrar).toHaveBeenCalledTimes(1);

    fireEvent.click(screen.getByRole('button', { name: /Descartar/i }));
    expect(onCerrar).toHaveBeenCalledTimes(2);
  });

  it('dispara onGuardar con los datos actualizados al hacer clic en Guardar Cambios o presionar ⌘S', () => {
    const onGuardar = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={onGuardar}
        onNavigateSeq={vi.fn()}
      />
    );

    // Cambiar avance real
    const inputReal = screen.getByLabelText(/Avance Real/i);
    fireEvent.change(inputReal, { target: { value: '35' } });

    // Guardar con botón
    fireEvent.click(screen.getByRole('button', { name: /Guardar Cambios/i }));

    expect(onGuardar).toHaveBeenCalledTimes(1);
    expect(onGuardar).toHaveBeenCalledWith(
      expect.objectContaining({
        unique_id: 101,
        Fecha_Inicio: '2026-08-10',
        Fecha_Fin: '2026-08-20',
        unidad: 'm³',
        cantidad_ppto: 450,
        Ejecutado: 35,
        EjecutadoRatio: 0.35,
        codigo_actividad: 'EST-01',
        Responsable_AIA: 'Ing. Carlos Restrepo',
        Sub_Contratista: 'Excavaciones del Norte S.A.S.',
      })
    );

    // Guardar con atajo ⌘S
    fireEvent.keyDown(window, { key: 's', metaKey: true });
    expect(onGuardar).toHaveBeenCalledTimes(2);
  });
});
