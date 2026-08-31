import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { expect, test, vi } from 'vitest';
import { ApiError } from '../../lib/api/cliente';
import { PanelError } from './PanelError';

test('se anuncia como alert (role="alert") con título y mensaje de la clasificación', () => {
  const error = new ApiError('no autenticado', { tipo: 'http', status: 401, codigo: 'UNAUTHENTICATED' });
  render(<PanelError error={error} />);

  const alerta = screen.getByRole('alert');
  expect(alerta).toHaveTextContent('Sesión finalizada');
  expect(alerta).toHaveTextContent('no autenticado');
});

test('nunca inserta HTML crudo: el mensaje siempre viaja como texto, no como markup', () => {
  const error = new ApiError('/api/x respondió 502 con HTML en vez del contrato esperado', {
    tipo: 'contenido_inesperado',
    status: 502,
    codigo: 'UNEXPECTED_CONTENT_TYPE',
  });
  const { container } = render(<PanelError error={error} />);

  // Ningún nodo del panel puede haberse insertado vía dangerouslySetInnerHTML con etiquetas.
  expect(container.querySelector('script, iframe, style')).toBeNull();
  expect(screen.getByRole('alert').innerHTML).not.toMatch(/&lt;html|<html/i);
});

test('muestra los campos inválidos de un 422 sin perder ninguno', () => {
  const error = new ApiError('inválido', {
    tipo: 'http',
    status: 422,
    codigo: 'VALIDATION',
    camposInvalidos: { fecha: 'La fecha es obligatoria', proyecto: 'Selecciona un proyecto' },
  });
  render(<PanelError error={error} />);

  expect(screen.getByText('fecha:')).toBeInTheDocument();
  expect(screen.getByText(/la fecha es obligatoria/i)).toBeInTheDocument();
  expect(screen.getByText(/selecciona un proyecto/i)).toBeInTheDocument();
});

test('muestra el correlationId de un 5xx cuando existe', () => {
  const error = new ApiError('fallo interno', {
    tipo: 'http',
    status: 500,
    codigo: 'INTERNAL',
    correlationId: 'corr-abc-123',
  });
  render(<PanelError error={error} />);

  expect(screen.getByText(/corr-abc-123/)).toBeInTheDocument();
});

test('sin correlationId no inventa uno ni deja el rótulo huérfano', () => {
  const error = new ApiError('fallo', { tipo: 'http', status: 500, codigo: 'INTERNAL' });
  render(<PanelError error={error} />);

  expect(screen.queryByText(/código de referencia/i)).not.toBeInTheDocument();
});

test('el botón "Reintentar" solo aparece si se pasa alReintentar, y lo invoca al hacer clic', async () => {
  const usuario = userEvent.setup();
  const alReintentar = vi.fn();
  const error = new ApiError('fallo', { tipo: 'red', codigo: 'NETWORK_ERROR' });

  const { rerender } = render(<PanelError error={error} />);
  expect(screen.queryByRole('button', { name: /reintentar/i })).not.toBeInTheDocument();

  rerender(<PanelError alReintentar={alReintentar} error={error} />);
  await usuario.click(screen.getByRole('button', { name: /reintentar/i }));
  expect(alReintentar).toHaveBeenCalledTimes(1);
});

test('el botón de salida usa la etiqueta provista y dispara su propio callback', async () => {
  const usuario = userEvent.setup();
  const onClick = vi.fn();
  const error = new ApiError('sin permiso', { tipo: 'http', status: 403, codigo: 'FORBIDDEN' });

  render(<PanelError alSalir={{ etiqueta: 'Volver al inicio', onClick }} error={error} />);
  await usuario.click(screen.getByRole('button', { name: /volver al inicio/i }));
  expect(onClick).toHaveBeenCalledTimes(1);
});
