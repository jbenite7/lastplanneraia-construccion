import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { BarraContexto } from './BarraContexto';
import { DialogosSemana } from './DialogosSemana';

const { mockSeleccionar } = vi.hoisted(() => ({ mockSeleccionar: vi.fn() }));

vi.mock('./useContextoSemana', () => ({
  useContextoSemana: () => ({
    seleccionar: mockSeleccionar,
    crear: vi.fn(),
    eliminarUltima: vi.fn(),
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

describe('BarraContexto (paridad con .context-bar del legado)', () => {
  it('muestra proyecto, módulo y el chip de la semana vigente', () => {
    render(<BarraContexto csrfToken="t" modulo="Programa General" proyecto="Da Porto" recargar={vi.fn()} semana={semana} />);

    expect(document.querySelector('#shellContextBar.context-bar')).not.toBeNull();
    expect(screen.getByText('Da Porto')).toBeInTheDocument();
    expect(screen.getByText('Programa General')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Semana 2/ })).toHaveAttribute('aria-haspopup', 'menu');
  });

  it('abre el menú y cambia de semana', async () => {
    const usuario = userEvent.setup();
    render(<BarraContexto csrfToken="t" modulo="Programa General" proyecto="Da Porto" recargar={vi.fn()} semana={semana} />);

    await usuario.click(screen.getByRole('button', { name: /Semana 2/ }));
    expect(screen.getByRole('menuitem', { name: /Semana 2/ })).toHaveAttribute('aria-current', 'true');
    await usuario.click(screen.getByRole('menuitem', { name: /Semana 1/ }));
    expect(mockSeleccionar).toHaveBeenCalledWith(1);
  });

  it('cierra el menú con Escape y devuelve el foco al chip', async () => {
    const usuario = userEvent.setup();
    render(<BarraContexto csrfToken="t" modulo="Programa General" proyecto="Da Porto" recargar={vi.fn()} semana={semana} />);

    const chip = screen.getByRole('button', { name: /Semana 2/ });
    await usuario.click(chip);
    await usuario.keyboard('{Escape}');

    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
    expect(chip).toHaveFocus();
  });

  it('no se pinta sin semana', () => {
    const { container } = render(<BarraContexto csrfToken="t" modulo="M" proyecto="P" recargar={vi.fn()} semana={null} />);

    expect(container.querySelector('.context-week-chip')).toBeNull();
  });

  it('ofrece crear y eliminar solo cuando el servidor autoriza las acciones', async () => {
    const usuario = userEvent.setup();
    render(<DialogosSemana abierto alCerrar={vi.fn()} csrfToken="t" recargar={vi.fn()} semana={semana} />);

    await usuario.click(screen.getByRole('button', { name: /crear semana/i }));
    expect(screen.getByRole('dialog', { name: /crear semana 3/i })).toHaveClass('shell-week-dialog');
  });
});
