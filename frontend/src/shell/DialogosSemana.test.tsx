import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { DialogosSemana } from './DialogosSemana';

const { mockCrear, mockEliminarUltima } = vi.hoisted(() => ({
  mockCrear: vi.fn(),
  mockEliminarUltima: vi.fn(),
}));

vi.mock('./useContextoSemana', () => ({
  useContextoSemana: () => ({
    seleccionar: vi.fn(),
    crear: mockCrear,
    eliminarUltima: mockEliminarUltima,
    seleccionando: false,
    creando: false,
    eliminando: false,
    error: null,
  }),
}));

const semana = {
  current: 2,
  options: [
    { number: 1, startsOn: '2026-08-18', endsOn: '2026-08-24' },
    { number: 2, startsOn: '2026-08-25', endsOn: '2026-08-31' },
  ],
  actions: { select: true, create: true, deleteLast: true },
};

beforeEach(() => {
  mockCrear.mockReset();
  mockEliminarUltima.mockReset();
});

describe('DialogosSemana (confirmaciones de crear / eliminar semana)', () => {
  it('no pinta nada sin diálogo o sin semana', () => {
    const { container, rerender } = render(
      <DialogosSemana alCerrar={vi.fn()} csrfToken="t" dialogo={null} recargar={vi.fn()} semana={semana} />,
    );
    expect(container).toBeEmptyDOMElement();
    rerender(<DialogosSemana alCerrar={vi.fn()} csrfToken="t" dialogo={{ vista: 'crear' }} recargar={vi.fn()} semana={null} />);
    expect(container).toBeEmptyDOMElement();
  });

  it('se abre como modal (showModal) dentro de .aia-dialog para caer en la capa superior', () => {
    const showModal = vi.spyOn(HTMLDialogElement.prototype, 'showModal');
    render(<DialogosSemana alCerrar={vi.fn()} csrfToken="t" dialogo={{ vista: 'crear' }} recargar={vi.fn()} semana={semana} />);

    expect(showModal).toHaveBeenCalledTimes(1);
    const dialogo = screen.getByRole('dialog', { name: /crear semana 3/i });
    expect(dialogo).toHaveClass('shell-week-dialog', 'aia-modal-surface');
    expect(dialogo.parentElement).toHaveClass('aia-dialog');
    showModal.mockRestore();
  });

  it('propone como fecha de inicio el día siguiente al fin de la última semana y crea con ella', async () => {
    mockCrear.mockResolvedValue(true);
    const alCerrar = vi.fn();
    render(<DialogosSemana alCerrar={alCerrar} csrfToken="t" dialogo={{ vista: 'crear' }} recargar={vi.fn()} semana={semana} />);

    expect(screen.getByLabelText('Fecha de inicio')).toHaveValue('2026-09-01');
    await userEvent.click(screen.getByRole('button', { name: 'Crear semana' }));
    expect(mockCrear).toHaveBeenCalledWith('2026-09-01');
    expect(alCerrar).toHaveBeenCalledTimes(1);
  });

  it('si crear falla, el diálogo sigue abierto', async () => {
    mockCrear.mockResolvedValue(false);
    const alCerrar = vi.fn();
    render(<DialogosSemana alCerrar={alCerrar} csrfToken="t" dialogo={{ vista: 'crear' }} recargar={vi.fn()} semana={semana} />);

    await userEvent.click(screen.getByRole('button', { name: 'Crear semana' }));
    expect(alCerrar).not.toHaveBeenCalled();
  });

  it('elimina la semana indicada (la última), no la vigente, tras confirmar', async () => {
    mockEliminarUltima.mockResolvedValue(true);
    const alCerrar = vi.fn();
    const vigenteNoEsLaUltima = { ...semana, current: 1 };
    render(<DialogosSemana alCerrar={alCerrar} csrfToken="t" dialogo={{ vista: 'eliminar', semana: 2 }} recargar={vi.fn()} semana={vigenteNoEsLaUltima} />);

    expect(screen.getByRole('dialog', { name: /eliminar semana 2/i })).toBeInTheDocument();
    expect(screen.getByText(/¿Eliminar la Semana 2 \(del 2026-08-25 al 2026-08-31\)\?/)).toBeInTheDocument();
    expect(mockEliminarUltima).not.toHaveBeenCalled();
    await userEvent.click(screen.getByRole('button', { name: 'Eliminar semana' }));
    expect(mockEliminarUltima).toHaveBeenCalledWith(2);
    expect(alCerrar).toHaveBeenCalledTimes(1);
  });

  it('Cancelar cierra sin tocar el servidor', async () => {
    const alCerrar = vi.fn();
    render(<DialogosSemana alCerrar={alCerrar} csrfToken="t" dialogo={{ vista: 'eliminar', semana: 2 }} recargar={vi.fn()} semana={semana} />);

    await userEvent.click(screen.getByRole('button', { name: 'Cancelar' }));
    expect(alCerrar).toHaveBeenCalledTimes(1);
    expect(mockEliminarUltima).not.toHaveBeenCalled();
  });

  it('cierra al recibir el evento close nativo (Escape del navegador)', () => {
    const alCerrar = vi.fn();
    render(<DialogosSemana alCerrar={alCerrar} csrfToken="t" dialogo={{ vista: 'crear' }} recargar={vi.fn()} semana={semana} />);

    screen.getByRole('dialog').dispatchEvent(new Event('close'));
    expect(alCerrar).toHaveBeenCalledTimes(1);
  });
});
