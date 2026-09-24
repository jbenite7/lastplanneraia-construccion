import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { ProgramaFilters } from './ProgramaFilters';

describe('ProgramaFilters', () => {
  it('renderiza el campo de búsqueda con etiqueta accesible y atajo de teclado', () => {
    render(<ProgramaFilters busqueda="" onBusquedaChange={vi.fn()} />);

    const input = screen.getByRole('textbox', {
      name: /buscar actividad, código o responsable/i,
    });
    expect(input).toBeInTheDocument();
    expect(input).toHaveAttribute('placeholder', 'Buscar actividad, código o responsable...');
    expect(screen.getByText('⌘K')).toBeInTheDocument();
  });

  it('llama onBusquedaChange al escribir en el campo', () => {
    const onBusquedaChange = vi.fn();
    render(<ProgramaFilters busqueda="" onBusquedaChange={onBusquedaChange} />);

    const input = screen.getByRole('textbox', {
      name: /buscar actividad, código o responsable/i,
    });
    fireEvent.change(input, { target: { value: 'zapatas' } });

    expect(onBusquedaChange).toHaveBeenCalledWith('zapatas');
  });

  it('muestra el botón de limpiar búsqueda cuando hay texto y lo borra al hacer clic', () => {
    const onBusquedaChange = vi.fn();
    const { rerender } = render(
      <ProgramaFilters busqueda="" onBusquedaChange={onBusquedaChange} />
    );

    expect(screen.queryByRole('button', { name: /limpiar búsqueda/i })).not.toBeInTheDocument();

    rerender(<ProgramaFilters busqueda="cimentación" onBusquedaChange={onBusquedaChange} />);
    const btnClear = screen.getByRole('button', { name: /limpiar búsqueda/i });
    expect(btnClear).toBeInTheDocument();

    fireEvent.click(btnClear);
    expect(onBusquedaChange).toHaveBeenCalledWith('');
  });

  it('muestra la barra de estado de filtros activos cuando hay búsqueda o estado', () => {
    const onBusquedaChange = vi.fn();
    const onLimpiarEstado = vi.fn();

    const { rerender } = render(
      <ProgramaFilters
        busqueda=""
        estadoFiltro={null}
        onBusquedaChange={onBusquedaChange}
        onLimpiarEstado={onLimpiarEstado}
      />
    );

    expect(screen.queryByRole('status')).not.toBeInTheDocument();

    rerender(
      <ProgramaFilters
        busqueda="muros"
        estadoFiltro="Atrasada"
        onBusquedaChange={onBusquedaChange}
        onLimpiarEstado={onLimpiarEstado}
      />
    );

    const statusBar = screen.getByRole('status');
    expect(statusBar).toBeInTheDocument();
    expect(screen.getByText(/Texto: “muros”/i)).toBeInTheDocument();
    expect(screen.getByText(/Estado: Atrasada/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /limpiar todos los filtros/i })).toBeInTheDocument();
  });

  it('permite remover filtros individuales o todos a la vez', () => {
    const onBusquedaChange = vi.fn();
    const onLimpiarEstado = vi.fn();

    render(
      <ProgramaFilters
        busqueda="vigas"
        estadoFiltro="En Curso"
        onBusquedaChange={onBusquedaChange}
        onLimpiarEstado={onLimpiarEstado}
      />
    );

    fireEvent.click(screen.getByRole('button', { name: /quitar filtro de texto/i }));
    expect(onBusquedaChange).toHaveBeenCalledWith('');

    fireEvent.click(screen.getByRole('button', { name: /quitar filtro de estado En Curso/i }));
    expect(onLimpiarEstado).toHaveBeenCalledTimes(1);

    fireEvent.click(screen.getByRole('button', { name: /limpiar todos los filtros/i }));
    expect(onBusquedaChange).toHaveBeenCalledWith('');
    expect(onLimpiarEstado).toHaveBeenCalledTimes(2);
  });

  it('renderiza contadores de visibilidad y avance macro cuando se suministran', () => {
    render(
      <ProgramaFilters
        busqueda=""
        onBusquedaChange={vi.fn()}
        totalVisibles={15}
        totalTotal={22}
        avanceMacroPct={38.4}
      />
    );

    expect(screen.getByText(/Actividades visibles:/i)).toBeInTheDocument();
    expect(screen.getByText('15 de 22')).toBeInTheDocument();
    expect(screen.getByText(/Avance macro obra:/i)).toBeInTheDocument();
    expect(screen.getByText('38.4%')).toBeInTheDocument();
  });
});
