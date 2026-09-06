import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { expect, test, vi } from 'vitest';
import { ApiError } from '../../../lib/api/cliente';
import type { Notificacion } from '../api/notificaciones';
import type { EstadoNotificaciones, UseNotificacionesApi } from '../estado/useNotificaciones';
import { BandejaNotificaciones } from './BandejaNotificaciones';

/**
 * `BandejaNotificaciones` es puramente presentacional (recibe el resultado de
 * `useNotificaciones()` ya resuelto) — estas pruebas inyectan un `UseNotificacionesApi` fabricado
 * a mano en vez de montar el hook real, igual que `DigestLps.test.tsx` inyecta sus adapters.
 */

function notificacion(overrides: Partial<Notificacion> = {}): Notificacion {
  return {
    id: 1,
    type: 'pi_restriction_lowered',
    title: 'Restricción bajó de nivel',
    message: 'Mensaje de prueba',
    itemCount: 1,
    createdAt: '2026-08-31 09:00:00',
    ...overrides,
  };
}

function apiFalsa(estado: EstadoNotificaciones, overrides: Partial<UseNotificacionesApi> = {}): UseNotificacionesApi {
  return {
    estado,
    reintentar: vi.fn(),
    marcarLeida: vi.fn().mockResolvedValue(undefined),
    ...overrides,
  };
}

test('estado "cargando" muestra un indicador accesible y ninguna lista todavía (T02-AC-146)', () => {
  render(<BandejaNotificaciones api={apiFalsa({ status: 'cargando' })} />);

  expect(screen.getByRole('status')).toHaveTextContent('Cargando notificaciones');
  expect(screen.queryByRole('list')).not.toBeInTheDocument();
});

test('T02-AC-146: una sola lista DOM, aunque haya varias notificaciones — no hay marcado duplicado desktop/móvil', () => {
  render(<BandejaNotificaciones api={apiFalsa({
    status: 'lista',
    notificaciones: [notificacion({ id: 1 }), notificacion({ id: 2, title: 'Otra' })],
    actualizando: false,
  })} />);

  expect(screen.getAllByRole('list')).toHaveLength(1);
  expect(screen.getAllByRole('listitem')).toHaveLength(2);
});

test('T02-AC-143: cada notificación expone título, mensaje y contador de grupo cuando item_count > 1', () => {
  render(<BandejaNotificaciones api={apiFalsa({
    status: 'lista',
    notificaciones: [notificacion({ itemCount: 5, title: 'Restricción bajó de nivel', message: 'Mensaje de prueba' })],
    actualizando: false,
  })} />);

  expect(screen.getByText('Restricción bajó de nivel')).toBeInTheDocument();
  expect(screen.getByText('Mensaje de prueba')).toBeInTheDocument();
  expect(screen.getByText('5')).toBeInTheDocument();
});

test('lista vacía sin error muestra el mensaje "sin notificaciones", no un error', () => {
  render(<BandejaNotificaciones api={apiFalsa({ status: 'lista', notificaciones: [], actualizando: false })} />);

  expect(screen.getByText('No hay notificaciones nuevas')).toBeInTheDocument();
  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
});

test('T02-AC-144: el contador refleja la cantidad de grupos no leídos, no la suma de item_count', () => {
  render(<BandejaNotificaciones api={apiFalsa({
    status: 'lista',
    notificaciones: [notificacion({ id: 1, itemCount: 5 }), notificacion({ id: 2, itemCount: 9 })],
    actualizando: false,
  })} />);

  expect(screen.getByLabelText('2 notificaciones sin leer')).toBeInTheDocument();
});

test('T02-AC-150: un error conserva las notificaciones previas visibles y ofrece reintento manual', () => {
  const reintentar = vi.fn();
  render(<BandejaNotificaciones api={apiFalsa(
    { status: 'error', notificacionesPrevias: [notificacion({ id: 3, title: 'Previa' })], error: new ApiError('falló', { tipo: 'red' }) },
    { reintentar },
  )} />);

  expect(screen.getByRole('alert')).toHaveTextContent('No se pudieron actualizar');
  expect(screen.getByText('Previa')).toBeInTheDocument();
});

test('reintentar() se dispara al hacer click en el botón del error', async () => {
  const reintentar = vi.fn();
  const usuario = userEvent.setup();
  render(<BandejaNotificaciones api={apiFalsa(
    { status: 'error', notificacionesPrevias: null, error: new ApiError('falló', { tipo: 'red' }) },
    { reintentar },
  )} />);

  await usuario.click(screen.getByRole('button', { name: 'Reintentar' }));

  expect(reintentar).toHaveBeenCalledTimes(1);
});

test('un error sin bandeja previa (falla desde el primer arranque) no muestra "sin notificaciones" como si fuera un éxito', () => {
  render(<BandejaNotificaciones api={apiFalsa(
    { status: 'error', notificacionesPrevias: null, error: new ApiError('falló', { tipo: 'red' }) },
  )} />);

  expect(screen.queryByText('No hay notificaciones nuevas')).not.toBeInTheDocument();
  expect(screen.queryAllByRole('listitem')).toHaveLength(0);
});

test('T02-AC-151: click en una notificación llama marcarLeida(id) — la bandeja no la retira por sí sola', async () => {
  const marcarLeida = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();
  render(<BandejaNotificaciones api={apiFalsa(
    { status: 'lista', notificaciones: [notificacion({ id: 42, title: 'Marcar esta' })], actualizando: false },
    { marcarLeida },
  )} />);

  await usuario.click(screen.getByRole('button', { name: /Marcar esta/ }));

  expect(marcarLeida).toHaveBeenCalledWith(42);
  // Sigue en el DOM: es `useNotificaciones` quien retira el ítem tras confirmar éxito, no este
  // componente — aquí no hay estado local que lo oculte optimísticamente.
  expect(screen.getByText('Marcar esta')).toBeInTheDocument();
});

test('un fallo de marcarLeida no rompe el render — el ítem sigue disponible para reintentar', async () => {
  const marcarLeida = vi.fn().mockRejectedValue(new ApiError('falló', { tipo: 'http', status: 403, codigo: 'CSRF_INVALID' }));
  const usuario = userEvent.setup();
  render(<BandejaNotificaciones api={apiFalsa(
    { status: 'lista', notificaciones: [notificacion({ id: 42, title: 'Marcar esta' })], actualizando: false },
    { marcarLeida },
  )} />);

  await usuario.click(screen.getByRole('button', { name: /Marcar esta/ }));

  expect(marcarLeida).toHaveBeenCalledWith(42);
  expect(screen.getByText('Marcar esta')).toBeInTheDocument();
});

test('actualizando en segundo plano muestra el indicador sin ocultar la lista existente', () => {
  render(<BandejaNotificaciones api={apiFalsa({
    status: 'lista',
    notificaciones: [notificacion({ id: 1 })],
    actualizando: true,
  })} />);

  expect(screen.getAllByRole('status').some((el) => el.textContent?.includes('Actualizando'))).toBe(true);
  expect(screen.getAllByRole('listitem')).toHaveLength(1);
});
