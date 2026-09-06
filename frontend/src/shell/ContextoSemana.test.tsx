import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import type { SemanaActiva } from '../lib/api/esquemas/contexto';
import { ContextoSemana } from './ContextoSemana';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function respuesta(cuerpo: unknown, estado = 200): Response {
  return new Response(JSON.stringify(cuerpo), { status: estado });
}

function semanaDe(overrides: Partial<SemanaActiva> = {}): SemanaActiva {
  return {
    current: 6,
    options: [
      { number: 5, startsOn: '2026-08-17', endsOn: '2026-08-23' },
      { number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' },
    ],
    actions: { select: true, create: true, deleteLast: true },
    ...overrides,
  };
}

afterEach(() => vi.unstubAllGlobals());

test('no renderiza nada sin semana activa (semana=null)', () => {
  const { container } = render(
    <ContextoSemana semana={null} csrfToken={csrfToken} recargar={vi.fn()} />,
  );

  expect(container).toBeEmptyDOMElement();
});

test('muestra el número y el rango de la semana actual', () => {
  const { container } = render(<ContextoSemana semana={semanaDe()} csrfToken={csrfToken} recargar={vi.fn()} />);

  const etiqueta = container.querySelector('.aia-sidebar__week-label');
  expect(etiqueta).toHaveTextContent(/semana 6/i);
  expect(etiqueta).toHaveTextContent('2026-08-24 – 2026-08-30');
});

test('solo ofrece las acciones que el servidor emite en actions', () => {
  render(
    <ContextoSemana
      semana={semanaDe({ actions: { select: true, create: false, deleteLast: false } })}
      csrfToken={csrfToken}
      recargar={vi.fn()}
    />,
  );

  expect(screen.queryByRole('button', { name: /crear semana/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('button', { name: /eliminar semana/i })).not.toBeInTheDocument();
});

test('seleccionar otra semana llama a /context/week con CSRF y refresca el estado canónico', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(
    respuesta({ ok: true, week: semanaDe({ current: 5 }) }),
  );
  vi.stubGlobal('fetch', fetchFalso);
  const recargar = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();

  render(<ContextoSemana semana={semanaDe()} csrfToken={csrfToken} recargar={recargar} />);

  await usuario.selectOptions(screen.getByLabelText(/cambiar de semana/i), '5');

  await waitFor(() => expect(recargar).toHaveBeenCalledOnce());
  expect(fetchFalso).toHaveBeenCalledWith('/context/week', expect.objectContaining({ method: 'POST' }));
  const [, opciones] = fetchFalso.mock.calls[0] as [string, RequestInit];
  expect(new Headers(opciones.headers).get('X-CSRF-Token')).toBe(csrfToken);
  expect(JSON.parse(opciones.body as string)).toEqual({ semana: 5 });
});

test('crear semana pide startsOn, llama al adaptador tipado y refresca sin mutar la lista local', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(
    respuesta({ ok: true, week: { number: 7, startsOn: '2026-08-31', endsOn: '2026-09-06' } }, 201),
  );
  vi.stubGlobal('fetch', fetchFalso);
  const recargar = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();

  render(<ContextoSemana semana={semanaDe()} csrfToken={csrfToken} recargar={recargar} />);

  await usuario.click(screen.getByRole('button', { name: /crear semana/i }));
  await usuario.type(screen.getByLabelText(/fecha de inicio/i), '2026-08-31');
  await usuario.click(screen.getByRole('button', { name: /^crear$/i }));

  await waitFor(() => expect(recargar).toHaveBeenCalledOnce());
  expect(fetchFalso).toHaveBeenCalledWith('/api/context/weeks/create', expect.objectContaining({ method: 'POST' }));
  const [, opciones] = fetchFalso.mock.calls[0] as [string, RequestInit];
  expect(JSON.parse(opciones.body as string)).toEqual({ startsOn: '2026-08-31' });
  // El diálogo se cierra tras el éxito — no queda un formulario de creación abierto sobre datos
  // que ya deberían venir del refresco canónico.
  expect(screen.queryByRole('dialog', { name: /crear nueva semana/i })).not.toBeInTheDocument();
});

test('crear semana bloqueada muestra el mensaje del servidor y no reintenta sola', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(
    respuesta(
      { ok: false, error: { code: 'SEMANA_NO_CONFIRMADA', message: 'No se puede crear la Semana 7 hasta confirmar los compromisos de la Semana 6.' } },
      409,
    ),
  );
  vi.stubGlobal('fetch', fetchFalso);
  const recargar = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();

  render(<ContextoSemana semana={semanaDe()} csrfToken={csrfToken} recargar={recargar} />);

  await usuario.click(screen.getByRole('button', { name: /crear semana/i }));
  await usuario.type(screen.getByLabelText(/fecha de inicio/i), '2026-08-31');
  await usuario.click(screen.getByRole('button', { name: /^crear$/i }));

  expect(await screen.findByRole('alert')).toHaveTextContent(/confirmar los compromisos/i);
  expect(fetchFalso).toHaveBeenCalledTimes(1); // ningún reintento automático
  expect(recargar).not.toHaveBeenCalled();
});

test('eliminar la última semana exige confirmación y refresca tras el éxito', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(
    respuesta({ ok: true, deletedWeek: 6, maxWeek: 5 }),
  );
  vi.stubGlobal('fetch', fetchFalso);
  const recargar = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();

  render(<ContextoSemana semana={semanaDe()} csrfToken={csrfToken} recargar={recargar} />);

  await usuario.click(screen.getByRole('button', { name: /eliminar semana 6/i }));
  expect(screen.getByRole('dialog', { name: /eliminar la semana 6/i })).toBeInTheDocument();

  await usuario.click(screen.getByRole('button', { name: /^eliminar$/i }));

  await waitFor(() => expect(recargar).toHaveBeenCalledOnce());
  expect(fetchFalso).toHaveBeenCalledWith('/api/context/weeks/delete-last', expect.objectContaining({ method: 'POST' }));
  const [, opciones] = fetchFalso.mock.calls[0] as [string, RequestInit];
  expect(JSON.parse(opciones.body as string)).toEqual({ week: 6 });
});
