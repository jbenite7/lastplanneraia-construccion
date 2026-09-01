import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { expect, test, vi } from 'vitest';
import { CierreCrisis } from './CierreCrisis';

const justificacionValida = 'x'.repeat(120);
const justificacionCorta = 'muy corta';

test('el botón permanece deshabilitado hasta alcanzar el mínimo de 100 caracteres (T02-AC-124)', async () => {
  const usuario = userEvent.setup();
  const cerrarCrisis = vi.fn().mockResolvedValue(undefined);

  render(<CierreCrisis alertaId={901} cerrarCrisis={cerrarCrisis} />);

  const boton = screen.getByRole('button', { name: /cerrar crisis/i });
  expect(boton).toBeDisabled();

  await usuario.type(screen.getByLabelText(/justificación/i), justificacionCorta);
  expect(boton).toBeDisabled();

  await usuario.type(screen.getByLabelText(/justificación/i), 'x'.repeat(100));
  expect(boton).toBeEnabled();
});

test('éxito llama cerrarCrisis con la justificación recortada y avisa para refrescar (T02-AC-127)', async () => {
  const usuario = userEvent.setup();
  const cerrarCrisis = vi.fn().mockResolvedValue(undefined);
  const alCerrarConExito = vi.fn();

  render(<CierreCrisis alertaId={901} cerrarCrisis={cerrarCrisis} alCerrarConExito={alCerrarConExito} />);

  await usuario.type(screen.getByLabelText(/justificación/i), `  ${justificacionValida}  `);
  await usuario.click(screen.getByRole('button', { name: /cerrar crisis/i }));

  await waitFor(() => expect(alCerrarConExito).toHaveBeenCalledOnce());
  expect(cerrarCrisis).toHaveBeenCalledWith({ alertaId: 901, justificacion: justificacionValida });
});

test('un error de cierre conserva el borrador y muestra el error (T02-AC-126)', async () => {
  const usuario = userEvent.setup();
  const cerrarCrisis = vi.fn().mockRejectedValue(new Error('fallo de red'));
  const alCerrarConExito = vi.fn();

  render(<CierreCrisis alertaId={901} cerrarCrisis={cerrarCrisis} alCerrarConExito={alCerrarConExito} />);

  const campo = screen.getByLabelText(/justificación/i);
  await usuario.type(campo, justificacionValida);
  await usuario.click(screen.getByRole('button', { name: /cerrar crisis/i }));

  await waitFor(() => expect(screen.getByRole('status')).toHaveTextContent(/no se pudo cerrar/i));
  expect(alCerrarConExito).not.toHaveBeenCalled();
  expect(campo).toHaveValue(justificacionValida);
});

test('deshabilitado (actions.close en falso) no permite enviar', async () => {
  const usuario = userEvent.setup();
  const cerrarCrisis = vi.fn().mockResolvedValue(undefined);

  render(<CierreCrisis alertaId={901} cerrarCrisis={cerrarCrisis} deshabilitado />);

  const campo = screen.getByLabelText(/justificación/i);
  expect(campo).toBeDisabled();
  const boton = screen.getByRole('button', { name: /cerrar crisis/i });
  expect(boton).toBeDisabled();

  await usuario.click(boton);
  expect(cerrarCrisis).not.toHaveBeenCalled();
});
