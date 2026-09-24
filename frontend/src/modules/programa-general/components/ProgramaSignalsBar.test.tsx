import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { ProgramaSignalsBar } from './ProgramaSignalsBar';

describe('ProgramaSignalsBar', () => {
  const conteos = {
    total: 22,
    atrasadas: 2,
    conAlerta: 2,
    debeIniciar: 2,
    enCurso: 11,
    futuras: 3,
    terminadas: 1,
  };

  it('renderiza todos los chips canónicos con sus contadores', () => {
    render(<ProgramaSignalsBar conteos={conteos} estadoFiltro={null} onSelectEstado={vi.fn()} />);

    expect(screen.getByText('Atrasada')).toBeInTheDocument();
    expect(screen.getByText('Con Alerta')).toBeInTheDocument();
    expect(screen.getByText('Debe Iniciar')).toBeInTheDocument();
    expect(screen.getByText('En Curso')).toBeInTheDocument();
    expect(screen.getByText('Actividad Futura')).toBeInTheDocument();
    expect(screen.getByText('Terminada')).toBeInTheDocument();

    expect(screen.getAllByText('2')).toHaveLength(3);
    expect(screen.getByText('11')).toBeInTheDocument();
    expect(screen.getByText('3')).toBeInTheDocument();
    expect(screen.getByText('1')).toBeInTheDocument();
  });

  it('cumple con el rol accesible y aria-label', () => {
    render(<ProgramaSignalsBar conteos={conteos} estadoFiltro={null} onSelectEstado={vi.fn()} />);
    const region = screen.getByRole('region', { name: 'Filtros rápidos por señal de estado' });
    expect(region).toBeInTheDocument();
  });

  it('aplica los tokens canónicos de DESIGN.md en los puntos de color', () => {
    const { container } = render(
      <ProgramaSignalsBar conteos={conteos} estadoFiltro={null} onSelectEstado={vi.fn()} />
    );

    const dots = container.querySelectorAll('.signal-dot');
    expect(dots).toHaveLength(6);

    const expectedTokens = [
      'var(--ds-color-state-critical-text)',
      'var(--ds-color-state-warning-text)',
      'var(--ds-state-solid-orange)',
      'var(--ds-color-state-info-text)',
      'var(--ds-color-state-success-text)',
      'var(--ds-text-muted)',
    ];

    dots.forEach((dot, idx) => {
      expect((dot as HTMLElement).style.backgroundColor).toBe(expectedTokens[idx]);
    });
  });

  it('refleja el estado activo con aria-pressed', () => {
    const { rerender } = render(
      <ProgramaSignalsBar conteos={conteos} estadoFiltro={null} onSelectEstado={vi.fn()} />
    );

    const btnAtrasada = screen.getByRole('button', { name: /atrasada/i });
    expect(btnAtrasada).toHaveAttribute('aria-pressed', 'false');

    rerender(
      <ProgramaSignalsBar conteos={conteos} estadoFiltro="Atrasada" onSelectEstado={vi.fn()} />
    );
    expect(btnAtrasada).toHaveAttribute('aria-pressed', 'true');
    expect(btnAtrasada.className).toContain('active');
  });

  it('llama onSelectEstado con el nombre al hacer clic en un chip inactivo', () => {
    const onSelect = vi.fn();
    render(<ProgramaSignalsBar conteos={conteos} estadoFiltro={null} onSelectEstado={onSelect} />);

    fireEvent.click(screen.getByText('Atrasada'));
    expect(onSelect).toHaveBeenCalledTimes(1);
    expect(onSelect).toHaveBeenCalledWith('Atrasada');
  });

  it('llama onSelectEstado con null al hacer clic en el chip actualmente activo (deselección)', () => {
    const onSelect = vi.fn();
    render(
      <ProgramaSignalsBar conteos={conteos} estadoFiltro="Atrasada" onSelectEstado={onSelect} />
    );

    fireEvent.click(screen.getByText('Atrasada'));
    expect(onSelect).toHaveBeenCalledTimes(1);
    expect(onSelect).toHaveBeenCalledWith(null);
  });
});
