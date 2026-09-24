import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { ProgramaToolbar } from './ProgramaToolbar';

describe('ProgramaToolbar', () => {
  const defaultProps = {
    semana: 34,
    modo13Cols: false,
    onToggleColumnas: vi.fn(),
    onOpenDrawer: vi.fn(),
    onExportCsv: vi.fn(),
    onDownloadCorteXlsx: vi.fn(),
  };

  it('renderiza título, badge de semana vigente y controles principales', () => {
    render(<ProgramaToolbar {...defaultProps} />);

    expect(screen.getByText('Programa General')).toBeInTheDocument();
    expect(screen.getByText('Semana 34 Vigente')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /8 Cols Esenciales/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /13 Cols Reales/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Drawer LPS/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /CSV/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Corte XLSX/i })).toBeInTheDocument();
  });

  it('refleja correctamente el estado activo del conmutador de columnas', () => {
    const { rerender } = render(<ProgramaToolbar {...defaultProps} modo13Cols={false} />);
    const btn8 = screen.getByRole('button', { name: /8 Cols Esenciales/i });
    const btn13 = screen.getByRole('button', { name: /13 Cols Reales/i });

    expect(btn8).toHaveAttribute('aria-pressed', 'true');
    expect(btn8.className).toContain('active');
    expect(btn13).toHaveAttribute('aria-pressed', 'false');

    rerender(<ProgramaToolbar {...defaultProps} modo13Cols={true} />);
    expect(btn8).toHaveAttribute('aria-pressed', 'false');
    expect(btn13).toHaveAttribute('aria-pressed', 'true');
    expect(btn13.className).toContain('active');
  });

  it('dispara onToggleColumnas al hacer clic en los botones de conmutación', () => {
    const onToggle = vi.fn();
    render(<ProgramaToolbar {...defaultProps} onToggleColumnas={onToggle} />);

    fireEvent.click(screen.getByRole('button', { name: /13 Cols Reales/i }));
    expect(onToggle).toHaveBeenCalledTimes(1);

    fireEvent.click(screen.getByRole('button', { name: /8 Cols Esenciales/i }));
    expect(onToggle).toHaveBeenCalledTimes(2);
  });

  it('dispara onOpenDrawer al hacer clic en el botón de Drawer LPS', () => {
    const onOpen = vi.fn();
    render(<ProgramaToolbar {...defaultProps} onOpenDrawer={onOpen} />);

    fireEvent.click(screen.getByRole('button', { name: /Drawer LPS/i }));
    expect(onOpen).toHaveBeenCalledTimes(1);
  });

  it('dispara onExportCsv y maneja estado de exportación activa', () => {
    const onExport = vi.fn();
    const { rerender } = render(
      <ProgramaToolbar {...defaultProps} onExportCsv={onExport} exportandoCsv={false} />
    );

    const btnCsv = screen.getByRole('button', { name: /CSV/i });
    fireEvent.click(btnCsv);
    expect(onExport).toHaveBeenCalledTimes(1);

    rerender(<ProgramaToolbar {...defaultProps} onExportCsv={onExport} exportandoCsv={true} />);
    const btnExportando = screen.getByRole('button', { name: /Exportando CSV/i });
    expect(btnExportando).toBeDisabled();
    expect(btnExportando).toHaveAttribute('aria-busy', 'true');
  });

  it('dispara onDownloadCorteXlsx y maneja estado de generación activa', () => {
    const onDownload = vi.fn();
    const { rerender } = render(
      <ProgramaToolbar {...defaultProps} onDownloadCorteXlsx={onDownload} generandoCorte={false} />
    );

    const btnCorte = screen.getByRole('button', { name: /Corte XLSX/i });
    fireEvent.click(btnCorte);
    expect(onDownload).toHaveBeenCalledTimes(1);

    rerender(
      <ProgramaToolbar {...defaultProps} onDownloadCorteXlsx={onDownload} generandoCorte={true} />
    );
    const btnGenerando = screen.getByRole('button', { name: /Generando/i });
    expect(btnGenerando).toBeDisabled();
    expect(btnGenerando).toHaveAttribute('aria-busy', 'true');
  });

  it('inhabilita los botones cuando deshabilitado=true o cuando no puedeDescargarCorte', () => {
    const { rerender } = render(<ProgramaToolbar {...defaultProps} deshabilitado={true} />);

    expect(screen.getByRole('button', { name: /8 Cols Esenciales/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /13 Cols Reales/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /Drawer LPS/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /CSV/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /Corte XLSX/i })).toBeDisabled();

    rerender(<ProgramaToolbar {...defaultProps} deshabilitado={false} puedeDescargarCorte={false} />);
    expect(screen.getByRole('button', { name: /Corte XLSX/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /CSV/i })).not.toBeDisabled();
  });
});
